<?php
// Ce fichier est inclus dans le dashboard admin et contient la section de maintenance
require_once __DIR__ . '/../../config/maintenance.php';
$maintenanceConfig = include __DIR__ . '/../../config/maintenance.php';
?>
<!-- Maintenance Section -->
<div class="terminal-container mb-4">
    <div class="terminal-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-wrench me-2"></i>
            <h5 class="mb-0">Maintenance Mode</h5>
        </div>
    </div>
    <div class="terminal-body p-4">
        <div class="terminal-card">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="maintenance-status-section">
                        <div class="terminal-card-icon">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <div class="maintenance-toggle">
                            <div class="form-check form-switch custom-switch">
                                <input class="form-check-input" type="checkbox" id="maintenanceSwitch" 
                                       <?php echo isset($maintenanceConfig['enabled']) && $maintenanceConfig['enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenanceSwitch">
                                    <span class="status-text">Mode Maintenance</span>
                                    <span class="status-label" style="color: <?php echo isset($maintenanceConfig['enabled']) && $maintenanceConfig['enabled'] ? 'var(--primary)' : 'var(--accent)'; ?>">
                                        <?php echo isset($maintenanceConfig['enabled']) && $maintenanceConfig['enabled'] ? 'Maintenance activée' : 'Maintenance désactivée'; ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="maintenance-message mt-4">
                            <label for="maintenance_message" class="form-label">Message de maintenance</label>
                            <div class="message-input-container">
                                <textarea class="form-control terminal-textarea" id="maintenance_message" 
                                    rows="3" placeholder="Message affiché pendant la maintenance"><?php echo htmlspecialchars($maintenanceConfig['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="maintenance-message-preview mt-3" id="messagePreview">
                                <h4 class="text-danger mb-4">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Site en Maintenance
                                </h4>
                                <div class="preview-content">
                                    <?php echo nl2br(htmlspecialchars($maintenanceConfig['message'] ?? 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="maintenance-access-section">
                        <div class="access-header">
                            <i class="bi bi-shield-lock me-2"></i>
                            <h6 class="mb-0">Accès Autorisé</h6>
                        </div>
                        <div class="ip-list-container mt-3">
                            <label for="allowed_ips" class="form-label">IPs autorisées</label>
                            <div class="message-input-container">
                                <textarea class="form-control terminal-textarea" id="allowed_ips" 
                                    rows="4" placeholder="Liste des IPs autorisées pendant la maintenance"><?php 
                                    echo isset($maintenanceConfig['allowed_ips']) ? htmlspecialchars(implode("\n", $maintenanceConfig['allowed_ips'])) : ''; 
                                    ?></textarea>
                            </div>
                            <small class="text-info mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i>
                                Votre IP sera automatiquement ajoutée lors de l'activation
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.terminal-card {
    background: var(--card-bg);
    border: 1px solid var(--primary);
    border-radius: 8px;
    padding: 2rem;
    position: relative;
    transition: all 0.3s ease;
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

.terminal-card-icon {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: var(--primary);
    transition: all 0.3s ease;
}

.terminal-card-icon i {
    animation: spin 10s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.maintenance-toggle {
    margin-top: 1rem;
}

.custom-switch .form-check-input {
    width: 3rem;
    height: 1.5rem;
    background-color: var(--card-bg);
    border: 2px solid var(--primary);
    cursor: pointer;
}

.custom-switch .form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.custom-switch .form-check-input:focus {
    box-shadow: 0 0 0 0.25rem var(--glow);
}

.status-text {
    display: block;
    color: var(--text);
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.status-label {
    font-size: 0.875rem;
    font-weight: 600;
}

.terminal-textarea {
    background-color: var(--card-bg);
    border: 1px solid var(--primary);
    color: var(--text);
    font-family: 'JetBrains Mono', monospace;
    resize: none;
    transition: all 0.3s ease;
}

.terminal-textarea:focus {
    background-color: var(--card-bg);
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem var(--glow);
    color: var(--text);
}

.terminal-textarea::placeholder {
    color: #6c757d;
}

.access-header {
    color: var(--text);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.access-header i {
    color: var(--primary);
}

.form-label {
    color: var(--text);
    font-weight: 500;
}

.text-info {
    color: var(--primary) !important;
}

@keyframes scanline {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maintenanceSwitch = document.getElementById('maintenanceSwitch');
    const maintenanceMessage = document.getElementById('maintenance_message');
    const messagePreview = document.getElementById('messagePreview');
    const allowedIps = document.getElementById('allowed_ips');

    function updateStatusLabel() {
        const statusLabel = maintenanceSwitch.nextElementSibling.querySelector('.status-label');
        statusLabel.textContent = maintenanceSwitch.checked ? 
            'Maintenance activée' : 'Maintenance désactivée';
        statusLabel.style.color = maintenanceSwitch.checked ? 
            'var(--primary)' : 'var(--accent)';
    }

    function updateMessagePreview() {
        const content = maintenanceMessage.value.trim() || 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.';
        messagePreview.querySelector('.preview-content').innerHTML = content.replace(/\n/g, '<br>');
    }

    async function updateMaintenanceStatus() {
        try {
            const response = await fetch('/api/admin/maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    enabled: maintenanceSwitch.checked,
                    message: maintenanceMessage.value,
                    allowed_ips: allowedIps.value.split('\n').map(ip => ip.trim()).filter(ip => ip)
                })
            });

            const data = await response.json();
            if (data.success) {
                showNotification('Configuration de maintenance mise à jour', 'success');
            } else {
                throw new Error(data.error || 'Erreur lors de la mise à jour');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification(error.message, 'error');
        }
    }

    maintenanceSwitch.addEventListener('change', () => {
        updateStatusLabel();
        updateMaintenanceStatus();
    });

    maintenanceMessage.addEventListener('input', updateMessagePreview);
    allowedIps.addEventListener('change', updateMaintenanceStatus);

    // Initial updates
    updateStatusLabel();
    updateMessagePreview();
});
</script>
