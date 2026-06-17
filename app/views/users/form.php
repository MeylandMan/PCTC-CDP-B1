<?php
$isEdit = $user !== null;
$action = $isEdit ? url('/users/' . $user['id'] . '/update') : url('/users/store');
$val    = fn(string $k, mixed $d = '') => e($user[$k] ?? $d);
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-7">
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2 text-primary"></i>
            <?= $isEdit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' ?>
        </h6>
    </div>

    <form method="POST" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>

        <div class="card-body">
            <div class="row g-3">

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="firstname" class="form-control"
                           value="<?= $val('firstname') ?>" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="lastname" class="form-control"
                           value="<?= $val('lastname') ?>" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= $val('email') ?>" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Téléphone</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="+241 77 00 00 00"
                           value="<?= $val('phone') ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Rôle <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"
                            <?= ((int)($user['role_id'] ?? 0)) === (int)$role['id'] ? 'selected' : '' ?>>
                            <?= e(str_replace('_', ' ', $role['role_name'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Mot de passe
                        <?= !$isEdit ? '<span class="text-danger">*</span>' : '<span class="text-muted small">(laisser vide pour ne pas changer)</span>' ?>
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="password"
                               class="form-control"
                               placeholder="<?= $isEdit ? 'Nouveau mot de passe…' : 'Minimum 8 caractères' ?>"
                               <?= !$isEdit ? 'required minlength="8"' : '' ?>>
                        <button type="button" class="input-group-text bg-light"
                                onclick="togglePass()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="<?= $isEdit ? url('/users/' . $user['id']) : url('/users') ?>"
               class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                <?= $isEdit ? 'Enregistrer' : 'Créer l\'utilisateur' ?>
            </button>
        </div>
    </form>
</div>
</div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>