<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erreur serveur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center">
        <div class="display-1 fw-bold text-warning mb-3">500</div>
        <h2 class="mb-2">Erreur serveur</h2>
        <p class="text-muted mb-4">
            Une erreur inattendue s'est produite. Veuillez réessayer plus tard.
        </p>
        <a href="<?= defined('BASE_URL') ? BASE_URL . '/dashboard' : '/' ?>"
           class="btn btn-warning">
            <i class="bi bi-house me-2"></i>Retour au dashboard
        </a>
    </div>
</body>
</html>
