// Variables globales
let currentRoom = null;
let roomModal = null;

// Effet Matrix
function createMatrixEffect() {
    const canvas = document.createElement('canvas');
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    document.querySelector('.matrix-bg').appendChild(canvas);

    const ctx = canvas.getContext('2d');
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()';
    const fontSize = 10;
    let drops = [];

    function initMatrix() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const columns = canvas.width / fontSize;
        drops = [];
        for (let i = 0; i < columns; i++) {
            drops[i] = 1;
        }
    }

    function drawMatrix() {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#0F0';
        ctx.font = fontSize + 'px monospace';

        for (let i = 0; i < drops.length; i++) {
            const text = chars[Math.floor(Math.random() * chars.length)];
            ctx.fillText(text, i * fontSize, drops[i] * fontSize);
            if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                drops[i] = 0;
            }
            drops[i]++;
        }
    }

    window.addEventListener('resize', initMatrix);
    initMatrix();
    setInterval(drawMatrix, 33);
}

// Système de notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type} show`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

async function loadMachines() {
    try {
        const response = await fetch('/api/get_machines.php');
        const machines = await response.json();
        
        const tbody = document.querySelector('#vpnTable tbody');
        if (!tbody) {
            console.error('Table body element not found');
            return;
        }
        
        tbody.innerHTML = machines.map(machine => `
            <tr>
                <td>${machine.name}</td>
                <td>
                    <span class="badge ${machine.status === 'running' ? 'bg-success' : 'bg-danger'}">
                        ${machine.status}
                    </span>
                </td>
                <td>${machine.ip || 'N/A'}</td>
                <td>
                    <div class="btn-group" role="group">
                        ${machine.status === 'running' ? `
                            <button class="btn btn-sm btn-outline-danger" onclick="controlMachine(${machine.id}, 'stop')">
                                <i class="bi bi-stop-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadVpnConfig(${machine.id})">
                                <i class="bi bi-download"></i>
                            </button>
                        ` : `
                            <button class="btn btn-sm btn-outline-success" onclick="controlMachine(${machine.id}, 'start')">
                                <i class="bi bi-play-fill"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        showNotification('Une erreur est survenue lors du chargement des machines', 'error');
    }
}

async function controlMachine(machineId, action) {
    try {
        const response = await fetch('/api/control_machine.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ machineId, action })
        });
        
        const result = await response.json();
        if (result.success) {
            loadMachines();
        } else {
            throw new Error(result.message || 'Une erreur est survenue');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Une erreur est survenue lors du contrôle de la machine');
    }
}

async function downloadVpnConfig(roomId) {
    try {
        showNotification('Downloading VPN configuration...', 'info');

        // Créer un lien invisible pour le téléchargement
        const a = document.createElement('a');
        a.href = `/api/get_vpn_config.php?room_id=${roomId}`;
        a.download = `vpn-room-${roomId}.ovpn`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        showNotification('VPN configuration downloaded successfully', 'success');
        
        // Mettre à jour le statut VPN dans l'interface
        const vpnStatus = document.getElementById('vpnStatus');
        if (vpnStatus) {
            vpnStatus.textContent = 'Config Downloaded';
            vpnStatus.classList.remove('connected');
        }
    } catch (error) {
        console.error('Error downloading VPN config:', error);
        showNotification('Failed to download VPN configuration', 'error');
    }
}

async function loadRooms() {
    try {
        const response = await fetch('/api/get_rooms.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load rooms');
        }

        const container = document.getElementById('roomsList');
        container.innerHTML = '';

        data.rooms.forEach(room => {
            console.log('Room status:', room.name, room.status);
            const card = createRoomCard(room);
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Error loading rooms:', error);
        showNotification(error.message, 'error');
    }
}

function createRoomCard(room) {
    console.log('Creating card for room:', room.name, 'Status:', room.status);
    const div = document.createElement('div');
    div.className = 'col-md-6 col-lg-4 mb-4';
    
    // Déterminer le statut et la classe CSS correspondante
    let statusClass = '';
    let statusText = '';
    switch(room.status) {
        case 'completed':
            statusClass = 'status-completed';
            statusText = 'Completed';
            break;
        case 'joined':
            statusClass = 'status-joined';
            statusText = 'Joined';
            break;
        case 'full':
            statusClass = 'status-occupied';
            statusText = 'Full';
            break;
        default:
            statusClass = 'status-available pulse';
            statusText = 'Available';
    }

    div.innerHTML = `
        <div class="room-card h-100">
            <div class="card-body">
                <div class="card-header">
                    <h5 class="card-title">${room.name}</h5>
                    <span class="room-status ${statusClass}">
                        ${statusText}
                    </span>
                </div>
                <p class="card-description">${room.description}</p>
                <div class="room-details">
                    <div class="machine-info">
                        <small>Machine Type</small>
                        <span class="badge machine-type">${room.machine_type || 'Standard'}</span>
                    </div>
                    <div class="difficulty-info">
                        <small>Difficulty</small>
                        <span class="badge ${getDifficultyClass(room.difficulty)}">${room.difficulty}</span>
                    </div>
                    <div class="points-info">
                        <small>Points</small>
                        <span class="badge points-badge">${room.points}</span>
                    </div>
                </div>
                <div class="room-actions">
                    ${room.status === 'joined' ? `
                        <div class="flag-input mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter flag" id="flag-${room.id}">
                                <button class="btn btn-success" onclick="submitFlag(${room.id})">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </div>
                        </div>
                    ` : ''}
                    ${room.status !== 'completed' ? `
                        <button class="btn btn-hack" onclick="openRoom(${room.id})">
                            <i class="bi bi-terminal me-2"></i>${room.status === 'joined' ? 'Resume' : 'Start'}
                        </button>
                    ` : `
                        <button class="btn btn-hack completed" disabled>
                            <i class="bi bi-trophy me-2"></i>Completed
                        </button>
                    `}
                </div>
            </div>
        </div>
    `;
    return div;
}

async function refreshRooms() {
    const button = document.querySelector('button[onclick="refreshRooms()"]');
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Refreshing...';
    
    await loadRooms();
    
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalContent;
        showNotification('Rooms refreshed successfully', 'success');
    }, 1000);
}

