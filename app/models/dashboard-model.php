<?php

require_once ROOT_PATH . '/core/Model.php';

class DashboardModel extends Model
{
    // table inutilisée ici (requêtes multi-tables), on la déclare quand même
    protected string $table = 'devices';

    // -----------------------------------------------------------------------
    // Compteurs — ligne de KPIs en haut du dashboard
    // -----------------------------------------------------------------------

    /**
     * Retourne les compteurs globaux en une seule requête.
     *
     * [
     *   'total'       => 12,
     *   'online'      => 8,
     *   'offline'     => 2,
     *   'warning'     => 1,
     *   'maintenance' => 1,
     * ]
     */
    public function deviceCounts(): array
    {
        $row = $this->queryOne(
            "SELECT
                COUNT(*)                                          AS total,
                SUM(status = 'online')                           AS online,
                SUM(status = 'offline')                          AS offline,
                SUM(status = 'warning')                          AS warning,
                SUM(status = 'maintenance')                      AS maintenance
             FROM devices"
        );

        // Cast en entiers (SUM retourne des chaînes en PHP/PDO)
        return array_map('intval', $row ?: [
            'total' => 0, 'online' => 0, 'offline' => 0,
            'warning' => 0, 'maintenance' => 0,
        ]);
    }

    /**
     * Nombre d'alertes ouvertes, par niveau.
     *
     * ['total' => 4, 'critique' => 1, 'urgent' => 0, 'warning' => 2, 'info' => 1]
     */
    public function alertCounts(): array
    {
        $row = $this->queryOne(
            "SELECT
                COUNT(*)                            AS total,
                SUM(alert_level = 'critique')       AS critique,
                SUM(alert_level = 'urgent')         AS urgent,
                SUM(alert_level = 'warning')        AS warning,
                SUM(alert_level = 'info')           AS info
             FROM alerts
             WHERE status = 'open'"
        );

        return array_map('intval', $row ?: [
            'total' => 0, 'critique' => 0, 'urgent' => 0,
            'warning' => 0, 'info' => 0,
        ]);
    }

    /**
     * Nombre d'incidents ouverts (hors resolved et closed).
     */
    public function openIncidentCount(): int
    {
        return (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM incidents
             WHERE status NOT IN ('resolved', 'closed')"
        )['n'];
    }

    /**
     * Nombre de notifications non lues pour un utilisateur donné.
     */
    public function unreadNotificationCount(int $userId): int
    {
        return (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM notifications
             WHERE user_id = :uid AND is_read = 0",
            [':uid' => $userId]
        )['n'];
    }

    // -----------------------------------------------------------------------
    // Métriques moyennes — jauges CPU / RAM / Disque
    // -----------------------------------------------------------------------

    /**
     * Retourne la moyenne des dernières métriques de tous les appareils
     * en ligne (sur les 5 dernières minutes).
     *
     * ['avg_cpu' => 52.3, 'avg_ram' => 71.4, 'avg_disk' => 48.2]
     */
    public function avgMetrics(): array
    {
        $row = $this->queryOne(
            "SELECT
                ROUND(AVG(m.cpu_usage),  2) AS avg_cpu,
                ROUND(AVG(m.ram_usage),  2) AS avg_ram,
                ROUND(AVG(m.disk_usage), 2) AS avg_disk
             FROM device_metrics m
             JOIN devices d ON d.id = m.device_id
             WHERE d.status = 'online'
               AND m.collected_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );

        return [
            'avg_cpu'  => (float) ($row['avg_cpu']  ?? 0),
            'avg_ram'  => (float) ($row['avg_ram']  ?? 0),
            'avg_disk' => (float) ($row['avg_disk'] ?? 0),
        ];
    }

    // -----------------------------------------------------------------------
    // Données pour graphiques
    // -----------------------------------------------------------------------

    /**
     * Historique CPU / RAM des dernières 30 collectes pour un appareil.
     * Utilisé par les graphiques de courbes temps réel (AJAX).
     *
     * Retourne :
     * [
     *   'labels'  => ['14:00', '14:05', …],
     *   'cpu'     => [45.2, 48.7, …],
     *   'ram'     => [62.5, 63.1, …],
     *   'disk'    => [38.1, 38.2, …],
     * ]
     */
    public function metricsHistory(int $deviceId, int $limit = 30): array
    {
        $rows = $this->query(
            "SELECT cpu_usage, ram_usage, disk_usage,
                    DATE_FORMAT(collected_at, '%H:%i') AS label
             FROM device_metrics
             WHERE device_id = :id
             ORDER BY collected_at DESC
             LIMIT :lim",
            [':id' => $deviceId, ':lim' => $limit]
        );

        // On inverse pour avoir l'ordre chronologique
        $rows = array_reverse($rows);

        return [
            'labels' => array_column($rows, 'label'),
            'cpu'    => array_map('floatval', array_column($rows, 'cpu_usage')),
            'ram'    => array_map('floatval', array_column($rows, 'ram_usage')),
            'disk'   => array_map('floatval', array_column($rows, 'disk_usage')),
        ];
    }

