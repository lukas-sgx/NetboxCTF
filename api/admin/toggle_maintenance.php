<?php
require_once '../../includes/session.php';

// Fonction de logging
function logDebug($message, $data = null) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

logDebug("Début de l'exécution du script toggle_maintenance.php");
logDebug("Méthode HTTP:", $_SERVER['REQUEST_METHOD']);
logDebug("Headers reçus:", getallheaders());
logDebug("Content Length:", $_SERVER['CONTENT_LENGTH'] ?? 'non défini');

// Headers CORS et sécurité
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    logDebug("Requête OPTIONS reçue - Preflight CORS");
    http_response_code(200);
    exit();
}

// Vérifier que l'utilisateur est connecté et est admin
logDebug("Vérification de l'authentification");
logDebug("Session actuelle:", $_SESSION);

if (!isAdmin()) {
    logDebug("Accès refusé - Utilisateur non admin ou non connecté");
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

logDebug("Utilisateur admin authentifié:", $_SESSION['user']);

$maintenanceConfig = __DIR__ . '/../../config/maintenance.php';
$configDir = dirname($maintenanceConfig);

logDebug("Chemins de configuration:", [
    'maintenanceConfig' => $maintenanceConfig,
    'configDir' => $configDir
]);

// Vérifier si le dossier config existe, sinon le créer
if (!file_exists($configDir)) {
    logDebug("Dossier de configuration inexistant, tentative de création");
    if (!mkdir($configDir, 0755, true)) {
        logDebug("Erreur lors de la création du dossier de configuration");
        http_response_code(500);
        echo json_encode(['error' => 'Impossible de créer le dossier de configuration']);
        exit();
    }
    logDebug("Dossier de configuration créé avec succès");
}

$defaultConfig = array(
    'enabled' => false,
    'message' => 'Notre système est actuellement en cours de maintenance.',
    'allowed_ips' => array(),
    'end_time' => null
);

// Charger la configuration existante
try {
    logDebug("Tentative de chargement de la configuration");
    if (file_exists($maintenanceConfig)) {
        logDebug("Fichier de configuration trouvé");
        $config = @include $maintenanceConfig;
        if ($config === false || !is_array($config)) {
            $error = error_get_last();
            logDebug("Erreur lors du chargement du fichier de configuration:", $error);
            $config = $defaultConfig;
        } else {
            // S'assurer que toutes les clés requises existent
            $config = array_merge($defaultConfig, $config);
            // S'assurer que allowed_ips est un tableau
            if (!isset($config['allowed_ips']) || !is_array($config['allowed_ips'])) {
                $config['allowed_ips'] = array();
            }
            logDebug("Configuration chargée avec succès:", $config);
        }
    } else {
        logDebug("Fichier de configuration non trouvé, utilisation de la configuration par défaut");
        $config = $defaultConfig;
    }
} catch (Exception $e) {
    logDebug("Exception lors du chargement de la configuration:", $e->getMessage());
    $config = $defaultConfig;
}

// GET request pour obtenir l'état actuel
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logDebug("Requête GET reçue - Envoi de la configuration actuelle");
    echo json_encode([
        'success' => true,
        'config' => $config
    ]);
    exit();
}

// POST request pour modifier la configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logDebug("Requête POST reçue");
    
    // Vérifier si le Content-Type est correct
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    logDebug("Content-Type reçu:", $contentType);
    
    if (strpos($contentType, 'application/json') === false) {
        logDebug("Content-Type invalide");
        http_response_code(400);
        echo json_encode([
            'error' => 'Content-Type doit être application/json',
            'received' => $contentType
        ]);
        exit();
    }
    
    $rawInput = file_get_contents('php://input');
    logDebug("Données brutes reçues:", $rawInput);
    
    if (empty($rawInput)) {
        logDebug("Aucune donnée reçue - Basculement de l'état de maintenance");
        // Si aucune donnée n'est reçue, on bascule simplement l'état de maintenance
        $config['enabled'] = !$config['enabled'];
        // S'assurer que allowed_ips est initialisé
        if (!isset($config['allowed_ips']) || !is_array($config['allowed_ips'])) {
            $config['allowed_ips'] = array();
        }
        logDebug("Nouvel état de maintenance:", $config['enabled']);
    } else {
        $input = json_decode($rawInput, true);
        logDebug("Données décodées:", $input);
        
        if ($input === null) {
            $jsonError = json_last_error_msg();
            logDebug("Erreur de décodage JSON:", $jsonError);
            http_response_code(400);
            echo json_encode([
                'error' => 'JSON invalide',
                'details' => $jsonError,
                'received' => $rawInput
            ]);
            exit();
        }

        // Mise à jour de la configuration
        if (isset($input['enabled'])) {
            logDebug("Mise à jour du statut enabled:", $input['enabled']);
            $config['enabled'] = filter_var($input['enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($input['message'])) {
            logDebug("Mise à jour du message:", $input['message']);
            $config['message'] = filter_var($input['message'], FILTER_SANITIZE_STRING);
        }

        if (isset($input['end_time'])) {
            logDebug("Mise à jour de la date de fin:", $input['end_time']);
            $config['end_time'] = filter_var($input['end_time'], FILTER_SANITIZE_STRING);
        }
        
        // S'assurer que allowed_ips est initialisé comme un tableau
        if (!isset($config['allowed_ips']) || !is_array($config['allowed_ips'])) {
            $config['allowed_ips'] = array();
        }

        if (isset($input['allowed_ips']) && is_array($input['allowed_ips'])) {
            logDebug("Mise à jour des IPs autorisées:", $input['allowed_ips']);
            $config['allowed_ips'] = array_filter($input['allowed_ips'], function($ip) {
                $isValid = filter_var($ip, FILTER_VALIDATE_IP) !== false;
                logDebug("Validation IP $ip:", $isValid ? "valide" : "invalide");
                return $isValid;
            });
        }
    }

    // Toujours ajouter l'IP de l'admin
    $adminIp = $_SERVER['REMOTE_ADDR'];
    logDebug("IP de l'admin:", $adminIp);
    
    // S'assurer une dernière fois que allowed_ips est un tableau
    if (!isset($config['allowed_ips']) || !is_array($config['allowed_ips'])) {
        $config['allowed_ips'] = array();
    }
    
    if (!in_array($adminIp, $config['allowed_ips'])) {
        logDebug("Ajout de l'IP de l'admin à la liste des IPs autorisées");
        $config['allowed_ips'][] = $adminIp;
    }

    // Sauvegarder la configuration
    try {
        logDebug("Tentative de sauvegarde de la configuration");
        $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
        logDebug("Contenu à sauvegarder:", $configContent);
        
        if (file_put_contents($maintenanceConfig, $configContent, LOCK_EX) === false) {
            throw new Exception("Échec de l'écriture du fichier");
        }
        logDebug("Configuration sauvegardée avec succès");
        
        echo json_encode([
            'success' => true,
            'config' => $config,
            'message' => 'Mode maintenance ' . ($config['enabled'] ? 'activé' : 'désactivé')
        ]);
    } catch (Exception $e) {
        logDebug("Erreur lors de la sauvegarde:", $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'Erreur lors de la sauvegarde',
            'details' => $e->getMessage()
        ]);
    }
    exit();
}

logDebug("Méthode HTTP non autorisée:", $_SERVER['REQUEST_METHOD']);
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
exit();
