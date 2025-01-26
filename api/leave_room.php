<?php
session_start();
require_once '../config/database.php';

// Créer un fichier de log
$log_file = '../logs/leave_room.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Leave room called\n", FILE_APPEND);
file_put_contents($log_file, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    file_put_contents($log_file, "Error: No user in session\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
file_put_contents($log_file, "Raw input: " . $json . "\n", FILE_APPEND);

$data = json_decode($json, true);
file_put_contents($log_file, "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents($log_file, "JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
}

// Vérification des paramètres
if (!isset($data['room_id'])) {
    file_put_contents($log_file, "Error: No room_id in JSON data\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Room ID is required']);
    exit();
}

$room_id = $data['room_id'];
$user_id = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    file_put_contents($log_file, "Starting transaction for user_id: $user_id, room_id: $room_id\n", FILE_APPEND);

    // Log initial state
    $query = "SELECT * FROM room_users 
              WHERE user_id = :user_id 
              AND room_id = :room_id 
              ORDER BY start_time DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    $initial_state = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Initial state: " . print_r($initial_state, true) . "\n", FILE_APPEND);

    // 1. Récupérer les informations du container actif
    $query = "SELECT * FROM room_users 
              WHERE room_id = :room_id 
              AND user_id = :user_id 
              AND active = 1 
              AND end_time IS NULL
              ORDER BY start_time DESC 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $active_session = $stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "Active session: " . print_r($active_session, true) . "\n", FILE_APPEND);

    // 2. Nettoyer toutes les sessions de l'utilisateur pour cette room
    $query = "UPDATE room_users 
              SET active = 0,
                  end_time = NOW(),
                  container_name = NULL,
                  container_ip = NULL
              WHERE user_id = :user_id 
              AND room_id = :room_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':room_id', $room_id);
    $rows_affected = $stmt->execute();
    file_put_contents($log_file, "Rows affected by update: $rows_affected\n", FILE_APPEND);

    // Log after update
    $query = "SELECT * FROM room_users 
              WHERE user_id = :user_id 
              AND room_id = :room_id 
              ORDER BY start_time DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    $after_update = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($log_file, "After update: " . print_r($after_update, true) . "\n", FILE_APPEND);

    // 3. Si un container était actif, l'arrêter
    if ($active_session && $active_session['container_name']) {
        file_put_contents($log_file, "Stopping container: {$active_session['container_name']}\n", FILE_APPEND);
        exec("docker stop {$active_session['container_name']} 2>&1", $output, $return_var);
        exec("docker rm {$active_session['container_name']} 2>&1", $output, $return_var);
        file_put_contents($log_file, "Docker command output: " . print_r($output, true) . "\n", FILE_APPEND);
    }
    
    $db->commit();
    file_put_contents($log_file, "Transaction committed successfully\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully left the room',
        'debug' => [
            'initial_state' => $initial_state,
            'active_session' => $active_session,
            'rows_affected' => $rows_affected,
            'after_update' => $after_update
        ]
    ]);

} catch (Exception $e) {
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log_file, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        file_put_contents($log_file, "Transaction rolled back\n", FILE_APPEND);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'debug' => [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
