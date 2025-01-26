<?php
session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des IPs bloquées - HackLabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="../assets/css/ctf-style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Gestion des IPs bloquées</h1>
        
        <div id="alertContainer"></div>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Bloquer une IP manuellement</h2>
            </div>
            <div class="card-body">
                <form id="blockIPForm" class="row g-3">
                    <div class="col-md-4">
                        <label for="ipAddress" class="form-label">Adresse IP</label>
                        <input type="text" class="form-control" id="ipAddress" required 
                               pattern="^(\d{1,3}\.){3}\d{1,3}$" 
                               title="Format: xxx.xxx.xxx.xxx">
                    </div>
                    <div class="col-md-3">
                        <label for="duration" class="form-label">Durée (minutes)</label>
                        <input type="number" class="form-control" id="duration" value="15" min="1" required>
                    </div>
                    <div class="col-md-5">
                        <label for="reason" class="form-label">Raison</label>
                        <input type="text" class="form-control" id="reason" 
                               placeholder="Raison du blocage" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-shield-x me-2"></i>Bloquer l'IP
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">IPs actuellement bloquées</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="blockedIPsTable">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Bloquée depuis</th>
                                <th>Bloquée jusqu'au</th>
                                <th>Raison</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">Chargement...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour au tableau de bord
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher une alerte
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            
            // Auto-dismiss après 5 secondes
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Fonction pour charger les IPs bloquées
        async function loadBlockedIPs() {
            try {
                const response = await fetch('../api/security/blocked_ips.php');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors du chargement des IPs');
                }
                
                const tbody = document.querySelector('#blockedIPsTable tbody');
                tbody.innerHTML = '';
                
                if (data.data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center">Aucune IP n'est actuellement bloquée.</td>
                        </tr>
                    `;
                    return;
                }
                
                data.data.forEach(ip => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${ip.ip_address}</td>
                            <td>${ip.blocked_at}</td>
                            <td>${ip.blocked_until}</td>
                            <td>${ip.reason}</td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-sm" 
                                           placeholder="Notes (optionnel)">
                                    <button class="btn btn-warning btn-sm" 
                                            onclick="unblockIP('${ip.ip_address}', this.previousElementSibling.value)">
                                        <i class="bi bi-unlock me-1"></i>Débloquer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        // Fonction pour bloquer une IP
        async function blockIP(event) {
            event.preventDefault();
            
            const ipAddress = document.getElementById('ipAddress').value;
            const duration = document.getElementById('duration').value;
            const reason = document.getElementById('reason').value;
            
            try {
                const response = await fetch('../api/security/blocked_ips.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ip_address: ipAddress, duration, reason })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || "Erreur lors du blocage de l'IP");
                }
                
                showAlert(data.message);
                document.getElementById('blockIPForm').reset();
                loadBlockedIPs();
                
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        // Fonction pour débloquer une IP
        async function unblockIP(ipAddress, notes = '') {
            if (!confirm(`Êtes-vous sûr de vouloir débloquer l'IP ${ipAddress} ?`)) {
                return;
            }
            
            try {
                const response = await fetch('../api/security/blocked_ips.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ip_address: ipAddress, notes })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || "Erreur lors du déblocage de l'IP");
                }
                
                showAlert(data.message);
                loadBlockedIPs();
                
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            loadBlockedIPs();
            document.getElementById('blockIPForm').addEventListener('submit', blockIP);
        });

        // Rafraîchir la liste toutes les 30 secondes
        setInterval(loadBlockedIPs, 30000);
    </script>
</body>
</html>
