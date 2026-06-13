<?php

// ---------------------------------------------------------------------------
// 1. Constantes globales
// ---------------------------------------------------------------------------

define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', getenv('APP_URL') ?: 'http://localhost/cisco-pk');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// ---------------------------------------------------------------------------
// 2. Affichage des erreurs selon l'environnement
// ---------------------------------------------------------------------------

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ---------------------------------------------------------------------------
// 3. Démarrage de la session
// ---------------------------------------------------------------------------

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => APP_ENV === 'production',
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------------
// 4. Chargement du Core
// ---------------------------------------------------------------------------

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/core/model.php';
require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/core/router.php';

// ---------------------------------------------------------------------------
// 5. Chargement des Helpers
// ---------------------------------------------------------------------------

require_once ROOT_PATH . '/app/helpers/functions.php';

// ---------------------------------------------------------------------------
// 6. Instanciation du Router
// ---------------------------------------------------------------------------

$router = new Router();

// ===========================================================================
// DÉCLARATION DES ROUTES
// ===========================================================================

// ---------------------------------------------------------------------------
// Racine → redirige vers le dashboard si connecté, sinon vers login
// ---------------------------------------------------------------------------
$router->get('/', 'HomeController', 'index');

// ---------------------------------------------------------------------------
// Authentification
// ---------------------------------------------------------------------------
$router->form('/auth/login',               'AuthController', 'login');
$router->get('/auth/logout',               'AuthController', 'logout');
$router->form('/auth/forgot-password',     'AuthController', 'forgotPassword');
$router->form('/auth/reset-password/:token','AuthController','resetPassword');
$router->form('/auth/verify-otp',          'AuthController', 'verifyOtp');

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------
$router->get('/dashboard', 'DashboardController', 'index');

// AJAX — données temps réel pour les graphiques
$router->get('/dashboard/stats',           'DashboardController', 'stats');
$router->get('/dashboard/metrics/:id',     'DashboardController', 'deviceMetrics');

// ---------------------------------------------------------------------------
// Gestion des appareils
// ---------------------------------------------------------------------------
$router->resource('devices', 'DeviceController');

// AJAX
$router->get('/devices/:id/metrics',       'DeviceController', 'metrics');
$router->post('/devices/:id/ping',         'DeviceController', 'ping');

// ---------------------------------------------------------------------------
// Localisations
// ---------------------------------------------------------------------------
$router->resource('locations', 'LocationController');

// ---------------------------------------------------------------------------
// Métriques
// ---------------------------------------------------------------------------
$router->get('/metrics',                   'MetricController', 'index');
$router->get('/metrics/device/:id',        'MetricController', 'byDevice');

// ---------------------------------------------------------------------------
// Alertes
// ---------------------------------------------------------------------------
$router->resource('alerts', 'AlertController');
$router->post('/alerts/:id/acknowledge',   'AlertController', 'acknowledge');
$router->post('/alerts/:id/resolve',       'AlertController', 'resolve');

// ---------------------------------------------------------------------------
// Incidents
// ---------------------------------------------------------------------------
$router->resource('incidents', 'IncidentController');
$router->post('/incidents/:id/assign',     'IncidentController', 'assign');
$router->post('/incidents/:id/status',     'IncidentController', 'updateStatus');

// ---------------------------------------------------------------------------
// Notifications
// ---------------------------------------------------------------------------
$router->get('/notifications',             'NotificationController', 'index');
$router->post('/notifications/:id/read',   'NotificationController', 'markRead');
$router->post('/notifications/read-all',   'NotificationController', 'markAllRead');
$router->delete('/notifications/:id',      'NotificationController', 'destroy');

// AJAX — compteur non lus pour la navbar
$router->get('/notifications/unread-count','NotificationController', 'unreadCount');

// ---------------------------------------------------------------------------
// Utilisateurs
// ---------------------------------------------------------------------------
$router->resource('users', 'UserController');
$router->post('/users/:id/toggle-active',  'UserController', 'toggleActive');

// ---------------------------------------------------------------------------
// Rôles & Permissions
// ---------------------------------------------------------------------------
$router->get('/roles',                     'RoleController', 'index');
$router->get('/roles/create',              'RoleController', 'create');
$router->post('/roles/store',              'RoleController', 'store');
$router->get('/roles/:id/edit',            'RoleController', 'edit');
$router->post('/roles/:id/update',         'RoleController', 'update');
$router->post('/roles/:id/delete',         'RoleController', 'delete');

// ---------------------------------------------------------------------------
// Rapports
// ---------------------------------------------------------------------------
$router->get('/reports',                   'ReportController', 'index');
$router->post('/reports/generate',         'ReportController', 'generate');
$router->get('/reports/:id/download',      'ReportController', 'download');
$router->post('/reports/:id/delete',       'ReportController', 'delete');

// ---------------------------------------------------------------------------
// Audit / Logs
// ---------------------------------------------------------------------------
$router->get('/audit',                     'AuditController', 'index');
$router->get('/audit/export',              'AuditController', 'export');

// ---------------------------------------------------------------------------
// Profil utilisateur connecté
// ---------------------------------------------------------------------------
$router->form('/profile',                  'ProfileController', 'index');
$router->post('/profile/avatar',           'ProfileController', 'updateAvatar');
$router->post('/profile/password',         'ProfileController', 'updatePassword');

// ===========================================================================
// 7. DISPATCH — analyse l'URL et appelle le bon controller
// ===========================================================================

$router->dispatch();