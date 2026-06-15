<?php

require_once ROOT_PATH . '/core/Model.php';

class NotificationModel extends Model
{
    protected string $table      = 'notifications';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id', 'title', 'content', 'channel', 'is_read',
    ];

    // -----------------------------------------------------------------------
    // Lecture
    // -----------------------------------------------------------------------

    /**
     * Notifications paginées d'un utilisateur, les plus récentes en premier.
     */
    public function forUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $data = $this->query(
            "SELECT * FROM notifications
             WHERE user_id = :uid
             ORDER BY sent_at DESC
             LIMIT :limit OFFSET :offset",
            [':uid' => $userId, ':limit' => $perPage, ':offset' => $offset]
        );

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM notifications WHERE user_id = :uid",
            [':uid' => $userId]
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
     * Dernières N notifications non lues pour l'aperçu dans la navbar.
     */
    public function recentForUser(int $userId, int $limit = 5): array
    {
        return $this->query(
            "SELECT * FROM notifications
             WHERE user_id = :uid
             ORDER BY sent_at DESC
             LIMIT :lim",
            [':uid' => $userId, ':lim' => $limit]
        );
    }

    /**
     * Nombre de notifications non lues.
     */
    public function unreadCount(int $userId): int
    {
        return (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM notifications
             WHERE user_id = :uid AND is_read = 0",
            [':uid' => $userId]
        )['n'];
    }

    // -----------------------------------------------------------------------
    // Écriture
    // -----------------------------------------------------------------------

    /**
     * Marque une notification comme lue.
     */
    public function markRead(int $id, int $userId): void
    {
        $this->execute(
            "UPDATE notifications SET is_read = 1
             WHERE id = :id AND user_id = :uid",
            [':id' => $id, ':uid' => $userId]
        );
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues.
     */
    public function markAllRead(int $userId): void
    {
        $this->execute(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :uid",
            [':uid' => $userId]
        );
    }

    /**
     * Supprime une notification.
     */
    public function deleteForUser(int $id, int $userId): void
    {
        $this->execute(
            "DELETE FROM notifications WHERE id = :id AND user_id = :uid",
            [':id' => $id, ':uid' => $userId]
        );
    }

    /**
     * Envoie une notification à un utilisateur (insert en base).
     * Le canal 'dashboard' est toujours créé ; email/sms/push
     * déclencheraient des services tiers en production.
     */
    public function send(
        int    $userId,
        string $title,
        string $content,
        string $channel = 'dashboard'
    ): void {
        $this->insert([
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
            'channel' => $channel,
            'is_read' => 0,
        ]);
    }
}