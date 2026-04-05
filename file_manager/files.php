<?php
// files.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

// Redirection par défaut vers l'onglet utilisateur si aucun tab spécifié
if (!isset($_GET['tab']) && !isset($_GET['system_folder'])) {
    header('Location: files.php?tab=user&folder=root');
    exit();
}

// Récupérer les messages de notification depuis l'URL
$successMsg = isset($_GET['success']) ? urldecode($_GET['success']) : '';
$errorMsg = isset($_GET['error']) ? urldecode($_GET['error']) : '';

// Nettoyer les messages pour éviter les injections
$successMsg = htmlspecialchars($successMsg, ENT_QUOTES, 'UTF-8');
$errorMsg = htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8');

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$activeTab = $_GET['tab'] ?? 'user';
$currentFolder = $_GET['folder'] ?? 'root';
$viewMode = $_GET['view'] ?? 'details';
$systemCurrentFolder = $_GET['system_folder'] ?? 'root';

$database = new Database();
$pdo = $database->getConnection();


// Récupérer les fichiers système
$systemFiles = $pdo->query("SELECT * FROM system_files ORDER BY type DESC, name")->fetchAll();


// Récupérer les dossiers utilisateur du niveau courant
if ($currentFolder === 'root') {
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND parent_id IS NULL ORDER BY name");
    $stmt->execute([$userId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND parent_id = ? ORDER BY name");
    $stmt->execute([$userId, $currentFolder]);
}
$userFolders = $stmt->fetchAll();

// Récupérer les fichiers utilisateur du dossier courant
if ($currentFolder === 'root') {
    $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND folder_id IS NULL ORDER BY created_at DESC");
    $stmt->execute([$userId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND folder_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId, $currentFolder]);
}
$userFiles = $stmt->fetchAll();

// Récupérer TOUS les dossiers utilisateur (pour le sélecteur de déplacement)
$stmtAll = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? ORDER BY name");
$stmtAll->execute([$userId]);
$allUserFolders = $stmtAll->fetchAll();

// Récupérer les infos du dossier courant pour le breadcrumb
$currentFolderData = null;
if ($currentFolder !== 'root') {
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$currentFolder, $userId]);
    $currentFolderData = $stmt->fetch();
}

// Fonctions utilitaires
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 0) . ' Ko';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 0) . ' Ko';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 0) . ' Ko';
    }
    return $bytes . ' o';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getFileIcon($filename, $type) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'txt' => '📝', 'pdf' => '📑', 'jpg' => '🖼️', 'jpeg' => '🖼️',
        'png' => '🖼️', 'gif' => '🖼️', 'mp3' => '🎵', 'mp4' => '🎬',
        'mov' => '🎬', 'avi' => '🎬', 'doc' => '📄', 'docx' => '📄',
        'xls' => '📊', 'xlsx' => '📊', 'zip' => '📦', 'rar' => '📦'
    ];
    return $icons[$ext] ?? ($type === 'directory' ? '📁' : '📄');
}

function getFileTypeCategory($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $audio = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
    $video = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    $image = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'];
    $text = ['txt', 'md', 'json', 'xml', 'csv', 'log', 'php', 'js', 'css', 'html'];
    
    if (in_array($ext, $audio)) return 'audio';
    if (in_array($ext, $video)) return 'video';
    if (in_array($ext, $image)) return 'image';
    if (in_array($ext, $text)) return 'text';
    return 'file';
}

