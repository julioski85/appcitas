<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sucursal;

class SucursalController extends Controller
{
    public function index(): void
    {
        $this->authorize('admin');
        $sucursales = Sucursal::all();
        $this->view('sucursales/index', compact('sucursales'));
    }

    public function create(): void
    {
        $this->authorize('admin');
        $sucursal = null;
        $this->view('sucursales/form', compact('sucursal'));
    }

    public function store(): void
    {
        $this->authorize('admin');
        verify_csrf();

        $data = $this->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'color_calendario' => 'required',
            'capacidad_simultanea' => 'required',
            'hora_apertura' => 'required|time',
            'hora_cierre' => 'required|time',
        ], $_POST);

        if (strtotime('2000-01-01 ' . $data['hora_cierre']) <= strtotime('2000-01-01 ' . $data['hora_apertura'])) {
            set_flash('error', 'La hora de cierre debe ser mayor a la hora de apertura.');
            set_old($_POST);
            back();
        }

        Sucursal::create($data);
        set_flash('success', 'Sucursal creada correctamente.');
        redirect('/sucursales');
    }

    public function edit(string $id): void
    {
        $this->authorize('admin');
        $sucursal = Sucursal::find((int)$id);
        if (!$sucursal) {
            set_flash('error', 'Sucursal no encontrada.');
            redirect('/sucursales');
        }
        $this->view('sucursales/form', compact('sucursal'));
    }

    public function update(string $id): void
    {
        $this->authorize('admin');
        verify_csrf();

        $data = $this->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'telefono' => 'required',
            'color_calendario' => 'required',
            'capacidad_simultanea' => 'required',
            'hora_apertura' => 'required|time',
            'hora_cierre' => 'required|time',
        ], $_POST);

        if (strtotime('2000-01-01 ' . $data['hora_cierre']) <= strtotime('2000-01-01 ' . $data['hora_apertura'])) {
            set_flash('error', 'La hora de cierre debe ser mayor a la hora de apertura.');
            set_old($_POST);
            back();
        }

        Sucursal::update((int)$id, $data);
        set_flash('success', 'Sucursal actualizada correctamente.');
        redirect('/sucursales');
    }

    public function delete(string $id): void
    {
        $this->authorize('admin');
        verify_csrf();
        Sucursal::delete((int)$id);
        set_flash('success', 'Sucursal eliminada.');
        redirect('/sucursales');
    }
}
