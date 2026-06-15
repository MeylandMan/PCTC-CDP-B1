<!-- Onglets statut -->
<ul class="nav nav-tabs mb-3">
    <?php
    $total = array_sum($countStatus);
    $tabs  = [
        ''         => ['Tous',         $total,                   'secondary'],
        'open'     => ['Ouverts',      $countStatus['open'],     'danger'],
        'incoming' => ['En cours',     $countStatus['incoming'], 'warning'],
        'waiting'  => ['En attente',   $countStatus['waiting'],  'info'],
        'resolved' => ['Résolus',      $countStatus['resolved'], 'success'],
        'closed'   => ['Fermés',       $countStatus['closed'],   'secondary'],
    ];
    foreach ($tabs as $val => [$label, $count, $color]):
        $active = $filters['status'] === $val ? 'active' : '';
        $href   = url('/incidents?status=' . $val);
    ?>
    <li class="nav-item">
        <a class="nav-link <?= $active ?>" href="<?= $href ?>">
            <?= $label ?>
            <?php if ($count > 0): ?>
            <span class="badge bg-<?= $color ?> ms-1"><?= $count ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/incidents') ?>" class="row g-2 align-items-end">
            <input type="hidden" name="status" value="<?= e($filters['status']) ?>">

            <div class="col-12 col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Titre, appareil…"
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>

            <div class="col-6 col-sm-3">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">Toutes priorités</option>
                    <?php foreach ($priorities as $p): ?>
                    <option value="<?= e($p) ?>" <?= $filters['priority'] === $p ? 'selected' : '' ?>>
                        <?= ucfirst(e($p)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-sm-5 d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/incidents') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
                <?php if (!$this->hasRole('technicien')): ?>
                <a href="<?= url('/incidents?mine=1') ?>"
                   class="btn btn-outline-info btn-sm ms-auto">
                    <i class="bi bi-person me-1"></i>Mes incidents
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-ticket-detailed me-2 text-info"></i>
            Incidents
            <span class="badge bg-secondary ms-1"><?= $paginator['total'] ?></span>
        </h6>
        <a href="<?= url('/incidents/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Créer
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Priorité</th>
                        <th>Titre</th>
                        <th>Appareil</th>
                        <th>Assigné à</th>
                        <th>Statut</th>
                        <th>Ouvert le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($paginator['data'])): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucun incident trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginator['data'] as $inc): ?>
                    <tr>
                        <td class="text-muted small"><?= $inc['id'] ?></td>
                        <td><?= incident_priority_badge($inc['priority']) ?></td>
                        <td>
                            <a href="<?= url('/incidents/' . $inc['id']) ?>"
                               class="fw-semibold text-decoration-none small">
                                <?= e(truncate($inc['title'], 50)) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= url('/devices/' . $inc['device_id']) ?>"
                               class="small text-decoration-none text-muted">
                                <i class="bi bi-hdd-network me-1"></i>
                                <?= e($inc['device_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= e($inc['assigned_to_name']) ?></td>
                        <td><?= incident_status_badge($inc['status']) ?></td>
                        <td class="small text-muted"><?= time_ago($inc['opened_at']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/incidents/' . $inc['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/incidents/' . $inc['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
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
        <?= pagination($paginator, url('/incidents?status=' . $filters['status'])) ?>
    </div>
    <?php endif; ?>
</div>