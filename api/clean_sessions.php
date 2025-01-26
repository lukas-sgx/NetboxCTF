<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/session.php';
    require_once '../config/database.php';

    header('Content-Type: application/json');

    // Debug session state
    $session_debug = [
        'logged_in' => function_exists('isLoggedIn') ? isLoggedIn() : 'function not found',
        'session_exists' => isset($_SESSION),
        'user_exists' => isset($_SESSION['user']),
        'role_exists' => isset($_SESSION['user']['role']),
        'current_role' => $_SESSION['user']['role'] ?? 'none',
        'is_admin' => function_exists('isAdmin') ? isAdmin() : 'function not found',
        'session_id' => session_id(),
        'session_status' => session_status(),
        'included_files' => get_included_files()
    ];

    // Vérification de l'authentification et du rôle admin
    if (!function_exists('isAdmin')) {
        throw new Exception('Session helper functions not loaded properly');
    }

    if (!isAdmin()) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Non autorisé',
            'debug' => $session_debug
        ]);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    // Récupérer l'état initial des sessions actives
    $query = "SELECT COUNT(*) as total FROM room_users WHERE active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $initial_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nettoyer les sessions expirées (plus de 24 heures ou dépassant time_limit)
    $query = "UPDATE room_users 
              SET active = 0,
                  end_time = COALESCE(end_time, NOW())
              WHERE active = 1 
              AND (
                  start_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  OR (
                      end_time IS NULL 
                      AND TIMESTAMPDIFF(MINUTE, start_time, NOW()) > time_limit
                  )
              )";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $deleted = $stmt->rowCount();

    // Récupérer les conteneurs à arrêter
    $query = "SELECT container_name 
              FROM room_users 
              WHERE active = 0 
              AND end_time IS NOT NULL 
              AND container_name IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $containers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Arrêter les conteneurs
    foreach ($containers as $container) {
        exec("docker stop {$container} 2>&1", $output, $return_var);
        exec("docker rm {$container} 2>&1", $output, $return_var);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sessions nettoyées avec succès',
        'stats' => [
            'initial_active_sessions' => $initial_count,
            'cleaned_sessions' => $deleted,
            'containers_stopped' => count($containers),
            'remaining_active' => $initial_count - $deleted
        ]
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Database error in clean_sessions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du nettoyage des sessions',
        'debug' => [
            'error_type' => 'PDOException',
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'session_debug' => $session_debug ?? 'not available'
        ]
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("General error in clean_sessions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'debug' => [
            'error_type' => get_class($e),
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'session_debug' => $session_debug ?? 'not available'
        ]
    ]);
}
