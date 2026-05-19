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
            
            <!-- Section Aide -->
            <div class="settings-section">
                <h4>❓ Aide</h4>
                <div class="setting-item">
                    <button class="btn-help-settings" id="openHelpBtn" type="button" onclick="openHelpModal(); return false;">Afficher le guide</button>
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
            <button class="btn-save-settings" id="saveSettingsBtn">💾 Enregistrer</button>
            <button class="btn-cancel-settings" id="cancelSettingsBtn">Annuler</button>
        </div>
    </div>
</div>

<div id="helpModal" class="modal" style="display: none;">
    <div class="modal-content help-modal">
        <div class="settings-header">
            <h3>❓ Guide d'utilisation</h3>
            <button class="close-settings" id="closeHelpBtn">&times;</button>
        </div>
        <div class="settings-body">
            <div class="help-content">
                <h4>Navigation générale</h4>
                <p>Utilisez les onglets en haut pour basculer entre votre espace utilisateur et l'espace sécurisé.</p>
                <p>Le chemin du dossier courant est affiché dans le breadcrumb pour savoir où vous êtes dans l'arborescence.</p>

                <h4>Créer un dossier</h4>
                <p>Cliquez sur « Nouveau dossier », saisissez le nom, puis confirmez pour créer un sous-dossier dans le dossier courant.</p>

                <h4>Importer un fichier</h4>
                <p>Cliquez sur « Importer » pour sélectionner un fichier depuis votre ordinateur. Le fichier sera ajouté au dossier courant.</p>

                <h4>Sélectionner des éléments</h4>
                <p>Cliquez sur les cases à cocher pour sélectionner un ou plusieurs dossiers/fichiers.</p>
                <ul>
                    <li>Copier : met les éléments dans le presse-papiers.</li>
                    <li>Couper : prépare les éléments pour déplacement.</li>
                    <li>Coller : place les éléments copiés ou coupés dans le dossier courant.</li>
                </ul>

                <h4>Renommer</h4>
                <p>Sélectionnez un seul élément, puis cliquez sur « Renommer ». Entrez le nouveau nom et confirmez.</p>

                <h4>Supprimer</h4>
                <p>Sélectionnez un ou plusieurs éléments, puis cliquez sur « Supprimer ». Confirmez l'action dans la boîte de dialogue.</p>

                <h4>Changer de vue</h4>
                <p>Le footer contient les boutons pour passer en vue Détails, Liste ou Icônes. Choisissez celle que vous préférez.</p>

                <h4>Recherche</h4>
                <p>Le champ de recherche filtre en temps réel les éléments affichés dans la vue active.</p>

                <h4>Paramètres</h4>
                <p>Dans le modal Paramètres, vous pouvez modifier :</p>
                <ul>
                    <li>la vue par défaut,</li>
                    <li>la taille des icônes,</li>
                    <li>le nombre d'éléments par page,</li>
                    <li>l'activation des notifications.</li>
                </ul>
            </div>
        </div>
        <div class="settings-footer">
            <button class="btn-close-help" id="closeHelpFooterBtn">Fermer</button>
        </div>
    </div>
</div>