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
                     WHERE u.sucursal_id = :id OR u.rol = 'admin'
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
                'prospectos_nuevos' => (int)(Database::first("SELECT COUNT(*) AS total FROM clientes WHERE estatus_cliente = 'prospecto' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') AND sucursal_id = :id", ['id' => $branchId])['total'] ?? 0),
                'clientes_activos' => (int)(Database::first("SELECT COUNT(*) AS total FROM clientes WHERE estatus_cliente = 'cliente_activo' AND sucursal_id = :id", ['id' => $branchId])['total'] ?? 0),
                'no_asistidas' => Cita::noAsistidasThisMonth($branchId),
                'conversion' => (float)(Database::first("SELECT IFNULL((SUM(estatus_cliente IN ('asistio_primera_vez','cliente_activo')) / NULLIF(COUNT(*),0)) * 100, 0) AS tasa FROM clientes WHERE sucursal_id = :id", ['id' => $branchId])['tasa'] ?? 0),
            ];
        } else {
            $stats = [
                'porSucursal' => Sucursal::countsThisMonth(),
                'porDia' => Cita::citasPorDiaThisWeek(),
                'canceladas' => Cita::canceladasThisMonth(),
                'porUsuario' => User::countByUserThisMonth(),
                'horarios' => Cita::horariosMasOcupados(),
                'ultimas' => Cita::latest($user, []),
                'prospectos_nuevos' => (int)(Database::first("SELECT COUNT(*) AS total FROM clientes WHERE estatus_cliente = 'prospecto' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")['total'] ?? 0),
                'clientes_activos' => (int)(Database::first("SELECT COUNT(*) AS total FROM clientes WHERE estatus_cliente = 'cliente_activo'")['total'] ?? 0),
                'no_asistidas' => Cita::noAsistidasThisMonth(),
                'conversion' => (float)(Database::first("SELECT IFNULL((SUM(estatus_cliente IN ('asistio_primera_vez','cliente_activo')) / NULLIF(COUNT(*),0)) * 100, 0) AS tasa FROM clientes")['tasa'] ?? 0),
            ];
        }

        $this->view('dashboard/index', compact('stats', 'user'));
    }
}
