<?php
// secure_files.php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'config/upload.php';  // ← AJOUTER CETTE LIGNE

requireLogin();

// Vérifier l'accès à l'espace sécurisé
if (!isset($_SESSION['secure_access']) || $_SESSION['secure_access'] !== true) {
    header('Location: secure_pin.php');
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$action = $_GET['action'] ?? '';

$database = new Database();
$pdo = $database->getConnection();

// Upload de fichier
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        header("Location: secure_files.php?error=Aucun fichier sélectionné");
        exit();
    }
    
    $originalName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $tmpName = $_FILES['file']['tmp_name'];
    $fileError = $_FILES['file']['error'];
    
    // Validation centralisée
    $validation = validateUploadedFile($originalName, $fileSize, $fileError, $allowedExtensions, $maxFileSize);
    
    if (!$validation['valid']) {
        header("Location: secure_files.php?error=" . urlencode($validation['message']));
        exit();
    }
    
    $extension = $validation['extension'];
    
    $secureDir = 'uploads/secure/user_' . $userId . '/';
    if (!file_exists($secureDir)) {
        mkdir($secureDir, 0777, true);
    }
    
    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $secureDir . $uniqueName;
    
    if (move_uploaded_file($tmpName, $filePath)) {
        $stmt = $pdo->prepare("
            INSERT INTO secure_files (user_id, name, original_name, path, type, size)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $uniqueName, $originalName, $filePath, $fileType, $fileSize]);
        header("Location: secure_files.php?success=Fichier importé avec succès");
    } else {
        header("Location: secure_files.php?error=Erreur lors du déplacement du fichier");
    }
    exit();
}

// ... reste du code inchangé ...

// Suppression de fichier
if ($action === 'delete' && isset($_GET['id'])) {
    $fileId = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT path FROM secure_files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $userId]);
    $file = $stmt->fetch();
    
    if ($file && file_exists($file['path'])) {
        unlink($file['path']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM secure_files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $userId]);
    header("Location: secure_files.php?success=Fichier supprimé");
    exit();
}

// Téléchargement de fichier
if ($action === 'download' && isset($_GET['id'])) {
    $fileId = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("SELECT original_name, path FROM secure_files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $userId]);
    $file = $stmt->fetch();
    
    if ($file && file_exists($file['path'])) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . filesize($file['path']));
        readfile($file['path']);
        exit();
    }
    header("Location: secure_files.php?error=Fichier introuvable");
    exit();
}

