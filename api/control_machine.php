<?php
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$machineId = $data['machineId'] ?? '';
$action = $data['action'] ?? '';

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

if (!empty($machineId) && in_array($action, ['start', 'stop'])) {
    $result = executeDockerCommand("$action $machineId");
    
    $response = [
        'success' => ($result['status'] === 0),
        'message' => $result['output'] ?: ($result['status'] === 0 ? 'Operation successful' : 'Operation failed')
    ];
}

echo json_encode($response);
