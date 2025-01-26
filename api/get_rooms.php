<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

error_log("Debug: Starting get_rooms.php");

// Vérification de l'authentification
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    error_log("Debug: Authentication failed");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user']['id'];
error_log("Debug: User ID: " . $user_id);

try {
    $database = new Database();
    $db = $database->getConnection();
    error_log("Debug: Database connection established");

    // Récupérer toutes les rooms avec leur statut
    $query = "SELECT r.*,
              (SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id AND ru.active = 1) as active_users,
              (SELECT ru.active FROM room_users ru WHERE ru.room_id = r.id AND ru.user_id = :user_id AND ru.end_time IS NULL LIMIT 1) as is_user_active,
              (SELECT ru.completed FROM room_users ru WHERE ru.room_id = r.id AND ru.user_id = :user_id LIMIT 1) as is_completed
              FROM rooms r
              WHERE r.is_active = 1";

    error_log("Debug: Query: " . $query);
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    
    if (!$stmt->execute()) {
        error_log("Debug: Query execution failed. Error info: " . print_r($stmt->errorInfo(), true));
        throw new PDOException("Query execution failed: " . implode(", ", $stmt->errorInfo()));
    }
    error_log("Debug: Query executed successfully");

    $rooms = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        error_log("Debug: Processing room: " . print_r($row, true));
        
        // Déterminer le statut de la room
        $status = 'available';
        if ($row['is_completed']) {
            $status = 'completed';
        } elseif ($row['is_user_active']) {
            $status = 'joined';
        } elseif ($row['active_users'] >= $row['max_users']) {
            $status = 'full';
        }

        $rooms[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'difficulty' => $row['difficulty'],
            'points' => $row['points'],
            'time_limit' => $row['time_limit'],
            'max_users' => $row['max_users'],
            'active_users' => (int)$row['active_users'],
            'machine_type' => $row['machine_type'] ?? 'Standard',
            'status' => $status,
            'is_user_active' => (bool)$row['is_user_active'],
            'is_completed' => (bool)$row['is_completed']
        ];
    }

    error_log("Debug: Total rooms found: " . count($rooms));
    echo json_encode([
        'success' => true,
        'rooms' => $rooms
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_rooms.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Error Info: " . print_r($e->errorInfo ?? [], true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la récupération des rooms',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in get_rooms.php: " . $e->getMessage());
    error_log("Error on line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue',
        'debug' => $e->getMessage()
    ]);
}
