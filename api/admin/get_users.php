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

    // Requête pour obtenir le nombre total d'utilisateurs
    $countQuery = "SELECT COUNT(*) as total FROM users";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalUsers / $limit);

    // Vérifier si un ID spécifique est demandé
    if (isset($_GET['id'])) {
        $query = "SELECT id, username, email, role, points, created_at, last_login, is_active 
                  FROM users 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    } else {
        $query = "SELECT id, username, email, role, points, created_at, last_login, is_active 
                  FROM users 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates
    foreach ($users as &$user) {
        $user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
        $user['last_login'] = $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : null;
        $user['is_admin'] = ($user['role'] === 'admin');
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalUsers,
            'items_per_page' => $limit
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database Error in get_users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des utilisateurs'
    ]);
}
