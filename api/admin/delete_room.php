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
$roomId = $data['id'] ?? null;

if (!$roomId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de la salle manquant']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier que la salle existe
    $stmt = $db->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    if (!$stmt->fetch()) {
        throw new Exception('Salle non trouvée');
    }

    // Commencer une transaction
    $db->beginTransaction();

    try {
        // Supprimer d'abord les utilisateurs de la salle
        $stmt = $db->prepare("DELETE FROM room_users WHERE room_id = ?");
        $stmt->execute([$roomId]);

        // Supprimer la salle
        $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$roomId]);

        // Valider la transaction
        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
