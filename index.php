<?php
declare(strict_types=1);

session_start();

date_default_timezone_set('America/Mexico_City');

define('BASE_PATH', __DIR__);

require BASE_PATH . '/config/config.php';
require BASE_PATH . '/app/Core/helpers.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Core\Database;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SucursalController;
use App\Controllers\CitaController;
use App\Controllers\ApiController;
use App\Controllers\InstallController;
use App\Controllers\UserController;

$router = new Router();

$router->get('/', [DashboardController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/sucursales', [SucursalController::class, 'index']);
$router->get('/sucursales/create', [SucursalController::class, 'create']);
$router->post('/sucursales/store', [SucursalController::class, 'store']);
$router->get('/sucursales/edit/{id}', [SucursalController::class, 'edit']);
$router->post('/sucursales/update/{id}', [SucursalController::class, 'update']);
$router->post('/sucursales/delete/{id}', [SucursalController::class, 'delete']);

$router->get('/citas', [CitaController::class, 'index']);
$router->get('/citas/create', [CitaController::class, 'create']);
$router->post('/citas/store', [CitaController::class, 'store']);
$router->get('/citas/edit/{id}', [CitaController::class, 'edit']);
$router->post('/citas/update/{id}', [CitaController::class, 'update']);
$router->post('/citas/delete/{id}', [CitaController::class, 'delete']);
$router->post('/citas/cancel/{id}', [CitaController::class, 'cancel']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/store', [UserController::class, 'store']);
$router->get('/users/edit/{id}', [UserController::class, 'edit']);
$router->post('/users/update/{id}', [UserController::class, 'update']);
$router->get('/users/password/{id}', [UserController::class, 'password']);
$router->post('/users/password/{id}', [UserController::class, 'updatePassword']);
$router->post('/users/toggle-active/{id}', [UserController::class, 'toggleActive']);

$router->get('/api/sucursales', [ApiController::class, 'sucursales']);
$router->get('/api/citas', [ApiController::class, 'citas']);
$router->post('/api/citas', [ApiController::class, 'storeCita']);
$router->get('/api/horarios-disponibles', [ApiController::class, 'horariosDisponibles']);

$router->get('/install', [InstallController::class, 'index']);
$router->post('/install/run', [InstallController::class, 'run']);

try {
    Database::boot(config('db'));
} catch (Throwable $e) {
    // handled later, installer screen can still render
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', current_path());
