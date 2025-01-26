<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupérer les points totaux
    $query = "SELECT COALESCE(SUM(points), 0) as total_points FROM user_challenges WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();
    $points = $stmt->fetch(PDO::FETCH_ASSOC)['total_points'];

    // Récupérer le nombre de salles terminées
    $query = "SELECT COUNT(*) as completed_rooms FROM user_challenges WHERE user_id = :user_id AND completed = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();
    $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed_rooms'];

    // Récupérer le classement
    $query = "WITH user_points AS (
        SELECT user_id, SUM(points) as total
        FROM user_challenges
        GROUP BY user_id
    ),
    user_ranks AS (
        SELECT user_id, RANK() OVER (ORDER BY total DESC) as rank
        FROM user_points
    )
    SELECT rank
    FROM user_ranks
    WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();
    $ranking = $stmt->fetch(PDO::FETCH_ASSOC)['rank'] ?? '-';

    echo json_encode([
        'success' => true,
        'stats' => [
            'points' => $points,
            'completed_rooms' => $completed,
            'ranking' => $ranking
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in get_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
}
