<?php
session_start();

echo "Session ID: " . session_id() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";
echo "Current Session Data: " . print_r($_SESSION, true) . "\n";

// Test d'écriture de session
$_SESSION['test'] = 'test_value_' . time();
echo "After Setting Test Value: " . print_r($_SESSION, true) . "\n";

// Vérification des permissions du dossier de session
$sessionPath = session_save_path();
if (empty($sessionPath)) {
    $sessionPath = '/tmp';
}

echo "Session directory permissions: " . substr(sprintf('%o', fileperms($sessionPath)), -4) . "\n";
echo "Session directory writable: " . (is_writable($sessionPath) ? 'yes' : 'no') . "\n";

// Test de la connexion à la base de données
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: success\n";
    
    // Test de requête
    $stmt = $db->query("SELECT 1");
    echo "Database query test: success\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
