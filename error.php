<?php
$error_code = isset($_GET['code']) ? intval($_GET['code']) : 500;

$error_messages = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'Erreur de syntaxe détectée. Votre requête contient des paramètres invalides.',
        'ascii_art' => '
   _____   _____ _____  
  |  __ \ / ____/ ____| 
  | |  | | |   | |      
  | |  | | |   | |      
  | |__| | |___| |____  
  |_____/ \_____\_____| 
        '
    ],
    401 => [
        'title' => 'Non Autorisé',
        'message' => 'Accès refusé. Vos credentials sont invalides ou ont expiré.',
        'ascii_art' => '
   _    _ _____ _____ _____ 
  | |  | |  __ \_   _|  __ \
  | |__| | |  | || | | |  | |
  |  __  | |  | || | | |  | |
  | |  | | |__| || |_| |__| |
  |_|  |_|_____/_____|_____/ 
        '
    ],
    403 => [
        'title' => 'Accès Interdit',
        'message' => 'Zone restreinte détectée. Votre niveau d\'accès est insuffisant.',
        'ascii_art' => '
  ______ _____ _____ _____ 
 |  ____|_   _|  __ \_   _|
 | |__    | | | |__) || |  
 |  __|   | | |  _  / | |  
 | |     _| |_| | \ \_| |_ 
 |_|    |_____|_|  \_\_____|
        '
    ],
    404 => [
        'title' => 'Cible Non Trouvée',
        'message' => 'La ressource demandée a été déplacée ou n\'existe pas dans notre système.',
        'ascii_art' => '
  __  __ _____ _____ _____ 
 |  \/  |_   _|  __ \_   _|
 | \  / | | | | |  | || |  
 | |\/| | | | | |  | || |  
 | |  | |_| |_| |__| || |_ 
 |_|  |_|_____|_____/_____|
        '
    ],
    500 => [
        'title' => 'Erreur Système',
        'message' => 'Une erreur critique a été détectée dans le système. Nos agents sont sur le coup.',
        'ascii_art' => '
   _____ ____  _____ 
  | ____|  _ \|  __ \
  | |__  | |_) | |  | |
  |___ \ |  _ <| |  | |
   ___) || |_) | |__| |
  |____/ |____/|_____/
        '
    ]
];

$error = $error_messages[$error_code] ?? $error_messages[500];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur <?php echo $error_code; ?> - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #00ff00;
            --dark: #0a0a0a;
            --text: #ffffff;
            --text-muted: #888888;
            --terminal-bg: #141414;
            --border: #333333;
        }

        body {
            font-family: monospace;
            background-color: var(--dark);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .error-container {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 2rem;
            background: var(--terminal-bg);
            border: 1px solid var(--primary);
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.1);
        }

        .ascii-art {
            font-family: 'Courier New', monospace;
            white-space: pre;
            color: var(--primary);
            font-size: 0.8rem;
            margin: 2rem 0;
            text-align: left;
            display: inline-block;
        }

        .error-code {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .error-message {
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .back-button {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 2rem;
            text-decoration: none;
            transition: all 0.3s;
            font-family: monospace;
            font-size: 1.1rem;
            display: inline-block;
            margin-top: 1rem;
        }

        .back-button:hover {
            background: var(--primary);
            color: var(--dark);
        }

        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            opacity: 0.1;
        }

        @keyframes glitch {
            0% { transform: translate(0) }
            20% { transform: translate(-2px, 2px) }
            40% { transform: translate(-2px, -2px) }
            60% { transform: translate(2px, 2px) }
            80% { transform: translate(2px, -2px) }
            100% { transform: translate(0) }
        }

        .error-code {
            animation: glitch 1s infinite;
        }
    </style>
</head>
<body>
    <canvas id="matrix" class="matrix-bg"></canvas>
    <div class="error-container">
        <div class="error-code"><?php echo $error_code; ?></div>
        <div class="ascii-art"><?php echo $error['ascii_art']; ?></div>
        <h1 class="error-title"><?php echo $error['title']; ?></h1>
        <p class="error-message"><?php echo $error['message']; ?></p>
        <a href="/" class="back-button">Retour à la base</a>
    </div>

    <script>
        // Matrix rain effect
        const canvas = document.getElementById('matrix');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const chars = "01";
        const fontSize = 14;
        const columns = canvas.width / fontSize;
        const drops = [];

        for (let i = 0; i < columns; i++) {
            drops[i] = 1;
        }

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#0F0';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = chars.charAt(Math.floor(Math.random() * chars.length));
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        setInterval(draw, 33);

        // Redimensionnement du canvas
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>
