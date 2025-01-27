// Debug log
console.log('Admin.js loaded');

// Variables globales
let usersTable = null;
let roomsTable = null;
let machinesTable = null;
let modals = {};
let currentPage = {
    users: 1,
    rooms: 1,
    machines: 1,
    blockedIPs: 1,
    securityEvents: 1
};

// Fonction d'initialisation
document.addEventListener('DOMContentLoaded', () => {
    initializeModals();
    initializeTables();
    loadDashboardData();
    setupEventListeners();
    setInterval(loadDashboardData, 30000);
    setInterval(refreshTables, 30000);
});

// Initialisation des modales
function initializeModals() {
    // Initialiser toutes les modales Bootstrap
    document.querySelectorAll('.modal').forEach(modalEl => {
        new bootstrap.Modal(modalEl, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    });
}

// Rafraîchir tous les tableaux
function refreshTables() {
    loadUsers();
    loadRooms();
    loadMachines();
    loadBlockedIPs();
    loadSecurityEvents();
}

// Initialiser les tableaux
function initializeTables() {
    loadUsers();
    loadRooms();
    loadMachines();
    loadDashboardData();
    loadBlockedIPs();
    loadSecurityEvents();
}

// Charger les données du tableau de bord
function loadDashboardData() {
    console.log('Loading dashboard data...');
    fetch('/api/admin/get_stats.php', {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Received stats:', data);
        if (data.success) {
            // Mise à jour des compteurs et labels pour les machines
            const activeMachinesEl = document.getElementById('totalUsers');
            const activeMachinesLabel = document.getElementById('totalUsersLabel');
            
            // Mise à jour des compteurs et labels pour les utilisateurs actifs
            const activeUsersEl = document.getElementById('activeUsers');
            const activeUsersLabel = document.getElementById('activeUsersLabel');
            
            // Mise à jour des compteurs et labels pour les salles
            const activeRoomsEl = document.getElementById('totalRooms');
            const activeRoomsLabel = document.getElementById('totalRoomsLabel');
            
            if (activeMachinesEl) activeMachinesEl.textContent = data.stats.active_machines || 0;
            if (activeMachinesLabel) activeMachinesLabel.textContent = 'Active Machines';
            
            if (activeUsersEl) activeUsersEl.textContent = data.stats.active_users || 0;
            if (activeUsersLabel) activeUsersLabel.textContent = 'Active Users';
            
            if (activeRoomsEl) activeRoomsEl.textContent = data.stats.active_rooms || 0;
            if (activeRoomsLabel) activeRoomsLabel.textContent = 'Active Rooms';
        }
    })
    .catch(error => {
        console.error('Error loading dashboard data:', error);
    });
}

// Charger les utilisateurs
function loadUsers() {
    fetch(`/api/admin/get_users.php?page=${currentPage.users}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#usersTable tbody');
            if (!tbody) {
                console.error('Users table body not found');
                return;
            }
            tbody.innerHTML = '';
            
            data.users.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <span class="badge ${user.is_active ? 'bg-success' : 'bg-danger'}">
                            ${user.is_active ? 'Actif' : 'Inactif'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editUser(${user.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Mise à jour de la pagination
            if (data.pagination) {
                const container = document.querySelector('#usersTable').closest('.table-responsive').querySelector('.pagination-container');
                updatePagination(container, 'users', data.pagination.total_pages);
            }
        }
    })
    .catch(error => {
        console.error('Error loading users:', error);
    });
}

// Charger les salles
function loadRooms() {
    fetch(`/api/admin/get_rooms.php?page=${currentPage.rooms}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#roomsTable tbody');
            if (!tbody) {
                console.error('Rooms table body not found');
                return;
            }
            tbody.innerHTML = '';
            
            data.rooms.forEach(room => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${room.id}</td>
                    <td>${room.name}</td>
                    <td>${room.machine_name || 'Aucune'}</td>
                    <td>${room.active_users} / ${room.max_users}</td>
                    <td>
                        <span class="badge ${room.is_active ? 'bg-success' : 'bg-danger'}">
                            ${room.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editRoom(${room.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(${room.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Mise à jour de la pagination
            if (data.pagination) {
                const container = document.querySelector('#roomsTable').closest('.table-responsive').querySelector('.pagination-container');
                updatePagination(container, 'rooms', data.pagination.total_pages);
            }
        }
    })
    .catch(error => {
        console.error('Error loading rooms:', error);
    });
}

// Charger les machines
function loadMachines() {
    fetch(`/api/admin/get_machines.php?page=${currentPage.machines}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#machinesTable tbody');
            if (!tbody) {
                console.error('Machines table body not found');
                return;
            }
            tbody.innerHTML = '';
            
            data.machines.forEach(machine => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${machine.id}</td>
                    <td>${machine.name}</td>
                    <td>${machine.docker_image}</td>
                    <td>CPU: ${machine.cpu_limit || '0'}, RAM: ${machine.ram_limit || '0'} MB</td>
                    <td>
                        <span class="badge ${machine.is_active ? 'bg-success' : 'bg-danger'}">
                            ${machine.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editMachine(${machine.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMachine(${machine.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Mise à jour de la pagination
            if (data.pagination) {
                const container = document.querySelector('#machinesTable').closest('.table-responsive').querySelector('.pagination-container');
                updatePagination(container, 'machines', data.pagination.total_pages);
            }
        }
    })
    .catch(error => {
        console.error('Error loading machines:', error);
    });
}

// Charger les IPs bloquées
function loadBlockedIPs() {
    fetch('/api/admin/get_blocked_ips.php?page=${currentPage.blockedIPs}', {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#blockedIPsTable tbody');
            const container = document.querySelector('#blockedIPsTable').closest('.terminal-body');
            if (!tbody || !container) return;

            tbody.innerHTML = '';
            data.blocked_ips.forEach(ip => {
                tbody.innerHTML += `
                    <tr data-ip="${ip.ip}">
                        <td>${ip.ip}</td>
                        <td>${ip.blocked_at}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="unblockIP('${ip.ip}')">
                                <i class="bi bi-unlock"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            // Mettre à jour la pagination
            updatePagination(container, 'blockedIPs', data.pagination.total_pages);
        }
    })
    .catch(error => console.error('Error loading blocked IPs:', error));
}

// Charger les événements de sécurité
function loadSecurityEvents() {
    fetch('/api/admin/get_security_events.php?page=${currentPage.securityEvents}', {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#securityEventsTable tbody');
            const container = document.querySelector('#securityEventsTable').closest('.terminal-body');
            if (!tbody || !container) return;

            tbody.innerHTML = '';
            data.events.forEach(event => {
                const statusClass = event.success ? 'success' : 'danger';
                tbody.innerHTML += `
                    <tr>
                        <td>${event.username}</td>
                        <td>${event.ip}</td>
                        <td>${event.timestamp}</td>
                        <td>
                            <span class="badge bg-${statusClass}">
                                ${event.success ? 'Succès' : 'Échec'}
                            </span>
                        </td>
                    </tr>
                `;
            });

            // Mettre à jour la pagination
            updatePagination(container, 'securityEvents', data.pagination.total_pages);
        }
    })
    .catch(error => console.error('Error loading security events:', error));
}

// Fonction pour mettre à jour la pagination
function updatePagination(container, type, totalPages) {
    if (!container) return;
    
    let paginationHtml = '';
    if (totalPages > 1) {
        paginationHtml = `
            <div class="d-flex justify-content-center mt-3">
                <nav>
                    <ul class="pagination">
                        <li class="page-item ${currentPage[type] === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="event.preventDefault(); changePage('${type}', ${currentPage[type] - 1}); return false;">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <li class="page-item ${currentPage[type] === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); changePage('${type}', ${i}); return false;">${i}</a>
                </li>
            `;
        }

        paginationHtml += `
                        <li class="page-item ${currentPage[type] === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="event.preventDefault(); changePage('${type}', ${currentPage[type] + 1}); return false;">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        `;
    }

    container.innerHTML = paginationHtml;
}

// Fonction pour changer de page
function changePage(type, newPage) {
    currentPage[type] = newPage;
    switch (type) {
        case 'users':
            loadUsers();
            break;
        case 'rooms':
            loadRooms();
            break;
        case 'machines':
            loadMachines();
            break;
        case 'blockedIPs':
            loadBlockedIPs();
            break;
        case 'securityEvents':
            loadSecurityEvents();
            break;
    }
}

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Gestionnaires d'onglets
    const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', (event) => {
            const targetId = event.target.getAttribute('data-bs-target');
            if (targetId === '#users') {
                loadUsers();
            } else if (targetId === '#rooms') {
                loadRooms();
            } else if (targetId === '#machines') {
                loadMachines();
            } else if (targetId === '#blockedIPs') {
                loadBlockedIPs();
            } else if (targetId === '#securityEvents') {
                loadSecurityEvents();
            }
        });
    });

    // Gestionnaire pour le bouton d'ajout d'utilisateur
    const addUserBtn = document.getElementById('addUserBtn');
    if (addUserBtn) {
        addUserBtn.onclick = () => addUser();
    }

    // Gestionnaire pour le bouton d'ajout de salle
    const addRoomBtn = document.getElementById('addRoomBtn');
    if (addRoomBtn) {
        addRoomBtn.onclick = () => addRoom();
    }

    // Boutons d'ajout
    const addMachineBtn = document.getElementById('addMachineBtn');
    if (addMachineBtn) {
        addMachineBtn.addEventListener('click', () => {
            const modal = modals['addMachineModal'];
            if (modal) {
                modal.show();
            } else {
                console.error('Add machine modal not found');
            }
        });
    }

    // Formulaires d'ajout
    setupFormListener('addUserForm', '/api/admin/add_user.php', loadUsers);
    setupFormListener('addRoomForm', '/api/admin/add_room.php', loadRooms);
    setupFormListener('addMachineForm', '/api/admin/add_machine.php', loadMachines);

    // Gestion du mode maintenance
    const maintenanceSwitch = document.getElementById('maintenanceSwitch');
    if (maintenanceSwitch) {
        maintenanceSwitch.addEventListener('change', function() {
            const message = document.getElementById('maintenance_message').value;
            const allowedIps = document.getElementById('allowed_ips').value
                .split('\n')
                .map(ip => ip.trim())
                .filter(ip => ip);

            fetch('/api/admin/toggle_maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    enabled: this.checked,
                    message: message,
                    allowed_ips: allowedIps
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(
                        this.checked ? 'Mode maintenance activé' : 'Mode maintenance désactivé',
                        'success'
                    );
                    // Mettre à jour le label de statut
                    const statusLabel = document.querySelector('.status-label');
                    if (statusLabel) {
                        statusLabel.textContent = this.checked ? 'Maintenance activée' : 'Maintenance désactivée';
                        statusLabel.style.color = this.checked ? '#00ff9d' : '#dc3545';
                    }
                } else {
                    showNotification(data.error || 'Erreur lors du changement de mode', 'danger');
                    this.checked = !this.checked; // Remettre le switch dans son état précédent
                }
            })
            .catch(error => {
                console.error('Error toggling maintenance:', error);
                showNotification('Erreur lors du changement de mode', 'danger');
                this.checked = !this.checked; // Remettre le switch dans son état précédent
            });
        });
    }

    // Fonction pour charger l'état de la maintenance
    function loadMaintenanceStatus() {
        fetch('/api/admin/get_maintenance.php', {
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const maintenanceSwitch = document.getElementById('maintenanceSwitch');
            const maintenanceMessage = document.getElementById('maintenance_message');
            const allowedIps = document.getElementById('allowed_ips');
            
            if (maintenanceSwitch) {
                maintenanceSwitch.checked = data.enabled;
            }
            if (maintenanceMessage) {
                maintenanceMessage.value = data.message;
            }
            if (allowedIps && data.allowed_ips) {
                allowedIps.value = data.allowed_ips.join('\n');
            }
        })
        .catch(error => {
            console.error('Error loading maintenance status:', error);
            showNotification('Erreur lors du chargement du statut de maintenance', 'danger');
        });
    }

    // Gestionnaire de l'ouverture de la modal de maintenance
    const maintenanceModal = document.getElementById('maintenanceModal');
    if (maintenanceModal) {
        maintenanceModal.addEventListener('show.bs.modal', function () {
            loadMaintenanceStatus();
        });
    }

    // Gestionnaire du bouton de sauvegarde du message de maintenance
    const saveMaintenanceBtn = document.getElementById('saveMaintenanceBtn');
    if (saveMaintenanceBtn) {
        saveMaintenanceBtn.addEventListener('click', function() {
            const message = document.getElementById('maintenance_message').value;
            const duration = document.getElementById('maintenance_duration').value;
            const durationUnit = document.getElementById('maintenance_duration_unit').value;
            
            // Calculer l'heure de fin
            let endTime = null;
            if (duration && duration > 0) {
                const now = new Date();
                switch (durationUnit) {
                    case 'minutes':
                        endTime = new Date(now.getTime() + duration * 60000);
                        break;
                    case 'hours':
                        endTime = new Date(now.getTime() + duration * 3600000);
                        break;
                    case 'days':
                        endTime = new Date(now.getTime() + duration * 86400000);
                        break;
                }
            }

            fetch('/api/admin/toggle_maintenance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    enabled: document.getElementById('maintenanceSwitch').checked,
                    message: message,
                    end_time: endTime ? endTime.toISOString() : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Configuration de maintenance mise à jour', 'success');
                    const modal = bootstrap.Modal.getInstance(maintenanceModal);
                    modal.hide();
                } else {
                    showNotification(data.error || 'Erreur lors de la mise à jour', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur lors de la mise à jour', 'danger');
            });
        });
    }
}

