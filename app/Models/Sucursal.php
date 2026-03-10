<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Sucursal
{
    public static function all(): array
    {
        return Database::select("SELECT * FROM sucursales ORDER BY nombre ASC");
    }

    public static function find(int $id): ?array
    {
        return Database::first("SELECT * FROM sucursales WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function create(array $data): bool
    {
        return Database::execute(
            "INSERT INTO sucursales (nombre, direccion, telefono, color_calendario, created_at, updated_at)
             VALUES (:nombre, :direccion, :telefono, :color, NOW(), NOW())",
            [
                'nombre' => $data['nombre'],
                'direccion' => $data['direccion'],
                'telefono' => $data['telefono'],
                'color' => $data['color_calendario'],
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE sucursales
             SET nombre = :nombre, direccion = :direccion, telefono = :telefono, color_calendario = :color, updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'nombre' => $data['nombre'],
                'direccion' => $data['direccion'],
                'telefono' => $data['telefono'],
                'color' => $data['color_calendario'],
            ]
        );
    }

    public static function delete(int $id): bool
    {
        return Database::execute("DELETE FROM sucursales WHERE id = :id", ['id' => $id]);
    }

    public static function countsThisMonth(): array
    {
        return Database::select(
            "SELECT s.nombre, s.color_calendario, COUNT(c.id) AS total
             FROM sucursales s
             LEFT JOIN citas c ON c.sucursal_id = s.id
               AND DATE_FORMAT(c.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
             GROUP BY s.id, s.nombre, s.color_calendario
             ORDER BY total DESC, s.nombre ASC"
        );
    }
}
