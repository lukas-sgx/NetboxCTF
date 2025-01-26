<?php
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$machine = $data['machine'] ?? '';
$flag = $data['flag'] ?? '';

// TODO: Implement actual flag validation logic
$validFlags = [
    'metasploitable2' => 'flag{test123}'
];

$response = [
    'success' => false,
    'message' => 'Flag incorrect'
];

if (isset($validFlags[$machine]) && $validFlags[$machine] === $flag) {
    $response = [
        'success' => true,
        'message' => 'Flag correct! Félicitations!'
    ];
}

echo json_encode($response);
