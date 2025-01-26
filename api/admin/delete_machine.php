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
$machineId = $data['id'] ?? null;

if (!$machineId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de la machine manquant']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier que la machine existe
    $stmt = $db->prepare("SELECT id FROM machines WHERE id = ?");
    $stmt->execute([$machineId]);
    if (!$stmt->fetch()) {
        throw new Exception('Machine non trouvée');
    }

    // Commencer une transaction
    $db->beginTransaction();

    try {
        // Vérifier si la machine est utilisée dans des salles
        $stmt = $db->prepare("SELECT id FROM rooms WHERE machine_id = ?");
        $stmt->execute([$machineId]);
        if ($stmt->fetch()) {
            throw new Exception('Cette machine est utilisée dans une ou plusieurs salles');
        }

        // Supprimer la machine
        $stmt = $db->prepare("DELETE FROM machines WHERE id = ?");
        $stmt->execute([$machineId]);

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
