<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Cita
{
    public static function filtersForUser(array $user, array $filters = []): array
    {
        $where = [];
        $params = [];

        if ($user['rol'] === 'sucursal') {
            $where[] = 'c.sucursal_id = :user_sucursal_id';
            $params['user_sucursal_id'] = $user['sucursal_id'];
        } elseif (!empty($filters['sucursal_id'])) {
            $where[] = 'c.sucursal_id = :sucursal_id';
            $params['sucursal_id'] = (int)$filters['sucursal_id'];
        }

        if (!empty($filters['start'])) {
            $where[] = 'c.fecha >= :start';
            $params['start'] = $filters['start'];
        }

        if (!empty($filters['end'])) {
            $where[] = 'c.fecha <= :end';
            $params['end'] = $filters['end'];
        }

        if (!empty($filters['estatus'])) {
            $where[] = 'c.estatus = :estatus';
            $params['estatus'] = $filters['estatus'];
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$sqlWhere, $params];
    }

    public static function calendarEvents(array $user, array $filters = []): array
    {
        [$where, $params] = self::filtersForUser($user, $filters);

        return Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre, s.color_calendario
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             $where
             ORDER BY c.fecha ASC, c.hora_inicio ASC",
            $params
        );
    }

    public static function latest(array $user, array $filters = []): array
    {
        [$where, $params] = self::filtersForUser($user, $filters);

        return Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre, s.color_calendario, u.nombre AS creador_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             LEFT JOIN usuarios u ON u.id = c.creado_por
             $where
             ORDER BY c.fecha DESC, c.hora_inicio DESC
             LIMIT 20",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            "SELECT c.*, s.nombre AS sucursal_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function create(array $data, int $userId): bool
    {
        return Database::execute(
            "INSERT INTO citas
             (sucursal_id, cliente_nombre, cliente_telefono, servicio, fecha, hora_inicio, hora_fin, estatus, creado_por, origen, created_at, updated_at)
             VALUES
             (:sucursal_id, :cliente_nombre, :cliente_telefono, :servicio, :fecha, :hora_inicio, :hora_fin, :estatus, :creado_por, :origen, NOW(), NOW())",
            [
                'sucursal_id' => $data['sucursal_id'],
                'cliente_nombre' => $data['cliente_nombre'],
                'cliente_telefono' => $data['cliente_telefono'],
                'servicio' => $data['servicio'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'estatus' => $data['estatus'],
                'creado_por' => $userId,
                'origen' => $data['origen'],
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE citas
             SET sucursal_id = :sucursal_id,
                 cliente_nombre = :cliente_nombre,
                 cliente_telefono = :cliente_telefono,
                 servicio = :servicio,
                 fecha = :fecha,
                 hora_inicio = :hora_inicio,
                 hora_fin = :hora_fin,
                 estatus = :estatus,
                 origen = :origen,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'sucursal_id' => $data['sucursal_id'],
                'cliente_nombre' => $data['cliente_nombre'],
                'cliente_telefono' => $data['cliente_telefono'],
                'servicio' => $data['servicio'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'estatus' => $data['estatus'],
                'origen' => $data['origen'],
            ]
        );
    }

    public static function delete(int $id): bool
    {
        return Database::execute("DELETE FROM citas WHERE id = :id", ['id' => $id]);
    }

    public static function cancel(int $id): bool
    {
        return Database::execute(
            "UPDATE citas SET estatus = 'cancelada', updated_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }

    public static function hasConflict(array $data, ?int $ignoreId = null): bool
    {
        $sql = "SELECT id
                FROM citas
                WHERE sucursal_id = :sucursal_id
                  AND fecha = :fecha
                  AND estatus <> 'cancelada'
                  AND hora_inicio < :hora_fin
                  AND hora_fin > :hora_inicio";
        $params = [
            'sucursal_id' => $data['sucursal_id'],
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
        ];

        if ($ignoreId) {
            $sql .= " AND id <> :ignore_id";
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= " LIMIT 1";

        return Database::first($sql, $params) !== null;
    }

    public static function citasPorDiaThisWeek(): array
    {
        return Database::select(
            "SELECT DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, COUNT(*) AS total
             FROM citas
             WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
             GROUP BY fecha
             ORDER BY fecha ASC"
        );
    }

    public static function canceladasThisMonth(): int
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total
             FROM citas
             WHERE estatus = 'cancelada'
               AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"
        );
        return (int)($row['total'] ?? 0);
    }

    public static function horariosMasOcupados(): array
    {
        return Database::select(
            "SELECT TIME_FORMAT(hora_inicio, '%H:%i') AS hora, COUNT(*) AS total
             FROM citas
             WHERE estatus <> 'cancelada'
             GROUP BY hora_inicio
             ORDER BY total DESC, hora_inicio ASC
             LIMIT 8"
        );
    }

    public static function availableSlots(int $sucursalId, string $fecha, string $start = '08:00', string $end = '20:00', int $interval = 30): array
    {
        $events = Database::select(
            "SELECT hora_inicio, hora_fin
             FROM citas
             WHERE sucursal_id = :sucursal_id
               AND fecha = :fecha
               AND estatus <> 'cancelada'
             ORDER BY hora_inicio ASC",
            ['sucursal_id' => $sucursalId, 'fecha' => $fecha]
        );

        $slots = [];
        $current = strtotime($fecha . ' ' . $start);
        $last = strtotime($fecha . ' ' . $end);

        while ($current < $last) {
            $slotStart = date('H:i', $current);
            $slotEnd = date('H:i', strtotime("+{$interval} minutes", $current));
            $available = true;
            foreach ($events as $event) {
                if ($slotStart < substr($event['hora_fin'], 0, 5) && $slotEnd > substr($event['hora_inicio'], 0, 5)) {
                    $available = false;
                    break;
                }
            }
            $slots[] = [
                'hora_inicio' => $slotStart,
                'hora_fin' => $slotEnd,
                'disponible' => $available,
            ];
            $current = strtotime("+{$interval} minutes", $current);
        }

        return $slots;
    }
}
