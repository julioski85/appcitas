<section class="page-head">
    <div>
        <h1><?= $sucursal ? 'Editar sucursal' : 'Nueva sucursal' ?></h1>
        <p>Configura la información visible del sistema.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($sucursal ? url('/sucursales/update/' . $sucursal['id']) : url('/sucursales/store')) ?>" class="form-grid two">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?= e(old('nombre', $sucursal['nombre'] ?? '')) ?>" required>
        </div>
        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= e(old('telefono', $sucursal['telefono'] ?? '')) ?>" required>
        </div>
        <div class="form-group span-2">
            <label>Dirección</label>
            <input type="text" name="direccion" value="<?= e(old('direccion', $sucursal['direccion'] ?? '')) ?>" required>
        </div>
        <div class="form-group">
            <label>Color calendario</label>
            <input type="color" name="color_calendario" value="<?= e(old('color_calendario', $sucursal['color_calendario'] ?? '#4f46e5')) ?>" required>
        </div>
        <div class="form-actions span-2">
            <a class="btn btn-outline" href="<?= e(url('/sucursales')) ?>">Volver</a>
            <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
    </form>
</section>
