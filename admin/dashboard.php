<?php
session_start();

// Vérification simple de la session admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Définir une constante pour l'accès aux includes
define('ADMIN_ACCESS', true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="../assets/css/ctf-style.css" rel="stylesheet">
</head>
<body>
    <div class="matrix-bg" id="matrixCanvas"></div>
    <div class="notification" id="notification"></div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">NetboxCTF Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../leaderboard.php">
                            <i class="bi bi-trophy"></i> Leaderboard
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../api/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="rooms-container mt-4">
            <!-- Overview Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <h5 class="mb-0">Overview</h5>
                    </div>
                </div>
                <div class="terminal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="terminal-stats">
                                <h3 id="totalUsers">-</h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="terminal-stats">
                                <h3 id="activeRooms">-</h3>
                                <p>Active Rooms</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="terminal-stats">
                                <h3 id="totalMachines">-</h3>
                                <p>Total Machines</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gestion de la Sécurité -->
            <?php include 'includes/security_section.php'; ?>

            <!-- Users Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people me-2"></i>
                        <h5 class="mb-0">Users Management</h5>
                    </div>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i> Add User
                    </button>
                </div>
                <div class="terminal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rooms Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-grid me-2"></i>
                        <h5 class="mb-0">Rooms Management</h5>
                    </div>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="bi bi-plus-square"></i> Add Room
                    </button>
                </div>
                <div class="terminal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="roomsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Machine</th>
                                    <th>Active Users</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Machines Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hdd-rack me-2"></i>
                        <h5 class="mb-0">Machines Management</h5>
                    </div>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                        <i class="bi bi-plus-circle"></i> Add Machine
                    </button>
                </div>
                <div class="terminal-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="machinesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Docker Image</th>
                                    <th>Resources</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Maintenance Section -->
            <div class="terminal-container mb-4">
                <div class="terminal-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-wrench me-2"></i>
                        <h5 class="mb-0">Maintenance</h5>
                    </div>
                </div>
                <div class="terminal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label">Mode Maintenance</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenanceSwitch">
                                        <label class="form-check-label status-label" for="maintenanceSwitch">Désactivé</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="maintenance_message" class="form-label">Message de maintenance</label>
                                <textarea class="form-control" id="maintenance_message" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="allowed_ips" class="form-label">IPs autorisées (une par ligne)</label>
                                <textarea class="form-control" id="allowed_ips" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Les modals -->
    <?php include 'includes/modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
