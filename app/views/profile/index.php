<div class="row g-3">

    <!-- Colonne gauche : avatar + infos -->
    <div class="col-12 col-xl-4">

        <!-- Carte avatar -->
        <div class="card mb-3">
            <div class="card-body text-center">
                <?php
                $avatar   = $user['avatar'] ?? null;
                $initials = strtoupper(
                    substr($user['firstname'], 0, 1) .
                    substr($user['lastname'],  0, 1)
                );
                ?>

                <?php if ($avatar): ?>
                    <img src="<?= url($avatar) ?>" alt="Avatar"
                         class="rounded-circle mb-3"
                         style="width:90px;height:90px;object-fit:cover;">
                <?php else: ?>
                    <div class="mx-auto mb-3 rounded-circle d-flex align-items-center
                                justify-content-center text-white fw-bold"
                         style="width:90px;height:90px;font-size:1.8rem;
                                background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                        <?= e($initials) ?>
                    </div>
                <?php endif; ?>

                <h5 class="mb-1"><?= e($user['firstname'] . ' ' . $user['lastname']) ?></h5>
                <span class="badge bg-primary mb-3">
                    <?= e(str_replace('_', ' ', $user['role_name'])) ?>
                </span>

                <!-- Upload avatar -->
                <form method="POST" action="<?= url('/profile/avatar') ?>"
                      enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="file" name="avatar" id="avatarInput"
                               class="d-none" accept="image/jpeg,image/png,image/webp"
                               onchange="this.form.submit()">
                        <label for="avatarInput" class="btn btn-sm btn-outline-secondary w-100"
                               style="cursor:pointer;">
                            <i class="bi bi-camera me-1"></i>Changer la photo
                        </label>
                    </div>
                    <div class="text-muted small">JPG, PNG, WebP · max 2 Mo</div>
                </form>
            </div>
        </div>

        <!-- Infos compte -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-info-circle me-2 text-muted"></i>Informations compte
                </h6>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Email</span>
                    <span><?= e($user['email']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Dernière connexion</span>
                    <span><?= $user['last_login'] ? time_ago($user['last_login']) : 'Jamais' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Dernière IP</span>
                    <span class="font-monospace"><?= e($user['last_ip'] ?? '—') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Membre depuis</span>
                    <span><?= format_date($user['created_at'], 'd/m/Y') ?></span>
                </li>
            </ul>
        </div>

    </div>

    <!-- Colonne droite : formulaires -->
    <div class="col-12 col-xl-8">

        <!-- Modifier les infos -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-person-gear me-2 text-primary"></i>
                    Informations personnelles
                </h6>
            </div>
            <form method="POST" action="<?= url('/profile') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">
                                Prénom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="firstname" class="form-control"
                                   value="<?= e($user['firstname']) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="lastname" class="form-control"
                                   value="<?= e($user['lastname']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" name="phone" class="form-control"
                                   placeholder="+241 77 00 00 00"
                                   value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Changer mot de passe -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-lock me-2 text-warning"></i>
                    Changer le mot de passe
                </h6>
            </div>
            <form method="POST" action="<?= url('/profile/password') ?>" novalidate>
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold">Mot de passe actuel</label>
                            <input type="password" name="current_password"
                                   class="form-control" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nouveau mot de passe</label>
                            <input type="password" name="new_password"
                                   id="newPass" class="form-control"
                                   minlength="8" required>
                            <div class="progress mt-1" style="height:3px;">
                                <div class="progress-bar" id="strengthBar" style="width:0%"></div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Confirmer</label>
                            <input type="password" name="confirm_password"
                                   id="confirmPass" class="form-control" required>
                            <small id="matchMsg" class="text-muted"></small>
                        </div>

                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-lock me-1"></i>Changer le mot de passe
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
// Indicateur de force du mot de passe
document.getElementById('newPass').addEventListener('input', function () {
    const v = this.value;
    const bar = document.getElementById('strengthBar');
    let score = 0;
    if (v.length >= 8)          score++;
    if (/[A-Z]/.test(v))        score++;
    if (/[0-9]/.test(v))        score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const map = [
        ['25%',  'bg-danger'],
        ['50%',  'bg-warning'],
        ['75%',  'bg-info'],
        ['100%', 'bg-success'],
    ];
    const lvl = map[Math.max(0, score - 1)] || map[0];
    bar.style.width = v.length ? lvl[0] : '0%';
    bar.className   = 'progress-bar ' + (v.length ? lvl[1] : '');
});

// Vérification correspondance
document.getElementById('confirmPass').addEventListener('input', function () {
    const match = this.value === document.getElementById('newPass').value;
    const msg   = document.getElementById('matchMsg');
    msg.textContent = this.value
        ? (match ? '✓ Correspondance OK' : '✗ Ne correspond pas')
        : '';
    msg.className = 'small ' + (match ? 'text-success' : 'text-danger');
});
</script>