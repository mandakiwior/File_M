<?php
// test_change.php
require_once 'includes/session.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];

$database = new Database();
$pdo = $database->getConnection();

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo "<h2>Test de changement de mot de passe</h2>";
echo "Utilisateur: " . htmlspecialchars($user['username']) . "<br>";
echo "Email: " . htmlspecialchars($user['email']) . "<br>";
echo "<hr>";

echo "<form method='POST'>";
echo "Ancien mot de passe: <input type='password' name='old' required><br><br>";
echo "Nouveau mot de passe: <input type='password' name='new' required><br><br>";
echo "<button type='submit'>Changer</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old'];
    $new = $_POST['new'];
    
    if (strlen($new) < 6) {
        echo "<p style='color:red'>Le mot de passe doit contenir au moins 6 caractères</p>";
    } elseif (!password_verify($old, $user['password'])) {
        echo "<p style='color:red'>Ancien mot de passe incorrect</p>";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$newHash, $userId])) {
            echo "<p style='color:green'>Mot de passe changé avec succès !</p>";
        } else {
            echo "<p style='color:red'>Erreur lors du changement</p>";
        }
    }
}
?>