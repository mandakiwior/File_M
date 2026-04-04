<?php
// cleanup_archive.php - Supprimer l'archive temporaire
require_once 'includes/session.php';

$input = json_decode(file_get_contents('php://input'), true);
$archiveFile = $input['file'] ?? '';

if (!empty($archiveFile)) {
    $archivePath = 'temp/' . $archiveFile;
    if (file_exists($archivePath)) {
        unlink($archivePath);
    }
}

echo json_encode(['success' => true]);
exit();
?>