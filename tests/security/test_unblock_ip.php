<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../admin/security/SecurityMonitor.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $securityMonitor = new SecurityMonitor($db);

    // IP à tester
    $testIP = '192.168.1.100';
    
    echo "Test de déblocage d'IP\n";
    echo "======================\n\n";
    
    // 1. Vérifier si l'IP est bloquée
    echo "1. Vérification du statut initial de l'IP $testIP...\n";
    if ($securityMonitor->isIPBlocked($testIP)) {
        echo "L'IP est actuellement bloquée.\n";
    } else {
        echo "L'IP n'est pas bloquée. Simulation d'une attaque pour la bloquer...\n";
        
        // Simuler des tentatives de connexion échouées
        $_SERVER['REMOTE_ADDR'] = $testIP;
        $_SERVER['HTTP_USER_AGENT'] = 'Test Script';
        
        for ($i = 0; $i < 6; $i++) {
            $securityMonitor->logLoginAttempt('test_user', false);
            echo ".";
        }
        echo "\nAttaque simulée.\n";
    }
    
    // 2. Vérifier à nouveau le statut
    echo "\n2. Vérification du statut après l'attaque...\n";
    if ($securityMonitor->isIPBlocked($testIP)) {
        echo "L'IP est bien bloquée.\n";
        
        // 3. Tenter de débloquer l'IP
        echo "\n3. Tentative de déblocage de l'IP...\n";
        if ($securityMonitor->unblockIP($testIP, 'test_admin', 'Test de déblocage')) {
            echo "L'IP a été débloquée avec succès.\n";
        } else {
            echo "Échec du déblocage de l'IP.\n";
        }
        
        // 4. Vérifier une dernière fois
        echo "\n4. Vérification finale du statut...\n";
        if ($securityMonitor->isIPBlocked($testIP)) {
            echo "ERREUR: L'IP est toujours bloquée !\n";
        } else {
            echo "Succès: L'IP n'est plus bloquée.\n";
        }
    } else {
        echo "ERREUR: L'IP n'a pas été bloquée comme prévu.\n";
    }
    
    // 5. Afficher les logs
    echo "\n5. Dernières activités suspectes:\n";
    $stmt = $db->query("SELECT * FROM suspicious_activities ORDER BY timestamp DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf(
            "[%s] %s - %s\n",
            $row['timestamp'],
            $row['activity_type'],
            $row['description']
        );
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Trace : \n" . $e->getTraceAsString() . "\n";
}
