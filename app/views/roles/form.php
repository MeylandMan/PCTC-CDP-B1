<?php
/**
 * Vue : Formulaire rôle (création/édition) avec permissions
 *
 * Variables :
 *   $role        — null ou tableau du rôle
 *   $permissions — toutes les permissions disponibles
 *   $rolePermIds — IDs des permissions actuellement cochées
 */

$isEdit = $role !== null;
$action = $isEdit ? url('/roles/' . $role['id'] . '/update') : url('/roles/store');
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-8">
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2 text-primary"></i>
            <?= $isEdit ? 'Modifier le rôle' : 'Créer un rôle' ?>
        </h6>
    </div>

    <form method="POST" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>

        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Nom du rôle <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="role_name" class="form-control"
                           placeholder="technicien_reseau"
                           value="<?= e($role['role_name'] ?? '') ?>" required>
                    <small class="text-muted">Utiliser le snake_case, sans espaces.</small>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" class="form-control"
                           value="<?= e($role['description'] ?? '') ?>">
                </div>
            </div>

            <hr>

            <label class="form-label fw-semibold mb-3">
                <i class="bi bi-key me-1"></i>Permissions
            </label>

            <div class="row g-2">
                <?php foreach ($permissions as $perm): ?>
                <div class="col-12 col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="permissions[]" value="<?= $perm['id'] ?>"
                               id="perm-<?= $perm['id'] ?>"
                               <?= in_array($perm['id'], $rolePermIds) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="perm-<?= $perm['id'] ?>">
                            <span class="fw-semibold"><?= e($perm['permission_name']) ?></span>
                            <?php if ($perm['description']): ?>
                            <div class="text-muted" style="font-size:.75rem;">
                                <?= e($perm['description']) ?>
                            </div>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="<?= url('/roles') ?>" class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                <?= $isEdit ? 'Enregistrer' : 'Créer le rôle' ?>
            </button>
        </div>
    </form>
</div>
</div>
</div>