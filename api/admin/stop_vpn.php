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

try {
    // Vérifier si le VPN est en cours d'exécution
    exec('pgrep openvpn', $output, $return_var);
    if ($return_var !== 0) {
        echo json_encode([
            'success' => false,
            'error' => 'VPN server is not running'
        ]);
        exit();
    }

    // Arrêter le serveur OpenVPN
    exec('sudo systemctl stop openvpn@server', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to stop VPN server');
    }

    echo json_encode([
        'success' => true,
        'message' => 'VPN server stopped successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error stopping VPN server: ' . $e->getMessage()
    ]);
}