    /**
     * Répartition des appareils par type (pour le donut chart).
     *
     * [['type' => 'server', 'total' => 4], ['type' => 'switch', 'total' => 2], …]
     */
    public function devicesByType(): array
    {
        return $this->query(
            "SELECT type, COUNT(*) AS total
             FROM devices
             GROUP BY type
             ORDER BY total DESC"
        );
    }

    /**
     * Nombre d'alertes par niveau sur les 7 derniers jours (bar chart).
     *
     * [['day' => '2025-06-08', 'critique' => 2, 'warning' => 5, …], …]
     */
    public function alertsPerDay(int $days = 7): array
    {
        return $this->query(
            "SELECT
                DATE(created_at)              AS day,
                SUM(alert_level = 'info')     AS info,
                SUM(alert_level = 'warning')  AS warning,
                SUM(alert_level = 'critique') AS critique,
                SUM(alert_level = 'urgent')   AS urgent
             FROM alerts
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [':days' => $days]
        );
    }

    // -----------------------------------------------------------------------
    // Listes récentes — tableaux du bas du dashboard
    // -----------------------------------------------------------------------

    /**
     * Les 8 dernières alertes ouvertes avec le nom de l'appareil.
     */
    public function recentAlerts(int $limit = 8): array
    {
        return $this->query(
            "SELECT a.id, a.alert_level, a.alert_type, a.message,
                    a.current_value, a.threshold_value, a.status,
                    a.created_at, d.name AS device_name
             FROM alerts a
             JOIN devices d ON d.id = a.device_id
             WHERE a.status = 'open'
             ORDER BY
                FIELD(a.alert_level, 'urgent','critique','warning','info'),
                a.created_at DESC
             LIMIT :lim",
            [':lim' => $limit]
        );
    }

    /**
     * Les 6 derniers incidents actifs avec le nom de l'appareil
     * et le nom du technicien assigné.
     */
    public function recentIncidents(int $limit = 6): array
    {
        return $this->query(
            "SELECT i.id, i.title, i.priority, i.status, i.opened_at,
                    d.name AS device_name,
                    CONCAT(u.firstname, ' ', u.lastname) AS assigned_to
             FROM incidents i
             JOIN devices d ON d.id  = i.device_id
             JOIN users   u ON u.id  = i.assigned_to
             WHERE i.status NOT IN ('resolved', 'closed')
             ORDER BY
                FIELD(i.priority, 'critique','high','medium','low'),
                i.opened_at DESC
             LIMIT :lim",
            [':lim' => $limit]
        );
    }

    /**
     * Les 5 appareils avec les métriques les plus critiques
     * (disque ou RAM > 80 %).
     */
    public function criticalDevices(): array
    {
        return $this->query(
            "SELECT d.id, d.name, d.hostname, d.status,
                    m.cpu_usage, m.ram_usage, m.disk_usage, m.temperature,
                    m.collected_at
             FROM devices d
             JOIN device_metrics m ON m.id = (
                 SELECT id FROM device_metrics
                 WHERE device_id = d.id
                 ORDER BY collected_at DESC
                 LIMIT 1
             )
             WHERE d.status = 'online'
               AND (m.ram_usage > 80 OR m.disk_usage > 80 OR m.cpu_usage > 85)
             ORDER BY GREATEST(m.cpu_usage, m.ram_usage, m.disk_usage) DESC
             LIMIT 5"
        );
    }

    // -----------------------------------------------------------------------
    // Données JSON pour les endpoints AJAX
    // -----------------------------------------------------------------------

    /**
     * Retourne toutes les stats en un seul tableau (utilisé par /dashboard/stats).
     * Appelé toutes les 30 secondes par le JS du dashboard pour rafraîchir
     * les KPIs sans recharger la page.
     */
    public function allStats(int $userId): array
    {
        return [
            'devices'       => $this->deviceCounts(),
            'alerts'        => $this->alertCounts(),
            'incidents'     => $this->openIncidentCount(),
            'notifications' => $this->unreadNotificationCount($userId),
            'metrics'       => $this->avgMetrics(),
        ];
    }
}