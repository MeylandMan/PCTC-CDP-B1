<?php

$isEdit  = $device !== null;
$action  = $isEdit
    ? url('/devices/' . $device['id'] . '/update')
    : url('/devices/store');
$val     = fn(string $k, mixed $default = '') => e($device[$k] ?? $default);
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-9">
<div class="card">

    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2 text-primary"></i>
            <?= $isEdit ? 'Modifier l\'appareil' : 'Ajouter un appareil' ?>
        </h6>
    </div>

    <form method="POST" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>

        <div class="card-body">
            <div class="row g-3">

                <!-- Nom -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Nom <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control"
                           placeholder="Serveur Web Principal"
                           value="<?= $val('name') ?>" required>
                </div>

                <!-- Hostname -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Hostname <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="hostname" class="form-control"
                           placeholder="srv-web-01"
                           value="<?= $val('hostname') ?>" required>
                </div>

                <!-- IP -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Adresse IP <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-ethernet text-muted"></i>
                        </span>
                        <input type="text" name="ip_address" class="form-control font-monospace"
                               placeholder="192.168.1.1"
                               value="<?= $val('ip_address') ?>" required>
                    </div>
                </div>

                <!-- MAC -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Adresse MAC <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="mac_address" class="form-control font-monospace"
                           placeholder="AA:BB:CC:DD:EE:FF"
                           value="<?= $val('mac_address') ?>"
                           pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$"
                           required>
                </div>

                <!-- Type -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>"
                            <?= ($device['type'] ?? 'other') === $t ? 'selected' : '' ?>>
                            <?= ucfirst(e($t)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Statut -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>"
                            <?= ($device['status'] ?? 'offline') === $s ? 'selected' : '' ?>>
                            <?= ucfirst(e($s)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Localisation -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">
                        Localisation <span class="text-danger">*</span>
                    </label>
                    <select name="location_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($locations as $lid => $lname): ?>
                        <option value="<?= (int)$lid ?>"
                            <?= ((int)($device['location_id'] ?? 0)) === (int)$lid ? 'selected' : '' ?>>
                            <?= e($lname) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- OS -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Version OS <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="os_version" class="form-control"
                           placeholder="Ubuntu Server 22.04 LTS"
                           value="<?= $val('os_version') ?>" required>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="<?= $isEdit ? url('/devices/' . $device['id']) : url('/devices') ?>"
               class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                <?= $isEdit ? 'Enregistrer les modifications' : 'Ajouter l\'appareil' ?>
            </button>
        </div>

    </form>
</div>
</div>
</div>