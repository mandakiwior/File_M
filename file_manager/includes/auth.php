<?php
// includes/auth.php
require_once 'config/database.php';

class Auth {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    // Inscription
    public function register($username, $email, $password) {
        try {
            // Vérifier si le nom d'utilisateur existe déjà
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'];
            }
            
            // Hacher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer le nouvel utilisateur
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Créer le dossier personnel de l'utilisateur dans uploads/
            $userFolder = 'uploads/user_' . $userId;
            if (!file_exists($userFolder)) {
                mkdir($userFolder, 0777, true);
            }
            
            return ['success' => true, 'message' => 'Inscription réussie !'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }
    
    // Connexion (par email ou username)
    public function login($identifier, $password) {
        try {
            // Chercher par email ou username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$identifier, $identifier]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                return ['success' => true, 'message' => 'Connexion réussie !'];
            }
            
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    
    // Déconnexion
    public function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }
    
    // Récupérer l'utilisateur courant
    public function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }
        return null;
    }
}
?>