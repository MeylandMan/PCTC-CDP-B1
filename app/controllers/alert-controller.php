<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/alert-model.php';

class AlertController extends Controller
{
    private AlertModel $alerts;

    public function __construct()
    {
        $this->alerts = new AlertModel();
    }

    // -----------------------------------------------------------------------
    // GET /alerts
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requirePermission('manage_alerts');

        $page     = (int)    $this->query('page',   1);
        $level    = (string) $this->query('level',  '');
        $status   = (string) $this->query('status', 'open');
        $search   = (string) $this->query('search', '');

        $paginator    = $this->alerts->listPaginated($page, 15, $level, $status, 0, $search);
        $countStatus  = $this->alerts->countsByStatus();
        $countLevel   = $this->alerts->countsByLevel();

        $this->view('alerts/index', [
            'title'        => 'Alertes',
            'breadcrumbs'  => ['Alertes' => null],
            'paginator'    => $paginator,
            'countStatus'  => $countStatus,
            'countLevel'   => $countLevel,
            'levels'       => $this->alerts->levels(),
            'statuses'     => $this->alerts->statuses(),
            'filters'      => compact('level', 'status', 'search'),
            'alertCount'   => $countStatus['open'],
            'incidentCount'=> 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // GET /alerts/:id
    // -----------------------------------------------------------------------
    public function show(string $id): void
    {
        $this->requirePermission('manage_alerts');

        $alert = $this->alerts->findWithDevice((int) $id);
        if (!$alert) {
            $this->abort(404, 'Alerte introuvable.');
        }

        $this->view('alerts/show', [
            'title'        => 'Alerte #' . $id,
            'breadcrumbs'  => ['Alertes' => '/alerts', 'Détail' => null],
            'alert'        => $alert,
            'alertCount'   => 0,
            'incidentCount'=> 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /alerts/:id/acknowledge  (AJAX)
    // -----------------------------------------------------------------------
    public function acknowledge(string $id): void
    {
        $this->requirePermission('manage_alerts');
        $this->verifyCsrfToken();

        $alert = $this->alerts->findById((int) $id);
        if (!$alert) {
            $this->json(['success' => false, 'message' => 'Alerte introuvable.'], 404);
            return;
        }

        $this->alerts->acknowledge((int) $id);
        $this->logAction('ACKNOWLEDGE_ALERT', 'alerts');

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Alerte reconnue.']);
        } else {
            $this->flash('success', 'Alerte #' . $id . ' reconnue.');
            $this->redirect('/alerts');
        }
    }

    // -----------------------------------------------------------------------
    // POST /alerts/:id/resolve  (AJAX)
    // -----------------------------------------------------------------------
    public function resolve(string $id): void
    {
        $this->requirePermission('manage_alerts');
        $this->verifyCsrfToken();

        $alert = $this->alerts->findById((int) $id);
        if (!$alert) {
            $this->json(['success' => false, 'message' => 'Alerte introuvable.'], 404);
            return;
        }

        $this->alerts->resolve((int) $id);
        $this->logAction('RESOLVE_ALERT', 'alerts');

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Alerte résolue.']);
        } else {
            $this->flash('success', 'Alerte #' . $id . ' résolue.');
            $this->redirect('/alerts');
        }
    }

    // -----------------------------------------------------------------------
    // POST /alerts/:id/delete
    // -----------------------------------------------------------------------
    public function delete(string $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->verifyCsrfToken();

        $this->alerts->delete((int) $id);
        $this->logAction('DELETE_ALERT', 'alerts');
        $this->flash('success', 'Alerte supprimée.');
        $this->redirect('/alerts');
    }

    // Routes inutilisées du resource() — redirigent vers index
    public function create(): void { $this->redirect('/alerts'); }
    public function store(): void  { $this->redirect('/alerts'); }
    public function edit(): void   { $this->redirect('/alerts'); }
    public function update(): void { $this->redirect('/alerts'); }
}