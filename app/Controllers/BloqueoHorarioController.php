<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BloqueoHorario;
use App\Models\Sucursal;

class BloqueoHorarioController extends Controller
{
    private function normalizeAndValidate(array $input, array $user): array
    {
        $data = $this->validate([
            'sucursal_id' => 'required',
            'tipo_bloqueo' => 'required|in:fecha_especifica,recurrente_diario,recurrente_semanal',
            'hora_inicio' => 'required|time',
            'hora_fin' => 'required|time',
        ], $input);

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $data['fecha'] = trim((string)($input['fecha'] ?? ''));
        $data['dia_semana'] = trim((string)($input['dia_semana'] ?? ''));
        $data['motivo'] = trim((string)($input['motivo'] ?? ''));
        $data['activo'] = !empty($input['activo']) ? '1' : '0';

        if (strtotime('2000-01-01 ' . $data['hora_fin']) <= strtotime('2000-01-01 ' . $data['hora_inicio'])) {
            set_flash('error', 'La hora fin debe ser mayor a la hora inicio.');
            set_old($input);
            back();
        }

        if ($data['tipo_bloqueo'] === 'fecha_especifica') {
            if ($data['fecha'] === '' || strtotime($data['fecha']) === false) {
                set_flash('error', 'Para bloqueo por fecha específica debes enviar una fecha válida.');
                set_old($input);
                back();
            }
            $data['dia_semana'] = '';
        } elseif ($data['tipo_bloqueo'] === 'recurrente_semanal') {
            if ($data['dia_semana'] === '' || !in_array($data['dia_semana'], ['1', '2', '3', '4', '5', '6', '7'], true)) {
                set_flash('error', 'Para bloqueo semanal debes seleccionar un día de la semana.');
                set_old($input);
                back();
            }
            $data['fecha'] = '';
        } else {
            $data['fecha'] = '';
            $data['dia_semana'] = '';
        }

        return $data;
    }

    public function index(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $filters = [
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'tipo_bloqueo' => $_GET['tipo_bloqueo'] ?? '',
        ];

        if ($user['rol'] === 'sucursal') {
            $filters['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $sucursales = Sucursal::all();
        $bloqueos = BloqueoHorario::allForUser($user, $filters);

        $this->view('bloqueos/index', compact('user', 'filters', 'sucursales', 'bloqueos'));
    }

    public function create(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $sucursales = Sucursal::all();
        $bloqueo = [
            'sucursal_id' => $user['rol'] === 'sucursal' ? (string)$user['sucursal_id'] : '',
            'tipo_bloqueo' => 'fecha_especifica',
            'fecha' => date('Y-m-d'),
            'dia_semana' => '',
            'hora_inicio' => '14:00',
            'hora_fin' => '15:00',
            'motivo' => '',
            'activo' => 1,
        ];
        $isEdit = false;

        $this->view('bloqueos/form', compact('user', 'sucursales', 'bloqueo', 'isEdit'));
    }

    public function store(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();
        $data = $this->normalizeAndValidate($_POST, $user);

        BloqueoHorario::create($data);
        set_flash('success', 'Bloqueo guardado correctamente.');
        redirect('/bloqueos-horario');
    }

    public function edit(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $sucursales = Sucursal::all();
        $bloqueo = BloqueoHorario::find((int)$id);

        if (!$bloqueo) {
            set_flash('error', 'Bloqueo no encontrado.');
            redirect('/bloqueos-horario');
        }

        if ($user['rol'] === 'sucursal' && (int)$bloqueo['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $isEdit = true;
        $this->view('bloqueos/form', compact('user', 'sucursales', 'bloqueo', 'isEdit'));
    }

    public function update(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();
        $bloqueo = BloqueoHorario::find((int)$id);

        if (!$bloqueo) {
            set_flash('error', 'Bloqueo no encontrado.');
            redirect('/bloqueos-horario');
        }

        if ($user['rol'] === 'sucursal' && (int)$bloqueo['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $data = $this->normalizeAndValidate($_POST, $user);
        BloqueoHorario::update((int)$id, $data);

        set_flash('success', 'Bloqueo actualizado correctamente.');
        redirect('/bloqueos-horario');
    }

    public function delete(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();
        $bloqueo = BloqueoHorario::find((int)$id);

        if (!$bloqueo) {
            set_flash('error', 'Bloqueo no encontrado.');
            redirect('/bloqueos-horario');
        }

        if ($user['rol'] === 'sucursal' && (int)$bloqueo['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        BloqueoHorario::delete((int)$id);
        set_flash('success', 'Bloqueo eliminado.');
        redirect('/bloqueos-horario');
    }

    public function toggle(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();
        $bloqueo = BloqueoHorario::find((int)$id);

        if (!$bloqueo) {
            set_flash('error', 'Bloqueo no encontrado.');
            redirect('/bloqueos-horario');
        }

        if ($user['rol'] === 'sucursal' && (int)$bloqueo['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        BloqueoHorario::toggle((int)$id);
        set_flash('success', 'Estado de bloqueo actualizado.');
        redirect('/bloqueos-horario');
    }
}
