<section class="page-head">
    <div>
        <h1>Bloqueos de horario</h1>
        <p>Administra horarios no disponibles por sucursal.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/bloqueos-horario/create')) ?>">Nuevo bloqueo</a>
    </div>
</section>

<section class="card">
    <form method="GET" action="<?= e(url('/bloqueos-horario')) ?>" class="filters">
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
            <label>Tipo</label>
            <select name="tipo_bloqueo">
                <option value="">Todos</option>
                <?php foreach (['fecha_especifica' => 'Fecha específica', 'recurrente_diario' => 'Recurrente diario', 'recurrente_semanal' => 'Recurrente semanal'] as $tipo => $label): ?>
                    <option value="<?= e($tipo) ?>" <?= selected($filters['tipo_bloqueo'], $tipo) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions compact"><button class="btn btn-outline" type="submit">Filtrar</button></div>
    </form>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Sucursal</th><th>Tipo</th><th>Fecha/Día</th><th>Horario</th><th>Motivo</th><th>Activo</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($bloqueos as $bloqueo): ?>
                    <tr>
                        <td><?= e($bloqueo['sucursal_nombre']) ?></td>
                        <td><?= e($bloqueo['tipo_bloqueo']) ?></td>
                        <td>
                            <?php if ($bloqueo['tipo_bloqueo'] === 'fecha_especifica'): ?>
                                <?= e((string)$bloqueo['fecha']) ?>
                            <?php elseif ($bloqueo['tipo_bloqueo'] === 'recurrente_semanal'): ?>
                                <?= e(['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'][(string)$bloqueo['dia_semana']] ?? '-') ?>
                            <?php else: ?>
                                Todos los días
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_time($bloqueo['hora_inicio'])) ?> - <?= e(format_time($bloqueo['hora_fin'])) ?></td>
                        <td><?= e($bloqueo['motivo'] ?: '—') ?></td>
                        <td><?= (int)$bloqueo['activo'] === 1 ? 'Sí' : 'No' ?></td>
                        <td class="actions-cell">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('/bloqueos-horario/edit/' . $bloqueo['id'])) ?>">Editar</a>
                            <form method="POST" action="<?= e(url('/bloqueos-horario/toggle/' . $bloqueo['id'])) ?>"><?= csrf_field() ?><button class="btn btn-warning btn-sm" type="submit"><?= (int)$bloqueo['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button></form>
                            <form method="POST" action="<?= e(url('/bloqueos-horario/delete/' . $bloqueo['id'])) ?>" onsubmit="return confirm('¿Eliminar bloqueo?');"><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Eliminar</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
