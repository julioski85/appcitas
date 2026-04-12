<?php
$queryBase = $filters;
unset($queryBase['page']);
?>
<section class="page-head">
    <div>
        <h1>Citas</h1>
        <p>Listado completo de citas con filtros, paginación y actualización rápida de estatus.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline" href="<?= e(url('/calendario')) ?>">Ver calendario</a>
        <a class="btn btn-primary" href="<?= e(url('/citas/create')) ?>">Nueva cita</a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = document.querySelector('.citas-table[data-status-update-url]');
    if (!table) return;

    var endpointBase = table.getAttribute('data-status-update-url');
    var csrf = table.getAttribute('data-csrf') || '';

    function setFeedback(citaId, message, state) {
        var feedback = table.querySelector('[data-status-feedback="' + citaId + '"]');
        if (!feedback) return;
        feedback.textContent = message || '';
        feedback.classList.remove('is-ok', 'is-error');
        if (state === 'ok') feedback.classList.add('is-ok');
        if (state === 'error') feedback.classList.add('is-error');
    }

    function setSavingState(select, button, saving) {
        select.classList.toggle('is-loading', saving);
        select.disabled = saving;
        if (button) {
            button.disabled = saving;
            button.textContent = saving ? 'Actualizando...' : 'Actualizar';
        }
    }

    function saveStatus(select, button) {
        var citaId = select.getAttribute('data-cita-id');
        var originalValue = select.getAttribute('data-original-status') || select.value;
        var status = select.value;

        if (status === originalValue) {
            setFeedback(citaId, 'Sin cambios por guardar.', '');
            return;
        }

        var body = new URLSearchParams();
        body.append('_csrf', csrf);
        body.append('estatus', status);

        setSavingState(select, button, true);
        setFeedback(citaId, 'Guardando...', '');

        fetch(endpointBase + '/' + encodeURIComponent(citaId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { ok: response.ok, payload: payload || {} };
            });
        }).then(function (result) {
            if (!result.ok || !result.payload.ok) {
                throw new Error((result.payload && result.payload.message) ? result.payload.message : 'No se pudo actualizar el estatus.');
            }
            select.setAttribute('data-original-status', status);
            setFeedback(citaId, 'Actualizado', 'ok');
        }).catch(function (error) {
            select.value = originalValue;
            setFeedback(citaId, (error && error.message) ? error.message : 'Error al actualizar estatus.', 'error');
        }).finally(function () {
            setSavingState(select, button, false);
        });
    }

    table.querySelectorAll('.inline-status[data-cita-id]').forEach(function (select) {
        var citaId = select.getAttribute('data-cita-id');
        var button = table.querySelector('[data-status-save="' + citaId + '"]');

        select.addEventListener('change', function () {
            var changed = select.value !== (select.getAttribute('data-original-status') || select.value);
            setFeedback(citaId, changed ? 'Cambio pendiente. Presiona Actualizar.' : 'Sin cambios por guardar.', '');
        });

        if (button) {
            button.addEventListener('click', function () {
                saveStatus(select, button);
            });
        }
    });
});
</script>

<section class="card">
    <form method="GET" action="<?= e(url('/citas')) ?>" class="filters filters-citas">
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
                <?php foreach ($statusOptions as $est): ?>
                    <option value="<?= e($est) ?>" <?= selected($filters['estatus'], $est) ?>><?= e($est) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Buscar</label>
            <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="ID, cliente, teléfono o servicio">
        </div>

        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="start" value="<?= e($filters['start']) ?>">
        </div>

        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="end" value="<?= e($filters['end']) ?>">
        </div>

        <div class="form-actions compact">
            <button class="btn btn-outline" type="submit">Filtrar</button>
        </div>
    </form>
</section>

<section class="card">
    <div class="table-meta">
        <strong><?= e((string)$total) ?></strong> cita(s) encontradas · página <?= e((string)$page) ?> de <?= e((string)$totalPages) ?>
    </div>

    <div class="table-wrap">
        <table class="table citas-table" data-status-update-url="<?= e(url('/citas/update-status')) ?>" data-csrf="<?= e(csrf_token()) ?>">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Hora inicio</th>
                    <th>Hora fin</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Servicio</th>
                    <th>Sucursal</th>
                    <th>Origen</th>
                    <th>Estatus</th>
                    <th>Creado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="12"><small>No hay citas para los filtros aplicados.</small></td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($citas as $cita): ?>
                <tr>
                    <td>#<?= e((string)$cita['id']) ?></td>
                    <td><?= e(format_date($cita['fecha'])) ?></td>
                    <td><?= e(format_time($cita['hora_inicio'])) ?></td>
                    <td><?= e(format_time($cita['hora_fin'])) ?></td>
                    <td><?= e($cita['cliente_nombre']) ?></td>
                    <td><?= e($cita['cliente_telefono']) ?></td>
                    <td><?= e($cita['servicio']) ?></td>
                    <td><?= e($cita['sucursal_nombre']) ?></td>
                    <td><?= e($cita['origen']) ?></td>
                    <td>
                        <div class="inline-status-wrap">
                            <select class="inline-status" data-cita-id="<?= e((string)$cita['id']) ?>" data-original-status="<?= e($cita['estatus']) ?>" aria-label="Cambiar estatus de cita <?= e((string)$cita['id']) ?>">
                                <?php foreach ($statusOptions as $est): ?>
                                    <option value="<?= e($est) ?>" <?= selected($cita['estatus'], $est) ?>><?= e($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline btn-sm" data-status-save="<?= e((string)$cita['id']) ?>">Actualizar</button>
                            <span class="inline-status-feedback" data-status-feedback="<?= e((string)$cita['id']) ?>"></span>
                        </div>
                    </td>
                    <td><?= e($cita['creador_nombre'] ?: 'Sistema') ?></td>
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

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php
        $prevQuery = array_merge($queryBase, ['page' => max(1, $page - 1)]);
        $nextQuery = array_merge($queryBase, ['page' => min($totalPages, $page + 1)]);
        ?>
        <a class="btn btn-outline btn-sm <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : e(url('/citas?' . http_build_query($prevQuery))) ?>">Anterior</a>

        <div class="pagination-pages">
            <?php
            $windowStart = max(1, $page - 2);
            $windowEnd = min($totalPages, $page + 2);
            for ($i = $windowStart; $i <= $windowEnd; $i++):
                $pageQuery = array_merge($queryBase, ['page' => $i]);
            ?>
                <a class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" href="<?= e(url('/citas?' . http_build_query($pageQuery))) ?>"><?= e((string)$i) ?></a>
            <?php endfor; ?>
        </div>

        <a class="btn btn-outline btn-sm <?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : e(url('/citas?' . http_build_query($nextQuery))) ?>">Siguiente</a>
    </div>
    <?php endif; ?>
</section>
