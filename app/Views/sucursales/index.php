<section class="page-head">
    <div>
        <h1>Sucursales</h1>
        <p>Administra nombres, teléfonos, direcciones, color y horarios de atención por sucursal.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/sucursales/create')) ?>">Nueva sucursal</a>
    </div>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Color</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Capacidad</th>
                    <th>Horario</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sucursales as $sucursal): ?>
                <tr>
                    <td><span class="color-dot" style="background: <?= e($sucursal['color_calendario']) ?>"></span></td>
                    <td><?= e($sucursal['nombre']) ?></td>
                    <td><?= e($sucursal['direccion']) ?></td>
                    <td><?= e($sucursal['telefono']) ?></td>
                    <td><?= e((string)($sucursal['capacidad_simultanea'] ?? 1)) ?></td>
                    <td><?= e(substr((string)($sucursal['hora_apertura'] ?? '08:00'), 0, 5)) ?> - <?= e(substr((string)($sucursal['hora_cierre'] ?? '20:00'), 0, 5)) ?></td>
                    <td class="actions-cell">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('/sucursales/edit/' . $sucursal['id'])) ?>">Editar</a>
                        <form method="POST" action="<?= e(url('/sucursales/delete/' . $sucursal['id'])) ?>" onsubmit="return confirm('¿Eliminar sucursal?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
