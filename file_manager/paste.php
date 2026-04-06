<?php
// paste.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Débogage
error_log('=== PASTE.PHP DEBUG ===');
error_log('userId: ' . $userId);
error_log('input reçu: ' . file_get_contents('php://input'));
error_log('input decodé: ' . json_encode($input));

$items = $input['items'] ?? [];
$targetFolder = $input['target_folder'] ?? 'root';
$isCut = $input['is_cut'] ?? false;

error_log('items count: ' . count($items));
error_log('items: ' . json_encode($items));
error_log('targetFolder: ' . $targetFolder);
error_log('isCut: ' . ($isCut ? 'true' : 'false'));

if (empty($items)) {
    error_log('Erreur: aucun élément à coller');
    echo json_encode(['success' => false, 'message' => 'Aucun élément à coller']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// Déterminer le dossier cible
if ($targetFolder === 'root') {
    $targetId = null;
    $targetPath = '';
} else {
    $stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetFolder, $userId]);
    $target = $stmt->fetch();
    
    if (!$target) {
        echo json_encode(['success' => false, 'message' => 'Dossier cible introuvable']);
        exit();
    }
    $targetId = $targetFolder;
    $targetPath = $target['path'];
}

$pasted = 0;
$errors = 0;

foreach ($items as $item) {
    $item = trim($item);
    
    if (strpos($item, 'folder_') === 0) {
        // Coller un dossier
        $folderId = str_replace('folder_', '', $item);
        
        $stmt = $pdo->prepare("SELECT name, path FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);
        $folder = $stmt->fetch();
        
        if ($folder) {
            // Vérifier si le nom existe déjà
            $stmt = $pdo->prepare("SELECT id FROM folders WHERE user_id = ? AND parent_id = ? AND name = ?");
            $stmt->execute([$userId, $targetId, $folder['name']]);
            
            $newName = $folder['name'];
            if ($stmt->fetch()) {
                $newName = $folder['name'] . '_copie_' . date('Ymd_His');
            }
            
            $sourcePath = 'uploads/user_' . $userId . '/' . $folder['path'];
            $newPath = $targetPath ? $targetPath . '/' . $newName : $newName;
            $destPath = 'uploads/user_' . $userId . '/' . $newPath;
            
            // Copier le dossier
            function copyDirectory($src, $dst) {
                if (!is_dir($src)) {
                    return copy($src, $dst);
                }
                if (!is_dir($dst)) {
                    mkdir($dst, 0777, true);
                }
                $files = scandir($src);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        copyDirectory($src . '/' . $file, $dst . '/' . $file);
                    }
                }
                return true;
            }
            
            if (copyDirectory($sourcePath, $destPath)) {
                // Insérer en BDD
                $stmt = $pdo->prepare("INSERT INTO folders (user_id, parent_id, name, path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $targetId, $newName, $newPath]);
                $pasted++;
                
                // Si c'est un "couper", supprimer l'original
                if ($isCut) {
                    // Supprimer le dossier original
                    function deleteDirectory($dir) {
                        if (!file_exists($dir)) return true;
                        if (!is_dir($dir)) return unlink($dir);
                        foreach (scandir($dir) as $item) {
                            if ($item == '.' || $item == '..') continue;
                            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
                        }
                        return rmdir($dir);
                    }
                    deleteDirectory($sourcePath);
                    
                    // Supprimer de la BDD
                    $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
                    $stmt->execute([$folderId]);
                }
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
        
    } elseif (strpos($item, 'file_') === 0) {
        // Coller un fichier
        $fileId = str_replace('file_', '', $item);
        
        $stmt = $pdo->prepare("SELECT name, original_name, path, type, size FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $userId]);
        $file = $stmt->fetch();
        
        if ($file) {
            $extension = pathinfo($file['original_name'], PATHINFO_EXTENSION);
            $newUniqueName = uniqid() . '_' . time() . '.' . $extension;
            
            $sourcePath = 'uploads/' . $file['path'];
            $newPath = 'user_' . $userId . '/' . ($targetPath ? $targetPath . '/' : '') . $newUniqueName;
            $destPath = 'uploads/' . $newPath;
            
            $destDir = dirname($destPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }
            
            if (copy($sourcePath, $destPath)) {
                $stmt = $pdo->prepare("INSERT INTO files (user_id, folder_id, name, original_name, path, type, size) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $targetId,
                    $newUniqueName,
                    $file['original_name'],
                    $newPath,
                    $file['type'],
                    $file['size']
                ]);
                $pasted++;
                
                // Si c'est un "couper", supprimer l'original
                if ($isCut) {
                    if (file_exists($sourcePath)) {
                        unlink($sourcePath);
                    }
                    $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
                    $stmt->execute([$fileId]);
                }
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
    }
}

$action = $isCut ? 'déplacé' : 'collé';
if ($errors > 0) {
    echo json_encode(['success' => false, 'message' => "📂 $pasted élément(s) $action, $errors erreur(s)"]);
} else {
    echo json_encode(['success' => true, 'message' => "✅ $pasted élément(s) $action avec succès"]);
}
exit();
?>