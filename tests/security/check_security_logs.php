<?php
try {
    $db = new PDO(
        "mysql:host=172.17.0.3;dbname=ctf_platform",
        "lks",
        "lks",
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "Connexion à la base de données réussie!\n\n";
    
    // Vérifier les tentatives de connexion
    echo "=== Dernières tentatives de connexion ===\n";
    $stmt = $db->query("SELECT * FROM login_attempts ORDER BY attempt_time DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "[%s] IP: %s, User: %s, Success: %s, Agent: %s\n",
            $row['attempt_time'],
            $row['ip_address'],
            $row['username'],
            $row['success'] ? 'Oui' : 'Non',
            $row['user_agent']
        );
    }
    
    echo "\n=== IPs bloquées ===\n";
    $stmt = $db->query("SELECT * FROM blocked_ips WHERE blocked_until > NOW() ORDER BY blocked_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "IP: %s, Bloqué le: %s, Jusqu'au: %s\nRaison: %s\n\n",
            $row['ip_address'],
            $row['blocked_at'],
            $row['blocked_until'],
            $row['reason']
        );
    }
    
    echo "\n=== Activités suspectes ===\n";
    $stmt = $db->query("SELECT * FROM suspicious_activities ORDER BY timestamp DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "[%s] IP: %s\nType: %s\nDescription: %s\nSeverity: %d\nStatus: %s\n\n",
            $row['timestamp'],
            $row['ip_address'],
            $row['activity_type'],
            $row['description'],
            $row['severity'],
            $row['status']
        );
    }
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage() . "\n";
    echo "Trace : \n" . $e->getTraceAsString() . "\n";
}
