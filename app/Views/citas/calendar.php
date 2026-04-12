<section class="page-head">
    <div>
        <h1>Calendario</h1>
        <p>Consulta disponibilidad en tiempo real y agenda sin encimar horarios.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/citas/create')) ?>">Nueva cita</a>
    </div>
</section>

<section class="card">
    <form method="GET" action="<?= e(url('/calendario')) ?>" class="filters">
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
        data-open-hour="<?= e($calendarOpenHour ?? '08:00') ?>"
        data-close-hour="<?= e($calendarCloseHour ?? '20:00') ?>"
    ></div>
</section>
