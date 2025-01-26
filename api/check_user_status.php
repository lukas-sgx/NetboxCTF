<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT is_active FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'is_active' => (bool)$user['is_active']
        ]);
    } else {
        // L'utilisateur n'existe plus dans la base de données
        session_destroy();
        echo json_encode([
            'success' => false,
            'error' => 'Utilisateur non trouvé'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database Error in check_user_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la vérification du statut'
    ]);
}
