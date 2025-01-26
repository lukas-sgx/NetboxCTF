<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HackLabs - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="assets/css/ctf-style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--background);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }

        .login-form {
            background: var(--card-bg);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), transparent);
            animation: scanline 2s linear infinite;
        }

        .ascii-art {
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7em;
            white-space: pre;
            text-align: center;
            margin-bottom: 2rem;
            animation: glitch 1s infinite;
        }

        .form-control {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--primary);
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
        }

        .form-control:focus {
            background: rgba(0, 255, 0, 0.1);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 255, 0, 0.25);
            color: var(--text);
        }

        .btn-login {
            width: 100%;
            position: relative;
            overflow: hidden;
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300px;
            height: 300px;
            background-color: var(--primary);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.5s ease;
            z-index: 0;
        }

        .btn-login:hover::before {
            transform: translate(-50%, -50%) scale(1);
        }

        .btn-login span {
            position: relative;
            z-index: 1;
        }

        .btn-login:hover {
            color: var(--card-bg);
        }

        .alert {
            display: none;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="ascii-art">
 _    _            _    _       _         
| |  | |          | |  | |     | |        
| |__| | __ _  ___| | _| |     | |__  ___ 
|  __  |/ _` |/ __| |/ / |     | '_ \/ __|
| |  | | (_| | (__|   <| |____ | |_) \__ \
|_|  |_|\__,_|\___|_|\_\______||_.__/|___/
            </div>
            <form id="registerForm" action="api/auth/register.php" method="POST">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-primary text-primary">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-primary text-primary">
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-primary text-primary">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                </div>
                <div class="alert alert-danger mb-4" role="alert"></div>
                <button type="submit" class="btn btn-login">
                    <span>Register</span>
                </button>
                <div class="text-center mt-4">
                    <a href="login.php" class="text-primary text-decoration-none">Already have an account? Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const alert = document.querySelector('.alert');
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert.style.display = 'block';
                    alert.textContent = data.message;
                }
            })
            .catch(error => {
                alert.style.display = 'block';
                alert.textContent = 'Une erreur est survenue. Veuillez réessayer.';
            });
        });
    </script>
</body>
</html>
