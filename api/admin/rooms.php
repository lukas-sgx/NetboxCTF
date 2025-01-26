<?php
session_start();
require_once '../../config/database.php';

// Vérification de l'authentification et des droits admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // Créer une salle
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['machine_id'])) {
                throw new Exception('Données manquantes');
            }
            
            $stmt = $db->prepare("INSERT INTO rooms (name, machine_id, status, created_at) VALUES (?, ?, 'active', NOW())");
            $stmt->execute([$data['name'], $data['machine_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Salle créée avec succès']);
            break;
            
        case 'PUT':
            // Modifier une salle
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID salle manquant');
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updates[] = "name = ?";
                $params[] = $data['name'];
            }
            if (isset($data['machine_id'])) {
                $updates[] = "machine_id = ?";
                $params[] = $data['machine_id'];
            }
            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (empty($updates)) {
                throw new Exception('Aucune donnée à mettre à jour');
            }
            
            $params[] = $id;
            $sql = "UPDATE rooms SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Salle mise à jour avec succès']);
            break;
            
        case 'DELETE':
            // Supprimer une salle
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID salle manquant');
            }
            
            // Vérifier s'il y a des utilisateurs actifs
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_rooms WHERE room_id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($activeUsers > 0) {
                throw new Exception('Impossible de supprimer une salle avec des utilisateurs actifs');
            }
            
            $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Salle supprimée avec succès']);
            break;
            
        default:
            throw new Exception('Méthode non supportée');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
