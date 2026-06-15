<div class="row justify-content-center">
<div class="col-12 col-xl-8">

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-bell me-2 text-warning"></i>
            Alerte #<?= $alert['id'] ?>
            — <?= alert_level_badge($alert['alert_level']) ?>
        </h6>
        <a href="<?= url('/alerts') ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>

    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4 text-muted">Appareil</dt>
            <dd class="col-8">
                <a href="<?= url('/devices/' . $alert['device_id']) ?>" class="text-decoration-none fw-semibold">
                    <?= e($alert['device_name']) ?>
                </a>
                <span class="text-muted font-monospace small ms-2"><?= e($alert['ip_address']) ?></span>
            </dd>

            <dt class="col-4 text-muted">Type</dt>
            <dd class="col-8"><?= e($alert['alert_type']) ?></dd>

            <dt class="col-4 text-muted">Message</dt>
            <dd class="col-8"><?= e($alert['message']) ?></dd>

            <dt class="col-4 text-muted">Valeur détectée</dt>
            <dd class="col-8">
                <?php if ($alert['current_value'] !== null): ?>
                <span class="fw-bold text-danger fs-5">
                    <?= number_format((float)$alert['current_value'], 2) ?> %
                </span>
                <span class="text-muted small ms-2">
                    (seuil : <?= number_format((float)$alert['threshold_value'], 0) ?>%)
                </span>
                <?php else: ?>—<?php endif; ?>
            </dd>

            <dt class="col-4 text-muted">Statut</dt>
            <dd class="col-8">
                <?php
                echo match($alert['status']) {
                    'open'         => '<span class="badge bg-danger">Ouverte</span>',
                    'acknowledged' => '<span class="badge bg-warning text-dark">Reconnue</span>',
                    'resolved'     => '<span class="badge bg-success">Résolue</span>',
                    default        => '<span class="badge bg-secondary">' . e($alert['status']) . '</span>',
                };
                ?>
            </dd>

            <dt class="col-4 text-muted">Créée le</dt>
            <dd class="col-8"><?= format_date($alert['created_at']) ?></dd>
        </dl>
    </div>

    <?php if (in_array($alert['status'], ['open', 'acknowledged'])): ?>
    <div class="card-footer d-flex gap-2">
        <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <?php if ($alert['status'] === 'open'): ?>
        <button class="btn btn-warning btn-sm"
                onclick="changeStatus(<?= $alert['id'] ?>, 'acknowledge')">
            <i class="bi bi-check me-1"></i>Reconnaître
        </button>
        <?php endif; ?>

        <button class="btn btn-success btn-sm"
                onclick="changeStatus(<?= $alert['id'] ?>, 'resolve')">
            <i class="bi bi-check-all me-1"></i>Résoudre
        </button>
    </div>
    <?php endif; ?>
</div>

</div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function changeStatus(id, action) {
    fetch(`<?= url('/alerts/') ?>${id}/${action}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?= url('/alerts') ?>';
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('Erreur lors de la mise à jour.'));
}
</script>