<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar cita' : 'Nueva cita' ?></h1>
        <p>Agenda con cliente existente o crea prospecto en el mismo flujo.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/citas/update/' . $cita['id']) : url('/citas/store')) ?>" class="form-grid two" data-cita-form>
        <?= csrf_field() ?>

        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group"><label>Sucursal</label><select name="sucursal_id" required><?php foreach ($sucursales as $sucursal): ?><option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $cita['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
            <input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
            <div class="form-group"><label>Sucursal</label><input type="text" value="<?= e($user['sucursal_nombre'] ?? 'Sucursal') ?>" disabled></div>
        <?php endif; ?>

        <div class="form-group"><label>Modo cliente</label><?php $mode = old('cliente_mode', (($cita['cliente_id'] ?? '') ? 'existente' : 'manual')); ?><select name="cliente_mode" data-cliente-mode><option value="existente" <?= selected($mode,'existente') ?>>Cliente existente</option><option value="nuevo" <?= selected($mode,'nuevo') ?>>Nuevo prospecto</option><option value="manual" <?= selected($mode,'manual') ?>>Solo nombre/teléfono</option></select></div>

        <div class="form-group" data-cliente-existente><label>Cliente existente</label><select name="cliente_id"><option value="">Selecciona cliente</option><?php foreach ($clientes as $cliente): ?><option value="<?= e($cliente['id']) ?>" <?= selected(old('cliente_id', $cita['cliente_id'] ?? ''), $cliente['id']) ?>><?= e($cliente['nombre_completo'].' · '.$cliente['telefono']) ?></option><?php endforeach; ?></select></div>

        <div class="form-group" data-cliente-manual><label>Cliente</label><input type="text" name="cliente_nombre" value="<?= e(old('cliente_nombre', $cita['cliente_nombre'] ?? '')) ?>"></div>
        <div class="form-group" data-cliente-manual><label>Teléfono</label><input type="text" name="cliente_telefono" value="<?= e(old('cliente_telefono', $cita['cliente_telefono'] ?? '')) ?>"></div>

        <div class="span-2" data-cliente-nuevo>
            <div class="card" style="margin:0;">
                <h3>Alta rápida de prospecto</h3>
                <div class="form-grid two">
                    <div class="form-group"><label>Nombre completo</label><input name="nuevo_nombre_completo" data-nuevo-input value="<?= e(old('nuevo_nombre_completo', '')) ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input name="nuevo_telefono" data-nuevo-input value="<?= e(old('nuevo_telefono', '')) ?>"></div>
                    <div class="form-group"><label>Sexo</label><select name="nuevo_sexo" data-nuevo-input><option value="">Selecciona</option><option value="masculino">Masculino</option><option value="femenino">Femenino</option><option value="otro">Otro</option></select></div>
                    <div class="form-group"><label>Fecha nacimiento</label><input type="date" name="nuevo_fecha_nacimiento" value="<?= e(old('nuevo_fecha_nacimiento', '')) ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="nuevo_email" value="<?= e(old('nuevo_email', '')) ?>"></div>
                    <div class="form-group"><label>Dirección</label><input name="nuevo_direccion" value="<?= e(old('nuevo_direccion', '')) ?>"></div>
                    <div class="form-group"><label>Ciudad</label><input name="nuevo_ciudad" value="<?= e(old('nuevo_ciudad', '')) ?>"></div>
                    <div class="form-group"><label>Origen</label><input name="nuevo_origen" value="<?= e(old('nuevo_origen', 'cita')) ?>"></div>
                    <div class="form-group span-2"><label>Notas</label><textarea name="nuevo_notas"><?= e(old('nuevo_notas', '')) ?></textarea></div>
                    <div class="form-group span-2"><label><input type="checkbox" name="nuevo_tiene_responsable" value="1" data-responsable-toggle> ¿Tiene contacto responsable?</label></div>
                    <div class="responsable-fields span-2" data-responsable-fields><div class="form-grid two"><div class="form-group"><label>Nombre responsable</label><input name="nuevo_responsable_nombre" data-responsable-input></div><div class="form-group"><label>Teléfono responsable</label><input name="nuevo_responsable_telefono" data-responsable-input></div><div class="form-group"><label>Parentesco</label><input name="nuevo_responsable_parentesco" data-responsable-input></div></div></div>
                </div>
            </div>
        </div>

        <div class="form-group"><label>Servicio</label><input type="text" name="servicio" value="<?= e(old('servicio', $cita['servicio'] ?? '')) ?>" required></div>
        <div class="form-group"><label>Código de promoción</label><input type="text" name="codigo_promocion" value="<?= e(old('codigo_promocion', $cita['codigo_promocion'] ?? '')) ?>"></div>
        <div class="form-group"><label>Fecha</label><input type="date" name="fecha" value="<?= e(old('fecha', $cita['fecha'] ?? date('Y-m-d'))) ?>" required></div>
        <div class="form-group"><label>Hora inicio</label><input type="time" name="hora_inicio" value="<?= e(substr(old('hora_inicio', $cita['hora_inicio'] ?? '10:00'), 0, 5)) ?>" required></div>
        <div class="form-group"><label>Hora fin</label><input type="time" name="hora_fin" value="<?= e(substr(old('hora_fin', $cita['hora_fin'] ?? '10:30'), 0, 5)) ?>" required></div>

        <div class="form-group"><label>Estatus</label><select name="estatus" required><?php $estatus = old('estatus', $cita['estatus'] ?? 'agendada'); foreach(['agendada','confirmada','asistio','no_asistio','cancelada','reprogramada'] as $eStatus): ?><option value="<?= e($eStatus) ?>" <?= selected($estatus,$eStatus) ?>><?= e($eStatus) ?></option><?php endforeach; ?></select></div>

        <?php if ($user['rol'] === 'sucursal'): ?><input type="hidden" name="origen" value="sucursal"><?php else: ?>
        <div class="form-group"><label>Origen</label><?php $origen = old('origen', $cita['origen'] ?? ($user['rol'] === 'call_center' ? 'call_center' : 'web')); ?><select name="origen" required><option value="call_center" <?= selected($origen, 'call_center') ?>>call_center</option><option value="sucursal" <?= selected($origen, 'sucursal') ?>>sucursal</option><option value="web" <?= selected($origen, 'web') ?>>web</option></select></div>
        <?php endif; ?>

        <div class="form-actions span-2"><a class="btn btn-outline" href="<?= e(url('/citas')) ?>">Volver</a><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Actualizar cita' : 'Guardar cita' ?></button></div>
    </form>

    <?php if ($isEdit): ?><div class="separator"></div><div class="danger-zone"><form method="POST" action="<?= e(url('/citas/delete/' . $cita['id'])) ?>" onsubmit="return confirm('¿Eliminar definitivamente esta cita?');"><?= csrf_field() ?><button class="btn btn-danger" type="submit">Eliminar cita</button></form></div><?php endif; ?>
</section>
