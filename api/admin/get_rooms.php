<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role using the same structure as dashboard.php
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupération des paramètres de pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Requête pour obtenir le nombre total de salles
    $countQuery = "SELECT COUNT(*) as total FROM rooms";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalRooms = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRooms / $limit);

    // Requête principale
    $query = "SELECT r.*, m.name as machine_name,
              COALESCE((SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id AND ru.active = 1), 0) as active_users
              FROM rooms r
              LEFT JOIN machines m ON r.machine_id = m.id
              ORDER BY r.id DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données
    foreach ($rooms as &$room) {
        $room['is_active'] = (bool)$room['is_active'];
        $room['active_users'] = (int)$room['active_users'];
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalRooms,
            'items_per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur'
    ]);
}
