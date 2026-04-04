<?php
// secure_pin.php - Page de vérification du code PIN
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';

$database = new Database();
$pdo = $database->getConnection();

// Vérifier si l'espace est activé
$stmt = $pdo->prepare("SELECT secure_pin_enabled FROM users WHERE id = ?");
$stmt->execute([$userId]);
$isEnabled = $stmt->fetchColumn();

if (!$isEnabled) {
    header('Location: secure_pin_setup.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    
    $stmt = $pdo->prepare("SELECT secure_pin FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pin, $user['secure_pin'])) {
        $_SESSION['secure_access'] = true;
        $_SESSION['secure_access_time'] = time();
        header('Location: secure_files.php');
        exit();
    } else {
        $error = 'Code PIN incorrect';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès sécurisé - Lun'Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #0a0e27; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif;">
    <div style="max-width: 500px; width: 100%; margin: 2rem auto; padding: 0 1rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #818cf8; margin-bottom: 0.5rem;">🔒 Espace sécurisé</h1>
            <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Vos fichiers sont protégés par un code PIN</p>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 24px; padding: 2rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔐</div>
            <h2 style="font-size: 1.5rem; font-weight: 600; color: white; margin-bottom: 0.5rem;">Accès restreint</h2>
            <p style="color: rgba(255, 255, 255, 0.5); font-size: 0.875rem; margin-bottom: 1.5rem;">Entrez votre code PIN à 6 chiffres</p>
            
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.875rem;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="pinForm">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; text-align: left; color: rgba(255, 255, 255, 0.7); font-size: 0.813rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">CODE PIN</label>
                    <input type="password" name="pin" style="width: 100%; padding: 1rem; font-size: 2rem; letter-spacing: 10px; text-align: center; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; color: white; font-weight: 600;" maxlength="6" pattern="\d{6}" autofocus required>
                </div>
                <button type="submit" style="width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #4f46e5, #818cf8); border: none; border-radius: 12px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer;">🔓 Déverrouiller</button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="files.php?tab=user" style="display: inline-flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.875rem;">← Retour à mes fichiers</a>
        </div>
    </div>
    
    <script>
    document.getElementById('pinForm').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '⏳ Vérification...';
        btn.disabled = true;
    });
    </script>
</body>
</html>