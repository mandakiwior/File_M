<?php
// authenticate.php
require_once 'includes/session.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Vérifier que les champs ne sont pas vides
    if (empty($email) || empty($password)) {
        header('Location: login.php?error=' . urlencode('Veuillez remplir tous les champs'));
        exit();
    }
    
    // Tentative de connexion
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit();
    } else {
        header('Location: login.php?error=' . urlencode($result['message']));
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>