<?php

require_once ROOT_PATH . '/core/model.php';

class IncidentModel extends Model
{
    protected string $table      = 'incidents';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'device_id', 'assigned_to', 'priority',
        'status', 'title', 'description', 'opened_at',
    ];

    // -----------------------------------------------------------------------
    // Lecture
    // -----------------------------------------------------------------------

    /**
     * Liste paginée avec appareil et technicien assigné.
     */
    public function listPaginated(
        int    $page     = 1,
        int    $perPage  = 15,
        string $status   = '',
        string $priority = '',
        int    $userId   = 0,
        string $search   = ''
    ): array {
        $where    = ['1 = 1'];
        $bindings = [];

        if ($status !== '') {
            $where[]            = 'i.status = :status';
            $bindings[':status'] = $status;
        }
        if ($priority !== '') {
            $where[]              = 'i.priority = :priority';
            $bindings[':priority'] = $priority;
        }
        // Filtre "mes incidents" pour le technicien connecté
        if ($userId > 0) {
            $where[]           = 'i.assigned_to = :uid';
            $bindings[':uid']  = $userId;
        }
        if ($search !== '') {
            $where[]        = '(i.title LIKE :s OR d.name LIKE :s)';
            $bindings[':s'] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        $offset      = ($page - 1) * $perPage;

        $data = $this->query(
            "SELECT i.*,
                    d.name                              AS device_name,
                    CONCAT(u.firstname,' ',u.lastname)  AS assigned_to_name
             FROM incidents i
             JOIN devices d ON d.id = i.device_id
             JOIN users   u ON u.id = i.assigned_to
             WHERE {$whereClause}
             ORDER BY
                FIELD(i.priority,'critique','high','medium','low'),
                i.opened_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($bindings, [':limit' => $perPage, ':offset' => $offset])
        );

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS n
             FROM incidents i
             JOIN devices d ON d.id = i.device_id
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
     * Détail avec appareil et technicien.
     */
    public function findWithDetails(int $id): array|false
    {
        return $this->queryOne(
            "SELECT i.*,
                    d.name                              AS device_name,
                    d.ip_address,
                    CONCAT(u.firstname,' ',u.lastname)  AS assigned_to_name,
                    u.email                             AS assigned_to_email
             FROM incidents i
             JOIN devices d ON d.id = i.device_id
             JOIN users   u ON u.id = i.assigned_to
             WHERE i.id = :id",
            [':id' => $id]
        );
    }

    /**
     * Compteurs par statut.
     */
    public function countsByStatus(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS n FROM incidents GROUP BY status"
        );

        $result = [
            'open' => 0, 'incoming' => 0, 'waiting' => 0,
            'resolved' => 0, 'closed' => 0,
        ];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['n'];
        }
        return $result;
    }

    // -----------------------------------------------------------------------
    // Écriture
    // -----------------------------------------------------------------------

    /**
     * Crée un incident et retourne son ID.
     */
    public function createIncident(array $data): int
    {
        $this->execute(
            "INSERT INTO incidents
                (device_id, assigned_to, priority, status, title, description, opened_at)
             VALUES
                (:device_id, :assigned_to, :priority, 'open', :title, :description, NOW())",
            [
                ':device_id'   => $data['device_id'],
                ':assigned_to' => $data['assigned_to'],
                ':priority'    => $data['priority'],
                ':title'       => $data['title'],
                ':description' => $data['description'] ?? null,
            ]
        );

        return (int) Database::getInstance()->lastInsertId();
    }

    /**
     * Met à jour le statut d'un incident.
     * Renseigne resolved_at automatiquement si résolu ou fermé.
     */
    public function updateStatus(int $id, string $status): void
    {
        $resolvedAt = in_array($status, ['resolved', 'closed']) ? ', resolved_at = NOW()' : '';

        $this->execute(
            "UPDATE incidents SET status = :status{$resolvedAt} WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );
    }

    /**
     * Réassigne un incident à un autre technicien.
     */
    public function assign(int $id, int $userId): void
    {
        $this->execute(
            "UPDATE incidents SET assigned_to = :uid WHERE id = :id",
            [':uid' => $userId, ':id' => $id]
        );
    }

    // -----------------------------------------------------------------------
    // Utilitaires
    // -----------------------------------------------------------------------

    public function priorities(): array
    {
        return ['low', 'medium', 'high', 'critique'];
    }

    public function statuses(): array
    {
        return ['open', 'incoming', 'waiting', 'resolved', 'closed'];
    }

    /** Prochain statut possible dans le cycle de vie. */
    public function nextStatuses(string $current): array
    {
        return match($current) {
            'open'     => ['incoming', 'waiting', 'resolved', 'closed'],
            'incoming' => ['waiting', 'resolved', 'closed'],
            'waiting'  => ['incoming', 'resolved', 'closed'],
            'resolved' => ['closed'],
            default    => [],
        };
    }
}