<?php
// delete_account.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

$database = new Database();
$pdo = $database->getConnection();

// Supprimer les fichiers physiques
$userUploadDir = 'uploads/user_' . $userId . '/';
if (file_exists($userUploadDir)) {
    function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
    deleteDirectory($userUploadDir);
}

// Supprimer l'utilisateur (les fichiers en BDD seront supprimés par CASCADE)
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Détruire la session
session_destroy();

echo json_encode(['success' => true, 'message' => 'Compte supprimé avec succès']);
exit();
?>