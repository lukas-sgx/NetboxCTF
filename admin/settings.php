<?php
session_start();
require_once __DIR__ . '/../includes/check_maintenance.php';
checkMaintenance();

// Vérification de l'authentification admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$username = htmlspecialchars($_SESSION['user']['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Admin - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="matrix-bg" id="matrixCanvas"></div>
    <div class="notification" id="notification"></div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">NetboxCTF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="bi bi-house-door"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../api/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Paramètres Administrateur</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mode Maintenance</h5>
                    </div>
                    <div class="card-body">
                        <form id="maintenanceForm">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                    <label class="form-check-label" for="maintenanceMode">Activer le mode maintenance</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="maintenanceMessage" class="form-label">Message de maintenance</label>
                                <textarea class="form-control" id="maintenanceMessage" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="allowedIPs" class="form-label">IPs autorisées (une par ligne)</label>
                                <textarea class="form-control" id="allowedIPs" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Paramètres Globaux</h5>
                    </div>
                    <div class="card-body">
                        <form id="globalSettingsForm">
                            <div class="mb-3">
                                <label for="maxLoginAttempts" class="form-label">Tentatives de connexion max</label>
                                <input type="number" class="form-control" id="maxLoginAttempts" min="1" max="10">
                            </div>
                            <div class="mb-3">
                                <label for="sessionTimeout" class="form-label">Timeout de session (minutes)</label>
                                <input type="number" class="form-control" id="sessionTimeout" min="5" max="1440">
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/matrix-bg.js"></script>
    <script>
        // Charger les paramètres actuels
        fetch('../api/admin/get_settings.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('maintenanceMode').checked = data.settings.maintenance_mode;
                    document.getElementById('maintenanceMessage').value = data.settings.maintenance_message;
                    document.getElementById('allowedIPs').value = data.settings.allowed_ips.join('\n');
                    document.getElementById('maxLoginAttempts').value = data.settings.max_login_attempts;
                    document.getElementById('sessionTimeout').value = data.settings.session_timeout;
                }
            });

        // Gérer la soumission du formulaire de maintenance
        document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = {
                maintenance_mode: document.getElementById('maintenanceMode').checked,
                maintenance_message: document.getElementById('maintenanceMessage').value,
                allowed_ips: document.getElementById('allowedIPs').value.split('\n').filter(ip => ip.trim())
            };

            fetch('../api/admin/update_maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Paramètres de maintenance mis à jour', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour', 'error');
                }
            });
        });

        // Gérer la soumission du formulaire des paramètres globaux
        document.getElementById('globalSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = {
                max_login_attempts: document.getElementById('maxLoginAttempts').value,
                session_timeout: document.getElementById('sessionTimeout').value
            };

            fetch('../api/admin/update_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Paramètres globaux mis à jour', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour', 'error');
                }
            });
        });

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
