<!-- En-tête : infos rapides + boutons -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">

                    <!-- Identité -->
                    <div class="d-flex align-items-center gap-3">
                        <div style="
                            width:52px; height:52px; border-radius:12px;
                            background: linear-gradient(135deg,#4f46e5,#7c3aed);
                            display:flex; align-items:center; justify-content:center;
                        ">
                            <i class="bi bi-hdd-network text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?= e($device['name']) ?></h5>
                            <div class="text-muted small font-monospace"><?= e($device['hostname']) ?></div>
                            <div class="mt-1 d-flex gap-2 flex-wrap">
                                <?= device_status_badge($device['status']) ?>
                                <span class="badge bg-light text-dark border">
                                    <?= ucfirst(e($device['type'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 flex-wrap">
                        <!-- Ping -->
                        <button type="button" class="btn btn-sm btn-outline-info"
                                id="pingBtn" onclick="pingDevice(<?= $device['id'] ?>)">
                            <i class="bi bi-wifi me-1"></i>Ping
                        </button>
                        <!-- Modifier -->
                        <a href="<?= url('/devices/' . $device['id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>Modifier
                        </a>
                        <!-- Retour -->
                        <a href="<?= url('/devices') ?>" class="btn btn-sm btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Retour
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ligne : infos réseau + métriques instantanées -->
<div class="row g-3 mb-3">

    <!-- Infos réseau -->
    <div class="col-12 col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Informations réseau
                </h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Adresse IP</dt>
                    <dd class="col-7 font-monospace"><?= e($device['ip_address']) ?></dd>

                    <dt class="col-5 text-muted">Adresse MAC</dt>
                    <dd class="col-7 font-monospace"><?= e($device['mac_address']) ?></dd>

                    <dt class="col-5 text-muted">OS</dt>
                    <dd class="col-7"><?= e($device['os_version']) ?></dd>

                    <dt class="col-5 text-muted">Localisation</dt>
                    <dd class="col-7">
                        <?= e($device['site_name']) ?>
                        <span class="text-muted">(<?= e($device['city']) ?>, <?= e($device['country']) ?>)</span>
                    </dd>

                    <dt class="col-5 text-muted">Ajouté le</dt>
                    <dd class="col-7"><?= format_date($device['created_at']) ?></dd>

                    <dt class="col-5 text-muted">Dernière vérif.</dt>
                    <dd class="col-7"><?= format_date($device['last_check']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Métriques instantanées -->
    <div class="col-12 col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-activity me-2 text-success"></i>Métriques actuelles
                </h6>
                <?php if ($lastMetric): ?>
                <span class="text-muted small">
                    Collecté <?= time_ago($lastMetric['collected_at']) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$lastMetric): ?>
                    <p class="text-muted small mb-0">Aucune métrique disponible.</p>
                <?php else: ?>
                <div class="row g-3">

                    <?php
                    $metrics_display = [
                        ['label' => 'CPU',    'key' => 'cpu_usage',  'icon' => 'bi-cpu',    'color' => 'primary', 'threshold' => 85],
                        ['label' => 'RAM',    'key' => 'ram_usage',  'icon' => 'bi-memory', 'color' => 'warning', 'threshold' => 85],
                        ['label' => 'Disque', 'key' => 'disk_usage', 'icon' => 'bi-hdd',    'color' => 'danger',  'threshold' => 90],
                    ];
                    foreach ($metrics_display as $m):
                        $val   = (float)($lastMetric[$m['key']] ?? 0);
                        $over  = $val > $m['threshold'];
                        $color = $over ? 'danger' : $m['color'];
                    ?>
                    <div class="col-4">
                        <div class="info-box mb-0">
                            <span class="info-box-icon text-bg-<?= $color ?> rounded">
                                <i class="bi <?= $m['icon'] ?>"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= $m['label'] ?></span>
                                <span class="info-box-number fw-bold <?= $over ? 'text-danger' : '' ?>">
                                    <?= number_format($val, 1) ?> %
                                </span>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $color ?>"
                                         style="width:<?= min($val,100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <!-- Réseau + Température -->
                <div class="row g-2 mt-1">
                    <div class="col-4 text-center">
                        <div class="text-muted small">↓ Réseau</div>
                        <div class="fw-semibold small">
                            <?= number_format((float)($lastMetric['network_in'] ?? 0), 1) ?> KB/s
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">↑ Réseau</div>
                        <div class="fw-semibold small">
                            <?= number_format((float)($lastMetric['network_out'] ?? 0), 1) ?> KB/s
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Température</div>
                        <div class="fw-semibold small">
                            <?php $temp = $lastMetric['temperature']; ?>
                            <?= $temp ? number_format((float)$temp, 1) . ' °C' : '—' ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Graphique historique métriques -->
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-graph-up me-2 text-primary"></i>Historique des métriques (30 dernières collectes)
        </h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="refreshMetrics">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="chartMetrics" height="90"></canvas>
    </div>
</div>

<!-- Alertes ouvertes + Incidents actifs -->
<div class="row g-3">

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-bell me-2 text-warning"></i>
                    Alertes ouvertes
                    <span class="badge bg-danger ms-1"><?= count($openAlerts) ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($openAlerts)): ?>
                <p class="text-muted small text-center py-3 mb-0">
                    <i class="bi bi-check-circle text-success me-1"></i>Aucune alerte ouverte
                </p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($openAlerts as $a): ?>
                    <li class="list-group-item py-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <?= alert_level_badge($a['alert_level']) ?>
                                <span class="small ms-1 fw-semibold"><?= e($a['alert_type']) ?></span>
                                <div class="text-muted small"><?= e(truncate($a['message'], 80)) ?></div>
                            </div>
                            <span class="text-muted small text-nowrap">
                                <?= time_ago($a['created_at']) ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-ticket-detailed me-2 text-info"></i>
                    Incidents actifs
                    <span class="badge bg-warning text-dark ms-1"><?= count($activeIncidents) ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeIncidents)): ?>
                <p class="text-muted small text-center py-3 mb-0">
                    <i class="bi bi-check-circle text-success me-1"></i>Aucun incident actif
                </p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($activeIncidents as $inc): ?>
                    <li class="list-group-item py-2">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <?= incident_priority_badge($inc['priority']) ?>
                                <?= incident_status_badge($inc['status']) ?>
                                <a href="<?= url('/incidents/' . $inc['id']) ?>"
                                   class="small fw-semibold ms-1 text-decoration-none">
                                    <?= e(truncate($inc['title'], 50)) ?>
                                </a>
                                <div class="text-muted small">
                                    Assigné à <?= e($inc['assigned_to']) ?>
                                </div>
                            </div>
                            <span class="text-muted small text-nowrap">
                                <?= time_ago($inc['opened_at']) ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ======================================================================
     Scripts
====================================================================== -->
<script>
(function () {
    const deviceId   = <?= (int)$device['id'] ?>;
    const initialData = <?= $chartMetrics ?>;

    // ------------------------------------------------------------------
    // Graphique historique
    // ------------------------------------------------------------------
    const ctx = document.getElementById('chartMetrics').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: initialData.labels,
            datasets: [
                {
                    label: 'CPU (%)',
                    data: initialData.cpu,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,0.08)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
                {
                    label: 'RAM (%)',
                    data: initialData.ram,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255,193,7,0.08)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
                {
                    label: 'Disque (%)',
                    data: initialData.disk,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.08)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
                x: { grid: { display: false } },
            },
        },
    });

    // Rafraîchissement du graphique
    function refreshChart() {
        fetch(`<?= url('/devices/') ?>${deviceId}/metrics?limit=30`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => {
            chart.data.labels       = d.labels;
            chart.data.datasets[0].data = d.cpu;
            chart.data.datasets[1].data = d.ram;
            chart.data.datasets[2].data = d.disk;
            chart.update('none');
        })
        .catch(() => {});
    }

    document.getElementById('refreshMetrics')
        .addEventListener('click', refreshChart);

    setInterval(refreshChart, 30_000);

    // ------------------------------------------------------------------
    // Ping
    // ------------------------------------------------------------------
    function pingDevice(id) {
        const btn = document.getElementById('pingBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Ping…';

        fetch(`<?= url('/devices/') ?>${id}/ping`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: '_csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>',
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi me-1"></i>Ping';

            const icon = data.alive ? '✅' : '❌';
            alert(icon + ' ' + data.message);
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi me-1"></i>Ping';
            alert('Erreur lors du ping.');
        });
    }

    window.pingDevice = pingDevice;
})();
</script>