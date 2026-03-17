<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Programa
{
    public static function allForUser(array $user): array
    {
        if ($user['rol'] === 'admin') {
            return Database::select(
                "SELECT p.*, s.nombre AS sucursal_nombre
                 FROM programas p
                 LEFT JOIN sucursales s ON s.id = p.sucursal_id
                 ORDER BY p.nombre ASC"
            );
        }

        return Database::select(
            "SELECT p.*, s.nombre AS sucursal_nombre
             FROM programas p
             LEFT JOIN sucursales s ON s.id = p.sucursal_id
             WHERE p.sucursal_id IS NULL OR p.sucursal_id = :sucursal_id
             ORDER BY p.nombre ASC",
            ['sucursal_id' => (int)$user['sucursal_id']]
        );
    }

    public static function optionsForSucursal(int $sucursalId, ?int $selectedId = null): array
    {
        $sql = "SELECT id, nombre, activo
                FROM programas
                WHERE (sucursal_id IS NULL OR sucursal_id = :sucursal_id)
                  AND activo = 1";
        $params = ['sucursal_id' => $sucursalId];

        if ($selectedId) {
            $sql = "SELECT id, nombre, activo
                    FROM programas
                    WHERE (sucursal_id IS NULL OR sucursal_id = :sucursal_id)
                      AND (activo = 1 OR id = :selected_id)";
            $params['selected_id'] = $selectedId;
        }

        $sql .= ' ORDER BY nombre ASC';
        return Database::select($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            "SELECT p.*, s.nombre AS sucursal_nombre
             FROM programas p
             LEFT JOIN sucursales s ON s.id = p.sucursal_id
             WHERE p.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function findVisibleForUser(int $id, array $user): ?array
    {
        if ($user['rol'] === 'admin') {
            return self::find($id);
        }

        return Database::first(
            "SELECT p.*, s.nombre AS sucursal_nombre
             FROM programas p
             LEFT JOIN sucursales s ON s.id = p.sucursal_id
             WHERE p.id = :id
               AND (p.sucursal_id IS NULL OR p.sucursal_id = :sucursal_id)
             LIMIT 1",
            [
                'id' => $id,
                'sucursal_id' => (int)$user['sucursal_id'],
            ]
        );
    }

    public static function create(array $data): bool
    {
        return Database::execute(
            "INSERT INTO programas (nombre, sucursal_id, activo, created_at, updated_at)
             VALUES (:nombre, :sucursal_id, :activo, NOW(), NOW())",
            [
                'nombre' => $data['nombre'],
                'sucursal_id' => $data['sucursal_id'] !== '' ? (int)$data['sucursal_id'] : null,
                'activo' => (int)$data['activo'],
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE programas
             SET nombre = :nombre,
                 sucursal_id = :sucursal_id,
                 activo = :activo,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'nombre' => $data['nombre'],
                'sucursal_id' => $data['sucursal_id'] !== '' ? (int)$data['sucursal_id'] : null,
                'activo' => (int)$data['activo'],
            ]
        );
    }

    public static function toggle(int $id): bool
    {
        return Database::execute("UPDATE programas SET activo = IF(activo = 1, 0, 1), updated_at = NOW() WHERE id = :id", ['id' => $id]);
    }
}
