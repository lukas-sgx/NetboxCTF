<?php
session_start();
require_once __DIR__ . '/includes/check_maintenance.php';
checkMaintenance();

// Vérification de l'authentification
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
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
    <title>Paramètres - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="matrix-bg" id="matrixCanvas"></div>
    <div class="notification" id="notification"></div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">NetboxCTF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">
                            <i class="bi bi-trophy"></i> Classement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear"></i> Paramètres
                        </a>
                    </li>
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="api/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Paramètres du compte</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" value="<?php echo $username; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card bg-dark text-light mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Sécurité</h5>
                    </div>
                    <div class="card-body">
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label for="currentPassword" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="currentPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="newPassword" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirmPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>

                <div class="card bg-dark text-light">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Préférences</h5>
                    </div>
                    <div class="card-body">
                        <form id="preferencesForm">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications">
                                    <label class="form-check-label" for="emailNotifications">Notifications par email</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="showProgress">
                                    <label class="form-check-label" for="showProgress">Afficher ma progression dans le classement</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/matrix-bg.js"></script>
    <script>
        // Charger les informations de l'utilisateur
        fetch('api/user/get_profile.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('email').value = data.user.email;
                    document.getElementById('emailNotifications').checked = data.user.email_notifications;
                    document.getElementById('showProgress').checked = data.user.show_progress;
                }
            });

        // Gérer la mise à jour du profil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = {
                email: document.getElementById('email').value
            };

            fetch('api/user/update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profil mis à jour avec succès', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour', 'error');
                }
            });
        });

        // Gérer le changement de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword !== confirmPassword) {
                showNotification('Les mots de passe ne correspondent pas', 'error');
                return;
            }

            const formData = {
                current_password: document.getElementById('currentPassword').value,
                new_password: newPassword
            };

            fetch('api/user/change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Mot de passe modifié avec succès', 'success');
                    document.getElementById('passwordForm').reset();
                } else {
                    showNotification('Erreur lors du changement de mot de passe', 'error');
                }
            });
        });

        // Gérer les préférences
        document.getElementById('preferencesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = {
                email_notifications: document.getElementById('emailNotifications').checked,
                show_progress: document.getElementById('showProgress').checked
            };

            fetch('api/user/update_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Préférences mises à jour', 'success');
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
