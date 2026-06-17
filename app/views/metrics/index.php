<?php

function metricCellClass(?float $value, float $warnAt, float $criticalAt): string
{
    if ($value === null) return 'text-muted';
    if ($value >= $criticalAt) return 'text-danger fw-bold';
    if ($value >= $warnAt)     return 'text-warning fw-semibold';
    return '';
}
?>

<!-- Moyennes globales -->
<div class="row g-3 mb-3">

    <div class="col-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary rounded"><i class="bi bi-cpu"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">CPU moyen (parc)</span>
                <span class="info-box-number"><?= number_format($averages['avg_cpu'], 1) ?> %</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-warning rounded"><i class="bi bi-memory"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">RAM moyenne (parc)</span>
                <span class="info-box-number"><?= number_format($averages['avg_ram'], 1) ?> %</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger rounded"><i class="bi bi-hdd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Disque moyen (parc)</span>
                <span class="info-box-number"><?= number_format($averages['avg_disk'], 1) ?> %</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-info rounded"><i class="bi bi-thermometer-half"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Température moyenne</span>
                <span class="info-box-number"><?= number_format($averages['avg_temp'], 1) ?> °C</span>
            </div>
        </div>
    </div>

</div>

<!-- Top consommateurs -->
<div class="row g-3 mb-3">

    <?php
    $tops = [
        ['title' => 'Top CPU',    'icon' => 'bi-cpu',    'color' => 'primary', 'data' => $topCpu,  'unit' => '%'],
        ['title' => 'Top RAM',    'icon' => 'bi-memory', 'color' => 'warning', 'data' => $topRam,  'unit' => '%'],
        ['title' => 'Top Disque', 'icon' => 'bi-hdd',    'color' => 'danger',  'data' => $topDisk, 'unit' => '%'],
    ];
    ?>
    <?php foreach ($tops as $top): ?>
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi <?= $top['icon'] ?> me-2 text-<?= $top['color'] ?>"></i>
                    <?= $top['title'] ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($top['data'])): ?>
                <p class="text-muted small text-center py-3 mb-0">Aucune donnée.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top['data'] as $i => $item): ?>
                    <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border" style="width:24px;">
                                <?= $i + 1 ?>
                            </span>
                            <a href="<?= url('/devices/' . $item['id']) ?>"
                               class="small text-decoration-none fw-semibold">
                                <?= e($item['name']) ?>
                            </a>
                        </div>
                        <span class="fw-bold text-<?= $top['color'] ?> small">
                            <?= number_format((float)$item['value'], 1) ?><?= $top['unit'] ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/metrics') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom, hostname…"
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>>
                        <?= ucfirst(e($t)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/metrics') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau détaillé par appareil -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-graph-up me-2 text-primary"></i>
            Métriques par appareil
            <span class="badge bg-secondary ms-1"><?= count($rows) ?></span>
        </h6>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Appareil</th>
                        <th>Statut</th>
                        <th>CPU</th>
                        <th>RAM</th>
                        <th>Disque</th>
                        <th>Réseau ↓/↑</th>
                        <th>Temp.</th>
                        <th>Dernière collecte</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucune métrique disponible.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= url('/devices/' . $r['id']) ?>"
                               class="fw-semibold text-decoration-none small">
                                <?= e($r['name']) ?>
                            </a>
                            <div class="text-muted" style="font-size:.72rem;"><?= e($r['hostname']) ?></div>
                        </td>
                        <td><?= device_status_badge($r['status']) ?></td>
                        <td class="<?= metricCellClass($r['cpu_usage'] !== null ? (float)$r['cpu_usage'] : null, 70, 90) ?>">
                            <?= $r['cpu_usage'] !== null ? number_format((float)$r['cpu_usage'], 1) . '%' : '—' ?>
                        </td>
                        <td class="<?= metricCellClass($r['ram_usage'] !== null ? (float)$r['ram_usage'] : null, 70, 85) ?>">
                            <?= $r['ram_usage'] !== null ? number_format((float)$r['ram_usage'], 1) . '%' : '—' ?>
                        </td>
                        <td class="<?= metricCellClass($r['disk_usage'] !== null ? (float)$r['disk_usage'] : null, 80, 95) ?>">
                            <?= $r['disk_usage'] !== null ? number_format((float)$r['disk_usage'], 1) . '%' : '—' ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($r['network_in'] !== null): ?>
                                ↓<?= number_format((float)$r['network_in'], 0) ?>
                                ↑<?= number_format((float)$r['network_out'], 0) ?> KB/s
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="small">
                            <?= $r['temperature'] !== null ? number_format((float)$r['temperature'], 1) . ' °C' : '—' ?>
                        </td>
                        <td class="small text-muted">
                            <?= $r['collected_at'] ? time_ago($r['collected_at']) : 'Jamais' ?>
                        </td>
                        <td>
                            <a href="<?= url('/metrics/device/' . $r['id']) ?>"
                               class="btn btn-sm btn-outline-primary py-0 px-2" title="Historique détaillé">
                                <i class="bi bi-graph-up"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>