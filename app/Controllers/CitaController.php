<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Sucursal;

class CitaController extends Controller
{
    private function normalizeAndValidateCita(array $input, array $user, ?int $ignoreId = null): array
    {
        $data = $this->validate([
            'sucursal_id' => 'required',
            'servicio' => 'required',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|time',
            'hora_fin' => 'required|time',
            'estatus' => 'required|in:agendada,confirmada,asistio,no_asistio,cancelada,reprogramada',
            'origen' => 'required|in:call_center,sucursal,web',
        ], $input);

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
            $data['origen'] = 'sucursal';
        }

        $codigoPromocion = trim((string)($input['codigo_promocion'] ?? ''));
        $data['codigo_promocion'] = $codigoPromocion === '' ? null : $codigoPromocion;
        $data['cliente_id'] = trim((string)($input['cliente_id'] ?? ''));

        if (($input['cliente_mode'] ?? 'existente') === 'nuevo') {
            $clienteData = $this->validate([
                'nuevo_nombre_completo' => 'required',
                'nuevo_telefono' => 'required',
                'nuevo_sexo' => 'required|in:masculino,femenino,otro',
                'nuevo_fecha_nacimiento' => 'date',
                'nuevo_email' => 'email',
                'nuevo_direccion' => '',
                'nuevo_ciudad' => '',
                'nuevo_origen' => '',
                'nuevo_notas' => '',
            ], $input);

            $tieneResponsable = ($input['nuevo_tiene_responsable'] ?? '0') === '1' || ($input['nuevo_tiene_responsable'] ?? '') === 'on';
            $responsableNombre = trim((string)($input['nuevo_responsable_nombre'] ?? ''));
            $responsableTelefono = trim((string)($input['nuevo_responsable_telefono'] ?? ''));
            $responsableParentesco = trim((string)($input['nuevo_responsable_parentesco'] ?? ''));

            if ($tieneResponsable && ($responsableNombre === '' || $responsableTelefono === '' || $responsableParentesco === '')) {
                set_flash('error', 'Completa los datos del contacto responsable del nuevo prospecto.');
                set_old($input);
                back();
            }

            $nuevoId = Cliente::create([
                'sucursal_id' => $data['sucursal_id'],
                'nombre_completo' => $clienteData['nuevo_nombre_completo'],
                'telefono' => $clienteData['nuevo_telefono'],
                'fecha_nacimiento' => $clienteData['nuevo_fecha_nacimiento'],
                'sexo' => $clienteData['nuevo_sexo'],
                'email' => $clienteData['nuevo_email'],
                'direccion' => $clienteData['nuevo_direccion'],
                'ciudad' => $clienteData['nuevo_ciudad'],
                'origen' => $clienteData['nuevo_origen'],
                'tiene_responsable' => $tieneResponsable ? '1' : '0',
                'responsable_nombre' => $tieneResponsable ? $responsableNombre : '',
                'responsable_telefono' => $tieneResponsable ? $responsableTelefono : '',
                'responsable_parentesco' => $tieneResponsable ? $responsableParentesco : '',
                'estatus_cliente' => 'prospecto',
                'notas' => $clienteData['nuevo_notas'],
            ]);
            $data['cliente_id'] = (string)$nuevoId;
            $data['cliente_nombre'] = $clienteData['nuevo_nombre_completo'];
            $data['cliente_telefono'] = $clienteData['nuevo_telefono'];
        } else {
            if ($data['cliente_id'] !== '') {
                $cliente = Cliente::find((int)$data['cliente_id']);
                if (!$cliente) {
                    set_flash('error', 'El cliente seleccionado no existe.');
                    set_old($input);
                    back();
                }
                if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
                    http_response_code(403);
                    exit('No autorizado.');
                }
                $data['cliente_nombre'] = $cliente['nombre_completo'];
                $data['cliente_telefono'] = $cliente['telefono'];
            } else {
                $base = $this->validate([
                    'cliente_nombre' => 'required',
                    'cliente_telefono' => 'required',
                ], $input);
                $data['cliente_nombre'] = $base['cliente_nombre'];
                $data['cliente_telefono'] = $base['cliente_telefono'];
            }
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
        $clientes = Cliente::allForSelect($user, '');
        $cita = [
            'sucursal_id' => $_GET['sucursal_id'] ?? ($user['sucursal_id'] ?? ''),
            'cliente_id' => '',
            'cliente_nombre' => '',
            'cliente_telefono' => '',
            'servicio' => '',
            'fecha' => $_GET['date'] ?? date('Y-m-d'),
            'hora_inicio' => $_GET['time'] ?? '10:00',
            'hora_fin' => $_GET['end'] ?? '10:30',
            'estatus' => 'agendada',
            'origen' => $user['rol'] === 'call_center' ? 'call_center' : ($user['rol'] === 'sucursal' ? 'sucursal' : 'web'),
            'codigo_promocion' => '',
        ];
        $isEdit = false;
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit', 'clientes'));
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
        $clientes = Cliente::allForSelect($user, '');
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
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit', 'clientes'));
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
