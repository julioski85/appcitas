<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        return Database::first(
            "SELECT u.*, s.nombre AS sucursal_nombre
             FROM usuarios u
             LEFT JOIN sucursales s ON s.id = u.sucursal_id
             WHERE u.email = :email LIMIT 1",
            ['email' => $email]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first("SELECT * FROM usuarios WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function countByUserThisMonth(): array
    {
        return Database::select(
            "SELECT u.nombre, COUNT(c.id) AS total
             FROM usuarios u
             LEFT JOIN citas c ON c.creado_por = u.id
               AND DATE_FORMAT(c.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
             GROUP BY u.id, u.nombre
             ORDER BY total DESC, u.nombre ASC"
        );
    }
}
