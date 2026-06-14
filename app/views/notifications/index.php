<?php

$channelIcon = [
    'email'     => 'bi-envelope',
    'sms'       => 'bi-phone',
    'push'      => 'bi-bell',
    'dashboard' => 'bi-display',
];
?>

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

<!-- Barre d'actions -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <span class="fw-semibold">
            <?php if ($unread > 0): ?>
                <span class="badge bg-danger me-1"><?= $unread ?></span>
                notification<?= $unread > 1 ? 's' : '' ?> non lue<?= $unread > 1 ? 's' : '' ?>
            <?php else: ?>
                <i class="bi bi-check-circle text-success me-1"></i>
                Tout est lu
            <?php endif; ?>
        </span>
    </div>

    <?php if ($unread > 0): ?>
    <button class="btn btn-sm btn-outline-secondary" id="markAllReadBtn">
        <i class="bi bi-check-all me-1"></i>Tout marquer comme lu
    </button>
    <?php endif; ?>
</div>

<!-- Liste des notifications -->
<div class="card">
    <div class="card-body p-0">

        <?php if (empty($paginator['data'])): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
            Aucune notification pour le moment.
        </div>
        <?php else: ?>

        <ul class="list-group list-group-flush" id="notifList">
            <?php foreach ($paginator['data'] as $n): ?>
            <li class="list-group-item py-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>"
                id="notif-<?= $n['id'] ?>">
                <div class="d-flex align-items-start gap-3">

                    <!-- Icône canal -->
                    <div class="flex-shrink-0 mt-1">
                        <span class="badge rounded-circle p-2
                                     <?= !$n['is_read'] ? 'bg-primary' : 'bg-secondary' ?>"
                              title="<?= e($n['channel']) ?>">
                            <i class="bi <?= $channelIcon[$n['channel']] ?? 'bi-bell' ?>"></i>
                        </span>
                    </div>

                    <!-- Contenu -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold <?= !$n['is_read'] ? '' : 'text-muted' ?>">
                                    <?= e($n['title']) ?>
                                </div>
                                <?php if ($n['content']): ?>
                                <div class="small text-muted mt-1">
                                    <?= e(truncate($n['content'], 120)) ?>
                                </div>
                                <?php endif; ?>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= time_ago($n['sent_at']) ?>
                                    · <?= ucfirst(e($n['channel'])) ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-1 flex-shrink-0">
                                <?php if (!$n['is_read']): ?>
                                <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                        title="Marquer comme lu"
                                        onclick="markRead(<?= $n['id'] ?>)">
                                    <i class="bi bi-check"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                        title="Supprimer"
                                        onclick="deleteNotif(<?= $n['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php endif; ?>
    </div>

    <?php if ($paginator['lastPage'] > 1): ?>
    <div class="card-footer">
        <?= pagination($paginator, url('/notifications')) ?>
    </div>
    <?php endif; ?>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function markRead(id) {
    fetch(`<?= url('/notifications/') ?>${id}/read`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrf),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const li = document.getElementById(`notif-${id}`);
        if (li) {
            li.classList.remove('bg-light');
            // retire le bouton "marquer lu"
            li.querySelector('[title="Marquer comme lu"]')?.remove();
        }
    });
}

function deleteNotif(id) {
    if (!confirm('Supprimer cette notification ?')) return;

    fetch(`<?= url('/notifications/') ?>${id}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrf),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`notif-${id}`)?.remove();
        }
    });
}

document.getElementById('markAllReadBtn')?.addEventListener('click', function () {
    fetch('<?= url('/notifications/read-all') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrf),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) window.location.reload();
    });
});
</script>