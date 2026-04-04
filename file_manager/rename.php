<?php
// rename.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];
$itemId = $_POST['id'] ?? '';
$newName = trim($_POST['name'] ?? '');

if (empty($itemId) || empty($newName)) {
    header("Location: files.php?tab=user&error=Nom invalide");
    exit();
}

// Vérifier les caractères interdits
if (preg_match('/[\/\\\:\*\?\"\<\>\|]/', $newName)) {
    header("Location: files.php?tab=user&error=Le nom contient des caractères interdits");
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

if (strpos($itemId, 'folder_') === 0) {
    // Renommer un dossier
    $folderId = str_replace('folder_', '', $itemId);
    
    // Récupérer les infos du dossier
    $stmt = $pdo->prepare("SELECT name, path, parent_id FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$folderId, $userId]);
    $folder = $stmt->fetch();
    
    if (!$folder) {
        header("Location: files.php?tab=user&error=Dossier introuvable");
        exit();
    }
    
    // Vérifier si un dossier avec le nouveau nom existe déjà
    $stmt = $pdo->prepare("
        SELECT id FROM folders 
        WHERE user_id = ? AND name = ? AND parent_id = ? AND id != ?
    ");
    $stmt->execute([$userId, $newName, $folder['parent_id'], $folderId]);
    if ($stmt->fetch()) {
        header("Location: files.php?tab=user&error=Un dossier avec ce nom existe déjà");
        exit();
    }
    
    // Renommer physiquement
    $oldPath = 'uploads/user_' . $userId . '/' . $folder['path'];
    $parentPath = dirname($oldPath);
    $newPath = $parentPath . '/' . $newName;
    
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
    
    // Mettre à jour le chemin dans la BDD
    $newFullPath = str_replace($folder['name'], $newName, $folder['path']);
    $stmt = $pdo->prepare("UPDATE folders SET name = ?, path = ? WHERE id = ?");
    $stmt->execute([$newName, $newFullPath, $folderId]);
    
    header("Location: files.php?tab=user&success=Dossier renommé en '$newName'");
    
} elseif (strpos($itemId, 'file_') === 0) {
    // Renommer un fichier
    $fileId = str_replace('file_', '', $itemId);
    
    // Récupérer les infos du fichier
    $stmt = $pdo->prepare("SELECT name, original_name, path FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$fileId, $userId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        header("Location: files.php?tab=user&error=Fichier introuvable");
        exit();
    }
    
    // Conserver l'extension
    $extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
    $newOriginalName = $newName . '.' . $extension;
    
    // Renommer physiquement
    $oldPath = 'uploads/' . $file['path'];
    $newPath = dirname($oldPath) . '/' . $newName . '.' . $extension;
    
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
    }
    
    // Mettre à jour en BDD
    $newUniqueName = $newName . '.' . $extension;
    $newRelativePath = str_replace($file['name'], $newUniqueName, $file['path']);
    
    $stmt = $pdo->prepare("UPDATE files SET name = ?, original_name = ?, path = ? WHERE id = ?");
    $stmt->execute([$newUniqueName, $newOriginalName, $newRelativePath, $fileId]);
    
    header("Location: files.php?tab=user&success=Fichier renommé en '$newOriginalName'");
}

exit();
?>