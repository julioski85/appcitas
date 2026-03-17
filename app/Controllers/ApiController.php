<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\BloqueoHorario;
use App\Models\Programa;

class ApiController extends Controller
{
    private function requireApiOrSession(): ?array
    {
        if (is_logged_in()) {
            return auth_user();
        }
        if (api_request_has_key()) {
            return [
                'id' => 0,
                'rol' => 'admin',
                'sucursal_id' => null,
            ];
        }
        $this->json(['ok' => false, 'message' => 'No autorizado. Usa sesión o X-API-KEY.'], 401);
    }

    public function sucursales(): void
    {
        $this->requireApiOrSession();
        $sucursales = array_map(static function (array $sucursal): array {
            $sucursal['hora_apertura'] = Sucursal::openingHour($sucursal);
            $sucursal['hora_cierre'] = Sucursal::closingHour($sucursal);
            return $sucursal;
        }, Sucursal::all());

        $this->json(['ok' => true, 'data' => $sucursales]);
    }

    public function citas(): void
    {
        $user = $this->requireApiOrSession();

        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $sucursalId = $_GET['sucursal_id'] ?? '';
        $estatus = $_GET['estatus'] ?? '';

        $events = Cita::calendarEvents($user, [
            'start' => $start,
            'end' => $end,
            'sucursal_id' => $sucursalId,
            'estatus' => $estatus,
        ]);

        $payload = array_map(function ($event) {
            return [
                'id' => (int)$event['id'],
                'title' => $event['cliente_nombre'] . ' · ' . $event['servicio'],
                'start' => $event['fecha'] . 'T' . substr($event['hora_inicio'], 0, 5),
                'end' => $event['fecha'] . 'T' . substr($event['hora_fin'], 0, 5),
                'status' => $event['estatus'],
                'origen' => $event['origen'],
                'sucursal_id' => (int)$event['sucursal_id'],
                'sucursal_nombre' => $event['sucursal_nombre'],
                'cliente_id' => $event['cliente_id'] ? (int)$event['cliente_id'] : null,
                'cliente_nombre' => $event['cliente_nombre'],
                'cliente_telefono' => $event['cliente_telefono'],
                'servicio' => $event['servicio'],
                'codigo_promocion' => $event['codigo_promocion'],
                'programa_id' => $event['programa_id'] ? (int)$event['programa_id'] : null,
                'programa_nombre' => $event['programa_nombre'],
                'backgroundColor' => $event['color_calendario'],
                'borderColor' => $event['color_calendario'],
                'url' => url('/citas/edit/' . $event['id']),
                'is_block' => false,
            ];
        }, $events);

        $bloqueos = BloqueoHorario::allActiveForCalendar($user, [
            'start' => $start,
            'end' => $end,
            'sucursal_id' => $sucursalId,
        ]);

        foreach ($bloqueos as $bloqueo) {
            $fechas = [];
            if ($bloqueo['tipo_bloqueo'] === 'fecha_especifica' && !empty($bloqueo['fecha'])) {
                $fechas[] = $bloqueo['fecha'];
            } else {
                $cursor = strtotime($start);
                $to = strtotime($end);
                while ($cursor <= $to) {
                    $f = date('Y-m-d', $cursor);
                    if ($bloqueo['tipo_bloqueo'] === 'recurrente_diario') {
                        $fechas[] = $f;
                    } elseif ($bloqueo['tipo_bloqueo'] === 'recurrente_semanal' && (int)date('N', $cursor) === (int)$bloqueo['dia_semana']) {
                        $fechas[] = $f;
                    }
                    $cursor = strtotime('+1 day', $cursor);
                }
            }

            foreach ($fechas as $f) {
                $payload[] = [
                    'id' => 'block-' . $bloqueo['id'] . '-' . $f,
                    'title' => 'Bloqueo: ' . ($bloqueo['motivo'] ?: $bloqueo['tipo_bloqueo']),
                    'start' => $f . 'T' . substr($bloqueo['hora_inicio'], 0, 5),
                    'end' => $f . 'T' . substr($bloqueo['hora_fin'], 0, 5),
                    'status' => 'bloqueado',
                    'origen' => 'sistema',
                    'sucursal_id' => (int)$bloqueo['sucursal_id'],
                    'sucursal_nombre' => $bloqueo['sucursal_nombre'],
                    'cliente_id' => null,
                    'cliente_nombre' => 'Horario no disponible',
                    'cliente_telefono' => '',
                    'servicio' => 'Bloqueo de horario',
                    'codigo_promocion' => null,
                    'programa_id' => null,
                    'programa_nombre' => null,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                    'url' => '',
                    'is_block' => true,
                ];
            }
        }

        $this->json(['ok' => true, 'data' => $payload]);
    }