async function openRoom(roomId) {
    try {
        const response = await fetch(`/api/get_rooms.php`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load rooms');
        }

        const room = data.rooms.find(r => r.id === roomId);
        if (!room) {
            throw new Error('Room not found');
        }

        currentRoom = room;
        console.log('Opening room:', room.name, 'Status:', room.status, 'Full room object:', room);
        updateModalForRoom(room);
        $('#roomModal').modal('show');
    } catch (error) {
        console.error('Error opening room:', error);
        showNotification(error.message, 'error');
    }
}

// Mettre à jour le statut après avoir rejoint une room
async function registerRoom(roomId) {
    try {
        const response = await fetch('/api/register_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ roomId: roomId })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Successfully joined the room', 'success');
            await loadRooms();
            updateRoomStatus('joined'); // Mettre à jour le statut
        } else {
            throw new Error(data.error || 'Failed to join room');
        }
    } catch (error) {
        console.error('Error registering room:', error);
        showNotification(error.message, 'error');
    }
}

async function updateContainerStatus(room) {
    const startButton = document.getElementById('startMachine');
    const stopButton = document.getElementById('stopMachine');
    const machineInfoSection = document.getElementById('machineInfoSection');
    const machineStatus = document.getElementById('machineStatus');
    const machineIp = document.getElementById('machineIp');
    const vpnStatus = document.getElementById('vpnStatus');

    console.log('Updating container status for room:', room);
    console.log('Container IP:', room.user_session?.container_ip);
    console.log('Room status:', room.status);

    // Afficher la section d'informations si l'utilisateur a rejoint la room
    if (room.status === 'joined' || room.is_user_active) {
        console.log('User has joined the room, showing machine info section');
        machineInfoSection.style.display = 'block';
    } else {
        console.log('User has not joined the room, hiding machine info section');
        machineInfoSection.style.display = 'none';
        return;
    }

    if (room.user_session && room.user_session.container_ip) {
        console.log('Container is running, showing stop button');
        // Machine démarrée
        startButton.style.display = 'none';
        stopButton.style.display = 'block';
        
        // Mettre à jour les informations
        machineStatus.textContent = 'Running';
        machineStatus.classList.add('running');
        machineIp.textContent = room.user_session.container_ip;
        
        // Vérifier le statut VPN
        if (room.user_session.vpn_connected) {
            vpnStatus.textContent = 'Connected';
            vpnStatus.classList.add('connected');
        } else {
            vpnStatus.textContent = 'Not Connected';
            vpnStatus.classList.remove('connected');
        }
    } else {
        console.log('Container is not running, showing start button');
        // Machine arrêtée
        startButton.style.display = 'block';
        stopButton.style.display = 'none';
        
        // Réinitialiser les informations
        machineStatus.textContent = 'Stopped';
        machineStatus.classList.remove('running');
        machineIp.textContent = '-';
        vpnStatus.textContent = 'Not Connected';
        vpnStatus.classList.remove('connected');
    }
}

