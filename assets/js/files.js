// assets/js/files.js

let currentView = 'details';
let selectedItems = [];

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initViewMode();
    initCheckboxes();
    initActionButtons();
    initViewer();
});

// Mode d'affichage
function initViewMode() {
    const viewBtns = document.querySelectorAll('.view-mode-btn, .view-btn');
    const containers = {
        details: document.querySelector('.details-view'),
        list: document.querySelector('.list-view'),
        icons: document.querySelector('.icons-view')
    };
    
    function switchView(view) {
        // Cacher toutes les vues
        Object.values(containers).forEach(container => {
            if (container) container.style.display = 'none';
        });
        
        // Afficher la vue sélectionnée
        if (containers[view]) containers[view].style.display = 'block';
        else if (view === 'details' && document.querySelector('.files-list')) {
            document.querySelector('.files-list').style.display = 'block';
        }
        
        // Mettre à jour les boutons actifs
        document.querySelectorAll('.view-mode-btn, .view-btn').forEach(btn => {
            if (btn.dataset.view === view) btn.classList.add('active');
            else btn.classList.remove('active');
        });
        
        currentView = view;
        
        // Mettre à jour l'URL sans recharger
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.history.pushState({}, '', url);
    }
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => switchView(btn.dataset.view));
    });
    
    // Récupérer la vue depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const savedView = urlParams.get('view');
    if (savedView && containers[savedView]) switchView(savedView);
}

// Gestion des checkboxes
function initCheckboxes() {
    const selectAll = document.getElementById('selectAllUser');
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const totalCountSpan = document.querySelector('.total-count');
    
    function updateSelectionCount() {
        const selected = document.querySelectorAll('.file-checkbox:checked');
        const count = selected.length;
        if (selectedCountSpan) {
            selectedCountSpan.textContent = `${count} élément${count > 1 ? 's' : ''} sélectionné${count > 1 ? 's' : ''}`;
        }
        
        // Mettre à jour les lignes sélectionnées
        document.querySelectorAll('.file-row, .icon-item, .list-item').forEach(row => {
            const checkbox = row.querySelector('.file-checkbox');
            if (checkbox && checkbox.checked) row.classList.add('selected');
            else row.classList.remove('selected');
        });
        
        selectedItems = Array.from(selected).map(cb => cb.value);
    }
    
    if (selectAll) {
        selectAll.addEventListener('change', (e) => {
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateSelectionCount();
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionCount);
    });
}