// Fonction pour ajouter un utilisateur
function addUser() {
    const modal = document.getElementById('addUserModal');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (!form) return;

    form.onsubmit = async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const userData = {
            username: formData.get('username'),
            email: formData.get('email'),
            password: formData.get('password'),
            is_admin: formData.get('role') === 'admin',
            is_active: formData.get('is_active') === 'on'
        };

        try {
            const response = await fetch('/api/admin/add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            });

            const result = await response.json();
            if (result.success) {
                showNotification('Utilisateur créé avec succès', 'success');
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                form.reset();
                loadUsers(); // Recharger la liste des utilisateurs
            } else {
                showNotification(result.error || 'Erreur lors de la création de l\'utilisateur', 'danger');
            }
        } catch (error) {
            console.error('Error adding user:', error);
            showNotification('Erreur lors de la création de l\'utilisateur', 'danger');
        }
    };

    // Réinitialiser le formulaire et afficher le modal
    form.reset();
    const modalInstance = bootstrap.Modal.getInstance(modal);
    modalInstance.show();
}

// Configuration des écouteurs de formulaire
function setupFormListener(formId, endpoint, reloadFunction) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'is_active') {
                    data[key] = value === 'on';
                } else {
                    data[key] = value;
                }
            });

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Élément ajouté avec succès', 'success');
                    const modalId = form.closest('.modal').id;
                    const modal = modals[modalId];
                    if (modal) {
                        modal.hide();
                    }
                    form.reset();
                    if (reloadFunction) {
                        reloadFunction();
                    }
                } else {
                    showNotification(result.error || 'Erreur lors de l\'ajout', 'danger');
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                showNotification('Erreur lors de l\'ajout', 'danger');
            }
        });
    }
}

