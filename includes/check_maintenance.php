<?php
// Vérification du mode maintenance
function checkMaintenance() {
    $maintenance = require __DIR__ . '/../config/maintenance.php';
    
    // Si la maintenance est activée
    if ($maintenance['enabled']) {
        // Vérifier si la maintenance est terminée
        if (isset($maintenance['end_time']) && !empty($maintenance['end_time'])) {
            $endTime = new DateTime($maintenance['end_time']);
            $now = new DateTime();
            if ($now > $endTime) {
                // La maintenance est terminée, on la désactive
                $maintenance['enabled'] = false;
                $content = "<?php\nreturn array(\n";
                foreach ($maintenance as $key => $value) {
                    if (is_bool($value)) {
                        $content .= "  '$key' => " . ($value ? 'true' : 'false') . ",\n";
                    } elseif (is_array($value)) {
                        $content .= "  '$key' => array('" . implode("', '", $value) . "'),\n";
                    } elseif (is_null($value)) {
                        $content .= "  '$key' => null,\n";
                    } else {
                        $content .= "  '$key' => '" . addslashes($value) . "',\n";
                    }
                }
                $content .= ");\n";
                file_put_contents(__DIR__ . '/../config/maintenance.php', $content);
                return;
            }
        }

        // Vérifie si l'utilisateur est autorisé
        if (!isset($_SESSION['user']) || 
            !in_array($_SESSION['user']['role'], $maintenance['allowed_users'])) {
            
            // Si on n'est pas déjà sur la page de maintenance
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'maintenance.php') {
                header('Location: /maintenance.php');
                exit;
            }
        }
    }
    // Si la maintenance n'est pas activée mais qu'on est sur la page maintenance
    elseif (basename($_SERVER['PHP_SELF']) === 'maintenance.php') {
        header('Location: /');
        exit;
    }
}
