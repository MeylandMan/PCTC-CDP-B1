<?php

define('ROOT_PATH', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Pas de session ni de header HTTP en CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Ce script ne peut être exécuté qu\'en ligne de commande.');
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/core/model.php';
require_once ROOT_PATH . '/app/models/device-model.php';
require_once ROOT_PATH . '/app/models/alert-model.php';
require_once ROOT_PATH . '/app/models/notification-model.php';

// -----------------------------------------------------------------------
// Initialisation
// -----------------------------------------------------------------------

$startTime = microtime(true);
$deviceModel       = new DeviceModel();
$alertModel        = new AlertModel();
$notificationModel = new NotificationModel();
$db                 = Database::getInstance()->getConnection();

log_line('========================================');
log_line('Démarrage du cycle de monitoring');

// -----------------------------------------------------------------------
// Récupère tous les appareils à surveiller
// -----------------------------------------------------------------------

$devices = $deviceModel->findAll();
log_line(count($devices) . ' appareil(s) à vérifier.');

$stats = ['checked' => 0, 'online' => 0, 'offline' => 0, 'alerts_created' => 0];

foreach ($devices as $device) {
    $stats['checked']++;
    process_device($device, $deviceModel, $alertModel, $notificationModel, $stats);
}

// -----------------------------------------------------------------------
// Résumé final
// -----------------------------------------------------------------------

$duration = round(microtime(true) - $startTime, 2);

log_line('---');
log_line("Terminé en {$duration}s");
log_line("Appareils vérifiés : {$stats['checked']}");
log_line("En ligne : {$stats['online']} | Hors ligne : {$stats['offline']}");
log_line("Alertes créées : {$stats['alerts_created']}");
log_line('========================================');


// =========================================================================
// FONCTIONS
// =========================================================================

/**
 * Traite un appareil : ping, métriques, seuils, notifications.
 */
function process_device(
    array $device,
    DeviceModel $deviceModel,
    AlertModel $alertModel,
    NotificationModel $notificationModel,
    array &$stats
): void {
    $deviceId = (int) $device['id'];
    $ip       = $device['ip_address'];

    // -------------------------------------------------------------------
    // 1. Ping
    // -------------------------------------------------------------------
    $alive  = ping_host($ip);
    $status = $alive ? 'online' : 'offline';

    $previousStatus = $device['status'];
    $deviceModel->updateStatus($deviceId, $status);

    log_line("[{$device['name']}] {$ip} → " . ($alive ? 'EN LIGNE' : 'HORS LIGNE'));

    if ($alive) {
        $stats['online']++;
    } else {
        $stats['offline']++;
    }

    // -------------------------------------------------------------------
    // 2. Détecte un changement de statut (notification immédiate)
    // -------------------------------------------------------------------
    if ($previousStatus !== $status) {
        notify_status_change($device, $status, $notificationModel);

        // Une perte de connexion génère systématiquement une alerte
        if ($status === 'offline') {
            create_connectivity_alert($deviceId, $device['name'], $alertModel);
            $stats['alerts_created']++;
        }
    }

    // -------------------------------------------------------------------
    // 3. Collecte des métriques (uniquement si en ligne)
    // -------------------------------------------------------------------
    if (!$alive) {
        return;
    }

    $metrics = collect_metrics($device);
    $deviceModel->insertMetric($deviceId, $metrics);

    log_line(sprintf(
        "  CPU: %.1f%%  RAM: %.1f%%  Disque: %.1f%%  Temp: %.1f°C",
        $metrics['cpu_usage'],
        $metrics['ram_usage'],
        $metrics['disk_usage'],
        $metrics['temperature']
    ));

    // -------------------------------------------------------------------
    // 4. Vérifie les seuils et déclenche des alertes si besoin
    // -------------------------------------------------------------------
    $beforeCount = count($alertModel->findBy(['device_id' => $deviceId, 'status' => 'open']));
    $alertModel->checkThresholds($deviceId, $metrics);
    $afterCount  = count($alertModel->findBy(['device_id' => $deviceId, 'status' => 'open']));

    if ($afterCount > $beforeCount) {
        $created = $afterCount - $beforeCount;
        $stats['alerts_created'] += $created;
        log_line("  ⚠ {$created} nouvelle(s) alerte(s) déclenchée(s)");

        notify_threshold_alerts($device, $metrics, $notificationModel);
    }
}

/**
 * Ping ICMP réel via exec(). Fonctionne sous Linux et Windows.
 */
function ping_host(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    $escapedIp = escapeshellarg($ip);

    $cmd = PHP_OS_FAMILY === 'Windows'
        ? "ping -n 1 -w 1000 {$escapedIp}"
        : "ping -c 1 -W 1 {$escapedIp}";

    exec($cmd . ' 2>&1', $output, $returnCode);

    return $returnCode === 0;
}

