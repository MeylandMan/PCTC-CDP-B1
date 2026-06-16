<?php

require_once ROOT_PATH . '/core/controller.php';

class ReportController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /reports
    public function index(): void
    {
        $this->requirePermission('view_reports');

        $stmt = $this->db->prepare(
            "SELECT r.*, CONCAT(u.firstname,' ',u.lastname) AS generated_by_name
             FROM reports r
             JOIN users u ON u.id = r.generated_by
             ORDER BY r.generated_at DESC
             LIMIT 50"
        );
        $stmt->execute();
        $reports = $stmt->fetchAll();

        $this->view('reports/index', [
            'title'         => 'Rapports',
            'breadcrumbs'   => ['Rapports' => null],
            'reports'       => $reports,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // POST /reports/generate
    public function generate(): void
    {
        $this->requirePermission('export_data');
        $this->verifyCsrfToken();

        $type   = $this->input('report_type', 'daily');
        $format = $this->input('format', 'csv');

        // Génère les données selon le type
        $data     = $this->collectData($type);
        $filename = 'rapport_' . $type . '_' . date('Y-m-d') . '.' . $format;
        $filePath = '/storage/reports/' . date('Y') . '/' . date('m') . '/' . $filename;

        // Sauvegarde le rapport en base
        $stmt = $this->db->prepare(
            "INSERT INTO reports (generated_by, report_type, file_path, generated_at)
             VALUES (:uid, :type, :path, NOW())"
        );
        $stmt->execute([
            ':uid'  => $this->currentUser()['id'],
            ':type' => $type,
            ':path' => $filePath,
        ]);

        $reportId = (int) $this->db->lastInsertId();

        $this->logAction('GENERATE_REPORT', 'reports');

        // Téléchargement immédiat
        $this->downloadReport($data, $filename, $format, $type);
    }

    // GET /reports/:id/download
    public function download(string $id): void
    {
        $this->requirePermission('view_reports');

        $stmt = $this->db->prepare("SELECT * FROM reports WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $report = $stmt->fetch();

        if (!$report) {
            $this->flash('error', 'Rapport introuvable.');
            $this->redirect('/reports');
            return;
        }

        // Régénère le rapport à la volée
        $data = $this->collectData($report['report_type']);
        $ext  = pathinfo($report['file_path'], PATHINFO_EXTENSION) ?: 'csv';
        $this->downloadReport($data, basename($report['file_path']), $ext, $report['report_type']);
    }

    // POST /reports/:id/delete
    public function delete(string $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->verifyCsrfToken();

        $stmt = $this->db->prepare("DELETE FROM reports WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $this->logAction('DELETE_REPORT', 'reports');
        $this->flash('success', 'Rapport supprimé.');
        $this->redirect('/reports');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Collecte les données selon le type de rapport.
     */
    private function collectData(string $type): array
    {
        $interval = match($type) {
            'weekly'  => '7 DAY',
            'monthly' => '30 DAY',
            default   => '1 DAY',
        };

        // Métriques moyennes par appareil
        $stmt = $this->db->prepare(
            "SELECT d.name AS device_name, d.ip_address, d.type, d.status,
                    ROUND(AVG(m.cpu_usage), 2)  AS avg_cpu,
                    ROUND(AVG(m.ram_usage), 2)  AS avg_ram,
                    ROUND(AVG(m.disk_usage), 2) AS avg_disk,
                    COUNT(m.id)                  AS samples
             FROM devices d
             LEFT JOIN device_metrics m ON m.device_id = d.id
                 AND m.collected_at >= DATE_SUB(NOW(), INTERVAL {$interval})
             GROUP BY d.id
             ORDER BY d.name"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Envoie le rapport en téléchargement CSV.
     */
    private function downloadReport(array $data, string $filename, string $format, string $type): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Appareil', 'IP', 'Type', 'Statut',
            'CPU moy. (%)', 'RAM moy. (%)', 'Disque moy. (%)', 'Échantillons'
        ], ';');

        foreach ($data as $row) {
            fputcsv($out, [
                $row['device_name'],
                $row['ip_address'],
                $row['type'],
                $row['status'],
                $row['avg_cpu']  ?? '—',
                $row['avg_ram']  ?? '—',
                $row['avg_disk'] ?? '—',
                $row['samples'],
            ], ';');
        }

        fclose($out);
        exit;
    }
}