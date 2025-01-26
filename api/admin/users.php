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
            // Ajouter un utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || !isset($data['role'])) {
                throw new Exception('Données manquantes');
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role']]);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès']);
            break;
            
        case 'PUT':
            // Modifier un utilisateur
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID utilisateur manquant');
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['username'])) {
                $updates[] = "username = ?";
                $params[] = $data['username'];
            }
            if (isset($data['email'])) {
                $updates[] = "email = ?";
                $params[] = $data['email'];
            }
            if (isset($data['role'])) {
                $updates[] = "role = ?";
                $params[] = $data['role'];
            }
            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $updates[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updates)) {
                throw new Exception('Aucune donnée à mettre à jour');
            }
            
            $params[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
            break;
            
        case 'DELETE':
            // Supprimer un utilisateur
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID utilisateur manquant');
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            break;
            
        default:
            throw new Exception('Méthode non supportée');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
