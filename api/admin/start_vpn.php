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
    // Vérifier si le VPN est déjà en cours d'exécution
    exec('pgrep openvpn', $output, $return_var);
    if ($return_var === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'VPN server is already running'
        ]);
        exit();
    }

    // Démarrer le serveur OpenVPN
    exec('sudo systemctl start openvpn@server', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to start VPN server');
    }

    echo json_encode([
        'success' => true,
        'message' => 'VPN server started successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error starting VPN server: ' . $e->getMessage()
    ]);
}
