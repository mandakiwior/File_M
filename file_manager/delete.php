<?php
// delete.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];
$items = $_POST['items'] ?? '';
$currentFolder = $_POST['folder'] ?? 'root';

if (empty($items)) {
    header("Location: files.php?tab=user&folder=$currentFolder&error=Aucun élément sélectionné");
    exit();
}

$itemsArray = explode(',', $items);
$database = new Database();
$pdo = $database->getConnection();

$deleted = 0;
$errors = 0;

foreach ($itemsArray as $item) {
    $item = trim($item);
    
    if (strpos($item, 'folder_') === 0) {
        $folderId = str_replace('folder_', '', $item);
        
        $stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);
        $folder = $stmt->fetch();
        
        if ($folder) {
            $physicalPath = 'uploads/user_' . $userId . '/' . $folder['path'];
            if (file_exists($physicalPath)) {
                function deleteDirectory($dir) {
                    if (!file_exists($dir)) return true;
                    if (!is_dir($dir)) return unlink($dir);
                    foreach (scandir($dir) as $item) {
                        if ($item == '.' || $item == '..') continue;
                        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
                    }
                    return rmdir($dir);
                }
                deleteDirectory($physicalPath);
            }
            
            $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
            $stmt->execute([$folderId, $userId]);
            $deleted++;
        } else {
            $errors++;
        }
        
    } elseif (strpos($item, 'file_') === 0) {
        $fileId = str_replace('file_', '', $item);
        
        $stmt = $pdo->prepare("SELECT path FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();
        
        if ($file) {
            $physicalPath = 'uploads/' . $file['path'];
            if (file_exists($physicalPath)) {
                unlink($physicalPath);
            }
            
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
            $stmt->execute([$fileId, $userId]);
            $deleted++;
        } else {
            $errors++;
        }
    }
}

// Redirection avec message de notification
if ($errors > 0) {
    $message = urlencode("🗑️ $deleted élément(s) supprimé(s), $errors erreur(s)");
    header("Location: files.php?tab=user&folder=$currentFolder&error=$message");
} else {
    $message = urlencode("✅ $deleted élément(s) supprimé(s) avec succès");
    header("Location: files.php?tab=user&folder=$currentFolder&success=$message");
}
exit();
?>