async function updateRoomModal(room) {
    console.log('Updating room modal with room:', room);
    currentRoom = room;  // Mettre à jour la variable globale
    
    // Mettre à jour le contenu de la modal
    const flagSubmission = document.getElementById('flagSubmission');
    const actionButton = document.getElementById('actionButton');
    
    // Mettre à jour les boutons en fonction de l'état du container
    await updateContainerStatus(room);

    // Afficher/masquer l'input de flag en fonction du statut
    const shouldShowFlag = room.status === 'joined' || room.is_user_active;
    console.log('Should show flag:', shouldShowFlag);
    
    if (shouldShowFlag) {
        actionButton.textContent = '';
        actionButton.classList.remove('btn-success', 'btn-danger', 'btn-hack', 'btn-red');
        actionButton.classList.add('btn-hack', 'btn-red');
        actionButton.innerHTML = '<i class="bi bi-box-arrow-right me-2"></i>Leave Room';
        flagSubmission.style.display = 'block';
    } else {
        actionButton.textContent = '';
        actionButton.classList.remove('btn-success', 'btn-danger', 'btn-hack', 'btn-red');
        actionButton.classList.add('btn-hack');
        actionButton.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Join Room';
        flagSubmission.style.display = 'none';
    }
}

async function startMachine(roomId) {
    try {
        showNotification('Initializing machine...', 'info');

        const formData = new FormData();
        formData.append('room_id', roomId);

        const response = await fetch('/api/initialize_room.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Initialize response:', data);
        
        if (!data.success) {
            throw new Error(data.message || data.error || 'Failed to initialize machine');
        }

        // Wait a bit for the container to start
        showNotification('Waiting for machine to start...', 'info');
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Refresh room details to get container status
        const roomResponse = await fetch(`/api/get_room_details.php?id=${roomId}`);
        const roomData = await roomResponse.json();
        console.log('Room details after start:', roomData);
        
        if (!roomData.success) {
            throw new Error('Failed to get room details');
        }
        
        if (!roomData.room.user_session || !roomData.room.user_session.container_ip) {
            throw new Error('Machine failed to start properly');
        }

        currentRoom = roomData.room; // Mettre à jour la room courante
        console.log('Current room after start:', currentRoom);
        
        // Forcer la mise à jour de l'interface
        const startButton = document.getElementById('startMachine');
        const stopButton = document.getElementById('stopMachine');
        const machineInfoSection = document.getElementById('machineInfoSection');
        const machineStatus = document.getElementById('machineStatus');
        const machineIp = document.getElementById('machineIp');
        
        if (currentRoom.user_session && currentRoom.user_session.container_ip) {
            console.log('Container is running, updating UI...');
            startButton.style.display = 'none';
            stopButton.style.display = 'block';
            machineInfoSection.style.display = 'block';
            machineStatus.textContent = 'Running';
            machineStatus.classList.add('running');
            machineIp.textContent = currentRoom.user_session.container_ip;
        }
        
        showNotification('Machine initialized successfully!', 'success');
        showNotification('Download and connect to the VPN to access the machine', 'info');
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

async function stopMachine(roomId) {
    try {
        if (!confirm('Are you sure you want to stop this machine? You can restart it later if needed.')) {
            return;
        }

        showNotification('Stopping machine...', 'info');

        const formData = new FormData();
        formData.append('room_id', roomId);

        const response = await fetch('/api/stop_machine.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to stop machine');
        }

        // Leave the room after stopping the machine
        await leaveRoom(roomId);
        
        showNotification('Machine stopped successfully', 'success');
        openRoom(roomId);
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
    }
}

function handleRoomAction(roomId) {
    const room = currentRoom;
    if (room.user_session && room.user_session.machine_status === 'running') {
        stopMachine(roomId);
    } else if (room.user_session) {
        startMachine(roomId);
    } else {
        registerRoom(roomId);
    }
}

async function leaveRoom(roomId) {
    console.log('Leaving room:', roomId);
    try {
        const response = await fetch('/api/leave_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ room_id: roomId }), // Utiliser room_id au lieu de roomId
            credentials: 'include'
        });
        
        const data = await response.json();
        console.log('Leave room response:', data);
        
        if (!data.success) {
            throw new Error(data.message || data.error || 'Failed to leave room');
        }
        
        showNotification('Successfully left the room', 'success');
        roomModal.hide();
        loadRooms();
    } catch (error) {
        console.error('Error leaving room:', error);
        showNotification(error.message, 'error');
    }
}

