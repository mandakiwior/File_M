<?php
$host = 'localhost'; // Adresse du serveur de base de données
$dbname = 'ma_formation_db'; // Nom de la base de données
$user = 'root'; // Nom d'utilisateur de la base de données
$password = ''; // Mot de passe de la base de données

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];

    $requete = $db->prepare("INSERT INTO utilisateurs (nom, email) VALUES (?, ?)");
    $requete->execute([$nom, $email]);

    echo "<p style='color: green;'>Utilisateur enregistré avec succès !</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inscription</title>
</head>
<body>
    <h1>Formulaire d'inscription</h1>
    <h2>Ajouter un utilisateur</h2>
    <form method="POST">
        <label for="nom">Nom :</label><br>
        <input type="text" id="nom" name="nom" required><br><br>

        <label for="email">Email :</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <input type="submit" value="S'inscrire">
    </form>