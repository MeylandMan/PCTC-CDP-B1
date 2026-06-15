<?php

require_once ROOT_PATH . '/core/model.php';

class DeviceModel extends Model
{
    protected string $table      = 'devices';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name', 'hostname', 'ip_address', 'mac_address',
        'type', 'location_id', 'status', 'os_version',
    ];

    // -----------------------------------------------------------------------
    // Lecture
    // -----------------------------------------------------------------------

    public function listPaginated(
        int    $page    = 1,
        int    $perPage = 15,
        string $status  = '',
        string $type    = '',
        string $search  = ''
    ): array {
        $where    = ['1 = 1'];
        $bindings = [];

        if ($status !== '') {
            $where[]          = 'd.status = :status';
            $bindings[':status'] = $status;
        }
        if ($type !== '') {
            $where[]        = 'd.type = :type';
            $bindings[':type'] = $type;
        }
        if ($search !== '') {
            $where[]          = '(d.name LIKE :s OR d.hostname LIKE :s OR d.ip_address LIKE :s)';
            $bindings[':s']   = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $data = $this->query(
            "SELECT d.*, l.site_name, l.city
             FROM devices d
             JOIN locations l ON l.id = d.location_id
             WHERE {$whereClause}
             ORDER BY d.name ASC
             LIMIT :limit OFFSET :offset",
            array_merge($bindings, [':limit' => $perPage, ':offset' => $offset])
        );

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM devices d WHERE {$whereClause}",
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

    public function findWithLocation(int $id): array|false
    {
        return $this->queryOne(
            "SELECT d.*, l.site_name, l.city, l.country, l.latitude, l.longitude
             FROM devices d
             JOIN locations l ON l.id = d.location_id
             WHERE d.id = :id",
            [':id' => $id]
        );
    }

    public function latestMetrics(int $deviceId, int $limit = 30): array
    {
        $rows = $this->query(
            "SELECT cpu_usage, ram_usage, disk_usage,
                    network_in, network_out, temperature,
                    DATE_FORMAT(collected_at, '%H:%i') AS label
             FROM device_metrics
             WHERE device_id = :id
             ORDER BY collected_at DESC
             LIMIT :lim",
            [':id' => $deviceId, ':lim' => $limit]
        );

        return array_reverse($rows);
    }

    public function lastMetric(int $deviceId): array|false
    {
        return $this->queryOne(
            "SELECT * FROM device_metrics
             WHERE device_id = :id
             ORDER BY collected_at DESC
             LIMIT 1",
            [':id' => $deviceId]
        );
    }

    public function openAlerts(int $deviceId): array
    {
        return $this->query(
            "SELECT * FROM alerts
             WHERE device_id = :id AND status = 'open'
             ORDER BY FIELD(alert_level,'urgent','critique','warning','info'),
                      created_at DESC",
            [':id' => $deviceId]
        );
    }

    public function activeIncidents(int $deviceId): array
    {
        return $this->query(
            "SELECT i.*, CONCAT(u.firstname,' ',u.lastname) AS assigned_to
             FROM incidents i
             JOIN users u ON u.id = i.assigned_to
             WHERE i.device_id = :id
               AND i.status NOT IN ('resolved','closed')
             ORDER BY i.opened_at DESC",
            [':id' => $deviceId]
        );
    }

    // -----------------------------------------------------------------------
    // Écriture
    // -----------------------------------------------------------------------

    public function updateStatus(int $id, string $status): void
    {
        $this->execute(
            "UPDATE devices SET status = :status, last_check = NOW() WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }

    public function insertMetric(int $deviceId, array $metrics): void
    {
        $this->execute(
            "INSERT INTO device_metrics
                (device_id, cpu_usage, ram_usage, disk_usage,
                 network_in, network_out, temperature, collected_at)
             VALUES
                (:device_id, :cpu, :ram, :disk, :net_in, :net_out, :temp, NOW())",
            [
                ':device_id' => $deviceId,
                ':cpu'       => $metrics['cpu_usage']   ?? null,
                ':ram'       => $metrics['ram_usage']   ?? null,
                ':disk'      => $metrics['disk_usage']  ?? null,
                ':net_in'    => $metrics['network_in']  ?? null,
                ':net_out'   => $metrics['network_out'] ?? null,
                ':temp'      => $metrics['temperature'] ?? null,
            ]
        );
    }

    // -----------------------------------------------------------------------
    // Listes utilitaires
    // -----------------------------------------------------------------------

    public function types(): array
    {
        return ['server','router','switch','firewall','printer','iot','workstation','other'];
    }

    public function statuses(): array
    {
        return ['online','offline','warning','maintenance'];
    }
}