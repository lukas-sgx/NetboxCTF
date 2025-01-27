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

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID non fourni']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Préparer et exécuter la requête
    $query = "SELECT * FROM machines WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $_GET['id']]);
    
    // Récupérer la machine
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$machine) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Machine non trouvée']);
        exit();
    }
    
    // Renvoyer les données
    echo json_encode([
        'success' => true,
        'machine' => $machine
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}
