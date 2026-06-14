<?php

$isEdit = $incident !== null;
$action = $isEdit
    ? url('/incidents/' . $incident['id'] . '/update')
    : url('/incidents/store');
$val    = fn(string $k, mixed $d = '') => e($incident[$k] ?? $d);
?>

<div class="row justify-content-center">
<div class="col-12 col-xl-8">
<div class="card">

    <div class="card-header">
        <h6 class="card-title mb-0 fw-semibold">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2 text-primary"></i>
            <?= $isEdit ? 'Modifier l\'incident' : 'Créer un incident' ?>
        </h6>
    </div>

    <form method="POST" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>

        <div class="card-body">
            <div class="row g-3">

                <!-- Titre -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Titre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" class="form-control"
                           placeholder="Disque critique sur srv-bak-01"
                           value="<?= $val('title') ?>" required>
                </div>

                <!-- Appareil -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Appareil <span class="text-danger">*</span>
                    </label>
                    <select name="device_id" class="form-select" required>
                        <option value="">— Choisir un appareil —</option>
                        <?php foreach ($devices as $dev): ?>
                        <option value="<?= $dev['id'] ?>"
                            <?= ((int)($incident['device_id'] ?? 0)) === (int)$dev['id'] ? 'selected' : '' ?>>
                            <?= e($dev['name']) ?> (<?= e($dev['ip_address']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Technicien assigné -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Technicien assigné <span class="text-danger">*</span>
                    </label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">— Choisir un technicien —</option>
                        <?php foreach ($techniciens as $tech): ?>
                        <option value="<?= $tech['id'] ?>"
                            <?= ((int)($incident['assigned_to'] ?? 0)) === (int)$tech['id'] ? 'selected' : '' ?>>
                            <?= e($tech['firstname'] . ' ' . $tech['lastname']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Priorité -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Priorité</label>
                    <select name="priority" class="form-select">
                        <?php foreach ($priorities as $p): ?>
                        <option value="<?= e($p) ?>"
                            <?= ($incident['priority'] ?? 'medium') === $p ? 'selected' : '' ?>>
                            <?= ucfirst(e($p)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="5"
                              placeholder="Décrivez l'incident, les symptômes observés et les étapes déjà effectuées…"><?= $val('description') ?></textarea>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="<?= $isEdit ? url('/incidents/' . $incident['id']) : url('/incidents') ?>"
               class="btn btn-secondary">
                <i class="bi bi-x-lg me-1"></i>Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                <?= $isEdit ? 'Enregistrer' : 'Créer l\'incident' ?>
            </button>
        </div>

    </form>
</div>
</div>
</div>