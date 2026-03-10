<section class="page-head">
    <div>
        <h1>Calendario y citas</h1>
        <p>Consulta disponibilidad en tiempo real y agenda sin encimar horarios.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/citas/create')) ?>">Nueva cita</a>
    </div>
</section>

<section class="card">
    <form method="GET" action="<?= e(url('/citas')) ?>" class="filters">
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
        <?php else: ?>
            <input type="hidden" name="sucursal_id" value="<?= e($user['sucursal_id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Estatus</label>
            <select name="estatus">
                <option value="">Todos</option>
                <?php foreach (['agendada','confirmada','asistio','no_asistio','cancelada','reprogramada'] as $est): ?>
                <option value="<?= e($est) ?>" <?= selected($filters['estatus'], $est) ?>><?= e($est) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions compact">
            <button class="btn btn-outline" type="submit">Filtrar</button>
        </div>
    </form>
</section>

<section class="card">
    <div class="calendar-toolbar">
        <div class="calendar-nav">
            <button type="button" class="btn btn-outline btn-sm" data-cal-action="prev">←</button>
            <button type="button" class="btn btn-outline btn-sm" data-cal-action="today">Hoy</button>
            <button type="button" class="btn btn-outline btn-sm" data-cal-action="next">→</button>
        </div>
        <div class="calendar-title" id="calendarTitle"></div>
        <div class="calendar-views">
            <button type="button" class="btn btn-outline btn-sm is-active" data-cal-view="month">Mes</button>
            <button type="button" class="btn btn-outline btn-sm" data-cal-view="week">Semana</button>
            <button type="button" class="btn btn-outline btn-sm" data-cal-view="day">Día</button>
        </div>
    </div>

    <div
        id="calendarApp"
        data-endpoint="<?= e(url('/api/citas')) ?>"
        data-create-url="<?= e(url('/citas/create')) ?>"
        data-sucursal="<?= e((string)$filters['sucursal_id']) ?>"
        data-estatus="<?= e((string)$filters['estatus']) ?>"
    ></div>
</section>

<section class="card">
    <h3>Últimas 20 citas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Servicio</th>
                    <th>Código promo</th>
                    <th>Estatus</th>
                    <th>Origen</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimasCitas as $cita): ?>
                <tr>
                    <td><?= e(format_date($cita['fecha'])) ?></td>
                    <td><?= e(format_time($cita['hora_inicio'])) ?> - <?= e(format_time($cita['hora_fin'])) ?></td>
                    <td><?= e($cita['cliente_nombre']) ?><br><small><?= e($cita['cliente_telefono']) ?></small></td>
                    <td><?= e($cita['sucursal_nombre']) ?></td>
                    <td><?= e($cita['servicio']) ?></td>
                    <td><?= e($cita['codigo_promocion'] ?: '—') ?></td>
                    <td><span class="badge badge-<?= e($cita['estatus']) ?>"><?= e($cita['estatus']) ?></span></td>
                    <td><?= e($cita['origen']) ?></td>
                    <td class="actions-cell">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('/citas/edit/' . $cita['id'])) ?>">Editar</a>
                        <form method="POST" action="<?= e(url('/citas/cancel/' . $cita['id'])) ?>" onsubmit="return confirm('¿Cancelar cita?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-warning btn-sm" type="submit">Cancelar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
