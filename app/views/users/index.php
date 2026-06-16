<!-- Filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/users') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom, prénom, email…"
                           value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-people me-2 text-primary"></i>
            Utilisateurs
            <span class="badge bg-secondary ms-1"><?= $paginator['total'] ?></span>
        </h6>
        <a href="<?= url('/users/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($paginator['data'])): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucun utilisateur trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginator['data'] as $u): ?>
                    <tr id="user-row-<?= $u['id'] ?>">
                        <td class="text-muted small"><?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Initiales -->
                                <div style="
                                    width:34px;height:34px;border-radius:50%;
                                    background:linear-gradient(135deg,#4f46e5,#7c3aed);
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0;">
                                    <?= strtoupper(substr($u['firstname'],0,1) . substr($u['lastname'],0,1)) ?>
                                </div>
                                <div>
                                    <a href="<?= url('/users/' . $u['id']) ?>"
                                       class="fw-semibold text-decoration-none small">
                                        <?= e($u['firstname'] . ' ' . $u['lastname']) ?>
                                    </a>
                                    <?php if ($u['phone']): ?>
                                    <div class="text-muted" style="font-size:.72rem;">
                                        <?= e($u['phone']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= e($u['email']) ?></td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <?= e(str_replace('_', ' ', $u['role_name'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="form-check form-switch mb-0"
                                 title="<?= $u['is_active'] ? 'Actif — cliquer pour désactiver' : 'Inactif — cliquer pour activer' ?>">
                                <input class="form-check-input" type="checkbox"
                                       <?= $u['is_active'] ? 'checked' : '' ?>
                                       onchange="toggleActive(<?= $u['id'] ?>, this)"
                                       <?= $u['id'] === ($currentUser['id'] ?? 0) ? 'disabled' : '' ?>>
                            </div>
                        </td>
                        <td class="small text-muted">
                            <?= $u['last_login'] ? time_ago($u['last_login']) : 'Jamais' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/users/' . $u['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/users/' . $u['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($u['id'] !== ($currentUser['id'] ?? 0)): ?>
                                <button class="btn btn-sm btn-outline-danger py-0 px-2"
                                        title="Supprimer"
                                        onclick="confirmDelete(<?= $u['id'] ?>, '<?= e(addslashes($u['firstname'] . ' ' . $u['lastname'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($paginator['lastPage'] > 1): ?>
    <div class="card-footer">
        <?= pagination($paginator, url('/users')) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Formulaire suppression -->
<form id="deleteForm" method="POST" action="">
    <?= csrf_field() ?>
</form>

<!-- Modal suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmer
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 small">
                Supprimer <strong id="deleteUserName"></strong> ? Cette action est irréversible.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">

<script>
const csrf       = document.querySelector('meta[name="csrf-token"]').content;
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

function confirmDelete(id, name) {
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('confirmDeleteBtn').onclick = () => {
        const form = document.getElementById('deleteForm');
        form.action = '<?= url('/users/') ?>' + id + '/delete';
        form.submit();
    };
    deleteModal.show();
}

function toggleActive(id, checkbox) {
    fetch('<?= url('/users/') ?>' + id + '/toggle-active', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '_csrf_token=' + encodeURIComponent(csrf),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            checkbox.checked = !checkbox.checked; // annule le toggle visuel
            alert(data.message);
        }
    })
    .catch(() => { checkbox.checked = !checkbox.checked; });
}
</script>