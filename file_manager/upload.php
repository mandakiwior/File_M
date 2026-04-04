<?php
// upload.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();


// Activer l'affichage des erreurs pour déboguer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$userId = $_SESSION['user_id'];

// Créer le dossier de base s'il n'existe pas
$baseDir = 'uploads/user_' . $userId . '/';
if (!file_exists($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        header("Location: files.php?tab=user&error=Impossible de créer le dossier de base pour l'upload");
        exit();
    }
}

$targetFolder = $_POST['folder'] ?? 'root';

// Configuration de l'upload
$uploadBaseDir = 'uploads/user_' . $userId . '/';

// Créer le dossier de base s'il n'existe pas
if (!file_exists($uploadBaseDir)) {
    mkdir($uploadBaseDir, 0777, true);
}

// Déterminer le dossier de destination
if ($targetFolder !== 'root') {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetFolder, $userId]);
    $folder = $stmt->fetch();
    
    if ($folder) {
        $uploadDir = $uploadBaseDir . $folder['path'] . '/';
    } else {
        $uploadDir = $uploadBaseDir;
    }
} else {
    $uploadDir = $uploadBaseDir;
}



// Créer le dossier de destination s'il n'existe pas
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Après avoir calculé $uploadDir, ajoute :
error_log("Upload dir: " . $uploadDir);
error_log("Upload dir exists: " . (file_exists($uploadDir) ? 'yes' : 'no'));

$response = ['success' => [], 'errors' => []];

if (isset($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
            $originalName = $_FILES['files']['name'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            $fileType = $_FILES['files']['type'][$key];
            
            // Nettoyer le nom du fichier
            $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            
            // Générer un nom unique
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $uniqueName;
            
            // Déplacer le fichier
            if (move_uploaded_file($tmpName, $filePath)) {
                // Chemin relatif pour la BDD
                $relativePath = str_replace('uploads/', '', $filePath);
                
                $database = new Database();
                $pdo = $database->getConnection();
                
                $folderId = ($targetFolder !== 'root') ? $targetFolder : null;
                
                // Enregistrer en BDD
                $stmt = $pdo->prepare("
                    INSERT INTO files (user_id, folder_id, name, original_name, path, type, size)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $userId,
                    $folderId,
                    $uniqueName,
                    $originalName,
                    $relativePath,
                    $fileType,
                    $fileSize
                ]);
                
                $response['success'][] = $originalName;
            } else {
                $response['errors'][] = $originalName . ' (erreur de déplacement)';
            }
        } else {
            $response['errors'][] = $_FILES['files']['name'][$key] . ' (erreur upload code: ' . $_FILES['files']['error'][$key] . ')';
        }
    }
}

// Redirection avec message
$folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
if (!empty($response['errors'])) {
    $errorMsg = urlencode(implode(', ', $response['errors']));
    header("Location: files.php?tab=user$folderParam&error=$errorMsg");
} else {
    $successMsg = urlencode(count($response['success']) . ' fichier(s) importé(s) avec succès');
    header("Location: files.php?tab=user$folderParam&success=$successMsg");
}
exit();
?>