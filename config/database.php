<?php 
 // configuration pour la connexion à la base de données
 class Database {
     private $host = "localhost";
     private $db_name = "file_manager";
     private $username = 'root';
     private $password = '';
     private $charset = 'utf8mb4';
     private $pdo;
     private $options = [
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false,
     ];

     public function getConnection() {
         try {
             $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
             $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
             return $this->pdo;
         } catch (PDOException $e) {
             // En production, logger l'erreur au lieu de l'afficher
             die("Erreur de connexion à la base de données: " . $e->getMessage());
         }
     }

     //Méthode utilitaire pour tester la connexion
     public function testConnection() {
         try {
             $conn = $this->getConnection();
             echo "✅ Connexion à la base de données réussie!";
             return true;
         } catch (Exception $e) {
             echo "❌ Échec de la connexion à la base de données: " . $e->getMessage();
             return false;
         }
     }
 }
/*
 $db = new Database();
 $db->testConnection();
*/
?>