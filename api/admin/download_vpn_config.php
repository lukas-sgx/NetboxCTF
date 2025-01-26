<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer la configuration VPN
    $query = "SELECT * FROM vpn_config LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        throw new Exception('VPN configuration not found');
    }

    // Générer le fichier de configuration client
    $client_config = "client\n";
    $client_config .= "dev tun\n";
    $client_config .= "proto {$config['protocol']}\n";
    $client_config .= "remote {$config['server']} {$config['port']}\n";
    $client_config .= "resolv-retry infinite\n";
    $client_config .= "nobind\n";
    $client_config .= "persist-key\n";
    $client_config .= "persist-tun\n";
    $client_config .= "remote-cert-tls server\n";
    $client_config .= "cipher AES-256-GCM\n";
    $client_config .= "auth SHA256\n";
    $client_config .= "key-direction 1\n";
    $client_config .= "verb 3\n";

    // Ajouter les certificats
    $client_config .= "\n<ca>\n" . file_get_contents('/etc/openvpn/ca.crt') . "\n</ca>\n";
    $client_config .= "\n<cert>\n" . file_get_contents('/etc/openvpn/client.crt') . "\n</cert>\n";
    $client_config .= "\n<key>\n" . file_get_contents('/etc/openvpn/client.key') . "\n</key>\n";
    $client_config .= "\n<tls-auth>\n" . file_get_contents('/etc/openvpn/ta.key') . "\n</tls-auth>\n";

    // Envoyer le fichier
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="client.ovpn"');
    header('Content-Length: ' . strlen($client_config));
    echo $client_config;
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error generating VPN configuration: ' . $e->getMessage()
    ]);
}
