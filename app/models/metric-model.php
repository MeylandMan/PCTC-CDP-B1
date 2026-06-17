<?php

require_once ROOT_PATH . '/core/model.php';

class MetricModel extends Model
{
    protected string $table = 'device_metrics';

    // -----------------------------------------------------------------------
    // Vue d'ensemble
    // -----------------------------------------------------------------------

    /**
     * Dernière métrique connue de chaque appareil, avec nom et statut.
     * C'est la base du tableau principal de la page Métriques.
     */
    public function latestPerDevice(string $search = '', string $type = ''): array
    {
        $where    = ['1 = 1'];
        $bindings = [];

        if ($search !== '') {
            $where[]        = '(d.name LIKE :s OR d.hostname LIKE :s)';
            $bindings[':s'] = '%' . $search . '%';
        }
        if ($type !== '') {
            $where[]           = 'd.type = :type';
            $bindings[':type'] = $type;
        }

        $whereClause = implode(' AND ', $where);

        return $this->query(
            "SELECT d.id, d.name, d.hostname, d.type, d.status,
                    m.cpu_usage, m.ram_usage, m.disk_usage,
                    m.network_in, m.network_out, m.temperature,
                    m.collected_at
             FROM devices d
             LEFT JOIN device_metrics m ON m.id = (
                 SELECT id FROM device_metrics
                 WHERE device_id = d.id
                 ORDER BY collected_at DESC
                 LIMIT 1
             )
             WHERE {$whereClause}
             ORDER BY d.name ASC",
            $bindings
        );
    }

    /**
     * Moyennes globales sur les dernières 5 minutes, tous appareils en ligne.
     */
    public function globalAverages(): array
    {
        $row = $this->queryOne(
            "SELECT
                ROUND(AVG(m.cpu_usage),  2) AS avg_cpu,
                ROUND(AVG(m.ram_usage),  2) AS avg_ram,
                ROUND(AVG(m.disk_usage), 2) AS avg_disk,
                ROUND(AVG(m.temperature), 2) AS avg_temp
             FROM device_metrics m
             JOIN devices d ON d.id = m.device_id
             WHERE d.status = 'online'
               AND m.collected_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );

        return [
            'avg_cpu'  => (float) ($row['avg_cpu']  ?? 0),
            'avg_ram'  => (float) ($row['avg_ram']  ?? 0),
            'avg_disk' => (float) ($row['avg_disk'] ?? 0),
            'avg_temp' => (float) ($row['avg_temp'] ?? 0),
        ];
    }

    /**
     * Top 5 des appareils les plus consommateurs sur une métrique donnée.
     * $column doit être l'une de : cpu_usage, ram_usage, disk_usage.
     */
    public function topConsumers(string $column, int $limit = 5): array
    {
        $allowed = ['cpu_usage', 'ram_usage', 'disk_usage'];
        if (!in_array($column, $allowed, true)) {
            $column = 'cpu_usage';
        }

        return $this->query(
            "SELECT d.id, d.name, m.{$column} AS value, m.collected_at
             FROM devices d
             JOIN device_metrics m ON m.id = (
                 SELECT id FROM device_metrics
                 WHERE device_id = d.id
                 ORDER BY collected_at DESC
                 LIMIT 1
             )
             WHERE d.status = 'online'
             ORDER BY m.{$column} DESC
             LIMIT :lim",
            [':lim' => $limit]
        );
    }

    // -----------------------------------------------------------------------
    // Historique par appareil (graphiques)
    // -----------------------------------------------------------------------

    /**
     * Historique des N dernières collectes pour un appareil donné.
     * Utilisé par /metrics/device/:id et les graphiques AJAX.
     */
    public function historyForDevice(int $deviceId, int $limit = 30): array
    {
        $rows = $this->query(
            "SELECT cpu_usage, ram_usage, disk_usage,
                    network_in, network_out, temperature,
                    DATE_FORMAT(collected_at, '%H:%i') AS label,
                    collected_at
             FROM device_metrics
             WHERE device_id = :id
             ORDER BY collected_at DESC
             LIMIT :lim",
            [':id' => $deviceId, ':lim' => $limit]
        );

        return array_reverse($rows);
    }

    /**
     * Moyenne CPU/RAM/Disque par heure sur les dernières 24h pour un appareil.
     * Utilisé pour un graphique plus lissé que la collecte brute toutes les 5 min.
     */
    public function hourlyAverages(int $deviceId, int $hours = 24): array
    {
        return $this->query(
            "SELECT
                DATE_FORMAT(collected_at, '%H:00') AS hour_label,
                ROUND(AVG(cpu_usage), 2)  AS avg_cpu,
                ROUND(AVG(ram_usage), 2)  AS avg_ram,
                ROUND(AVG(disk_usage), 2) AS avg_disk
             FROM device_metrics
             WHERE device_id = :id
               AND collected_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
             GROUP BY HOUR(collected_at)
             ORDER BY collected_at ASC",
            [':id' => $deviceId, ':hours' => $hours]
        );
    }
}