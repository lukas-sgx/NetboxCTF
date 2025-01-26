<?php
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$containerId = $data['containerId'] ?? '';
$action = $data['action'] ?? '';

// Function to execute docker commands safely
function executeDockerCommand($command) {
    $output = [];
    $returnVar = 0;
    exec("docker $command 2>&1", $output, $returnVar);
    return [
        'output' => implode("\n", $output),
        'status' => $returnVar
    ];
}

$response = [
    'success' => false,
    'message' => 'Invalid action'
];

if (!empty($containerId) && in_array($action, ['start', 'stop'])) {
    $result = executeDockerCommand("$action $containerId");
    
    $response = [
        'success' => ($result['status'] === 0),
        'message' => $result['output']
    ];
}

echo json_encode($response);
