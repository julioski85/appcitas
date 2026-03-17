<section class="page-head">
    <div>
        <h1>Programas</h1>
        <p>Administra programas globales y por sucursal para asignarlos a las citas.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= e(url('/programas/create')) ?>">Nuevo programa</a>
    </div>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Alcance</th>
                    <th>Estatus</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programas as $programa): ?>
                    <tr>
                        <td><?= e($programa['nombre']) ?></td>
                        <td><?= empty($programa['sucursal_id']) ? 'Global' : e($programa['sucursal_nombre'] ?? 'Sucursal') ?></td>
                        <td><?= (int)$programa['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="actions-cell">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('/programas/edit/' . $programa['id'])) ?>">Editar</a>
                            <form method="POST" action="<?= e(url('/programas/toggle/' . $programa['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-warning btn-sm" type="submit"><?= (int)$programa['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
