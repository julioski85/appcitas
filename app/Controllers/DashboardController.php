<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Cita;
use App\Models\Sucursal;
use App\Models\User;

class DashboardController extends Controller
{
    public function home(): void
    {
        if (!is_logged_in()) {
            redirect('/login');
        }
        redirect('/dashboard');
    }

    public function index(): void
    {
        $user = $this->requireAuth();

        if ($user['rol'] === 'sucursal') {
            $branchId = (int)$user['sucursal_id'];

            $stats = [
                'porSucursal' => Database::select(
                    "SELECT s.nombre, s.color_calendario, COUNT(c.id) AS total
                     FROM sucursales s
                     LEFT JOIN citas c ON c.sucursal_id = s.id
                       AND DATE_FORMAT(c.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
                     WHERE s.id = :id
                     GROUP BY s.id, s.nombre, s.color_calendario",
                    ['id' => $branchId]
                ),
                'porDia' => Database::select(
                    "SELECT DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, COUNT(*) AS total
                     FROM citas
                     WHERE sucursal_id = :id
                       AND fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                     GROUP BY fecha
                     ORDER BY fecha ASC",
                    ['id' => $branchId]
                ),
                'canceladas' => (int)(Database::first(
                    "SELECT COUNT(*) AS total
                     FROM citas
                     WHERE sucursal_id = :id
                       AND estatus = 'cancelada'
                       AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
                    ['id' => $branchId]
                )['total'] ?? 0),
                'porUsuario' => Database::select(
                    "SELECT u.nombre, COUNT(c.id) AS total
                     FROM usuarios u
                     LEFT JOIN citas c ON c.creado_por = u.id
                       AND c.sucursal_id = :id
                       AND DATE_FORMAT(c.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
                     GROUP BY u.id, u.nombre
                     ORDER BY total DESC, u.nombre ASC",
                    ['id' => $branchId]
                ),
                'horarios' => Database::select(
                    "SELECT TIME_FORMAT(hora_inicio, '%H:%i') AS hora, COUNT(*) AS total
                     FROM citas
                     WHERE sucursal_id = :id
                       AND estatus <> 'cancelada'
                     GROUP BY hora_inicio
                     ORDER BY total DESC, hora_inicio ASC
                     LIMIT 8",
                    ['id' => $branchId]
                ),
                'ultimas' => Cita::latest($user, []),
            ];
        } else {
            $stats = [
                'porSucursal' => Sucursal::countsThisMonth(),
                'porDia' => Cita::citasPorDiaThisWeek(),
                'canceladas' => Cita::canceladasThisMonth(),
                'porUsuario' => User::countByUserThisMonth(),
                'horarios' => Cita::horariosMasOcupados(),
                'ultimas' => Cita::latest($user, []),
            ];
        }

        $this->view('dashboard/index', compact('stats', 'user'));
    }
}
