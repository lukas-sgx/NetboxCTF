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

// Récupération des paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Requête pour obtenir le nombre total de salles
    $countQuery = "SELECT COUNT(*) as total FROM rooms";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalRooms = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRooms / $limit);

    // Base de la requête
    $baseQuery = "SELECT r.*, m.name as machine_name,
                  (SELECT COUNT(*) FROM room_users ru WHERE ru.room_id = r.id AND ru.active = 1) as active_users
                  FROM rooms r
                  LEFT JOIN machines m ON r.machine_id = m.id";

    // Vérifier si un ID spécifique est demandé
    if (isset($_GET['id'])) {
        $query = $baseQuery . " WHERE r.id = :id ORDER BY r.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    } else {
        $query = $baseQuery . " ORDER BY r.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates et les données
    foreach ($rooms as &$room) {
        $room['created_at'] = date('Y-m-d H:i:s', strtotime($room['created_at']));
        $room['is_active'] = (bool)$room['is_active'];
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

} catch (PDOException $e) {
    error_log("Database Error in get_rooms.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des salles'
    ]);
}
