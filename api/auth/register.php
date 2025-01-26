<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';

// Validation des champs requis
if (empty($username) || empty($password) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit();
}

// Validation du nom d'utilisateur
if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères']);
    exit();
}

// Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
    exit();
}

// Validation du mot de passe
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérification si l'utilisateur existe déjà
    $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':username', $username);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé']);
        exit();
    }

    // Hashage du mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertion du nouvel utilisateur
    $insert_query = "INSERT INTO users (username, email, password_hash, role, created_at) 
                    VALUES (:username, :email, :password_hash, 'user', NOW())";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':username', $username);
    $insert_stmt->bindParam(':email', $email);
    $insert_stmt->bindParam(':password_hash', $password_hash);

    if ($insert_stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        // Création de la session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        $_SESSION['last_activity'] = time();

        echo json_encode([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'user' => [
                'id' => $user_id,
                'username' => $username,
                'role' => 'user'
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de la création du compte');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
