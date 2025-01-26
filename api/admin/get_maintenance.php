<?php
require_once '../../includes/session.php';

// Headers CORS et sécurité
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Vérifier que l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

$maintenanceConfig = __DIR__ . '/../../config/maintenance.php';

if (!file_exists($maintenanceConfig)) {
    // Créer le fichier avec la configuration par défaut
    $defaultConfig = array(
        'enabled' => false,
        'message' => 'Notre système est actuellement en cours de maintenance.',
        'allowed_ips' => array('127.0.0.1', '::1'),
        'end_time' => null
    );
    
    $configContent = "<?php\nreturn " . var_export($defaultConfig, true) . ";\n";
    file_put_contents($maintenanceConfig, $configContent);
    echo json_encode([
        'success' => true,
        'maintenance' => $defaultConfig
    ]);
    exit();
}

$config = require $maintenanceConfig;
echo json_encode([
    'success' => true,
    'maintenance' => $config
]);