    public function storeCita(): void
    {
        $user = $this->requireApiOrSession();

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $required = ['sucursal_id', 'servicio', 'fecha', 'hora_inicio', 'hora_fin'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                $this->json(['ok' => false, 'message' => "Falta el campo {$field}."], 422);
            }
        }

        $clienteId = (int)($payload['cliente_id'] ?? 0);
        $clienteNombre = trim((string)($payload['cliente_nombre'] ?? ''));
        $clienteTelefono = trim((string)($payload['cliente_telefono'] ?? ''));
        if ($clienteId > 0) {
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                $this->json(['ok' => false, 'message' => 'cliente_id inválido.'], 422);
            }
            $clienteNombre = $cliente['nombre_completo'];
            $clienteTelefono = $cliente['telefono'];
        }

        if ($clienteNombre === '' || $clienteTelefono === '') {
            $this->json(['ok' => false, 'message' => 'Debes enviar cliente_id o cliente_nombre + cliente_telefono.'], 422);
        }

        $data = [
            'sucursal_id' => (string)($payload['sucursal_id']),
            'cliente_id' => $clienteId ? (string)$clienteId : '',
            'cliente_nombre' => $clienteNombre,
            'cliente_telefono' => $clienteTelefono,
            'servicio' => trim((string)$payload['servicio']),
            'codigo_promocion' => null,
            'programa_id' => trim((string)($payload['programa_id'] ?? '')),
            'fecha' => trim((string)$payload['fecha']),
            'hora_inicio' => trim((string)$payload['hora_inicio']),
            'hora_fin' => trim((string)$payload['hora_fin']),
            'estatus' => trim((string)($payload['estatus'] ?? 'agendada')),
            'origen' => trim((string)($payload['origen'] ?? 'web')),
        ];

        $codigoPromocion = trim((string)($payload['codigo_promocion'] ?? ''));
        $data['codigo_promocion'] = $codigoPromocion === '' ? null : $codigoPromocion;

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
            $data['origen'] = 'sucursal';
        }

        if (strtotime($data['fecha'] . ' ' . $data['hora_fin']) <= strtotime($data['fecha'] . ' ' . $data['hora_inicio'])) {
            $this->json(['ok' => false, 'message' => 'La hora fin debe ser mayor a la hora inicio.'], 422);
        }

        $today = date('Y-m-d');
        if ($data['fecha'] < $today) {
            $this->json(['ok' => false, 'message' => 'No se pueden agendar citas en fechas anteriores al día actual.'], 422);
        }

        if (Cita::hasElapsed($data['fecha'], $data['hora_fin']) && in_array($data['estatus'], ['agendada', 'confirmada'], true)) {
            $this->json(['ok' => false, 'message' => 'Una cita pasada no puede quedar en Agendada/Confirmada; marca Asistió o No asistió.'], 422);
        }

        if (!in_array($data['servicio'], Cita::SERVICIOS, true)) {
            $this->json(['ok' => false, 'message' => 'Servicio inválido.'], 422);
        }


        if ($data['programa_id'] !== '') {
            $programa = Programa::find((int)$data['programa_id']);
            if (!$programa) {
                $this->json(['ok' => false, 'message' => 'programa_id inválido.'], 422);
            }
            if ((int)$programa['activo'] !== 1) {
                $this->json(['ok' => false, 'message' => 'El programa seleccionado está inactivo.'], 422);
            }
            if (!empty($programa['sucursal_id']) && (int)$programa['sucursal_id'] !== (int)$data['sucursal_id']) {
                $this->json(['ok' => false, 'message' => 'El programa no pertenece a la sucursal enviada.'], 422);
            }
        }

        $sucursal = Sucursal::find((int)$data['sucursal_id']);
        if (!$sucursal) {
            $this->json(['ok' => false, 'message' => 'sucursal_id inválido.'], 422);
        }

        if (!Sucursal::isRangeWithinBusinessHours($sucursal, $data['hora_inicio'], $data['hora_fin'])) {
            $this->json(['ok' => false, 'message' => 'Horario fuera del rango permitido de la sucursal (' . Sucursal::openingHour($sucursal) . ' - ' . Sucursal::closingHour($sucursal) . ').'], 422);
        }

        $capacidad = max(1, (int)($sucursal['capacidad_simultanea'] ?? 1));

        if (Cita::hasCapacityConflict($data, $capacidad)) {
            $this->json(['ok' => false, 'message' => 'Horario sin cupo en esa sucursal.'], 409);
        }
        if (BloqueoHorario::hasBlockingForRange((int)$data['sucursal_id'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            $this->json(['ok' => false, 'message' => 'Ese horario no está disponible en la sucursal seleccionada.'], 409);
        }

        Cita::create($data, (int)$user['id']);
        $this->json(['ok' => true, 'message' => 'Cita creada correctamente.']);
    }

    public function bloqueos(): void
    {
        $user = $this->requireApiOrSession();
        $filters = [
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'tipo_bloqueo' => $_GET['tipo_bloqueo'] ?? '',
        ];

        if ($user['rol'] === 'sucursal') {
            $filters['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $this->json(['ok' => true, 'data' => BloqueoHorario::allForUser($user, $filters)]);
    }

    public function storeBloqueo(): void
    {
        $user = $this->requireApiOrSession();
        if (!in_array($user['rol'], ['admin', 'sucursal'], true)) {
            $this->json(['ok' => false, 'message' => 'No autorizado.'], 403);
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        foreach (['sucursal_id', 'tipo_bloqueo', 'hora_inicio', 'hora_fin'] as $required) {
            if (empty($payload[$required])) {
                $this->json(['ok' => false, 'message' => "Falta el campo {$required}."], 422);
            }
        }

        $data = [
            'sucursal_id' => (string)$payload['sucursal_id'],
            'tipo_bloqueo' => trim((string)$payload['tipo_bloqueo']),
            'fecha' => trim((string)($payload['fecha'] ?? '')),
            'dia_semana' => trim((string)($payload['dia_semana'] ?? '')),
            'hora_inicio' => trim((string)$payload['hora_inicio']),
            'hora_fin' => trim((string)$payload['hora_fin']),
            'motivo' => trim((string)($payload['motivo'] ?? '')),
            'activo' => array_key_exists('activo', $payload) ? (!empty($payload['activo']) ? '1' : '0') : '1',
        ];

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        if (!in_array($data['tipo_bloqueo'], ['fecha_especifica', 'recurrente_diario', 'recurrente_semanal'], true)) {
            $this->json(['ok' => false, 'message' => 'tipo_bloqueo inválido.'], 422);
        }

        if (strtotime('2000-01-01 ' . $data['hora_fin']) <= strtotime('2000-01-01 ' . $data['hora_inicio'])) {
            $this->json(['ok' => false, 'message' => 'La hora fin debe ser mayor a hora inicio.'], 422);
        }

        if ($data['tipo_bloqueo'] === 'fecha_especifica' && ($data['fecha'] === '' || strtotime($data['fecha']) === false)) {
            $this->json(['ok' => false, 'message' => 'fecha es obligatoria para fecha_especifica.'], 422);
        }

        if ($data['tipo_bloqueo'] === 'recurrente_semanal' && !in_array($data['dia_semana'], ['1', '2', '3', '4', '5', '6', '7'], true)) {
            $this->json(['ok' => false, 'message' => 'dia_semana inválido para recurrente_semanal.'], 422);
        }

        if ($data['tipo_bloqueo'] !== 'fecha_especifica') {
            $data['fecha'] = '';
        }
        if ($data['tipo_bloqueo'] !== 'recurrente_semanal') {
            $data['dia_semana'] = '';
        }

        BloqueoHorario::create($data);
        $this->json(['ok' => true, 'message' => 'Bloqueo creado correctamente.'], 201);
    }

    public function clientes(): void
    {
        $user = $this->requireApiOrSession();
        $filters = [
            'q' => $_GET['q'] ?? '',
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'estatus_cliente' => $_GET['estatus_cliente'] ?? '',
        ];
        $data = Cliente::listForIndex($user, $filters);
        $this->json(['ok' => true, 'data' => $data]);
    }

    public function storeCliente(): void
    {
        $user = $this->requireApiOrSession();
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        foreach (['nombre_completo', 'telefono', 'sexo'] as $required) {
            if (empty($payload[$required])) {
                $this->json(['ok' => false, 'message' => "Falta el campo {$required}."], 422);
            }
        }

        $tieneResponsable = !empty($payload['tiene_responsable']);
        if ($tieneResponsable && (empty($payload['responsable_nombre']) || empty($payload['responsable_telefono']) || empty($payload['responsable_parentesco']))) {
            $this->json(['ok' => false, 'message' => 'Si tiene_responsable=1 debes enviar todos los campos del responsable.'], 422);
        }

        $data = [
            'sucursal_id' => (string)($payload['sucursal_id'] ?? ''),
            'nombre_completo' => trim((string)$payload['nombre_completo']),
            'telefono' => trim((string)$payload['telefono']),
            'fecha_nacimiento' => trim((string)($payload['fecha_nacimiento'] ?? '')),
            'sexo' => trim((string)$payload['sexo']),
            'email' => trim((string)($payload['email'] ?? '')),
            'direccion' => trim((string)($payload['direccion'] ?? '')),
            'ciudad' => trim((string)($payload['ciudad'] ?? '')),
            'origen' => trim((string)($payload['origen'] ?? 'Otros')),
            'tiene_responsable' => $tieneResponsable ? '1' : '0',
            'responsable_nombre' => $tieneResponsable ? trim((string)$payload['responsable_nombre']) : '',
            'responsable_telefono' => $tieneResponsable ? trim((string)$payload['responsable_telefono']) : '',
            'responsable_parentesco' => $tieneResponsable ? trim((string)$payload['responsable_parentesco']) : '',
            'estatus_cliente' => trim((string)($payload['estatus_cliente'] ?? 'prospecto')),
            'notas' => trim((string)($payload['notas'] ?? '')),
        ];

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        if ($data['origen'] !== '' && !in_array($data['origen'], ['Redes sociales', 'Programa de televisión', 'Google', 'Otros'], true)) {
            $this->json(['ok' => false, 'message' => 'origen inválido.'], 422);
        }

        $id = Cliente::create($data);
        Cliente::updateStatusAndCitas($id);

        $this->json(['ok' => true, 'data' => Cliente::find($id)], 201);
    }

    public function showCliente(string $id): void
    {
        $user = $this->requireApiOrSession();
        $cliente = Cliente::find((int)$id);
        if (!$cliente) {
            $this->json(['ok' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
            $this->json(['ok' => false, 'message' => 'No autorizado.'], 403);
        }

        $cliente['historial_citas'] = Cliente::citas((int)$id);
        $this->json(['ok' => true, 'data' => $cliente]);
    }

    public function horariosDisponibles(): void
    {
        $user = $this->requireApiOrSession();
        $sucursalId = (int)($_GET['sucursal_id'] ?? 0);
        $fecha = (string)($_GET['fecha'] ?? '');

        if ($user['rol'] === 'sucursal') {
            $sucursalId = (int)$user['sucursal_id'];
        }

        if (!$sucursalId || !$fecha) {
            $this->json(['ok' => false, 'message' => 'Debes enviar sucursal_id y fecha.'], 422);
        }

        $slots = Cita::availableSlots($sucursalId, $fecha);
        $this->json(['ok' => true, 'data' => $slots]);
    }
}
