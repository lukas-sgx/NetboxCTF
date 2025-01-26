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

// Vérification des données POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['roomId']) || !isset($data['flag'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$roomId = $data['roomId'];
$flag = trim($data['flag']);
$userId = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'utilisateur est actif dans cette room
    $query = "SELECT ur.id FROM user_rooms ur WHERE ur.user_id = :user_id AND ur.room_id = :room_id AND ur.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':room_id', $roomId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'You must be active in this room to submit a flag']);
        exit();
    }

    // Vérifier le flag
    $query = "SELECT flag, points FROM rooms WHERE id = :room_id AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $roomId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Room not found']);
        exit();
    }

    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($flag !== $room['flag']) {
        echo json_encode(['success' => false, 'error' => 'Invalid flag']);
        exit();
    }

    // Vérifier si l'utilisateur a déjà validé cette room
    $query = "SELECT id FROM user_rooms WHERE user_id = :user_id AND room_id = :room_id AND completed = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':room_id', $roomId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'You have already completed this room']);
        exit();
    }

    // Mettre à jour le statut de la room pour l'utilisateur
    $query = "UPDATE user_rooms SET completed = 1, completion_date = NOW() WHERE user_id = :user_id AND room_id = :room_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':room_id', $roomId);
    $stmt->execute();

    // Ajouter les points à l'utilisateur
    $query = "UPDATE users SET points = points + :points WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':points', $room['points']);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Congratulations! Flag is correct!',
        'points' => $room['points']
    ]);

} catch (PDOException $e) {
    error_log("Database Error in submit_flag.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la validation du flag'
    ]);
} catch (Exception $e) {
    error_log("General Error in submit_flag.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue'
    ]);
}
