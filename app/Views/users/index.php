<section class="page-head">
    <div>
        <h1>Usuarios</h1>
        <p>Administra accesos, roles, sucursal y estatus del personal.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/users/create')) ?>">Nuevo usuario</a>
    </div>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Sucursal</th>
                    <th>Estatus</th>
                    <th>Creación</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= e((string)$item['id']) ?></td>
                    <td><?= e($item['nombre']) ?></td>
                    <td><?= e($item['email']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $item['rol']))) ?></td>
                    <td><?= e($item['sucursal_nombre'] ?? '—') ?></td>
                    <td>
                        <?php if ((int)$item['activo'] === 1): ?>
                            <span class="badge badge-atendida">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-cancelada">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(format_date($item['created_at'])) ?></td>
                    <td class="actions-cell">
                        <a class="btn btn-outline btn-sm" href="<?= e(url('/users/edit/' . $item['id'])) ?>">Editar</a>
                        <a class="btn btn-warning btn-sm" href="<?= e(url('/users/password/' . $item['id'])) ?>">Cambiar contraseña</a>
                        <form method="POST" action="<?= e(url('/users/toggle-active/' . $item['id'])) ?>" onsubmit="return confirm('¿Confirmas cambiar el estatus de este usuario?');">
                            <?= csrf_field() ?>
                            <button class="btn <?= (int)$item['activo'] === 1 ? 'btn-danger' : 'btn-primary' ?> btn-sm" type="submit">
                                <?= (int)$item['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
