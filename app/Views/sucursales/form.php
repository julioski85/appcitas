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
        <div class="form-group">
            <label>Capacidad simultánea</label>
            <input type="number" min="1" step="1" name="capacidad_simultanea" value="<?= e(old('capacidad_simultanea', (string)($sucursal['capacidad_simultanea'] ?? '1'))) ?>" required>
        </div>
        <div class="form-group">
            <label>Buffer entre citas (minutos)</label>
            <input type="number" min="0" step="1" name="buffer_minutos" value="<?= e(old('buffer_minutos', (string)($sucursal['buffer_minutos'] ?? '0'))) ?>" required>
        </div>
        <div class="form-group">
            <label>Hora de apertura</label>
            <input type="time" name="hora_apertura" value="<?= e(substr(old('hora_apertura', $sucursal['hora_apertura'] ?? '08:00'), 0, 5)) ?>" required>
        </div>
        <div class="form-group">
            <label>Hora de cierre</label>
            <input type="time" name="hora_cierre" value="<?= e(substr(old('hora_cierre', $sucursal['hora_cierre'] ?? '20:00'), 0, 5)) ?>" required>
        </div>
        <div class="form-actions span-2">
            <a class="btn btn-outline" href="<?= e(url('/sucursales')) ?>">Volver</a>
            <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
    </form>
</section>
