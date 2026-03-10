<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar cita' : 'Nueva cita' ?></h1>
        <p>Agenda con cliente existente o captura manual en un flujo más limpio.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/citas/update/' . $cita['id']) : url('/citas/store')) ?>" class="form-grid two" data-cita-form data-client-search-url="<?= e(url('/citas/clientes/search')) ?>" data-prospect-url="<?= e(url('/citas/prospectos/quick-store')) ?>">
        <?= csrf_field() ?>

        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group"><label>Sucursal</label><select name="sucursal_id" required><?php foreach ($sucursales as $sucursal): ?><option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $cita['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
            <input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
            <div class="form-group"><label>Sucursal</label><input type="text" value="<?= e($user['sucursal_nombre'] ?? 'Sucursal') ?>" disabled></div>
        <?php endif; ?>

        <div class="form-group"><label>Modo cliente</label><?php $mode = old('cliente_mode', (($cita['cliente_id'] ?? '') ? 'existente' : 'manual')); ?><select name="cliente_mode" data-cliente-mode><option value="existente" <?= selected($mode,'existente') ?>>Cliente existente</option><option value="manual" <?= selected($mode,'manual') ?>>Solo nombre/teléfono</option></select></div>

        <div class="form-group" data-cliente-existente>
            <label>Cliente existente</label>
            <input type="hidden" name="cliente_id" value="<?= e(old('cliente_id', $cita['cliente_id'] ?? '')) ?>" data-cliente-id>
            <div class="autocomplete" data-cliente-autocomplete>
                <input type="text" placeholder="Buscar por nombre o teléfono" autocomplete="off" value="<?= e(old('cliente_display', (($cita['cliente_nombre'] ?? '') !== '' ? (($cita['cliente_nombre'] ?? '') . ' · ' . ($cita['cliente_telefono'] ?? '')) : ''))) ?>" data-cliente-search>
                <div class="autocomplete-list" data-cliente-results></div>
            </div>
            <small>Escribe al menos 2 caracteres para buscar.</small>
            <div class="inline-actions" style="margin-top:10px;">
                <button type="button" class="btn btn-outline btn-sm" data-open-prospect-modal>Nuevo prospecto</button>
            </div>
        </div>

        <div class="form-group" data-cliente-manual><label>Cliente</label><input type="text" name="cliente_nombre" value="<?= e(old('cliente_nombre', $cita['cliente_nombre'] ?? '')) ?>"></div>
        <div class="form-group" data-cliente-manual><label>Teléfono</label><input type="text" name="cliente_telefono" value="<?= e(old('cliente_telefono', $cita['cliente_telefono'] ?? '')) ?>"></div>

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

<div class="modal" data-prospect-modal aria-hidden="true">
    <div class="modal-dialog card">
        <div class="modal-head">
            <h3>Nuevo prospecto</h3>
            <button type="button" class="btn btn-outline btn-sm" data-close-prospect-modal>Cerrar</button>
        </div>
        <form class="form-grid two" data-prospect-form>
            <div class="form-group"><label>Nombre completo</label><input name="nuevo_nombre_completo" data-nuevo-input required></div>
            <div class="form-group"><label>Teléfono</label><input name="nuevo_telefono" data-nuevo-input required></div>
            <div class="form-group"><label>Sexo</label><select name="nuevo_sexo" data-nuevo-input required><option value="">Selecciona</option><option value="masculino">Masculino</option><option value="femenino">Femenino</option><option value="otro">Otro</option></select></div>
            <div class="form-group"><label>Fecha nacimiento</label><input type="date" name="nuevo_fecha_nacimiento"></div>
            <div class="form-group"><label>Email</label><input type="email" name="nuevo_email"></div>
            <div class="form-group"><label>Dirección</label><input name="nuevo_direccion"></div>
            <div class="form-group"><label>Ciudad</label><input name="nuevo_ciudad"></div>
            <div class="form-group"><label>Origen</label><select name="nuevo_origen"><option value="Redes sociales">Redes sociales</option><option value="Programa de televisión">Programa de televisión</option><option value="Google">Google</option><option value="Otros">Otros</option></select></div>
            <div class="form-group span-2"><label>Notas</label><textarea name="nuevo_notas"></textarea></div>
            <div class="form-group span-2"><label><input type="checkbox" name="nuevo_tiene_responsable" value="1" data-responsable-toggle> ¿Tiene contacto responsable?</label></div>
            <div class="responsable-fields span-2" data-responsable-fields><div class="form-grid two"><div class="form-group"><label>Nombre responsable</label><input name="nuevo_responsable_nombre" data-responsable-input></div><div class="form-group"><label>Teléfono responsable</label><input name="nuevo_responsable_telefono" data-responsable-input></div><div class="form-group"><label>Parentesco</label><input name="nuevo_responsable_parentesco" data-responsable-input></div></div></div>
            <div class="form-actions span-2">
                <small data-prospect-feedback></small>
                <button type="submit" class="btn btn-primary">Guardar prospecto</button>
            </div>
        </form>
    </div>
</div>
