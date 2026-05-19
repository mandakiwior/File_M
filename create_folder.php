<?php
// create_folder.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

// Créer le dossier de base s'il n'existe pas
$baseDir = 'uploads/user_' . $userId . '/';
if (!file_exists($baseDir)) {
    if (!mkdir($baseDir, 0777, true)) {
        header("Location: files.php?tab=user&folder=" . ($_POST['folder'] ?? 'root') . "&error=Impossible de créer le dossier de base");
        exit();
    }
}

$userId = $_SESSION['user_id'];
$folderName = trim($_POST['name'] ?? '');
$parentFolder = $_POST['folder'] ?? 'root';

// Vérifications
if (empty($folderName)) {
    header("Location: files.php?tab=user&folder=$parentFolder&error=Le nom du dossier ne peut pas être vide");
    exit();
}

if (preg_match('/[\/\\\:\*\?\"\<\>\|]/', $folderName)) {
    header("Location: files.php?tab=user&folder=$parentFolder&error=Le nom contient des caractères interdits");
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// Déterminer le chemin du dossier parent
if ($parentFolder === 'root') {
    $parentPath = '';
    $parentId = null;
} else {
    $stmt = $pdo->prepare("SELECT path FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$parentFolder, $userId]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        header("Location: files.php?tab=user&folder=root&error=Dossier parent introuvable");
        exit();
    }
    $parentPath = $parent['path'];
    $parentId = $parentFolder;
}

// Vérifier les doublons
$stmt = $pdo->prepare("
    SELECT id FROM folders 
    WHERE user_id = ? AND name = ? AND (parent_id = ? OR (parent_id IS NULL AND ? IS NULL))
");
$stmt->execute([$userId, $folderName, $parentId, $parentId]);

if ($stmt->fetch()) {
    header("Location: files.php?tab=user&folder=$parentFolder&error=Un dossier avec ce nom existe déjà");
    exit();
}

// Calculer le chemin complet
$fullPath = $parentPath ? $parentPath . '/' . $folderName : $folderName;

// ===== CRÉATION PHYSIQUE DU DOSSIER =====
$physicalBase = 'uploads/user_' . $userId . '/';
$physicalPath = $physicalBase . $fullPath;

// Créer le dossier de base s'il n'existe pas
if (!file_exists($physicalBase)) {
    mkdir($physicalBase, 0777, true);
}

// Créer le dossier physique
if (!file_exists($physicalPath)) {
    if (!mkdir($physicalPath, 0777, true)) {
        header("Location: files.php?tab=user&folder=$parentFolder&error=Impossible de créer le dossier sur le serveur");
        exit();
    }
}

// Enregistrer en base de données
$stmt = $pdo->prepare("
    INSERT INTO folders (user_id, parent_id, name, path) 
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$userId, $parentId, $folderName, $fullPath]);

header("Location: files.php?tab=user&folder=$parentFolder&success=Dossier '$folderName' créé avec succès");
exit();
?>