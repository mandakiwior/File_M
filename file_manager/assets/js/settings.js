// assets/js/settings.js - Version corrigée
// Gestion des paramètres - Réutilisable sur toutes les pages

// Ouvrir le modal des paramètres
function openSettingsModal() {
    console.log('openSettingsModal appelée');
    
    // Charger le contenu du modal via AJAX
    fetch('settings/settings_modal.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(html => {
            console.log('HTML chargé, longueur:', html.length);
            
            // Supprimer l'ancien modal s'il existe
            const oldModal = document.getElementById('settingsModal');
            if (oldModal) {
                oldModal.remove();
                console.log('Ancien modal supprimé');
            }
            
            // Ajouter le nouveau modal
            document.body.insertAdjacentHTML('beforeend', html);
            console.log('Nouveau modal ajouté');
            
            // Vérifier que le modal est bien dans le DOM
            const modal = document.getElementById('settingsModal');
            if (!modal) {
                console.error('Modal non trouvé après insertion');
                return;
            }
            
            // Initialiser les événements du modal (avec vérification)
            try {
                initSettingsEvents();
                console.log('Événements initialisés');
            } catch(e) {
                console.error('Erreur initSettingsEvents:', e);
            }
            
            // Charger les paramètres sauvegardés
            try {
                loadSettings();
                console.log('Paramètres chargés');
            } catch(e) {
                console.error('Erreur loadSettings:', e);
            }
            
            // Afficher le modal
            modal.style.display = 'flex';
            console.log('Modal affiché avec succès');
        })
        .catch(error => {
            console.error('Erreur lors du chargement du modal:', error);
            alert('Impossible de charger les paramètres. Erreur: ' + error.message);
        });
}

// Fermer le modal
function closeSettingsModal() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        modal.style.display = 'none';
        setTimeout(() => modal.remove(), 300);
    }
}

// Afficher une notification (fallback si showNotification n'existe pas)
function showSettingsNotification(message, type = 'success', duration = 3000) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type, duration);
    } else {
        // Fallback: afficher une alerte
        console.log('Notification:', message);
        // Optionnel: décommenter pour voir les alertes
        // alert(message);
    }
}

// Initialiser les événements du modal
function initSettingsEvents() {
    console.log('initSettingsEvents appelée');
    
    // Bouton fermer
    const closeBtn = document.querySelector('.close-settings');
    console.log('closeBtn trouvé:', closeBtn);
    if (closeBtn) closeBtn.onclick = closeSettingsModal;
    
    // Bouton annuler
    const cancelBtn = document.querySelector('.btn-cancel-settings');
    console.log('cancelBtn trouvé:', cancelBtn);
    if (cancelBtn) cancelBtn.onclick = closeSettingsModal;
    
    // Bouton sauvegarder
    const saveBtn = document.querySelector('.btn-save-settings');
    console.log('saveBtn trouvé:', saveBtn);
    if (saveBtn) saveBtn.onclick = saveSettings;
    
    // Bouton changement mot de passe
    const pwdBtn = document.getElementById('changePasswordBtn');
    console.log('pwdBtn trouvé:', pwdBtn);
    if (pwdBtn) pwdBtn.onclick = changePassword;
    
    // Bouton suppression compte
    const deleteBtn = document.getElementById('deleteAccountBtn');
    console.log('deleteBtn trouvé:', deleteBtn);
    if (deleteBtn) deleteBtn.onclick = deleteAccount;
    
    // Clic en dehors du modal pour fermer
    const modal = document.getElementById('settingsModal');
    if (modal) {
        modal.onclick = function(e) {
            if (e.target === modal) closeSettingsModal();
        };
    }
}

// Charger les paramètres sauvegardés
function loadSettings() {
    const savedView = localStorage.getItem('lun_drive_default_view') || 'details';
    const savedIconSize = localStorage.getItem('lun_drive_icon_size') || 'medium';
    const savedItemsPerPage = localStorage.getItem('lun_drive_items_per_page') || '50';
    const savedNotifications = localStorage.getItem('lun_drive_notifications') !== 'false';
    const savedDuration = localStorage.getItem('lun_drive_notification_duration') || '3';
    
    const viewSelect = document.getElementById('defaultView');
    const iconSelect = document.getElementById('iconSize');
    const itemsSelect = document.getElementById('itemsPerPage');
    const notifCheckbox = document.getElementById('enableNotifications');
    const durationInput = document.getElementById('notificationDuration');
    
    if (viewSelect) viewSelect.value = savedView;
    if (iconSelect) iconSelect.value = savedIconSize;
    if (itemsSelect) itemsSelect.value = savedItemsPerPage;
    if (notifCheckbox) notifCheckbox.checked = savedNotifications;
    if (durationInput) durationInput.value = savedDuration;
    
    // Appliquer la taille des icônes
    applyIconSize(savedIconSize);
    
    // Appliquer le mode d'affichage
    applyViewMode(savedView);
}