// Fonction pour éditer un utilisateur
function editUser(userId) {
    // Récupérer les données de l'utilisateur depuis l'API
    fetch(`/api/admin/get_user.php?id=${userId}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const user = data.user;
            
            // Remplir les champs du formulaire
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            
            // Gérer correctement la case à cocher is_active
            const isActive = user.is_active === '1' || user.is_active === 1 || user.is_active === true;
            document.getElementById('editUserActive').checked = isActive;
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        } else {
            showNotification('Erreur lors de la récupération des données', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la récupération des données', 'error');
    });
}

// Fonction pour mettre à jour un utilisateur
function updateUser() {
    const userId = document.getElementById('editUserId').value;
    const username = document.getElementById('editUsername').value;
    const email = document.getElementById('editEmail').value;
    const role = document.getElementById('editRole').value;
    const isActive = document.getElementById('editUserActive').checked;

    const userData = {
        id: userId,
        username: username,
        email: email,
        role: role,
        is_active: isActive
    };

    fetch('/api/admin/update_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Utilisateur mis à jour avec succès');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            loadUsers(); // Recharger la liste des utilisateurs
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    });
}

// Fonction pour éditer une salle
function editRoom(roomId) {
    let roomData = null;
    const editRoomModal = document.getElementById('editRoomModal');
    const modal = new bootstrap.Modal(editRoomModal);

    fetch(`/api/admin/get_room.php?id=${roomId}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            roomData = data.room;
            
            // Remplir les champs du formulaire avec les IDs corrects
            document.querySelector('#editRoomForm input[name="room_id"]').value = roomData.id;
            document.getElementById('edit_room_name').value = roomData.name;
            document.getElementById('edit_room_description').value = roomData.description || '';
            document.getElementById('edit_room_max_users').value = roomData.max_users;
            document.getElementById('edit_room_active').checked = roomData.is_active === '1' || roomData.is_active === true;
            
            // Charger les machines disponibles
            return fetch('/api/admin/get_machines.php');
        }
        throw new Error('Failed to get room data');
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const machineSelect = document.getElementById('edit_room_machine');
            machineSelect.innerHTML = '';
            
            // Ajouter une option vide
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-- Sélectionner une machine --';
            machineSelect.appendChild(emptyOption);
            
            data.machines.forEach(machine => {
                const option = document.createElement('option');
                option.value = machine.id;
                option.textContent = machine.name;
                if (machine.id === roomData.machine_id) {
                    option.selected = true;
                }
                machineSelect.appendChild(option);
            });
            
            // Afficher le modal
            modal.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la récupération des données', 'error');
    });
}

