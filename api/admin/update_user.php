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
    echo json_encode(['error' => 'ID utilisateur manquant']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $updateFields = [];
    $params = ['id' => $data['id']];

    if (isset($data['username'])) {
        $updateFields[] = 'username = :username';
        $params['username'] = $data['username'];
    }
    if (isset($data['email'])) {
        $updateFields[] = 'email = :email';
        $params['email'] = $data['email'];
    }
    if (isset($data['is_active'])) {
        $updateFields[] = 'is_active = :is_active';
        $params['is_active'] = $data['is_active'] ? 1 : 0;
    }
    if (isset($data['role'])) {
        $updateFields[] = 'role = :role';
        $params['role'] = $data['role'];
    }
    if (isset($data['points'])) {
        $updateFields[] = 'points = :points';
        $params['points'] = intval($data['points']);
    }

    if (!empty($updateFields)) {
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Aucune donnée à mettre à jour'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in update_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour de l\'utilisateur'
    ]);
}
