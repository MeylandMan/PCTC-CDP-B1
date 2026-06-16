<!-- ======================================================================
     LIGNE 1 — KPIs appareils (small-box)
====================================================================== -->
<div class="row g-3 mb-3">

    <!-- Appareils en ligne -->
    <div class="col-6 col-xl-3">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3 id="kpi-online"><?= $devices['online'] ?></h3>
                <p>Appareils en ligne</p>
            </div>
            <div class="small-box-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <a href="<?= url('/devices?status=online') ?>" class="small-box-footer">
                Voir <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Appareils hors ligne -->
    <div class="col-6 col-xl-3">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3 id="kpi-offline"><?= $devices['offline'] ?></h3>
                <p>Hors ligne</p>
            </div>
            <div class="small-box-icon">
                <i class="bi bi-x-circle"></i>
            </div>
            <a href="<?= url('/devices?status=offline') ?>" class="small-box-footer">
                Voir <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Alertes critiques ouvertes -->
    <div class="col-6 col-xl-3">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3 id="kpi-alerts"><?= $alerts['critique'] + $alerts['urgent'] ?></h3>
                <p>Alertes critiques</p>
            </div>
            <div class="small-box-icon">
                <i class="bi bi-bell"></i>
            </div>
            <a href="<?= url('/alerts?level=critique') ?>" class="small-box-footer">
                Voir <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Incidents actifs -->
    <div class="col-6 col-xl-3">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3 id="kpi-incidents"><?= $incidentCount ?></h3>
                <p>Incidents actifs</p>
            </div>
            <div class="small-box-icon">
                <i class="bi bi-ticket-detailed"></i>
            </div>
            <a href="<?= url('/incidents') ?>" class="small-box-footer">
                Voir <i class="bi bi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

</div>
<!-- /.row KPIs -->


<!-- ======================================================================
     LIGNE 2 — Jauges métriques moyennes (info-box) + disponibilité
====================================================================== -->
<div class="row g-3 mb-3">

    <!-- CPU moyen -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary rounded">
                <i class="bi bi-cpu"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">CPU moyen</span>
                <span class="info-box-number" id="avg-cpu">
                    <?= number_format($metrics['avg_cpu'], 1) ?> %
                </span>
                <div class="progress">
                    <div class="progress-bar bg-primary" id="bar-cpu"
                         style="width: <?= min($metrics['avg_cpu'], 100) ?>%"></div>
                </div>
                <span class="progress-description">
                    <?= $metrics['avg_cpu'] > 85 ? '⚠ Charge élevée' : 'Normal' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- RAM moyenne -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-warning rounded">
                <i class="bi bi-memory"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">RAM moyenne</span>
                <span class="info-box-number" id="avg-ram">
                    <?= number_format($metrics['avg_ram'], 1) ?> %
                </span>
                <div class="progress">
                    <div class="progress-bar bg-warning" id="bar-ram"
                         style="width: <?= min($metrics['avg_ram'], 100) ?>%"></div>
                </div>
                <span class="progress-description">
                    <?= $metrics['avg_ram'] > 85 ? '⚠ Charge élevée' : 'Normal' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Disque moyen -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger rounded">
                <i class="bi bi-hdd"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Disque moyen</span>
                <span class="info-box-number" id="avg-disk">
                    <?= number_format($metrics['avg_disk'], 1) ?> %
                </span>
                <div class="progress">
                    <div class="progress-bar bg-danger" id="bar-disk"
                         style="width: <?= min($metrics['avg_disk'], 100) ?>%"></div>
                </div>
                <span class="progress-description">
                    <?= $metrics['avg_disk'] > 90 ? '⚠ Espace critique' : 'Normal' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Disponibilité globale -->
    <div class="col-12 col-sm-6 col-xl-3">
        <?php
        $uptime = $devices['total'] > 0
            ? round(($devices['online'] / $devices['total']) * 100, 1)
            : 0;
        $uptimeClass = $uptime >= 95 ? 'success' : ($uptime >= 80 ? 'warning' : 'danger');
        ?>
        <div class="info-box">
            <span class="info-box-icon text-bg-<?= $uptimeClass ?> rounded">
                <i class="bi bi-graph-up-arrow"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Disponibilité</span>
                <span class="info-box-number" id="kpi-uptime"><?= $uptime ?> %</span>
                <div class="progress">
                    <div class="progress-bar bg-<?= $uptimeClass ?>"
                         style="width: <?= $uptime ?>%"></div>
                </div>
                <span class="progress-description">
                    <?= $devices['online'] ?>/<?= $devices['total'] ?> appareils
                </span>
            </div>
        </div>
    </div>

