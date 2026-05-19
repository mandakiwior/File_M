<?php
// secure_pin_setup.php
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

$database = new Database();
$pdo = $database->getConnection();

// Vérifier si l'espace est déjà activé
$stmt = $pdo->prepare("SELECT secure_pin_enabled FROM users WHERE id = ?");
$stmt->execute([$userId]);
$isEnabled = $stmt->fetchColumn();

if ($isEnabled && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: secure_pin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    $confirm_pin = $_POST['confirm_pin'] ?? '';
    
    if (!preg_match('/^\d{6}$/', $pin)) {
        $error = 'Le code PIN doit contenir exactement 6 chiffres';
    } elseif ($pin !== $confirm_pin) {
        $error = 'Les codes PIN ne correspondent pas';
    } else {
        $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET secure_pin = ?, secure_pin_enabled = TRUE WHERE id = ?");
        $stmt->execute([$hashedPin, $userId]);
        
        $secureDir = 'uploads/secure/user_' . $userId;
        if (!file_exists($secureDir)) {
            mkdir($secureDir, 0777, true);
        }
        
        $success = 'Espace sécurisé activé avec succès !';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activer l'espace sécurisé - Lun'Drive</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0e27;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container { max-width: 500px; width: 100%; margin: 2rem auto; padding: 0 1rem; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { font-size: 2rem; font-weight: 700; color: #818cf8; margin-bottom: 0.5rem; }
        .header p { color: rgba(255, 255, 255, 0.6); font-size: 0.875rem; }
        .card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 24px; padding: 2rem; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1); }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        .card h2 { font-size: 1.5rem; font-weight: 600; color: white; margin-bottom: 0.5rem; }
        .subtitle { color: rgba(255, 255, 255, 0.5); font-size: 0.875rem; margin-bottom: 1.5rem; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; text-align: left; color: rgba(255, 255, 255, 0.7); font-size: 0.813rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .pin-input { width: 100%; padding: 1rem; font-size: 2rem; letter-spacing: 10px; text-align: center; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; color: white; font-weight: 600; }
        .pin-input:focus { outline: none; border-color: #818cf8; background: rgba(255, 255, 255, 0.15); }
        .btn { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #4f46e5, #818cf8); border: none; border-radius: 12px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .btn:hover { transform: translateY(-2px); }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.875rem; margin-top: 2rem; text-align: center; justify-content: center; }
        .back-link:hover { color: #818cf8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Espace sécurisé</h1>
            <p>Protégez vos fichiers sensibles</p>
        </div>
        
        <div class="card">
            <div class="icon">🔑</div>
            <h2>Activation</h2>
            <p class="subtitle">Définissez un code PIN à 6 chiffres</p>
            
            <?php if ($success): ?>
                <div class="alert-success"><?= htmlspecialchars($success) ?></div>
                <a href="secure_pin.php" class="btn">🔓 Accéder à l'espace</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>CODE PIN (6 chiffres)</label>
                        <input type="password" name="pin" class="pin-input" maxlength="6" pattern="\d{6}" required>
                    </div>
                    <div class="form-group">
                        <label>CONFIRMER LE CODE PIN</label>
                        <input type="password" name="confirm_pin" class="pin-input" maxlength="6" pattern="\d{6}" required>
                    </div>
                    <button type="submit" class="btn">✅ Activer l'espace sécurisé</button>
                </form>
            <?php endif; ?>
        </div>
        
        <a href="files.php?tab=user&folder=root" class="back-link">← Retour à mes fichiers</a>
    </div>
</body>
</html>