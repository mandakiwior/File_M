<?php
// index.php
session_start();

// Si déjà connecté, rediriger vers dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lun'Drive - Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="hero-section">
        <div class="content">
            <h1>LUN'DRIVE</h1>
            <p>Une nouvelle façon de gérer vos fichiers, plus <strong>rapide</strong>, plus <strong>intelligente</strong> et en toute <strong>simplicité</strong></p>
            <a href="login.php" class="btn-connexion">CONNEXION</a>
        </div>
    </div>
</body>
</html>