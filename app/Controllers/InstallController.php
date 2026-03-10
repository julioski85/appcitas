<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Throwable;

class InstallController extends Controller
{
    public function index(): void
    {
        $status = [
            'config_ok' => config('db.password') !== 'AQUI_TU_PASSWORD' && config('security.api_key') !== 'CAMBIA_ESTA_API_KEY_SEGURA',
            'db_connected' => false,
            'installed' => false,
            'message' => '',
        ];

        try {
            Database::boot(config('db'));
            $status['db_connected'] = true;
            $stmt = Database::pdo()->query("SHOW TABLES LIKE 'usuarios'");
            $status['installed'] = (bool)$stmt?->fetch();
        } catch (Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        $this->view('install/index', compact('status'));
    }

    public function run(): void
    {
        verify_csrf();

        try {
            Database::boot(config('db'));
            $pdo = Database::pdo();

            $sql = [
                "CREATE TABLE IF NOT EXISTS sucursales (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(150) NOT NULL,
                    direccion VARCHAR(255) NOT NULL,
                    telefono VARCHAR(30) NOT NULL,
                    color_calendario VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS usuarios (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(150) NOT NULL,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    rol ENUM('admin','call_center','sucursal') NOT NULL DEFAULT 'sucursal',
                    sucursal_id INT UNSIGNED NULL,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    CONSTRAINT fk_usuarios_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "CREATE TABLE IF NOT EXISTS citas (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    sucursal_id INT UNSIGNED NOT NULL,
                    cliente_nombre VARCHAR(150) NOT NULL,
                    cliente_telefono VARCHAR(30) NOT NULL,
                    servicio VARCHAR(150) NOT NULL,
                    fecha DATE NOT NULL,
                    hora_inicio TIME NOT NULL,
                    hora_fin TIME NOT NULL,
                    estatus ENUM('agendada','cancelada','atendida') NOT NULL DEFAULT 'agendada',
                    creado_por INT UNSIGNED NOT NULL,
                    origen ENUM('call_center','sucursal','web') NOT NULL DEFAULT 'web',
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    INDEX idx_sucursal_fecha (sucursal_id, fecha),
                    INDEX idx_fecha_horas (fecha, hora_inicio, hora_fin),
                    CONSTRAINT fk_citas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
                    CONSTRAINT fk_citas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            ];

            foreach ($sql as $statement) {
                $pdo->exec($statement);
            }

            $row = Database::first("SELECT COUNT(*) AS total FROM sucursales");
            if ((int)($row['total'] ?? 0) === 0) {
                $seedSucursales = [
                    ['Sucursal 1', 'Dirección sucursal 1', '5550000001', '#3b82f6'],
                    ['Sucursal 2', 'Dirección sucursal 2', '5550000002', '#8b5cf6'],
                    ['Sucursal 3', 'Dirección sucursal 3', '5550000003', '#10b981'],
                    ['Sucursal 4', 'Dirección sucursal 4', '5550000004', '#f59e0b'],
                    ['Sucursal 5', 'Dirección sucursal 5', '5550000005', '#ef4444'],
                    ['Sucursal 6', 'Dirección sucursal 6', '5550000006', '#06b6d4'],
                    ['Sucursal 7', 'Dirección sucursal 7', '5550000007', '#ec4899'],
                ];
                foreach ($seedSucursales as $sucursal) {
                    Database::execute(
                        "INSERT INTO sucursales (nombre, direccion, telefono, color_calendario, created_at, updated_at)
                         VALUES (:nombre, :direccion, :telefono, :color, NOW(), NOW())",
                        [
                            'nombre' => $sucursal[0],
                            'direccion' => $sucursal[1],
                            'telefono' => $sucursal[2],
                            'color' => $sucursal[3],
                        ]
                    );
                }
            }

            $rowUsers = Database::first("SELECT COUNT(*) AS total FROM usuarios");
            if ((int)($rowUsers['total'] ?? 0) === 0) {
                Database::execute(
                    "INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
                     VALUES (:nombre, :email, :password, :rol, :sucursal_id, :activo, NOW(), NOW())",
                    [
                        'nombre' => 'Admin Demo',
                        'email' => 'admin.demo@citas.local',
                        'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
                        'rol' => 'admin',
                        'sucursal_id' => null,
                        'activo' => 1,
                    ]
                );

                Database::execute(
                    "INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
                     VALUES (:nombre, :email, :password, :rol, :sucursal_id, :activo, NOW(), NOW())",
                    [
                        'nombre' => 'Call Center Demo',
                        'email' => 'callcenter.demo@citas.local',
                        'password' => password_hash('Call123!', PASSWORD_DEFAULT),
                        'rol' => 'call_center',
                        'sucursal_id' => null,
                        'activo' => 1,
                    ]
                );

                Database::execute(
                    "INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
                     VALUES (:nombre, :email, :password, :rol, :sucursal_id, :activo, NOW(), NOW())",
                    [
                        'nombre' => 'Sucursal Demo',
                        'email' => 'sucursal.demo@citas.local',
                        'password' => password_hash('Sucursal123!', PASSWORD_DEFAULT),
                        'rol' => 'sucursal',
                        'sucursal_id' => 1,
                        'activo' => 1,
                    ]
                );
            }

            set_flash('success', 'Sistema instalado correctamente. Ya puedes iniciar sesión.');
            redirect('/login');
        } catch (Throwable $e) {
            set_flash('error', 'No se pudo instalar: ' . $e->getMessage());
            redirect('/install');
        }
    }
}
