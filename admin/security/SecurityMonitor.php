<?php

class SecurityMonitor {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes in seconds
    private $suspiciousActivityThreshold = 10;
    private $initialized = false;

    public function __construct($database) {
        if (!$database instanceof PDO) {
            throw new InvalidArgumentException("La connexion à la base de données doit être une instance de PDO");
        }
        $this->db = $database;
        $this->initTables();
    }

    private function initTables() {
        $inTransaction = false;
        try {
            // Vérifier si la connexion est active
            $this->db->query("SELECT 1");

            // Désactiver temporairement l'auto-commit pour créer les tables dans une transaction
            $this->db->beginTransaction();
            $inTransaction = true;

            // Vérifier si les tables existent déjà
            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // Création de la table pour les tentatives de connexion si elle n'existe pas
            if (!in_array('login_attempts', $tables)) {
                $loginAttemptsResult = $this->db->exec("CREATE TABLE login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    attempt_time DATETIME NOT NULL,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    user_agent VARCHAR(255),
                    INDEX idx_ip_attempt_time (ip_address, attempt_time),
                    INDEX idx_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                if ($loginAttemptsResult === false) {
                    throw new PDOException("Erreur lors de la création de la table login_attempts");
                }
            }

            // Création de la table pour les activités suspectes si elle n'existe pas
            if (!in_array('suspicious_activities', $tables)) {
                $suspiciousActivitiesResult = $this->db->exec("CREATE TABLE suspicious_activities (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                if ($suspiciousActivitiesResult === false) {
                    throw new PDOException("Erreur lors de la création de la table suspicious_activities");
                }
            }

            // Création de la table pour les IP bloquées si elle n'existe pas
            if (!in_array('blocked_ips', $tables)) {
                $blockedIPsResult = $this->db->exec("CREATE TABLE blocked_ips (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    blocked_at DATETIME NOT NULL,
                    blocked_until DATETIME NOT NULL,
                    reason TEXT NOT NULL,
                    UNIQUE KEY unique_ip (ip_address),
                    INDEX idx_blocked_until (blocked_until)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                if ($blockedIPsResult === false) {
                    throw new PDOException("Erreur lors de la création de la table blocked_ips");
                }
            }

            if ($inTransaction) {
                $this->db->commit();
            }
            $this->initialized = true;
        } catch (PDOException $e) {
            if ($inTransaction) {
                try {
                    $this->db->rollBack();
                } catch (PDOException $rollbackError) {
                    error_log("Erreur lors du rollback: " . $rollbackError->getMessage());
                }
            }
            error_log("Erreur lors de l'initialisation des tables de sécurité: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new Exception("Erreur lors de l'initialisation du système de sécurité: " . $e->getMessage());
        }
    }

    public function logLoginAttempt($username, $success = false) {
        try {
            $stmt = $this->db->prepare("INSERT INTO login_attempts 
                (username, ip_address, attempt_time, success, user_agent) 
                VALUES (:username, :ip_address, NOW(), :success, :user_agent)");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':ip_address', $ip);
            $stmt->bindValue(':success', $success ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':user_agent', $userAgent);
            
            $stmt->execute();
            
            // Vérifier les tentatives échouées récentes pour cette IP
            if (!$success) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as attempts 
                    FROM login_attempts 
                    WHERE ip_address = :ip_address 
                    AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                    AND success = 0
                ");
                $stmt->bindValue(':ip_address', $ip);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si plus de 5 tentatives échouées en 15 minutes, bloquer l'IP
                if ($result['attempts'] >= 5) {
                    $this->blockIP($ip, "Trop de tentatives de connexion échouées");
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement de la tentative de connexion: " . $e->getMessage());
            return false;
        }
    }

    private function blockIP($ipAddress, $reason) {
        try {
            $stmt = $this->db->prepare("INSERT INTO blocked_ips 
                (ip_address, blocked_at, blocked_until, reason) 
                VALUES (:ip_address, NOW(), DATE_ADD(NOW(), INTERVAL :lockout_time SECOND), :reason)
                ON DUPLICATE KEY UPDATE 
                blocked_at = NOW(),
                blocked_until = DATE_ADD(NOW(), INTERVAL :lockout_time SECOND),
                reason = :reason");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->bindValue(':lockout_time', $this->lockoutTime, PDO::PARAM_INT);
            $stmt->bindValue(':reason', $reason);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors du blocage de l'IP: " . $e->getMessage());
            return false;
        }
    }

    public function isIPBlocked($ipAddress) {
        try {
            // Nettoyer les anciennes entrées
            $this->db->exec("DELETE FROM blocked_ips WHERE blocked_until < NOW()");

            // Vérifier si l'IP est bloquée
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM blocked_ips 
                WHERE ip_address = :ip_address AND blocked_until > NOW()");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du blocage IP: " . $e->getMessage());
            return false;
        }
    }

    public function logSuspiciousActivity($ipAddress, $activityType, $description, $severity) {
        if (!$this->initialized) return false;

        try {
            $stmt = $this->db->prepare("INSERT INTO suspicious_activities 
                (ip_address, activity_type, description, timestamp, severity) 
                VALUES (:ip_address, :activity_type, :description, NOW(), :severity)");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->bindValue(':activity_type', $activityType);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':severity', $severity, PDO::PARAM_INT);
            $stmt->execute();

            if ($severity >= 3) {
                $this->notifyAdmins($activityType, $description, $ipAddress);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement de l'activité suspecte: " . $e->getMessage());
            return false;
        }
    }

    public function getSuspiciousActivities($limit = 50, $status = null) {
        try {
            $sql = "SELECT * FROM suspicious_activities";
            $params = [];

            if ($status !== null) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }

            $sql .= " ORDER BY timestamp DESC LIMIT :limit";
            $params[':limit'] = (int)$limit;

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des activités suspectes: " . $e->getMessage());
            return [];
        }
    }

    public function getLoginAttempts($limit = 50, $success = null) {
        try {
            $sql = "SELECT * FROM login_attempts";
            $params = [];

            if ($success !== null) {
                $sql .= " WHERE success = :success";
                $params[':success'] = $success ? 1 : 0;
            }

            $sql .= " ORDER BY attempt_time DESC LIMIT :limit";
            $params[':limit'] = (int)$limit;

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des tentatives de connexion: " . $e->getMessage());
            return [];
        }
    }

    public function getBlockedIPs() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM blocked_ips WHERE blocked_until > NOW()");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des IPs bloquées: " . $e->getMessage());
            return [];
        }
    }

    private function notifyAdmins($activityType, $description, $ipAddress) {
        $message = "SECURITY ALERT: {$activityType} detected from IP {$ipAddress}. Details: {$description}";
        error_log($message);
        
        // Envoyer une notification aux administrateurs (à implémenter selon vos besoins)
        // Par exemple, envoyer un email, une notification Slack, etc.
    }

    public function resolveActivity($activityId, $resolvedBy, $notes) {
        try {
            $stmt = $this->db->prepare("UPDATE suspicious_activities 
                SET status = 'resolved', 
                    resolved_at = NOW(), 
                    resolved_by = :resolved_by, 
                    resolution_notes = :notes 
                WHERE id = :activity_id");
            
            $stmt->bindValue(':resolved_by', $resolvedBy);
            $stmt->bindValue(':notes', $notes);
            $stmt->bindValue(':activity_id', $activityId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new PDOException("Échec de la mise à jour");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur lors de la résolution de l'activité: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Débloque une adresse IP
     * @param string $ipAddress L'adresse IP à débloquer
     * @param string $unlockedBy L'utilisateur qui débloque l'IP (optionnel)
     * @param string $notes Notes sur le déblocage (optionnel)
     * @return bool True si l'IP a été débloquée, False si elle n'était pas bloquée
     */
    public function unblockIP($ipAddress, $unlockedBy = null, $notes = null) {
        try {
            // Vérifier si l'IP est actuellement bloquée
            $stmt = $this->db->prepare("SELECT id FROM blocked_ips WHERE ip_address = :ip_address");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return false; // L'IP n'était pas bloquée
            }
            
            // Supprimer l'IP de la table blocked_ips
            $stmt = $this->db->prepare("DELETE FROM blocked_ips WHERE ip_address = :ip_address");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->execute();
            
            // Enregistrer l'action dans suspicious_activities
            $description = sprintf(
                "IP %s débloquée par %s. Notes: %s",
                $ipAddress,
                $unlockedBy ?? 'System',
                $notes ?? 'Aucune note'
            );
            
            $stmt = $this->db->prepare("INSERT INTO suspicious_activities 
                (ip_address, activity_type, description, timestamp, severity) 
                VALUES (:ip_address, 'ip_unblock', :description, NOW(), 1)");
            $stmt->bindValue(':ip_address', $ipAddress);
            $stmt->bindValue(':description', $description);
            $stmt->execute();
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur lors du déblocage de l'IP: " . $e->getMessage());
            throw new Exception("Impossible de débloquer l'IP: " . $e->getMessage());
        }
    }
}
