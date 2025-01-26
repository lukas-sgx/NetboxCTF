<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->query("SELECT * FROM vpn_connections WHERE status = 'connected' ORDER BY last_seen DESC");
    $html = '';
    
    while ($row = $stmt->fetch()) {
        $connected_since = new DateTime($row['connected_since']);
        $last_seen = new DateTime($row['last_seen']);
        $now = new DateTime();
        
        // Calculer la durée de connexion
        $duration = $connected_since->diff($now);
        $duration_str = '';
        if ($duration->d > 0) $duration_str .= $duration->d . 'j ';
        if ($duration->h > 0) $duration_str .= $duration->h . 'h ';
        $duration_str .= $duration->i . 'm';
        
        // Calculer le temps depuis la dernière activité
        $last_activity = $last_seen->diff($now);
        $last_activity_str = '';
        if ($last_activity->d > 0) $last_activity_str .= $last_activity->d . 'j ';
        if ($last_activity->h > 0) $last_activity_str .= $last_activity->h . 'h ';
        $last_activity_str .= $last_activity->i . 'm';
        
        // Déterminer le statut basé sur la dernière activité
        $status_class = ($last_activity->i < 5) ? 'success' : 'warning';
        $status_text = ($last_activity->i < 5) ? 'Actif' : 'Inactif';
        
        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($row['common_name']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['virtual_ip']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['real_ip']) . "</td>";
        $html .= "<td>" . $duration_str . "</td>";
        $html .= "<td>" . $last_activity_str . "</td>";
        $html .= "<td><span class='badge badge-{$status_class}'>{$status_text}</span></td>";
        $html .= "</tr>";
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
