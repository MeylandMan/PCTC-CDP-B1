<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/device-model.php';
require_once ROOT_PATH . '/app/models/location-model.php';

class DeviceController extends Controller
{
    private DeviceModel   $devices;
    private LocationModel $locations;

    public function __construct()
    {
        $this->devices   = new DeviceModel();
        $this->locations = new LocationModel();
    }

    // -----------------------------------------------------------------------
    // GET /devices — liste paginée
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requirePermission('manage_devices');

        $page   = (int)   $this->query('page',   1);
        $status = (string)$this->query('status', '');
        $type   = (string)$this->query('type',   '');
        $search = (string)$this->query('search', '');

        $paginator = $this->devices->listPaginated($page, 15, $status, $type, $search);

        $this->view('devices/index', [
            'title'       => 'Gestion des appareils',
            'breadcrumbs' => ['Appareils' => null],
            'paginator'   => $paginator,
            'types'       => $this->devices->types(),
            'statuses'    => $this->devices->statuses(),
            'filters'     => compact('status', 'type', 'search'),
            'alertCount'  => 0,
            'incidentCount' => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /devices/:id — détail
    // -----------------------------------------------------------------------
    public function show(string $id): void
    {
        $this->requirePermission('manage_devices');

        $device = $this->devices->findWithLocation((int) $id);
        if (!$device) {
            $this->abort(404, 'Appareil introuvable.');
        }

        $lastMetric      = $this->devices->lastMetric((int) $id);
        $openAlerts      = $this->devices->openAlerts((int) $id);
        $activeIncidents = $this->devices->activeIncidents((int) $id);
        $metricsHistory  = $this->devices->latestMetrics((int) $id, 30);

        $chartMetrics = json_encode([
            'labels' => array_column($metricsHistory, 'label'),
            'cpu'    => array_map('floatval', array_column($metricsHistory, 'cpu_usage')),
            'ram'    => array_map('floatval', array_column($metricsHistory, 'ram_usage')),
            'disk'   => array_map('floatval', array_column($metricsHistory, 'disk_usage')),
        ]);

        $this->view('devices/show', [
            'title'           => e($device['name']),
            'breadcrumbs'     => ['Appareils' => '/devices', $device['name'] => null],
            'device'          => $device,
            'lastMetric'      => $lastMetric,
            'openAlerts'      => $openAlerts,
            'activeIncidents' => $activeIncidents,
            'chartMetrics'    => $chartMetrics,
            'alertCount'      => 0,
            'incidentCount'   => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /devices/create — formulaire de création
    // -----------------------------------------------------------------------
    public function create(): void
    {
        $this->requirePermission('manage_devices');

        $this->view('devices/form', [
            'title'       => 'Ajouter un appareil',
            'breadcrumbs' => ['Appareils' => '/devices', 'Ajouter' => null],
            'device'      => null,
            'locations'   => $this->locations->forSelect(),
            'types'       => $this->devices->types(),
            'statuses'    => $this->devices->statuses(),
            'alertCount'  => 0,
            'incidentCount' => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /devices/store — enregistrement
    // -----------------------------------------------------------------------
    public function store(): void
    {
        $this->requirePermission('manage_devices');
        $this->verifyCsrfToken();

        $errors = $this->validateDevice();

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/devices/create');
            return;
        }

        $this->devices->insert($this->deviceData());
        $this->logAction('CREATE_DEVICE', 'devices');
        $this->flash('success', 'Appareil ajouté avec succès.');
        $this->redirect('/devices');
    }

    // -----------------------------------------------------------------------
    // GET /devices/:id/edit — formulaire d'édition
    // -----------------------------------------------------------------------
    public function edit(string $id): void
    {
        $this->requirePermission('manage_devices');

        $device = $this->devices->findById((int) $id);
        if (!$device) {
            $this->abort(404, 'Appareil introuvable.');
        }

        $this->view('devices/form', [
            'title'       => 'Modifier — ' . e($device['name']),
            'breadcrumbs' => ['Appareils' => '/devices', 'Modifier' => null],
            'device'      => $device,
            'locations'   => $this->locations->forSelect(),
            'types'       => $this->devices->types(),
            'statuses'    => $this->devices->statuses(),
            'alertCount'  => 0,
            'incidentCount' => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /devices/:id/update — mise à jour
    // -----------------------------------------------------------------------
    public function update(string $id): void
    {
        $this->requirePermission('manage_devices');
        $this->verifyCsrfToken();

        $device = $this->devices->findById((int) $id);
        if (!$device) {
            $this->abort(404, 'Appareil introuvable.');
        }

        $errors = $this->validateDevice((int) $id);

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/devices/' . $id . '/edit');
            return;
        }

        $this->devices->update((int) $id, $this->deviceData());
        $this->logAction('UPDATE_DEVICE', 'devices');
        $this->flash('success', 'Appareil mis à jour avec succès.');
        $this->redirect('/devices/' . $id);
    }

    // -----------------------------------------------------------------------
    // POST /devices/:id/delete — suppression
    // -----------------------------------------------------------------------
    public function delete(string $id): void
    {
        $this->requirePermission('manage_devices');
        $this->verifyCsrfToken();

        $device = $this->devices->findById((int) $id);
        if (!$device) {
            $this->abort(404, 'Appareil introuvable.');
        }

        $this->devices->delete((int) $id);
        $this->logAction('DELETE_DEVICE', 'devices');
        $this->flash('success', 'Appareil supprimé.');
        $this->redirect('/devices');
    }

    // -----------------------------------------------------------------------
    // GET /devices/:id/metrics — AJAX : historique métriques
    // -----------------------------------------------------------------------
    public function metrics(string $id): void
    {
        $this->requireAuth();

        if (!$this->isAjax()) {
            $this->abort(403);
        }

        $limit = min((int) $this->query('limit', 30), 60);
        $rows  = $this->devices->latestMetrics((int) $id, $limit);

        $this->json([
            'labels' => array_column($rows, 'label'),
            'cpu'    => array_map('floatval', array_column($rows, 'cpu_usage')),
            'ram'    => array_map('floatval', array_column($rows, 'ram_usage')),
            'disk'   => array_map('floatval', array_column($rows, 'disk_usage')),
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /devices/:id/ping — AJAX : test de connectivité
    // -----------------------------------------------------------------------
    public function ping(string $id): void
    {
        $this->requirePermission('manage_devices');

        if (!$this->isAjax()) {
            $this->abort(403);
        }

        $device = $this->devices->findById((int) $id);
        if (!$device) {
            $this->json(['success' => false, 'message' => 'Appareil introuvable.'], 404);
            return;
        }

        $ip      = $device['ip_address'];
        $alive   = $this->pingHost($ip);
        $status  = $alive ? 'online' : 'offline';

        $this->devices->updateStatus((int) $id, $status);
        $this->logAction('PING_DEVICE', 'devices');

        $this->json([
            'success' => true,
            'alive'   => $alive,
            'status'  => $status,
            'message' => $alive
                ? "L'appareil {$ip} répond."
                : "L'appareil {$ip} ne répond pas.",
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers privés
    // -----------------------------------------------------------------------

    /** Valide les données du formulaire. Retourne un tableau d'erreurs. */
    private function validateDevice(?int $excludeId = null): array
    {
        $errors = [];

        if (empty($this->input('name'))) {
            $errors[] = 'Le nom est obligatoire.';
        }
        if (empty($this->input('hostname'))) {
            $errors[] = 'Le hostname est obligatoire.';
        }

        $ip = $this->input('ip_address');
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $errors[] = 'L\'adresse IP est invalide.';
        }

        $mac = $this->input('mac_address');
        if (empty($mac) || !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac)) {
            $errors[] = 'L\'adresse MAC est invalide (format AA:BB:CC:DD:EE:FF).';
        }

        if (empty($this->input('location_id'))) {
            $errors[] = 'La localisation est obligatoire.';
        }
        if (empty($this->input('os_version'))) {
            $errors[] = 'La version OS est obligatoire.';
        }

        // Vérifie l'unicité de l'IP (hors appareil en cours d'édition)
        if (!empty($ip) && empty($errors)) {
            $existing = $this->devices->findOneBy(['ip_address' => $ip]);
            if ($existing && (int)$existing['id'] !== $excludeId) {
                $errors[] = "L'adresse IP {$ip} est déjà utilisée.";
            }
        }

        return $errors;
    }

    /** Extrait et nettoie les données du formulaire. */
    private function deviceData(): array
    {
        return [
            'name'        => $this->input('name'),
            'hostname'    => $this->input('hostname'),
            'ip_address'  => $this->input('ip_address'),
            'mac_address' => strtoupper($this->input('mac_address')),
            'type'        => $this->input('type', 'other'),
            'location_id' => (int) $this->input('location_id'),
            'status'      => $this->input('status', 'offline'),
            'os_version'  => $this->input('os_version'),
        ];
    }

    /**
     * Ping ICMP simplifié via exec().
     * Fonctionne sous Linux (serveur) et Windows (XAMPP local).
     */
    private function pingHost(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Sécurité : échappe l'IP pour éviter l'injection de commande
        $ip = escapeshellarg($ip);

        $cmd = PHP_OS_FAMILY === 'Windows'
            ? "ping -n 1 -w 1000 {$ip}"
            : "ping -c 1 -W 1 {$ip}";

        exec($cmd . ' 2>&1', $output, $returnCode);

        return $returnCode === 0;
    }
}