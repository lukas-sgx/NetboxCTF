<?php
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les statistiques
$stmt = $db->query("SELECT id, username, role, last_login FROM users ORDER BY last_login DESC LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT r.id, r.name, r.status, COUNT(ru.id) as active_users 
                    FROM rooms r 
                    LEFT JOIN room_users ru ON r.id = ru.room_id AND ru.active = 1 
                    GROUP BY r.id 
                    ORDER BY active_users DESC 
                    LIMIT 10");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Management Section -->
<div class="terminal-container mb-4">
    <div class="terminal-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-gear me-2"></i>
            <h5 class="mb-0">Gestion des Ressources</h5>
        </div>
    </div>
    <div class="terminal-body p-4">
        <div class="row g-4">
            <!-- Users Management -->
            <div class="col-md-6">
                <div class="terminal-card">
                    <h6 class="mb-3">
                        <i class="bi bi-people me-2"></i>
                        Utilisateurs Récents
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="bi bi-person-badge me-2"></i>
                                        Utilisateur
                                    </th>
                                    <th>
                                        <i class="bi bi-shield-lock me-2"></i>
                                        Rôle
                                    </th>
                                    <th>
                                        <i class="bi bi-clock-history me-2"></i>
                                        Dernière Connexion
                                    </th>
                                    <th>
                                        <i class="bi bi-tools me-2"></i>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info me-2">#<?php echo $user['id']; ?></span>
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                            <i class="bi <?php echo $user['role'] === 'admin' ? 'bi-star-fill' : 'bi-person-check'; ?> me-1"></i>
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo $user['last_login']; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-hack btn-danger btn-sm" 
                                                onclick="deleteResource('user', <?php echo $user['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Rooms Management -->
            <div class="col-md-6">
                <div class="terminal-card">
                    <h6 class="mb-3">
                        <i class="bi bi-door-open me-2"></i>
                        Salles Actives
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="bi bi-door-closed me-2"></i>
                                        Salle
                                    </th>
                                    <th>
                                        <i class="bi bi-activity me-2"></i>
                                        Status
                                    </th>
                                    <th>
                                        <i class="bi bi-people me-2"></i>
                                        Utilisateurs
                                    </th>
                                    <th>
                                        <i class="bi bi-tools me-2"></i>
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info me-2">#<?php echo $room['id']; ?></span>
                                        <i class="bi bi-door-open-fill me-1"></i>
                                        <?php echo htmlspecialchars($room['name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $room['status'] === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                            <i class="bi <?php echo $room['status'] === 'active' ? 'bi-play-circle' : 'bi-pause-circle'; ?> me-1"></i>
                                            <?php echo $room['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="bi bi-people-fill me-1"></i>
                                            <?php echo $room['active_users']; ?> utilisateurs
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-hack btn-warning btn-sm me-1" 
                                                onclick="toggleRoomStatus(<?php echo $room['id']; ?>)"
                                                title="<?php echo $room['status'] === 'active' ? 'Désactiver' : 'Activer'; ?> la salle">
                                            <i class="bi <?php echo $room['status'] === 'active' ? 'bi-pause-fill' : 'bi-play-fill'; ?>"></i>
                                        </button>
                                        <button class="btn btn-hack btn-danger btn-sm" 
                                                onclick="deleteResource('room', <?php echo $room['id']; ?>)"
                                                <?php echo $room['active_users'] > 0 ? 'disabled' : ''; ?>
                                                title="Supprimer la salle">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function deleteResource(type, id) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer cette ${type === 'user' ? 'utilisateur' : 'salle'} ?`)) {
        return;
    }

    try {
        const response = await fetch(`/api/admin/${type}s.php?id=${id}`, {
            method: 'DELETE'
        });

        const data = await response.json();
        if (data.success) {
            showNotification(`${type === 'user' ? 'Utilisateur' : 'Salle'} supprimé avec succès`, 'success');
            // Recharger la section après 1 seconde
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.error || `Erreur lors de la suppression`);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

async function toggleRoomStatus(id) {
    try {
        const response = await fetch(`/api/admin/rooms.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                status: 'toggle'
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Status de la salle mis à jour', 'success');
            // Recharger la section après 1 seconde
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}
</script>
