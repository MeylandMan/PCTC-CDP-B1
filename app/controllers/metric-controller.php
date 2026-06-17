<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/metric-model.php';
require_once ROOT_PATH . '/app/models/device-model.php';

class MetricController extends Controller
{
    private MetricModel $metrics;
    private DeviceModel $devices;

    public function __construct()
    {
        $this->metrics = new MetricModel();
        $this->devices = new DeviceModel();
    }

    // -----------------------------------------------------------------------
    // GET /metrics
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requireAuth();

        $search = (string) $this->query('search', '');
        $type   = (string) $this->query('type', '');

        $rows      = $this->metrics->latestPerDevice($search, $type);
        $averages  = $this->metrics->globalAverages();
        $topCpu    = $this->metrics->topConsumers('cpu_usage', 5);
        $topRam    = $this->metrics->topConsumers('ram_usage', 5);
        $topDisk   = $this->metrics->topConsumers('disk_usage', 5);

        $this->view('metrics/index', [
            'title'       => 'Métriques',
            'breadcrumbs' => ['Métriques' => null],
            'rows'        => $rows,
            'averages'    => $averages,
            'topCpu'      => $topCpu,
            'topRam'      => $topRam,
            'topDisk'     => $topDisk,
            'types'       => $this->devices->types(),
            'filters'     => compact('search', 'type'),
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /metrics/device/:id
    // -----------------------------------------------------------------------
    public function byDevice(string $id): void
    {
        $this->requireAuth();

        $device = $this->devices->findWithLocation((int) $id);
        if (!$device) {
            $this->abort(404, 'Appareil introuvable.');
        }

        $history = $this->metrics->historyForDevice((int) $id, 30);
        $hourly  = $this->metrics->hourlyAverages((int) $id, 24);

        $chartRaw = json_encode([
            'labels' => array_column($history, 'label'),
            'cpu'    => array_map('floatval', array_column($history, 'cpu_usage')),
            'ram'    => array_map('floatval', array_column($history, 'ram_usage')),
            'disk'   => array_map('floatval', array_column($history, 'disk_usage')),
        ]);

        $chartHourly = json_encode([
            'labels' => array_column($hourly, 'hour_label'),
            'cpu'    => array_map('floatval', array_column($hourly, 'avg_cpu')),
            'ram'    => array_map('floatval', array_column($hourly, 'avg_ram')),
            'disk'   => array_map('floatval', array_column($hourly, 'avg_disk')),
        ]);

        $this->view('metrics/device', [
            'title'       => 'Métriques — ' . e($device['name']),
            'breadcrumbs' => ['Métriques' => '/metrics', $device['name'] => null],
            'device'      => $device,
            'chartRaw'    => $chartRaw,
            'chartHourly' => $chartHourly,
        ]);
    }
}