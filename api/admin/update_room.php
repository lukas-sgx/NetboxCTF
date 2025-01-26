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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de la salle manquant']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $updateFields = [];
    $params = ['id' => $data['id']];

    if (isset($data['name'])) {
        $updateFields[] = 'name = :name';
        $params['name'] = $data['name'];
    }
    if (isset($data['description'])) {
        $updateFields[] = 'description = :description';
        $params['description'] = $data['description'];
    }
    if (isset($data['machine_id'])) {
        $updateFields[] = 'machine_id = :machine_id';
        $params['machine_id'] = intval($data['machine_id']);
    }
    if (isset($data['difficulty'])) {
        $updateFields[] = 'difficulty = :difficulty';
        $params['difficulty'] = $data['difficulty'];
    }
    if (isset($data['points'])) {
        $updateFields[] = 'points = :points';
        $params['points'] = intval($data['points']);
    }
    if (isset($data['max_users'])) {
        $updateFields[] = 'max_users = :max_users';
        $params['max_users'] = intval($data['max_users']);
    }
    if (isset($data['time_limit'])) {
        $updateFields[] = 'time_limit = :time_limit';
        $params['time_limit'] = intval($data['time_limit']);
    }
    if (isset($data['is_active'])) {
        $updateFields[] = 'is_active = :is_active';
        $params['is_active'] = $data['is_active'] ? 1 : 0;
    }

    if (!empty($updateFields)) {
        $query = "UPDATE rooms SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Salle mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Aucune donnée à mettre à jour'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in update_room.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour de la salle'
    ]);
}
