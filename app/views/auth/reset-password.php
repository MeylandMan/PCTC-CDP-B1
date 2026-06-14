<div class="card auth-card mx-auto">
    <div class="card-body p-4 p-sm-5">

        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h5 class="fw-bold mb-1">Nouveau mot de passe</h5>
            <p class="text-muted small">Choisissez un mot de passe d'au moins 8 caractères.</p>
        </div>

        <form method="POST" action="<?= url('/auth/reset-password/' . e($token ?? '')) ?>" novalidate>
            <?= csrf_field() ?>

            <!-- Nouveau mot de passe -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Nouveau mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input
                        type="password"
                        class="form-control border-start-0 border-end-0 ps-0"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        minlength="8"
                        autofocus
                    >
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="toggleVis('password', 'eye1')">
                        <i class="bi bi-eye text-muted" id="eye1"></i>
                    </button>
                </div>

                <!-- Indicateur de force -->
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar" id="strengthBar" style="width:0%; transition: width .3s;"></div>
                </div>
                <small id="strengthLabel" class="text-muted"></small>
            </div>

            <!-- Confirmation -->
            <div class="mb-4">
                <label for="password_confirm" class="form-label fw-semibold">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock-fill text-muted"></i>
                    </span>
                    <input
                        type="password"
                        class="form-control border-start-0 border-end-0 ps-0"
                        id="password_confirm"
                        name="password_confirm"
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" class="input-group-text bg-light border-start-0" onclick="toggleVis('password_confirm', 'eye2')">
                        <i class="bi bi-eye text-muted" id="eye2"></i>
                    </button>
                </div>
                <small id="matchMsg" class="text-muted"></small>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Enregistrer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleVis(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash text-muted' : 'bi bi-eye text-muted';
}

// Indicateur de force
document.getElementById('password').addEventListener('input', function () {
    const v   = this.value;
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');
    let score = 0;

    if (v.length >= 8)               score++;
    if (/[A-Z]/.test(v))             score++;
    if (/[0-9]/.test(v))             score++;
    if (/[^A-Za-z0-9]/.test(v))      score++;

    const levels = [
        { pct: '25%',  cls: 'bg-danger',  txt: 'Très faible' },
        { pct: '50%',  cls: 'bg-warning', txt: 'Faible'      },
        { pct: '75%',  cls: 'bg-info',    txt: 'Moyen'       },
        { pct: '100%', cls: 'bg-success', txt: 'Fort'        },
    ];

    const lvl = levels[Math.max(0, score - 1)] || levels[0];
    bar.style.width = v.length ? lvl.pct : '0%';
    bar.className   = 'progress-bar ' + (v.length ? lvl.cls : '');
    lbl.textContent = v.length ? lvl.txt : '';
});

// Vérification de correspondance
document.getElementById('password_confirm').addEventListener('input', function () {
    const match = this.value === document.getElementById('password').value;
    const msg   = document.getElementById('matchMsg');
    msg.textContent = this.value ? (match ? '✓ Les mots de passe correspondent' : '✗ Les mots de passe ne correspondent pas') : '';
    msg.className   = 'small ' + (match ? 'text-success' : 'text-danger');
});
</script>