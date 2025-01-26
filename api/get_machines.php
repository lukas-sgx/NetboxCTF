<?php
header('Content-Type: application/json');

function executeDockerCommand($command) {
    $output = [];
    $returnVar = 0;
    exec("docker $command 2>&1", $output, $returnVar);
    return [
        'output' => $output,
        'status' => $returnVar
    ];
}

function getContainerIp($containerId) {
    $result = executeDockerCommand("inspect --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $containerId");
    return ($result['status'] === 0 && !empty($result['output'])) ? $result['output'][0] : null;
}

// Get list of containers
$result = executeDockerCommand('ps -a --format "{{.ID}}\t{{.Names}}\t{{.Status}}"');

$machines = [];
if ($result['status'] === 0) {
    foreach ($result['output'] as $line) {
        $parts = explode("\t", $line);
        if (count($parts) === 3) {
            $id = $parts[0];
            $status = strtolower(substr($parts[2], 0, 2)) === 'up' ? 'running' : 'stopped';
            
            $machines[] = [
                'id' => $id,
                'name' => $parts[1],
                'status' => $status,
                'ip' => $status === 'running' ? getContainerIp($id) : null
            ];
        }
    }
}

echo json_encode($machines);
