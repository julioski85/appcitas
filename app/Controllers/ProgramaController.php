<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Programa;
use App\Models\Sucursal;

class ProgramaController extends Controller
{
    private function normalize(array $input, array $user): array
    {
        $data = [
            'nombre' => trim((string)($input['nombre'] ?? '')),
            'scope' => trim((string)($input['scope'] ?? 'sucursal')),
            'sucursal_id' => trim((string)($input['sucursal_id'] ?? '')),
            'activo' => !empty($input['activo']) ? '1' : '0',
        ];

        if ($data['nombre'] === '') {
            set_flash('error', 'El nombre del programa es obligatorio.');
            set_old($input);
            back();
        }

        if ($user['rol'] === 'admin') {
            if ($data['scope'] === 'global') {
                $data['sucursal_id'] = '';
            } else {
                if ($data['sucursal_id'] === '') {
                    set_flash('error', 'Selecciona una sucursal para programas de sucursal.');
                    set_old($input);
                    back();
                }
            }
        } else {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        return $data;
    }

    public function index(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $programas = Programa::allForUser($user);
        $this->view('programas/index', compact('user', 'programas'));
    }

    public function create(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $sucursales = Sucursal::all();
        $programa = ['nombre' => '', 'sucursal_id' => '', 'activo' => 1];
        $isEdit = false;
        $this->view('programas/form', compact('user', 'sucursales', 'programa', 'isEdit'));
    }

    public function store(): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();

        $data = $this->normalize($_POST, $user);
        Programa::create($data);

        set_flash('success', 'Programa guardado correctamente.');
        redirect('/programas');
    }

    public function edit(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        $programa = Programa::findVisibleForUser((int)$id, $user);

        if (!$programa) {
            set_flash('error', 'Programa no encontrado.');
            redirect('/programas');
        }

        $sucursales = Sucursal::all();
        $isEdit = true;
        $this->view('programas/form', compact('user', 'sucursales', 'programa', 'isEdit'));
    }

    public function update(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();

        $existing = Programa::findVisibleForUser((int)$id, $user);
        if (!$existing) {
            set_flash('error', 'Programa no encontrado.');
            redirect('/programas');
        }

        $data = $this->normalize($_POST, $user);
        Programa::update((int)$id, $data);

        set_flash('success', 'Programa actualizado correctamente.');
        redirect('/programas');
    }

    public function toggle(string $id): void
    {
        $user = $this->authorize('admin', 'sucursal');
        verify_csrf();

        $existing = Programa::findVisibleForUser((int)$id, $user);
        if (!$existing) {
            set_flash('error', 'Programa no encontrado.');
            redirect('/programas');
        }

        Programa::toggle((int)$id);
        set_flash('success', 'Programa actualizado.');
        redirect('/programas');
    }
}