/**
 * Collecte les métriques d'un appareil.
 *
 * NOTE IMPORTANTE : dans un environnement de démonstration / projet
 * étudiant sans agent de supervision installé sur les machines distantes
 * (comme Zabbix ou un agent SNMP), il est impossible de lire le vrai
 * CPU/RAM d'un serveur distant juste via ping. Cette fonction simule
 * donc des métriques réalistes pour permettre de démontrer les
 * fonctionnalités d'alerte et de reporting.
 *
 * Pour une collecte RÉELLE en production, il faudrait :
 *   - SNMP (snmpget) si l'appareil expose un agent SNMP
 *   - Un agent léger installé sur chaque machine qui pousse ses métriques
 *     vers une API de l'application (POST /api/metrics)
 *   - SSH + commandes système (top, free, df) pour les serveurs Linux
 */
function collect_metrics(array $device): array
{
    // Simulation réaliste : valeurs de base + variation aléatoire
    // Les appareils de type 'server' ont des charges plus variables
    $isServer = $device['type'] === 'server';

    $baseCpu  = $isServer ? random_int(30, 60) : random_int(5, 25);
    $baseRam  = $isServer ? random_int(40, 70) : random_int(10, 35);
    $baseDisk = $isServer ? random_int(35, 65) : random_int(10, 30);

    // 5% de chance de simuler un pic (pour tester les alertes)
    $spike = random_int(1, 100) <= 5;

    return [
        'cpu_usage'   => round($spike ? random_int(88, 99) : $baseCpu + random_int(-5, 5), 2),
        'ram_usage'   => round($spike ? random_int(86, 96) : $baseRam + random_int(-5, 5), 2),
        'disk_usage'  => round($baseDisk + random_int(-2, 2), 2), // évolue lentement
        'network_in'  => round(random_int(100, 2000) / 10, 2),
        'network_out' => round(random_int(50, 1500) / 10, 2),
        'temperature' => round(random_int(350, 600) / 10, 2),
    ];
}

/**
 * Crée une alerte de type "connectivité perdue".
 */
function create_connectivity_alert(int $deviceId, string $deviceName, AlertModel $alertModel): void
{
    $alertModel->insert([
        'device_id'       => $deviceId,
        'alert_level'     => 'critique',
        'alert_type'      => 'connectivity',
        'message'         => "L'appareil {$deviceName} ne répond plus au ping.",
        'threshold_value' => null,
        'current_value'   => null,
        'status'          => 'open',
    ]);
}

/**
 * Notifie les administrateurs et techniciens d'un changement de statut.
 */
function notify_status_change(array $device, string $newStatus, NotificationModel $notificationModel): void
{
    $db = Database::getInstance()->getConnection();

    // Notifie tous les admins et super_admins
    $stmt = $db->prepare(
        "SELECT u.id FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE r.role_name IN ('super_admin', 'admin') AND u.is_active = 1"
    );
    $stmt->execute();
    $recipients = $stmt->fetchAll();

    $title = $newStatus === 'offline'
        ? "⚠ {$device['name']} est hors ligne"
        : "✓ {$device['name']} est de nouveau en ligne";

    $content = $newStatus === 'offline'
        ? "L'appareil {$device['name']} ({$device['ip_address']}) ne répond plus depuis la dernière vérification."
        : "L'appareil {$device['name']} ({$device['ip_address']}) a retrouvé sa connectivité.";

    foreach ($recipients as $user) {
        $notificationModel->send((int) $user['id'], $title, $content, 'dashboard');
    }
}

/**
 * Notifie les responsables quand une alerte de seuil est créée.
 */
function notify_threshold_alerts(array $device, array $metrics, NotificationModel $notificationModel): void
{
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare(
        "SELECT u.id FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE r.role_name IN ('super_admin', 'admin', 'technicien') AND u.is_active = 1"
    );
    $stmt->execute();
    $recipients = $stmt->fetchAll();

    $title   = "⚠ Seuil critique atteint sur {$device['name']}";
    $content = sprintf(
        "CPU: %.1f%% | RAM: %.1f%% | Disque: %.1f%% sur l'appareil %s (%s)",
        $metrics['cpu_usage'],
        $metrics['ram_usage'],
        $metrics['disk_usage'],
        $device['name'],
        $device['ip_address']
    );

    foreach ($recipients as $user) {
        $notificationModel->send((int) $user['id'], $title, $content, 'dashboard');
    }
}

/**
 * Affiche une ligne de log horodatée (console + fichier si redirigé).
 */
function log_line(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}" . PHP_EOL;
}