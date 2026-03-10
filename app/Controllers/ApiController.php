<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Sucursal;

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
        $this->json(['ok' => true, 'data' => Sucursal::all()]);
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
                'backgroundColor' => $event['color_calendario'],
                'borderColor' => $event['color_calendario'],
                'url' => url('/citas/edit/' . $event['id']),
            ];
        }, $events);

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

        if (Cita::hasConflict($data)) {
            $this->json(['ok' => false, 'message' => 'Horario ocupado en esa sucursal.'], 409);
        }

        Cita::create($data, (int)$user['id']);
        $this->json(['ok' => true, 'message' => 'Cita creada correctamente.']);
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
            'origen' => trim((string)($payload['origen'] ?? 'api')),
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
