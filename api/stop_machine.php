<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Vérification des paramètres
if (!isset($_POST['room_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID is required']);
    exit();
}

$room_id = $_POST['room_id'];
$user_id = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Get container information
    $query = "SELECT container_name FROM room_users 
              WHERE room_id = :room_id 
              AND user_id = :user_id 
              AND end_time IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $container = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($container && $container['container_name']) {
        // Stop the container
        exec("docker stop {$container['container_name']} 2>&1", $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Failed to stop container: ' . implode("\n", $output));
        }

        // Update container information
        $query = "UPDATE room_users SET 
                    container_name = NULL,
                    container_ip = NULL
                 WHERE room_id = :room_id 
                 AND user_id = :user_id 
                 AND end_time IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Machine stopped successfully'
        ]);
    } else {
        $db->rollBack();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No active machine',
            'message' => 'No active machine found for this room'
        ]);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
