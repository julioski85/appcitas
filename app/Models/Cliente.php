<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Cliente
{
    public static function filtersForUser(array $user, array $filters = []): array
    {
        $where = [];
        $params = [];

        if ($user['rol'] === 'sucursal') {
            $where[] = 'cl.sucursal_id = :user_sucursal_id';
            $params['user_sucursal_id'] = $user['sucursal_id'];
        } elseif (!empty($filters['sucursal_id'])) {
            $where[] = 'cl.sucursal_id = :sucursal_id';
            $params['sucursal_id'] = (int)$filters['sucursal_id'];
        }

        if (!empty($filters['estatus_cliente'])) {
            $where[] = 'cl.estatus_cliente = :estatus_cliente';
            $params['estatus_cliente'] = $filters['estatus_cliente'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(cl.nombre_completo LIKE :q OR cl.telefono LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$sqlWhere, $params];
    }

    public static function listForIndex(array $user, array $filters = []): array
    {
        [$where, $params] = self::filtersForUser($user, $filters);

        return Database::select(
            "SELECT cl.*, s.nombre AS sucursal_nombre,
                    (
                        SELECT CONCAT(c.fecha, ' ', c.hora_inicio)
                        FROM citas c
                        WHERE c.cliente_id = cl.id
                          AND c.estatus <> 'cancelada'
                        ORDER BY c.fecha DESC, c.hora_inicio DESC
                        LIMIT 1
                    ) AS ultima_cita_programada,
                    (
                        SELECT CONCAT(c.fecha, ' ', c.hora_inicio)
                        FROM citas c
                        WHERE c.cliente_id = cl.id
                          AND c.fecha >= CURDATE()
                          AND c.estatus IN ('agendada','confirmada','reprogramada')
                        ORDER BY c.fecha ASC, c.hora_inicio ASC
                        LIMIT 1
                    ) AS proxima_cita
             FROM clientes cl
             LEFT JOIN sucursales s ON s.id = cl.sucursal_id
             $where
             ORDER BY cl.created_at DESC, cl.id DESC",
            $params
        );
    }

    public static function allForSelect(array $user, string $q = ''): array
    {
        [$where, $params] = self::filtersForUser($user, ['q' => $q]);

        return Database::select(
            "SELECT cl.id, cl.nombre_completo, cl.telefono, cl.estatus_cliente
             FROM clientes cl
             $where
             ORDER BY cl.nombre_completo ASC
             LIMIT 100",
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            "SELECT cl.*, s.nombre AS sucursal_nombre
             FROM clientes cl
             LEFT JOIN sucursales s ON s.id = cl.sucursal_id
             WHERE cl.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            "INSERT INTO clientes
            (sucursal_id, nombre_completo, telefono, fecha_nacimiento, sexo, email, direccion, ciudad, origen,
             tiene_responsable, responsable_nombre, responsable_telefono, responsable_parentesco,
             estatus_cliente, notas, primera_cita_at, ultima_cita_at, created_at, updated_at)
            VALUES
            (:sucursal_id, :nombre_completo, :telefono, :fecha_nacimiento, :sexo, :email, :direccion, :ciudad, :origen,
             :tiene_responsable, :responsable_nombre, :responsable_telefono, :responsable_parentesco,
             :estatus_cliente, :notas, :primera_cita_at, :ultima_cita_at, NOW(), NOW())",
            self::dbPayload($data)
        );

        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        return Database::execute(
            "UPDATE clientes
             SET sucursal_id = :sucursal_id,
                 nombre_completo = :nombre_completo,
                 telefono = :telefono,
                 fecha_nacimiento = :fecha_nacimiento,
                 sexo = :sexo,
                 email = :email,
                 direccion = :direccion,
                 ciudad = :ciudad,
                 origen = :origen,
                 tiene_responsable = :tiene_responsable,
                 responsable_nombre = :responsable_nombre,
                 responsable_telefono = :responsable_telefono,
                 responsable_parentesco = :responsable_parentesco,
                 estatus_cliente = :estatus_cliente,
                 notas = :notas,
                 updated_at = NOW()
             WHERE id = :id",
            array_merge(['id' => $id], self::dbPayload($data))
        );
    }

    public static function updateStatusAndCitas(int $clienteId): void
    {
        $asistidas = (int)(Database::first(
            "SELECT COUNT(*) AS total FROM citas WHERE cliente_id = :id AND estatus = 'asistio'",
            ['id' => $clienteId]
        )['total'] ?? 0);

        $agendadas = (int)(Database::first(
            "SELECT COUNT(*) AS total FROM citas WHERE cliente_id = :id AND estatus IN ('agendada','confirmada','reprogramada')",
            ['id' => $clienteId]
        )['total'] ?? 0);

        $primeraAsistencia = Database::first(
            "SELECT CONCAT(fecha, ' ', hora_inicio) AS fecha_hora
             FROM citas
             WHERE cliente_id = :id AND estatus = 'asistio'
             ORDER BY fecha ASC, hora_inicio ASC
             LIMIT 1",
            ['id' => $clienteId]
        )['fecha_hora'] ?? null;

        $ultimaCita = Database::first(
            "SELECT CONCAT(fecha, ' ', hora_inicio) AS fecha_hora
             FROM citas
             WHERE cliente_id = :id
             ORDER BY fecha DESC, hora_inicio DESC
             LIMIT 1",
            ['id' => $clienteId]
        )['fecha_hora'] ?? null;

        $cliente = self::find($clienteId);
        if (!$cliente) {
            return;
        }

        $status = $cliente['estatus_cliente'];
        if ($status !== 'inactivo') {
            if ($asistidas === 0 && $agendadas === 0) {
                $status = 'prospecto';
            } elseif ($asistidas === 0 && $agendadas > 0) {
                $status = 'cita_agendada';
            } elseif ($asistidas === 1) {
                $status = 'asistio_primera_vez';
            } elseif ($asistidas > 1) {
                $status = 'cliente_activo';
            }
        }

        Database::execute(
            "UPDATE clientes
             SET estatus_cliente = :estatus_cliente,
                 primera_cita_at = :primera_cita_at,
                 ultima_cita_at = :ultima_cita_at,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'id' => $clienteId,
                'estatus_cliente' => $status,
                'primera_cita_at' => $primeraAsistencia,
                'ultima_cita_at' => $ultimaCita,
            ]
        );
    }

    public static function citas(int $clienteId): array
    {
        return Database::select(
            "SELECT c.*, s.nombre AS sucursal_nombre
             FROM citas c
             INNER JOIN sucursales s ON s.id = c.sucursal_id
             WHERE c.cliente_id = :cliente_id
             ORDER BY c.fecha DESC, c.hora_inicio DESC",
            ['cliente_id' => $clienteId]
        );
    }

    private static function dbPayload(array $data): array
    {
        return [
            'sucursal_id' => $data['sucursal_id'] !== '' ? (int)$data['sucursal_id'] : null,
            'nombre_completo' => $data['nombre_completo'],
            'telefono' => $data['telefono'],
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
            'sexo' => $data['sexo'],
            'email' => $data['email'] ?: null,
            'direccion' => $data['direccion'] ?: null,
            'ciudad' => $data['ciudad'] ?: null,
            'origen' => $data['origen'] ?: null,
            'tiene_responsable' => (int)$data['tiene_responsable'],
            'responsable_nombre' => $data['responsable_nombre'] ?: null,
            'responsable_telefono' => $data['responsable_telefono'] ?: null,
            'responsable_parentesco' => $data['responsable_parentesco'] ?: null,
            'estatus_cliente' => $data['estatus_cliente'],
            'notas' => $data['notas'] ?: null,
            'primera_cita_at' => $data['primera_cita_at'] ?? null,
            'ultima_cita_at' => $data['ultima_cita_at'] ?? null,
        ];
    }
}
