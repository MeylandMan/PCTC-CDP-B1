<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-geo-alt me-2 text-primary"></i>
            Localisations
            <span class="badge bg-secondary ms-1"><?= count($locations) ?></span>
        </h6>
        <a href="<?= url('/locations/create') ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Ajouter un site
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Site</th>
                        <th>Ville</th>
                        <th>Pays</th>
                        <th>Coordonnées</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($locations)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Aucun site enregistré.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($locations as $loc): ?>
                    <tr>
                        <td class="text-muted small"><?= $loc['id'] ?></td>
                        <td>
                            <a href="<?= url('/locations/' . $loc['id']) ?>"
                               class="fw-semibold text-decoration-none small">
                                <i class="bi bi-building me-1 text-muted"></i>
                                <?= e($loc['site_name']) ?>
                            </a>
                        </td>
                        <td class="small"><?= e($loc['city']) ?></td>
                        <td class="small text-muted"><?= e($loc['country'] ?? '—') ?></td>
                        <td class="small font-monospace text-muted">
                            <?php if ($loc['latitude'] && $loc['longitude']): ?>
                                <?= number_format((float)$loc['latitude'], 4) ?>,
                                <?= number_format((float)$loc['longitude'], 4) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= url('/locations/' . $loc['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/locations/' . $loc['id'] . '/edit') ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      action="<?= url('/locations/' . $loc['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Supprimer ce site ? Les appareils associés seront aussi supprimés.')"
                                      class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger py-0 px-2" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>