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
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalElement => {
        modals[modalElement.id] = new bootstrap.Modal(modalElement);
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
    refreshTables();
}

// Charger les données du tableau de bord
function loadDashboardData() {
    fetch('../api/admin/get_stats.php', {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatElement('totalUsers', data.stats.totalUsers);
            updateStatElement('activeRooms', data.stats.activeRooms);
            updateStatElement('totalMachines', data.stats.totalMachines);
        }
    })
    .catch(error => {
        console.error('Error loading dashboard data:', error);
        showNotification('Erreur lors du chargement des statistiques', 'danger');
    });

    // Charger l'état de la maintenance
    fetch('../api/admin/toggle_maintenance.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.config) {
            const maintenanceSwitch = document.getElementById('maintenanceSwitch');
            const maintenanceMessage = document.getElementById('maintenance_message');
            const allowedIps = document.getElementById('allowed_ips');
            
            if (maintenanceSwitch) {
                maintenanceSwitch.checked = data.config.enabled;
                // Mettre à jour le label de statut
                const statusLabel = document.querySelector('.status-label');
                if (statusLabel) {
                    statusLabel.textContent = data.config.enabled ? 'Maintenance activée' : 'Maintenance désactivée';
                    statusLabel.style.color = data.config.enabled ? '#00ff9d' : '#dc3545';
                }
            }
            if (maintenanceMessage) {
                maintenanceMessage.value = data.config.message || '';
            }
            if (allowedIps && data.config.allowed_ips) {
                allowedIps.value = data.config.allowed_ips.join('\n');
            }
        }
    })
    .catch(error => {
        console.error('Error loading maintenance status:', error);
        showNotification('Erreur lors du chargement du statut de maintenance', 'danger');
    });
}

// Mettre à jour un élément statistique avec animation
function updateStatElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const current = parseInt(element.textContent) || 0;
    const target = parseInt(value) || 0;
    
    if (current === target) return;

    const duration = 1000;
    const stepTime = 50;
    const steps = duration / stepTime;
    const increment = (target - current) / steps;
    let currentValue = current;
    
    const updateValue = () => {
        currentValue += increment;
        if ((increment > 0 && currentValue >= target) || 
            (increment < 0 && currentValue <= target)) {
            element.textContent = target;
        } else {
            element.textContent = Math.round(currentValue);
            requestAnimationFrame(updateValue);
        }
    };

    requestAnimationFrame(updateValue);
}

// Charger les utilisateurs
function loadUsers() {
    fetch(`../api/admin/get_users.php?page=${currentPage.users}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#usersTable tbody');
            const container = document.querySelector('#usersTable').closest('.terminal-body');
            if (!tbody || !container) return;

            tbody.innerHTML = '';
            data.users.forEach(user => {
                const roleColors = {
                    'admin': 'danger',
                    'user': 'info'
                };
                const roleColor = roleColors[user.is_admin ? 'admin' : 'user'];
                
                tbody.innerHTML += `
                    <tr data-user-id="${user.id}">
                        <td>${user.id}</td>
                        <td data-field="username">${user.username}</td>
                        <td data-field="email">${user.email}</td>
                        <td data-field="role" data-role="${user.is_admin ? 'admin' : 'user'}">
                            <span class="badge bg-${roleColor}">
                                ${user.is_admin ? 'Admin' : 'User'}
                            </span>
                        </td>
                        <td data-field="is_active" data-active="${user.is_active ? '1' : '0'}">
                            <span class="badge bg-${user.is_active ? 'success' : 'danger'}">
                                ${user.is_active ? 'Actif' : 'Inactif'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            // Mettre à jour la pagination
            updatePagination(container, 'users', data.pagination.total_pages);
        }
    })
    .catch(error => console.error('Error loading users:', error));
}

