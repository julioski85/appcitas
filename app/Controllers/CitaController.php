<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
use App\Models\Sucursal;

class CitaController extends Controller
{
    private function normalizeAndValidateCita(array $input, array $user, ?int $ignoreId = null): array
    {
        $data = $this->validate([
            'sucursal_id' => 'required',
            'cliente_nombre' => 'required',
            'cliente_telefono' => 'required',
            'servicio' => 'required',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|time',
            'hora_fin' => 'required|time',
            'estatus' => 'required|in:agendada,cancelada,atendida',
            'origen' => 'required|in:call_center,sucursal,web',
        ], $input);

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
            $data['origen'] = 'sucursal';
        }

        if (strtotime($data['fecha'] . ' ' . $data['hora_fin']) <= strtotime($data['fecha'] . ' ' . $data['hora_inicio'])) {
            set_flash('error', 'La hora fin debe ser mayor a la hora inicio.');
            set_old($input);
            back();
        }

        if (Cita::hasConflict($data, $ignoreId)) {
            set_flash('error', 'Ese horario ya está ocupado en la sucursal seleccionada.');
            set_old($input);
            back();
        }

        return $data;
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        $filters = [
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'estatus' => $_GET['estatus'] ?? '',
            'start' => $_GET['start'] ?? '',
            'end' => $_GET['end'] ?? '',
        ];

        if ($user['rol'] === 'sucursal') {
            $filters['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $sucursales = Sucursal::all();
        $ultimasCitas = Cita::latest($user, $filters);

        $this->view('citas/index', compact('user', 'filters', 'sucursales', 'ultimasCitas'));
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $sucursales = Sucursal::all();
        $cita = [
            'sucursal_id' => $_GET['sucursal_id'] ?? ($user['sucursal_id'] ?? ''),
            'cliente_nombre' => '',
            'cliente_telefono' => '',
            'servicio' => '',
            'fecha' => $_GET['date'] ?? date('Y-m-d'),
            'hora_inicio' => $_GET['time'] ?? '10:00',
            'hora_fin' => $_GET['end'] ?? '10:30',
            'estatus' => 'agendada',
            'origen' => $user['rol'] === 'call_center' ? 'call_center' : ($user['rol'] === 'sucursal' ? 'sucursal' : 'web'),
        ];
        $isEdit = false;
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit'));
    }

    public function store(): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $data = $this->normalizeAndValidateCita($_POST, $user);
        Cita::create($data, (int)$user['id']);

        set_flash('success', 'Cita creada correctamente.');
        redirect('/citas');
    }

    public function edit(string $id): void
    {
        $user = $this->requireAuth();
        $sucursales = Sucursal::all();
        $cita = Cita::find((int)$id);

        if (!$cita) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$cita['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $isEdit = true;
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit'));
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $data = $this->normalizeAndValidateCita($_POST, $user, (int)$id);
        Cita::update((int)$id, $data);

        set_flash('success', 'Cita actualizada correctamente.');
        redirect('/citas');
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        Cita::delete((int)$id);
        set_flash('success', 'Cita eliminada correctamente.');
        redirect('/citas');
    }

    public function cancel(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        Cita::cancel((int)$id);
        set_flash('success', 'Cita cancelada.');
        redirect('/citas');
    }
}
