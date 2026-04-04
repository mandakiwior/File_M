<?php
// change_password.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Correction des chemins
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

// Récupérer et décoder le JSON
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides: ' . json_last_error_msg()]);
    exit();
}

$oldPassword = $input['old_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($oldPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs']);
    exit();
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Vérifier l'ancien mot de passe
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }

    if (!password_verify($oldPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Ancien mot de passe incorrect']);
        exit();
    }

    // Mettre à jour le mot de passe
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $result = $stmt->execute([$newHashedPassword, $userId]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
exit();
?>