<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> — Nexora Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/adminlte.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

    <style>
        /* ------------------------------------------------------------------ */
        /* Variables personnalisées Nexora                                      */
        /* ------------------------------------------------------------------ */
        :root {
            --nexora-primary:   #4f46e5;
            --nexora-primary-dark: #4338ca;
            --nexora-accent:    #7c3aed;
            --nexora-sidebar-bg: #1e2230;
        }

        /* ------------------------------------------------------------------ */
        /* Sidebar — thème sombre Nexora                                        */
        /* ------------------------------------------------------------------ */
        .app-sidebar {
            background-color: var(--nexora-sidebar-bg) !important;
        }

        .sidebar-brand {
            background-color: var(--nexora-sidebar-bg);
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand .brand-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .brand-text {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.3px;
        }

        /* Items du menu */
        .sidebar-menu .nav-link {
            border-radius: 8px;
            margin-bottom: 2px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .sidebar-menu .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
            font-size: 1rem;
        }

        .sidebar-menu .nav-link.active,
        .sidebar-menu .nav-item.menu-open > .nav-link {
            background-color: var(--nexora-primary) !important;
            color: #fff !important;
        }

        .sidebar-menu .nav-link:hover:not(.active) {
            background-color: rgba(255,255,255,0.08) !important;
            color: #fff !important;
        }

        .sidebar-menu .nav-header {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35) !important;
            padding: 1rem 0.75rem 0.35rem;
        }

        /* Sous-menu (treeview) */
        .sidebar-menu .nav-treeview .nav-link {
            padding-left: 2.5rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.55) !important;
        }

        .sidebar-menu .nav-treeview .nav-link:hover,
        .sidebar-menu .nav-treeview .nav-link.active {
            color: #fff !important;
        }

        /* ------------------------------------------------------------------ */
        /* Header                                                               */
        /* ------------------------------------------------------------------ */
        .app-header {
            border-bottom: 1px solid var(--bs-border-color);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .app-header .nav-link {
            color: var(--bs-body-color);
            border-radius: 8px;
            padding: 0.4rem 0.6rem;
            transition: background 0.15s;
        }

        .app-header .nav-link:hover {
            background-color: var(--bs-tertiary-bg);
        }

        /* Badge notifications */
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.6rem;
            min-width: 16px;
            height: 16px;
            line-height: 16px;
            padding: 0 4px;
            border-radius: 8px;
        }

        /* Avatar utilisateur */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--nexora-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
        }

        /* ------------------------------------------------------------------ */
        /* Contenu principal                                                    */
        /* ------------------------------------------------------------------ */
        .app-content-header {
            background-color: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .app-content-header h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .app-content {
            padding: 1.25rem 0.75rem;
        }

        /* ------------------------------------------------------------------ */
        /* Footer                                                               */
        /* ------------------------------------------------------------------ */
        .app-footer {
            font-size: 0.82rem;
            color: var(--bs-secondary-color);
        }

        /* ------------------------------------------------------------------ */
        /* Utilitaires globaux                                                  */
        /* ------------------------------------------------------------------ */

        /* Scrollbar fine sur la sidebar */
        .sidebar-wrapper {
            overflow-y: auto;
            max-height: calc(100vh - 3.5rem);
            scrollbar-width: thin;
        }

        /* Cards avec bordure colorée à gauche */
        .card-left-border {
            border-left: 4px solid var(--nexora-primary);
        }
    </style>
</head>

<body class="sidebar-mini sidebar-expand-lg layout-fixed">

<div class="app-wrapper">

    <!-- ================================================================== -->
    <!-- SIDEBAR                                                              -->
    <!-- ================================================================== -->
    <aside class="app-sidebar" data-bs-theme="dark">

        <!-- Logo / Marque -->
        <div class="sidebar-brand">
            <a href="<?= url('/dashboard') ?>" class="brand-link">
                <!-- Icône visible quand sidebar réduite -->
                <i class="bi bi-activity text-primary fs-4 logo-xs d-none"></i>
                <!-- Logo complet -->
                <span class="d-flex align-items-center gap-2 logo-xl">
                    <span style="
                        width:32px; height:32px; border-radius:8px;
                        background: linear-gradient(135deg, var(--nexora-primary), var(--nexora-accent));
                        display:flex; align-items:center; justify-content:center;
                    ">
                        <i class="bi bi-activity text-white" style="font-size:1rem;"></i>
                    </span>
                    <span class="brand-text text-white">Nexora<span class="text-primary">Mon</span></span>
                </span>
            </a>
        </div>

        <!-- Menu de navigation -->
        <div class="sidebar-wrapper">
            <nav>
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?= url('/dashboard') ?>"
                           class="nav-link <?= active('/dashboard') ?>">
                            <i class="bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- ---- INFRASTRUCTURE ---- -->
                    <li class="nav-header">Infrastructure</li>

                    <!-- Appareils -->
                    <li class="nav-item <?= active('/devices') ? 'menu-open' : '' ?>">
                        <a href="#"
                           class="nav-link <?= active('/devices') ?>">
                            <i class="bi bi-hdd-network"></i>
                            <p>
                                Appareils
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= url('/devices') ?>" class="nav-link <?= active('/devices') ?>">
                                    <i class="bi bi-list-ul"></i>
                                    <p>Liste</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= url('/devices/create') ?>" class="nav-link <?= active('/devices/create') ?>">
                                    <i class="bi bi-plus-circle"></i>
                                    <p>Ajouter</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Localisations -->
                    <li class="nav-item">
                        <a href="<?= url('/locations') ?>"
                           class="nav-link <?= active('/locations') ?>">
                            <i class="bi bi-geo-alt"></i>
                            <p>Localisations</p>
                        </a>
                    </li>

                    <!-- Métriques -->
                    <li class="nav-item">
                        <a href="<?= url('/metrics') ?>"
                           class="nav-link <?= active('/metrics') ?>">
                            <i class="bi bi-graph-up"></i>
                            <p>Métriques</p>
                        </a>
                    </li>

                    <!-- ---- SUPERVISION ---- -->
                    <li class="nav-header">Supervision</li>

                    <!-- Alertes -->
                    <li class="nav-item">
                        <a href="<?= url('/alerts') ?>"
                           class="nav-link <?= active('/alerts') ?>">
                            <i class="bi bi-bell"></i>
                            <p>
                                Alertes
                                <?php if (($alertCount ?? 0) > 0): ?>
                                <span class="nav-badge badge bg-danger ms-auto">
                                    <?= (int)($alertCount ?? 0) ?>
                                </span>
                                <?php endif; ?>
                            </p>
                        </a>
                    </li>

                    <!-- Incidents -->
                    <li class="nav-item">
                        <a href="<?= url('/incidents') ?>"
                           class="nav-link <?= active('/incidents') ?>">
                            <i class="bi bi-ticket-detailed"></i>
                            <p>
                                Incidents
                                <?php if (($incidentCount ?? 0) > 0): ?>
                                <span class="nav-badge badge bg-warning text-dark ms-auto">
                                    <?= (int)($incidentCount ?? 0) ?>
                                </span>
                                <?php endif; ?>
                            </p>
                        </a>
                    </li>

                    <!-- Notifications -->
                    <li class="nav-item">
                        <a href="<?= url('/notifications') ?>"
                           class="nav-link <?= active('/notifications') ?>">
                            <i class="bi bi-chat-dots"></i>
                            <p>Notifications</p>
                        </a>
                    </li>

                    <!-- ---- ADMINISTRATION ---- (visible admin+ seulement) -->
                    <?php if (in_array($_SESSION['user']['role_name'] ?? '', ['super_admin', 'admin'])): ?>
                    <li class="nav-header">Administration</li>

                    <!-- Utilisateurs -->
                    <li class="nav-item">
                        <a href="<?= url('/users') ?>"
                           class="nav-link <?= active('/users') ?>">
                            <i class="bi bi-people"></i>
                            <p>Utilisateurs</p>
                        </a>
                    </li>

                    <!-- Rôles -->
                    <li class="nav-item">
                        <a href="<?= url('/roles') ?>"
                           class="nav-link <?= active('/roles') ?>">
                            <i class="bi bi-shield-lock"></i>
                            <p>Rôles & Permissions</p>
                        </a>
                    </li>

                    <!-- Rapports -->
                    <li class="nav-item">
                        <a href="<?= url('/reports') ?>"
                           class="nav-link <?= active('/reports') ?>">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <p>Rapports</p>
                        </a>
                    </li>

                    <!-- Audit -->
                    <li class="nav-item">
                        <a href="<?= url('/audit') ?>"
                           class="nav-link <?= active('/audit') ?>">
                            <i class="bi bi-journal-text"></i>
                            <p>Audit / Logs</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- ---- COMPTE ---- -->
                    <li class="nav-header">Compte</li>

                    <li class="nav-item">
                        <a href="<?= url('/profile') ?>"
                           class="nav-link <?= active('/profile') ?>">
                            <i class="bi bi-person-circle"></i>
                            <p>Mon profil</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= url('/auth/logout') ?>" class="nav-link text-danger">
                            <i class="bi bi-box-arrow-right"></i>
                            <p>Déconnexion</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
        <!-- /.sidebar-wrapper -->

    </aside>
    <!-- /.app-sidebar -->


    <!-- ================================================================== -->
    <!-- HEADER                                                               -->
    <!-- ================================================================== -->
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">

            <!-- Bouton toggle sidebar (gauche) -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list fs-5"></i>
                    </a>
                </li>
            </ul>

            <!-- Titre de la page courante (visible en mobile) -->
            <span class="navbar-brand d-lg-none fw-bold">
                <?= e($title ?? '') ?>
            </span>

            <!-- Actions à droite -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                <!-- Bouton dark/light mode -->
                <li class="nav-item">
                    <a class="nav-link" href="#" id="themeSwitcher" title="Changer le thème">
                        <i class="bi bi-moon-stars fs-5" id="themeIcon"></i>
                    </a>
                </li>

                <!-- Cloche notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false"
                       id="notifBell" title="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge badge bg-danger d-none" id="notifCount">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow" style="width:320px; max-height:400px; overflow-y:auto;">
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <span class="fw-semibold">Notifications</span>
                            <a href="<?= url('/notifications/read-all') ?>"
                               class="small text-decoration-none text-primary"
                               id="markAllRead">Tout marquer lu</a>
                        </div>
                        <div id="notifList">
                            <p class="text-center text-muted small py-3 mb-0">Chargement…</p>
                        </div>
                        <div class="border-top text-center py-2">
                            <a href="<?= url('/notifications') ?>" class="small text-decoration-none">
                                Voir toutes les notifications
                            </a>
                        </div>
                    </div>
                </li>

                <!-- Séparateur -->
                <li class="nav-item"><span class="text-muted">|</span></li>

                <!-- Menu utilisateur -->
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center gap-2" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false">

                        <?php
                        $user   = $_SESSION['user'] ?? [];
                        $avatar = $user['avatar'] ?? null;
                        $initials = strtoupper(
                            substr($user['firstname'] ?? '?', 0, 1) .
                            substr($user['lastname']  ?? '',  0, 1)
                        );
                        ?>

                        <?php if ($avatar): ?>
                            <img src="<?= url($avatar) ?>" alt="avatar" class="user-avatar">
                        <?php else: ?>
                            <div class="user-avatar"><?= e($initials) ?></div>
                        <?php endif; ?>

                        <span class="d-none d-md-inline fw-semibold small">
                            <?= e($user['firstname'] ?? '') ?>
                        </span>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-semibold"><?= e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></div>
                                <div class="small text-muted"><?= e($user['email'] ?? '') ?></div>
                                <span class="badge bg-primary mt-1" style="font-size:.65rem;">
                                    <?= e(str_replace('_', ' ', $user['role_name'] ?? '')) ?>
                                </span>
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= url('/profile') ?>">
                                <i class="bi bi-person me-2"></i>Mon profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= url('/auth/logout') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </nav>
    <!-- /.app-header -->


    <!-- ================================================================== -->
    <!-- CONTENU PRINCIPAL                                                    -->
    <!-- ================================================================== -->
    <main class="app-main">

        <!-- En-tête de page : titre + fil d'ariane -->
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between py-2">
                    <h3><?= e($title ?? '') ?></h3>
                    <!-- Fil d'ariane -->
                    <?php if (!empty($breadcrumbs)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="<?= url('/dashboard') ?>">
                                    <i class="bi bi-house"></i>
                                </a>
                            </li>
                            <?php foreach ($breadcrumbs as $label => $href): ?>
                                <?php if ($href): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= e($href) ?>"><?= e($label) ?></a>
                                </li>
                                <?php else: ?>
                                <li class="breadcrumb-item active"><?= e($label) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Zone de contenu — $content injecté par Controller::view() -->
        <div class="app-content">
            <div class="container-fluid">

                <!-- Messages flash -->
                <?= flash_messages() ?>

                <!-- Contenu de la vue -->
                <?= $content ?>

            </div>
        </div>

    </main>
    <!-- /.app-main -->


    <!-- ================================================================== -->
    <!-- FOOTER                                                               -->
    <!-- ================================================================== -->
    <footer class="app-footer">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>
                <strong>Nexora Monitoring</strong> &copy; <?= date('Y') ?>
                — Plateforme de supervision réseau
            </span>
            <span>
                Version <strong>1.0.0</strong>
                &nbsp;·&nbsp;
                <a href="<?= url('/audit') ?>" class="text-decoration-none">Logs</a>
            </span>
        </div>
    </footer>

</div>
<!-- /.app-wrapper -->


<!-- Bootstrap 5 JS (Popper + dropdowns) — requis car AdminLTE n'embarque pas ce JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE v4 JS (toggle sidebar, treeview…) -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc7/dist/js/adminlte.min.js"></script>

<script>
(function () {
    'use strict';

    // ------------------------------------------------------------------
    // Thème sombre / clair
    // ------------------------------------------------------------------
    const root      = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    const stored    = localStorage.getItem('nexora-theme') || 'light';

    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
        themeIcon.className = theme === 'dark'
            ? 'bi bi-sun fs-5'
            : 'bi bi-moon-stars fs-5';
        localStorage.setItem('nexora-theme', theme);
    }

    applyTheme(stored);

    document.getElementById('themeSwitcher').addEventListener('click', function (e) {
        e.preventDefault();
        applyTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });

    // ------------------------------------------------------------------
    // Notifications — chargement AJAX au clic sur la cloche
    // ------------------------------------------------------------------
    const bell      = document.getElementById('notifBell');
    const notifList = document.getElementById('notifList');
    const countBadge = document.getElementById('notifCount');

    // Charge le nombre de non-lus toutes les 60 secondes
    function fetchUnreadCount() {
        fetch('<?= url('/notifications/unread-count') ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const count = data.count ?? 0;
            if (count > 0) {
                countBadge.textContent = count > 99 ? '99+' : count;
                countBadge.classList.remove('d-none');
            } else {
                countBadge.classList.add('d-none');
            }
        })
        .catch(() => {});
    }

    fetchUnreadCount();
    setInterval(fetchUnreadCount, 60_000);

    // Charge les dernières notifications au clic
    bell.addEventListener('click', function () {
        notifList.innerHTML = '<p class="text-center text-muted small py-3 mb-0">Chargement…</p>';

        fetch('<?= url('/notifications') ?>?ajax=1&limit=5', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const items = data.notifications ?? [];
            if (items.length === 0) {
                notifList.innerHTML = '<p class="text-center text-muted small py-3 mb-0">Aucune notification</p>';
                return;
            }

            notifList.innerHTML = items.map(n => `
                <a href="<?= url('/notifications') ?>" class="dropdown-item py-2 ${n.is_read ? '' : 'fw-semibold bg-light'}">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-info-circle text-primary mt-1"></i>
                        <div>
                            <div class="small">${escHtml(n.title)}</div>
                            <div class="text-muted" style="font-size:.75rem;">${escHtml(n.sent_at)}</div>
                        </div>
                    </div>
                </a>
            `).join('');
        })
        .catch(() => {
            notifList.innerHTML = '<p class="text-center text-danger small py-3 mb-0">Erreur de chargement</p>';
        });
    });

    // ------------------------------------------------------------------
    // Marquer tout comme lu
    // ------------------------------------------------------------------
    document.getElementById('markAllRead')?.addEventListener('click', function (e) {
        e.preventDefault();
        fetch('<?= url('/notifications/read-all') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: '_csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
        .then(r => r.json())
        .then(() => {
            countBadge.classList.add('d-none');
            notifList.innerHTML = '<p class="text-center text-muted small py-3 mb-0">Aucune notification</p>';
        })
        .catch(() => {});
    });

    // ------------------------------------------------------------------
    // Utilitaire : échappe le HTML dans les templates JS
    // ------------------------------------------------------------------
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
</script>

</body>
</html>