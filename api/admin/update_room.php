<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit();
}

// Validate required fields
$required_fields = ['id', 'name', 'max_users'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Le champ $field est requis"]);
        exit();
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Prepare query
    $query = "UPDATE rooms 
              SET name = :name,
                  description = :description,
                  machine_id = :machine_id,
                  max_users = :max_users,
                  is_active = :is_active
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    // Convert and validate values
    $id = intval($data['id']);
    $name = trim($data['name']);
    $description = isset($data['description']) ? trim($data['description']) : '';
    $machine_id = !empty($data['machine_id']) ? intval($data['machine_id']) : null;
    $max_users = intval($data['max_users']);
    $is_active = isset($data['is_active']) ? intval($data['is_active']) : 0;
    
    // Bind parameters
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':machine_id', $machine_id, PDO::PARAM_INT);
    $stmt->bindParam(':max_users', $max_users, PDO::PARAM_INT);
    $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Salle mise à jour avec succès'
        ]);
    } else {
        $error = $stmt->errorInfo();
        error_log("SQL Error in update_room.php: " . json_encode($error));
        throw new Exception('Erreur lors de la mise à jour de la salle: ' . $error[2]);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour de la salle'
    ]);
}
