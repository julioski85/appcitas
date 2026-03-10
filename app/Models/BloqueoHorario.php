<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class BloqueoHorario
{
    public static function allForUser(array $user, array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if ($user['rol'] === 'sucursal') {
            $where[] = 'b.sucursal_id = :user_sucursal_id';
            $params['user_sucursal_id'] = (int)$user['sucursal_id'];
        } elseif (!empty($filters['sucursal_id'])) {
            $where[] = 'b.sucursal_id = :sucursal_id';
            $params['sucursal_id'] = (int)$filters['sucursal_id'];
        }

        if (!empty($filters['tipo_bloqueo'])) {
            $where[] = 'b.tipo_bloqueo = :tipo_bloqueo';
            $params['tipo_bloqueo'] = $filters['tipo_bloqueo'];
        }

        return Database::select(
            "SELECT b.*, s.nombre AS sucursal_nombre
             FROM bloqueos_horario b
             INNER JOIN sucursales s ON s.id = b.sucursal_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY b.activo DESC, b.created_at DESC",
            $params
        );
    }

    public static function allActiveForCalendar(array $user, array $filters = []): array
    {
        $where = ['b.activo = 1'];
        $params = [];

        if ($user['rol'] === 'sucursal') {
            $where[] = 'b.sucursal_id = :user_sucursal_id';
            $params['user_sucursal_id'] = (int)$user['sucursal_id'];
        } elseif (!empty($filters['sucursal_id'])) {
            $where[] = 'b.sucursal_id = :sucursal_id';
            $params['sucursal_id'] = (int)$filters['sucursal_id'];
        }

        if (!empty($filters['start']) && !empty($filters['end'])) {
            $where[] = "(b.tipo_bloqueo <> 'fecha_especifica' OR (b.fecha BETWEEN :start AND :end))";
            $params['start'] = $filters['start'];
            $params['end'] = $filters['end'];
        }

        return Database::select(
            "SELECT b.*, s.nombre AS sucursal_nombre, s.color_calendario
             FROM bloqueos_horario b
             INNER JOIN sucursales s ON s.id = b.sucursal_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY b.hora_inicio ASC",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first("SELECT * FROM bloqueos_horario WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    public static function create(array $data): bool
    {
        return Database::execute(
            "INSERT INTO bloqueos_horario
             (sucursal_id, tipo_bloqueo, fecha, dia_semana, hora_inicio, hora_fin, motivo, activo, created_at, updated_at)
             VALUES
             (:sucursal_id, :tipo_bloqueo, :fecha, :dia_semana, :hora_inicio, :hora_fin, :motivo, :activo, NOW(), NOW())",
            [
                'sucursal_id' => (int)$data['sucursal_id'],
                'tipo_bloqueo' => $data['tipo_bloqueo'],
                'fecha' => $data['fecha'] ?: null,
                'dia_semana' => $data['dia_semana'] !== '' ? (int)$data['dia_semana'] : null,
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'motivo' => $data['motivo'] ?: null,
                'activo' => $data['activo'] === '1' ? 1 : 0,
            ]
        );
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE bloqueos_horario
             SET sucursal_id = :sucursal_id,
                 tipo_bloqueo = :tipo_bloqueo,
                 fecha = :fecha,
                 dia_semana = :dia_semana,
                 hora_inicio = :hora_inicio,
                 hora_fin = :hora_fin,
                 motivo = :motivo,
                 activo = :activo,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $id,
                'sucursal_id' => (int)$data['sucursal_id'],
                'tipo_bloqueo' => $data['tipo_bloqueo'],
                'fecha' => $data['fecha'] ?: null,
                'dia_semana' => $data['dia_semana'] !== '' ? (int)$data['dia_semana'] : null,
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'motivo' => $data['motivo'] ?: null,
                'activo' => $data['activo'] === '1' ? 1 : 0,
            ]
        );
    }

    public static function delete(int $id): bool
    {
        return Database::execute("DELETE FROM bloqueos_horario WHERE id = :id", ['id' => $id]);
    }

    public static function toggle(int $id): bool
    {
        return Database::execute(
            "UPDATE bloqueos_horario
             SET activo = IF(activo = 1, 0, 1), updated_at = NOW()
             WHERE id = :id",
            ['id' => $id]
        );
    }

    public static function hasBlockingForRange(int $sucursalId, string $fecha, string $horaInicio, string $horaFin, ?int $ignoreId = null): bool
    {
        $dayOfWeek = (int)date('N', strtotime($fecha));

        $sql = "SELECT id
                FROM bloqueos_horario
                WHERE sucursal_id = :sucursal_id
                  AND activo = 1
                  AND hora_inicio < :hora_fin
                  AND hora_fin > :hora_inicio
                  AND (
                    (tipo_bloqueo = 'fecha_especifica' AND fecha = :fecha)
                    OR (tipo_bloqueo = 'recurrente_diario')
                    OR (tipo_bloqueo = 'recurrente_semanal' AND dia_semana = :dia_semana)
                  )";

        $params = [
            'sucursal_id' => $sucursalId,
            'fecha' => $fecha,
            'dia_semana' => $dayOfWeek,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ];

        if ($ignoreId) {
            $sql .= " AND id <> :ignore_id";
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';
        return Database::first($sql, $params) !== null;
    }
}
