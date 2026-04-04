<?php
$host = 'localhost'; // Adresse du serveur de base de données
$dbname = 'ma_formation_db'; // Nom de la base de données
$user = 'root'; // Nom d'utilisateur de la base de données
$password = ''; // Mot de passe de la base de données

try {
    // Connexion à la base de données avec PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    // Configuration des options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Connexion à la base de données réussie !</h1>";
    echo "<p>Vous êtes connecté à la base de données <strong>$dbname</strong> sur le serveur <strong>$host</strong>.</p>";
    
} catch (PDOException $e) {
    // En cas d'erreur de connexion, afficher un message d'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>