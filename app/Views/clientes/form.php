<section class="page-head"><div><h1><?= $isEdit ? 'Editar cliente' : 'Nuevo cliente' ?></h1></div></section>
<section class="card form-card">
<form method="POST" action="<?= e($isEdit ? url('/clientes/update/' . $cliente['id']) : url('/clientes/store')) ?>" class="form-grid two" data-responsable-form>
<?= csrf_field() ?>
<?php if ($user['rol'] !== 'sucursal'): ?>
<div class="form-group"><label>Sucursal</label><select name="sucursal_id"><option value="">Sin sucursal</option><?php foreach ($sucursales as $sucursal): ?><option value="<?= e($sucursal['id']) ?>" <?= selected(old('sucursal_id', $cliente['sucursal_id'] ?? ''), $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
<?php else: ?><input type="hidden" name="sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>"><?php endif; ?>
<div class="form-group"><label>Nombre completo</label><input name="nombre_completo" required value="<?= e(old('nombre_completo', $cliente['nombre_completo'] ?? '')) ?>"></div>
<div class="form-group"><label>Teléfono</label><input name="telefono" required value="<?= e(old('telefono', $cliente['telefono'] ?? '')) ?>"></div>
<div class="form-group"><label>Sexo</label><select name="sexo" required><?php $sexo=old('sexo',$cliente['sexo']??''); ?><option value="">Seleccionar</option><option value="masculino" <?= selected($sexo,'masculino') ?>>Masculino</option><option value="femenino" <?= selected($sexo,'femenino') ?>>Femenino</option><option value="otro" <?= selected($sexo,'otro') ?>>Otro</option></select></div>
<div class="form-group"><label>Fecha nacimiento</label><input type="date" name="fecha_nacimiento" value="<?= e(old('fecha_nacimiento', $cliente['fecha_nacimiento'] ?? '')) ?>"></div>
<div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e(old('email', $cliente['email'] ?? '')) ?>"></div>
<div class="form-group"><label>Dirección</label><input name="direccion" value="<?= e(old('direccion', $cliente['direccion'] ?? '')) ?>"></div>
<div class="form-group"><label>Ciudad</label><input name="ciudad" value="<?= e(old('ciudad', $cliente['ciudad'] ?? '')) ?>"></div>
<div class="form-group"><label>Origen</label><?php $origen = old('origen', $cliente['origen'] ?? ''); ?><select name="origen"><option value="">Selecciona</option><option value="Redes sociales" <?= selected($origen, 'Redes sociales') ?>>Redes sociales</option><option value="Programa de televisión" <?= selected($origen, 'Programa de televisión') ?>>Programa de televisión</option><option value="Google" <?= selected($origen, 'Google') ?>>Google</option><option value="Otros" <?= selected($origen, 'Otros') ?>>Otros</option></select></div>
<div class="form-group"><label>Estatus cliente</label><select name="estatus_cliente" required><?php $st=old('estatus_cliente',$cliente['estatus_cliente']??'prospecto'); foreach (['prospecto','cita_agendada','asistio_primera_vez','cliente_activo','inactivo'] as $s): ?><option value="<?= e($s) ?>" <?= selected($st,$s) ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
<div class="form-group span-2"><label>Notas</label><textarea name="notas" rows="3"><?= e(old('notas', $cliente['notas'] ?? '')) ?></textarea></div>
<div class="form-group span-2"><label class="switch-field"><input type="checkbox" name="tiene_responsable" value="1" data-responsable-toggle <?= checked((old('tiene_responsable', (string)($cliente['tiene_responsable'] ?? '0')) === '1')) ?>><span>¿Tiene contacto responsable?</span></label></div>
<div class="responsable-fields span-2" data-responsable-fields>
<div class="form-grid two">
<div class="form-group"><label>Nombre responsable</label><input name="responsable_nombre" data-responsable-input value="<?= e(old('responsable_nombre', $cliente['responsable_nombre'] ?? '')) ?>"></div>
<div class="form-group"><label>Teléfono responsable</label><input name="responsable_telefono" data-responsable-input value="<?= e(old('responsable_telefono', $cliente['responsable_telefono'] ?? '')) ?>"></div>
<div class="form-group"><label>Parentesco</label><input name="responsable_parentesco" data-responsable-input value="<?= e(old('responsable_parentesco', $cliente['responsable_parentesco'] ?? '')) ?>"></div>
</div>
</div>
<div class="form-actions span-2"><a class="btn btn-outline" href="<?= e(url('/clientes')) ?>">Volver</a><button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button></div>
</form>
</section>
