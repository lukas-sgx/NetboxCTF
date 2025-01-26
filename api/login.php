<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier si les données requises sont présentes
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer l'utilisateur
    $query = "SELECT id, username, password_hash, role, is_active FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        exit();
    }

    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Account is inactive']);
        exit();
    }

    // Mettre à jour la dernière connexion
    $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();

    // Définir les variables de session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = ($user['role'] === 'admin');

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'is_admin' => ($user['role'] === 'admin')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
