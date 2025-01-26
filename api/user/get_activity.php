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
    
    // Récupérer l'activité récente de l'utilisateur
    $query = "SELECT 
        uc.date_created as date,
        r.name as room_name,
        CASE 
            WHEN uc.completed = 1 THEN 'Terminé'
            ELSE 'En cours'
        END as action,
        uc.points
    FROM user_challenges uc
    JOIN rooms r ON uc.room_id = r.id
    WHERE uc.user_id = :user_id
    ORDER BY uc.date_created DESC
    LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();
    
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'activity' => $activity
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_activity.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'query' => $query
        ]
    ]);
} catch (Exception $e) {
    error_log("General error in get_activity.php: " . $e->getMessage());
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