// Fonction pour mettre à jour une salle
function updateRoom() {
    const form = document.getElementById('editRoomForm');
    const formData = {
        id: form.querySelector('input[name="room_id"]').value,
        name: document.getElementById('edit_room_name').value,
        description: document.getElementById('edit_room_description').value,
        machine_id: document.getElementById('edit_room_machine').value,
        max_users: document.getElementById('edit_room_max_users').value,
        is_active: document.getElementById('edit_room_active').checked ? 1 : 0
    };

    fetch('/api/admin/update_room.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editRoomModal'));
            modal.hide();
            
            // Rafraîchir la liste des salles
            loadRooms();
            
            showNotification('Salle mise à jour avec succès', 'success');
        } else {
            throw new Error(data.error || 'Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour de la salle', 'error');
    });
}

// Fonction pour éditer une machine
function editMachine(machineId) {
    fetch(`/api/admin/get_machine.php?id=${machineId}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const machine = data.machine;
            
            // Remplir les champs du formulaire
            document.getElementById('editMachineId').value = machine.id;
            document.getElementById('editMachineName').value = machine.name;
            document.getElementById('editMachineIP').value = machine.ip;
            document.getElementById('editMachineCPU').value = machine.cpu_limit || 1;
            document.getElementById('editMachineRAM').value = machine.ram_limit || 512;
            document.getElementById('editMachineStatus').value = machine.status;
            document.getElementById('editMachineActive').checked = machine.is_active === '1' || machine.is_active === 1 || machine.is_active === true;
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('editMachineModal'));
            modal.show();
        } else {
            showNotification('Erreur lors de la récupération des données', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la récupération des données', 'error');
    });
}

// Fonction pour mettre à jour une machine
function updateMachine() {
    const machineId = document.getElementById('editMachineId').value;
    const machineName = document.getElementById('editMachineName').value;
    const machineIP = document.getElementById('editMachineIP').value;
    const machineCPU = document.getElementById('editMachineCPU').value;
    const machineRAM = document.getElementById('editMachineRAM').value;
    const machineStatus = document.getElementById('editMachineStatus').value;
    const isActive = document.getElementById('editMachineActive').checked;

    const data = {
        id: machineId,
        name: machineName,
        ip: machineIP,
        cpu_limit: machineCPU,
        ram_limit: machineRAM,
        status: machineStatus,
        is_active: isActive
    };

    fetch('/api/admin/update_machine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Machine mise à jour avec succès');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editMachineModal'));
            modal.hide();
            loadMachines();
        } else {
            showNotification(result.error || 'Erreur lors de la mise à jour', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    });
}

// Fonction pour ajouter une machine
function addMachine() {
    const machineName = document.getElementById('machineName').value;
    const machineIP = document.getElementById('machineIP').value;
    const machineCPU = document.getElementById('machineCPU').value;
    const machineRAM = document.getElementById('machineRAM').value;
    const machineStatus = document.getElementById('machineStatus').value;
    const isActive = document.getElementById('machineActive').checked;

    const data = {
        name: machineName,
        ip: machineIP,
        cpu_limit: machineCPU,
        ram_limit: machineRAM,
        status: machineStatus,
        is_active: isActive
    };

    fetch('/api/admin/add_machine.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Machine ajoutée avec succès');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addMachineModal'));
            modal.hide();
            document.getElementById('addMachineForm').reset();
            loadMachines();
        } else {
            showNotification(result.error || 'Erreur lors de l\'ajout', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de l\'ajout', 'error');
    });
}

// Fonctions de gestion de la sécurité
function loadBlockedIPs() {
    fetch('/api/admin/get_blocked_ips.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#blockedIPsTable tbody');
                if (!tbody) return;
                
                tbody.innerHTML = '';
                const blockedIps = Array.isArray(data.blocked_ips) ? data.blocked_ips : [];
                
                blockedIps.forEach(ip => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <i class="bi bi-laptop me-2"></i>
                            ${ip.ip_address}
                        </td>
                        <td>
                            <i class="bi bi-calendar-event me-1"></i>
                            ${ip.blocked_at}
                        </td>
                        <td>
                            <button class="btn btn-hack btn-success btn-sm" 
                                    onclick="unblockIp('${ip.ip_address}')"
                                    title="Débloquer cette IP">
                                <i class="bi bi-unlock"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des IPs bloquées:', error);
            showNotification('Erreur lors du chargement des IPs bloquées', 'error');
        });
}

function loadSecurityEvents() {
    fetch('/api/admin/get_security_events.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#securityEventsTable tbody');
                if (!tbody) return;
                
                tbody.innerHTML = '';
                const events = Array.isArray(data.events) ? data.events : [];
                
                events.forEach(event => {
                    const statusClass = event.success ? 'success' : 'danger';
                    tbody.innerHTML += `
                        <tr>
                            <td>
                                <i class="bi bi-person me-1"></i>
                                ${event.username}
                            </td>
                            <td>
                                <i class="bi bi-laptop me-1"></i>
                                ${event.ip_address}
                            </td>
                            <td>
                                <i class="bi bi-calendar-event me-1"></i>
                                ${event.attempt_time}
                            </td>
                            <td>
                                <span class="badge ${event.success ? 'bg-success' : 'bg-danger'}">
                                    <i class="bi ${event.success ? 'bi-check-circle' : 'bi-x-circle'} me-1"></i>
                                    ${event.success ? 'Succès' : 'Échec'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des événements:', error);
            showNotification('Erreur lors du chargement des événements', 'error');
        });
}

