<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../config/database.php';

// Créer un fichier de log
$log_file = '../logs/get_vpn_config.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Get VPN config called\n", FILE_APPEND);
file_put_contents($log_file, "GET data: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isLoggedIn()) {
    file_put_contents($log_file, "Error: Not logged in\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié',
        'debug' => [
            'session_exists' => isset($_SESSION),
            'user_exists' => isset($_SESSION['user']),
            'user_id_exists' => isset($_SESSION['user']['id']),
            'session_id' => session_id(),
            'session_status' => session_status()
        ]
    ]);
    exit();
}

// Vérifier si l'ID de la room est fourni
if (!isset($_GET['room_id'])) {
    file_put_contents($log_file, "Error: No room_id provided\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Room ID is required'
    ]);
    exit;
}

$roomId = intval($_GET['room_id']);
$userId = $_SESSION['user']['id'];

file_put_contents($log_file, "Processing request for user $userId and room $roomId\n", FILE_APPEND);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Récupérer la configuration VPN de base
    $query = "SELECT * FROM vpn_config LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $vpnConfig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vpnConfig) {
        file_put_contents($log_file, "Error: VPN configuration not found\n", FILE_APPEND);
        throw new Exception('VPN configuration not found');
    }

    // Vérifier si l'utilisateur a accès à cette room
    $stmt = $pdo->prepare('SELECT r.*, ru.container_ip 
                         FROM rooms r 
                         LEFT JOIN room_users ru ON r.id = ru.room_id AND ru.user_id = :user_id 
                         WHERE r.id = :room_id 
                         AND ru.active = 1 
                         AND ru.end_time IS NULL');
    $stmt->execute([
        'room_id' => $roomId,
        'user_id' => $userId
    ]);
    
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Room data: " . print_r($room, true) . "\n", FILE_APPEND);

    if (!$room) {
        file_put_contents($log_file, "Error: Room not found\n", FILE_APPEND);
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Room not found'
        ]);
        exit;
    }

    if (!isset($room['container_ip']) || empty($room['container_ip'])) {
        file_put_contents($log_file, "Error: No container IP found\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No active container found'
        ]);
        exit;
    }

    file_put_contents($log_file, "Generating VPN config for container IP: " . $room['container_ip'] . "\n", FILE_APPEND);

    // Générer le fichier de configuration client
    $client_config = "client\n";
    $client_config .= "dev tun\n";
    $client_config .= "proto {$vpnConfig['protocol']}\n";
    $client_config .= "remote {$vpnConfig['server']} {$vpnConfig['port']}\n";
    $client_config .= "resolv-retry infinite\n";
    $client_config .= "nobind\n";
    $client_config .= "persist-key\n";
    $client_config .= "persist-tun\n";
    $client_config .= "remote-cert-tls server\n";
    $client_config .= "cipher AES-256-GCM\n";
    $client_config .= "auth SHA256\n";
    $client_config .= "key-direction 1\n";
    $client_config .= "verb 3\n\n";
    
    // Ajouter les certificats
    $client_config .= "<ca>\n" . file_get_contents('/etc/openvpn/ca.crt') . "</ca>\n";
    $client_config .= "<cert>\n" . file_get_contents('/etc/openvpn/client.crt') . "</cert>\n";
    $client_config .= "<key>\n" . file_get_contents('/etc/openvpn/client.key') . "</key>\n";
    $client_config .= "<tls-auth>\n" . file_get_contents('/etc/openvpn/ta.key') . "</tls-auth>\n";

    // Ajouter les informations spécifiques à la room
    $client_config .= "\n# Room ID: $roomId\n";
    $client_config .= "# User ID: $userId\n";
    $client_config .= "# Container IP: " . $room['container_ip'] . "\n";

    // Envoyer le fichier
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="vpn-room-' . $roomId . '.ovpn"');
    header('Content-Length: ' . strlen($client_config));
    echo $client_config;
    file_put_contents($log_file, "VPN config sent successfully\n", FILE_APPEND);
    exit;

} catch (Exception $e) {
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    error_log("Error in get_vpn_config.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
