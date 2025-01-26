<?php
session_start();

// Check if user is actually logged in using the expected session structure
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Not logged in',
        'debug' => [
            'session_status' => session_status(),
            'session_exists' => isset($_SESSION),
            'user_exists' => isset($_SESSION['user']),
        ]
    ]);
    exit();
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Check if this is an API call or browser navigation
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Successfully logged out']);
} else {
    // Browser navigation - redirect to index
    header('Location: ../../index.php');
}