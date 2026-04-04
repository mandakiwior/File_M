<?php
// secure_api.php - API backend pour les requêtes AJAX
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$database = new Database();
$pdo = $database->getConnection();

// Vérifier si l'espace sécurisé est activé
if ($action === 'status') {
    $stmt = $pdo->prepare("SELECT secure_pin_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $enabled = $stmt->fetchColumn();
    echo json_encode(['enabled' => (bool)$enabled]);
    exit();
}

// Activer l'espace sécurisé avec code PIN
if ($action === 'enable') {
    $pin = $_POST['pin'] ?? '';
    
    if (!preg_match('/^\d{6}$/', $pin)) {
        echo json_encode(['success' => false, 'message' => 'Le code PIN doit contenir 6 chiffres']);
        exit();
    }
    
    $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET secure_pin = ?, secure_pin_enabled = TRUE WHERE id = ?");
    $stmt->execute([$hashedPin, $userId]);
    
    $secureDir = 'uploads/secure/user_' . $userId;
    if (!file_exists($secureDir)) {
        mkdir($secureDir, 0777, true);
    }
    
    echo json_encode(['success' => true, 'message' => 'Espace sécurisé activé']);
    exit();
}

// Désactiver l'espace sécurisé
if ($action === 'disable') {
    $stmt = $pdo->prepare("UPDATE users SET secure_pin_enabled = FALSE WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'message' => 'Espace sécurisé désactivé']);
    exit();
}

// Vérifier le code PIN
if ($action === 'verify') {
    $pin = $_POST['pin'] ?? '';
    
    $stmt = $pdo->prepare("SELECT secure_pin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pin, $user['secure_pin'])) {
        $_SESSION['secure_access'] = true;
        $_SESSION['secure_access_time'] = time();
        echo json_encode(['success' => true, 'message' => 'Accès autorisé']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Code PIN incorrect']);
    }
    exit();
}

// Vérifier si l'accès est autorisé (session)
if ($action === 'check') {
    $hasAccess = isset($_SESSION['secure_access']) && $_SESSION['secure_access'] === true;
    echo json_encode(['access' => $hasAccess]);
    exit();
}

// Déconnecter de l'espace sécurisé
if ($action === 'logout') {
    unset($_SESSION['secure_access']);
    unset($_SESSION['secure_access_time']);
    echo json_encode(['success' => true, 'message' => 'Déconnecté de l\'espace sécurisé']);
    exit();
}
?>