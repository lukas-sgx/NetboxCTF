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
    echo json_encode(['error' => 'ID de la machine manquant']);
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
    if (isset($data['docker_image'])) {
        $updateFields[] = 'docker_image = :docker_image';
        $params['docker_image'] = $data['docker_image'];
    }
    if (isset($data['description'])) {
        $updateFields[] = 'description = :description';
        $params['description'] = $data['description'];
    }
    if (isset($data['cpu_limit'])) {
        $updateFields[] = 'cpu_limit = :cpu_limit';
        $params['cpu_limit'] = intval($data['cpu_limit']);
    }
    if (isset($data['memory_limit'])) {
        $updateFields[] = 'memory_limit = :memory_limit';
        $params['memory_limit'] = intval($data['memory_limit']);
    }
    if (isset($data['exposed_ports'])) {
        $updateFields[] = 'exposed_ports = :exposed_ports';
        $params['exposed_ports'] = $data['exposed_ports'];
    }
    if (isset($data['is_active'])) {
        $updateFields[] = 'is_active = :is_active';
        $params['is_active'] = $data['is_active'] ? 1 : 0;
    }

    if (!empty($updateFields)) {
        $query = "UPDATE machines SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Machine mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Aucune donnée à mettre à jour'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in update_machine.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour de la machine'
    ]);
}
