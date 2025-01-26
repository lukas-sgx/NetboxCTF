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

if (!isset($_POST['username'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username is required']);
    exit();
}

$username = $_POST['username'];

try {
    // Déconnecter le client via le management interface d'OpenVPN
    exec("sudo pkill -SIGTERM -f \"openvpn.*$username\"", $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to disconnect client');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Client disconnected successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error disconnecting client: ' . $e->getMessage()
    ]);
}
