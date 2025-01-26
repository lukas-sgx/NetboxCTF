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
    // Vérifier si le processus OpenVPN est en cours d'exécution
    exec('pgrep openvpn', $output, $return_var);
    $is_running = $return_var === 0;

    echo json_encode([
        'success' => true,
        'is_running' => $is_running
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error checking VPN status: ' . $e->getMessage()
    ]);
}
