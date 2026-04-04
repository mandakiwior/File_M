<?php
// settings/settings_modal.php - HTML du modal paramètres
?>
<div id="settingsModal" class="modal" style="display: none;">
    <div class="modal-content settings-modal">
        <div class="settings-header">
            <h3>⚙️ Paramètres</h3>
            <button class="close-settings">&times;</button>
        </div>
        
        <div class="settings-body">
            <!-- Section Affichage -->
            <div class="settings-section">
                <h4>📺 Affichage</h4>
                <div class="setting-item">
                    <label>Mode d'affichage par défaut</label>
                    <select id="defaultView">
                        <option value="details">📋 Détails</option>
                        <option value="list">📝 Liste</option>
                        <option value="icons">🖼️ Icônes</option>
                    </select>
                </div>
                <div class="setting-item">
                    <label>Taille des icônes</label>
                    <select id="iconSize">
                        <option value="small">Petite</option>
                        <option value="medium" selected>Moyenne</option>
                        <option value="large">Grande</option>
                    </select>
                </div>
                <div class="setting-item">
                    <label>Nombre d'éléments par page</label>
                    <select id="itemsPerPage">
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="0">Tout afficher</option>
                    </select>
                </div>
            </div>
            
            <!-- Section Notifications -->
            <div class="settings-section">
                <h4>🔔 Notifications</h4>
                <div class="setting-item">
                    <label>
                        <input type="checkbox" id="enableNotifications" checked>
                        Activer les notifications
                    </label>
                </div>
                <div class="setting-item">
                    <label>Durée d'affichage (secondes)</label>
                    <input type="number" id="notificationDuration" min="1" max="10" value="3" step="0.5">
                </div>
            </div>
            
            <!-- Section Compte -->
            <div class="settings-section">
                <h4>👤 Compte</h4>
                <div class="setting-item">
                    <button class="btn-change-password" id="changePasswordBtn">🔑 Changer le mot de passe</button>
                </div>
                <div class="setting-item">
                    <button class="btn-danger" id="deleteAccountBtn">🗑️ Supprimer mon compte</button>
                </div>
            </div>
            
            <!-- Section À propos -->
            <div class="settings-section">
                <h4>ℹ️ À propos</h4>
                <div class="setting-item">
                    <p><strong>Lun'Drive</strong> v1.0</p>
                    <p>Gestionnaire de fichiers moderne</p>
                    <p>© 2025 - Tous droits réservés</p>
                </div>
            </div>
        </div>
        
        <div class="settings-footer">
            <button class="btn-save-settings">💾 Enregistrer</button>
            <button class="btn-cancel-settings">Annuler</button>
        </div>
    </div>
</div>