function getDifficultyClass(difficulty) {
    switch (difficulty.toLowerCase()) {
        case 'easy':
            return 'difficulty-easy';
        case 'medium':
            return 'difficulty-medium';
        case 'hard':
            return 'difficulty-hard';
        default:
            return 'difficulty-medium';
    }
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

function getStatusClass(status) {
    switch(status) {
        case 'completed': return 'status-completed';
        case 'joined': return 'status-joined';
        case 'full': return 'status-occupied';
        default: return 'status-available';
    }
}

function getActionButtonText(status) {
    switch(status) {
        case 'joined': return 'Resume';
        case 'full': return 'Full';
        default: return 'Start';
    }
}

// Fonction pour charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('/api/admin/dashboard.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            const statsElements = {
                totalUsers: document.getElementById('totalUsers'),
                activeRooms: document.getElementById('activeRooms'),
                activeContainers: document.getElementById('activeContainers')
            };

            // Mettre à jour les statistiques avec une animation
            for (const [key, element] of Object.entries(statsElements)) {
                if (element) {
                    const newValue = data.data[key];
                    const currentValue = parseInt(element.textContent) || 0;
                    
                    if (newValue !== currentValue) {
                        element.style.transition = 'color 0.3s ease';
                        element.style.color = newValue > currentValue ? '#00ff9d' : '#dc3545';
                        element.textContent = newValue;
                        
                        setTimeout(() => {
                            element.style.color = '#fff';
                        }, 300);
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        showNotification('Erreur lors du chargement des statistiques', 'error');
    }
}

// Rafraîchir les statistiques toutes les 5 secondes
let statsInterval;

function startStatsRefresh() {
    // Charger les stats immédiatement
    loadStats();
    // Puis toutes les 5 secondes
    statsInterval = setInterval(loadStats, 5000);
}

function stopStatsRefresh() {
    if (statsInterval) {
        clearInterval(statsInterval);
    }
}

// Initialisation des composants Bootstrap
function initializeBootstrapComponents() {
    const modalElement = document.getElementById('roomModal');
    if (modalElement) {
        roomModal = new bootstrap.Modal(modalElement);
    }
}

// Initialize everything when the page loads
document.addEventListener('DOMContentLoaded', () => {
    createMatrixEffect();
    loadMachines();
    startStatsRefresh(); // Démarrer le rafraîchissement automatique
    loadRooms();
    initializeBootstrapComponents();
});

// Arrêter le rafraîchissement quand la page est cachée
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopStatsRefresh();
    } else {
        startStatsRefresh();
    }
});
