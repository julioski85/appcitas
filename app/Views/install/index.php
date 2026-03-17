<section class="page-head">
    <div>
        <h1>Instalación del sistema</h1>
        <p>Esta versión está preparada para hosting compartido sin Composer ni SSH.</p>
    </div>
</section>

<div class="card stack">
    <div class="notice-grid">
        <div class="notice">
            <div class="notice-label">Config DB</div>
            <div class="notice-value"><?= $status['config_ok'] ? 'Lista' : 'Pendiente' ?></div>
        </div>
        <div class="notice">
            <div class="notice-label">Conexión MySQL</div>
            <div class="notice-value"><?= $status['db_connected'] ? 'Correcta' : 'Sin conexión' ?></div>
        </div>
        <div class="notice">
            <div class="notice-label">Sistema</div>
            <div class="notice-value"><?= $status['installed'] ? 'Instalado' : 'No instalado' ?></div>
        </div>
    </div>

    <div class="stack">
        <h3>Paso 1</h3>
        <p>Edita <code>config/config.php</code> y coloca la contraseña real de la base de datos. También cambia la API KEY.</p>
        <pre class="code-block">database: u801126150_citas
usuario:  u801126150_citas
host:     localhost</pre>
    </div>

    <?php if (!$status['db_connected'] && !empty($status['message'])): ?>
        <div class="alert alert-danger"><?= e($status['message']) ?></div>
    <?php endif; ?>

    <?php if (!$status['installed'] && $status['db_connected']): ?>
        <form method="POST" action="<?= e(url('/install/run')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit">Instalar base de datos y usuarios demo</button>
        </form>
    <?php elseif ($status['installed']): ?>
        <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Ir al login</a>
    <?php endif; ?>
</div>
