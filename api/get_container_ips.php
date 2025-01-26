<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $output = [];
    $command = "docker network inspect bridge";
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        $networkInfo = json_decode(implode('', $output), true);
        $containers = [];
        
        if (isset($networkInfo[0]['Containers'])) {
            foreach ($networkInfo[0]['Containers'] as $container) {
                $containers[] = [
                    'name' => $container['Name'],
                    'ip' => explode('/', $container['IPv4Address'])[0]
                ];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'containers' => $containers]);
    } else {
        throw new Exception("Failed to get container information");
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
