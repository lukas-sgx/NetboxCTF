-- Sauvegarder les données existantes
CREATE TEMPORARY TABLE temp_login_attempts AS 
SELECT * FROM login_attempts;

-- Supprimer la table existante
DROP TABLE IF EXISTS login_attempts;

-- Recréer la table avec la structure correcte
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    user_agent VARCHAR(255),
    INDEX idx_ip_attempt_time (ip_address, attempt_time),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurer les données
INSERT INTO login_attempts (
    id, username, ip_address, attempt_time, success, user_agent
)
SELECT 
    id, username, ip_address, 
    COALESCE(attempt_time, NOW()) as attempt_time,
    COALESCE(success, 0) as success,
    user_agent
FROM temp_login_attempts;

-- Nettoyer
DROP TEMPORARY TABLE temp_login_attempts;
