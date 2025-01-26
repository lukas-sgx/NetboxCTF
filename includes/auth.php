<?php

/**
 * Vérifie si l'utilisateur est authentifié
 * Redirige vers la page de connexion si non authentifié
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Vérifie si l'utilisateur a accès à une salle spécifique
 * @param int $room_id ID de la salle
 * @return bool
 */
function hasRoomAccess($room_id) {
    global $db;
    
    $query = "SELECT 1 FROM room_users 
              WHERE room_id = :room_id 
              AND user_id = :user_id 
              AND active = 1 
              AND (end_time IS NULL OR end_time > NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

/**
 * Génère un jeton CSRF
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * @param string $token Jeton à vérifier
 * @return bool
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie si l'utilisateur a les points nécessaires pour une salle
 * @param int $room_id ID de la salle
 * @return bool
 */
function hasEnoughPoints($room_id) {
    global $db;
    
    // Récupérer les points requis pour la salle
    $query = "SELECT points_required FROM rooms WHERE id = :room_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room || !$room['points_required']) {
        return true; // Si pas de points requis, accès autorisé
    }
    
    // Calculer les points totaux de l'utilisateur
    $query = "SELECT COALESCE(SUM(f.points), 0) as total_points 
              FROM user_flags uf 
              INNER JOIN flags f ON uf.flag_id = f.id 
              WHERE uf.user_id = :user_id";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total_points'] >= $room['points_required'];
}

/**
 * Enregistre une tentative de connexion échouée
 * @param string $username Nom d'utilisateur
 * @param string $ip Adresse IP
 */
function logFailedLogin($username, $ip) {
    global $db;
    
    $query = "INSERT INTO login_attempts (username, ip_address, attempt_time) 
              VALUES (:username, :ip, NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
}

/**
 * Vérifie si l'adresse IP est bloquée après trop de tentatives
 * @param string $ip Adresse IP
 * @return bool
 */
function isIpBlocked($ip) {
    global $db;
    
    $query = "SELECT COUNT(*) as attempts 
              FROM login_attempts 
              WHERE ip_address = :ip 
              AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['attempts'] >= 5; // Bloque après 5 tentatives en 15 minutes
}
