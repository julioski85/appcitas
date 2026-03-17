<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cliente;
use App\Models\Sucursal;

class ClienteController extends Controller
{
    private function normalizeAndValidate(array $input, array $user, bool $isEdit = false): array
    {
        $data = $this->validate([
            'nombre_completo' => 'required',
            'telefono' => 'required',
            'sexo' => 'required|in:masculino,femenino,otro',
            'fecha_nacimiento' => 'date',
            'email' => 'email',
            'direccion' => '',
            'ciudad' => '',
            'origen' => 'in:Redes sociales,Programa de televisión,Google,Otros',
            'sucursal_id' => '',
            'notas' => '',
            'estatus_cliente' => 'required|in:prospecto,cita_agendada,asistio_primera_vez,cliente_activo,inactivo',
        ], $input);

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $tieneResponsable = ($input['tiene_responsable'] ?? '0') === '1' || ($input['tiene_responsable'] ?? '') === 'on';
        $data['tiene_responsable'] = $tieneResponsable ? '1' : '0';

        $data['responsable_nombre'] = trim((string)($input['responsable_nombre'] ?? ''));
        $data['responsable_telefono'] = trim((string)($input['responsable_telefono'] ?? ''));
        $data['responsable_parentesco'] = trim((string)($input['responsable_parentesco'] ?? ''));

        if ($tieneResponsable) {
            if ($data['responsable_nombre'] === '' || $data['responsable_telefono'] === '' || $data['responsable_parentesco'] === '') {
                set_flash('error', 'Si activas contacto responsable debes completar nombre, teléfono y parentesco.');
                set_old($input);
                back();
            }
        } else {
            $data['responsable_nombre'] = null;
            $data['responsable_telefono'] = null;
            $data['responsable_parentesco'] = null;
        }

        if (!$isEdit && $data['estatus_cliente'] === '') {
            $data['estatus_cliente'] = 'prospecto';
        }

        return $data;
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        $filters = [
            'q' => $_GET['q'] ?? '',
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'estatus_cliente' => $_GET['estatus_cliente'] ?? '',
        ];
        if ($user['rol'] === 'sucursal') {
            $filters['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $clientes = Cliente::listForIndex($user, $filters);
        $sucursales = Sucursal::all();

        $this->view('clientes/index', compact('user', 'clientes', 'sucursales', 'filters'));
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $sucursales = Sucursal::all();
        $cliente = [
            'sucursal_id' => $user['rol'] === 'sucursal' ? (string)$user['sucursal_id'] : '',
            'nombre_completo' => '',
            'telefono' => '',
            'fecha_nacimiento' => '',
            'sexo' => '',
            'email' => '',
            'direccion' => '',
            'ciudad' => '',
            'origen' => '',
            'tiene_responsable' => 0,
            'responsable_nombre' => '',
            'responsable_telefono' => '',
            'responsable_parentesco' => '',
            'estatus_cliente' => 'prospecto',
            'notas' => '',
        ];
        $isEdit = false;
        $this->view('clientes/form', compact('user', 'sucursales', 'cliente', 'isEdit'));
    }

    public function store(): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $data = $this->normalizeAndValidate($_POST, $user);
        $id = Cliente::create($data);
        Cliente::updateStatusAndCitas($id);

        set_flash('success', 'Cliente creado correctamente.');
        redirect('/clientes');
    }

    public function show(string $id): void
    {
        $user = $this->requireAuth();
        $cliente = Cliente::find((int)$id);

        if (!$cliente) {
            set_flash('error', 'Cliente no encontrado.');
            redirect('/clientes');
        }

        if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $historial = Cliente::citas((int)$id);
        $this->view('clientes/show', compact('user', 'cliente', 'historial'));
    }

    public function edit(string $id): void
    {
        $user = $this->requireAuth();
        $cliente = Cliente::find((int)$id);
        $sucursales = Sucursal::all();

        if (!$cliente) {
            set_flash('error', 'Cliente no encontrado.');
            redirect('/clientes');
        }

        if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $isEdit = true;
        $this->view('clientes/form', compact('user', 'sucursales', 'cliente', 'isEdit'));
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $cliente = Cliente::find((int)$id);
        if (!$cliente) {
            set_flash('error', 'Cliente no encontrado.');
            redirect('/clientes');
        }

        if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $data = $this->normalizeAndValidate($_POST, $user, true);
        Cliente::update((int)$id, $data);
        Cliente::updateStatusAndCitas((int)$id);

        set_flash('success', 'Cliente actualizado correctamente.');
        redirect('/clientes');
    }
}
