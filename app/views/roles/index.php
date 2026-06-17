<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-shield-lock me-2 text-primary"></i>
            Rôles & Permissions
            <span class="badge bg-secondary ms-1"><?= count($roles) ?></span>
        </h6>
        <?php if ($this->hasRole('super_admin')): ?>
        <a href="<?= url('/roles/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Créer un rôle
        </a>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Rôle</th>
                        <th>Description</th>
                        <th>Utilisateurs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td class="text-muted small"><?= $role['id'] ?></td>
                        <td>
                            <span class="badge bg-primary fs-6">
                                <?= e(str_replace('_', ' ', $role['role_name'])) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= e($role['description'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= $role['user_count'] ?> utilisateur<?= $role['user_count'] > 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($this->hasRole('super_admin')): ?>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/roles/' . $role['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($role['user_count'] == 0): ?>
                                <form method="POST"
                                      action="<?= url('/roles/' . $role['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Supprimer ce rôle ?')"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger py-0 px-2" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>