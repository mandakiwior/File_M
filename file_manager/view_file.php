<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// view_file.php - Visualisation des fichiers (texte, image, audio, vidéo)
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];
$fileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fileId <= 0) {
    die("Fichier non spécifié");
}

$database = new Database();
$pdo = $database->getConnection();

// Récupérer les infos du fichier
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch();

if (!$file) {
    die("Fichier introuvable");
}

$filePath = 'uploads/' . $file['path'];
$originalName = $file['original_name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Définir les types de fichiers visualisables
$textExtensions = ['txt', 'md', 'json', 'xml', 'csv', 'log', 'php', 'js', 'css', 'html', 'htm', 'sql'];
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
$audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
$videoExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];

if (!file_exists($filePath)) {
    die("Fichier introuvable sur le serveur");
}

// Fonction pour obtenir la couleur du type de fichier
function getTypeColor($extension, $textExtensions, $imageExtensions, $audioExtensions, $videoExtensions) {
    if (in_array($extension, $textExtensions)) return '#10b981';
    if (in_array($extension, $imageExtensions)) return '#3b82f6';
    if (in_array($extension, $audioExtensions)) return '#f59e0b';
    if (in_array($extension, $videoExtensions)) return '#ef4444';
    return '#6b7280';
}

$typeColor = getTypeColor($extension, $textExtensions, $imageExtensions, $audioExtensions, $videoExtensions);

// Calculer la taille formatée
$fileSize = $file['size'];
if ($fileSize >= 1048576) {
    $formattedSize = round($fileSize / 1048576, 1) . ' Mo';
} elseif ($fileSize >= 1024) {
    $formattedSize = round($fileSize / 1024, 1) . ' Ko';
} else {
    $formattedSize = $fileSize . ' o';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation - <?= htmlspecialchars($originalName) ?> - Lun'Drive</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0e27;
            color: #fff;
            min-height: 100vh;
        }

        .viewer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .viewer-header {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .file-icon {
            font-size: 2rem;
        }

        .file-name {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .file-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .file-meta span {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-download {
            background: #10b981;
            color: white;
        }

        .btn-download:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Zone de contenu */
        .viewer-content {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Texte */
        .text-content {
            width: 100%;
            background: #1e1e2e;
            border-radius: 8px;
            padding: 1.5rem;
            overflow-x: auto;
        }

        .text-content pre {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #e2e8f0;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
        }

        /* Image */
        .image-content {
            text-align: center;
        }

        .image-content img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Audio */
        .audio-content {
            text-align: center;
        }

        .audio-content audio {
            width: 100%;
            max-width: 500px;
        }

        /* Vidéo */
        .video-content {
            text-align: center;
        }

        .video-content video {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
        }

        /* Erreur */
        .error-content {
            text-align: center;
            color: #ef4444;
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        /* Footer */
        .viewer-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .viewer-container {
                padding: 1rem;
            }
            
            .viewer-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .file-info {
                width: 100%;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: flex-end;
            }
            
            .viewer-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="viewer-container">
        <div class="viewer-header">
            <div class="file-info">
                <div class="file-icon">
                    <?php
                    if (in_array($extension, $textExtensions)) {
                        echo '📝';
                    } elseif (in_array($extension, $imageExtensions)) {
                        echo '🖼️';
                    } elseif (in_array($extension, $audioExtensions)) {
                        echo '🎵';
                    } elseif (in_array($extension, $videoExtensions)) {
                        echo '🎬';
                    } else {
                        echo '📄';
                    }
                    ?>
                </div>
                <div>
                    <div class="file-name"><?= htmlspecialchars($originalName) ?></div>
                    <div class="file-meta">
                        <span><?= strtoupper($extension) ?></span>
                        <span><?= $formattedSize ?></span>
                        <span><?= date('d/m/Y H:i', strtotime($file['created_at'])) ?></span>
                    </div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="download.php?id=<?= $fileId ?>" class="btn btn-download">⬇️ Télécharger</a>
                <a href="files.php?tab=user" class="btn btn-back">← Retour</a>
            </div>
        </div>

        <div class="viewer-content">
            <?php if (in_array($extension, $textExtensions)): ?>
                <!-- Visualisation Texte -->
                <div class="text-content">
                    <pre><?= htmlspecialchars(file_get_contents($filePath)) ?></pre>
                </div>

            <?php elseif (in_array($extension, $imageExtensions)): ?>
                <!-- Visualisation Image -->
                <div class="image-content">
                    <img src="download.php?id=<?= $fileId ?>" alt="<?= htmlspecialchars($originalName) ?>">
                </div>

            <?php elseif (in_array($extension, $audioExtensions)): ?>
                <!-- Visualisation Audio -->
                <div class="audio-content">
                    <audio controls autoplay>
                        <source src="download.php?id=<?= $fileId ?>" type="audio/<?= $extension ?>">
                        Votre navigateur ne supporte pas la lecture audio.
                    </audio>
                </div>

            <?php elseif (in_array($extension, $videoExtensions)): ?>
                <!-- Visualisation Vidéo -->
                <div class="video-content">
                    <video controls autoplay>
                        <source src="download.php?id=<?= $fileId ?>" type="video/<?= $extension ?>">
                        Votre navigateur ne supporte pas la lecture vidéo.
                    </video>
                </div>

            <?php else: ?>
                <!-- Type non supporté -->
                <div class="error-content">
                    <div class="error-icon">⚠️</div>
                    <h3>Visualisation non disponible</h3>
                    <p>Ce type de fichier (<?= strtoupper($extension) ?>) ne peut pas être visualisé directement.</p>
                    <p style="margin-top: 1rem;">
                        <a href="download.php?id=<?= $fileId ?>" class="btn btn-download" style="display: inline-block;">Télécharger le fichier</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="viewer-footer">
            Lun'Drive - Gestionnaire de fichiers
        </div>
    </div>
</body>
</html>