$totalItems = count($userFolders) + count($userFiles);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes fichiers - Lun'Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="files-page">
    <div class="files-wrapper">
        <!-- Sidebar -->
        <aside class="files-sidebar">
            <div>
                <div class="sidebar-logo">
                    <h1>LUN'DRIVE</h1>
                </div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Tableau de bord</span>
                    </a>
                    <a href="files.php" class="nav-link active">
                        <span class="nav-icon">📁</span>
                        <span>Mes fichiers</span>
                    </a>
                </nav>
            </div>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar-small"><?= strtoupper(substr($username, 0, 2)) ?></div>
                    <div class="user-details">
                        <div class="user-name-sidebar"><?= htmlspecialchars($username) ?></div>
                        <div class="user-email-sidebar"><?= htmlspecialchars($_SESSION['email']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn-sidebar">SE DÉCONNECTER</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="files-main">
            <!-- En-tête avec onglets -->
            <div class="files-header">
                <div class="files-tabs">
                    <a href="?tab=user&folder=root" class="tab <?= $activeTab === 'user' ? 'active' : '' ?>">Utilisateur</a>
                    <a href="secure_pin_setup.php" class="tab">🔒 Espace sécurisé</a>                   </div>
                <div class="header-actions">
                    <button class="action-icon" title="Paramètres" id="settingsBtn" onclick="openSettingsModal()">⚙️</button>
                </div>
            </div>

            <!-- Affichage selon l'onglet -->
            <?php if ($activeTab === 'user'): ?>
                <!-- ========== ONGLET UTILISATEUR ========== -->
                
                <!-- Barre de navigation (breadcrumb) -->
                <div class="breadcrumb">
                    <?php
                    // Construire le chemin hiérarchique
                    $breadcrumbItems = [];
                    $currentId = $currentFolder;
                    
                    if ($currentFolder !== 'root') {
                        // Remonter la hiérarchie des dossiers
                        $tempId = $currentFolder;
                        while ($tempId !== null && $tempId !== 'root') {
                            $stmt = $pdo->prepare("SELECT id, name, parent_id FROM folders WHERE id = ? AND user_id = ?");
                            $stmt->execute([$tempId, $userId]);
                            $folder = $stmt->fetch();
                            
                            if ($folder) {
                                array_unshift($breadcrumbItems, [
                                    'id' => $folder['id'],
                                    'name' => $folder['name']
                                ]);
                                $tempId = $folder['parent_id'];
                            } else {
                                break;
                            }
                        }
                    }
                    ?>
                    
                    <a href="?tab=user&folder=root" class="breadcrumb-item <?= $currentFolder === 'root' ? 'active' : '' ?>">
                        📁 Racine
                    </a>
                    
                    <?php foreach ($breadcrumbItems as $index => $item): ?>
                        <span class="breadcrumb-separator">/</span>
                        <?php if ($index === count($breadcrumbItems) - 1 && $currentFolder !== 'root'): ?>
                            <span class="breadcrumb-item active"><?= htmlspecialchars($item['name']) ?></span>
                        <?php else: ?>
                            <a href="?tab=user&folder=<?= $item['id'] ?>" class="breadcrumb-item">
                                <?= htmlspecialchars($item['name']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Barre d'actions -->
                <div class="actions-bar">
                    <div class="actions-left">
                        <!-- Bouton Nouveau Dossier -->
                        <button type="button" class="action-btn" id="newFolderBtn">➕<br> Nouveau dossier</button>
                        
                        <!-- Formulaire Import -->
                        <form method="POST" action="upload.php" enctype="multipart/form-data" style="display: inline;" id="uploadForm">
                            <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                            <input type="file" name="files" style="display: none;" id="fileInput" onchange="handleFileSelect()">
                            <button type="button" class="action-btn" onclick="document.getElementById('fileInput').click()">📥<br> Importer</button>
                        </form>
                        
                        <!-- Boutons Télécharger, Copier, couper et Coller -->
                        <button type="button" class="action-btn" id="downloadBtn" onclick="prepareDownload()">⬇️<br> Télécharger</button>
                        <button type="button" class="action-btn" id="copyUserBtn" onclick="prepareCopy()">📋<br> Copier</button>
                        <button type="button" class="action-btn" id="cutBtn" onclick="prepareCut()">✂️<br> Couper</button>
                        <button type="button" class="action-btn" id="pasteUserBtn" onclick="preparePaste()">📌<br> Coller</button>
                        
                        <!-- Formulaire Suppression -->
                        <form method="POST" action="delete.php" style="display: inline;" id="deleteForm" onsubmit="return prepareDelete()">
                            <input type="hidden" name="items" id="deleteItems" value="">
                            <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                            <button type="submit" class="action-btn">🗑️<br> Supprimer</button>
                        </form>
                        
                        <!-- Formulaire Renommer -->
                        <form method="POST" action="rename.php" style="display: inline;" id="renameForm">
                            <input type="hidden" name="id" id="renameId" value="">
                            <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                            <input type="text" name="name" id="renameName" placeholder="Nouveau nom" class="inline-input" style="display: none;">
                            <button type="button" class="action-btn" onclick="prepareRename()">✏️<br> Renommer</button>
                        </form>
                        <!-- Barre de recherche -->
                        <div class="search-container">
                            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Rechercher..." autocomplete="off">
                            <button id="clearSearchBtn" class="search-clear" style="display: none;">x</button>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des fichiers -->
                <!-- Liste des fichiers -->
                <div class="files-container">
                    <!-- Vue Détails -->
                    <div class="files-list details-view" style="display: block;">
                        <div class="list-header">
                            <div class="col-checkbox"><input type="checkbox" id="selectAllUser" onclick="toggleAll(this)"></div>
                            <div class="col-name">Nom</div>
                            <div class="col-date">Date</div>
                            <div class="col-type">Type</div>
                            <div class="col-size">Taille</div>
                            <div class="col-actions">Actions</div>
                        </div>
                        
                        <?php if (empty($userFolders) && empty($userFiles)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📂</div>
                                <p class="empty-title">Ce dossier est encore vide</p>
                                <p class="empty-subtitle">Commencez par importer des fichiers ou créer un dossier !</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Dossiers -->
                        <?php foreach ($userFolders as $folder): ?>
                            <div class="file-row folder-row" data-id="<?= $folder['id'] ?>" data-type="folder" ondblclick="window.location.href='?tab=user&folder=<?= $folder['id'] ?>'">
                                <div class="col-checkbox">
                                    <input type="checkbox" class="file-checkbox" value="folder_<?= $folder['id'] ?>">
                                </div>
                                <div class="col-name">
                                    <span class="file-icon">📁</span>
                                    <a href="?tab=user&folder=<?= $folder['id'] ?>" class="folder-link"><?= htmlspecialchars($folder['name']) ?></a>
                                </div>
                                <div class="col-date"><?= formatDate($folder['created_at']) ?></div>
                                <div class="col-type">Dossier</div>
                                <div class="col-size">-</div>
                                <div class="col-actions">
                                    <button class="action-delete" onclick="deleteSingleItem('folder_<?= $folder['id'] ?>')" title="Supprimer">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Fichiers -->
                        <?php foreach ($userFiles as $file): 
                            $fileCategory = getFileTypeCategory($file['original_name']);
                        ?>
                            <div class="file-row" data-id="<?= $file['id'] ?>" data-type="file" ondblclick="openFile(<?= $file['id'] ?>, '<?= $fileCategory ?>')">
                                <div class="col-checkbox">
                                    <input type="checkbox" class="file-checkbox" value="file_<?= $file['id'] ?>">
                                </div>
                                <div class="col-name">
                                    <span class="file-icon"><?= getFileIcon($file['original_name'], 'file') ?></span>
                                    <span class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>"><?= htmlspecialchars($file['original_name']) ?></span>
                                </div>
                                <div class="col-date"><?= formatDate($file['created_at']) ?></div>
                                <div class="col-type"><?= htmlspecialchars($file['type']) ?></div>
                                <div class="col-size"><?= formatFileSize($file['size']) ?></div>
                                <div class="col-actions">
                                    <?php if ($fileCategory === 'text' || $fileCategory === 'image' || $fileCategory === 'audio' || $fileCategory === 'video'): ?>
                                        <a href="view_file.php?id=<?= $file['id'] ?>" class="action-view" target="_blank" title="Voir">👁️</a>
                                    <?php endif; ?>
                                    <a href="download.php?id=<?= $file['id'] ?>" class="action-download" title="Télécharger">⬇️</a>
                                    <button class="action-delete" onclick="deleteSingleItem('file_<?= $file['id'] ?>')" title="Supprimer">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Vue Liste -->
                    <div class="list-view" style="display: none;">
                        <div class="simple-list">
                            <?php foreach ($userFolders as $folder): ?>
                                <div class="list-item" data-id="<?= $folder['id'] ?>" data-type="folder" ondblclick="window.location.href='?tab=user&folder=<?= $folder['id'] ?>'">
                                    <span class="list-icon">📁</span>
                                    <span class="list-name"><?= htmlspecialchars($folder['name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($userFiles as $file): ?>
                                <div class="list-item" data-id="<?= $file['id'] ?>" data-type="file" ondblclick="openFile(<?= $file['id'] ?>, '<?= getFileTypeCategory($file['original_name']) ?>')">
                                    <span class="list-icon"><?= getFileIcon($file['original_name'], 'file') ?></span>
                                    <span class="list-name"><?= htmlspecialchars($file['original_name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($userFolders) && empty($userFiles)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📂</div>
                                    <p class="empty-title">Ce dossier est encore vide</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Vue Icônes -->
                    <div class="icons-view" style="display: none;">
                        <div class="icons-grid">
                            <?php foreach ($userFolders as $folder): ?>
                                <div class="icon-item" data-id="<?= $folder['id'] ?>" data-type="folder" ondblclick="window.location.href='?tab=user&folder=<?= $folder['id'] ?>'">
                                    <div class="icon-large">📁</div>
                                    <div class="icon-name"><?= htmlspecialchars($folder['name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($userFiles as $file): ?>
                                <div class="icon-item" data-id="<?= $file['id'] ?>" data-type="file" ondblclick="openFile(<?= $file['id'] ?>, '<?= getFileTypeCategory($file['original_name']) ?>')">
                                    <div class="icon-large"><?= getFileIcon($file['original_name'], 'file') ?></div>
                                    <div class="icon-name"><?= htmlspecialchars($file['original_name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($userFolders) && empty($userFiles)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📂</div>
                                    <p class="empty-title">Ce dossier est encore vide</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Formulaire caché pour suppression individuelle -->
                <form method="POST" id="singleDeleteForm" action="delete.php" style="display: none;">
                    <input type="hidden" name="items" id="singleDeleteItems">
                    <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                </form>
                
                <!-- Barre du bas avec compteurs -->
                <div class="files-footer">
                    <div class="footer-left">
                        <span class="total-count"><?= $totalItems ?> élément(s)</span>
                        <span class="selected-count" id="selectedCount">0 élément sélectionné</span>
                    </div>
                    <div class="footer-right">
                        <div class="view-mode-footer">
                            <button class="view-mode-btn <?= $viewMode === 'details' ? 'active' : '' ?>" data-view="details">📋 Détails</button>
                            <button class="view-mode-btn <?= $viewMode === 'list' ? 'active' : '' ?>" data-view="list">📝 Liste</button>
                            <button class="view-mode-btn <?= $viewMode === 'icons' ? 'active' : '' ?>" data-view="icons">🖼️ Icônes</button>
                        </div>
                    </div>
                </div>
                
                <script>
                    // Variables
                    let currentFolder = '<?= $currentFolder ?>';
                    let lastSelectedIndex = -1;

                    // Charger le presse-papiers depuis localStorage au chargement
                    let clipboard = JSON.parse(localStorage.getItem('lundrive_clipboard') || '[]');

                    // Échapper les caractères HTML
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    // Notifications
                    function showNotification(message, type = 'success', duration = 3000) {
                        const container = document.getElementById('notificationContainer');
                        if (!container) return;
                        
                        const notification = document.createElement('div');
                        notification.className = `notification notification-${type}`;
                        let icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
                        
                        notification.innerHTML = `
                            <div class="notification-icon">${icon}</div>
                            <div class="notification-content">${escapeHtml(message)}</div>
                            <button class="notification-close" onclick="this.parentElement.remove()">✕</button>
                        `;
                        
                        container.appendChild(notification);
                        setTimeout(() => {
                            if (notification.parentElement) {
                                notification.classList.add('notification-exit');
                                setTimeout(() => notification.remove(), 300);
                            }
                        }, duration);
                    }

                    // ========== CRÉATION DE DOSSIER ==========
                    function createNewFolder() {
                        const modal = document.getElementById('createFolderModal');
                        const input = document.getElementById('folderNameInput');
                        const cancelBtn = document.getElementById('createFolderCancel');
                        const okBtn = document.getElementById('createFolderOk');
                        
                        // Pré-remplir avec une valeur par défaut
                        input.value = 'Nouveau dossier';
                        input.focus();
                        input.select();
                        
                        // Afficher la modale
                        modal.style.display = 'flex';
                        
                        // Gérer la fermeture
                        function closeModal() {
                            modal.style.display = 'none';
                            input.value = '';
                        }
                        
                        // Événements
                        cancelBtn.onclick = closeModal;
                        modal.onclick = function(e) {
                            if (e.target === modal) closeModal();
                        };
                        
                        okBtn.onclick = function() {
                            const folderName = input.value.trim();
                            if (!folderName) {
                                showNotification('Le nom du dossier ne peut pas être vide', 'warning', 2000);
                                input.focus();
                                return;
                            }
                            
                            if (/[\/\\\:\*\?\"\<\>\|]/.test(folderName)) {
                                showNotification('Le nom contient des caractères interdits', 'error', 3000);
                                input.focus();
                                return;
                            }
                            
                            closeModal();
                            
                            fetch('create_folder.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'name=' + encodeURIComponent(folderName) + '&folder=' + encodeURIComponent(currentFolder)
                            })
                            .then(response => {
                                if (response.ok) {
                                    showNotification(`Dossier "${folderName}" créé avec succès`, 'success', 3000);
                                    setTimeout(() => location.reload(), 500);
                                } else {
                                    showNotification('Erreur lors de la création', 'error', 3000);
                                }
                            })
                            .catch(error => showNotification('Erreur réseau', 'error', 3000));
                        };
                        
                        // Gérer Enter/Escape
                        input.onkeydown = function(e) {
                            if (e.key === 'Enter') okBtn.click();
                            if (e.key === 'Escape') closeModal();
                        };
                    }

                    // ========== UTILITAIRES ==========
                    
                    // Fonction utilitaire pour afficher une modale de confirmation
                    function showConfirmModal(title, message, confirmText = 'Confirmer', confirmClass = 'btn-danger') {
                        return new Promise((resolve) => {
                            const modal = document.getElementById('confirmModal');
                            const modalTitle = document.getElementById('confirmTitle');
                            const modalMessage = document.getElementById('confirmMessage');
                            const cancelBtn = document.getElementById('confirmCancel');
                            const okBtn = document.getElementById('confirmOk');
                            
                            modalTitle.textContent = title;
                            modalMessage.textContent = message;
                            okBtn.textContent = confirmText;
                            okBtn.className = `btn ${confirmClass}`;
                            
                            modal.style.display = 'flex';
                            
                            function closeModal(result = false) {
                                modal.style.display = 'none';
                                resolve(result);
                            }
                            
                            cancelBtn.onclick = () => closeModal(false);
                            modal.onclick = function(e) {
                                if (e.target === modal) closeModal(false);
                            };
                            
                            okBtn.onclick = () => closeModal(true);
                            
                            // Gérer Escape
                            const handleEscape = function(e) {
                                if (e.key === 'Escape') {
                                    closeModal(false);
                                    document.removeEventListener('keydown', handleEscape);
                                }
                            };
                            document.addEventListener('keydown', handleEscape);
                        });
                    }

                    // ========== GESTION DE L'UPLOAD ==========
                    function handleFileSelect() {
                        const fileInput = document.getElementById('fileInput');
                        const uploadForm = document.getElementById('uploadForm');
                        
                        if (fileInput && fileInput.files.length > 0) {
                            console.log('Fichier sélectionné:', fileInput.files[0].name, 'Taille:', fileInput.files[0].size);
                            uploadForm.submit();
                        } else {
                            console.log('Aucun fichier sélectionné');
                        }
                    }

                    // ========== SÉLECTION ==========
                    function toggleAll(source) {
                        const checkboxes = document.querySelectorAll('.file-checkbox');
                        checkboxes.forEach(cb => {
                            cb.checked = source.checked;
                            const row = cb.closest('.file-row');
                            if (row) {
                                if (source.checked) row.classList.add('selected');
                                else row.classList.remove('selected');
                            }
                        });
                        updateSelectedCount();
                    }

                    function updateSelectedCount() {
                        let count = 0;
                        count += document.querySelectorAll('.details-view .file-checkbox:checked').length;
                        count += document.querySelectorAll('.list-view .list-item.selected').length;
                        count += document.querySelectorAll('.icons-view .icon-item.selected').length;
                        
                        const selectedCountSpan = document.getElementById('selectedCount');
                        if (selectedCountSpan) selectedCountSpan.textContent = `${count}`;
                    }

                    function getAllSelectedItems() {
                        const selected = [];
                        document.querySelectorAll('.details-view .file-checkbox:checked').forEach(cb => selected.push(cb.value));
                        document.querySelectorAll('.list-view .list-item.selected').forEach(item => {
                            const id = item.dataset.id;
                            const type = item.dataset.type;
                            if (id && type) selected.push(`${type}_${id}`);
                        });
                        document.querySelectorAll('.icons-view .icon-item.selected').forEach(item => {
                            const id = item.dataset.id;
                            const type = item.dataset.type;
                            if (id && type) selected.push(`${type}_${id}`);
                        });
                        return selected;
                    }

                    function getSelectedItems() {
                        return getAllSelectedItems();
                    }

                    // ========== ACTIONS ==========
                    function prepareDelete() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné', 'warning', 2000);
                            return false;
                        }
                        
                        return showConfirmModal(
                            '⚠️ Confirmation de suppression',
                            `Êtes-vous sûr de vouloir supprimer définitivement ${items.length} élément(s) ?\n\nCette action est irréversible.`,
                            'Supprimer',
                            'btn-danger'
                        ).then(confirmed => {
                            if (confirmed) {
                                document.getElementById('deleteItems').value = items.join(',');
                                showNotification(`Suppression en cours...`, 'info', 1500);
                                document.getElementById('deleteForm').submit();
                            }
                            return false;
                        });
                    }

                    function deleteSingleItem(itemId) {
                        showConfirmModal(
                            '⚠️ Confirmation de suppression',
                            'Êtes-vous sûr de vouloir supprimer cet élément ?\n\nCette action est irréversible.',
                            'Supprimer',
                            'btn-danger'
                        ).then(confirmed => {
                            if (confirmed) {
                                document.getElementById('singleDeleteItems').value = itemId;
                                document.getElementById('singleDeleteForm').submit();
                            }
                        });
                    }

                    function prepareRename() {
                        const items = getSelectedItems();
                        if (items.length !== 1) {
                            showNotification('Sélectionnez un seul élément à renommer', 'warning', 2500);
                            return;
                        }
                        
                        // Utiliser la modale de renommage
                        const modal = document.getElementById('renameModal');
                        const input = document.getElementById('renameInput');
                        const cancelBtn = document.getElementById('renameCancel');
                        const okBtn = document.getElementById('renameOk');
                        
                        // Essayer de récupérer le nom actuel
                        let currentName = '';
                        const selectedItem = items[0];
                        
                        if (selectedItem.startsWith('file_')) {
                            const fileId = selectedItem.replace('file_', '');
                            // Chercher le nom dans le DOM
                            const fileRow = document.querySelector(`.file-row[data-id="${fileId}"]`);
                            if (fileRow) {
                                const nameElement = fileRow.querySelector('.file-name');
                                if (nameElement) currentName = nameElement.textContent;
                            }
                        } else if (selectedItem.startsWith('folder_')) {
                            const folderId = selectedItem.replace('folder_', '');
                            // Chercher le nom dans le DOM
                            const folderRow = document.querySelector(`.file-row[data-id="${folderId}"]`);
                            if (folderRow) {
                                const nameElement = folderRow.querySelector('.folder-link');
                                if (nameElement) currentName = nameElement.textContent;
                            }
                        }
                        
                        input.value = currentName;
                        input.focus();
                        input.select();
                        
                        modal.style.display = 'flex';
                        
                        function closeModal(result = false) {
                            modal.style.display = 'none';
                            input.value = '';
                            if (result && input.value.trim()) {
                                document.getElementById('renameId').value = items[0];
                                document.getElementById('renameName').value = input.value.trim();
                                document.getElementById('renameForm').submit();
                            }
                        }
                        
                        cancelBtn.onclick = () => closeModal(false);
                        modal.onclick = function(e) {
                            if (e.target === modal) closeModal(false);
                        };
                        
                        okBtn.onclick = () => closeModal(true);
                        
                        // Gérer Enter/Escape
                        input.onkeydown = function(e) {
                            if (e.key === 'Enter') closeModal(true);
                            if (e.key === 'Escape') closeModal(false);
                        };
                    }

                    function prepareDownload() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné', 'warning', 2000);
                            return false;
                        }
                        const files = items.filter(item => item.startsWith('file_'));
                        const folders = items.filter(item => item.startsWith('folder_'));
                        
                        if (files.length === 0 && folders.length === 0) {
                            showNotification('Aucun élément valide', 'warning', 2000);
                            return false;
                        }
                        
                        if (files.length === 1 && folders.length === 0) {
                            window.location.href = 'download.php?id=' + files[0].replace('file_', '');
                            return true;
                        }
                        
                        showNotification('Préparation du téléchargement...', 'info', 1500);
                        fetch('download_archive.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ items: items })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success', 2000);
                                window.location.href = 'download_archive.php?file=' + data.archive;
                            } else {
                                showNotification(data.message, 'error', 4000);
                            }
                        })
                        .catch(error => showNotification('Erreur', 'error', 4000));
                        return true;
                    }

                    function prepareCopy() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné', 'warning', 2000);
                            return false;
                        }
                        clipboard = [...items];
                        saveClipboard();
                        showNotification(`${clipboard.length} élément(s) copié(s)`, 'success', 2500);
                        return true;
                    }

                    let cutItems = [];
                    function prepareCut() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné', 'warning', 2000);
                            return false;
                        }
                        cutItems = [...items];
                        localStorage.setItem('lundrive_cut', JSON.stringify(cutItems));
                        showNotification(`${cutItems.length} élément(s) coupé(s). Utilisez Coller.`, 'info', 3000);
                        return true;
                    }

                    function preparePaste() {
                        let cutClipboard = JSON.parse(localStorage.getItem('lundrive_cut') || '[]');
                        let copyClipboard = JSON.parse(localStorage.getItem('lundrive_clipboard') || '[]');
                        let itemsToPaste = [];
                        let isCut = false;
                        
                        if (cutClipboard.length > 0) {
                            itemsToPaste = cutClipboard;
                            isCut = true;
                        } else if (copyClipboard.length > 0) {
                            itemsToPaste = copyClipboard;
                            isCut = false;
                        }
                        
                        if (itemsToPaste.length === 0) {
                            showNotification('Rien à coller', 'warning', 2500);
                            return false;
                        }
                        
                        showNotification(`Collage en cours...`, 'info', 1500);
                        fetch('paste.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ items: itemsToPaste, target_folder: currentFolder, is_cut: isCut })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success', 3000);
                                if (isCut) {
                                    localStorage.removeItem('lundrive_cut');
                                    cutItems = [];
                                } else {
                                    localStorage.removeItem('lundrive_clipboard');
                                    clipboard = [];
                                }
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showNotification(data.message, 'error', 4000);
                            }
                        })
                        .catch(error => showNotification('Erreur', 'error', 4000));
                        return false;
                    }

                    function openFile(fileId, category) {
                        if (['text', 'image', 'audio', 'video'].includes(category)) {
                            window.open('view_file.php?id=' + fileId, '_blank');
                        } else {
                            showConfirmModal('📥 Téléchargement', 'Télécharger ce fichier ?', 'Télécharger', 'btn-primary')
                                .then(confirmed => {
                                    if (confirmed) {
                                        window.location.href = 'download.php?id=' + fileId;
                                    }
                                });
                        }
                    }

                    // ========== MODE D'AFFICHAGE ==========
                    function applyViewMode(viewMode) {
                        const detailsView = document.querySelector('.details-view');
                        const listView = document.querySelector('.list-view');
                        const iconsView = document.querySelector('.icons-view');
                        
                        if (detailsView) detailsView.style.display = 'none';
                        if (listView) listView.style.display = 'none';
                        if (iconsView) iconsView.style.display = 'none';
                        
                        if (viewMode === 'details' && detailsView) detailsView.style.display = 'block';
                        else if (viewMode === 'list' && listView) listView.style.display = 'block';
                        else if (viewMode === 'icons' && iconsView) iconsView.style.display = 'block';
                        else if (detailsView) detailsView.style.display = 'block';
                        
                        document.querySelectorAll('.view-mode-btn').forEach(btn => {
                            if (btn.dataset.view === viewMode) btn.classList.add('active');
                            else btn.classList.remove('active');
                        });
                        
                        // Re-initialiser les sélections après changement de vue
                        initListSelection();
                        initIconsSelection();
                        updateSelectedCount();
                    }

                    // ========== BARRE DE RECHERCHE ==========
                    const searchInput = document.getElementById('searchInput');
                    const clearSearchBtn = document.getElementById('clearSearchBtn');

                    function getCurrentDisplayedItems() {
                        const detailsView = document.querySelector('.details-view');
                        const listView = document.querySelector('.list-view');
                        const iconsView = document.querySelector('.icons-view');
                        
                        if (detailsView && detailsView.style.display !== 'none') return detailsView.querySelectorAll('.file-row');
                        if (listView && listView.style.display !== 'none') return listView.querySelectorAll('.list-item');
                        if (iconsView && iconsView.style.display !== 'none') return iconsView.querySelectorAll('.icon-item');
                        return [];
                    }

                    function setItemVisibility(item, mode, visible) {
                        if (mode === 'details') item.style.display = visible ? '' : 'none';
                        else if (mode === 'list') item.style.display = visible ? 'flex' : 'none';
                        else if (mode === 'icons') item.style.display = visible ? 'flex' : 'none';
                    }

                    function getItemName(item, mode) {
                        if (mode === 'details') return item.querySelector('.file-name, .folder-link')?.textContent.toLowerCase() || '';
                        if (mode === 'list') return item.querySelector('.list-name')?.textContent.toLowerCase() || '';
                        if (mode === 'icons') return item.querySelector('.icon-name')?.textContent.toLowerCase() || '';
                        return '';
                    }

                    function filterFilesAndFolders() {
                        if (!searchInput) return;
                        const searchTerm = searchInput.value.toLowerCase().trim();
                        let currentMode = 'details';
                        const listView = document.querySelector('.list-view');
                        const iconsView = document.querySelector('.icons-view');
                        if (listView && listView.style.display !== 'none') currentMode = 'list';
                        else if (iconsView && iconsView.style.display !== 'none') currentMode = 'icons';
                        
                        const items = getCurrentDisplayedItems();
                        let visibleCount = 0;
                        
                        if (searchTerm === '') {
                            items.forEach(item => setItemVisibility(item, currentMode, true));
                            if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                            const totalCountSpan = document.querySelector('.total-count');
                            if (totalCountSpan) totalCountSpan.innerHTML = `${items.length} élément(s)`;
                            document.querySelector('.no-results')?.remove();
                            return;
                        }
                        
                        if (clearSearchBtn) clearSearchBtn.style.display = 'block';
                        items.forEach(item => {
                            if (getItemName(item, currentMode).includes(searchTerm)) {
                                setItemVisibility(item, currentMode, true);
                                visibleCount++;
                            } else {
                                setItemVisibility(item, currentMode, false);
                            }
                        });
                        
                        const totalCountSpan = document.querySelector('.total-count');
                        if (totalCountSpan) totalCountSpan.innerHTML = visibleCount > 0 ? `${visibleCount} / ${items.length} élément(s)` : `0 élément(s)`;
                        
                        if (visibleCount === 0 && !document.querySelector('.no-results')) {
                            const container = document.querySelector('.files-container');
                            if (container) {
                                const noResultsDiv = document.createElement('div');
                                noResultsDiv.className = 'no-results';
                                noResultsDiv.innerHTML = `<div style="text-align:center;padding:3rem;"><div style="font-size:3rem;">🔍</div><p>Aucun résultat trouvé pour "${escapeHtml(searchTerm)}"</p></div>`;
                                container.appendChild(noResultsDiv);
                            }
                        } else if (visibleCount > 0) {
                            document.querySelector('.no-results')?.remove();
                        }
                    }

                    function resetSearch() {
                        if (searchInput) {
                            searchInput.value = '';
                            if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                            filterFilesAndFolders();
                        }
                    }

                    // ========== SÉLECTION PAR CLIC ==========
                    function initSelection() {
                        document.querySelectorAll('.file-row').forEach(row => {
                            row.removeEventListener('click', row._clickHandler);
                            row._clickHandler = function(e) {
                                if (e.target.closest('.folder-link') || e.target.closest('.col-actions') || e.target.type === 'checkbox') return;
                                const checkbox = row.querySelector('.file-checkbox');
                                if (checkbox) {
                                    checkbox.checked = !checkbox.checked;
                                    if (checkbox.checked) row.classList.add('selected');
                                    else row.classList.remove('selected');
                                    updateSelectedCount();
                                }
                            };
                            row.addEventListener('click', row._clickHandler);
                            row.style.cursor = 'pointer';
                        });
                    }

                    function initListSelection() {
                        document.querySelectorAll('.list-view .list-item').forEach(item => {
                            item.removeEventListener('click', item._clickHandler);
                            item._clickHandler = function(e) {
                                if (e.target.closest('.col-actions')) return;
                                this.classList.toggle('selected');
                                updateSelectedCount();
                            };
                            item.addEventListener('click', item._clickHandler);
                            item.style.cursor = 'pointer';
                        });
                    }

                    function initIconsSelection() {
                        document.querySelectorAll('.icons-view .icon-item').forEach(item => {
                            item.removeEventListener('click', item._clickHandler);
                            item._clickHandler = function() {
                                this.classList.toggle('selected');
                                updateSelectedCount();
                            };
                            item.addEventListener('click', item._clickHandler);
                            item.style.cursor = 'pointer';
                        });
                    }

                    function initAllSelections() {
                        initListSelection();
                        initIconsSelection();
                        const observer = new MutationObserver(() => { initListSelection(); initIconsSelection(); });
                        document.querySelectorAll('.list-view, .icons-view').forEach(container => {
                            observer.observe(container, { attributes: true, attributeFilter: ['style'] });
                        });
                    }

                    // ========== INITIALISATION ==========
                    document.querySelectorAll('.view-mode-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const view = this.dataset.view;
                            if (view) {
                                localStorage.setItem('lun_drive_default_view', view);
                                applyViewMode(view);
                                showNotification(`Mode : ${view === 'details' ? 'Détails' : view === 'list' ? 'Liste' : 'Icônes'}`, 'info', 1500);
                            }
                        });
                    });

                    if (searchInput) {
                        let timeout;
                        searchInput.addEventListener('input', () => {
                            clearTimeout(timeout);
                            timeout = setTimeout(filterFilesAndFolders, 300);
                        });
                    }
                    if (clearSearchBtn) clearSearchBtn.addEventListener('click', resetSearch);

                    document.getElementById('newFolderBtn')?.addEventListener('click', createNewFolder);

                    function init() {
                        document.querySelectorAll('.file-checkbox').forEach(cb => {
                            cb.removeEventListener('change', cb._changeHandler);
                            cb._changeHandler = function() {
                                const row = this.closest('.file-row');
                                if (row) {
                                    if (this.checked) row.classList.add('selected');
                                    else row.classList.remove('selected');
                                }
                                updateSelectedCount();
                            };
                            cb.addEventListener('change', cb._changeHandler);
                        });
                        initSelection();
                        initAllSelections();
                        updateSelectedCount();
                        applyViewMode(localStorage.getItem('lun_drive_default_view') || 'details');
                    }

                    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
                    else init();

                    console.log('Presse-papiers initial:', clipboard);
                </script>
            <?php  endif; ?>
        </main>
    </div>

        <!-- Conteneur pour les notifications -->
    <div id="notificationContainer" class="notification-container"></div>

    <?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('<?= addslashes($successMsg) ?>', 'success', 5000);
        });
    </script>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('<?= addslashes($errorMsg) ?>', 'error', 5000);
        });
    </script>
    <?php endif; ?>

    <!-- ========== GESTION GLOBALE DU BOUTON PARAMÈTRES ========== -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const settingsBtn = document.getElementById('settingsBtn');
        if (settingsBtn) {
            // Supprimer les anciens écouteurs
            const newSettingsBtn = settingsBtn.cloneNode(true);
            settingsBtn.parentNode.replaceChild(newSettingsBtn, settingsBtn);
            
            newSettingsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Clic sur le bouton paramètres');
                if (typeof openSettingsModal === 'function') {
                    openSettingsModal();
                } else {
                    console.error('openSettingsModal non définie - vérifier que settings.js est chargé');
                    // Tentative de rechargement du script
                    const script = document.createElement('script');
                    script.src = 'assets/js/settings.js';
                    document.head.appendChild(script);
                    setTimeout(() => {
                        if (typeof openSettingsModal === 'function') {
                            openSettingsModal();
                        } else {
                            alert('Erreur: Impossible de charger les paramètres');
                        }
                    }, 500);
                }
            });
            console.log('Bouton paramètres initialisé');
        } else {
            console.error('Bouton settingsBtn non trouvé');
        }
    });
    </script>

    <!-- Modales personnalisées -->
    
    <!-- Modal de confirmation -->
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content confirm-modal">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirmation</h3>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Êtes-vous sûr ?</p>
            </div>
            <div class="modal-footer">
                <button id="confirmCancel" class="btn btn-secondary">Annuler</button>
                <button id="confirmOk" class="btn btn-danger">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- Modal de création de dossier -->
    <div id="createFolderModal" class="modal" style="display: none;">
        <div class="modal-content create-folder-modal">
            <div class="modal-header">
                <h3>📁 Créer un nouveau dossier</h3>
            </div>
            <div class="modal-body">
                <label for="folderNameInput">Nom du dossier :</label>
                <input type="text" id="folderNameInput" class="modal-input" placeholder="Entrez le nom du dossier" autocomplete="off">
                <div class="modal-help">Le nom ne peut pas contenir les caractères suivants : / \ : * ? " < > |</div>
            </div>
            <div class="modal-footer">
                <button id="createFolderCancel" class="btn btn-secondary">Annuler</button>
                <button id="createFolderOk" class="btn btn-primary">Créer</button>
            </div>
        </div>
    </div>

    <!-- Modal de renommage -->
    <div id="renameModal" class="modal" style="display: none;">
        <div class="modal-content rename-modal">
            <div class="modal-header">
                <h3>✏️ Renommer</h3>
            </div>
            <div class="modal-body">
                <label for="renameInput">Nouveau nom :</label>
                <input type="text" id="renameInput" class="modal-input" placeholder="Entrez le nouveau nom" autocomplete="off">
                <div class="modal-help" id="renameHelp">Le nom ne peut pas contenir les caractères suivants : / \ : * ? " < > |</div>
            </div>
            <div class="modal-footer">
                <button id="renameCancel" class="btn btn-secondary">Annuler</button>
                <button id="renameOk" class="btn btn-primary">Renommer</button>
            </div>
        </div>
    </div>

    <!-- Charger settings.js APRÈS les fonctions -->
    <script src="assets/js/settings.js"></script>
</body>
</html>