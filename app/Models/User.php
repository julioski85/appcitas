<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function all(): array
    {
        return Database::select(
            "SELECT u.*, s.nombre AS sucursal_nombre
             FROM usuarios u
             LEFT JOIN sucursales s ON s.id = u.sucursal_id
             ORDER BY u.created_at DESC, u.id DESC"
        );
    }

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

    public static function findByEmailExceptId(string $email, int $id): ?array
    {
        return Database::first(
            "SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1",
            ['email' => $email, 'id' => $id]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            "SELECT u.*, s.nombre AS sucursal_nombre
             FROM usuarios u
             LEFT JOIN sucursales s ON s.id = u.sucursal_id
             WHERE u.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function create(array $data): bool
    {
        return Database::execute(
            "INSERT INTO usuarios (nombre, email, password, rol, sucursal_id, activo, created_at, updated_at)
             VALUES (:nombre, :email, :password, :rol, :sucursal_id, :activo, NOW(), NOW())",
            [
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password' => $data['password'],
                'rol' => $data['rol'],
                'sucursal_id' => $data['sucursal_id'],
                'activo' => $data['activo'],
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE usuarios
             SET nombre = :nombre,
                 email = :email,
                 rol = :rol,
                 sucursal_id = :sucursal_id,
                 activo = :activo,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'rol' => $data['rol'],
                'sucursal_id' => $data['sucursal_id'],
                'activo' => $data['activo'],
            ]
        );
    }

    public static function updatePassword(int $id, string $hashedPassword): bool
    {
        return Database::execute(
            "UPDATE usuarios SET password = :password, updated_at = NOW() WHERE id = :id",
            [
                'id' => $id,
                'password' => $hashedPassword,
            ]
        );
    }

    public static function setActivo(int $id, int $activo): bool
    {
        return Database::execute(
            "UPDATE usuarios SET activo = :activo, updated_at = NOW() WHERE id = :id",
            [
                'id' => $id,
                'activo' => $activo,
            ]
        );
    }

    public static function countActiveAdmins(): int
    {
        $row = Database::first("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin' AND activo = 1");
        return (int)($row['total'] ?? 0);
    }

    public static function countByUserThisMonth(): array
    {
        return Database::select(
            "SELECT u.nombre, COUNT(c.id) AS total
             FROM usuarios u
             INNER JOIN citas c ON c.creado_por = u.id
               AND DATE_FORMAT(c.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
             GROUP BY u.id, u.nombre
             HAVING COUNT(c.id) > 0
             ORDER BY total DESC, u.nombre ASC"
        );
    }
}
