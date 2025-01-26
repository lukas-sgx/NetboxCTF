<!-- Maintenance Section -->
<div class="terminal-container mb-4">
    <div class="terminal-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="bi bi-tools me-2"></i>
            <h5 class="mb-0">Maintenance Mode</h5>
        </div>
        <button class="btn btn-outline-warning" onclick="toggleMaintenance()" id="maintenanceToggle">
            <i class="bi bi-gear-fill me-2"></i>Toggle Maintenance
        </button>
    </div>
    <div class="terminal-body">
        <form id="maintenanceForm" class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="maintenance_message" class="form-label">Message de maintenance</label>
                        <textarea class="form-control bg-dark text-light" id="maintenance_message" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="maintenance_end_time" class="form-label">Date de fin (optionnel)</label>
                        <input type="datetime-local" class="form-control bg-dark text-light" id="maintenance_end_time">
                    </div>
                    <div class="mb-3">
                        <label for="allowed_ips" class="form-label">IPs autorisées (une par ligne)</label>
                        <textarea class="form-control bg-dark text-light" id="allowed_ips" rows="3" placeholder="192.168.1.1"></textarea>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="updateMaintenanceSettings()">
                <i class="bi bi-save me-2"></i>Save Settings
            </button>
        </form>
        <div class="alert" id="maintenanceStatus" role="alert"></div>
    </div>
</div>

<script>
// Fonctions pour la maintenance
async function loadMaintenanceStatus() {
    try {
        const response = await fetch('../api/admin/get_maintenance.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || "Erreur lors du chargement du statut de maintenance");
        }
        
        const maintenance = data.maintenance;
        document.getElementById('maintenance_message').value = maintenance.message || '';
        document.getElementById('maintenance_end_time').value = maintenance.end_time || '';
        document.getElementById('allowed_ips').value = (maintenance.allowed_ips || []).join('\n');
        
        const statusDiv = document.getElementById('maintenanceStatus');
        const toggleBtn = document.getElementById('maintenanceToggle');
        
        if (maintenance.enabled) {
            statusDiv.className = 'alert alert-warning';
            statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Maintenance Mode is <strong>ACTIVE</strong>';
            toggleBtn.classList.replace('btn-outline-warning', 'btn-warning');
            toggleBtn.innerHTML = '<i class="bi bi-gear-fill me-2"></i>Disable Maintenance';
        } else {
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i>System is <strong>ONLINE</strong>';
            toggleBtn.classList.replace('btn-warning', 'btn-outline-warning');
            toggleBtn.innerHTML = '<i class="bi bi-gear-fill me-2"></i>Enable Maintenance';
        }
    } catch (error) {
        console.error('Error in loadMaintenanceStatus:', error);
        showNotification(error.message, 'error');
    }
}

async function toggleMaintenance() {
    try {
        const response = await fetch('../api/admin/toggle_maintenance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || "Erreur lors du changement du mode maintenance");
        }
        
        showNotification(data.message || "Mode maintenance modifié avec succès");
        loadMaintenanceStatus();
    } catch (error) {
        console.error('Error in toggleMaintenance:', error);
        showNotification(error.message, 'error');
    }
}

async function updateMaintenanceSettings() {
    try {
        const message = document.getElementById('maintenance_message').value;
        const endTime = document.getElementById('maintenance_end_time').value;
        const allowedIps = document.getElementById('allowed_ips').value
            .split('\n')
            .map(ip => ip.trim())
            .filter(ip => ip);
        
        const response = await fetch('../api/admin/update_maintenance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message,
                end_time: endTime,
                allowed_ips: allowedIps
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || "Erreur lors de la mise à jour des paramètres de maintenance");
        }
        
        showNotification("Paramètres de maintenance mis à jour avec succès");
        loadMaintenanceStatus();
    } catch (error) {
        console.error('Error in updateMaintenanceSettings:', error);
        showNotification(error.message, 'error');
    }
}

// Charger le statut de maintenance au chargement de la page
document.addEventListener('DOMContentLoaded', loadMaintenanceStatus);
</script>
