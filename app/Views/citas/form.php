<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar cita' : 'Nueva cita' ?></h1>
        <p>El sistema valida disponibilidad en servidor antes de guardar.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/citas/update/' . $cita['id']) : url('/citas/store')) ?>" class="form-grid two">
        <?= csrf_field() ?>

        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group">
            <label>Sucursal</label>
            <select name="sucursal_id" required>
                <?php foreach ($sucursales as $sucursal): ?>
                    <option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $cita['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
            <div class="form-group">
                <label>Sucursal</label>
                <input type="text" value="<?= e($user['sucursal_nombre'] ?? 'Sucursal') ?>" disabled>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Servicio</label>
            <input type="text" name="servicio" value="<?= e(old('servicio', $cita['servicio'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label>Código de promoción</label>
            <input type="text" name="codigo_promocion" value="<?= e(old('codigo_promocion', $cita['codigo_promocion'] ?? '')) ?>" placeholder="Código de promoción (opcional)">
        </div>

        <div class="form-group">
            <label>Cliente</label>
            <input type="text" name="cliente_nombre" value="<?= e(old('cliente_nombre', $cita['cliente_nombre'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="cliente_telefono" value="<?= e(old('cliente_telefono', $cita['cliente_telefono'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= e(old('fecha', $cita['fecha'] ?? date('Y-m-d'))) ?>" required>
        </div>

        <div class="form-group">
            <label>Hora inicio</label>
            <input type="time" name="hora_inicio" value="<?= e(substr(old('hora_inicio', $cita['hora_inicio'] ?? '10:00'), 0, 5)) ?>" required>
        </div>

        <div class="form-group">
            <label>Hora fin</label>
            <input type="time" name="hora_fin" value="<?= e(substr(old('hora_fin', $cita['hora_fin'] ?? '10:30'), 0, 5)) ?>" required>
        </div>

        <div class="form-group">
            <label>Estatus</label>
            <select name="estatus" required>
                <?php $estatus = old('estatus', $cita['estatus'] ?? 'agendada'); ?>
                <option value="agendada" <?= selected($estatus, 'agendada') ?>>Agendada</option>
                <option value="cancelada" <?= selected($estatus, 'cancelada') ?>>Cancelada</option>
                <option value="atendida" <?= selected($estatus, 'atendida') ?>>Atendida</option>
            </select>
        </div>

        <?php if ($user['rol'] === 'sucursal'): ?>
            <input type="hidden" name="origen" value="sucursal">
            <div class="form-group">
                <label>Origen</label>
                <input type="text" value="sucursal" disabled>
            </div>
        <?php else: ?>
        <div class="form-group">
            <label>Origen</label>
            <?php $origen = old('origen', $cita['origen'] ?? ($user['rol'] === 'call_center' ? 'call_center' : 'web')); ?>
            <select name="origen" required>
                <option value="call_center" <?= selected($origen, 'call_center') ?>>call_center</option>
                <option value="sucursal" <?= selected($origen, 'sucursal') ?>>sucursal</option>
                <option value="web" <?= selected($origen, 'web') ?>>web</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-actions span-2">
            <a class="btn btn-outline" href="<?= e(url('/citas')) ?>">Volver</a>
            <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Actualizar cita' : 'Guardar cita' ?></button>
        </div>
    </form>

    <?php if ($isEdit): ?>
    <div class="separator"></div>
    <div class="danger-zone">
        <form method="POST" action="<?= e(url('/citas/delete/' . $cita['id'])) ?>" onsubmit="return confirm('¿Eliminar definitivamente esta cita?');">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit">Eliminar cita</button>
        </form>
    </div>
    <?php endif; ?>
</section>
