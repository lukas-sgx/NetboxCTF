<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['server']) || !isset($_POST['port']) || !isset($_POST['protocol'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Mettre à jour la configuration dans la base de données
    $query = "INSERT INTO vpn_config (server, port, protocol, updated_at) 
              VALUES (:server, :port, :protocol, NOW())
              ON DUPLICATE KEY UPDATE 
              server = VALUES(server),
              port = VALUES(port),
              protocol = VALUES(protocol),
              updated_at = NOW()";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':server', $_POST['server']);
    $stmt->bindParam(':port', $_POST['port']);
    $stmt->bindParam(':protocol', $_POST['protocol']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save VPN configuration');
    }

    // Générer le fichier de configuration OpenVPN
    $config_content = "server 10.8.0.0 255.255.255.0\n";
    $config_content .= "port {$_POST['port']}\n";
    $config_content .= "proto {$_POST['protocol']}\n";
    // Ajouter d'autres configurations OpenVPN selon vos besoins

    $config_file = '/etc/openvpn/server.conf';
    if (file_put_contents($config_file, $config_content) === false) {
        throw new Exception('Failed to write OpenVPN configuration file');
    }

    echo json_encode([
        'success' => true,
        'message' => 'VPN configuration saved successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error saving VPN configuration: ' . $e->getMessage()
    ]);
}