// Sauvegarder les paramètres
function saveSettings() {
    const defaultView = document.getElementById('defaultView')?.value || 'details';
    const iconSize = document.getElementById('iconSize')?.value || 'medium';
    const itemsPerPage = document.getElementById('itemsPerPage')?.value || '50';
    const enableNotifications = document.getElementById('enableNotifications')?.checked || false;
    const notificationDuration = document.getElementById('notificationDuration')?.value || '3';
    
    localStorage.setItem('lun_drive_default_view', defaultView);
    localStorage.setItem('lun_drive_icon_size', iconSize);
    localStorage.setItem('lun_drive_items_per_page', itemsPerPage);
    localStorage.setItem('lun_drive_notifications', enableNotifications);
    localStorage.setItem('lun_drive_notification_duration', notificationDuration);
    
    // Appliquer les changements immédiatement
    applyIconSize(iconSize);
    applyViewMode(defaultView);
    
    // Afficher la notification
    showSettingsNotification('Paramètres enregistrés', 'success', 2000);
    
    closeSettingsModal();
}

// Appliquer la taille des icônes
function applyIconSize(size) {
    const fileIcons = document.querySelectorAll('.file-icon');
    fileIcons.forEach(icon => {
        if (size === 'small') {
            icon.style.fontSize = '0.875rem';
        } else if (size === 'medium') {
            icon.style.fontSize = '1.125rem';
        } else if (size === 'large') {
            icon.style.fontSize = '1.5rem';
        }
    });
}

// Appliquer le mode d'affichage
function applyViewMode(viewMode) {
    // Récupérer les conteneurs de vues
    const detailsView = document.querySelector('.details-view');
    const listView = document.querySelector('.list-view');
    const iconsView = document.querySelector('.icons-view');
    
    // Cacher toutes les vues
    if (detailsView) detailsView.style.display = 'none';
    if (listView) listView.style.display = 'none';
    if (iconsView) iconsView.style.display = 'none';
    
    // Afficher la vue sélectionnée
    if (viewMode === 'details' && detailsView) {
        detailsView.style.display = 'block';
    } else if (viewMode === 'list' && listView) {
        listView.style.display = 'block';
    } else if (viewMode === 'icons' && iconsView) {
        iconsView.style.display = 'block';
    } else if (detailsView) {
        detailsView.style.display = 'block';
    }
    
    // Mettre à jour les boutons actifs
    document.querySelectorAll('.view-btn, .view-mode-btn').forEach(btn => {
        if (btn.dataset.view === viewMode) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// Changer le mot de passe
function changePassword() {
    const oldPassword = prompt('Ancien mot de passe :');
    if (!oldPassword) return;
    
    const newPassword = prompt('Nouveau mot de passe (min. 6 caractères) :');
    if (!newPassword) return;
    
    const confirmPassword = prompt('Confirmer le nouveau mot de passe :');
    if (newPassword !== confirmPassword) {
        alert('Les mots de passe ne correspondent pas');
        return;
    }
    
    if (newPassword.length < 6) {
        alert('Le mot de passe doit contenir au moins 6 caractères');
        return;
    }
    
    fetch('settings/change_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ old_password: oldPassword, new_password: newPassword })
    })
    .then(response => response.json())
    .then(data => {
        showSettingsNotification(data.message, data.success ? 'success' : 'error', 3000);
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(error => {
        alert('Erreur lors du changement de mot de passe');
    });
}

// Supprimer le compte
function deleteAccount() {
    if (confirm('⚠️ ATTENTION : Cette action est irréversible.\n\nVoulez-vous vraiment supprimer définitivement votre compte et tous vos fichiers ?')) {
        if (confirm('Dernière confirmation : Toutes vos données seront perdues. Confirmez-vous ?')) {
            fetch('settings/delete_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Compte supprimé. Redirection...');
                    window.location.href = 'logout.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Erreur lors de la suppression');
            });
        }
    }
}

// Appliquer le mode d'affichage (fonction globale)
window.applyViewMode = function(viewMode) {
    // Récupérer les conteneurs de vues
    const detailsView = document.querySelector('.details-view');
    const listView = document.querySelector('.list-view');
    const iconsView = document.querySelector('.icons-view');
    
    // Cacher toutes les vues
    if (detailsView) detailsView.style.display = 'none';
    if (listView) listView.style.display = 'none';
    if (iconsView) iconsView.style.display = 'none';
    
    // Afficher la vue sélectionnée
    if (viewMode === 'details' && detailsView) {
        detailsView.style.display = 'block';
    } else if (viewMode === 'list' && listView) {
        listView.style.display = 'block';
    } else if (viewMode === 'icons' && iconsView) {
        iconsView.style.display = 'block';
    } else if (detailsView) {
        detailsView.style.display = 'block';
    }
    
    // Mettre à jour les boutons actifs
    document.querySelectorAll('.view-mode-btn').forEach(btn => {
        if (btn.dataset.view === viewMode) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Sauvegarder dans localStorage
    localStorage.setItem('lun_drive_default_view', viewMode);
};

// Initialiser le mode d'affichage au chargement
function initViewMode() {
    const savedView = localStorage.getItem('lun_drive_default_view') || 'details';
    if (typeof window.applyViewMode === 'function') {
        window.applyViewMode(savedView);
    }
}

// Exécuter après chargement
document.addEventListener('DOMContentLoaded', function() {
    initViewMode();
});

console.log('settings.js chargé avec succès');