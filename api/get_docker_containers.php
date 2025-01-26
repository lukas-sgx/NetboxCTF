<?php
header('Content-Type: application/json');

// Function to execute docker commands safely
function executeDockerCommand($command) {
    $output = [];
    $returnVar = 0;
    exec("docker $command 2>&1", $output, $returnVar);
    return [
        'output' => $output,
        'status' => $returnVar
    ];
}

// Get list of containers
$result = executeDockerCommand('ps -a --format "{{.ID}}\t{{.Names}}\t{{.Status}}"');

$containers = [];
if ($result['status'] === 0) {
    foreach ($result['output'] as $line) {
        $parts = explode("\t", $line);
        if (count($parts) === 3) {
            $containers[] = [
                'id' => $parts[0],
                'name' => $parts[1],
                'status' => $parts[2]
            ];
        }
    }
}

echo json_encode($containers);
