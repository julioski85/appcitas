<section class="page-head">
    <div>
        <h1>Cambiar contraseña</h1>
        <p>Usuario: <strong><?= e($userData['nombre']) ?></strong> (<?= e($userData['email']) ?>)</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e(url('/users/password/' . $userData['id'])) ?>" class="form-grid two">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Nueva contraseña</label>
            <input type="password" name="password" minlength="8" required>
        </div>

        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password" name="password_confirm" minlength="8" required>
        </div>

        <div class="form-actions span-2">
            <a class="btn btn-outline" href="<?= e(url('/users')) ?>">Volver</a>
            <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
        </div>
    </form>
</section>
