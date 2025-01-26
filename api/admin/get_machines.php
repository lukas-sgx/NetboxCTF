<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Récupération des paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Requête pour obtenir le nombre total de machines
    $countQuery = "SELECT COUNT(*) as total FROM machines";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalMachines = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalMachines / $limit);

    // Base de la requête
    $baseQuery = "SELECT m.*, 
                  (SELECT COUNT(*) FROM rooms r WHERE r.machine_id = m.id AND r.is_active = 1) as active_rooms,
                  (SELECT COUNT(*) FROM room_users ru 
                   JOIN rooms r ON ru.room_id = r.id 
                   WHERE r.machine_id = m.id AND ru.active = 1) as active_instances
                  FROM machines m";

    // Vérifier si un ID spécifique est demandé
    if (isset($_GET['id'])) {
        $query = $baseQuery . " WHERE m.id = :id ORDER BY m.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    } else {
        $query = $baseQuery . " ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates et les données
    foreach ($machines as &$machine) {
        $machine['created_at'] = date('Y-m-d H:i:s', strtotime($machine['created_at']));
        $machine['updated_at'] = $machine['updated_at'] ? date('Y-m-d H:i:s', strtotime($machine['updated_at'])) : null;
        $machine['is_active'] = (bool)$machine['is_active'];
        $machine['memory_limit'] = (int)$machine['memory_limit'];
        $machine['cpu_limit'] = (int)$machine['cpu_limit'];
    }

    echo json_encode([
        'success' => true,
        'machines' => $machines,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalMachines,
            'items_per_page' => $limit
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_machines.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des machines'
    ]);
}
