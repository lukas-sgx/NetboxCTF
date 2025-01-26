// Flag validation
async function validateFlag() {
    const machine = document.getElementById('machine').value;
    const flag = document.getElementById('flag').value;

    try {
        const response = await fetch('api/validate_flag.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ machine, flag })
        });
        
        const data = await response.json();
        alert(data.message);
    } catch (error) {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de la validation du flag');
    }
}

// VPN Configuration
async function downloadVpnConfig() {
    try {
        const response = await fetch('api/get_vpn_config.php');
        const blob = await response.blob();
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'vpn-config.ovpn';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error:', error);
        alert('Une erreur est survenue lors du téléchargement de la configuration VPN');
    }
}

// Docker Management
async function loadDockerContainers() {
    try {
        const response = await fetch('api/get_docker_containers.php');
        const containers = await response.json();
        
        const containersList = document.getElementById('dockerContainers');
        containersList.innerHTML = containers.map(container => `
            <div class="container-item">
                <strong>${container.name}</strong> - ${container.status}
                <div class="btn-group float-end">
                    <button class="btn btn-sm btn-success" onclick="controlContainer('${container.id}', 'start')">Start</button>
                    <button class="btn btn-sm btn-danger" onclick="controlContainer('${container.id}', 'stop')">Stop</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('dockerContainers').innerHTML = 'Erreur lors du chargement des conteneurs';
    }
}

async function controlContainer(containerId, action) {
    try {
        const response = await fetch('api/control_docker.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ containerId, action })
        });
        
        const result = await response.json();
        if (result.success) {
            loadDockerContainers(); // Refresh the list
        } else {
            alert(result.message || 'Une erreur est survenue');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Une erreur est survenue lors du contrôle du conteneur');
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('flagForm');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const machine = document.getElementById('machine').value;
        const flag = document.getElementById('flag').value;
        
        if (!machine || !flag) {
            alert('Veuillez remplir tous les champs');
            return;
        }
        
        try {
            const response = await fetch('api/validate_flag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ machine, flag })
            });
            
            const data = await response.json();
            alert(data.message);
            
            if (data.success) {
                form.reset();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la validation du flag');
        }
    });
    
    loadDockerContainers();
});
