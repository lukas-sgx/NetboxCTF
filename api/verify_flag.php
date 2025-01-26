<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Vérification des paramètres
if (!isset($_POST['room_id']) || !isset($_POST['flag'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID and flag are required']);
    exit();
}

$room_id = $_POST['room_id'];
$flag = $_POST['flag'];
$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Vérifier si l'utilisateur est inscrit à cette salle
    $query = "SELECT ru.* FROM room_users ru 
              WHERE ru.room_id = :room_id 
              AND ru.user_id = :user_id 
              AND ru.end_time IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Not registered',
            'message' => 'You are not currently registered in this room.'
        ]);
        exit();
    }

    // Vérifier le flag
    $query = "SELECT * FROM flags WHERE room_id = :room_id AND flag_hash = :flag_hash";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':flag_hash', hash('sha256', $flag));
    $stmt->execute();
    
    if ($flag_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Marquer comme complété et ajouter les points
        $query = "UPDATE room_users SET 
                    completed = 1,
                    end_time = NOW()
                 WHERE room_id = :room_id 
                 AND user_id = :user_id 
                 AND end_time IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Ajouter les points à l'utilisateur
        $query = "UPDATE users SET 
                    points = points + (SELECT points FROM rooms WHERE id = :room_id)
                 WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Congratulations! Flag is correct.',
            'points_earned' => $flag_data['points']
        ]);
    } else {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Invalid flag',
            'message' => 'The submitted flag is incorrect. Please try again.'
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
