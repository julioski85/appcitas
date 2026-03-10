<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
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
                'cliente_nombre' => $event['cliente_nombre'],
                'cliente_telefono' => $event['cliente_telefono'],
                'servicio' => $event['servicio'],
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

        $required = ['sucursal_id', 'cliente_nombre', 'cliente_telefono', 'servicio', 'fecha', 'hora_inicio', 'hora_fin'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                $this->json(['ok' => false, 'message' => "Falta el campo {$field}."], 422);
            }
        }

        $data = [
            'sucursal_id' => (string)($payload['sucursal_id']),
            'cliente_nombre' => trim((string)$payload['cliente_nombre']),
            'cliente_telefono' => trim((string)$payload['cliente_telefono']),
            'servicio' => trim((string)$payload['servicio']),
            'fecha' => trim((string)$payload['fecha']),
            'hora_inicio' => trim((string)$payload['hora_inicio']),
            'hora_fin' => trim((string)$payload['hora_fin']),
            'estatus' => trim((string)($payload['estatus'] ?? 'agendada')),
            'origen' => trim((string)($payload['origen'] ?? 'web')),
        ];

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
