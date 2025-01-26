-- Sauvegarder les données existantes
CREATE TEMPORARY TABLE temp_suspicious_activities AS 
SELECT * FROM suspicious_activities;

-- Supprimer la table existante
DROP TABLE IF EXISTS suspicious_activities;

-- Recréer la table avec la nouvelle structure
CREATE TABLE suspicious_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    severity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'investigating', 'resolved') NOT NULL DEFAULT 'new',
    resolved_at DATETIME NULL,
    resolved_by VARCHAR(255) NULL,
    resolution_notes TEXT NULL,
    INDEX idx_ip_created (ip_address, created_at),
    INDEX idx_status (status),
    INDEX idx_severity (severity)
);

-- Restaurer les données
INSERT INTO suspicious_activities (
    id, ip_address, activity_type, description, severity, created_at
)
SELECT 
    id, ip_address, activity_type, description, 
    COALESCE(severity, 1), 
    COALESCE(created_at, NOW())
FROM temp_suspicious_activities;

-- Nettoyer
DROP TEMPORARY TABLE temp_suspicious_activities;
