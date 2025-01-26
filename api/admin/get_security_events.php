<?php
session_start();
require_once '../../config/database.php';

// Vérification de la session admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Récupération des paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $database = new Database();
    $db = $database->getConnection();

    // Requête pour obtenir le nombre total d'événements
    $countQuery = "SELECT COUNT(*) as total FROM login_attempts";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalEvents = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalEvents / $limit);

    // Requête principale avec pagination
    $query = "SELECT la.*, u.username 
              FROM login_attempts la 
              LEFT JOIN users u ON la.user_id = u.id 
              ORDER BY la.attempt_time DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $events = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[] = [
            'username' => $row['username'] ?? 'Unknown',
            'ip' => $row['ip_address'],
            'timestamp' => $row['attempt_time'],
            'success' => (bool)$row['success']
        ];
    }

    echo json_encode([
        'success' => true,
        'events' => $events,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalEvents,
            'items_per_page' => $limit
        ]
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des événements de sécurité']);
}
