<?php
session_start();
require_once '../../config/database.php';

// Vérification de l'authentification et des droits admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['enabled'])) {
            throw new Exception('Le statut de maintenance est requis');
        }

        // Préparer le contenu de la configuration
        $config = [
            'enabled' => (bool)$data['enabled'],
            'message' => $data['message'] ?? 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.',
            'allowed_ips' => array_unique(array_filter($data['allowed_ips'] ?? [])),
        ];

        // Ajouter l'IP de l'admin automatiquement
        if (!in_array($_SERVER['REMOTE_ADDR'], $config['allowed_ips'])) {
            $config['allowed_ips'][] = $_SERVER['REMOTE_ADDR'];
        }

        // Sauvegarder la configuration
        $configFile = '../../config/maintenance.php';
        $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
        
        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception('Erreur lors de la sauvegarde de la configuration');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configuration de maintenance mise à jour',
            'config' => $config
        ]);
    } else {
        throw new Exception('Méthode non supportée');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
