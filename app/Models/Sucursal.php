<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Sucursal
{
    public static function normalizeBufferMinutes($minutes, int $fallback = 5): int
    {
        if ($minutes === null || $minutes === '') {
            return $fallback;
        }

        return max(0, (int)$minutes);
    }

    public static function normalizeBusinessHour(?string $time, string $fallback): string
    {
        $value = trim((string)$time);
        if ($value === '') {
            return $fallback;
        }

        $normalized = substr($value, 0, 5);
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $normalized)) {
            return $fallback;
        }

        return $normalized;
    }

    public static function openingHour(array $sucursal): string
    {
        return self::normalizeBusinessHour($sucursal['hora_apertura'] ?? null, '08:00');
    }

    public static function closingHour(array $sucursal): string
    {
        return self::normalizeBusinessHour($sucursal['hora_cierre'] ?? null, '20:00');
    }

    public static function isRangeWithinBusinessHours(array $sucursal, string $horaInicio, string $horaFin): bool
    {
        $open = self::openingHour($sucursal);
        $close = self::closingHour($sucursal);
        $start = self::normalizeBusinessHour($horaInicio, '');
        $end = self::normalizeBusinessHour($horaFin, '');

        if ($start === '' || $end === '') {
            return false;
        }

        return $start >= $open && $end <= $close;
    }

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
        $horaApertura = self::normalizeBusinessHour($data['hora_apertura'] ?? null, '08:00');
        $horaCierre = self::normalizeBusinessHour($data['hora_cierre'] ?? null, '20:00');

        return Database::execute(
            "INSERT INTO sucursales (nombre, direccion, telefono, color_calendario, capacidad_simultanea, hora_apertura, hora_cierre, buffer_minutos, created_at, updated_at)
             VALUES (:nombre, :direccion, :telefono, :color, :capacidad_simultanea, :hora_apertura, :hora_cierre, :buffer_minutos, NOW(), NOW())",
            [
                'nombre' => $data['nombre'],
                'direccion' => $data['direccion'],
                'telefono' => $data['telefono'],
                'color' => $data['color_calendario'],
                'capacidad_simultanea' => max(1, (int)$data['capacidad_simultanea']),
                'hora_apertura' => $horaApertura,
                'hora_cierre' => $horaCierre,
                'buffer_minutos' => self::normalizeBufferMinutes($data['buffer_minutos'] ?? null),
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        $horaApertura = self::normalizeBusinessHour($data['hora_apertura'] ?? null, '08:00');
        $horaCierre = self::normalizeBusinessHour($data['hora_cierre'] ?? null, '20:00');

        return Database::execute(
            "UPDATE sucursales
             SET nombre = :nombre, direccion = :direccion, telefono = :telefono, color_calendario = :color, capacidad_simultanea = :capacidad_simultanea, hora_apertura = :hora_apertura, hora_cierre = :hora_cierre, buffer_minutos = :buffer_minutos, updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'nombre' => $data['nombre'],
                'direccion' => $data['direccion'],
                'telefono' => $data['telefono'],
                'color' => $data['color_calendario'],
                'capacidad_simultanea' => max(1, (int)$data['capacidad_simultanea']),
                'hora_apertura' => $horaApertura,
                'hora_cierre' => $horaCierre,
                'buffer_minutos' => self::normalizeBufferMinutes($data['buffer_minutos'] ?? null),
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
