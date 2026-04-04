<?php
// move.php (version avec redirection)
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];
$items = $_POST['items'] ?? '';
$targetFolder = $_POST['target_folder'] ?? '';
$currentFolder = $_POST['folder'] ?? 'root';

if (empty($items)) {
    header("Location: files.php?tab=user&folder=$currentFolder&error=Aucun élément sélectionné");
    exit();
}

if (empty($targetFolder)) {
    header("Location: files.php?tab=user&folder=$currentFolder&error=Veuillez choisir un dossier de destination");
    exit();
}

$itemsArray = explode(',', $items);
$database = new Database();
$pdo = $database->getConnection();

// Récupérer les infos du dossier cible
if ($targetFolder === 'root') {
    $targetPath = '';
    $targetId = null;
} else {
    $stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetFolder, $userId]);
    $target = $stmt->fetch();
    
    if (!$target) {
        header("Location: files.php?tab=user&folder=$currentFolder&error=Dossier cible introuvable");
        exit();
    }
    $targetPath = $target['path'];
    $targetId = $targetFolder;
}

$moved = 0;
$errors = 0;

foreach ($itemsArray as $item) {
    $item = trim($item);
    
    if (strpos($item, 'folder_') === 0) {
        $folderId = str_replace('folder_', '', $item);
        
        $stmt = $pdo->prepare("SELECT name, path FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);
        $folder = $stmt->fetch();
        
        if ($folder) {
            // Vérifier qu'on ne déplace pas dans un sous-dossier de lui-même
            if ($targetPath && strpos($targetPath, $folder['path']) === 0) {
                $errors++;
                continue;
            }
            
            $oldPhysicalPath = 'uploads/user_' . $userId . '/' . $folder['path'];
            $newDbPath = ($targetPath ? $targetPath . '/' : '') . $folder['name'];
            $newPhysicalPath = 'uploads/user_' . $userId . '/' . $newDbPath;
            
            if (file_exists($oldPhysicalPath)) {
                rename($oldPhysicalPath, $newPhysicalPath);
            }
            
            $stmt = $pdo->prepare("UPDATE folders SET parent_id = ?, path = ? WHERE id = ?");
            $stmt->execute([$targetId, $newDbPath, $folderId]);
            $moved++;
        } else {
            $errors++;
        }
        
    } elseif (strpos($item, 'file_') === 0) {
        $fileId = str_replace('file_', '', $item);
        
        $stmt = $pdo->prepare("SELECT name, path FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();
        
        if ($file) {
            $oldPhysicalPath = 'uploads/' . $file['path'];
            $newDbPath = 'user_' . $userId . '/' . ($targetPath ? $targetPath . '/' : '') . $file['name'];
            $newPhysicalPath = 'uploads/' . $newDbPath;
            
            $destDir = dirname($newPhysicalPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }
            
            if (file_exists($oldPhysicalPath)) {
                rename($oldPhysicalPath, $newPhysicalPath);
            }
            
            $stmt = $pdo->prepare("UPDATE files SET folder_id = ?, path = ? WHERE id = ?");
            $stmt->execute([$targetId, $newDbPath, $fileId]);
            $moved++;
        } else {
            $errors++;
        }
    }
}

// Redirection avec message
if ($errors > 0) {
    $msg = urlencode("📂 $moved élément(s) déplacé(s), $errors erreur(s)");
    header("Location: files.php?tab=user&folder=$currentFolder&error=$msg");
} else {
    $msg = urlencode("✅ $moved élément(s) déplacé(s) avec succès");
    header("Location: files.php?tab=user&folder=$currentFolder&success=$msg");
}
exit();
?>