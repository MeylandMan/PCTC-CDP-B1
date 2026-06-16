<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/location-model.php';

class LocationController extends Controller
{
    private LocationModel $locations;

    public function __construct()
    {
        $this->locations = new LocationModel();
    }

    public function index(): void
    {
        $this->requirePermission('manage_devices');

        $all = $this->locations->findAll('site_name');

        $this->view('locations/index', [
            'title'         => 'Localisations',
            'breadcrumbs'   => ['Localisations' => null],
            'locations'     => $all,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('manage_devices');

        $this->view('locations/form', [
            'title'         => 'Ajouter un site',
            'breadcrumbs'   => ['Localisations' => '/locations', 'Ajouter' => null],
            'location'      => null,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('manage_devices');
        $this->verifyCsrfToken();

        $errors = $this->validate();
        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/locations/create');
            return;
        }

        $this->locations->insert($this->formData());
        $this->logAction('CREATE_LOCATION', 'locations');
        $this->flash('success', 'Site ajouté.');
        $this->redirect('/locations');
    }

    public function show(string $id): void
    {
        $this->requirePermission('manage_devices');

        $location = $this->locations->findById((int) $id);
        if (!$location) $this->abort(404, 'Site introuvable.');

        // Appareils sur ce site
        $db      = Database::getInstance()->getConnection();
        $stmt    = $db->prepare(
            "SELECT id, name, hostname, ip_address, type, status
             FROM devices WHERE location_id = :id ORDER BY name"
        );
        $stmt->execute([':id' => $id]);
        $devices = $stmt->fetchAll();

        $this->view('locations/show', [
            'title'         => e($location['site_name']),
            'breadcrumbs'   => ['Localisations' => '/locations', $location['site_name'] => null],
            'location'      => $location,
            'devices'       => $devices,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('manage_devices');

        $location = $this->locations->findById((int) $id);
        if (!$location) $this->abort(404, 'Site introuvable.');

        $this->view('locations/form', [
            'title'         => 'Modifier — ' . e($location['site_name']),
            'breadcrumbs'   => ['Localisations' => '/locations', 'Modifier' => null],
            'location'      => $location,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('manage_devices');
        $this->verifyCsrfToken();

        $location = $this->locations->findById((int) $id);
        if (!$location) $this->abort(404, 'Site introuvable.');

        $errors = $this->validate();
        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/locations/' . $id . '/edit');
            return;
        }

        $this->locations->update((int) $id, $this->formData());
        $this->logAction('UPDATE_LOCATION', 'locations');
        $this->flash('success', 'Site mis à jour.');
        $this->redirect('/locations');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->verifyCsrfToken();

        $this->locations->delete((int) $id);
        $this->logAction('DELETE_LOCATION', 'locations');
        $this->flash('success', 'Site supprimé.');
        $this->redirect('/locations');
    }

    private function validate(): array
    {
        $errors = [];
        if (empty($this->input('site_name'))) $errors[] = 'Le nom du site est obligatoire.';
        if (empty($this->input('city')))      $errors[] = 'La ville est obligatoire.';
        return $errors;
    }

    private function formData(): array
    {
        return [
            'site_name' => $this->input('site_name'),
            'city'      => $this->input('city'),
            'country'   => $this->input('country'),
            'latitude'  => $this->input('latitude')  ?: null,
            'longitude' => $this->input('longitude') ?: null,
        ];
    }
}