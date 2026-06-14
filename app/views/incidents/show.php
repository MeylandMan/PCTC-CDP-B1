<?php
$statusLabels = [
    'open'     => 'Ouvert',
    'incoming' => 'En cours',
    'waiting'  => 'En attente',
    'resolved' => 'Résolu',
    'closed'   => 'Fermé',
];
$statusOrder = ['open', 'incoming', 'waiting', 'resolved', 'closed'];
?>

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

<div class="row g-3">

    <!-- Colonne principale -->
    <div class="col-12 col-xl-8">

        <!-- En-tête incident -->
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="card-title mb-0 fw-semibold">
                        <i class="bi bi-ticket-detailed me-2 text-info"></i>
                        Incident #<?= $incident['id'] ?>
                    </h6>
                    <?= incident_priority_badge($incident['priority']) ?>
                    <?= incident_status_badge($incident['status']) ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/incidents/' . $incident['id'] . '/edit') ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Modifier
                    </a>
                    <a href="<?= url('/incidents') ?>" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Retour
                    </a>
                </div>
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?= e($incident['title']) ?></h5>

                <?php if ($incident['description']): ?>
                <div class="bg-light rounded p-3 mb-3 small">
                    <?= nl2br(e($incident['description'])) ?>
                </div>
                <?php endif; ?>

                <dl class="row mb-0 small">
                    <dt class="col-sm-3 text-muted">Appareil</dt>
                    <dd class="col-sm-9">
                        <a href="<?= url('/devices/' . $incident['device_id']) ?>"
                           class="text-decoration-none fw-semibold">
                            <i class="bi bi-hdd-network me-1"></i>
                            <?= e($incident['device_name']) ?>
                        </a>
                        <span class="text-muted font-monospace ms-2"><?= e($incident['ip_address']) ?></span>
                    </dd>

                    <dt class="col-sm-3 text-muted">Assigné à</dt>
                    <dd class="col-sm-9 fw-semibold"><?= e($incident['assigned_to_name']) ?></dd>

                    <dt class="col-sm-3 text-muted">Ouvert le</dt>
                    <dd class="col-sm-9"><?= format_date($incident['opened_at']) ?></dd>

                    <?php if ($incident['resolved_at']): ?>
                    <dt class="col-sm-3 text-muted">Résolu le</dt>
                    <dd class="col-sm-9 text-success fw-semibold">
                        <?= format_date($incident['resolved_at']) ?>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Cycle de vie visuel -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-arrow-right-circle me-2 text-primary"></i>
                    Cycle de vie
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <?php foreach ($statusOrder as $i => $s):
                        $isCurrent = $incident['status'] === $s;
                        $isPast    = array_search($s, $statusOrder) < array_search($incident['status'], $statusOrder);
                        $color     = $isCurrent ? 'primary' : ($isPast ? 'success' : 'light');
                        $textColor = ($isCurrent || $isPast) ? 'text-white' : 'text-muted';
                    ?>
                    <div class="text-center flex-fill">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                                    bg-<?= $color ?> <?= $textColor ?>"
                             style="width:36px;height:36px;font-size:.8rem;font-weight:700;">
                            <?php if ($isPast): ?>
                                <i class="bi bi-check-lg"></i>
                            <?php else: ?>
                                <?= $i + 1 ?>
                            <?php endif; ?>
                        </div>
                        <div class="small <?= $isCurrent ? 'fw-bold text-primary' : 'text-muted' ?>">
                            <?= $statusLabels[$s] ?>
                        </div>
                    </div>
                    <?php if ($i < count($statusOrder) - 1): ?>
                    <div class="flex-fill" style="height:2px;background:var(--bs-border-color);margin-bottom:1.4rem;"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boutons de transition -->
            <?php if (!empty($nextStatuses) && $incident['status'] !== 'closed'): ?>
            <div class="card-footer d-flex gap-2 flex-wrap">
                <span class="small text-muted align-self-center">Passer à :</span>
                <?php foreach ($nextStatuses as $ns):
                    $btnClass = match($ns) {
                        'incoming' => 'warning',
                        'waiting'  => 'info',
                        'resolved' => 'success',
                        'closed'   => 'secondary',
                        default    => 'primary',
                    };
                ?>
                <button type="button"
                        class="btn btn-sm btn-<?= $btnClass ?>"
                        onclick="updateStatus('<?= $ns ?>')">
                    <?= $statusLabels[$ns] ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Colonne latérale -->
    <div class="col-12 col-xl-4">

        <!-- Réassignation -->
        <?php if (!in_array($incident['status'], ['resolved', 'closed'])): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-person-check me-2 text-primary"></i>
                    Réassigner
                </h6>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="<?= url('/incidents/' . $incident['id'] . '/assign') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <select name="assigned_to" class="form-select form-select-sm">
                            <?php foreach ($techniciens as $tech): ?>
                            <option value="<?= $tech['id'] ?>"
                                <?= $tech['id'] === $incident['assigned_to'] ? 'selected' : '' ?>>
                                <?= e($tech['firstname'] . ' ' . $tech['lastname']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-person-check me-1"></i>Réassigner
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Infos rapides -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-info-circle me-2 text-muted"></i>
                    Résumé
                </h6>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Priorité</span>
                    <?= incident_priority_badge($incident['priority']) ?>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Statut</span>
                    <?= incident_status_badge($incident['status']) ?>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Ouvert</span>
                    <span><?= time_ago($incident['opened_at']) ?></span>
                </li>
                <?php if ($incident['resolved_at']): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Résolu</span>
                    <span class="text-success"><?= time_ago($incident['resolved_at']) ?></span>
                </li>
                <?php endif; ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Technicien</span>
                    <span class="fw-semibold"><?= e($incident['assigned_to_name']) ?></span>
                </li>
            </ul>
        </div>

        <!-- Danger zone -->
        <?php if (in_array($_SESSION['user']['role_name'] ?? '', ['super_admin', 'admin'])): ?>
        <div class="card border-danger">
            <div class="card-header bg-danger bg-opacity-10 border-danger">
                <h6 class="card-title mb-0 fw-semibold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Zone dangereuse
                </h6>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="<?= url('/incidents/' . $incident['id'] . '/delete') ?>"
                      onsubmit="return confirm('Supprimer définitivement cet incident ?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger w-100">
                        <i class="bi bi-trash me-1"></i>Supprimer l'incident
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function updateStatus(newStatus) {
    const labels = {
        incoming: 'En cours', waiting: 'En attente',
        resolved: 'Résolu',   closed: 'Fermé',
    };

    if (!confirm(`Passer l'incident au statut "${labels[newStatus]}" ?`)) return;

    fetch('<?= url('/incidents/' . $incident['id'] . '/status') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrfToken)
            + '&status=' + encodeURIComponent(newStatus),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Erreur lors de la mise à jour.');
        }
    })
    .catch(() => alert('Erreur réseau.'));
}
</script>