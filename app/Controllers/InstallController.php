<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use Throwable;

class InstallController extends Controller
{
    private function schemaStatements(): array
    {
        $schemaPath = dirname(__DIR__, 2) . '/database/schema.sql';
        if (!is_file($schemaPath) || !is_readable($schemaPath)) {
            throw new \RuntimeException('No se encontró el archivo database/schema.sql para ejecutar la instalación.');
        }

        $contents = (string)file_get_contents($schemaPath);
        $contents = preg_replace('/^\s*--.*$/m', '', $contents) ?? '';
        $parts = preg_split('/;\s*(?:\r?\n|$)/', $contents) ?: [];

        $statements = [];
        foreach ($parts as $part) {
            $statement = trim($part);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

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

            foreach ($this->schemaStatements() as $statement) {
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
