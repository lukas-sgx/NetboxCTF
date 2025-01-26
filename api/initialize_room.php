<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Vérification des paramètres
if (!isset($_POST['room_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID is required']);
    exit();
}

$room_id = $_POST['room_id'];
$user_id = $_SESSION['user']['id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Vérifier si l'utilisateur a déjà un challenge actif
    $query = "SELECT ru.*, r.name as room_name 
              FROM room_users ru 
              JOIN rooms r ON ru.room_id = r.id 
              WHERE ru.user_id = :user_id AND ru.active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($active_challenge = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($active_challenge['room_id'] != $room_id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Active challenge exists',
                'message' => "You already have an active challenge in room '{$active_challenge['room_name']}'. Please finish or leave that challenge before starting a new one."
            ]);
            exit();
        }
        
        // If there's an existing container for this room, stop and remove it
        if ($active_challenge['container_name']) {
            exec("docker stop {$active_challenge['container_name']} 2>&1", $output, $return_var);
            exec("docker rm {$active_challenge['container_name']} 2>&1", $output, $return_var);
        }
    }

    // Vérifier si la salle existe et obtenir ses informations avec les détails de la machine
    $query = "SELECT r.*, m.docker_image, m.cpu_limit, m.memory_limit, m.exposed_ports 
              FROM rooms r 
              INNER JOIN machines m ON r.machine_id = m.id 
              WHERE r.id = :room_id AND r.is_active = 1 AND m.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->execute();
    
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit();
    }

    // Log des informations de la machine pour le débogage
    error_log("Room data: " . print_r($room, true));
    error_log("Docker image from DB: " . $room['docker_image']);

    // Vérifier le nombre d'utilisateurs actifs dans la salle (excluant l'utilisateur actuel)
    $query = "SELECT COUNT(*) as count FROM room_users 
              WHERE room_id = :room_id AND active = 1 AND user_id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] >= $room['max_users']) {
        http_response_code(400);
        echo json_encode(['error' => 'Room is full']);
        exit();
    }

    // Générer un nom unique pour le container et des ports aléatoires
    $container_name = "room_{$room_id}_user_{$user_id}_" . time();
    $ssh_port = rand(2000, 3000);  // Port aléatoire pour SSH
    $web_port = rand(3001, 4000);  // Port aléatoire pour HTTP
    
    // Préparer les options Docker
    $docker_options = [
        '--name', $container_name,
        '--cpus', $room['cpu_limit'],
        '--memory', "{$room['memory_limit']}m",
        '-d'  // détaché
    ];

    // Ajouter les ports exposés en fonction du type de machine
    if (strpos($room['docker_image'], 'binaryninja') !== false) {
        $docker_options[] = '-p';
        $docker_options[] = "{$ssh_port}:22";
    } elseif (strpos($room['docker_image'], 'webmaster') !== false) {
        $docker_options[] = '-p';
        $docker_options[] = "{$web_port}:80";
    }

    // Construire la commande Docker
    $command = 'docker run ' . implode(' ', array_map('escapeshellarg', $docker_options)) . ' ' . 
               escapeshellarg($room['docker_image']);
    
    // Log de la commande pour le débogage
    error_log("Executing Docker command: " . $command);

    // Exécuter la commande Docker
    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var !== 0) {
        error_log("Docker command failed: " . implode("\n", $output));
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to start Docker container',
            'details' => implode("\n", $output)
        ]);
        exit();
    }

    // Récupérer l'IP du container
    $inspect_command = "docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' " . 
                      escapeshellarg($container_name);
    exec($inspect_command, $output, $return_var);
    
    if ($return_var !== 0 || empty($output[0])) {
        // En cas d'échec, nettoyer le container
        exec('docker rm -f ' . escapeshellarg($container_name));
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get container IP address']);
        exit();
    }
    
    $container_ip = $output[0];

    // Créer ou mettre à jour la session dans la base de données
    $db->beginTransaction();
    
    if ($active_challenge) {
        // Update existing session
        $query = "UPDATE room_users 
                  SET container_name = :container_name,
                      container_ip = :container_ip,
                      start_time = NOW(),
                      end_time = NULL,
                      active = 1,
                      ssh_port = :ssh_port,
                      web_port = :web_port
                  WHERE id = :session_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':session_id', $active_challenge['id']);
    } else {
        // Create new session
        $query = "INSERT INTO room_users (room_id, user_id, container_name, container_ip, start_time, time_limit, active, ssh_port, web_port) 
                  VALUES (:room_id, :user_id, :container_name, :container_ip, NOW(), :time_limit, 1, :ssh_port, :web_port)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':time_limit', $room['time_limit']);
    }
    
    $stmt->bindParam(':container_name', $container_name);
    $stmt->bindParam(':container_ip', $container_ip);
    $stmt->bindParam(':ssh_port', $ssh_port);
    $stmt->bindParam(':web_port', $web_port);
    $stmt->execute();

    $db->commit();

    // Préparer les informations de connexion en fonction du type de machine
    $connection_info = [];
    if (strpos($room['docker_image'], 'binaryninja') !== false) {
        $connection_info = [
            'type' => 'ssh',
            'host' => $_SERVER['SERVER_NAME'],
            'port' => $ssh_port,
            'username' => 'ctf',
            'password' => 'ctf'
        ];
    } elseif (strpos($room['docker_image'], 'webmaster') !== false) {
        $connection_info = [
            'type' => 'web',
            'url' => "http://{$_SERVER['SERVER_NAME']}:{$web_port}"
        ];
    }

    echo json_encode([
        'success' => true,
        'container' => [
            'name' => $container_name,
            'ip' => $container_ip,
            'time_limit' => $room['time_limit'],
            'connection' => $connection_info
        ]
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
