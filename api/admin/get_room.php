<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role using the same structure as dashboard.php
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID non fourni']);
    exit();
}

$roomId = intval($_GET['id']);

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer les informations de la salle
    $query = "SELECT r.*, m.name as machine_name 
              FROM rooms r 
              LEFT JOIN machines m ON r.machine_id = m.id 
              WHERE r.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $roomId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Room data: " . json_encode($room)); // Debug log
        echo json_encode([
            'success' => true,
            'room' => $room
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Salle non trouvée'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
