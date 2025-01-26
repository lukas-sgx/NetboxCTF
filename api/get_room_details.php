<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$roomId = $_GET['id'] ?? '';
if (empty($roomId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Room ID is required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get room details with machine information
    $query = "SELECT 
                r.*,
                m.name as machine_name,
                m.docker_image,
                m.description as machine_description,
                m.cpu_limit,
                m.memory_limit,
                m.exposed_ports,
                COALESCE(ru_count.active_users, 0) as active_users,
                CASE 
                    WHEN COALESCE(ru_count.active_users, 0) >= r.max_users THEN 'occupied'
                    ELSE 'available'
                END as status,
                ru.container_name as user_container,
                ru.container_ip as user_container_ip,
                ru.active as is_user_registered
              FROM rooms r
              INNER JOIN machines m ON r.machine_id = m.id
              LEFT JOIN (
                SELECT room_id, COUNT(*) as active_users
                FROM room_users
                WHERE active = 1
                GROUP BY room_id
              ) ru_count ON r.id = ru_count.room_id
              LEFT JOIN room_users ru ON r.id = ru.room_id AND ru.user_id = :user_id AND ru.active = 1
              WHERE r.id = :room_id AND r.is_active = 1 AND m.is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $roomId);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $response = [
            'success' => true,
            'room' => [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'machine' => [
                    'name' => $row['machine_name'],
                    'docker_image' => $row['docker_image'],
                    'description' => $row['machine_description'],
                    'cpu_limit' => $row['cpu_limit'],
                    'memory_limit' => $row['memory_limit'],
                    'exposed_ports' => $row['exposed_ports']
                ],
                'status' => $row['status'],
                'time_limit' => $row['time_limit'],
                'active_users' => (int)$row['active_users'],
                'max_users' => $row['max_users'],
                'difficulty' => $row['difficulty'],
                'points' => $row['points']
            ]
        ];

        // Add user_session only if user is registered
        if ($row['is_user_registered']) {
            // Check if container is actually running
            $container_name = $row['user_container'];
            $container_status = 'unknown';
            $container_ip = $row['user_container_ip'];
            
            if ($container_name) {
                exec("docker inspect -f '{{.State.Status}}' " . escapeshellarg($container_name) . " 2>&1", $output, $return_var);
                if ($return_var === 0 && !empty($output[0])) {
                    $container_status = trim($output[0]);
                }
            }
            
            $response['room']['user_session'] = [
                'container_name' => $container_name,
                'container_ip' => $container_ip,
                'machine_status' => $container_status === 'running' ? 'running' : 'stopped'
            ];
        }
        
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Room not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
