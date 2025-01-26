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
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'stats':
            // Récupérer les statistiques globales
            $stats = [];
            
            // Total des utilisateurs
            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            $stats['totalUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Salles actives
            $stmt = $db->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'active'");
            $stats['activeRooms'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Conteneurs actifs
            $stmt = $db->query("SELECT COUNT(*) as total FROM room_users WHERE active = 1 AND container_name IS NOT NULL");
            $stats['activeContainers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'users':
            // Liste des utilisateurs
            $stmt = $db->query("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'rooms':
            // Liste des salles
            $query = "SELECT r.*, 
                     (SELECT COUNT(*) FROM user_rooms ur WHERE ur.room_id = r.id AND ur.status = 'active') as active_users,
                     m.name as machine_name
                     FROM rooms r 
                     LEFT JOIN machines m ON r.machine_id = m.id 
                     ORDER BY r.created_at DESC";
            $stmt = $db->query($query);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rooms]);
            break;
            
        case 'machines':
            // Liste des machines
            $stmt = $db->query("SELECT * FROM machines ORDER BY created_at DESC");
            $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $machines]);
            break;
            
        default:
            throw new Exception('Action non valide');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
