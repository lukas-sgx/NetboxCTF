<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'dockerv2');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de session
ini_set('session.cookie_lifetime', 86400); // 24 heures
ini_set('session.gc_maxlifetime', 86400); // 24 heures

// Configuration des chemins
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');

// Configuration de l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Fonction de connexion à la base de données
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
