<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de la session admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
require_once 'security/SecurityMonitor.php';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        if (!$db) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        $securityMonitor = new SecurityMonitor($db);

        switch ($_POST['action']) {
            case 'resolve':
                if (isset($_POST['activity_id']) && isset($_POST['notes'])) {
                    $success = $securityMonitor->resolveActivity(
                        $_POST['activity_id'],
                        $_SESSION['user']['username'],
                        $_POST['notes']
                    );
                    if ($success) {
                        $message = "Activité marquée comme résolue.";
                    } else {
                        throw new Exception("Erreur lors de la résolution de l'activité");
                    }
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Erreur dans security_logs.php (POST): " . $e->getMessage());
        $error = "Une erreur est survenue: " . $e->getMessage();
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    $securityMonitor = new SecurityMonitor($db);

    // Test de connexion à la base de données
    try {
        $db->query("SELECT 1");
    } catch (PDOException $e) {
        throw new Exception("La connexion à la base de données est inactive: " . $e->getMessage());
    }

    // Récupération des données
    $suspiciousActivities = $securityMonitor->getSuspiciousActivities(50);
    if ($suspiciousActivities === false) {
        throw new Exception("Erreur lors de la récupération des activités suspectes");
    }

    $loginAttempts = $securityMonitor->getLoginAttempts(50);
    if ($loginAttempts === false) {
        throw new Exception("Erreur lors de la récupération des tentatives de connexion");
    }

    $blockedIPs = $securityMonitor->getBlockedIPs();
    if ($blockedIPs === false) {
        throw new Exception("Erreur lors de la récupération des IPs bloquées");
    }

} catch (Exception $e) {
    error_log("Erreur dans security_logs.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $error = "Une erreur est survenue lors du chargement des logs de sécurité: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs - NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="../assets/css/ctf-style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">NetboxCTF Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="security_logs.php">
                            <i class="bi bi-shield"></i> Security Logs
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <?php if (isset($message)): ?>
        <div class="alert alert-success mt-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger mt-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!isset($error)): ?>
        <!-- IPs Bloquées -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="terminal-container mb-4">
                    <div class="terminal-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-x me-2"></i>
                            <h5 class="mb-0">IPs Bloquées</h5>
                        </div>
                    </div>
                    <div class="terminal-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Bloquée le</th>
                                        <th>Bloquée jusqu'au</th>
                                        <th>Raison</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($blockedIPs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Aucune IP bloquée actuellement</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($blockedIPs as $ip): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($ip['blocked_at']); ?></td>
                                            <td><?php echo htmlspecialchars($ip['blocked_until']); ?></td>
                                            <td><?php echo htmlspecialchars($ip['reason']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Activités Suspectes -->
            <div class="col-md-6">
                <div class="terminal-container mb-4">
                    <div class="terminal-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            <h5 class="mb-0">Activités Suspectes</h5>
                        </div>
                    </div>
                    <div class="terminal-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Sévérité</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suspiciousActivities)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune activité suspecte détectée</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($suspiciousActivities as $activity): ?>
                                        <tr class="<?php echo $activity['severity'] >= 3 ? 'table-danger' : ''; ?>">
                                            <td><?php echo htmlspecialchars($activity['ip_address'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($activity['activity_type'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($activity['description'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($activity['severity'] ?? '1'); ?></td>
                                            <td>
                                                <?php 
                                                $status = $activity['status'] ?? 'new';
                                                $statusClass = match($status) {
                                                    'new' => 'danger',
                                                    'investigating' => 'warning',
                                                    'resolved' => 'success',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['created_at'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (($activity['status'] ?? 'new') !== 'resolved'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#resolveModal" 
                                                        data-activity-id="<?php echo $activity['id']; ?>">
                                                    Résoudre
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tentatives de Connexion -->
            <div class="col-md-6">
                <div class="terminal-container mb-4">
                    <div class="terminal-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-door-open me-2"></i>
                            <h5 class="mb-0">Tentatives de Connexion</h5>
                        </div>
                    </div>
                    <div class="terminal-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Utilisateur</th>
                                        <th>User Agent</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($loginAttempts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucune tentative de connexion enregistrée</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($loginAttempts as $attempt): ?>
                                        <tr class="<?php echo !$attempt['success'] ? 'table-danger' : 'table-success'; ?>">
                                            <td><?php echo htmlspecialchars($attempt['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($attempt['username']); ?></td>
                                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($attempt['user_agent']); ?>">
                                                <?php echo htmlspecialchars($attempt['user_agent']); ?>
                                            </td>
                                            <td><?php echo $attempt['success'] ? 'Succès' : 'Échec'; ?></td>
                                            <td><?php echo htmlspecialchars($attempt['attempt_time'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de résolution -->
    <div class="modal fade modal-hack" id="resolveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Résoudre l'activité suspecte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="resolve">
                        <input type="hidden" name="activity_id" id="activityId">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes de résolution</label>
                            <textarea class="form-control bg-dark text-light" id="notes" name="notes" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Résoudre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Gestionnaire pour le modal de résolution
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
            button.addEventListener('click', function() {
                const activityId = this.getAttribute('data-activity-id');
                document.getElementById('activityId').value = activityId;
            });
        });
    </script>
</body>
</html>
