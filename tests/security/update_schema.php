<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Mise à jour du schéma de la base de données...\n";
    
    // Vérifier si les colonnes existent déjà
    $stmt = $db->query("SHOW COLUMNS FROM login_attempts LIKE 'success'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE login_attempts ADD COLUMN success TINYINT(1) NOT NULL DEFAULT 0");
        echo "Colonne 'success' ajoutée à login_attempts.\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM login_attempts LIKE 'user_agent'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE login_attempts ADD COLUMN user_agent VARCHAR(255) NULL");
        echo "Colonne 'user_agent' ajoutée à login_attempts.\n";
    }
    
    // Créer la table blocked_ips si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS blocked_ips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        blocked_at DATETIME NOT NULL,
        blocked_until DATETIME NOT NULL,
        reason TEXT NOT NULL,
        UNIQUE KEY unique_ip (ip_address),
        INDEX idx_blocked_until (blocked_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table blocked_ips créée avec succès.\n";
    
    // Créer la table suspicious_activities si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS suspicious_activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        timestamp DATETIME NOT NULL,
        severity INT NOT NULL DEFAULT 1,
        status ENUM('new', 'investigating', 'resolved') NOT NULL DEFAULT 'new',
        resolved_at DATETIME NULL,
        resolved_by VARCHAR(255) NULL,
        resolution_notes TEXT NULL,
        INDEX idx_ip_timestamp (ip_address, timestamp),
        INDEX idx_severity (severity),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table suspicious_activities créée avec succès.\n";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : \n" . $e->getTraceAsString() . "\n";
}
