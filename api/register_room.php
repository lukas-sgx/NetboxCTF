<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié',
        'debug' => [
            'session_exists' => isset($_SESSION),
            'user_exists' => isset($_SESSION['user']),
            'user_id_exists' => isset($_SESSION['user']['id']),
            'session_id' => session_id(),
            'session_status' => session_status()
        ]
    ]);
    exit();
}

// Vérification des paramètres
$log_file = __DIR__ . '/../logs/register_room.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Register room called\n", FILE_APPEND);

$json = file_get_contents('php://input');
file_put_contents($log_file, "Raw input: " . $json . "\n", FILE_APPEND);

$data = json_decode($json, true);
file_put_contents($log_file, "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents($log_file, "JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
}

file_put_contents($log_file, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

if (!isset($data['room_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit();
}

$room_id = $data['room_id'];
$user_id = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // 1. Nettoyer toutes les sessions incomplètes ou terminées
    $query = "UPDATE room_users 
              SET active = 0,
                  end_time = COALESCE(end_time, NOW())
              WHERE user_id = :user_id 
              AND (
                  container_name IS NULL 
                  OR container_ip IS NULL 
                  OR end_time IS NOT NULL 
                  OR start_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
              )";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    // 2. Vérifier s'il existe déjà une session valide dans cette room
    $query = "SELECT COUNT(*) as count
              FROM room_users
              WHERE user_id = :user_id 
              AND room_id = :room_id 
              AND active = 1 
              AND end_time IS NULL
              AND container_name IS NOT NULL
              AND container_ip IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    
    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        $db->commit();
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà une session active dans cette room']);
        exit();
    }

    // 3. Vérifier les limites de la room
    $query = "SELECT r.max_users, r.time_limit,
                     (SELECT COUNT(*) 
                      FROM room_users ru 
                      WHERE ru.room_id = r.id 
                      AND ru.active = 1) as current_users
              FROM rooms r
              WHERE r.id = :room_id
              AND r.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Room introuvable ou inactive']);
        exit();
    }

    if ($room['current_users'] >= $room['max_users']) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'La room est pleine']);
        exit();
    }

    // 4. Créer une nouvelle session
    $query = "INSERT INTO room_users (room_id, user_id, time_limit) 
              VALUES (:room_id, :user_id, :time_limit)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':time_limit', $room['time_limit']);
    $stmt->execute();
    
    $session_id = $db->lastInsertId();
    
    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Session créée avec succès',
        'session_id' => $session_id
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in register_room.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("General error in register_room.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
}
