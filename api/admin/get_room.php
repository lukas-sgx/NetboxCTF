<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role using the same structure as dashboard.php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Préparer et exécuter la requête
    $query = "SELECT r.*, m.name as machine_name 
              FROM rooms r 
              LEFT JOIN machines m ON r.machine_id = m.id 
              WHERE r.id = :id";
              
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', intval($_GET['id']), PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de l'exécution de la requête");
    }
    
    // Récupérer la salle
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Salle non trouvée']);
        exit();
    }
    
    // S'assurer que is_active est un booléen
    $room['is_active'] = (bool)$room['is_active'];
    
    // Renvoyer les données
    echo json_encode([
        'success' => true,
        'room' => $room
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur'
    ]);
}
