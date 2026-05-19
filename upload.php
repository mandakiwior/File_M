<?php
// upload.php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'config/upload.php';  // ← AJOUTER CETTE LIGNE

requireLogin();

$userId = $_SESSION['user_id'];
$targetFolder = $_POST['folder'] ?? 'root';

// Créer un fichier log pour diagnostiquer les uploads
$logDir = '/opt/lampp/logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/uploads_' . date('Y-m-d') . '.log';

function log_upload($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

log_upload("===== TENTATIVE D'UPLOAD POUR L'UTILISATEUR $userId =====");
log_upload("PHP Limits - upload_max_filesize: " . ini_get('upload_max_filesize'));
log_upload("PHP Limits - post_max_size: " . ini_get('post_max_size'));
log_upload("PHP Limits - max_execution_time: " . ini_get('max_execution_time'));
log_upload("PHP Limits - memory_limit: " . ini_get('memory_limit'));
log_upload("POST data: " . json_encode($_POST));
log_upload("FILES data: " . json_encode($_FILES));
log_upload("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
log_upload("CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
log_upload("CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set'));

// Vérifier si un fichier a été uploadé
if (!isset($_FILES['files']) || $_FILES['files']['error'] === UPLOAD_ERR_NO_FILE) {
    log_upload("✗ Aucun fichier détecté - redirection");
    header("Location: files.php?tab=user&folder=$targetFolder&error=Aucun fichier sélectionné");
    exit();
}

log_upload("✓ Fichier détecté: " . $_FILES['files']['name']);

// Configuration de l'upload
$uploadBaseDir = 'uploads/user_' . $userId . '/';

// Créer le dossier de base s'il n'existe pas
if (!file_exists($uploadBaseDir)) {
    mkdir($uploadBaseDir, 0777, true);
}

// Vérifier que le dossier est accessible en écriture
if (!is_writable($uploadBaseDir)) {
    die("Erreur critique: dossier non accessible en écriture ($uploadBaseDir). Contactez un administrateur.");
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

$response = ['success' => [], 'errors' => []];

// Traiter le fichier unique
log_upload("--- TRAITEMENT DU FICHIER ---");
$originalName = $_FILES['files']['name'];
$fileSize = $_FILES['files']['size'];
$fileType = $_FILES['files']['type'];
$fileError = $_FILES['files']['error'];
$tmpName = $_FILES['files']['tmp_name'];

log_upload("Début upload: $originalName (taille: $fileSize, type: $fileType, erreur: $fileError)");
    
    // Validation centralisée
    $validation = validateUploadedFile($originalName, $fileSize, $fileError, $allowedExtensions, $maxFileSize);
    
    if (!$validation['valid']) {
        $response['errors'][] = "$originalName: " . $validation['message'];
        log_upload("✗ Validation échouée: $originalName - " . $validation['message']);
        log_upload("--- FIN TRAITEMENT DU FICHIER ---\n");
        // Redirection avec message d'erreur
        log_upload("===== FIN DE TENTATIVE D'UPLOAD (Succès: 0, Erreurs: 1) =====\n");
        $folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
        $logInfo = " [Logs: $logFile]";
        $errorMsg = urlencode("$originalName: " . $validation['message'] . $logInfo);
        header("Location: files.php?tab=user$folderParam&error=$errorMsg");
        exit();
    }
    
    log_upload("✓ Validation OK: $originalName");
    
    $extension = $validation['extension'];
    
    // Nettoyer le nom du fichier
    $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    
    // Générer un nom unique
    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $uniqueName;
    
    // Déplacer le fichier avec diagnostic détaillé
    if (!file_exists($tmpName)) {
        $errorMsg = "$originalName (fichier temporaire manquant)";
        log_upload("✗ Fichier temporaire manquant: $tmpName");
        log_upload("--- FIN TRAITEMENT DU FICHIER ---\n");
        log_upload("===== FIN DE TENTATIVE D'UPLOAD (Succès: 0, Erreurs: 1) =====\n");
        $folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
        $logInfo = " [Logs: $logFile]";
        header("Location: files.php?tab=user$folderParam&error=" . urlencode($errorMsg . $logInfo));
        exit();
    }
    
    log_upload("✓ Fichier temporaire trouvé: $tmpName");
    
    // Vérifier l'espace disque
    $freespace = disk_free_space('/');
    if ($freespace < $fileSize * 2) {
        $errorMsg = "$originalName (espace disque insuffisant)";
        log_upload("✗ Espace disque insuffisant: " . round($freespace / 1024 / 1024) . " Mo libres");
        log_upload("--- FIN TRAITEMENT DU FICHIER ---\n");
        log_upload("===== FIN DE TENTATIVE D'UPLOAD (Succès: 0, Erreurs: 1) =====\n");
        $folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
        $logInfo = " [Logs: $logFile]";
        header("Location: files.php?tab=user$folderParam&error=" . urlencode($errorMsg . $logInfo));
        exit();
    }
    
    log_upload("✓ Espace disque OK: " . round($freespace / 1024 / 1024) . " Mo libres");
    
    // Vérifier les permissions du dossier
    if (!is_writable($uploadDir)) {
        $errorMsg = "$originalName (dossier non accessible en écriture)";
        log_upload("✗ Dossier non accessible: $uploadDir");
        log_upload("--- FIN TRAITEMENT DU FICHIER ---\n");
        log_upload("===== FIN DE TENTATIVE D'UPLOAD (Succès: 0, Erreurs: 1) =====\n");
        $folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
        $logInfo = " [Logs: $logFile]";
        header("Location: files.php?tab=user$folderParam&error=" . urlencode($errorMsg . $logInfo));
        exit();
    }
    
    log_upload("✓ Dossier accessible: $uploadDir");
    
    if (move_uploaded_file($tmpName, $filePath)) {
        log_upload("✓ Fichier déplacé: $filePath");
        
        // Chemin relatif pour la BDD
        $relativePath = str_replace('uploads/', '', $filePath);
        
        $database = new Database();
        $pdo = $database->getConnection();
        
        $folderId = ($targetFolder !== 'root') ? $targetFolder : null;
        
        try {
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
            
            log_upload("✓ Insertion base réussie: $originalName");
            $response['success'][] = $originalName;
        } catch (Exception $e) {
            // Si l'insertion échoue, supprimer le fichier uploadé
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            log_upload("✗ Erreur base de données: " . $e->getMessage());
            $response['errors'][] = "$originalName (erreur base de données)";
        }
    } else {
        // move_uploaded_file a échoué - diagnostic
        $error_reason = "déplacement impossible";
        if (file_exists($filePath)) {
            $error_reason = "fichier existe déjà";
        }
        log_upload("✗ move_uploaded_file échoué: $error_reason");
        $response['errors'][] = "$originalName ($error_reason)";
    }
    log_upload("--- FIN TRAITEMENT DU FICHIER ---\n");

// Redirection avec message
log_upload("===== FIN DE TENTATIVE D'UPLOAD (Succès: " . count($response['success']) . ", Erreurs: " . count($response['errors']) . ") =====\n");

$folderParam = ($targetFolder !== 'root') ? '&folder=' . $targetFolder : '';
$logInfo = " [Logs: $logFile]";

if (!empty($response['errors'])) {
    $errorMsg = urlencode(implode(', ', $response['errors']) . $logInfo);
    header("Location: files.php?tab=user$folderParam&error=$errorMsg");
} else {
    $successMsg = urlencode('Fichier importé avec succès' . $logInfo);
    header("Location: files.php?tab=user$folderParam&success=$successMsg");
}
exit();
?>