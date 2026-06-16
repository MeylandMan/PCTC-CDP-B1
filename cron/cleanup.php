<?php

define('ROOT_PATH', dirname(__DIR__));
define('APP_ENV', getenv('APP_ENV') ?: 'development');

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Ce script ne peut être exécuté qu\'en ligne de commande.');
}

require_once ROOT_PATH . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo '[' . date('Y-m-d H:i:s') . "] Démarrage du nettoyage\n";

// -----------------------------------------------------------------------
// 1. Purge les métriques de plus de 90 jours
//    (garde un historique raisonnable pour les rapports mensuels)
// -----------------------------------------------------------------------
$stmt = $db->prepare(
    "DELETE FROM device_metrics WHERE collected_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
);
$stmt->execute();
echo "  - " . $stmt->rowCount() . " métriques supprimées (> 90 jours)\n";

// -----------------------------------------------------------------------
// 2. Purge les logs d'audit de plus de 180 jours
// -----------------------------------------------------------------------
$stmt = $db->prepare(
    "DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)"
);
$stmt->execute();
echo "  - " . $stmt->rowCount() . " logs supprimés (> 180 jours)\n";

// -----------------------------------------------------------------------
// 3. Purge les notifications lues de plus de 30 jours
// -----------------------------------------------------------------------
$stmt = $db->prepare(
    "DELETE FROM notifications
     WHERE is_read = 1 AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$stmt->execute();
echo "  - " . $stmt->rowCount() . " notifications lues supprimées (> 30 jours)\n";

// -----------------------------------------------------------------------
// 4. Archive automatiquement les alertes résolues de plus de 60 jours
//    (les supprime, l'historique des incidents liés reste intact)
// -----------------------------------------------------------------------
$stmt = $db->prepare(
    "DELETE FROM alerts
     WHERE status = 'resolved' AND created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)"
);
$stmt->execute();
echo "  - " . $stmt->rowCount() . " alertes résolues archivées (> 60 jours)\n";

echo '[' . date('Y-m-d H:i:s') . "] Nettoyage terminé\n";