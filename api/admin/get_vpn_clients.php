<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Obtenir la liste des clients connectés via le management interface d'OpenVPN
    $clients = [];
    
    // Vérifier si le VPN est en cours d'exécution
    exec('pgrep openvpn', $output, $return_var);
    if ($return_var === 0) {
        // Lire le fichier de statut OpenVPN
        $status_file = '/etc/openvpn/openvpn-status.log';
        if (file_exists($status_file)) {
            $status = file_get_contents($status_file);
            $lines = explode("\n", $status);
            
            $reading_clients = false;
            foreach ($lines as $line) {
                if (trim($line) === 'ROUTING TABLE') {
                    break;
                }
                if ($reading_clients) {
                    $parts = preg_split('/,/', $line);
                    if (count($parts) >= 4) {
                        $clients[] = [
                            'username' => $parts[0],
                            'ip_address' => $parts[1],
                            'connected_since' => $parts[4],
                            'bytes_received' => $parts[2],
                            'bytes_sent' => $parts[3]
                        ];
                    }
                }
                if (trim($line) === 'Common Name,Real Address,Bytes Received,Bytes Sent,Connected Since') {
                    $reading_clients = true;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error getting VPN clients: ' . $e->getMessage()
    ]);
}
