<?php
declare(strict_types=1);

function config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/config.php';
    }
    if ($key === null) {
        return $config;
    }
    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

function app_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($dir === '') {
        $dir = '';
    }
    return rtrim($scheme . '://' . $host . ($dir ? $dir : ''), '/');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return app_url() . ($path === '/' ? '' : $path);
}

function asset(string $path): string
{
    $normalizedPath = ltrim($path, '/');
    $assetUrl = url('public/assets/' . $normalizedPath);
    $absolutePath = base_path('public/assets/' . $normalizedPath);

    if (is_file($absolutePath)) {
        $version = (string) filemtime($absolutePath);
        return $assetUrl . '?v=' . rawurlencode($version);
    }

    return $assetUrl;
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($baseDir && $baseDir !== '/' && str_starts_with($uri, $baseDir)) {
        $uri = substr($uri, strlen($baseDir));
    }
    return $uri ?: '/';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Token CSRF inválido.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
    header('Location: ' . $ref);
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash(string $key): ?string
{
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function is_role(string ...$roles): bool
{
    $user = auth_user();
    return $user && in_array($user['rol'], $roles, true);
}

function format_date(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

function format_time(?string $time): string
{
    return $time ? date('H:i', strtotime($time)) : '';
}

function selected(string|int|null $value, string|int|null $expected): string
{
    return (string) $value === (string) $expected ? 'selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function api_request_has_key(): bool
{
    $headerKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $queryKey = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
    $valid = config('security.api_key', '');
    return $valid && ($headerKey === $valid || $queryKey === $valid);
}

function require_installation_if_needed(string $path): void
{
    $allowed = ['/install', '/install/run'];
    if (in_array($path, $allowed, true)) {
        return;
    }

    try {
        $pdo = \App\Core\Database::pdo();
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if (!$stmt || !$stmt->fetch()) {
            redirect('/install');
        }
    } catch (\Throwable $e) {
        redirect('/install');
    }
}
