<section class="page-head">
    <div>
        <h1><?= $isEdit ? 'Editar bloqueo' : 'Nuevo bloqueo' ?></h1>
        <p>Define horarios no disponibles para agenda por sucursal.</p>
    </div>
</section>

<section class="card form-card">
    <form method="POST" action="<?= e($isEdit ? url('/bloqueos-horario/update/' . $bloqueo['id']) : url('/bloqueos-horario/store')) ?>" class="form-grid two" data-bloqueo-form>
        <?= csrf_field() ?>

        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group"><label>Sucursal</label><select name="sucursal_id" required><?php foreach ($sucursales as $sucursal): ?><option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $bloqueo['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
            <input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
            <div class="form-group"><label>Sucursal</label><input type="text" value="<?= e($user['sucursal_nombre'] ?? 'Sucursal') ?>" disabled></div>
        <?php endif; ?>

        <div class="form-group"><label>Tipo de bloqueo</label><?php $tipo = old('tipo_bloqueo', $bloqueo['tipo_bloqueo'] ?? 'fecha_especifica'); ?><select name="tipo_bloqueo" data-tipo-bloqueo><option value="fecha_especifica" <?= selected($tipo, 'fecha_especifica') ?>>Fecha específica</option><option value="recurrente_diario" <?= selected($tipo, 'recurrente_diario') ?>>Recurrente diario</option><option value="recurrente_semanal" <?= selected($tipo, 'recurrente_semanal') ?>>Recurrente semanal</option></select></div>

        <div class="form-group" data-bloqueo-fecha><label>Fecha</label><input type="date" name="fecha" value="<?= e(old('fecha', $bloqueo['fecha'] ?? '')) ?>"></div>
        <div class="form-group" data-bloqueo-dia><label>Día de semana</label><?php $diaSemana = old('dia_semana', (string)($bloqueo['dia_semana'] ?? '')); ?><select name="dia_semana"><option value="">Selecciona</option><?php foreach (['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'] as $d=>$label): ?><option value="<?= e($d) ?>" <?= selected($diaSemana,$d) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>

        <div class="form-group"><label>Hora inicio</label><input type="time" name="hora_inicio" value="<?= e(substr(old('hora_inicio', $bloqueo['hora_inicio'] ?? '14:00'), 0, 5)) ?>" required></div>
        <div class="form-group"><label>Hora fin</label><input type="time" name="hora_fin" value="<?= e(substr(old('hora_fin', $bloqueo['hora_fin'] ?? '15:00'), 0, 5)) ?>" required></div>

        <div class="form-group span-2"><label>Motivo</label><input type="text" name="motivo" value="<?= e(old('motivo', $bloqueo['motivo'] ?? '')) ?>"></div>
        <div class="form-group span-2"><label class="switch-field"><input type="checkbox" name="activo" value="1" <?= checked((int)old('activo', (string)($bloqueo['activo'] ?? 1)) === 1) ?>><span>Bloqueo activo</span></label></div>

        <div class="form-actions span-2"><a class="btn btn-outline" href="<?= e(url('/bloqueos-horario')) ?>">Volver</a><button class="btn btn-primary" type="submit">Guardar</button></div>
    </form>
</section>
