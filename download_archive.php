<?php
// download_archive.php - Créer une archive ZIP pour téléchargement multiple
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Aucun élément à télécharger']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// Créer un nom unique pour l'archive
$archiveName = 'download_' . $userId . '_' . time() . '.zip';
$archivePath = 'temp/' . $archiveName;

// Créer le dossier temp s'il n'existe pas
if (!file_exists('temp')) {
    mkdir('temp', 0777, true);
}

// Créer une instance de ZipArchive
$zip = new ZipArchive();
if ($zip->open($archivePath, ZipArchive::CREATE) !== true) {
    echo json_encode(['success' => false, 'message' => 'Impossible de créer l\'archive']);
    exit();
}

$added = 0;
$errors = 0;

foreach ($items as $item) {
    $item = trim($item);
    
    if (strpos($item, 'file_') === 0) {
        // Ajouter un fichier
        $fileId = str_replace('file_', '', $item);
        
        $stmt = $pdo->prepare("SELECT original_name, path FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();
        
        if ($file) {
            $filePath = 'uploads/' . $file['path'];
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $file['original_name']);
                $added++;
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
        
    } elseif (strpos($item, 'folder_') === 0) {
        // Ajouter un dossier (et son contenu)
        $folderId = str_replace('folder_', '', $item);
        
        $stmt = $pdo->prepare("SELECT name, path FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);
        $folder = $stmt->fetch();
        
        if ($folder) {
            $folderPath = 'uploads/user_' . $userId . '/' . $folder['path'];
            if (file_exists($folderPath) && is_dir($folderPath)) {
                addFolderToZip($folderPath, $zip, $folder['name']);
                $added++;
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
    }
}

$zip->close();

if ($added === 0) {
    unlink($archivePath);
    echo json_encode(['success' => false, 'message' => 'Aucun élément valide à télécharger']);
    exit();
}

// Fonction pour ajouter un dossier récursivement au ZIP
function addFolderToZip($folderPath, $zip, $zipFolderName) {
    $files = scandir($folderPath);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $fullPath = $folderPath . '/' . $file;
        $zipPath = $zipFolderName . '/' . $file;
        
        if (is_dir($fullPath)) {
            addFolderToZip($fullPath, $zip, $zipPath);
        } else {
            $zip->addFile($fullPath, $zipPath);
        }
    }
}

$message = "$added élément(s) ajouté(s) à l'archive";
if ($errors > 0) {
    $message .= ", $errors erreur(s)";
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'archive' => $archiveName
]);
exit();
?>