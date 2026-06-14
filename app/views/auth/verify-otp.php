<div class="card auth-card mx-auto">
    <div class="card-body p-4 p-sm-5">

        <!-- Logo + titre -->
        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5 class="fw-bold mb-1">Vérification en deux étapes</h5>
            <p class="text-muted small mb-0">
                Un code à 6 chiffres a été envoyé à
            </p>
            <p class="fw-semibold small"><?= e($email ?? '') ?></p>
        </div>

        <!-- Formulaire OTP -->
        <form method="POST" action="<?= url('/auth/verify-otp') ?>" id="otpForm" novalidate>
            <?= csrf_field() ?>

            <!-- 6 champs individuels pour le code OTP -->
            <div class="d-flex justify-content-center gap-2 mb-4" id="otpInputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]"
                    maxlength="1"
                    name="otp_<?= $i ?>"
                    id="otp_<?= $i ?>"
                    class="form-control text-center fw-bold fs-4 p-2 otp-input"
                    style="width: 48px; height: 56px;"
                    autocomplete="off"
                    required
                >
                <?php endfor; ?>
            </div>

            <!-- Bouton valider -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Valider le code
                </button>
            </div>

            <!-- Renvoyer le code -->
            <div class="text-center">
                <span class="text-muted small">Vous n'avez pas reçu le code ?</span>
                <a href="<?= url('/auth/login') ?>" class="small text-decoration-none ms-1">
                    Recommencer la connexion
                </a>
            </div>
        </form>

        <!-- Expiration -->
        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i>
                Ce code expire dans <span id="countdown" class="fw-semibold text-warning">10:00</span>
            </small>
        </div>
    </div>
</div>

<script>
(function () {
    // -----------------------------------------------------------------------
    // Navigation entre les champs OTP
    // -----------------------------------------------------------------------
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        // Accepte uniquement les chiffres
        input.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });

        // Passe au champ suivant automatiquement après saisie
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '').slice(-1);
            if (input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            // Soumet automatiquement quand tous les champs sont remplis
            if ([...inputs].every(i => i.value !== '')) {
                document.getElementById('otpForm').submit();
            }
        });

        // Retour arrière : efface et revient au champ précédent
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
            }
        });
    });

    // Focus sur le premier champ au chargement
    inputs[0]?.focus();

    // Support du collage (paste) : distribue les chiffres dans les 6 champs
    inputs[0]?.addEventListener('paste', (e) => {
        e.preventDefault();
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((digit, i) => {
            if (inputs[i]) inputs[i].value = digit;
        });
        if (digits.length === 6) document.getElementById('otpForm').submit();
        else inputs[Math.min(digits.length, 5)]?.focus();
    });

    // -----------------------------------------------------------------------
    // Compte à rebours 10 minutes
    // -----------------------------------------------------------------------
    let seconds = 10 * 60;
    const el    = document.getElementById('countdown');

    const timer = setInterval(() => {
        seconds--;
        const m = String(Math.floor(seconds / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        el.textContent = `${m}:${s}`;

        if (seconds <= 60) el.classList.replace('text-warning', 'text-danger');

        if (seconds <= 0) {
            clearInterval(timer);
            el.textContent = 'Expiré';
            document.querySelector('button[type=submit]').disabled = true;
        }
    }, 1000);
})();
</script>