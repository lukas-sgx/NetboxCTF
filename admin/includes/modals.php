<?php
if (!defined('ADMIN_ACCESS')) {
    header('Location: /');
    exit;
}
?>

<!-- Modal pour éditer un utilisateur -->
<div class="modal fade modal-hack" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Éditer un utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="editUsername" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Rôle</label>
                        <select class="form-select bg-dark text-light border-secondary" id="editRole" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editUserActive">
                        <label class="form-check-label" for="editUserActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="updateUser()">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un utilisateur -->
<div class="modal fade modal-hack" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Ajouter un utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control bg-dark text-light border-secondary" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select bg-dark text-light border-secondary" id="role" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="addUser()">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour bloquer une IP -->
<div class="modal fade modal-hack" id="blockIPModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Bloquer une adresse IP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="blockIPForm">
                    <div class="mb-3">
                        <label for="ipAddress" class="form-label">Adresse IP</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="ipAddress" required>
                    </div>
                    <div class="mb-3">
                        <label for="blockDuration" class="form-label">Durée (en heures)</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="blockDuration" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="blockReason" class="form-label">Raison</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="blockReason" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="blockIP()">Bloquer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour éditer une salle -->
<div class="modal fade modal-hack" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Éditer une salle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRoomForm">
                    <input type="hidden" name="room_id">
                    <div class="mb-3">
                        <label for="edit_room_name" class="form-label">Nom</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="edit_room_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_room_description" class="form-label">Description</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="edit_room_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_room_machine" class="form-label">Machine</label>
                        <select class="form-select bg-dark text-light border-secondary" id="edit_room_machine">
                            <option value="">-- Sélectionner une machine --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_room_max_users" class="form-label">Nombre maximum d'utilisateurs</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="edit_room_max_users" required min="1">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_room_active">
                        <label class="form-check-label" for="edit_room_active">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="updateRoom()">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une salle -->
<div class="modal fade modal-hack" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Ajouter une salle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm">
                    <div class="mb-3">
                        <label for="roomName" class="form-label">Nom de la salle</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="roomName" required>
                    </div>
                    <div class="mb-3">
                        <label for="roomDescription" class="form-label">Description</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="roomDescription" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="roomCapacity" class="form-label">Capacité</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="roomCapacity" min="1" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="addRoom()">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour éditer une machine -->
<div class="modal fade modal-hack" id="editMachineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Éditer une machine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editMachineForm">
                    <input type="hidden" id="editMachineId">
                    <div class="mb-3">
                        <label for="editMachineName" class="form-label">Nom de la machine</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="editMachineName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMachineIP" class="form-label">Adresse IP</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="editMachineIP" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMachineCPU" class="form-label">CPU Limit (cores)</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="editMachineCPU" min="1" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMachineRAM" class="form-label">RAM Limit (MB)</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="editMachineRAM" min="512" step="512" required>
                    </div>
                    <div class="mb-3">
                        <label for="editMachineStatus" class="form-label">Statut</label>
                        <select class="form-select bg-dark text-light border-secondary" id="editMachineStatus" required>
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="offline">Hors ligne</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editMachineActive">
                        <label class="form-check-label" for="editMachineActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="updateMachine()">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une machine -->
<div class="modal fade modal-hack" id="addMachineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Ajouter une machine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addMachineForm">
                    <div class="mb-3">
                        <label for="machineName" class="form-label">Nom de la machine</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="machineName" required>
                    </div>
                    <div class="mb-3">
                        <label for="machineIP" class="form-label">Adresse IP</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="machineIP" required>
                    </div>
                    <div class="mb-3">
                        <label for="machineCPU" class="form-label">CPU Limit (cores)</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="machineCPU" min="1" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="machineRAM" class="form-label">RAM Limit (MB)</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="machineRAM" min="512" step="512" required>
                    </div>
                    <div class="mb-3">
                        <label for="machineStatus" class="form-label">Statut</label>
                        <select class="form-select bg-dark text-light border-secondary" id="machineStatus" required>
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="offline">Hors ligne</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="machineActive" checked>
                        <label class="form-check-label" for="machineActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-hack" onclick="addMachine()">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade modal-hack" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet élément ?</p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>
