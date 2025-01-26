<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur et le mot de passe sont requis']);
    exit();
}

// Validation de la longueur du nom d'utilisateur
if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérification des identifiants
    $query = "SELECT id, username, password_hash, role, is_active FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $user['password_hash'])) {
            // Vérifier si le compte est actif
            if ($user['is_active'] != 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Ce compte a été désactivé']);
                exit();
            }

            // Mise à jour de la dernière connexion
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $user['id']);
            $update_stmt->execute();

            // Création de la session avec la structure attendue
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
            $_SESSION['last_activity'] = time();

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
