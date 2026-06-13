<?php

// ---------------------------------------------------------------------------
// Sécurité & échappement
// ---------------------------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $token = $_SESSION['csrf_token'];
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}

// ---------------------------------------------------------------------------
// Navigation & URL
// ---------------------------------------------------------------------------

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function asset(string $path): string
{
    return url('/public/assets/' . ltrim($path, '/'));
}

function active(string $pattern, string $class = 'active'): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = '/' . trim($uri, '/');

    // Correspondance exacte ou début de chemin (pour les sous-pages)
    if ($uri === $pattern || str_starts_with($uri, rtrim($pattern, '/') . '/')) {
        return $class;
    }

    return '';
}

// ---------------------------------------------------------------------------
// Messages flash
// ---------------------------------------------------------------------------

function flash_messages(): string
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    if (empty($messages)) {
        return '';
    }

    // Correspondance type → classe Bootstrap
    $map = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'info',
    ];

    $html = '';
    foreach ($messages as $msg) {
        $bsClass = $map[$msg['type']] ?? 'info';
        $html   .= sprintf(
            '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">'
            . '<i class="bi bi-' . alert_icon($msg['type']) . ' me-2"></i>'
            . '%s'
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
            . '</div>',
            e($msg['message'])
        );
    }

    return $html;
}

function alert_icon(string $type): string
{
    return match($type) {
        'success' => 'check-circle-fill',
        'error'   => 'x-circle-fill',
        'warning' => 'exclamation-triangle-fill',
        default   => 'info-circle-fill',
    };
}

// ---------------------------------------------------------------------------
// Formatage des données
// ---------------------------------------------------------------------------

function format_date(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if (empty($datetime)) {
        return '—';
    }

    try {
        return (new DateTime($datetime))->format($format);
    } catch (Exception) {
        return '—';
    }
}

function time_ago(?string $datetime): string
{
    if (empty($datetime)) {
        return '—';
    }

    try {
        $past = new DateTime($datetime);
        $now  = new DateTime();
        $diff = $now->diff($past);

        if ($diff->y > 0) return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        if ($diff->m > 0) return 'il y a ' . $diff->m . ' mois';
        if ($diff->d > 0) return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
        if ($diff->h > 0) return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        if ($diff->i > 0) return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');

        return 'à l\'instant';
    } catch (Exception) {
        return '—';
    }
}

function format_bytes(int|float $bytes, int $decimals = 2): string
{
    if ($bytes <= 0) return '0 o';

    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $i     = (int) floor(log($bytes, 1024));

    return round($bytes / pow(1024, $i), $decimals) . ' ' . $units[$i];
}

function format_percent(float|null $value): string
{
    if ($value === null) return '—';
    return number_format($value, 2, '.', ' ') . ' %';
}

// ---------------------------------------------------------------------------
// Badges de statut (HTML prêt à l'emploi)
// ---------------------------------------------------------------------------

function device_status_badge(string $status): string
{
    [$label, $class] = match($status) {
        'online'      => ['En ligne',      'success'],
        'offline'     => ['Hors ligne',    'danger'],
        'warning'     => ['Avertissement', 'warning'],
        'maintenance' => ['Maintenance',   'secondary'],
        default       => [$status,         'light'],
    };

    return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
}

function alert_level_badge(string $level): string
{
    [$label, $class] = match($level) {
        'info'    => ['Info',    'info'],
        'warning' => ['Alerte', 'warning'],
        'critique'=> ['Critique','danger'],
        'urgent'  => ['Urgent', 'dark'],
        default   => [$level,   'secondary'],
    };

    return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
}

function incident_status_badge(string $status): string
{
    [$label, $class] = match($status) {
        'open'     => ['Ouvert',      'danger'],
        'incoming' => ['En cours',    'warning'],
        'waiting'  => ['En attente',  'info'],
        'resolved' => ['Résolu',      'success'],
        'closed'   => ['Fermé',       'secondary'],
        default    => [$status,       'light'],
    };

    return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
}

function incident_priority_badge(string $priority): string
{
    [$label, $class] = match($priority) {
        'low'      => ['Faible',   'success'],
        'medium'   => ['Moyenne',  'info'],
        'high'     => ['Haute',    'warning'],
        'critique' => ['Critique', 'danger'],
        default    => [$priority,  'secondary'],
    };

    return '<span class="badge bg-' . $class . '">' . e($label) . '</span>';
}

// ---------------------------------------------------------------------------
// Pagination (HTML prêt à l'emploi)
// ---------------------------------------------------------------------------

/**
 * Génère le HTML de pagination Bootstrap 5 depuis le tableau retourné
 * par Model::paginate().
 *
 * Exemple dans une vue :
 *   <?= pagination($devices, '/devices') ?>
 *
 * @param array  $paginator Tableau retourné par Model::paginate()
 * @param string $baseUrl   URL de base (sans ?page=)
 */
function pagination(array $paginator, string $baseUrl): string
{
    $total       = $paginator['total']       ?? 0;
    $perPage     = $paginator['perPage']     ?? 15;
    $currentPage = $paginator['currentPage'] ?? 1;
    $lastPage    = $paginator['lastPage']    ?? 1;

    if ($total <= $perPage) {
        return '';
    }

    $html  = '<nav aria-label="Pagination"><ul class="pagination justify-content-center mb-0">';

    // Bouton précédent
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $prevHref     = $currentPage <= 1 ? '#' : e($baseUrl . '?page=' . ($currentPage - 1));
    $html .= '<li class="page-item' . $prevDisabled . '">'
           . '<a class="page-link" href="' . $prevHref . '">‹ Précédent</a></li>';

    // Pages numérotées (fenêtre glissante de 5 pages autour de la page courante)
    $start = max(1, $currentPage - 2);
    $end   = min($lastPage, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl . '?page=1') . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '">'
                . '<a class="page-link" href="' . e($baseUrl . '?page=' . $i) . '">' . $i . '</a>'
                . '</li>';
    }

    if ($end < $lastPage) {
        if ($end < $lastPage - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl . '?page=' . $lastPage) . '">' . $lastPage . '</a></li>';
    }

    // Bouton suivant
    $nextDisabled = $currentPage >= $lastPage ? ' disabled' : '';
    $nextHref     = $currentPage >= $lastPage ? '#' : e($baseUrl . '?page=' . ($currentPage + 1));
    $html .= '<li class="page-item' . $nextDisabled . '">'
           . '<a class="page-link" href="' . $nextHref . '">Suivant ›</a></li>';

    $html .= '</ul></nav>';

    // Résumé textuel
    $from  = ($currentPage - 1) * $perPage + 1;
    $to    = min($currentPage * $perPage, $total);
    $html .= '<p class="text-center text-muted small mt-2 mb-0">'
           . "Affichage de {$from} à {$to} sur {$total} résultats</p>";

    return $html;
}

// ---------------------------------------------------------------------------
// Divers
// ---------------------------------------------------------------------------

function truncate(string $text, int $length = 100, string $suffix = '…'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

function arr(array $data, string $key, mixed $default = null): mixed
{
    return $data[$key] ?? $default;
}

function uid(): string
{
    return substr(bin2hex(random_bytes(4)), 0, 8);
}