// Fonction pour supprimer un utilisateur
function deleteUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        fetch('/api/admin/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Utilisateur supprimé avec succès', 'success');
                loadUsers();
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            showNotification('Erreur lors de la suppression de l\'utilisateur', 'danger');
        });
    }
}

// Fonction pour supprimer une salle
function deleteRoom(roomId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette salle ?')) {
        fetch('/api/admin/delete_room.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ id: roomId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Salle supprimée avec succès', 'success');
                loadRooms();
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Error deleting room:', error);
            showNotification('Erreur lors de la suppression de la salle', 'danger');
        });
    }
}

// Fonction pour supprimer une machine
function deleteMachine(machineId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette machine ?')) {
        fetch('/api/admin/delete_machine.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ id: machineId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Machine supprimée avec succès', 'success');
                loadMachines();
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Error deleting machine:', error);
            showNotification('Erreur lors de la suppression de la machine', 'danger');
        });
    }
}

// Fonction pour bloquer une IP
function blockIP(ip = null) {
    if (ip) {
        // Si une IP est fournie, ouvrir la modale avec l'IP pré-remplie
        const modal = new bootstrap.Modal(document.getElementById('blockIPModal'));
        document.getElementById('ipAddress').value = ip;
        modal.show();
        return;
    }

    // Récupérer les valeurs du formulaire
    const ipAddress = document.getElementById('ipAddress').value;
    const duration = document.getElementById('blockDuration').value;
    const reason = document.getElementById('blockReason').value;

    if (!ipAddress || !duration || !reason) {
        showNotification('Tous les champs sont requis', 'warning');
        return;
    }

    fetch('/api/admin/ip_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'block',
            ip: ipAddress,
            duration: duration,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('IP bloquée avec succès', 'success');
            bootstrap.Modal.getInstance(document.getElementById('blockIPModal')).hide();
            loadBlockedIPs(); // Recharger la liste des IPs bloquées
        } else {
            showNotification(data.message || 'Erreur lors du blocage de l\'IP', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors du blocage de l\'IP', 'error');
    });
}

// Fonction pour débloquer une IP
function unblockIP(ip) {
    if (!confirm('Êtes-vous sûr de vouloir débloquer cette IP ?')) {
        return;
    }

    fetch('/api/admin/ip_management.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ip: ip
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('IP débloquée avec succès', 'success');
            loadBlockedIPs();
            loadSecurityEvents();
        } else {
            showNotification(data.message || 'Erreur lors du déblocage de l\'IP', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors du déblocage de l\'IP', 'error');
    });
}

// Système de notification amélioré
function showNotification(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toastContainer.removeChild(toast);
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Fonction pour basculer le statut d'un utilisateur
function toggleUserStatus(userId, isActive) {
    fetch('/api/admin/update_user_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            id: userId,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut de l\'utilisateur mis à jour');
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour du statut', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    });
}

// Fonction pour basculer le statut d'une salle
function toggleRoomStatus(roomId, isActive) {
    fetch('/api/admin/update_room_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            id: roomId,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut de la salle mis à jour');
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour du statut', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    });
}

// Fonction pour basculer le statut d'une machine
function toggleMachineStatus(machineId, isActive) {
    fetch('/api/admin/update_machine_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            id: machineId,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut de la machine mis à jour');
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour du statut', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    });
}
