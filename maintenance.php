<?php
session_start();
require_once __DIR__ . '/includes/check_maintenance.php';
checkMaintenance();

// Récupération du message de maintenance
$maintenance = require __DIR__ . '/config/maintenance.php';
$message = $maintenance['message'] ?? 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.';

// Calcul du temps restant
$timeLeft = '';
if (!empty($maintenance['end_time'])) {
    $endTime = new DateTime($maintenance['end_time']);
    $now = new DateTime();
    $interval = $now->diff($endTime);
    
    if ($interval->days > 0) {
        $timeLeft = $interval->days . ' jour(s)';
    } elseif ($interval->h > 0) {
        $timeLeft = $interval->h . ' heure(s)';
    } else {
        $timeLeft = $interval->i . ' minute(s)';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #00ff00;
            --dark: #0a0a0a;
            --text: #ffffff;
            --terminal-bg: #141414;
        }

        body {
            background-color: var(--dark);
            color: var(--text);
            font-family: monospace;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }

        .maintenance-container {
            text-align: center;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--primary);
            border-radius: 8px;
            max-width: 600px;
            position: relative;
            z-index: 1;
        }

        .maintenance-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        .maintenance-title {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .maintenance-message {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .maintenance-time {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid var(--primary);
            border-radius: 4px;
            display: inline-block;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.1;
        }

        .matrix-character {
            color: var(--primary);
            font-size: 1.2rem;
            position: absolute;
            animation: fall linear infinite;
        }

        @keyframes fall {
            from { transform: translateY(-100%); }
            to { transform: translateY(100vh); }
        }
    </style>
</head>
<body>
    <div class="matrix-bg" id="matrixBg"></div>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="bi bi-gear-fill"></i>
        </div>
        <h1 class="maintenance-title">Maintenance en cours</h1>
        <p class="maintenance-message"><?php echo htmlspecialchars($message); ?></p>
        <?php if (!empty($timeLeft)): ?>
        <div class="maintenance-time">
            <i class="bi bi-clock"></i> Temps restant estimé : <?php echo htmlspecialchars($timeLeft); ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Animation de la matrice en arrière-plan
        function createMatrixBackground() {
            const bg = document.getElementById('matrixBg');
            const characters = '01';
            const columns = Math.floor(window.innerWidth / 20);
            
            for (let i = 0; i < columns; i++) {
                const character = document.createElement('div');
                character.className = 'matrix-character';
                character.style.left = (i * 20) + 'px';
                character.style.animationDuration = (Math.random() * 2 + 1) + 's';
                character.style.animationDelay = Math.random() + 's';
                character.innerText = characters.charAt(Math.floor(Math.random() * characters.length));
                bg.appendChild(character);
            }
        }

        createMatrixBackground();
        
        // Régénérer les caractères périodiquement
        setInterval(() => {
            const characters = document.getElementsByClassName('matrix-character');
            for (let char of characters) {
                if (Math.random() > 0.98) {
                    char.innerText = '01'.charAt(Math.floor(Math.random() * 2));
                }
            }
        }, 100);
    </script>
</body>
</html>
