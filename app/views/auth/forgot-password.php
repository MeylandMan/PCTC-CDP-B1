<div class="card auth-card mx-auto">
    <div class="card-body p-4 p-sm-5">

        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="bi bi-key"></i>
            </div>
            <h5 class="fw-bold mb-1">Mot de passe oublié</h5>
            <p class="text-muted small">
                Saisissez votre adresse email pour recevoir un lien de réinitialisation.
            </p>
        </div>

        <form method="POST" action="<?= url('/auth/forgot-password') ?>" novalidate>
            <?= csrf_field() ?>

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
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-send me-2"></i>Envoyer le lien
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="<?= url('/auth/login') ?>" class="small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Retour à la connexion
            </a>
        </div>
    </div>
</div>