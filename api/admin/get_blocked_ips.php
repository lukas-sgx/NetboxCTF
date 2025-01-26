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

    // Requête pour obtenir le nombre total d'IPs bloquées
    $countQuery = "SELECT COUNT(*) as total FROM blocked_ips";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $totalIPs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalIPs / $limit);

    // Requête principale avec pagination
    $query = "SELECT * FROM blocked_ips 
              ORDER BY blocked_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $blocked_ips = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $blocked_ips[] = [
            'ip' => $row['ip'],
            'reason' => $row['reason'],
            'blocked_at' => $row['blocked_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'blocked_ips' => $blocked_ips,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalIPs,
            'items_per_page' => $limit
        ]
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des IPs bloquées']);
}
