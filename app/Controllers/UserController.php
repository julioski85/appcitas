<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sucursal;
use App\Models\User;

class UserController extends Controller
{
    private function normalizeUserPayload(array $input, bool $isCreate): array
    {
        $data = $this->validate([
            'nombre' => 'required',
            'email' => 'required|email',
            'rol' => 'required|in:admin,call_center,sucursal',
            'sucursal_id' => '',
            'activo' => $isCreate ? '' : 'required|in:0,1',
        ], $input);

        $data['email'] = strtolower($data['email']);
        $data['activo'] = $isCreate ? 1 : (int)$data['activo'];

        if ($data['rol'] === 'sucursal') {
            if ($data['sucursal_id'] === '') {
                set_flash('error', 'La sucursal es obligatoria cuando el rol es sucursal.');
                set_old($input);
                back();
            }

            if (!Sucursal::find((int)$data['sucursal_id'])) {
                set_flash('error', 'La sucursal seleccionada no existe.');
                set_old($input);
                back();
            }

            $data['sucursal_id'] = (int)$data['sucursal_id'];
        } else {
            $data['sucursal_id'] = null;
        }

        return $data;
    }

    public function index(): void
    {
        $this->authorize('admin');

        $users = User::all();
        $this->view('users/index', compact('users'));
    }

    public function create(): void
    {
        $this->authorize('admin');

        $userData = [
            'nombre' => '',
            'email' => '',
            'rol' => 'sucursal',
            'sucursal_id' => '',
            'activo' => 1,
        ];
        $isEdit = false;
        $sucursales = Sucursal::all();

        $this->view('users/form', compact('userData', 'isEdit', 'sucursales'));
    }

    public function store(): void
    {
        $this->authorize('admin');
        verify_csrf();

        $data = $this->normalizeUserPayload($_POST, true);

        $password = trim((string)($_POST['password'] ?? ''));
        $passwordConfirm = trim((string)($_POST['password_confirm'] ?? ''));

        if (strlen($password) < 8) {
            set_flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            set_old($_POST);
            back();
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'La confirmación de contraseña no coincide.');
            set_old($_POST);
            back();
        }

        if (User::findByEmail($data['email'])) {
            set_flash('error', 'Ese correo ya está en uso.');
            set_old($_POST);
            back();
        }

        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        User::create($data);

        set_flash('success', 'Usuario creado correctamente.');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        $this->authorize('admin');

        $userData = User::find((int)$id);
        if (!$userData) {
            set_flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $isEdit = true;
        $sucursales = Sucursal::all();

        $this->view('users/form', compact('userData', 'isEdit', 'sucursales'));
    }

    public function update(string $id): void
    {
        $auth = $this->authorize('admin');
        verify_csrf();

        $target = User::find((int)$id);
        if (!$target) {
            set_flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $data = $this->normalizeUserPayload($_POST, false);

        if (User::findByEmailExceptId($data['email'], (int)$id)) {
            set_flash('error', 'Ese correo ya está en uso por otro usuario.');
            set_old($_POST);
            back();
        }

        $isSelf = (int)$auth['id'] === (int)$target['id'];
        if ($isSelf && $data['activo'] === 0) {
            set_flash('error', 'No puedes desactivarte a ti mismo.');
            set_old($_POST);
            back();
        }

        if ($isSelf && $data['rol'] !== 'admin') {
            set_flash('error', 'No puedes cambiar tu propio rol de administrador.');
            set_old($_POST);
            back();
        }

        $wouldDisableLastAdmin = $target['rol'] === 'admin' && (int)$target['activo'] === 1
            && ($data['rol'] !== 'admin' || $data['activo'] === 0)
            && User::countActiveAdmins() <= 1;

        if ($wouldDisableLastAdmin) {
            set_flash('error', 'Debe existir al menos un administrador activo en el sistema.');
            set_old($_POST);
            back();
        }

        User::update((int)$id, $data);

        if ($isSelf) {
            $fresh = User::find((int)$id);
            if ($fresh) {
                unset($fresh['password']);
                $_SESSION['user'] = $fresh;
            }
        }

        set_flash('success', 'Usuario actualizado correctamente.');
        redirect('/users');
    }

    public function password(string $id): void
    {
        $this->authorize('admin');

        $userData = User::find((int)$id);
        if (!$userData) {
            set_flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $this->view('users/password', compact('userData'));
    }

    public function updatePassword(string $id): void
    {
        $this->authorize('admin');
        verify_csrf();

        $userData = User::find((int)$id);
        if (!$userData) {
            set_flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        $password = trim((string)($_POST['password'] ?? ''));
        $passwordConfirm = trim((string)($_POST['password_confirm'] ?? ''));

        if (strlen($password) < 8) {
            set_flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            set_old($_POST);
            back();
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'La confirmación de contraseña no coincide.');
            set_old($_POST);
            back();
        }

        User::updatePassword((int)$id, password_hash($password, PASSWORD_DEFAULT));

        set_flash('success', 'Contraseña actualizada correctamente.');
        redirect('/users');
    }

    public function toggleActive(string $id): void
    {
        $auth = $this->authorize('admin');
        verify_csrf();

        $userData = User::find((int)$id);
        if (!$userData) {
            set_flash('error', 'Usuario no encontrado.');
            redirect('/users');
        }

        if ((int)$auth['id'] === (int)$userData['id']) {
            set_flash('error', 'No puedes desactivarte/activarte desde esta opción.');
            redirect('/users');
        }

        $nextActivo = (int)$userData['activo'] === 1 ? 0 : 1;

        if ($userData['rol'] === 'admin' && (int)$userData['activo'] === 1 && $nextActivo === 0 && User::countActiveAdmins() <= 1) {
            set_flash('error', 'Debe existir al menos un administrador activo en el sistema.');
            redirect('/users');
        }

        User::setActivo((int)$id, $nextActivo);

        set_flash('success', $nextActivo ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
        redirect('/users');
    }
}
