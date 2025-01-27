<?php
if (!defined('ADMIN_ACCESS')) {
    exit('Direct access not permitted');
}
?>


<style>
.terminal-card {
    background: var(--card-bg);
    border: 1px solid var(--primary);
    border-radius: 8px;
    padding: 1.5rem;
    position: relative;
    transition: all 0.3s ease;
    height: 100%;
    overflow: hidden;
}

.terminal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px var(--glow);
    border-color: var(--primary);
}

.terminal-card:hover .terminal-card-icon {
    transform: scale(1.1);
    color: var(--primary);
}

.terminal-card:hover .terminal-card-action span {
    padding-right: 10px;
    color: var(--primary);
}

.terminal-card-icon {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: var(--primary);
    transition: all 0.3s ease;
}

.terminal-card-content h4 {
    color: var(--text);
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.terminal-card-content p {
    color: #6c757d;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.terminal-card-action {
    position: absolute;
    bottom: 1.5rem;
}

.terminal-card-action span {
    color: var(--primary);
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.terminal-card-action span i {
    transition: transform 0.3s ease;
}

.terminal-card:hover .terminal-card-action span i {
    transform: translateX(5px);
}

.terminal-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.terminal-card:hover::before {
    opacity: 1;
    animation: scanline 2s linear infinite;
}

@keyframes scanline {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.terminal-card {
    background-color: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.terminal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}
</style>

<?php
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les IPs bloquées
$stmt = $db->query("SELECT ip_address, reason, blocked_at FROM blocked_ips ORDER BY blocked_at DESC LIMIT 5");
$blockedIps = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les dernières tentatives de connexion
$stmt = $db->query("SELECT ip_address, username, attempt_time, success, user_agent FROM login_attempts ORDER BY attempt_time DESC LIMIT 5");
$loginAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les tentatives par IP
$ipAttempts = [];
$stmt = $db->query("SELECT ip_address, COUNT(*) as count FROM login_attempts 
                    WHERE success = 0 
                    GROUP BY ip_address");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ipAttempts[$row['ip_address']] = $row['count'];
}
?>

<!-- Security Section -->
<div class="terminal-container mb-4">
    <div class="terminal-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-shield-lock me-2"></i>
            <h5 class="mb-0">Gestion de la Sécurité</h5>
        </div>
    </div>
    <div class="terminal-body p-4">
        <div class="row g-4">
            <!-- Blocked IPs -->
            <div class="col-md-6">
                <div class="terminal-card">
                    <h6 class="mb-3">
                        <i class="bi bi-ban me-2"></i>
                        IPs Bloquées
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="blockedIPsTable">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blockedIps as $ip): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-laptop me-2"></i>
                                        <?php echo htmlspecialchars($ip['ip_address']); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php echo $ip['blocked_at']; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-hack btn-success btn-sm" 
                                                onclick="unblockIp('<?php echo $ip['ip_address']; ?>')"
                                                title="Débloquer cette IP">
                                            <i class="bi bi-unlock"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="pagination-container"></div>
                    </div>
                    <button class="btn btn-outline-primary mt-3" data-bs-toggle="modal" data-bs-target="#blockIPModal">
                        <i class="bi bi-plus-circle"></i> Bloquer une IP
                    </button>
                </div>
            </div>
            <!-- Security Events -->
            <div class="col-md-6">
                <div class="terminal-card">
                    <h6 class="mb-3">
                        <i class="bi bi-key me-2"></i>
                        Tentatives de Connexion
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover" id="securityEventsTable">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>IP</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loginAttempts as $attempt): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($attempt['username']); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-laptop me-1"></i>
                                        <?php echo htmlspecialchars($attempt['ip_address']); ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php echo $attempt['attempt_time']; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $attempt['success'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <i class="bi <?php echo $attempt['success'] ? 'bi-check-circle' : 'bi-x-circle'; ?> me-1"></i>
                                            <?php echo $attempt['success'] ? 'Succès' : 'Échec'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="pagination-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function unblockIp(ip) {
    if (!confirm(`Êtes-vous sûr de vouloir débloquer l'IP ${ip} ?`)) {
        return;
    }

    try {
        const response = await fetch('/api/admin/ip_management.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ip: ip })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('IP débloquée avec succès', 'success');
            // Recharger la page après 1 seconde
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.error || 'Erreur lors du déblocage de l\'IP');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

function blockIP(ip = '', reason = '') {
    // Pré-remplir les champs du modal si des valeurs sont fournies
    document.getElementById('ipAddress').value = ip;
    document.getElementById('reason').value = reason;
    
    // Afficher le modal en utilisant l'API Bootstrap 5
    const blockIPModal = document.getElementById('blockIPModal')
    if (blockIPModal) {
        const modal = new bootstrap.Modal(blockIPModal);
        modal.show();
    } else {
        console.error("Modal element not found");
    }
}
</script>
