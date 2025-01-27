<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role using the same structure as dashboard.php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtenir le nombre de machines actives
    $activeMachinesQuery = "SELECT COUNT(*) as total FROM machines WHERE is_active = 1";
    $activeMachinesStmt = $db->prepare($activeMachinesQuery);
    $activeMachinesStmt->execute();
    $activeMachines = $activeMachinesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtenir le nombre d'utilisateurs actifs
    $activeUsersQuery = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
    $activeUsersStmt = $db->prepare($activeUsersQuery);
    $activeUsersStmt->execute();
    $activeUsers = $activeUsersStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtenir le nombre de salles actives
    $activeRoomsQuery = "SELECT COUNT(*) as total FROM rooms WHERE is_active = 1";
    $activeRoomsStmt = $db->prepare($activeRoomsQuery);
    $activeRoomsStmt->execute();
    $activeRooms = $activeRoomsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Retourner les statistiques
    echo json_encode([
        'success' => true,
        'stats' => [
            'active_machines' => (int)$activeMachines,
            'active_users' => (int)$activeUsers,
            'active_rooms' => (int)$activeRooms
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_stats.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue',
        'debug' => $e->getMessage()
    ]);
}
