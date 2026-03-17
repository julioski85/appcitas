<section class="page-head">
    <div>
        <h1>Clientes</h1>
        <p>CRM administrativo para prospectos y clientes vinculados con citas.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/clientes/create')) ?>">Nuevo cliente</a>
    </div>
</section>

<section class="card">
    <form method="GET" class="filters">
        <div class="form-group">
            <label>Buscar</label>
            <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o teléfono">
        </div>
        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group">
            <label>Sucursal</label>
            <select name="sucursal_id">
                <option value="">Todas</option>
                <?php foreach ($sucursales as $sucursal): ?>
                <option value="<?= e($sucursal['id']) ?>" <?= selected($filters['sucursal_id'], $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label>Estatus</label>
            <select name="estatus_cliente">
                <option value="">Todos</option>
                <?php foreach (['prospecto','cita_agendada','asistio_primera_vez','cliente_activo','inactivo'] as $status): ?>
                <option value="<?= e($status) ?>" <?= selected($filters['estatus_cliente'], $status) ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions compact"><button class="btn btn-outline">Filtrar</button></div>
    </form>
</section>

<section class="card">
<div class="table-wrap"><table class="table"><thead><tr>
<th>ID</th><th>Nombre</th><th>Teléfono</th><th>Sexo</th><th>Edad</th><th>Sucursal</th><th>Estatus</th><th>Responsable</th><th>Última cita</th><th>Próxima cita</th><th>Alta</th><th></th>
</tr></thead><tbody>
<?php foreach ($clientes as $cliente): ?>
<tr>
<td><?= (int)$cliente['id'] ?></td>
<td><?= e($cliente['nombre_completo']) ?></td>
<td><?= e($cliente['telefono']) ?></td>
<td><?= e($cliente['sexo']) ?></td>
<td><?= $cliente['fecha_nacimiento'] ? (int)floor((time()-strtotime($cliente['fecha_nacimiento']))/31557600) : '—' ?></td>
<td><?= e($cliente['sucursal_nombre'] ?: '—') ?></td>
<td><span class="badge"><?= e($cliente['estatus_cliente']) ?></span></td>
<td><?= (int)$cliente['tiene_responsable'] === 1 ? 'Sí' : 'No' ?></td>
<td><?= $cliente['ultima_cita_programada'] ? e(format_date(substr($cliente['ultima_cita_programada'],0,10)).' '.substr($cliente['ultima_cita_programada'],11,5)) : '—' ?></td>
<td><?= $cliente['proxima_cita'] ? e(format_date(substr($cliente['proxima_cita'],0,10)).' '.substr($cliente['proxima_cita'],11,5)) : '—' ?></td>
<td><?= $cliente['created_at'] ? e(format_date(substr($cliente['created_at'],0,10))) : '—' ?></td>
<td class="actions-cell"><a class="btn btn-outline btn-sm" href="<?= e(url('/clientes/' . $cliente['id'])) ?>">Ver</a><a class="btn btn-outline btn-sm" href="<?= e(url('/clientes/edit/' . $cliente['id'])) ?>">Editar</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</section>
