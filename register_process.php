<?php
// register_process.php
require_once 'includes/session.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Vérifier que tous les champs sont remplis
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: register.php?error=' . urlencode('Veuillez remplir tous les champs'));
        exit();
    }
    
    // Vérifier que les mots de passe correspondent
    if ($password !== $confirm_password) {
        header('Location: register.php?error=' . urlencode('Les mots de passe ne correspondent pas'));
        exit();
    }
    
    // Vérifier la longueur du mot de passe
    if (strlen($password) < 6) {
        header('Location: register.php?error=' . urlencode('Le mot de passe doit contenir au moins 6 caractères'));
        exit();
    }
    
    // Vérifier le format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: register.php?error=' . urlencode('Email invalide'));
        exit();
    }
    
    // Tentative d'inscription
    $result = $auth->register($username, $email, $password);
    
    if ($result['success']) {
        header('Location: login.php?success=' . urlencode('Inscription réussie ! Vous pouvez maintenant vous connecter'));
        exit();
    } else {
        header('Location: register.php?error=' . urlencode($result['message']));
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>