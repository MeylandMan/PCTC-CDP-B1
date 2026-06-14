<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/notification-model.php';

class NotificationController extends Controller
{
    private NotificationModel $notifs;

    public function __construct()
    {
        $this->notifs = new NotificationModel();
    }

    // -----------------------------------------------------------------------
    // GET /notifications
    // Supporte aussi AJAX : ?ajax=1&limit=5
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requireAuth();

        $userId = (int) $this->currentUser()['id'];

        // Aperçu AJAX pour la cloche du header
        if ($this->isAjax() && $this->query('ajax') === '1') {
            $limit = min((int) $this->query('limit', 5), 20);
            $items = $this->notifs->recentForUser($userId, $limit);

            $this->json([
                'notifications' => array_map(fn($n) => [
                    'id'      => $n['id'],
                    'title'   => $n['title'],
                    'is_read' => (bool) $n['is_read'],
                    'sent_at' => time_ago($n['sent_at']),
                    'channel' => $n['channel'],
                ], $items),
            ]);
            return;
        }

        $page      = (int) $this->query('page', 1);
        $paginator = $this->notifs->forUser($userId, $page, 20);
        $unread    = $this->notifs->unreadCount($userId);

        $this->view('notifications/index', [
            'title'         => 'Notifications',
            'breadcrumbs'   => ['Notifications' => null],
            'paginator'     => $paginator,
            'unread'        => $unread,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /notifications/:id/read
    // -----------------------------------------------------------------------
    public function markRead(string $id): void
    {
        $this->requireAuth();

        $userId = (int) $this->currentUser()['id'];
        $this->notifs->markRead((int) $id, $userId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $this->redirect('/notifications');
        }
    }

    // -----------------------------------------------------------------------
    // POST /notifications/read-all
    // -----------------------------------------------------------------------
    public function markAllRead(): void
    {
        $this->requireAuth();

        $userId = (int) $this->currentUser()['id'];
        $this->notifs->markAllRead($userId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $this->flash('success', 'Toutes les notifications ont été marquées comme lues.');
            $this->redirect('/notifications');
        }
    }

    // -----------------------------------------------------------------------
    // DELETE /notifications/:id
    // -----------------------------------------------------------------------
    public function destroy(string $id): void
    {
        $this->requireAuth();

        $userId = (int) $this->currentUser()['id'];
        $this->notifs->deleteForUser((int) $id, $userId);

        if ($this->isAjax()) {
            $this->json(['success' => true]);
        } else {
            $this->flash('success', 'Notification supprimée.');
            $this->redirect('/notifications');
        }
    }

    // -----------------------------------------------------------------------
    // GET /notifications/unread-count  (AJAX — navbar badge)
    // -----------------------------------------------------------------------
    public function unreadCount(): void
    {
        $this->requireAuth();

        if (!$this->isAjax()) {
            $this->abort(403);
        }

        $userId = (int) $this->currentUser()['id'];
        $this->json(['count' => $this->notifs->unreadCount($userId)]);
    }
}