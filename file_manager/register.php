<?php
// register.php
require_once 'includes/session.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Lun'Drive</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="register-page">
    <div class="register-container">
        <div class="register-card">
            <a href="index.php" class="back-link">← Retour</a>
            <h1>CREATION DE COMPTE<br>LUN'DRIVE</h1>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register_process.php" class="register-form">
                <div class="form-group">
                    <label for="username">Nom</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Entrez votre nom">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="votreemail@exemple.com">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required 
                               placeholder="*********" minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            👁️
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmation de mot de passe</label>
                    <div class="password-container">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="*********">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">Créer compte</button>
            </form>
            
            <p class="login-link">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🙈';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
            }
        }
    </script>
</body>
</html>