// Récupérer la liste des fichiers sécurisés
$stmt = $pdo->prepare("SELECT * FROM secure_files WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$secureFiles = $stmt->fetchAll();

$successMsg = $_GET['success'] ?? '';
$errorMsg = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace sécurisé - Lun'Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles supplémentaires pour l'espace sécurisé */
        .secure-page {
            background: #0a0e27;
            min-height: 100vh;
        }
        .secure-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .secure-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #818cf8;
            margin-bottom: 0.5rem;
        }
        .secure-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }
        .secure-stats {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .stat-badge {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.813rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .stat-badge .badge-value {
            font-weight: 600;
            color: #818cf8;
        }
        .secure-empty {
            text-align: center;
            padding: 4rem 2rem;
        }
        .secure-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .secure-empty-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: white;
            margin-bottom: 0.5rem;
        }
        .secure-empty-subtitle {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Alignement des colonnes pour l'espace sécurisé */
        .files-list .list-header {
            display: grid;
            grid-template-columns: 3fr 1.5fr 1.5fr 1fr 100px;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .files-list .file-row {
            display: grid;
            grid-template-columns: 3fr 1.5fr 1.5fr 1fr 100px;
            padding: 0.75rem 1rem;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .col-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            overflow: hidden;
        }

        .col-name .file-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .col-date, .col-type, .col-size {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.813rem;
        }

        .col-actions {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body class="files-page secure-page">
    <div class="files-wrapper">
        <!-- Sidebar -->
        <aside class="files-sidebar">
            <div class="sidebar-logo">
                <h1>LUN'DRIVE</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    <span>Tableau de bord</span>
                </a>
                <a href="files.php" class="nav-link">
                    <span class="nav-icon">📁</span>
                    <span>Mes fichiers</span>
                </a>
                <a href="secure_files.php" class="nav-link active">
                    <span class="nav-icon">🔒</span>
                    <span>Espace sécurisé</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar-small"><?= strtoupper(substr($username, 0, 2)) ?></div>
                    <div class="user-details">
                        <div class="user-name-sidebar"><?= htmlspecialchars($username) ?></div>
                        <div class="user-email-sidebar"><?= htmlspecialchars($_SESSION['email']) ?></div>
                    </div>
                </div>
                <a href="secure_pin_logout.php" class="logout-btn-sidebar" onclick="return confirm('Quitter l\'espace sécurisé ?')">🔓 QUITTER</a>
            </div>
        </aside>

        <main class="files-main">
            <div class="secure-header">
                <h1>🔒 Espace sécurisé</h1>
                <p>Vos fichiers protégés par code PIN</p>
            </div>

            <div class="secure-stats">
                <div class="stat-badge">
                    <span>📁</span>
                    <span class="badge-value"><?= count($secureFiles) ?></span>
                    <span>fichier(s) sécurisé(s)</span>
                </div>
                <div class="stat-badge">
                    <span>🔒</span>
                    <span>Protégé par PIN</span>
                </div>
            </div>

            <div class="actions-bar">
                <div class="actions-left">
                    <form method="POST" action="secure_files.php?action=upload" enctype="multipart/form-data" style="display: inline;">
                        <input type="file" name="file" id="secureFileInput" style="display: none;" onchange="this.form.submit()">
                        <button type="button" class="action-btn" onclick="document.getElementById('secureFileInput').click()">📥 Importer</button>
                    </form>
                </div>
            </div>

            <div class="files-container">
                <div class="files-list">
                    <div class="list-header">
                        <div class="col-name">Nom</div>
                        <div class="col-date">Date</div>
                        <div class="col-type">Type</div>
                        <div class="col-size">Taille</div>
                        <div class="col-actions">Actions</div>
                    </div>
                    
                    <?php if (empty($secureFiles)): ?>
                        <div class="secure-empty">
                            <div class="secure-empty-icon">🔒</div>
                            <div class="secure-empty-title">Espace sécurisé vide</div>
                            <div class="secure-empty-subtitle">Importez des fichiers pour les protéger</div>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($secureFiles as $file): ?>
                        <div class="file-row">
                            <div class="col-name">
                                <span class="file-icon">📄</span>
                                <span class="file-name" title="<?= htmlspecialchars($file['original_name']) ?>"><?= htmlspecialchars($file['original_name']) ?></span>
                            </div>
                            <div class="col-date"><?= date('d/m/Y H:i', strtotime($file['created_at'])) ?></div>
                            <div class="col-type"><?= htmlspecialchars($file['type']) ?></div>
                            <div class="col-size"><?= number_format($file['size'] / 1024, 1) ?> Ko</div>
                            <div class="col-actions">
                                <a href="secure_files.php?action=download&id=<?= $file['id'] ?>" class="action-download" title="Télécharger">⬇️</a>
                                <a href="secure_files.php?action=delete&id=<?= $file['id'] ?>" class="action-delete" onclick="return confirm('Supprimer ce fichier ?')" title="Supprimer">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="notificationContainer" class="notification-container"></div>
    <script>
        function showNotification(message, type = 'success', duration = 3000) {
            const container = document.getElementById('notificationContainer');
            if (!container) return;

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            let icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';

            notification.innerHTML = `
                <div class="notification-icon">${icon}</div>
                <div class="notification-content">${message}</div>
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

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($successMsg): ?>
                showNotification('<?= addslashes(htmlspecialchars($successMsg, ENT_QUOTES)) ?>', 'success', 5000);
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                showNotification('<?= addslashes(htmlspecialchars($errorMsg, ENT_QUOTES)) ?>', 'error', 5000);
            <?php endif; ?>
        });
    </script>
</body>
</html>