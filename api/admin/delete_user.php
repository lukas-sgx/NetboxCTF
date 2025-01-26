<?php
require_once '../../includes/init.php';
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

// Récupérer les données
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier que l'utilisateur existe
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Utilisateur non trouvé');
    }

    // Supprimer l'utilisateur
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erreur lors de la suppression');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
