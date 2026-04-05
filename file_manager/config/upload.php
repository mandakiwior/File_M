<?php
// config/upload.php - Configuration centralisée des uploads

// Configuration des limites d'upload
// Note: Les valeurs sont définies dans php.ini, pas ici

// Extensions autorisées
$allowedExtensions = [
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
    // Audio
    'mp3', 'wav', 'ogg', 'flac', 'm4a',
    // Vidéo
    'mp4', 'webm', 'avi', 'mov', 'mkv', 'flv', 'wmv',
    // Documents
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    // Textes
    'txt', 'md', 'json', 'xml', 'csv', 'log',
    // Archives
    'zip', 'rar', '7z', 'tar', 'gz'
];

// Taille maximale (200 Mo pour vidéos)
$maxFileSize = 200 * 1024 * 1024;

// Messages d'erreur standardisés
$uploadErrors = [
    UPLOAD_ERR_INI_SIZE => 'Fichier trop gros (limite serveur)',
    UPLOAD_ERR_FORM_SIZE => 'Fichier trop gros (limite formulaire)',
    UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploadé',
    UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné',
    UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
    UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture',
    UPLOAD_ERR_EXTENSION => 'Extension PHP bloquée'
];

// Fonction pour vérifier un fichier
function validateUploadedFile($fileName, $fileSize, $fileError, $allowedExts, $maxSize) {
    // Vérifier l'erreur d'upload
    if ($fileError !== UPLOAD_ERR_OK) {
        global $uploadErrors;
        $errorMsg = $uploadErrors[$fileError] ?? ('Erreur inconnue (code ' . $fileError . ')');
        return ['valid' => false, 'message' => $errorMsg];
    }
    
    // Vérifier que le nom est fourni
    if (empty($fileName)) {
        return ['valid' => false, 'message' => 'Nom de fichier vide'];
    }
    
    // Vérifier la taille AVANT l'extension
    if ($fileSize <= 0) {
        return ['valid' => false, 'message' => 'Fichier vide ou corrompu'];
    }
    
    if ($fileSize > $maxSize) {
        $maxMo = round($maxSize / 1024 / 1024, 1);
        $fileMo = round($fileSize / 1024 / 1024, 1);
        return ['valid' => false, 'message' => "Fichier trop gros ($fileMo Mo, max $maxMo Mo)"];
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (empty($extension) || !in_array($extension, $allowedExts)) {
        return ['valid' => false, 'message' => "Type de fichier non autorisé (.$extension)"];
    }
    
    return ['valid' => true, 'extension' => $extension];
}
?>