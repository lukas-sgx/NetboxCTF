<?php
session_start();
require_once '../../config/database.php';
require_once '../../admin/security/SecurityMonitor.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Headers pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$db = $database->getConnection();
$securityMonitor = new SecurityMonitor($db);

// Traiter la requête en fonction de la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Récupérer la liste des IPs bloquées
            $stmt = $db->query("SELECT * FROM blocked_ips WHERE blocked_until > NOW() ORDER BY blocked_at DESC");
            $blockedIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $blockedIPs]);
            break;

        case 'POST':
            // Bloquer une IP manuellement
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ip_address']) || !filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
                throw new Exception("Adresse IP invalide");
            }
            
            $duration = isset($data['duration']) ? intval($data['duration']) : 15; // durée en minutes
            $reason = isset($data['reason']) ? $data['reason'] : "Blocage manuel par un administrateur";
            
            $stmt = $db->prepare("INSERT INTO blocked_ips (ip_address, blocked_at, blocked_until, reason) 
                VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)");
            $stmt->execute([$data['ip_address'], $duration, $reason]);
            
            echo json_encode([
                'success' => true, 
                'message' => "L'IP {$data['ip_address']} a été bloquée pour $duration minutes"
            ]);
            break;

        case 'DELETE':
            // Débloquer une IP
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['ip_address'])) {
                throw new Exception("Adresse IP non spécifiée");
            }
            
            if ($securityMonitor->unblockIP(
                $data['ip_address'],
                $_SESSION['user']['username'],
                $data['notes'] ?? null
            )) {
                echo json_encode([
                    'success' => true,
                    'message' => "L'IP {$data['ip_address']} a été débloquée"
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "L'IP {$data['ip_address']} n'était pas bloquée"
                ]);
            }
            break;

        default:
            throw new Exception("Méthode HTTP non supportée");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