</div>
<!-- /.row métriques -->


<!-- ======================================================================
     LIGNE 3 — Graphiques : alertes 7j (bar) + répartition types (donut)
====================================================================== -->
<div class="row g-3 mb-3">

    <!-- Bar chart : alertes par jour -->
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                    Alertes des 7 derniers jours
                </h6>
                <span class="badge bg-secondary">7 jours</span>
            </div>
            <div class="card-body">
                <canvas id="chartAlertsPerDay" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Donut : répartition par type -->
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-pie-chart me-2 text-primary"></i>
                    Appareils par type
                </h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartDeviceTypes" height="220"></canvas>
            </div>
        </div>
    </div>

</div>
<!-- /.row graphiques -->


<!-- ======================================================================
     LIGNE 4 — Tableaux : alertes récentes + incidents actifs
====================================================================== -->
<div class="row g-3 mb-3">

    <!-- Alertes récentes -->
    <div class="col-12 col-xl-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-bell me-2 text-warning"></i>
                    Alertes ouvertes
                    <?php if ($alerts['total'] > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $alerts['total'] ?></span>
                    <?php endif; ?>
                </h6>
                <a href="<?= url('/alerts') ?>" class="btn btn-sm btn-outline-secondary">
                    Tout voir
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Niveau</th>
                                <th>Appareil</th>
                                <th>Type</th>
                                <th>Valeur</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentAlerts)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    Aucune alerte ouverte
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAlerts as $alert): ?>
                            <tr>
                                <td><?= alert_level_badge($alert['alert_level']) ?></td>
                                <td class="fw-semibold small"><?= e($alert['device_name']) ?></td>
                                <td class="small text-muted"><?= e($alert['alert_type']) ?></td>
                                <td class="small">
                                    <?php if ($alert['current_value'] !== null): ?>
                                        <span class="text-danger fw-semibold">
                                            <?= number_format((float)$alert['current_value'], 1) ?>%
                                        </span>
                                        <span class="text-muted">/ <?= number_format((float)$alert['threshold_value'], 0) ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= time_ago($alert['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Incidents actifs -->
    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-ticket-detailed me-2 text-info"></i>
                    Incidents actifs
                </h6>
                <a href="<?= url('/incidents') ?>" class="btn btn-sm btn-outline-secondary">
                    Tout voir
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Priorité</th>
                                <th>Titre</th>
                                <th>Assigné à</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentIncidents)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    Aucun incident actif
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentIncidents as $inc): ?>
                            <tr>
                                <td><?= incident_priority_badge($inc['priority']) ?></td>
                                <td class="small fw-semibold">
                                    <a href="<?= url('/incidents/' . $inc['id']) ?>"
                                       class="text-decoration-none">
                                        <?= e(truncate($inc['title'], 35)) ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?= e($inc['assigned_to']) ?></td>
                                <td><?= incident_status_badge($inc['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.row tableaux -->


<!-- ======================================================================
     LIGNE 5 — Appareils critiques (RAM/Disque/CPU élevés)
====================================================================== -->
<?php if (!empty($criticalDevices)): ?>
<div class="row g-3">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger bg-opacity-10 border-danger">
                <h6 class="card-title mb-0 fw-semibold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Appareils nécessitant une attention immédiate
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Appareil</th>
                                <th>Statut</th>
                                <th>CPU</th>
                                <th>RAM</th>
                                <th>Disque</th>
                                <th>Temp.</th>
                                <th>Dernière collecte</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($criticalDevices as $dev): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold small"><?= e($dev['name']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;"><?= e($dev['hostname']) ?></div>
                            </td>
                            <td><?= device_status_badge($dev['status']) ?></td>
                            <td>
                                <?php $cpu = (float)$dev['cpu_usage']; ?>
                                <span class="<?= $cpu > 85 ? 'text-danger fw-bold' : '' ?>">
                                    <?= number_format($cpu, 1) ?>%
                                </span>
                            </td>
                            <td>
                                <?php $ram = (float)$dev['ram_usage']; ?>
                                <span class="<?= $ram > 85 ? 'text-danger fw-bold' : '' ?>">
                                    <?= number_format($ram, 1) ?>%
                                </span>
                            </td>
                            <td>
                                <?php $disk = (float)$dev['disk_usage']; ?>
                                <span class="<?= $disk > 90 ? 'text-danger fw-bold' : '' ?>">
                                    <?= number_format($disk, 1) ?>%
                                </span>
                            </td>
                            <td>
                                <?php $temp = $dev['temperature']; ?>
                                <?= $temp ? number_format((float)$temp, 1) . ' °C' : '—' ?>
                            </td>
                            <td class="small text-muted"><?= time_ago($dev['collected_at']) ?></td>
                            <td>
                                <a href="<?= url('/devices/' . $dev['id']) ?>"
                                   class="btn btn-xs btn-outline-primary btn-sm py-0 px-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- /.row criticalDevices -->


<!-- ======================================================================
     SCRIPTS CHART.JS + RAFRAÎCHISSEMENT AJAX
====================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ------------------------------------------------------------------
    // Données PHP → JS
    // ------------------------------------------------------------------
    const dataDeviceTypes  = <?= $chartDeviceTypes ?>;
    const dataAlertsPerDay = <?= $chartAlertsPerDay ?>;

    // ------------------------------------------------------------------
    // Couleurs cohérentes avec le thème
    // ------------------------------------------------------------------
    const COLORS = {
        success : '#198754',
        danger  : '#dc3545',
        warning : '#ffc107',
        info    : '#0dcaf0',
        primary : '#4f46e5',
        secondary:'#6c757d',
        purple  : '#7c3aed',
    };

    const TYPE_COLORS = [
        COLORS.primary, COLORS.success, COLORS.warning,
        COLORS.danger,  COLORS.info,    COLORS.purple,
        COLORS.secondary,
    ];

    // ------------------------------------------------------------------
    // Bar chart : alertes par jour
    // ------------------------------------------------------------------
    new Chart(document.getElementById('chartAlertsPerDay'), {
        type: 'bar',
        data: {
            labels: dataAlertsPerDay.labels,
            datasets: [
                {
                    label: 'Info',
                    data: dataAlertsPerDay.info,
                    backgroundColor: COLORS.info,
                    borderRadius: 4,
                },
                {
                    label: 'Alerte',
                    data: dataAlertsPerDay.warning,
                    backgroundColor: COLORS.warning,
                    borderRadius: 4,
                },
                {
                    label: 'Critique',
                    data: dataAlertsPerDay.critique,
                    backgroundColor: COLORS.danger,
                    borderRadius: 4,
                },
                {
                    label: 'Urgent',
                    data: dataAlertsPerDay.urgent,
                    backgroundColor: '#6f0000',
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });

    // ------------------------------------------------------------------
    // Donut : types d'appareils
    // ------------------------------------------------------------------
    new Chart(document.getElementById('chartDeviceTypes'), {
        type: 'doughnut',
        data: {
            labels: dataDeviceTypes.labels,
            datasets: [{
                data: dataDeviceTypes.data,
                backgroundColor: TYPE_COLORS,
                borderWidth: 2,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12 } },
            },
        },
    });

    // ------------------------------------------------------------------
    // Rafraîchissement AJAX toutes les 30 secondes
    // ------------------------------------------------------------------
    function refreshStats() {
        fetch('<?= url('/dashboard/stats') ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            // KPIs appareils
            setText('kpi-online',    data.devices?.online    ?? '—');
            setText('kpi-offline',   data.devices?.offline   ?? '—');
            setText('kpi-alerts',    (data.alerts?.critique ?? 0) + (data.alerts?.urgent ?? 0));
            setText('kpi-incidents', data.incidents ?? '—');

            // Disponibilité
            const total  = data.devices?.total  ?? 0;
            const online = data.devices?.online ?? 0;
            const uptime = total > 0 ? ((online / total) * 100).toFixed(1) : '0.0';
            setText('kpi-uptime', uptime + ' %');

            // Métriques moyennes
            if (data.metrics) {
                setMetric('avg-cpu',  'bar-cpu',  data.metrics.avg_cpu);
                setMetric('avg-ram',  'bar-ram',  data.metrics.avg_ram);
                setMetric('avg-disk', 'bar-disk', data.metrics.avg_disk);
            }
        })
        .catch(() => {}); // silencieux si hors ligne
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function setMetric(textId, barId, value) {
        const v = parseFloat(value) || 0;
        setText(textId, v.toFixed(1) + ' %');
        const bar = document.getElementById(barId);
        if (bar) bar.style.width = Math.min(v, 100) + '%';
    }

    setInterval(refreshStats, 30_000);

});
</script>