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
    <title>Profil - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="assets/css/ctf-style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">
                            <i class="bi bi-trophy"></i> Classement
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

    <div class="container-fluid">
        <div class="rooms-container mt-4">
            <!-- Profile Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-circle me-2"></i>
                        <h5 class="mb-0">Informations du profil</h5>
                    </div>
                </div>
                <div class="terminal-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                            <h4 class="mt-3"><?php echo $username; ?></h4>
                            <span class="badge bg-primary"><?php echo ucfirst($_SESSION['user']['role']); ?></span>
                            <div class="mt-3">
                                <button class="btn btn-outline-primary" id="changePasswordBtn">
                                    <i class="bi bi-key"></i> Changer le mot de passe
                                </button>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="terminal-stats">
                                        <h3 id="totalPoints">0</h3>
                                        <p>Points totaux</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="terminal-stats">
                                        <h3 id="completedRooms">0</h3>
                                        <p>Salles terminées</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="terminal-stats">
                                        <h3 id="ranking">-</h3>
                                        <p>Classement</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="terminal-container">
                <div class="terminal-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history me-2"></i>
                        <h5 class="mb-0">Activité récente</h5>
                    </div>
                </div>
                <div class="terminal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Salle</th>
                                    <th>Action</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody id="activityTable">
                                <!-- L'activité sera chargée ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Changement de mot de passe -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-hack">
                <div class="modal-header">
                    <h5 class="modal-title">Changer le mot de passe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="savePasswordBtn">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Charger les statistiques de l'utilisateur
        fetch('api/user/get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalPoints').textContent = data.stats.total_points;
                    document.getElementById('completedRooms').textContent = data.stats.completed_rooms;
                    document.getElementById('ranking').textContent = '#' + data.stats.ranking;
                }
            });

        // Charger l'activité récente
        fetch('api/user/get_activity.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const activityTable = document.getElementById('activityTable');
                    activityTable.innerHTML = '';
                    
                    data.activity.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${new Date(item.date).toLocaleString()}</td>
                            <td>${item.room_name}</td>
                            <td>${item.action}</td>
                            <td>${item.points}</td>
                        `;
                        activityTable.appendChild(row);
                    });
                }
            });

        // Gérer le changement de mot de passe
        const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
        
        document.getElementById('changePasswordBtn').addEventListener('click', () => {
            passwordModal.show();
        });

        document.getElementById('savePasswordBtn').addEventListener('click', () => {
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
                    passwordModal.hide();
                    document.getElementById('passwordForm').reset();
                } else {
                    showNotification('Erreur lors du changement de mot de passe', 'error');
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
