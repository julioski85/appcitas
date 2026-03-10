<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
        <p>Define datos de acceso y permisos por rol.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/users/update/' . $userData['id']) : url('/users/store')) ?>" class="form-grid two" id="userForm">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?= e(old('nombre', $userData['nombre'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e(old('email', $userData['email'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label>Rol</label>
            <select name="rol" id="rolField" required>
                <?php $selectedRol = old('rol', $userData['rol'] ?? 'sucursal'); ?>
                <option value="admin" <?= selected($selectedRol, 'admin') ?>>Admin</option>
                <option value="call_center" <?= selected($selectedRol, 'call_center') ?>>Call center</option>
                <option value="sucursal" <?= selected($selectedRol, 'sucursal') ?>>Sucursal</option>
            </select>
        </div>

        <div class="form-group" id="sucursalGroup">
            <label>Sucursal asignada</label>
            <select name="sucursal_id" id="sucursalField">
                <option value="">Selecciona una sucursal</option>
                <?php $selectedSucursal = old('sucursal_id', $userData['sucursal_id'] ?? ''); ?>
                <?php foreach ($sucursales as $sucursal): ?>
                    <option value="<?= e((string)$sucursal['id']) ?>" <?= selected($selectedSucursal, $sucursal['id']) ?>>
                        <?= e($sucursal['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!$isEdit): ?>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" minlength="8" required>
            </div>
            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" name="password_confirm" minlength="8" required>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Estatus</label>
                <?php $selectedActivo = old('activo', (string)$userData['activo']); ?>
                <select name="activo" required>
                    <option value="1" <?= selected($selectedActivo, '1') ?>>Activo</option>
                    <option value="0" <?= selected($selectedActivo, '0') ?>>Inactivo</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-actions span-2">
            <a class="btn btn-outline" href="<?= e(url('/users')) ?>">Volver</a>
            <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
    </form>
</section>

<script>
(function () {
    const rolField = document.getElementById('rolField');
    const sucursalGroup = document.getElementById('sucursalGroup');
    const sucursalField = document.getElementById('sucursalField');

    function syncSucursalVisibility() {
        const isSucursal = rolField.value === 'sucursal';
        sucursalGroup.style.display = isSucursal ? '' : 'none';
        sucursalField.required = isSucursal;
        if (!isSucursal) {
            sucursalField.value = '';
        }
    }

    syncSucursalVisibility();
    rolField.addEventListener('change', syncSucursalVisibility);
})();
</script>
