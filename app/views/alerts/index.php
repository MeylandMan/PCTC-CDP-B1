<!-- Onglets statut -->
<ul class="nav nav-tabs mb-3">
    <?php
    $tabs = [
        ''             => ['Toutes',       $countStatus['open'] + $countStatus['acknowledged'] + $countStatus['resolved'], 'secondary'],
        'open'         => ['Ouvertes',     $countStatus['open'],         'danger'],
        'acknowledged' => ['Reconnues',    $countStatus['acknowledged'], 'warning'],
        'resolved'     => ['Résolues',     $countStatus['resolved'],     'success'],
    ];
    foreach ($tabs as $val => [$label, $count, $color]):
        $active = $filters['status'] === $val ? 'active' : '';
        $url    = url('/alerts?status=' . $val . ($filters['level'] ? '&level=' . $filters['level'] : ''));
    ?>
    <li class="nav-item">
        <a class="nav-link <?= $active ?>" href="<?= $url ?>">
            <?= $label ?>
            <span class="badge bg-<?= $color ?> ms-1"><?= $count ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Barre de filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/alerts') ?>" class="row g-2 align-items-end">
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">

            <div class="col-12 col-sm-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Appareil, type, message…"
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>

            <div class="col-6 col-sm-3">
                <select name="level" class="form-select form-select-sm">
                    <option value="">Tous les niveaux</option>
                    <?php foreach ($levels as $l): ?>
                    <option value="<?= e($l) ?>" <?= $filters['level'] === $l ? 'selected' : '' ?>>
                        <?= ucfirst(e($l)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/alerts') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Badges niveaux -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <?php foreach (['urgent' => 'dark', 'critique' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $lvl => $cls): ?>
    <a href="<?= url('/alerts?status=' . $filters['status'] . '&level=' . $lvl) ?>"
       class="badge bg-<?= $cls ?> text-decoration-none fs-6 px-3 py-2
              <?= $filters['level'] === $lvl ? 'opacity-100' : 'opacity-75' ?>">
        <?= ucfirst($lvl) ?> <span class="ms-1">(<?= $countLevel[$lvl] ?? 0 ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tableau -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-bell me-2 text-warning"></i>
            Alertes
            <span class="badge bg-secondary ms-1"><?= $paginator['total'] ?></span>
        </h6>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Niveau</th>
                        <th>Appareil</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Valeur</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($paginator['data'])): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                            Aucune alerte trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginator['data'] as $a): ?>
                    <tr id="alert-row-<?= $a['id'] ?>">
                        <td class="text-muted small"><?= $a['id'] ?></td>
                        <td><?= alert_level_badge($a['alert_level']) ?></td>
                        <td>
                            <a href="<?= url('/devices/' . $a['device_id']) ?>"
                               class="text-decoration-none fw-semibold small">
                                <?= e($a['device_name']) ?>
                            </a>
                            <div class="text-muted font-monospace" style="font-size:.7rem;">
                                <?= e($a['ip_address']) ?>
                            </div>
                        </td>
                        <td class="small"><?= e($a['alert_type']) ?></td>
                        <td class="small"><?= e(truncate($a['message'], 60)) ?></td>
                        <td class="small">
                            <?php if ($a['current_value'] !== null): ?>
                                <span class="fw-semibold text-danger">
                                    <?= number_format((float)$a['current_value'], 1) ?>%
                                </span>
                                <span class="text-muted">
                                    / <?= number_format((float)$a['threshold_value'], 0) ?>%
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="alert-status-badge-<?= $a['id'] ?>">
                                <?php
                                $sBadge = match($a['status']) {
                                    'open'         => '<span class="badge bg-danger">Ouverte</span>',
                                    'acknowledged' => '<span class="badge bg-warning text-dark">Reconnue</span>',
                                    'resolved'     => '<span class="badge bg-success">Résolue</span>',
                                    default        => '<span class="badge bg-secondary">' . e($a['status']) . '</span>',
                                };
                                echo $sBadge;
                                ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= time_ago($a['created_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <!-- Voir -->
                                <a href="<?= url('/alerts/' . $a['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="Détail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Reconnaître -->
                                <?php if ($a['status'] === 'open'): ?>
                                <button class="btn btn-sm btn-outline-warning py-0 px-2"
                                        title="Reconnaître"
                                        onclick="changeStatus(<?= $a['id'] ?>, 'acknowledge')">
                                    <i class="bi bi-check"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Résoudre -->
                                <?php if (in_array($a['status'], ['open', 'acknowledged'])): ?>
                                <button class="btn btn-sm btn-outline-success py-0 px-2"
                                        title="Résoudre"
                                        onclick="changeStatus(<?= $a['id'] ?>, 'resolve')">
                                    <i class="bi bi-check-all"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($paginator['lastPage'] > 1): ?>
    <div class="card-footer">
        <?= pagination($paginator, url('/alerts?status=' . $filters['status'])) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Token CSRF pour AJAX -->
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function changeStatus(id, action) {
    const labels = { acknowledge: 'Reconnaître', resolve: 'Résoudre' };
    if (!confirm(`${labels[action]} l'alerte #${id} ?`)) return;

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
        if (!data.success) { alert(data.message); return; }

        // Met à jour le badge statut dans la ligne
        const badgeEl = document.querySelector(`.alert-status-badge-${id}`);
        if (badgeEl) {
            badgeEl.innerHTML = action === 'acknowledge'
                ? '<span class="badge bg-warning text-dark">Reconnue</span>'
                : '<span class="badge bg-success">Résolue</span>';
        }

        // Masque les boutons d'action devenus inutiles
        const row = document.getElementById(`alert-row-${id}`);
        if (row && action === 'resolve') {
            row.querySelectorAll('button').forEach(b => b.remove());
        }
    })
    .catch(() => alert('Erreur lors de la mise à jour.'));
}
</script>