<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        $this->view('auth/login');
    }

    public function login(): void
    {
        verify_csrf();

        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], $_POST);

        $user = User::findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            set_flash('error', 'Credenciales incorrectas.');
            set_old(['email' => $data['email']]);
            redirect('/login');
        }

        if ((int)($user['activo'] ?? 1) !== 1) {
            set_flash('error', 'Tu usuario está inactivo. Contacta al administrador.');
            set_old(['email' => $data['email']]);
            redirect('/login');
        }

        unset($user['password']);
        $_SESSION['user'] = $user;
        clear_old();

        set_flash('success', 'Bienvenido, ' . $user['nombre'] . '.');
        redirect('/dashboard');
    }

    public function logout(): void
    {
        verify_csrf();
        session_unset();
        session_destroy();
        session_start();
        set_flash('success', 'Sesión cerrada correctamente.');
        redirect('/login');
    }
}
