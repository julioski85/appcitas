<section class="page-head"><div><h1>Detalle cliente #<?= (int)$cliente['id'] ?></h1></div><div class="page-actions"><a class="btn btn-outline" href="<?= e(url('/clientes/edit/'.$cliente['id'])) ?>">Editar</a></div></section>
<section class="card">
<p><strong>Nombre:</strong> <?= e($cliente['nombre_completo']) ?></p>
<p><strong>Teléfono:</strong> <?= e($cliente['telefono']) ?></p>
<p><strong>Sexo:</strong> <?= e($cliente['sexo']) ?></p>
<p><strong>Edad:</strong> <?= $cliente['fecha_nacimiento'] ? (int)floor((time()-strtotime($cliente['fecha_nacimiento']))/31557600) : '—' ?></p>
<p><strong>Sucursal:</strong> <?= e($cliente['sucursal_nombre'] ?: '—') ?></p>
<p><strong>Estatus:</strong> <?= e($cliente['estatus_cliente']) ?></p>
<p><strong>Origen:</strong> <?= e($cliente['origen'] ?: '—') ?></p>
<p><strong>Notas:</strong> <?= nl2br(e($cliente['notas'] ?: '—')) ?></p>
<p><strong>Primera cita:</strong> <?= e($cliente['primera_cita_at'] ?: '—') ?></p>
<p><strong>Última cita:</strong> <?= e($cliente['ultima_cita_at'] ?: '—') ?></p>
<?php if ((int)$cliente['tiene_responsable'] === 1): ?>
<hr>
<p><strong>Responsable:</strong> <?= e($cliente['responsable_nombre']) ?></p>
<p><strong>Teléfono responsable:</strong> <?= e($cliente['responsable_telefono']) ?></p>
<p><strong>Parentesco:</strong> <?= e($cliente['responsable_parentesco']) ?></p>
<?php endif; ?>
</section>
<section class="card"><h3>Historial de citas</h3><div class="table-wrap"><table class="table"><thead><tr><th>Fecha</th><th>Horario</th><th>Sucursal</th><th>Servicio</th><th>Estatus</th></tr></thead><tbody><?php foreach($historial as $c): ?><tr><td><?= e(format_date($c['fecha'])) ?></td><td><?= e(format_time($c['hora_inicio'])) ?> - <?= e(format_time($c['hora_fin'])) ?></td><td><?= e($c['sucursal_nombre']) ?></td><td><?= e($c['servicio']) ?></td><td><?= e($c['estatus']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
