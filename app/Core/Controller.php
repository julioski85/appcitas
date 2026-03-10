<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = base_path('app/Views/' . $view . '.php');

        if ($layout === 'none') {
            require $viewPath;
            return;
        }

        require base_path('app/Views/layouts/header.php');
        require $viewPath;
        require base_path('app/Views/layouts/footer.php');
    }

    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireAuth(): array
    {
        if (!is_logged_in()) {
            set_flash('error', 'Inicia sesión para continuar.');
            redirect('/login');
        }
        return auth_user();
    }

    protected function authorize(string ...$roles): array
    {
        $user = $this->requireAuth();
        if (!in_array($user['rol'], $roles, true)) {
            http_response_code(403);
            exit('No autorizado.');
        }
        return $user;
    }

    protected function validate(array $rules, array $input): array
    {
        $errors = [];
        $clean = [];

        foreach ($rules as $field => $ruleStr) {
            $rulesArr = explode('|', $ruleStr);
            $value = trim((string)($input[$field] ?? ''));

            foreach ($rulesArr as $rule) {
                if ($rule === 'required' && $value === '') {
                    $errors[$field] = 'Este campo es obligatorio.';
                } elseif ($rule === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Correo inválido.';
                } elseif ($rule === 'date' && $value !== '' && strtotime($value) === false) {
                    $errors[$field] = 'Fecha inválida.';
                } elseif ($rule === 'time' && $value !== '' && !preg_match('/^\d{2}:\d{2}$/', $value)) {
                    $errors[$field] = 'Hora inválida.';
                } elseif (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($value !== '' && !in_array($value, $allowed, true)) {
                        $errors[$field] = 'Valor inválido.';
                    }
                }
            }

            $clean[$field] = $value;
        }

        if (!empty($errors)) {
            $_SESSION['_errors'] = $errors;
            set_old($input);
            back();
        }

        clear_old();
        unset($_SESSION['_errors']);

        return $clean;
    }

    protected function errors(): array
    {
        return $_SESSION['_errors'] ?? [];
    }
}
