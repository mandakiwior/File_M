<?php
// test_view.php - Fichier de test minimal
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de visualisation</h1>";
echo "PHP fonctionne correctement<br>";

// Tester l'inclusion des fichiers requis
echo "<h2>Test des inclusions :</h2>";

if (file_exists('includes/session.php')) {
    echo "✅ includes/session.php trouvé<br>";
    require_once 'includes/session.php';
    echo "✅ session.php chargé<br>";
} else {
    echo "❌ includes/session.php non trouvé<br>";
}

if (file_exists('config/database.php')) {
    echo "✅ config/database.php trouvé<br>";
    require_once 'config/database.php';
    echo "✅ database.php chargé<br>";
} else {
    echo "❌ config/database.php non trouvé<br>";
}

// Vérifier la session
echo "<h2>Session :</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Utilisateur connecté : " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ Non connecté<br>";
}

echo "<h2>Test terminé</h2>";
?>