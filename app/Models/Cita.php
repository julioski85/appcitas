<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Cita
{
    public const ESTATUS = [
        'agendada',
        'confirmada',
        'asistio',
        'no_asistio',
        'cancelada',
        'reprogramada',
    ];

    public const SERVICIOS = [
        'Consulta general',
        'Ozonoterapia',
        'Plasma rico en plaquetas',
        'Cámara hiperbárica',
        'Fisioterapia',
        'Sueroterapia',
    ];

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
            "SELECT c.*, s.nombre AS sucursal_nombre, s.color_calendario, cl.nombre_completo AS cliente_real_nombre, p.nombre AS programa_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             LEFT JOIN programas p ON p.id = c.programa_id
             $where
             ORDER BY c.fecha ASC, c.hora_inicio ASC",
            $params
        );
    }

    public static function latest(array $user, array $filters = []): array
    {
        [$where, $params] = self::filtersForUser($user, $filters);

        return Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre, s.color_calendario, u.nombre AS creador_nombre, cl.nombre_completo AS cliente_real_nombre, p.nombre AS programa_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             LEFT JOIN usuarios u ON u.id = c.creado_por
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             LEFT JOIN programas p ON p.id = c.programa_id
             $where
             ORDER BY c.fecha DESC, c.hora_inicio DESC
             LIMIT 20",
            $params
        );
    }

    public static function paginated(array $user, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        [$whereSql, $params] = self::filtersForUser($user, $filters);
        $whereParts = [];

        if ($whereSql !== '') {
            $whereParts[] = preg_replace('/^WHERE\s+/i', '', $whereSql);
        }

        if (!empty($filters['q'])) {
            $whereParts[] = '(c.id = :search_id OR c.cliente_nombre LIKE :search OR c.cliente_telefono LIKE :search OR c.servicio LIKE :search)';
            $params['search'] = '%' . trim((string)$filters['q']) . '%';
            $params['search_id'] = (int)$filters['q'];
        }

        $where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
        $countRow = Database::first(
            "SELECT COUNT(*) AS total
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             $where",
            $params
        );

        $total = (int)($countRow['total'] ?? 0);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre, s.color_calendario, u.nombre AS creador_nombre, cl.nombre_completo AS cliente_real_nombre, p.nombre AS programa_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             LEFT JOIN usuarios u ON u.id = c.creado_por
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             LEFT JOIN programas p ON p.id = c.programa_id
             $where
             ORDER BY c.fecha DESC, c.hora_inicio DESC, c.id DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'items' => $rows,
            'total' => $total,
        ];
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            "SELECT c.*, s.nombre AS sucursal_nombre, cl.nombre_completo AS cliente_real_nombre, cl.telefono AS cliente_real_telefono, p.nombre AS programa_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             LEFT JOIN clientes cl ON cl.id = c.cliente_id
             LEFT JOIN programas p ON p.id = c.programa_id
             WHERE c.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public static function create(array $data, int $userId): int
    {
        Database::execute(
            "INSERT INTO citas
             (sucursal_id, cliente_id, cliente_nombre, cliente_telefono, servicio, codigo_promocion, programa_id, fecha, hora_inicio, hora_fin, estatus, creado_por, origen, created_at, updated_at)
             VALUES
             (:sucursal_id, :cliente_id, :cliente_nombre, :cliente_telefono, :servicio, :codigo_promocion, :programa_id, :fecha, :hora_inicio, :hora_fin, :estatus, :creado_por, :origen, NOW(), NOW())",
            [
                'sucursal_id' => $data['sucursal_id'],
                'cliente_id' => $data['cliente_id'] ?: null,
                'cliente_nombre' => $data['cliente_nombre'],
                'cliente_telefono' => $data['cliente_telefono'],
                'servicio' => $data['servicio'],
                'codigo_promocion' => $data['codigo_promocion'],
                'programa_id' => $data['programa_id'] ?: null,
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'estatus' => $data['estatus'],
                'creado_por' => $userId,
                'origen' => $data['origen'],
            ]
        );

        $id = (int)Database::lastInsertId();
        if (!empty($data['cliente_id'])) {
            Cliente::updateStatusAndCitas((int)$data['cliente_id']);
        }
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $old = self::find($id);

        $ok = Database::execute(
            "UPDATE citas
             SET sucursal_id = :sucursal_id,
                 cliente_id = :cliente_id,
                 cliente_nombre = :cliente_nombre,
                 cliente_telefono = :cliente_telefono,
                 servicio = :servicio,
                 codigo_promocion = :codigo_promocion,
                 programa_id = :programa_id,
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
                'cliente_id' => $data['cliente_id'] ?: null,
                'cliente_nombre' => $data['cliente_nombre'],
                'cliente_telefono' => $data['cliente_telefono'],
                'servicio' => $data['servicio'],
                'codigo_promocion' => $data['codigo_promocion'],
                'programa_id' => $data['programa_id'] ?: null,
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'estatus' => $data['estatus'],
                'origen' => $data['origen'],
            ]
        );

        if (!empty($data['cliente_id'])) {
            Cliente::updateStatusAndCitas((int)$data['cliente_id']);
        }
        if (!empty($old['cliente_id']) && (int)$old['cliente_id'] !== (int)($data['cliente_id'] ?? 0)) {
            Cliente::updateStatusAndCitas((int)$old['cliente_id']);
        }

        return $ok;
    }

    public static function delete(int $id): bool
    {
        $existing = self::find($id);
        $ok = Database::execute("DELETE FROM citas WHERE id = :id", ['id' => $id]);
        if ($ok && !empty($existing['cliente_id'])) {
            Cliente::updateStatusAndCitas((int)$existing['cliente_id']);
        }
        return $ok;
    }

    public static function cancel(int $id): bool
    {
        $existing = self::find($id);
        $ok = Database::execute(
            "UPDATE citas SET estatus = 'cancelada', updated_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
        if ($ok && !empty($existing['cliente_id'])) {
            Cliente::updateStatusAndCitas((int)$existing['cliente_id']);
        }
        return $ok;
    }

    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::ESTATUS, true)) {
            return false;
        }

        $existing = self::find($id);
        $ok = Database::execute(
            "UPDATE citas SET estatus = :estatus, updated_at = NOW() WHERE id = :id",
            [
                'estatus' => $status,
                'id' => $id,
            ]
        );

        if ($ok && !empty($existing['cliente_id'])) {
            Cliente::updateStatusAndCitas((int)$existing['cliente_id']);
        }

        return $ok;
    }

    public static function overlappingCount(array $data, ?int $ignoreId = null): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM citas
                WHERE sucursal_id = :sucursal_id
                  AND fecha = :fecha
                  AND estatus <> 'cancelada'
                  AND NOT (hora_fin <= :hora_inicio OR hora_inicio >= :hora_fin)";
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

        $row = Database::first($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    public static function hasConflict(array $data, ?int $ignoreId = null): bool
    {
        return self::overlappingCount($data, $ignoreId) > 0;
    }

    public static function hasCapacityConflict(array $data, int $capacidadSimultanea, ?int $ignoreId = null): bool
    {
        if ($capacidadSimultanea <= 1) {
            return self::hasConflict($data, $ignoreId);
        }

        return self::overlappingCount($data, $ignoreId) >= $capacidadSimultanea;
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

    public static function noAsistidasThisMonth(?int $sucursalId = null): int
    {
        $sql = "SELECT COUNT(*) AS total FROM citas WHERE estatus = 'no_asistio' AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $params = [];
        if ($sucursalId) {
            $sql .= ' AND sucursal_id = :sucursal_id';
            $params['sucursal_id'] = $sucursalId;
        }
        return (int)(Database::first($sql, $params)['total'] ?? 0);
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


    public static function overdueOperationalSummary(array $filters = []): array
    {
        $where = ["TIMESTAMP(c.fecha, c.hora_fin) < NOW()"];
        $params = [];

        if (!empty($filters['sucursal_id'])) {
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

        $row = Database::first(
            "SELECT
                SUM(c.estatus = 'agendada') AS vencidas_agendada,
                SUM(c.estatus = 'confirmada') AS vencidas_confirmada,
                SUM(c.estatus = 'asistio') AS cerradas_asistio,
                SUM(c.estatus = 'no_asistio') AS cerradas_no_asistio
             FROM citas c
             WHERE " . implode(' AND ', $where),
            $params
        ) ?: [];

        return [
            'vencidas_agendada' => (int)($row['vencidas_agendada'] ?? 0),
            'vencidas_confirmada' => (int)($row['vencidas_confirmada'] ?? 0),
            'cerradas_asistio' => (int)($row['cerradas_asistio'] ?? 0),
            'cerradas_no_asistio' => (int)($row['cerradas_no_asistio'] ?? 0),
        ];
    }

    public static function overduePendingOperationalList(array $user, array $filters = []): array
    {
        $where = [
            "c.estatus IN ('agendada','confirmada')",
            "TIMESTAMP(c.fecha, c.hora_fin) < NOW()",
        ];
        $params = [];

        if ($user['rol'] === 'sucursal') {
            $where[] = 'c.sucursal_id = :user_sucursal_id';
            $params['user_sucursal_id'] = (int)$user['sucursal_id'];
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

        return Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre,
                    TIMESTAMPDIFF(MINUTE, TIMESTAMP(c.fecha, c.hora_fin), NOW()) AS minutos_desde_vencimiento
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.fecha DESC, c.hora_fin DESC
             LIMIT 100",
            $params
        );
    }


    private static function rangesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $aStart = strtotime('2000-01-01 ' . substr($startA, 0, 5));
        $aEnd = strtotime('2000-01-01 ' . substr($endA, 0, 5));
        $bStart = strtotime('2000-01-01 ' . substr($startB, 0, 5));
        $bEnd = strtotime('2000-01-01 ' . substr($endB, 0, 5));

        // Intervalos semiabiertos [inicio, fin): si una cita termina justo cuando otra inicia, no empalman.
        return $aStart < $bEnd && $aEnd > $bStart;
    }

    public static function availableSlots(int $sucursalId, string $fecha, string $start = '08:00', string $end = '20:00', int $interval = 30): array
    {
        $sucursal = Sucursal::find($sucursalId);
        if (!$sucursal) {
            return [];
        }

        $start = Sucursal::openingHour($sucursal);
        $end = Sucursal::closingHour($sucursal);

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
        $capacidad = max(1, (int)($sucursal['capacidad_simultanea'] ?? 1));
        $current = strtotime($fecha . ' ' . $start);
        $last = strtotime($fecha . ' ' . $end);

        while ($current < $last) {
            $slotStart = date('H:i', $current);
            $slotEnd = date('H:i', strtotime("+{$interval} minutes", $current));
            $ocupadas = 0;
            foreach ($events as $event) {
                if (self::rangesOverlap($slotStart, $slotEnd, (string)$event['hora_inicio'], (string)$event['hora_fin'])) {
                    $ocupadas++;
                }
            }

            $bloqueado = BloqueoHorario::hasBlockingForRange($sucursalId, $fecha, $slotStart, $slotEnd);
            $available = !$bloqueado && $ocupadas < $capacidad;

            $slots[] = [
                'hora_inicio' => $slotStart,
                'hora_fin' => $slotEnd,
                'disponible' => $available,
                'ocupadas' => $ocupadas,
                'capacidad' => $capacidad,
                'bloqueado' => $bloqueado,
            ];
            $current = strtotime("+{$interval} minutes", $current);
        }

        return $slots;
    }
}
