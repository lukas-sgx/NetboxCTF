<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../admin/security/SecurityMonitor.php';

// Configuration
$username = 'admin';  // username à tester
$numberOfAttempts = 10;  // nombre de tentatives
$delayBetweenAttempts = 1;  // délai entre les tentatives en secondes

try {
    $database = new Database();
    $db = $database->getConnection();
    $securityMonitor = new SecurityMonitor($db);

    echo "Démarrage de la simulation d'attaque...\n";

    // Utiliser une IP fixe pour tester le blocage
    $attackerIP = '192.168.86.71';
    $_SERVER['REMOTE_ADDR'] = $attackerIP;
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    // Simuler plusieurs tentatives de connexion échouées depuis la même IP
    for ($i = 1; $i <= $numberOfAttempts; $i++) {
        $fakePassword = 'wrongpassword' . $i;
        
        echo "\nTentative $i sur $numberOfAttempts\n";
        echo "Username: $username\n";
        echo "Password: $fakePassword\n";
        
        // Vérifier si l'IP est bloquée
        if ($securityMonitor->isIPBlocked($attackerIP)) {
            echo "IP $attackerIP est bloquée ! Tentative rejetée.\n";
            break;
        }
        
        // Simuler une tentative de connexion échouée
        $securityMonitor->logLoginAttempt($username, false);
        
        echo "Tentative enregistrée pour l'IP: $attackerIP\n";
        
        // Attendre avant la prochaine tentative
        if ($i < $numberOfAttempts) {
            sleep($delayBetweenAttempts);
        }
    }

    echo "\nSimulation terminée. Vérification des logs...\n\n";

    // Vérifier les tentatives de connexion
    echo "=== Dernières tentatives de connexion ===\n";
    $stmt = $db->query("SELECT * FROM login_attempts ORDER BY attempt_time DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "[%s] IP: %s, User: %s, Success: %s\n",
            $row['attempt_time'],
            $row['ip_address'],
            $row['username'],
            $row['success'] ? 'Oui' : 'Non'
        );
    }

    echo "\n=== IPs bloquées ===\n";
    $stmt = $db->query("SELECT * FROM blocked_ips WHERE blocked_until > NOW()");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "IP: %s\nBloqué depuis: %s\nBloqué jusqu'au: %s\nRaison: %s\n\n",
            $row['ip_address'],
            $row['blocked_at'],
            $row['blocked_until'],
            $row['reason']
        );
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : \n" . $e->getTraceAsString() . "\n";
}