// Actions
function initActionButtons() {
    // Nouveau dossier
    document.getElementById('newFolderBtn')?.addEventListener('click', () => {
        document.getElementById('newFolderModal').style.display = 'flex';
    });
    
    document.getElementById('confirmFolder')?.addEventListener('click', () => {
        const name = document.getElementById('folderName').value;
        if (name) {
            fetch('create_folder.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `name=${encodeURIComponent(name)}&folder=${currentFolder || 'root'}`
            }).then(() => location.reload());
        }
        document.getElementById('newFolderModal').style.display = 'none';
        document.getElementById('folderName').value = '';
    });
    
    document.getElementById('cancelFolder')?.addEventListener('click', () => {
        document.getElementById('newFolderModal').style.display = 'none';
        document.getElementById('folderName').value = '';
    });
    
    // Importer (upload)
    document.getElementById('importBtn')?.addEventListener('click', () => {
        document.getElementById('importModal').style.display = 'flex';
    });
    
    document.getElementById('confirmImport')?.addEventListener('click', () => {
        const fileInput = document.getElementById('fileImport');
        if (fileInput.files.length > 0) {
            const formData = new FormData();
            for (let file of fileInput.files) {
                formData.append('files[]', file);
            }
            formData.append('folder', currentFolder || 'root');
            fetch('upload.php', {
                method: 'POST',
                body: formData
            }).then(() => location.reload());
        }
        document.getElementById('importModal').style.display = 'none';
        fileInput.value = '';
    });
    
    document.getElementById('cancelImport')?.addEventListener('click', () => {
        document.getElementById('importModal').style.display = 'none';
        document.getElementById('fileImport').value = '';
    });
    
    // Supprimer
    document.getElementById('deleteBtn')?.addEventListener('click', () => {
        if (selectedItems.length === 0) {
            alert('Aucun élément sélectionné');
            return;
        }
        if (confirm(`Supprimer ${selectedItems.length} élément(s) ?`)) {
            fetch('delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({items: selectedItems})
            }).then(() => location.reload());
        }
    });
    
    // Renommer
    let currentRenameItem = null;
    document.getElementById('renameBtn')?.addEventListener('click', () => {
        if (selectedItems.length !== 1) {
            alert('Sélectionnez un seul élément à renommer');
            return;
        }
        currentRenameItem = selectedItems[0];
        document.getElementById('renameModal').style.display = 'flex';
    });
    
    document.getElementById('confirmRename')?.addEventListener('click', () => {
        const newName = document.getElementById('renameInput').value;
        if (newName && currentRenameItem) {
            fetch('rename.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${encodeURIComponent(currentRenameItem)}&name=${encodeURIComponent(newName)}`
            }).then(() => location.reload());
        }
        document.getElementById('renameModal').style.display = 'none';
        document.getElementById('renameInput').value = '';
    });
    
    document.getElementById('cancelRename')?.addEventListener('click', () => {
        document.getElementById('renameModal').style.display = 'none';
        document.getElementById('renameInput').value = '';
    });
    
    // Copier/Coller
    let clipboard = null;
    document.getElementById('copyBtn')?.addEventListener('click', () => {
        if (selectedItems.length === 0) {
            alert('Aucun élément sélectionné');
            return;
        }
        clipboard = [...selectedItems];
        alert(`${clipboard.length} élément(s) copié(s)`);
    });
    
    document.getElementById('pasteBtn')?.addEventListener('click', () => {
        if (!clipboard || clipboard.length === 0) {
            alert('Rien à coller');
            return;
        }
        fetch('paste.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({items: clipboard, folder: currentFolder || 'root'})
        }).then(() => location.reload());
    });
    
    // Déplacer
    document.getElementById('moveBtn')?.addEventListener('click', () => {
        if (selectedItems.length === 0) {
            alert('Aucun élément sélectionné');
            return;
        }
        clipboard = [...selectedItems];
        document.getElementById('moveModal').style.display = 'flex';
    });
    
    document.getElementById('confirmMove')?.addEventListener('click', () => {
        const folderId = document.getElementById('folderSelect').value;
        if (clipboard && clipboard.length > 0) {
            fetch('move.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({items: clipboard, folder: folderId})
            }).then(() => location.reload());
        }
        document.getElementById('moveModal').style.display = 'none';
    });
    
    document.getElementById('cancelMove')?.addEventListener('click', () => {
        document.getElementById('moveModal').style.display = 'none';
    });
}

// Visualisation des fichiers
function initViewer() {
    const viewerModal = document.getElementById('viewerModal');
    const closeBtn = viewerModal?.querySelector('.close-viewer');
    
    document.querySelectorAll('.action-view').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const name = btn.dataset.name;
            const category = btn.dataset.category;
            const path = btn.dataset.path;
            const id = btn.dataset.id;
            
            document.getElementById('viewerTitle').textContent = name;
            
            // Cacher tous les viewers
            document.getElementById('textViewer').style.display = 'none';
            document.getElementById('audioViewer').style.display = 'none';
            document.getElementById('videoViewer').style.display = 'none';
            document.getElementById('imageViewer').style.display = 'none';
            document.getElementById('errorViewer').style.display = 'none';
            
            if (category === 'text') {
                // Afficher le contenu texte
                const response = await fetch(`view_file.php?id=${id}&type=text`);
                const content = await response.text();
                document.getElementById('textContent').textContent = content;
                document.getElementById('textViewer').style.display = 'block';
            } else if (category === 'audio') {
                // Afficher le lecteur audio
                document.getElementById('audioPlayer').src = `download.php?id=${id}&view=1`;
                document.getElementById('audioViewer').style.display = 'block';
            } else if (category === 'video') {
                // Afficher le lecteur vidéo
                document.getElementById('videoPlayer').src = `download.php?id=${id}&view=1`;
                document.getElementById('videoViewer').style.display = 'block';
            } else if (category === 'image') {
                // Afficher l'image
                document.getElementById('imagePlayer').src = `download.php?id=${id}&view=1`;
                document.getElementById('imageViewer').style.display = 'block';
            } else {
                document.getElementById('errorViewer').style.display = 'block';
            }
            
            viewerModal.style.display = 'flex';
        });
    });
    
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            viewerModal.style.display = 'none';
            // Arrêter la lecture audio/vidéo
            const audio = document.getElementById('audioPlayer');
            const video = document.getElementById('videoPlayer');
            if (audio) audio.pause();
            if (video) video.pause();
        });
    }
}

// Fermer les modals en cliquant en dehors
window.onclick = (event) => {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        const audio = document.getElementById('audioPlayer');
        const video = document.getElementById('videoPlayer');
        if (audio) audio.pause();
        if (video) video.pause();
    }
};