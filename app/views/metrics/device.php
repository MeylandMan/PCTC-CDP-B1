<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><?= e($device['name']) ?></h5>
        <span class="text-muted small font-monospace"><?= e($device['hostname']) ?> · <?= e($device['ip_address']) ?></span>
    </div>
    <a href="<?= url('/metrics') ?>" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Retour aux métriques
    </a>
</div>

<!-- Graphique brut (30 dernières collectes, ~toutes les 5 min) -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-graph-up me-2 text-primary"></i>
            Collectes récentes (30 dernières mesures)
        </h6>
    </div>
    <div class="card-body">
        <canvas id="chartRaw" height="90"></canvas>
    </div>
</div>

<!-- Graphique moyennes horaires (24h) -->
<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-bar-chart me-2 text-primary"></i>
            Moyennes horaires (24 dernières heures)
        </h6>
    </div>
    <div class="card-body">
        <canvas id="chartHourly" height="90"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawData    = <?= $chartRaw ?>;
    const hourlyData = <?= $chartHourly ?>;

    const commonOptions = {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
            x: { grid: { display: false } },
        },
    };

    new Chart(document.getElementById('chartRaw'), {
        type: 'line',
        data: {
            labels: rawData.labels,
            datasets: [
                { label: 'CPU (%)',    data: rawData.cpu,  borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.08)', tension: .3, fill: true, pointRadius: 2 },
                { label: 'RAM (%)',    data: rawData.ram,  borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.08)', tension: .3, fill: true, pointRadius: 2 },
                { label: 'Disque (%)', data: rawData.disk, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.08)', tension: .3, fill: true, pointRadius: 2 },
            ],
        },
        options: commonOptions,
    });

    new Chart(document.getElementById('chartHourly'), {
        type: 'bar',
        data: {
            labels: hourlyData.labels,
            datasets: [
                { label: 'CPU moy. (%)',    data: hourlyData.cpu,  backgroundColor: '#4f46e5' },
                { label: 'RAM moy. (%)',    data: hourlyData.ram,  backgroundColor: '#ffc107' },
                { label: 'Disque moy. (%)', data: hourlyData.disk, backgroundColor: '#dc3545' },
            ],
        },
        options: commonOptions,
    });
});
</script>