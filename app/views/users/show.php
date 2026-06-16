<?php
/**
 * Vue : Détail utilisateur
 * Variable : $user (avec role_name)
 */
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-7">

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-person-circle me-2 text-primary"></i>
            <?= e($user['firstname'] . ' ' . $user['lastname']) ?>
        </h6>
        <div class="d-flex gap-2">
            <?php if ($canAct): ?>
            <a href="<?= url('/users/' . $user['id'] . '/edit') ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Modifier
            </a>
            <?php endif; ?>
            <a href="<?= url('/users') ?>" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Avatar initiales -->
        <div class="text-center mb-4">
            <div class="mx-auto mb-3" style="
                width:72px;height:72px;border-radius:50%;
                background:linear-gradient(135deg,#4f46e5,#7c3aed);
                display:flex;align-items:center;justify-content:center;
                color:#fff;font-weight:700;font-size:1.5rem;">
                <?= strtoupper(substr($user['firstname'],0,1) . substr($user['lastname'],0,1)) ?>
            </div>
            <h5 class="mb-1"><?= e($user['firstname'] . ' ' . $user['lastname']) ?></h5>
            <span class="badge bg-primary">
                <?= e(str_replace('_', ' ', $user['role_name'])) ?>
            </span>
            <?php if (!$user['is_active']): ?>
            <span class="badge bg-danger ms-1">Inactif</span>
            <?php endif; ?>
        </div>

        <dl class="row small mb-0">
            <dt class="col-4 text-muted">Email</dt>
            <dd class="col-8"><?= e($user['email']) ?></dd>

            <dt class="col-4 text-muted">Téléphone</dt>
            <dd class="col-8"><?= e($user['phone'] ?? '—') ?></dd>

            <dt class="col-4 text-muted">Dernière connexion</dt>
            <dd class="col-8"><?= $user['last_login'] ? format_date($user['last_login']) : 'Jamais' ?></dd>

            <dt class="col-4 text-muted">Dernière IP</dt>
            <dd class="col-8 font-monospace"><?= e($user['last_ip'] ?? '—') ?></dd>

            <dt class="col-4 text-muted">Créé le</dt>
            <dd class="col-8"><?= format_date($user['created_at']) ?></dd>
        </dl>
    </div>

    <!-- Danger zone -->
    <?php if ($user['id'] !== ($_SESSION['user']['id'] ?? 0) && $canAct): ?>
    <div class="card-footer d-flex gap-2">
        <form method="POST"
              action="<?= url('/users/' . $user['id'] . '/delete') ?>"
              onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">
                <i class="bi bi-trash me-1"></i>Supprimer le compte
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

</div>
</div>