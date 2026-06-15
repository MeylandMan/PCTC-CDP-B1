<?php

require_once ROOT_PATH . '/core/model.php';

class AlertModel extends Model
{
    protected string $table      = 'alerts';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'device_id', 'alert_level', 'alert_type',
        'message', 'threshold_value', 'current_value', 'status',
    ];

    // -----------------------------------------------------------------------
    // Lecture
    // -----------------------------------------------------------------------

    /**
     * Liste paginée avec nom de l'appareil.
     * Filtres : level, status, device_id, search.
     */
    public function listPaginated(
        int    $page    = 1,
        int    $perPage = 15,
        string $level   = '',
        string $status  = '',
        int    $deviceId = 0,
        string $search  = ''
    ): array {
        $where    = ['1 = 1'];
        $bindings = [];

        if ($level !== '') {
            $where[]           = 'a.alert_level = :level';
            $bindings[':level'] = $level;
        }
        if ($status !== '') {
            $where[]            = 'a.status = :status';
            $bindings[':status'] = $status;
        }
        if ($deviceId > 0) {
            $where[]              = 'a.device_id = :device_id';
            $bindings[':device_id'] = $deviceId;
        }
        if ($search !== '') {
            $where[]        = '(d.name LIKE :s OR a.alert_type LIKE :s OR a.message LIKE :s)';
            $bindings[':s'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $data = $this->query(
            "SELECT a.*, d.name AS device_name, d.ip_address
             FROM alerts a
             JOIN devices d ON d.id = a.device_id
             WHERE {$whereClause}
             ORDER BY
                FIELD(a.alert_level,'urgent','critique','warning','info'),
                a.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($bindings, [':limit' => $perPage, ':offset' => $offset])
        );

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS n
             FROM alerts a
             JOIN devices d ON d.id = a.device_id
             WHERE {$whereClause}",
            $bindings
        )['n'];

        return [
            'data'        => $data,
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $page,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Détail d'une alerte avec nom et IP de l'appareil.
     */
    public function findWithDevice(int $id): array|false
    {
        return $this->queryOne(
            "SELECT a.*, d.name AS device_name, d.ip_address, d.type AS device_type
             FROM alerts a
             JOIN devices d ON d.id = a.device_id
             WHERE a.id = :id",
            [':id' => $id]
        );
    }

    /**
     * Compteurs par statut (pour les onglets de filtre).
     */
    public function countsByStatus(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS n FROM alerts GROUP BY status"
        );

        $result = ['open' => 0, 'acknowledged' => 0, 'resolved' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['n'];
        }
        return $result;
    }

    /**
     * Compteurs par niveau (pour les badges).
     */
    public function countsByLevel(): array
    {
        $rows = $this->query(
            "SELECT alert_level, COUNT(*) AS n
             FROM alerts WHERE status = 'open'
             GROUP BY alert_level"
        );

        $result = ['info' => 0, 'warning' => 0, 'critique' => 0, 'urgent' => 0];
        foreach ($rows as $row) {
            $result[$row['alert_level']] = (int) $row['n'];
        }
        return $result;
    }

    // -----------------------------------------------------------------------
    // Changements de statut
    // -----------------------------------------------------------------------

    public function acknowledge(int $id): void
    {
        $this->execute(
            "UPDATE alerts SET status = 'acknowledged' WHERE id = :id AND status = 'open'",
            [':id' => $id]
        );
    }

    public function resolve(int $id): void
    {
        $this->execute(
            "UPDATE alerts SET status = 'resolved' WHERE id = :id",
            [':id' => $id]
        );
    }

    // -----------------------------------------------------------------------
    // Création automatique depuis le moteur de monitoring
    // -----------------------------------------------------------------------

    /**
     * Vérifie les seuils et crée une alerte si nécessaire.
     * Appelé après chaque collecte de métriques.
     *
     * @param int   $deviceId
     * @param array $metrics  ['cpu_usage' => 92.5, 'ram_usage' => 86.0, ...]
     */
    public function checkThresholds(int $deviceId, array $metrics): void
    {
        $thresholds = [
            'cpu_usage'  => ['value' => 90,  'type' => 'cpu_usage',  'level' => 'critique'],
            'ram_usage'  => ['value' => 85,  'type' => 'ram_usage',  'level' => 'warning'],
            'disk_usage' => ['value' => 95,  'type' => 'disk_usage', 'level' => 'critique'],
        ];

        foreach ($thresholds as $key => $threshold) {
            $current = (float)($metrics[$key] ?? 0);

            if ($current >= $threshold['value']) {
                // Vérifie qu'une alerte ouverte du même type n'existe pas déjà
                $existing = $this->queryOne(
                    "SELECT id FROM alerts
                     WHERE device_id = :did
                       AND alert_type = :type
                       AND status = 'open'
                     LIMIT 1",
                    [':did' => $deviceId, ':type' => $threshold['type']]
                );

                if (!$existing) {
                    $this->insert([
                        'device_id'       => $deviceId,
                        'alert_level'     => $threshold['level'],
                        'alert_type'      => $threshold['type'],
                        'message'         => ucfirst(str_replace('_', ' ', $key))
                                            . " à {$current}% sur l'appareil #{$deviceId}",
                        'threshold_value' => $threshold['value'],
                        'current_value'   => $current,
                        'status'          => 'open',
                    ]);
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // Listes utilitaires
    // -----------------------------------------------------------------------

    public function levels(): array
    {
        return ['info', 'warning', 'critique', 'urgent'];
    }

    public function statuses(): array
    {
        return ['open', 'acknowledged', 'resolved'];
    }
}