// Charger les salles
function loadRooms() {
    fetch(`../api/admin/get_rooms.php?page=${currentPage.rooms}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#roomsTable tbody');
            const container = document.querySelector('#roomsTable').closest('.terminal-body');
            if (!tbody || !container) return;

            tbody.innerHTML = '';
            data.rooms.forEach(room => {
                tbody.innerHTML += `
                    <tr data-room-id="${room.id}">
                        <td>${room.id}</td>
                        <td data-field="name">${room.name}</td>
                        <td data-field="machine_name">${room.machine_name}</td>
                        <td>${room.active_users}/${room.max_users}</td>
                        <td data-field="is_active" data-active="${room.is_active ? '1' : '0'}">
                            <span class="badge bg-${room.is_active ? 'success' : 'danger'}">
                                ${room.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary" onclick="editRoom(${room.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteRoom(${room.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            // Mettre à jour la pagination
            updatePagination(container, 'rooms', data.pagination.total_pages);
        }
    })
    .catch(error => console.error('Error loading rooms:', error));
}

// Charger les machines
function loadMachines() {
    fetch(`../api/admin/get_machines.php?page=${currentPage.machines}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('#machinesTable tbody');
            const container = document.querySelector('#machinesTable').closest('.terminal-body');
            if (!tbody || !container) return;

            tbody.innerHTML = '';
            data.machines.forEach(machine => {
                tbody.innerHTML += `
                    <tr data-machine-id="${machine.id}">
                        <td>${machine.id}</td>
                        <td data-field="name">${machine.name}</td>
                        <td data-field="docker_image">${machine.docker_image}</td>
                        <td>
                            <div class="d-flex flex-column">
                                <small>CPU: ${machine.cpu_limit} cores</small>
                                <small>RAM: ${machine.memory_limit} MB</small>
                            </div>
                        </td>
                        <td data-field="is_active" data-active="${machine.is_active ? '1' : '0'}">
                            <span class="badge bg-${machine.is_active ? 'success' : 'danger'}">
                                ${machine.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary" onclick="editMachine(${machine.id})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteMachine(${machine.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            // Mettre à jour la pagination
            updatePagination(container, 'machines', data.pagination.total_pages);
        }
    })
    .catch(error => console.error('Error loading machines:', error));
}

// Charger les IPs bloquées
function loadBlockedIPs() {
    fetch(`../api/admin/get_blocked_ips.php?page=${currentPage.blockedIPs}`, {
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
    fetch(`../api/admin/get_security_events.php?page=${currentPage.securityEvents}`, {
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
    let paginationHtml = '';
    if (totalPages > 1) {
        paginationHtml = `
            <div class="d-flex justify-content-center mt-3">
                <nav>
                    <ul class="pagination">
                        <li class="page-item ${currentPage[type] === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="changePage('${type}', ${currentPage[type] - 1})">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <li class="page-item ${currentPage[type] === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage('${type}', ${i})">${i}</a>
                </li>
            `;
        }

        paginationHtml += `
                        <li class="page-item ${currentPage[type] === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="changePage('${type}', ${currentPage[type] + 1})">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        `;
    }

    // Mettre à jour ou créer l'élément de pagination
    let paginationElement = container.querySelector('.pagination-container');
    if (!paginationElement) {
        paginationElement = document.createElement('div');
        paginationElement.className = 'pagination-container';
        container.appendChild(paginationElement);
    }
    paginationElement.innerHTML = paginationHtml;
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
    setupFormListener('addUserForm', '../api/admin/add_user.php', loadUsers);
    setupFormListener('addRoomForm', '../api/admin/add_room.php', loadRooms);
    setupFormListener('addMachineForm', '../api/admin/add_machine.php', loadMachines);

    // Gestion du mode maintenance
    const maintenanceSwitch = document.getElementById('maintenanceSwitch');
    if (maintenanceSwitch) {
        maintenanceSwitch.addEventListener('change', function() {
            const message = document.getElementById('maintenance_message').value;
            const allowedIps = document.getElementById('allowed_ips').value
                .split('\n')
                .map(ip => ip.trim())
                .filter(ip => ip);

            fetch('../api/admin/toggle_maintenance.php', {
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
        fetch('../api/admin/get_maintenance.php', {
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

            fetch('../api/admin/toggle_maintenance.php', {
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
            const response = await fetch('../api/admin/add_user.php', {
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
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) {
        console.error('User row not found');
        return;
    }

    const userData = {
        id: userId,
        username: row.querySelector('[data-field="username"]')?.textContent || '',
        email: row.querySelector('[data-field="email"]')?.textContent || '',
        role: row.querySelector('[data-field="role"]')?.getAttribute('data-role') || 'user',
        is_active: row.querySelector('[data-field="is_active"]')?.getAttribute('data-active') === '1'
    };

    const modal = modals['editUserModal'];
    if (!modal) {
        console.error('Edit user modal not found');
        return;
    }

    const form = document.getElementById('editUserForm');
    if (!form) {
        console.error('Edit user form not found');
        return;
    }

    form.reset();
    
    // Remplir le formulaire
    Object.keys(userData).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = userData[key];
            } else {
                input.value = userData[key];
            }
        }
    });

    // Gérer la soumission
    form.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            id: userId,
            username: formData.get('username'),
            email: formData.get('email'),
            role: formData.get('role'),
            is_active: formData.get('is_active') === 'on'
        };

        try {
            const response = await fetch('../api/admin/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showNotification('Utilisateur mis à jour avec succès', 'success');
                modal.hide();
                loadUsers();
            } else {
                showNotification(result.error || 'Erreur lors de la mise à jour', 'danger');
            }
        } catch (error) {
            console.error('Error updating user:', error);
            showNotification('Erreur lors de la mise à jour', 'danger');
        }
    };

    modal.show();
}

// Fonction pour éditer une salle
function editRoom(roomId) {
    console.log('editRoom called with id:', roomId);
    fetch(`../api/admin/get_room.php?id=${roomId}`, {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('API Response:', response);
        return response.json();
    })
    .then(data => {
        console.log('API Data:', data);
        if (data.success) {
            const room = data.room;
            console.log('Room data:', room); // Debug log
            const modal = document.getElementById('editRoomModal');
            if (!modal) return;

            // Remplir les champs du formulaire
            modal.querySelector('input[name="room_id"]').value = room.id;
            modal.querySelector('input[name="name"]').value = room.name;
            modal.querySelector('input[name="max_users"]').value = room.max_users;
            
            // Remplir la description
            const descriptionField = document.getElementById('edit_room_description');
            if (descriptionField && room.description) {
                console.log('Setting description:', room.description);
                descriptionField.textContent = room.description;
                descriptionField.value = room.description;
            } else {
                console.log('Description field or value missing:', { field: !!descriptionField, description: room.description });
            }
            
            // Charger la liste des machines disponibles
            fetch('../api/admin/get_machines.php', {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(machineData => {
                if (machineData.success) {
                    const machineSelect = modal.querySelector('select[name="machine_id"]');
                    machineSelect.innerHTML = '';
                    
                    machineData.machines.forEach(machine => {
                        if (machine.is_active) {
                            const option = document.createElement('option');
                            option.value = machine.id;
                            option.textContent = machine.name;
                            if (machine.id === room.machine_id) {
                                option.selected = true;
                            }
                            machineSelect.appendChild(option);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading machines:', error);
                showNotification('Erreur lors du chargement des machines', 'danger');
            });

            // Mettre à jour les autres champs
            const activeSwitch = modal.querySelector('input[name="is_active"]');
            if (activeSwitch) {
                activeSwitch.checked = room.is_active;
            }

            // Configurer la soumission du formulaire
            const form = modal.querySelector('#editRoomForm');
            form.onsubmit = async (e) => {
                e.preventDefault(); // Empêcher la redirection

                const formData = new FormData(form);
                const roomData = {
                    id: formData.get('room_id'),
                    name: formData.get('name'),
                    description: formData.get('description'),
                    machine_id: formData.get('machine_id'),
                    max_users: formData.get('max_users'),
                    is_active: formData.get('is_active') === 'on'
                };

                try {
                    const response = await fetch('../api/admin/update_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(roomData)
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification('Salle mise à jour avec succès', 'success');
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                        loadRooms(); // Recharger la liste des salles
                    } else {
                        showNotification(result.error || 'Erreur lors de la mise à jour', 'danger');
                    }
                } catch (error) {
                    console.error('Error updating room:', error);
                    showNotification('Erreur lors de la mise à jour de la salle', 'danger');
                }
            };

            // Afficher le modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.show();
        } else {
            showNotification('Erreur lors du chargement de la salle', 'danger');
        }
    })
    .catch(error => {
        console.error('Error editing room:', error);
        showNotification('Erreur lors de l\'édition de la salle', 'danger');
    });
}

// Fonction pour ajouter une salle
function addRoom() {
    const modal = document.getElementById('addRoomModal');
    if (!modal) return;

    // Charger la liste des machines disponibles
    fetch('../api/admin/get_machines.php', {
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const machineSelect = modal.querySelector('select[name="machine_id"]');
            machineSelect.innerHTML = '';
            
            // Ajouter une option par défaut
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Sélectionnez une machine';
            machineSelect.appendChild(defaultOption);
            
            // Ajouter les machines actives
            data.machines.forEach(machine => {
                if (machine.is_active) {
                    const option = document.createElement('option');
                    option.value = machine.id;
                    option.textContent = machine.name;
                    machineSelect.appendChild(option);
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading machines:', error);
        showNotification('Erreur lors du chargement des machines', 'danger');
    });

    // Réinitialiser le formulaire
    const form = modal.querySelector('form');
    if (form) form.reset();

    // Afficher le modal
    const modalInstance = bootstrap.Modal.getInstance(modal);
    modalInstance.show();
}

// Fonction pour éditer une machine
function editMachine(machineId) {
    const row = document.querySelector(`tr[data-machine-id="${machineId}"]`);
    if (!row) {
        console.error('Machine row not found');
        return;
    }

    const machineData = {
        id: machineId,
        name: row.querySelector('[data-field="name"]')?.textContent || '',
        docker_image: row.querySelector('[data-field="docker_image"]')?.textContent || '',
        is_active: row.querySelector('[data-field="is_active"]')?.getAttribute('data-active') === '1'
    };

    const modal = modals['editMachineModal'];
    if (!modal) {
        console.error('Edit machine modal not found');
        return;
    }

    const form = document.getElementById('editMachineForm');
    if (!form) {
        console.error('Edit machine form not found');
        return;
    }

    form.reset();
    
    // Remplir le formulaire
    Object.keys(machineData).forEach(key => {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = machineData[key];
            } else {
                input.value = machineData[key];
            }
        }
    });

    // Gérer la soumission
    form.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            id: machineId,
            name: formData.get('name'),
            docker_image: formData.get('docker_image'),
            is_active: formData.get('is_active') === 'on'
        };

        try {
            const response = await fetch('../api/admin/update_machine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                showNotification('Machine mise à jour avec succès', 'success');
                modal.hide();
                loadMachines();
            } else {
                showNotification(result.error || 'Erreur lors de la mise à jour', 'danger');
            }
        } catch (error) {
            console.error('Error updating machine:', error);
            showNotification('Erreur lors de la mise à jour', 'danger');
        }
    };

    modal.show();
}

// Fonctions de gestion de la sécurité
function loadBlockedIPs() {
    fetch('/api/security/blocked_ips.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ipsTable = document.getElementById('blockedIPsTable');
                if (!ipsTable) return;
                
                ipsTable.innerHTML = '';
                const recentIPs = data.data.slice(0, 5); // Afficher seulement les 5 plus récents
                
                recentIPs.forEach(ip => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${ip.ip_address}</td>
                        <td>${new Date(ip.blocked_until).toLocaleString()}</td>
                        <td>${ip.reason}</td>
                    `;
                    ipsTable.appendChild(row);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des IPs bloquées:', error);
        });
}

function loadSecurityEvents() {
    fetch('/api/security/logs.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const logsTable = document.getElementById('securityLogsTable');
                if (!logsTable) return;
                
                logsTable.innerHTML = '';
                const recentLogs = data.data.slice(0, 5); // Afficher seulement les 5 plus récents
                
                recentLogs.forEach(log => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${new Date(log.created_at).toLocaleString()}</td>
                        <td>${log.event}</td>
                        <td>${log.details}</td>
                    `;
                    logsTable.appendChild(row);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des événements:', error);
        });
}

// Initialisation des fonctionnalités
document.addEventListener('DOMContentLoaded', function() {
    initializeModals();
    initializeTables();
    loadDashboardData();
    setupEventListeners();
    
    // Rafraîchissement périodique des données
    setInterval(loadDashboardData, 30000);
    setInterval(refreshTables, 30000);
});

// Fonction pour bloquer une IP
function blockIP(ip = null) {
    const ipAddress = ip || document.getElementById('ipAddress').value;
    const duration = document.getElementById('blockDuration').value;
    const reason = document.getElementById('blockReason').value;

    if (!ipAddress || !duration || !reason) {
        showNotification('Tous les champs sont requis', 'warning');
        return;
    }

    fetch('/api/security/blocked_ips.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ip_address: ipAddress,
            duration: parseInt(duration),
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('IP bloquée avec succès', 'success');
            $('#blockIPModal').modal('hide');
            loadBlockedIPs();
            loadSecurityEvents();
        } else {
            showNotification(data.error || 'Erreur lors du blocage de l\'IP', 'danger');
        }
    })
    .catch(error => {
        showNotification('Erreur lors du blocage de l\'IP', 'danger');
    });
}

// Fonction pour débloquer une IP
function unblockIP(ip) {
    if (!confirm('Êtes-vous sûr de vouloir débloquer cette IP ?')) {
        return;
    }

    fetch('/api/security/blocked_ips.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            ip_address: ip
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('IP débloquée avec succès', 'success');
            loadBlockedIPs();
            loadSecurityEvents();
        } else {
            showNotification(data.error || 'Erreur lors du déblocage de l\'IP', 'danger');
        }
    })
    .catch(error => {
        showNotification('Erreur lors du déblocage de l\'IP', 'danger');
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

// Fonction pour supprimer un utilisateur
function deleteUser(userId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        fetch('../api/admin/delete_user.php', {
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
        fetch('../api/admin/delete_room.php', {
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
        fetch('../api/admin/delete_machine.php', {
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
