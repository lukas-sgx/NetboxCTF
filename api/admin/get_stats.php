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

    // Obtenir le nombre total d'utilisateurs actifs
    $userQuery = "SELECT COUNT(*) as total FROM users WHERE is_active = 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtenir le nombre de salles actives
    $roomQuery = "SELECT COUNT(*) as total FROM rooms WHERE is_active = 1";
    $roomStmt = $db->prepare($roomQuery);
    $roomStmt->execute();
    $activeRooms = $roomStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Obtenir le nombre total de machines actives
    $machineQuery = "SELECT COUNT(*) as total FROM machines WHERE is_active = 1";
    $machineStmt = $db->prepare($machineQuery);
    $machineStmt->execute();
    $totalMachines = $machineStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Retourner les statistiques
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalUsers' => (int)$totalUsers,
            'activeRooms' => (int)$activeRooms,
            'totalMachines' => (int)$totalMachines
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
