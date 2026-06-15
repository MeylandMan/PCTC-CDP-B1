<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/incident-model.php';
require_once ROOT_PATH . '/app/models/device-model.php';
require_once ROOT_PATH . '/app/models/user-model.php';

class IncidentController extends Controller
{
    private IncidentModel $incidents;
    private DeviceModel   $devices;
    private UserModel     $users;

    public function __construct()
    {
        $this->incidents = new IncidentModel();
        $this->devices   = new DeviceModel();
        $this->users     = new UserModel();
    }

    // -----------------------------------------------------------------------
    // GET /incidents
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requirePermission('manage_incidents');

        $page     = (int)    $this->query('page',     1);
        $status   = (string) $this->query('status',   '');
        $priority = (string) $this->query('priority', '');
        $search   = (string) $this->query('search',   '');
        $mine     = (string) $this->query('mine',     '');

        // Technicien : ne voit que ses incidents par défaut
        $userId = 0;
        if ($mine === '1' || $this->hasRole('technicien')) {
            $userId = (int) $this->currentUser()['id'];
        }

        $paginator    = $this->incidents->listPaginated($page, 15, $status, $priority, $userId, $search);
        $countStatus  = $this->incidents->countsByStatus();

        $this->view('incidents/index', [
            'title'        => 'Incidents',
            'breadcrumbs'  => ['Incidents' => null],
            'paginator'    => $paginator,
            'countStatus'  => $countStatus,
            'priorities'   => $this->incidents->priorities(),
            'statuses'     => $this->incidents->statuses(),
            'filters'      => compact('status', 'priority', 'search', 'mine'),
            'alertCount'   => 0,
            'incidentCount'=> array_sum(array_filter(
                $countStatus,
                fn($k) => !in_array($k, ['resolved', 'closed']),
                ARRAY_FILTER_USE_KEY
            )),
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /incidents/:id
    // -----------------------------------------------------------------------
    public function show(string $id): void
    {
        $this->requirePermission('manage_incidents');

        $incident = $this->incidents->findWithDetails((int) $id);
        if (!$incident) {
            $this->abort(404, 'Incident introuvable.');
        }

        // Techniciens disponibles pour la réassignation
        $techniciens = $this->users->findBy(['role_id' => 3, 'is_active' => 1], 'firstname');

        $this->view('incidents/show', [
            'title'        => 'Incident #' . $id,
            'breadcrumbs'  => ['Incidents' => '/incidents', '#' . $id => null],
            'incident'     => $incident,
            'nextStatuses' => $this->incidents->nextStatuses($incident['status']),
            'techniciens'  => $techniciens,
            'alertCount'   => 0,
            'incidentCount'=> 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /incidents/create
    // -----------------------------------------------------------------------
    public function create(): void
    {
        $this->requirePermission('manage_incidents');

        $this->view('incidents/form', [
            'title'        => 'Créer un incident',
            'breadcrumbs'  => ['Incidents' => '/incidents', 'Créer' => null],
            'incident'     => null,
            'devices'      => $this->devices->findAll('name'),
            'techniciens'  => $this->users->findBy(['role_id' => 3, 'is_active' => 1], 'firstname'),
            'priorities'   => $this->incidents->priorities(),
            'alertCount'   => 0,
            'incidentCount'=> 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /incidents/store
    // -----------------------------------------------------------------------
    public function store(): void
    {
        $this->requirePermission('manage_incidents');
        $this->verifyCsrfToken();

        $errors = [];
        if (empty($this->input('title')))       $errors[] = 'Le titre est obligatoire.';
        if (empty($this->input('device_id')))   $errors[] = 'L\'appareil est obligatoire.';
        if (empty($this->input('assigned_to'))) $errors[] = 'Le technicien est obligatoire.';

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/incidents/create');
            return;
        }

        $id = $this->incidents->createIncident([
            'device_id'   => (int) $this->input('device_id'),
            'assigned_to' => (int) $this->input('assigned_to'),
            'priority'    => $this->input('priority', 'medium'),
            'title'       => $this->input('title'),
            'description' => $this->input('description'),
        ]);

        $this->logAction('CREATE_INCIDENT', 'incidents');
        $this->flash('success', 'Incident #' . $id . ' créé avec succès.');
        $this->redirect('/incidents/' . $id);
    }

    // -----------------------------------------------------------------------
    // GET /incidents/:id/edit
    // -----------------------------------------------------------------------
    public function edit(string $id): void
    {
        $this->requirePermission('manage_incidents');

        $incident = $this->incidents->findWithDetails((int) $id);
        if (!$incident) {
            $this->abort(404, 'Incident introuvable.');
        }

        $this->view('incidents/form', [
            'title'        => 'Modifier — Incident #' . $id,
            'breadcrumbs'  => ['Incidents' => '/incidents', 'Modifier' => null],
            'incident'     => $incident,
            'devices'      => $this->devices->findAll('name'),
            'techniciens'  => $this->users->findBy(['role_id' => 3, 'is_active' => 1], 'firstname'),
            'priorities'   => $this->incidents->priorities(),
            'alertCount'   => 0,
            'incidentCount'=> 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /incidents/:id/update
    // -----------------------------------------------------------------------
    public function update(string $id): void
    {
        $this->requirePermission('manage_incidents');
        $this->verifyCsrfToken();

        $incident = $this->incidents->findById((int) $id);
        if (!$incident) {
            $this->abort(404, 'Incident introuvable.');
        }

        $this->incidents->update((int) $id, [
            'device_id'   => (int) $this->input('device_id'),
            'assigned_to' => (int) $this->input('assigned_to'),
            'priority'    => $this->input('priority', 'medium'),
            'title'       => $this->input('title'),
            'description' => $this->input('description'),
        ]);

        $this->logAction('UPDATE_INCIDENT', 'incidents');
        $this->flash('success', 'Incident mis à jour.');
        $this->redirect('/incidents/' . $id);
    }

    // -----------------------------------------------------------------------
    // POST /incidents/:id/status  (AJAX + form)
    // -----------------------------------------------------------------------
    public function updateStatus(string $id): void
    {
        $this->requirePermission('manage_incidents');
        $this->verifyCsrfToken();

        $incident = $this->incidents->findById((int) $id);
        if (!$incident) {
            $this->isAjax()
                ? $this->json(['success' => false, 'message' => 'Incident introuvable.'], 404)
                : $this->abort(404);
            return;
        }

        $newStatus = $this->input('status');
        $allowed   = $this->incidents->nextStatuses($incident['status']);

        if (!in_array($newStatus, $allowed, true)) {
            $msg = 'Transition de statut non autorisée.';
            $this->isAjax()
                ? $this->json(['success' => false, 'message' => $msg], 422)
                : $this->flash('error', $msg);
            if (!$this->isAjax()) $this->redirect('/incidents/' . $id);
            return;
        }

        $this->incidents->updateStatus((int) $id, $newStatus);
        $this->logAction('UPDATE_INCIDENT_STATUS', 'incidents');

        if ($this->isAjax()) {
            $this->json(['success' => true, 'status' => $newStatus]);
        } else {
            $this->flash('success', 'Statut mis à jour.');
            $this->redirect('/incidents/' . $id);
        }
    }

    // -----------------------------------------------------------------------
    // POST /incidents/:id/assign
    // -----------------------------------------------------------------------
    public function assign(string $id): void
    {
        $this->requirePermission('manage_incidents');
        $this->verifyCsrfToken();

        $incident = $this->incidents->findById((int) $id);
        if (!$incident) {
            $this->abort(404, 'Incident introuvable.');
        }

        $techId = (int) $this->input('assigned_to');
        if ($techId > 0) {
            $this->incidents->assign((int) $id, $techId);
            $this->logAction('ASSIGN_INCIDENT', 'incidents');
            $this->flash('success', 'Incident réassigné.');
        }

        $this->redirect('/incidents/' . $id);
    }

    // -----------------------------------------------------------------------
    // POST /incidents/:id/delete
    // -----------------------------------------------------------------------
    public function delete(string $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->verifyCsrfToken();

        $this->incidents->delete((int) $id);
        $this->logAction('DELETE_INCIDENT', 'incidents');
        $this->flash('success', 'Incident supprimé.');
        $this->redirect('/incidents');
    }
}