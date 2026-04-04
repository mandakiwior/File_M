<?php
// secure_files.php
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();

// Vérifier l'accès à l'espace sécurisé (via session)
$hasAccess = isset($_SESSION['secure_access']) && $_SESSION['secure_access'] === true;

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$action = $_GET['action'] ?? '';

$database = new Database();
$pdo = $database->getConnection();

// Si pas d'accès, afficher le formulaire PIN
if (!$hasAccess) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accès sécurisé - Lun'Drive</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            .pin-container {
                max-width: 400px;
                margin: 100px auto;
                text-align: center;
            }
            .pin-input {
                font-size: 2rem;
                letter-spacing: 10px;
                text-align: center;
                padding: 0.5rem;
            }
        </style>
    </head>
    <body class="dashboard-body">
        <div class="dashboard-wrapper">
            <main class="dashboard-main" style="margin-left: 0;">
                <div class="pin-container">
                    <h1>🔒 Espace sécurisé</h1>
                    <p>Entrez votre code PIN à 6 chiffres pour accéder à vos fichiers protégés.</p>
                    
                    <div id="errorMsg" class="alert alert-error" style="display: none;"></div>
                    
                    <form id="pinForm">
                        <div class="form-group">
                            <label>Code PIN</label>
                            <input type="password" id="pin" class="pin-input" maxlength="6" pattern="\d{6}" autofocus required>
                        </div>
                        <button type="submit" class="btn-login">Accéder</button>
                    </form>
                    
                    <p style="margin-top: 20px;">
                        <a href="files.php?tab=user">← Retour à mes fichiers</a>
                    </p>
                </div>
            </main>
        </div>
        
        <script>
        document.getElementById('pinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const pin = document.getElementById('pin').value;
            const errorDiv = document.getElementById('errorMsg');
            
            fetch('secure_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=verify&pin=' + encodeURIComponent(pin)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'secure_files.php';
                } else {
                    errorDiv.style.display = 'block';
                    errorDiv.textContent = data.message;
                    document.getElementById('pin').value = '';
                    document.getElementById('pin').focus();
                }
            })
            .catch(error => {
                errorDiv.style.display = 'block';
                errorDiv.textContent = 'Erreur de connexion';
            });
        });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// ========== SUITE : ESPACE SÉCURISÉ ACCESSIBLE ==========

// Upload de fichier
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $secureDir = 'uploads/secure/user_' . $userId . '/';
    if (!file_exists($secureDir)) {
        mkdir($secureDir, 0777, true);
    }
    
    if (isset($_FILES['file'])) {
        $originalName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $tmpName = $_FILES['file']['tmp_name'];
        
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
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
            header("Location: secure_files.php?error=Erreur lors de l'import");
        }
    }
    exit();
}

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
</head>
<body class="files-page">
    <div class="files-wrapper">
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
            <div class="files-header">
                <h1>🔒 Espace sécurisé</h1>
            </div>

            <?php if ($successMsg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

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
                        <div class="col-name">Name</div>
                        <div class="col-date">Date</div>
                        <div class="col-type">Type</div>
                        <div class="col-size">Taille</div>
                        <div class="col-actions">Actions</div>
                    </div>
                    
                    <?php if (empty($secureFiles)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🔒</div>
                            <p class="empty-title">Espace sécurisé vide</p>
                            <p class="empty-subtitle">Importez des fichiers pour les protéger</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($secureFiles as $file): ?>
                        <div class="file-row">
                            <div class="col-name">
                                <span class="file-icon">📄</span>
                                <span class="file-name"><?= htmlspecialchars($file['original_name']) ?></span>
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
</body>
</html>