<section class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Resumen operativo de citas, usuarios y horarios.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/citas/create')) ?>">Nueva cita</a>
    </div>
</section>

<section class="stats-grid">
    <div class="card stat-card">
        <div class="stat-label">Canceladas este mes</div>
        <div class="stat-value"><?= (int)$stats['canceladas'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Sucursales activas</div>
        <div class="stat-value"><?= count($stats['porSucursal']) ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Usuarios visibles</div>
        <div class="stat-value"><?= count($stats['porUsuario']) ?></div>
    </div>


    <div class="card stat-card">
        <div class="stat-label">Prospectos nuevos</div>
        <div class="stat-value"><?= (int)$stats['prospectos_nuevos'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Clientes activos</div>
        <div class="stat-value"><?= (int)$stats['clientes_activos'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Citas no asistidas</div>
        <div class="stat-value"><?= (int)$stats['no_asistidas'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="stat-label">Conversión prospecto-cliente</div>
        <div class="stat-value"><?= number_format((float)$stats['conversion'], 1) ?>%</div>
    </div>
</section>

<section class="two-col">
    <div class="card">
        <h3>Citas por sucursal (mes actual)</h3>
        <div class="bar-list">
            <?php
            $max = max(array_map(fn($i) => (int)$i['total'], $stats['porSucursal']) ?: [1]);
            foreach ($stats['porSucursal'] as $item):
                $pct = $max > 0 ? max(8, (int)(($item['total'] / $max) * 100)) : 8;
            ?>
            <div class="bar-item">
                <div class="bar-top">
                    <span><?= e($item['nombre']) ?></span>
                    <strong><?= (int)$item['total'] ?></strong>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= $pct ?>%; background: <?= e($item['color_calendario']) ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Citas por usuario (mes actual)</h3>
        <div class="bar-list">
            <?php
            $maxUser = max(array_map(fn($i) => (int)$i['total'], $stats['porUsuario']) ?: [1]);
            foreach ($stats['porUsuario'] as $item):
                $pct = $maxUser > 0 ? max(8, (int)(($item['total'] / $maxUser) * 100)) : 8;
            ?>
            <div class="bar-item">
                <div class="bar-top">
                    <span><?= e($item['nombre']) ?></span>
                    <strong><?= (int)$item['total'] ?></strong>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="two-col">
    <div class="card">
        <h3>Citas por día (últimos 7 días)</h3>
        <div class="bar-list">
            <?php
            $maxDay = max(array_map(fn($i) => (int)$i['total'], $stats['porDia']) ?: [1]);
            foreach ($stats['porDia'] as $item):
                $pct = $maxDay > 0 ? max(8, (int)(($item['total'] / $maxDay) * 100)) : 8;
            ?>
            <div class="bar-item">
                <div class="bar-top">
                    <span><?= e(format_date($item['fecha'])) ?></span>
                    <strong><?= (int)$item['total'] ?></strong>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Horarios más ocupados</h3>
        <div class="bar-list">
            <?php
            $maxHour = max(array_map(fn($i) => (int)$i['total'], $stats['horarios']) ?: [1]);
            foreach ($stats['horarios'] as $item):
                $pct = $maxHour > 0 ? max(8, (int)(($item['total'] / $maxHour) * 100)) : 8;
            ?>
            <div class="bar-item">
                <div class="bar-top">
                    <span><?= e($item['hora']) ?></span>
                    <strong><?= (int)$item['total'] ?></strong>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>



<section class="card">
    <h3>Control operativo de cierre de citas</h3>
    <form method="GET" action="<?= e(url('/dashboard')) ?>" class="filters">
        <?php if ($user['rol'] !== 'sucursal'): ?>
        <div class="form-group">
            <label>Sucursal</label>
            <select name="overdue_sucursal_id">
                <option value="">Todas</option>
                <?php foreach (($sucursales ?? []) as $sucursal): ?>
                    <option value="<?= e($sucursal['id']) ?>" <?= selected($overdueFilters['sucursal_id'] ?? '', $sucursal['id']) ?>><?= e($sucursal['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="overdue_sucursal_id" value="<?= e((string)$user['sucursal_id']) ?>">
        <?php endif; ?>
        <div class="form-group"><label>Desde</label><input type="date" name="overdue_start" value="<?= e($overdueFilters['start'] ?? '') ?>"></div>
        <div class="form-group"><label>Hasta</label><input type="date" name="overdue_end" value="<?= e($overdueFilters['end'] ?? '') ?>"></div>
        <div class="form-actions compact"><button class="btn btn-outline" type="submit">Aplicar</button></div>
    </form>

    <section class="stats-grid" style="margin-top: 12px;">
        <div class="card stat-card">
            <div class="stat-label">Vencidas agendada</div>
            <div class="stat-value"><?= (int)($stats['cierre_operativo']['vencidas_agendada'] ?? 0) ?></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Vencidas confirmada</div>
            <div class="stat-value"><?= (int)($stats['cierre_operativo']['vencidas_confirmada'] ?? 0) ?></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Cerradas asistio</div>
            <div class="stat-value"><?= (int)($stats['cierre_operativo']['cerradas_asistio'] ?? 0) ?></div>
        </div>
        <div class="card stat-card">
            <div class="stat-label">Cerradas no_asistio</div>
            <div class="stat-value"><?= (int)($stats['cierre_operativo']['cerradas_no_asistio'] ?? 0) ?></div>
        </div>
    </section>

    <h4 style="margin-top:14px;">Pendientes de cierre</h4>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Cliente</th><th>Sucursal</th><th>Estatus</th><th>Tiempo desde vencimiento</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($pendingOverdue ?? []) as $cita): ?>
                <?php $mins = max(0, (int)($cita['minutos_desde_vencimiento'] ?? 0)); $dias = intdiv($mins, 1440); $horas = intdiv($mins % 1440, 60); $min = $mins % 60; ?>
                <tr>
                    <td><?= e(format_date($cita['fecha'])) ?></td>
                    <td><?= e(format_time($cita['hora_inicio'])) ?></td>
                    <td><?= e(format_time($cita['hora_fin'])) ?></td>
                    <td><?= e($cita['cliente_nombre']) ?></td>
                    <td><?= e($cita['sucursal_nombre']) ?></td>
                    <td><span class="badge badge-<?= e($cita['estatus']) ?>"><?= e($cita['estatus']) ?></span></td>
                    <td><?= e(($dias > 0 ? $dias . 'd ' : '') . $horas . 'h ' . $min . 'm') ?></td>
                    <td><a class="btn btn-outline btn-sm" href="<?= e(url('/citas/edit/' . $cita['id'])) ?>">Editar / Cerrar</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingOverdue ?? [])): ?>
                <tr><td colspan="8">No hay citas vencidas pendientes con los filtros seleccionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h3>Últimas citas</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Servicio</th>
                    <th>Estatus</th>
                    <th>Origen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['ultimas'] as $cita): ?>
                <tr>
                    <td><?= e(format_date($cita['fecha'])) ?></td>
                    <td><?= e(format_time($cita['hora_inicio'])) ?> - <?= e(format_time($cita['hora_fin'])) ?></td>
                    <td><?= e($cita['cliente_nombre']) ?></td>
                    <td><?= e($cita['sucursal_nombre']) ?></td>
                    <td><?= e($cita['servicio']) ?></td>
                    <td><span class="badge badge-<?= e($cita['estatus']) ?>"><?= e($cita['estatus']) ?></span></td>
                    <td><?= e($cita['origen']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
