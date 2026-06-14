<!-- Barre de filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/devices') ?>"
              class="row g-2 align-items-end">

            <!-- Recherche -->
            <div class="col-12 col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom, hostname, IP…"
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>

            <!-- Filtre statut -->
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst(e($s)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtre type -->
            <div class="col-6 col-sm-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>>
                        <?= ucfirst(e($t)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Boutons -->
            <div class="col-12 col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/devices') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Tableau des appareils -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-hdd-network me-2 text-primary"></i>
            Appareils
            <span class="badge bg-secondary ms-1"><?= $paginator['total'] ?></span>
        </h6>
        <a href="<?= url('/devices/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Nom / Hostname</th>
                        <th>IP</th>
                        <th>Type</th>
                        <th>Localisation</th>
                        <th>Statut</th>
                        <th>OS</th>
                        <th>Dernière vérif.</th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($paginator['data'])): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucun appareil trouvé.
                            <a href="<?= url('/devices/create') ?>">En ajouter un ?</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginator['data'] as $d): ?>
                    <tr>
                        <td class="text-muted small"><?= $d['id'] ?></td>
                        <td>
                            <a href="<?= url('/devices/' . $d['id']) ?>"
                               class="fw-semibold text-decoration-none">
                                <?= e($d['name']) ?>
                            </a>
                            <div class="text-muted" style="font-size:.75rem;">
                                <?= e($d['hostname']) ?>
                            </div>
                        </td>
                        <td class="font-monospace small"><?= e($d['ip_address']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= e(ucfirst($d['type'])) ?>
                            </span>
                        </td>
                        <td class="small">
                            <i class="bi bi-geo-alt text-muted me-1"></i>
                            <?= e($d['site_name']) ?>
                            <span class="text-muted">(<?= e($d['city']) ?>)</span>
                        </td>
                        <td><?= device_status_badge($d['status']) ?></td>
                        <td class="small text-muted"><?= e($d['os_version']) ?></td>
                        <td class="small text-muted"><?= time_ago($d['last_check']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <!-- Voir -->
                                <a href="<?= url('/devices/' . $d['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Modifier -->
                                <a href="<?= url('/devices/' . $d['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Supprimer -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger py-0 px-2"
                                        title="Supprimer"
                                        onclick="confirmDelete(<?= $d['id'] ?>, '<?= e(addslashes($d['name'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($paginator['lastPage'] > 1): ?>
    <div class="card-footer">
        <?= pagination($paginator, url('/devices')) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal confirmation suppression -->
<form id="deleteForm" method="POST" action="">
    <?= csrf_field() ?>
    <?= method_field('POST') ?>
</form>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>Confirmer la suppression
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <p class="mb-0 small">
                    Supprimer <strong id="deleteDeviceName"></strong> ?
                    Cette action supprimera aussi toutes les métriques, alertes et incidents associés.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-secondary"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-sm btn-danger"
                        id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

function confirmDelete(id, name) {
    document.getElementById('deleteDeviceName').textContent = name;
    document.getElementById('confirmDeleteBtn').onclick = function () {
        const form = document.getElementById('deleteForm');
        form.action = '<?= url('/devices/') ?>' + id + '/delete';
        form.submit();
    };
    deleteModal.show();
}
</script>