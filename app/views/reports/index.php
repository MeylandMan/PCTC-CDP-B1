<div class="row g-3">

    <!-- Générateur -->
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-file-earmark-plus me-2 text-primary"></i>
                    Générer un rapport
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/reports/generate') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type de rapport</label>
                        <select name="report_type" class="form-select">
                            <option value="daily">Quotidien (dernières 24h)</option>
                            <option value="weekly">Hebdomadaire (7 jours)</option>
                            <option value="monthly">Mensuel (30 jours)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Format</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="format" value="csv" id="fmtCsv" checked>
                                <label class="form-check-label" for="fmtCsv">
                                    <i class="bi bi-filetype-csv text-success me-1"></i>CSV
                                </label>
                            </div>
                        </div>
                        <div class="text-muted small mt-1">
                            Le CSV s'ouvre directement dans Excel.
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download me-2"></i>Générer et télécharger
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Historique -->
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    Rapports générés
                    <span class="badge bg-secondary ms-1"><?= count($reports) ?></span>
                </h6>
            </div>

            <div class="card-body p-0">
                <?php if (empty($reports)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-file-earmark-bar-graph fs-1 d-block mb-2 opacity-50"></i>
                    Aucun rapport généré pour l'instant.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Généré par</th>
                                <th>Date</th>
                                <th>Fichier</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>
                                <?php
                                $typeLabel = match($r['report_type']) {
                                    'daily'   => ['Quotidien',    'info'],
                                    'weekly'  => ['Hebdomadaire', 'warning'],
                                    'monthly' => ['Mensuel',      'primary'],
                                    default   => [$r['report_type'], 'secondary'],
                                };
                                ?>
                                <span class="badge bg-<?= $typeLabel[1] ?>">
                                    <?= $typeLabel[0] ?>
                                </span>
                            </td>
                            <td class="small"><?= e($r['generated_by_name']) ?></td>
                            <td class="small text-muted"><?= format_date($r['generated_at']) ?></td>
                            <td class="small font-monospace text-muted">
                                <?= e(basename($r['file_path'])) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= url('/reports/' . $r['id'] . '/download') ?>"
                                       class="btn btn-sm btn-outline-success py-0 px-2"
                                       title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <form method="POST"
                                          action="<?= url('/reports/' . $r['id'] . '/delete') ?>"
                                          onsubmit="return confirm('Supprimer ce rapport ?')">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger py-0 px-2"
                                                title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>