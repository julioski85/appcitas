<?php
$user = auth_user();
$success = flash('success');
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge">CM</div>
                <div>
                    <div class="brand-title">Citas MultiSucursal</div>
                    <div class="brand-subtitle"><?= e(ucwords(str_replace('_', ' ', $user['rol']))) ?></div>
                </div>
            </div>

            <nav class="nav">
                <a class="nav-link <?= current_path() === '/dashboard' ? 'active' : '' ?>" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
                <a class="nav-link <?= current_path() === '/citas' ? 'active' : '' ?>" href="<?= e(url('/citas')) ?>">Calendario y citas</a>
                <a class="nav-link <?= str_starts_with(current_path(), '/clientes') ? 'active' : '' ?>" href="<?= e(url('/clientes')) ?>">Clientes</a>
                <?php if (in_array($user['rol'], ['admin', 'sucursal'], true)): ?>
                    <a class="nav-link <?= str_starts_with(current_path(), '/bloqueos-horario') ? 'active' : '' ?>" href="<?= e(url('/bloqueos-horario')) ?>">Bloqueos de horario</a>
                <?php endif; ?>
                <?php if (in_array($user['rol'], ['admin', 'sucursal'], true)): ?>
                    <a class="nav-link <?= str_starts_with(current_path(), '/programas') ? 'active' : '' ?>" href="<?= e(url('/programas')) ?>">Programas</a>
                <?php endif; ?>
                <?php if ($user['rol'] === 'admin'): ?>
                    <a class="nav-link <?= str_starts_with(current_path(), '/sucursales') ? 'active' : '' ?>" href="<?= e(url('/sucursales')) ?>">Sucursales</a>
                    <a class="nav-link <?= str_starts_with(current_path(), '/users') ? 'active' : '' ?>" href="<?= e(url('/users')) ?>">Usuarios</a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="theme-toggle" id="themeToggle">Modo dark</button>
                <div class="user-card">
                    <div class="user-name"><?= e($user['nombre']) ?></div>
                    <div class="user-email"><?= e($user['email']) ?></div>
                    <?php if (!empty($user['sucursal_nombre'])): ?>
                        <div class="user-branch"><?= e($user['sucursal_nombre']) ?></div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline btn-block" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </aside>
    <?php endif; ?>

    <main class="main-content <?= $user ? '' : 'main-content-auth' ?>">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
