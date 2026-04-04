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
                    <a href="secure_files.php" class="tab">🔒 Espace sécurisé</a>
                </div>
                <div class="header-actions">
                    <button class="action-icon" title="Paramètres" id="settingsBtn" onclick="openSettingsModal()">⚙️</button>
                </div>
            </div>

            <!-- Affichage selon l'onglet -->
            <?php if ($activeTab === 'system'): ?>
                 <!-- ========== ONGLET SYSTÈME ========== -->
                <div class="files-tabs">
                    <a href="?tab=user&folder=root" class="tab <?= $activeTab === 'user' ? 'active' : '' ?>">Utilisateur</a>
                    <a href="secure_pin_setup.php" class="tab">🔒 Espace sécurisé</a>
                </div>
            <?php else: ?>
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
                        <!-- Formulaire Nouveau Dossier -->
                        <form method="POST" action="create_folder.php" style="display: inline;">
                            <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                            <input type="text" name="name" placeholder="Nom du dossier" class="inline-input" required>
                            <button type="submit" class="action-btn">➕<br> Nouveau dossier</button>
                        </form>
                        
                        <!-- Formulaire Import -->
                        <form method="POST" action="upload.php" enctype="multipart/form-data" style="display: inline;">
                            <input type="hidden" name="folder" value="<?= $currentFolder ?>">
                            <input type="file" name="files[]" multiple style="display: none;" id="fileInput" onchange="this.form.submit()">
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
                                    <span class="file-name"><?= htmlspecialchars($file['original_name']) ?></span>
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
                                <div class="list-item" ondblclick="window.location.href='?tab=user&folder=<?= $folder['id'] ?>'">
                                    <span class="list-icon">📁</span>
                                    <a href="?tab=user&folder=<?= $folder['id'] ?>" class="list-name"><?= htmlspecialchars($folder['name']) ?></a>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($userFiles as $file): ?>
                                <div class="list-item" ondblclick="openFile(<?= $file['id'] ?>, '<?= getFileTypeCategory($file['original_name']) ?>')">
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
                                <div class="icon-item" ondblclick="window.location.href='?tab=user&folder=<?= $folder['id'] ?>'">
                                    <div class="icon-large">📁</div>
                                    <div class="icon-name"><?= htmlspecialchars($folder['name']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($userFiles as $file): ?>
                                <div class="icon-item" ondblclick="openFile(<?= $file['id'] ?>, '<?= getFileTypeCategory($file['original_name']) ?>')">
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

                    // Échapper les caractères HTML pour éviter les injections
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    // Système de notifications
                    function showNotification(message, type = 'success', duration = 3000) {
                        console.log('showNotification appelée:', message, type);
                        
                        const container = document.getElementById('notificationContainer');
                        console.log('Container trouvé:', container);
                        
                        if (!container) {
                            console.error('Conteneur de notifications non trouvé !');
                            alert(message);
                            return;
                        }
                        
                        const notification = document.createElement('div');
                        notification.className = `notification notification-${type}`;
                        
                        let icon = '';
                        switch(type) {
                            case 'success': icon = '✅'; break;
                            case 'error': icon = '❌'; break;
                            case 'warning': icon = '⚠️'; break;
                            case 'info': icon = 'ℹ️'; break;
                            default: icon = '📋';
                        }
                        
                        notification.innerHTML = `
                            <div class="notification-icon">${icon}</div>
                            <div class="notification-content">${escapeHtml(message)}</div>
                            <button class="notification-close" onclick="this.parentElement.remove()">✕</button>
                        `;
                        
                        container.appendChild(notification);
                        console.log('Notification ajoutée au conteneur');
                        
                        setTimeout(() => {
                            if (notification.parentElement) {
                                notification.classList.add('notification-exit');
                                setTimeout(() => {
                                    if (notification.parentElement) {
                                        notification.remove();
                                    }
                                }, 300);
                            }
                        }, duration);
                    }

                    // Sauvegarder le presse-papiers dans localStorage
                    function saveClipboard() {
                        localStorage.setItem('lundrive_clipboard', JSON.stringify(clipboard));
                    }

                    // Sélection multiple
                    function toggleAll(source) {
                        const checkboxes = document.querySelectorAll('.file-checkbox');
                        checkboxes.forEach(cb => cb.checked = source.checked);
                        updateSelectedCount();
                    }

                    
                    function getSelectedItems() {
                        const selected = document.querySelectorAll('.file-checkbox:checked');
                        return Array.from(selected).map(cb => cb.value);
                    }

                    // Suppression groupée
                    function prepareDelete() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné', 'warning', 2000);
                            return false;
                        }
                        
                        if (confirm(`⚠️ Supprimer définitivement ${items.length} élément(s) ?\nCette action est irréversible.`)) {
                            document.getElementById('deleteItems').value = items.join(',');
                            showNotification(`Suppression de ${items.length} élément(s) en cours...`, 'info', 1500);
                            return true;
                        }
                        return false;
                    }

                    // Suppression individuelle
                    function deleteSingleItem(itemId) {
                        if (confirm('Supprimer cet élément ?')) {
                            const form = document.getElementById('singleDeleteForm');
                            document.getElementById('singleDeleteItems').value = itemId;
                            form.submit();
                        }
                    }

                    // Renommer
                    function prepareRename() {
                        const items = getSelectedItems();
                        if (items.length !== 1) {
                            showNotification('Veuillez sélectionner un seul élément à renommer', 'warning', 2500);
                            return;
                        }
                        
                        const newName = prompt('Entrez le nouveau nom :');
                        if (newName && newName.trim()) {
                            const form = document.getElementById('renameForm');
                            document.getElementById('renameId').value = items[0];
                            document.getElementById('renameName').value = newName;
                            form.submit();
                        }
                    }

                    // Télécharger les éléments sélectionnés
                    function prepareDownload() {
                        const items = getSelectedItems();
                        
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné à télécharger', 'warning', 2000);
                            return false;
                        }
                        
                        const files = items.filter(item => item.startsWith('file_'));
                        const folders = items.filter(item => item.startsWith('folder_'));
                        
                        if (files.length === 0 && folders.length === 0) {
                            showNotification('Aucun élément valide à télécharger', 'warning', 2000);
                            return false;
                        }
                        
                        showNotification(`Préparation du téléchargement de ${items.length} élément(s)...`, 'info', 1500);
                        
                        if (files.length === 1 && folders.length === 0) {
                            const fileId = files[0].replace('file_', '');
                            window.location.href = 'download.php?id=' + fileId;
                            showNotification('Téléchargement démarré', 'success', 2000);
                            return true;
                        }
                        
                        if (items.length > 1 || folders.length > 0) {
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
                                    setTimeout(() => {
                                        fetch('cleanup_archive.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({ file: data.archive })
                                        });
                                    }, 5000);
                                } else {
                                    showNotification(data.message, 'error', 4000);
                                }
                            })
                            .catch(error => {
                                console.error('Erreur:', error);
                                showNotification('Erreur lors de la préparation du téléchargement', 'error', 4000);
                            });
                        }
                        return true;
                    }

                    // Copier les éléments sélectionnés
                    function prepareCopy() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné à copier', 'warning', 2000);
                            return false;
                        }
                        clipboard = [...items];
                        saveClipboard();
                        showNotification(`${clipboard.length} élément(s) copié(s) dans le presse-papiers`, 'success', 2500);
                        return true;
                    }

                    // Couper les éléments sélectionnés
                    let cutItems = [];

                    function prepareCut() {
                        const items = getSelectedItems();
                        if (items.length === 0) {
                            showNotification('Aucun élément sélectionné à couper', 'warning', 2000);
                            return false;
                        }
                        
                        cutItems = [...items];
                        localStorage.setItem('lundrive_cut', JSON.stringify(cutItems));
                        
                        document.querySelectorAll('.file-checkbox:checked').forEach(cb => {
                            const row = cb.closest('.file-row');
                            if (row) {
                                row.style.opacity = '0.5';
                                row.style.backgroundColor = 'rgba(245, 158, 11, 0.2)';
                            }
                        });
                        
                        showNotification(`${cutItems.length} élément(s) coupé(s). Utilisez Coller pour déplacer.`, 'info', 3000);
                        return true;
                    }

                    // Coller les éléments
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
                            showNotification('Rien à coller. Copiez ou coupez d\'abord des éléments.', 'warning', 2500);
                            return false;
                        }
                        
                        const action = isCut ? 'déplacer' : 'copier';
                        showNotification(`Collage de ${itemsToPaste.length} élément(s) en cours (${action})...`, 'info', 1500);
                        
                        fetch('paste.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                items: itemsToPaste,
                                target_folder: currentFolder,
                                is_cut: isCut
                            })
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
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification('Erreur lors du collage: ' + error, 'error', 4000);
                        });
                        return false;
                    }

                    // Ouvrir un fichier par double-clic
                    function openFile(fileId, category) {
                        if (category === 'text' || category === 'image' || category === 'audio' || category === 'video') {
                            window.open('view_file.php?id=' + fileId, '_blank');
                        } else {
                            if (confirm('Ce type de fichier ne peut pas être visualisé directement.\nVoulez-vous le télécharger ?')) {
                                window.location.href = 'download.php?id=' + fileId;
                            }
                        }
                    }

                    // Gestion du changement de mode d'affichage depuis le footer
                    document.querySelectorAll('.view-mode-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const view = this.dataset.view;
                            if (view) {
                                // Sauvegarder le mode dans localStorage
                                localStorage.setItem('lun_drive_default_view', view);
                                // Appliquer le mode
                                applyViewMode(view);
                                // Afficher une notification
                                showNotification(`Mode d'affichage : ${view === 'details' ? 'Détails' : view === 'list' ? 'Liste' : 'Icônes'}`, 'info', 1500);
                            }
                        });
                    });

                    // ========== GESTION DE LA SÉLECTION ==========

                    // Mettre à jour le compteur et les classes selected
                    function updateSelectedCount() {
                        const selected = document.querySelectorAll('.file-checkbox:checked');
                        const count = selected.length;
                        const selectedCountSpan = document.getElementById('selectedCount');
                        if (selectedCountSpan) {
                            selectedCountSpan.textContent = `${count}`;
                        }
                        
                        // Mettre à jour la classe selected sur les lignes
                        document.querySelectorAll('.file-row').forEach(row => {
                            const checkbox = row.querySelector('.file-checkbox');
                            if (checkbox && checkbox.checked) {
                                row.classList.add('selected');
                            } else {
                                row.classList.remove('selected');
                            }
                        });
                    }

                    // Sélection par clic sur la ligne (uniquement la zone de la checkbox ou l'espace vide)
                    function initSelection() {
                        const rows = document.querySelectorAll('.file-row');
                        
                        rows.forEach(row => {
                            // Supprimer l'ancien écouteur pour éviter les doublons
                            row.removeEventListener('click', row._clickHandler);
                            
                            // Créer le nouveau gestionnaire
                            row._clickHandler = function(e) {
                                // IMPORTANT: Si on clique sur le lien du dossier, on laisse la navigation se faire
                                if (e.target.closest('.folder-link')) {
                                    return; // Ne pas sélectionner, laisser le lien fonctionner
                                }
                                
                                // Si on clique sur un bouton d'action, ne pas sélectionner
                                if (e.target.closest('.col-actions')) {
                                    return;
                                }
                                
                                // Si on clique directement sur la checkbox, laisser son comportement normal
                                if (e.target.type === 'checkbox') {
                                    return;
                                }
                                
                                // Sinon, on sélectionne/désélectionne
                                const checkbox = row.querySelector('.file-checkbox');
                                if (checkbox) {
                                    checkbox.checked = !checkbox.checked;
                                    updateSelectedCount();
                                }
                            };
                            
                            row.addEventListener('click', row._clickHandler);
                            
                            // Effet de survol
                            row.style.cursor = 'pointer';
                            row.addEventListener('mouseenter', function() {
                                this.style.backgroundColor = 'rgba(255,255,255,0.08)';
                            });
                            row.addEventListener('mouseleave', function() {
                                if (!this.classList.contains('selected')) {
                                    this.style.backgroundColor = '';
                                }
                            });
                        });
                    }

                    // Initialiser les checkboxes
                    function initCheckboxes() {
                        document.querySelectorAll('.file-checkbox').forEach(cb => {
                            cb.removeEventListener('change', updateSelectedCount);
                            cb.addEventListener('change', updateSelectedCount);
                        });
                    }

                    // Initialisation complète
                    function init() {
                        initCheckboxes();
                        initSelection();
                        updateSelectedCount();
                    }

                    // Démarrer l'initialisation
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', init);
                    } else {
                        init();
                    }

                    // Bouton paramètres
                    const settingsBtn = document.getElementById('settingsBtn');
                    if (settingsBtn) {
                        settingsBtn.addEventListener('click', function() {
                            if (typeof openSettingsModal === 'function') {
                                openSettingsModal();
                            } else {
                                console.error('openSettingsModal non défini - vérifier que settings.js est chargé');
                            }
                        });
                    }

                    // Initialiser le mode d'affichage au chargement
                    function initViewMode() {
                        const savedView = localStorage.getItem('lun_drive_default_view') || 'details';
                        applyViewMode(savedView);
                    }

                    // Appeler après le chargement de la page
                    document.addEventListener('DOMContentLoaded', function() {
                        initViewMode();
                    });

                    // Debug
                    console.log('Presse-papiers initial:', clipboard);
                </script>
            <?php endif; ?>
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

    <!-- Charger settings.js APRÈS les fonctions -->
    <script src="assets/js/settings.js"></script>
</body>
</html>