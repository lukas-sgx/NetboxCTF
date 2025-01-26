<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get total users
    $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total challenges (rooms)
    $query = "SELECT COUNT(*) as count FROM rooms WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $challenges = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total completed challenges
    $query = "SELECT COUNT(*) as count FROM room_users WHERE end_time IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $completed = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'users' => (int)$users,
            'challenges' => (int)$challenges,
            'completed' => (int)$completed
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}
