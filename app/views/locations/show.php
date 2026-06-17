<div class="row g-3">

    <!-- Infos site -->
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                    <?= e($location['site_name']) ?>
                </h6>
                <a href="<?= url('/locations/' . $location['id'] . '/edit') ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i>
                </a>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted">Ville</dt>
                    <dd class="col-8"><?= e($location['city']) ?></dd>

                    <dt class="col-4 text-muted">Pays</dt>
                    <dd class="col-8"><?= e($location['country'] ?? '—') ?></dd>

                    <?php if ($location['latitude'] && $location['longitude']): ?>
                    <dt class="col-4 text-muted">Coordonnées</dt>
                    <dd class="col-8 font-monospace">
                        <?= number_format((float)$location['latitude'], 6) ?>,
                        <?= number_format((float)$location['longitude'], 6) ?>
                    </dd>
                    <?php endif; ?>

                    <dt class="col-4 text-muted">Appareils</dt>
                    <dd class="col-8"><?= count($devices) ?></dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="<?= url('/locations') ?>" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <!-- Appareils du site -->
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-hdd-network me-2 text-primary"></i>
                    Appareils sur ce site
                    <span class="badge bg-secondary ms-1"><?= count($devices) ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($devices)): ?>
                <div class="text-center text-muted py-4">
                    Aucun appareil enregistré sur ce site.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>IP</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($devices as $d): ?>
                        <tr>
                            <td class="small fw-semibold"><?= e($d['name']) ?></td>
                            <td class="small font-monospace text-muted"><?= e($d['ip_address']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= ucfirst(e($d['type'])) ?></span></td>
                            <td><?= device_status_badge($d['status']) ?></td>
                            <td>
                                <a href="<?= url('/devices/' . $d['id']) ?>"
                                   class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <i class="bi bi-eye"></i>
                                </a>
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