<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/core/model.php';

class AuditController extends Controller
{
    private object $model;

    public function __construct()
    {
        // Utilise le Model de base directement — pas besoin d'un model dédié
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /audit
    public function index(): void
    {
        $this->requirePermission('view_logs');

        $page    = (int)    $this->query('page',   1);
        $module  = (string) $this->query('module', '');
        $userId  = (int)    $this->query('user_id', 0);
        $search  = (string) $this->query('search', '');

        $where    = ['1 = 1'];
        $bindings = [];

        if ($module !== '') {
            $where[]            = 'l.module = :module';
            $bindings[':module'] = $module;
        }
        if ($userId > 0) {
            $where[]           = 'l.user_id = :uid';
            $bindings[':uid']  = $userId;
        }
        if ($search !== '') {
            $where[]        = '(l.action LIKE :s OR l.ip_address LIKE :s)';
            $bindings[':s'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        $perPage     = 20;
        $offset      = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT l.*, CONCAT(u.firstname,' ',u.lastname) AS user_name
             FROM logs l
             JOIN users u ON u.id = l.user_id
             WHERE {$whereClause}
             ORDER BY l.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->execute(array_merge($bindings, [':limit' => $perPage, ':offset' => $offset]));
        $data = $stmt->fetchAll();

        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM logs l WHERE {$whereClause}"
        );
        $stmt2->execute($bindings);
        $total = (int) $stmt2->fetchColumn();

        // Modules distincts pour le filtre
        $modulesStmt = $this->db->query("SELECT DISTINCT module FROM logs ORDER BY module");
        $modules     = array_column($modulesStmt->fetchAll(), 'module');

        $paginator = [
            'data'        => $data,
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $page,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
        ];

        $this->view('audit/index', [
            'title'         => 'Audit & Logs',
            'breadcrumbs'   => ['Audit' => null],
            'paginator'     => $paginator,
            'modules'       => $modules,
            'filters'       => compact('module', 'userId', 'search'),
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // GET /audit/export  — export CSV
    public function export(): void
    {
        $this->requirePermission('view_logs');

        $stmt = $this->db->prepare(
            "SELECT l.id, CONCAT(u.firstname,' ',u.lastname) AS user_name,
                    l.action, l.module, l.ip_address, l.user_agent, l.created_at
             FROM logs l
             JOIN users u ON u.id = l.user_id
             ORDER BY l.created_at DESC
             LIMIT 5000"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->logAction('EXPORT_LOGS', 'audit');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, ['ID', 'Utilisateur', 'Action', 'Module', 'IP', 'User Agent', 'Date'], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'],
                $row['user_name'],
                $row['action'],
                $row['module'],
                $row['ip_address'],
                $row['user_agent'],
                $row['created_at'],
            ], ';');
        }

        fclose($out);
        exit;
    }
}