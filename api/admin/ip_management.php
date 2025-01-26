<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

// Vérification de l'authentification admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Récupérer la liste des IPs bloquées
        try {
            $query = "SELECT * FROM blocked_ips ORDER BY blocked_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'blocked_ips' => $ips]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des IPs bloquées']);
        }
        break;

    case 'POST':
        // Bloquer une nouvelle IP
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ip']) || !isset($data['reason'])) {
            http_response_code(400);
            echo json_encode(['error' => 'IP et raison requises']);
            exit();
        }

        // Valider l'IP
        if (!filter_var($data['ip'], FILTER_VALIDATE_IP)) {
            http_response_code(400);
            echo json_encode(['error' => 'IP invalide']);
            exit();
        }

        try {
            $query = "INSERT INTO blocked_ips (ip_address, reason, blocked_at) VALUES (:ip, :reason, NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':ip' => $data['ip'],
                ':reason' => $data['reason']
            ]);
            
            // Log l'action
            $logQuery = "INSERT INTO security_logs (ip_address, event, details) VALUES (:ip, 'IP_BLOCKED', :details)";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([
                ':ip' => $data['ip'],
                ':details' => "IP bloquée. Raison: " . $data['reason']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'IP bloquée avec succès']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors du blocage de l\'IP']);
        }
        break;

    case 'DELETE':
        // Débloquer une IP
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ip'])) {
            http_response_code(400);
            echo json_encode(['error' => 'IP requise']);
            exit();
        }

        try {
            $query = "DELETE FROM blocked_ips WHERE ip_address = :ip";
            $stmt = $db->prepare($query);
            $stmt->execute([':ip' => $data['ip']]);
            
            // Log l'action
            $logQuery = "INSERT INTO security_logs (ip_address, event, details) VALUES (:ip, 'IP_UNBLOCKED', 'IP débloquée')";
            $logStmt = $db->prepare($logQuery);
            $logStmt->execute([':ip' => $data['ip']]);
            
            echo json_encode(['success' => true, 'message' => 'IP débloquée avec succès']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors du déblocage de l\'IP']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
