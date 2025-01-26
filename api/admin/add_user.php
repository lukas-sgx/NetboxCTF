<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Check for admin role using the same structure as dashboard.php
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Activer les erreurs PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur existe déjà
    $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':username', $data['username']);
    $checkStmt->bindParam(':email', $data['email']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Nom d\'utilisateur ou email déjà utilisé']);
        exit();
    }

    // Hasher le mot de passe
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Préparer la requête d'insertion
    $query = "INSERT INTO users (username, email, password_hash, role, is_active, created_at) 
              VALUES (:username, :email, :password_hash, :role, :is_active, NOW())";
    
    $stmt = $db->prepare($query);
    
    // Définir les valeurs
    $role = isset($data['is_admin']) && $data['is_admin'] ? 'admin' : 'user';
    $isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;
    
    // Debug des valeurs
    error_log("Adding user with data: " . json_encode([
        'username' => $data['username'],
        'email' => $data['email'],
        'role' => $role,
        'is_active' => $isActive
    ]));
    
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password_hash', $hashedPassword);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $role,
                'is_active' => $isActive
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de l\'exécution de la requête d\'insertion');
    }

} catch (PDOException $e) {
    error_log("Database Error in add_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in add_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
