<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get all sessions for the user
    $query = "SELECT 
                ru.*,
                r.name as room_name,
                r.id as room_id,
                (SELECT COUNT(*) 
                 FROM room_users ru2 
                 WHERE ru2.room_id = r.id AND ru2.active = 1) as active_users
              FROM room_users ru
              JOIN rooms r ON ru.room_id = r.id
              WHERE ru.user_id = :user_id
              ORDER BY ru.start_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Force clean all sessions
    $query = "UPDATE room_users 
              SET active = 0,
                  end_time = NOW(),
                  container_name = NULL,
                  container_ip = NULL
              WHERE user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    // Get sessions after cleaning
    $query = "SELECT 
                ru.*,
                r.name as room_name,
                r.id as room_id,
                (SELECT COUNT(*) 
                 FROM room_users ru2 
                 WHERE ru2.room_id = r.id AND ru2.active = 1) as active_users
              FROM room_users ru
              JOIN rooms r ON ru.room_id = r.id
              WHERE ru.user_id = :user_id
              ORDER BY ru.start_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $cleaned_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'before_cleaning' => $sessions,
        'after_cleaning' => $cleaned_sessions,
        'message' => 'All sessions have been forcefully cleaned'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
