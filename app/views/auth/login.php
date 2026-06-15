<div class="card auth-card mx-auto">
    <div class="card-body p-4 p-sm-5">

        <!-- Logo + titre -->
        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="bi bi-activity"></i>
            </div>
            <h4 class="fw-bold mb-1">Nexora Monitoring</h4>
            <p class="text-muted small">Connectez-vous à votre espace</p>
        </div>

        <!-- Formulaire -->
        <form method="POST" action="<?= url('/auth/login') ?>" novalidate>
            <?= csrf_field() ?>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input
                        type="email"
                        class="form-control border-start-0 ps-0"
                        id="email"
                        name="email"
                        placeholder="vous@nexora.com"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                        autofocus
                    >
                </div>
            </div>

            <!-- Mot de passe -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label fw-semibold mb-0">Mot de passe</label>
                    <a href="<?= url('/auth/forgot-password') ?>" class="small text-decoration-none">
                        Mot de passe oublié ?
                    </a>
                </div>
                <div class="input-group mt-1">
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
                        autocomplete="current-password"
                    >
                    <!-- Bouton afficher/masquer le mot de passe -->
                    <button
                        type="button"
                        class="input-group-text bg-light border-start-0"
                        id="togglePassword"
                        title="Afficher / masquer"
                    >
                        <i class="bi bi-eye text-muted" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Bouton connexion -->
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </div>
        </form>

        <!-- Pied de carte -->
        <p class="text-center text-muted small mt-4 mb-0">
            <i class="bi bi-shield-lock me-1"></i>
            Connexion sécurisée — Vérification en deux étapes activée
        </p>
    </div>
</div>

<script>
    // Afficher / masquer le mot de passe
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input   = document.getElementById('password');
        const icon    = document.getElementById('eyeIcon');
        const visible = input.type === 'text';

        input.type    = visible ? 'password' : 'text';
        icon.className = visible ? 'bi bi-eye text-muted' : 'bi bi-eye-slash text-muted';
    });
</script>