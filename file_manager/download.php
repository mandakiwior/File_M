<?php
// download.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

// Vérifier si s'est une demande d'archive
$archiveFile = $_GET['archive'] ?? '';
if (!empty($archiveFile)) {
    $archivePath = 'temp/' . $archiveFile;
    if (file_exists($archivePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($archiveFile) . '"');
        header('Content-Length: ' . filesize($archivePath));
        readfile($archivePath);
        exit();
    }
}

// Sinon, téléchargement normal d'un fichier
$userId = $_SESSION['user_id'];
$fileId = $_GET['id'] ?? 0;

$database = new Database();
$pdo = $database->getConnection();

// Récupérer les infos du fichier
$stmt = $pdo->prepare("SELECT name, original_name, path FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch();

if (!$file) {
    header("Location: files.php?tab=user&error=Fichier introuvable");
    exit();
}

$filePath = 'uploads/' . $file['path'];

if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header("Cache-Control: no-cache, must-revalidate");
    readfile($filePath);
    exit();
} else {
    header("Location: files.php?tab=user&error=Fichier introuvable sur le serveur");
    exit();
}
?>