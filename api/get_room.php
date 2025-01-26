<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Vérification du paramètre
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Room ID is required']);
    exit();
}

$room_id = $_GET['id'];
$user_id = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer les informations de la room et le statut de l'utilisateur
    $query = "SELECT r.*,
              COALESCE((SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id AND ru.active = 1), 0) as active_users,
              COALESCE((SELECT ru.active FROM room_users ru WHERE ru.room_id = r.id AND ru.user_id = :user_id AND ru.end_time IS NULL), 0) as is_user_active,
              COALESCE((SELECT ru.completed FROM room_users ru WHERE ru.room_id = r.id AND ru.user_id = :user_id), 0) as is_completed
              FROM rooms r
              WHERE r.id = :room_id AND r.is_active = 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Room not found']);
        exit();
    }

    // Déterminer le statut
    $status = 'available';
    if ($room['is_completed']) {
        $status = 'completed';
    } elseif ($room['is_user_active']) {
        $status = 'joined';
    } elseif ($room['active_users'] >= $room['max_users']) {
        $status = 'full';
    }

    $response = [
        'success' => true,
        'room' => [
            'id' => $room['id'],
            'name' => $room['name'],
            'description' => $room['description'],
            'difficulty' => $room['difficulty'],
            'points' => $room['points'],
            'time_limit' => $room['time_limit'],
            'max_users' => $room['max_users'],
            'active_users' => $room['active_users'],
            'machine_type' => $room['machine_type'] ?? 'Standard',
            'status' => $status,
            'is_user_active' => (bool)$room['is_user_active'],
            'is_completed' => (bool)$room['is_completed']
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error in get_room.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la récupération des détails de la room'
    ]);
} catch (Exception $e) {
    error_log("General Error in get_room.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue'
    ]);
}
