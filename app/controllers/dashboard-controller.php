<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/dashboard-model.php';

class DashboardController extends Controller
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    // -----------------------------------------------------------------------
    // GET /dashboard
    // -----------------------------------------------------------------------

    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $this->currentUser()['id'];

        // --- Données initiales (rendu côté serveur) ---
        $devices       = $this->model->deviceCounts();
        $alerts        = $this->model->alertCounts();
        $metrics       = $this->model->avgMetrics();
        $recentAlerts  = $this->model->recentAlerts(8);
        $recentIncidents = $this->model->recentIncidents(6);
        $criticalDevices = $this->model->criticalDevices();
        $devicesByType = $this->model->devicesByType();
        $alertsPerDay  = $this->model->alertsPerDay(7);

        // Compteurs pour les badges de la sidebar
        $alertCount    = $alerts['total'];
        $incidentCount = $this->model->openIncidentCount();

        // --- Prépare les données JSON pour Chart.js ---
        // Donut : répartition par type d'appareil
        $chartDeviceTypes = json_encode([
            'labels' => array_column($devicesByType, 'type'),
            'data'   => array_map('intval', array_column($devicesByType, 'total')),
        ]);

        // Bar chart : alertes par jour sur 7 jours
        $chartAlertsPerDay = json_encode([
            'labels'   => array_column($alertsPerDay, 'day'),
            'info'     => array_map('intval', array_column($alertsPerDay, 'info')),
            'warning'  => array_map('intval', array_column($alertsPerDay, 'warning')),
            'critique' => array_map('intval', array_column($alertsPerDay, 'critique')),
            'urgent'   => array_map('intval', array_column($alertsPerDay, 'urgent')),
        ]);

        $this->logAction('VIEW_DASHBOARD', 'dashboard');

        $this->view('dashboard/index', compact(
            'devices', 'alerts', 'metrics',
            'recentAlerts', 'recentIncidents', 'criticalDevices',
            'alertCount', 'incidentCount',
            'chartDeviceTypes', 'chartAlertsPerDay'
        ), 'main');
    }

    // -----------------------------------------------------------------------
    // GET /dashboard/stats  (AJAX — toutes les 30 s)
    // -----------------------------------------------------------------------

    public function stats(): void
    {
        $this->requireAuth();

        if (!$this->isAjax()) {
            $this->abort(403);
        }

        $userId = (int) $this->currentUser()['id'];
        $this->json($this->model->allStats($userId));
    }

    // -----------------------------------------------------------------------
    // GET /dashboard/metrics/:id  (AJAX — courbes temps réel)
    // -----------------------------------------------------------------------

    public function deviceMetrics(string $id): void
    {
        $this->requireAuth();

        if (!$this->isAjax()) {
            $this->abort(403);
        }

        $deviceId = (int) $id;
        $limit    = min((int) $this->query('limit', 30), 60);

        $this->json($this->model->metricsHistory($deviceId, $limit));
    }
}