<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar programa' : 'Nuevo programa' ?></h1>
        <p>Define programas globales o por sucursal para mostrarlos en el formulario de citas.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/programas/update/' . $programa['id']) : url('/programas/store')) ?>" class="form-grid two" data-programa-form>
        <?= csrf_field() ?>

        <div class="form-group"><label>Nombre</label><input type="text" name="nombre" value="<?= e(old('nombre', $programa['nombre'] ?? '')) ?>" required></div>

        <?php if ($user['rol'] === 'admin'): ?>
            <?php $scope = old('scope', empty($programa['sucursal_id']) ? 'global' : 'sucursal'); ?>
            <div class="form-group"><label>Alcance</label><select name="scope" data-programa-scope><option value="global" <?= selected($scope, 'global') ?>>Global</option><option value="sucursal" <?= selected($scope, 'sucursal') ?>>Sucursal</option></select></div>
            <div class="form-group" data-programa-sucursal><label>Sucursal</label><select name="sucursal_id"><option value="">Selecciona una sucursal</option><?php foreach ($sucursales as $sucursal): ?><option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $programa['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
            <input type="hidden" name="scope" value="sucursal">
            <input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
            <div class="form-group"><label>Sucursal</label><input type="text" value="<?= e($user['sucursal_nombre'] ?? 'Sucursal') ?>" disabled></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="switch-field">
                <input type="checkbox" name="activo" value="1" <?= (int)old('activo', (string)($programa['activo'] ?? 1)) === 1 ? 'checked' : '' ?>>
                <span>Programa activo</span>
            </label>
        </div>

        <div class="form-actions span-2"><a class="btn btn-outline" href="<?= e(url('/programas')) ?>">Volver</a><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Actualizar programa' : 'Guardar programa' ?></button></div>
    </form>
</section>
