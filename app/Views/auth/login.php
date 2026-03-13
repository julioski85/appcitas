<section class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-badge large">CM</div>
            <h1>Gestión de citas</h1>
            <p>Accede al sistema centralizado para call center y sucursales.</p>
        </div>

        <form method="POST" action="<?= e(url('/login')) ?>" class="form-grid">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="email" value="<?= e(old('email')) ?>" placeholder="admin.demo@citas.local" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button class="btn btn-primary" type="submit">Entrar</button>
        </form>

    </div>
</section>
