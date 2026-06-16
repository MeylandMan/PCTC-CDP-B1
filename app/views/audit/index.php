<?php

$actionColors = [
    'LOGIN_SUCCESS'  => 'success',
    'LOGIN_FAILED'   => 'danger',
    'LOGOUT'         => 'secondary',
    'CREATE_'        => 'primary',
    'UPDATE_'        => 'warning',
    'DELETE_'        => 'danger',
    'EXPORT_'        => 'info',
];

function actionBadge(string $action): string {
    $color = 'secondary';
    if (str_contains($action, 'LOGIN_SUCCESS')) $color = 'success';
    elseif (str_contains($action, 'FAILED'))    $color = 'danger';
    elseif (str_starts_with($action, 'CREATE')) $color = 'primary';
    elseif (str_starts_with($action, 'UPDATE')) $color = 'warning';
    elseif (str_starts_with($action, 'DELETE')) $color = 'danger';
    elseif (str_starts_with($action, 'EXPORT')) $color = 'info';
    return '<span class="badge bg-' . $color . '">' . e($action) . '</span>';
}
?>

<!-- Filtres -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= url('/audit') ?>" class="row g-2 align-items-end">

            <div class="col-12 col-sm-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Action, IP…"
                           value="<?= e($filters['search']) ?>">
                </div>
            </div>

            <div class="col-6 col-sm-3">
                <select name="module" class="form-select form-select-sm">
                    <option value="">Tous les modules</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= e($m) ?>"
                        <?= $filters['module'] === $m ? 'selected' : '' ?>>
                        <?= e(ucfirst($m)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-sm-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Filtrer
                </button>
                <a href="<?= url('/audit') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
                <a href="<?= url('/audit/export') ?>" class="btn btn-outline-success btn-sm ms-auto">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-journal-text me-2 text-primary"></i>
            Journal d'audit
            <span class="badge bg-secondary ms-1"><?= $paginator['total'] ?></span>
        </h6>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($paginator['data'])): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Aucun log trouvé.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginator['data'] as $log): ?>
                    <tr>
                        <td class="text-muted small"><?= $log['id'] ?></td>
                        <td class="small fw-semibold"><?= e($log['user_name']) ?></td>
                        <td><?= actionBadge($log['action']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                <?= e($log['module']) ?>
                            </span>
                        </td>
                        <td class="small font-monospace text-muted"><?= e($log['ip_address']) ?></td>
                        <td class="small text-muted">
                            <?= format_date($log['created_at']) ?>
                            <div style="font-size:.7rem;"><?= time_ago($log['created_at']) ?></div>
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
        <?= pagination($paginator, url('/audit')) ?>
    </div>
    <?php endif; ?>
</div>