<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
checkAuth();

$database = new Database();
$db = $database->getConnection();

// Récupérer l'historique des salles
$query = "SELECT ru.*, r.name as room_name, r.difficulty, r.points 
          FROM room_users ru 
          INNER JOIN rooms r ON ru.room_id = r.id 
          WHERE ru.user_id = :user_id 
          ORDER BY ru.start_time DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les flags validés
$query = "SELECT f.*, r.name as room_name 
          FROM user_flags uf 
          INNER JOIN flags f ON uf.flag_id = f.id 
          INNER JOIN rooms r ON f.room_id = r.id 
          WHERE uf.user_id = :user_id 
          ORDER BY uf.validation_time DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - CTF Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Historique</h1>
        
        <div class="card">
            <h2>Salles complétées</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Salle</th>
                        <th>Difficulté</th>
                        <th>Points</th>
                        <th>Date de début</th>
                        <th>Date de fin</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                            <td><?php echo htmlspecialchars($room['difficulty']); ?></td>
                            <td><?php echo htmlspecialchars($room['points']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($room['start_time'])); ?></td>
                            <td><?php echo $room['end_time'] ? date('d/m/Y H:i', strtotime($room['end_time'])) : '-'; ?></td>
                            <td><?php echo $room['completed'] ? 'Terminé' : 'En cours'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card mt-4">
            <h2>Flags validés</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Salle</th>
                        <th>Flag</th>
                        <th>Points</th>
                        <th>Date de validation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flags as $flag): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($flag['room_name']); ?></td>
                            <td><?php echo htmlspecialchars($flag['flag']); ?></td>
                            <td><?php echo htmlspecialchars($flag['points']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($flag['validation_time'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
