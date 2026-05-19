<?php
// dashboard.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

$database = new Database();
$pdo = $database->getConnection();

// Récupérer les statistiques de l'utilisateur
// Nombre de fichiers utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ?");
$stmt->execute([$userId]);
$userFilesCount = $stmt->fetchColumn();

// Nombre de dossiers utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ?");
$stmt->execute([$userId]);
$userFoldersCount = $stmt->fetchColumn();

// Récupérer les statistiques des fichiers système
$stmt = $pdo->prepare("SELECT COUNT(*) FROM system_files WHERE type = 'file'");
$stmt->execute();
$systemFilesCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM system_files WHERE type = 'directory'");
$stmt->execute();
$systemFoldersCount = $stmt->fetchColumn();

// Espace total utilisé par l'utilisateur
$stmt = $pdo->prepare("SELECT SUM(size) FROM files WHERE user_id = ?");
$stmt->execute([$userId]);
$totalUsed = $stmt->fetchColumn() ?? 0;

// Espace total disponible (172 Go)
$totalSpace = 172 * 1024 * 1024 * 1024; // 172 Go en octets
$freeSpace = $totalSpace - $totalUsed;
$usedPercentage = ($totalUsed / $totalSpace) * 100;

// Fonction pour formater la taille
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 1) . ' Go';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' Mo';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' Ko';
    }
    return $bytes . ' o';
}

// Générer une couleur d'avatar basée sur l'email
function getAvatarColor($email) {
    $hash = md5($email);
    $colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec489a'];
    $index = hexdec(substr($hash, 0, 2)) % count($colors);
    return $colors[$index];
}

// Récupérer les initiales pour l'avatar
function getInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

$avatarColor = getAvatarColor($email);
$initials = getInitials($username);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Lun'Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div>
                <div class="sidebar-logo">
                    <h1>LUN'DRIVE</h1>
                </div>
                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="nav-link active">
                        <span class="nav-icon">🏠</span>
                        <span>Tableau de bord</span>
                    </a>
                    <a href="files.php?tab=user&folder=root" class="nav-link">
                        <span class="nav-icon">📁</span>
                        <span>Mes fichiers</span>
                    </a>
                </nav>
            </div>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar-small" style="background: <?= $avatarColor ?>;">
                        <?= $initials ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name-sidebar"><?= htmlspecialchars($username) ?></div>
                        <div class="user-email-sidebar"><?= htmlspecialchars($email) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn-sidebar">SE DÉCONNECTER</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <!-- En-tête -->
            <header class="dashboard-header">
                <div class="welcome-text">
                    <h1>Bienvenue <span class="username-highlight"><?= htmlspecialchars($username) ?></span></h1>
                    <p class="user-email-header"><?= htmlspecialchars($email) ?></p>
                </div>
                <div class="user-avatar-large" style="background: <?= $avatarColor ?>;">
                    <?= $initials ?>
                </div>
            </header>

            <!-- Statistiques -->
            <div class="stats-section">
                <!-- Carte espace disque -->
                <div class="storage-card">
                    <div class="storage-text">
                        <span class="storage-used"><?= formatSize($totalUsed) ?></span>
                        <span class="storage-total">libres sur <?= formatSize($totalSpace) ?></span>
                    </div>
                    <div class="storage-bar">
                        <div class="storage-progress" style="width: <?= $usedPercentage ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Grands boutons Espace sécurisé et Utilisateur avec stats intégrées -->
            <div class="big-cards-section">
                <!-- Carte Espace sécurisé -->
                <div class="big-card system-card" onclick="location.href='secure_pin_setup.php'">
                    <div class="big-card-icon">🔒</div>
                    <div class="big-card-content">
                        <h2>Espace sécurisé</h2>
                        <div class="big-card-stats">
                            <div class="stat-item">
                                <span class="stat-number-large">PIN</span>
                                <span class="stat-label-large">Protégé</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-number-large">🔐</span>
                                <span class="stat-label-large">Sécurisé</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-number-large">📁</span>
                                <span class="stat-label-large">Privé</span>
                            </div>
                        </div>
                    </div>
                    <div class="big-card-arrow">→</div>
                </div>

                <!-- Carte Utilisateur -->
                <div class="big-card user-card" onclick="location.href='files.php?tab=user'">
                    <div class="big-card-icon">👤</div>
                    <div class="big-card-content">
                        <h2>Utilisateur</h2>
                        <div class="big-card-stats">
                            <div class="stat-item">
                                <span class="stat-number-large"><?= $userFilesCount + $userFoldersCount ?></span>
                                <span class="stat-label-large">Éléments</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-number-large"><?= $userFilesCount ?></span>
                                <span class="stat-label-large">Fichiers</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-number-large"><?= $userFoldersCount ?></span>
                                <span class="stat-label-large">Dossiers</span>
                            </div>
                        </div>
                    </div>
                    <div class="big-card-arrow">→</div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>