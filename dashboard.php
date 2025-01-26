<?php
session_start();
require_once __DIR__ . '/includes/check_maintenance.php';
checkMaintenance();

require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Vérifier si l'utilisateur est actif
try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT is_active FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user']['id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user['is_active']) {
            // L'utilisateur est inactif, le déconnecter
            session_destroy();
            header('Location: login.php?error=account_inactive');
            exit();
        }
    } else {
        // L'utilisateur n'existe plus dans la base de données
        session_destroy();
        header('Location: login.php?error=account_not_found');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database Error in dashboard.php: " . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$username = htmlspecialchars($_SESSION['user']['username']);
$role = $_SESSION['user']['role'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="assets/css/ctf-style.css" rel="stylesheet">
    <style>
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            z-index: -1;
        }
        
        .notification {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: #333;
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .notification.show {
            opacity: 1;
        }
        
        .notification.success {
            background-color: #2ecc71;
        }
        
        .notification.error {
            background-color: #e74c3c;
        }
        
        .notification.info {
            background-color: #3498db;
        }
    </style>
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
                        <a class="nav-link active" href="dashboard.php">
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
            <!-- Rooms Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-terminal me-2"></i>
                        <h5 class="mb-0">Available Rooms</h5>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshRooms()">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Refresh
                    </button>
                </div>
                <div class="terminal-body">
                    <div id="roomsList" class="row g-4">
                        <!-- Les rooms seront chargées ici dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-hack">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="room-details"></div>
                    <div id="containerInfo"></div>
                </div>
                <div class="modal-footer">
                    <div id="flagSubmission" class="w-100 mb-3" style="display: none;">
                        <div class="flag-input">
                            <div class="input-group">
                                <input type="text" class="form-control" id="modalFlagInput" placeholder="Enter flag">
                                <button class="btn btn-success" id="submitFlagBtn">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end w-100">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        <div class="machine-controls">
                            <button type="button" class="btn btn-warning" id="stopMachine" style="display: none;">
                                <i class="bi bi-stop-fill me-2"></i>Stop Machine
                            </button>
                            <button type="button" class="btn btn-hack" id="startMachine">
                                <i class="bi bi-play-fill me-2"></i>Start Machine
                            </button>
                            <button type="button" class="btn" id="actionButton"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-hack">
                <div class="modal-header">
                    <h5 class="modal-title">Success!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="successMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-hack">
                <div class="modal-header">
                    <h5 class="modal-title">Warning</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="warningMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
<script src="assets/js/dashboard.js"></script>
<script>
    // Gestion des flags
    document.addEventListener('DOMContentLoaded', function() {
        const flagSubmission = document.getElementById('flagSubmission');
        const modalFlagInput = document.getElementById('modalFlagInput');
        const submitFlagBtn = document.getElementById('submitFlagBtn');
        const actionButton = document.getElementById('actionButton');

        function updateModalForRoom(room) {
            console.log('Updating modal for room:', room.name);
            console.log('Room status:', room.status);
            console.log('Room is_user_active:', room.is_user_active);
            console.log('Full room object:', room);

            // Mettre à jour le contenu de la modal
            document.getElementById('roomModalLabel').textContent = room.name;
            
            // Afficher/masquer l'input de flag en fonction du statut
            const shouldShowFlag = room.status === 'joined' || room.is_user_active;
            console.log('Should show flag input:', shouldShowFlag);
            flagSubmission.style.display = shouldShowFlag ? 'block' : 'none';
            
            // Mettre à jour le bouton d'action
            if (shouldShowFlag) {
                actionButton.textContent = 'Leave Room';
                actionButton.classList.remove('btn-success');
                actionButton.classList.add('btn-danger');
            } else {
                actionButton.textContent = 'Join Room';
                actionButton.classList.remove('btn-danger');
                actionButton.classList.add('btn-success');
            }
        }

        // Gestionnaire pour le bouton d'action
        actionButton.addEventListener('click', async function() {
            const room = currentRoom;
            if (!room) return;

            console.log('Action button clicked for room:', room.name);
            console.log('Current room status:', room.status);

            try {
                if (room.status === 'joined' || room.is_user_active) {
                    console.log('Attempting to leave room');
                    // Code pour quitter la room
                    const response = await fetch('/api/leave_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            roomId: room.id
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        room.status = 'available';
                        room.is_user_active = false;
                        showNotification('Successfully left the room', 'success');
                    } else {
                        throw new Error(data.error || 'Failed to leave room');
                    }
                } else {
                    console.log('Attempting to join room');
                    // Code pour rejoindre la room
                    const response = await fetch('/api/register_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            roomId: room.id
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        room.status = 'joined';
                        room.is_user_active = true;
                        showNotification('Successfully joined the room', 'success');
                    } else {
                        throw new Error(data.error || 'Failed to join room');
                    }
                }
                console.log('Room status after action:', room.status);
                console.log('Room is_user_active after action:', room.is_user_active);
                updateModalForRoom(room);
                loadRooms(); // Recharger toutes les rooms
            } catch (error) {
                console.error('Error:', error);
                showNotification(error.message, 'error');
            }
        });

        // Gestionnaire de soumission du flag
        submitFlagBtn.addEventListener('click', async function() {
            const flag = modalFlagInput.value.trim();
            if (!flag) {
                showNotification('Please enter a flag', 'error');
                return;
            }

            try {
                const response = await fetch('/api/submit_flag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        roomId: currentRoom.id,
                        flag: flag
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification(data.message, 'success');
                    confetti({
                        particleCount: 100,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                    modalFlagInput.value = '';
                    $('#roomModal').modal('hide');
                    loadRooms();
                } else {
                    showNotification(data.error, 'error');
                }
            } catch (error) {
                console.error('Error submitting flag:', error);
                showNotification('An error occurred while submitting the flag', 'error');
            }
        });

        // Permettre la soumission avec Enter
        modalFlagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                submitFlagBtn.click();
            }
        });

        // Exposer la fonction updateModalForRoom globalement
        window.updateModalForRoom = updateModalForRoom;
    });
</script>
</html>
