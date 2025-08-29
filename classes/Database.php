<?php
/**
 * Classe Database pour Copisteria Low Cost
 * Gestion de la connexion à la base de données
 */

class Database {
    // Paramètres de connexion
    private $host = 'localhost';
    private $db_name = 'copisteria_db';
    private $username = 'root';
    private $password = '';
    private $conn = null;
    
    /**
     * Obtenir la connexion à la base de données
     * @return PDO|null
     */
    public function getConnection() {
        // Si déjà connecté, retourner la connexion existante
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            // Créer la connexion PDO
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            return $this->conn;
            
        } catch(PDOException $e) {
            // Logger l'erreur
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            
            // En développement, afficher l'erreur
            if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
                die("Erreur de connexion à la base de données: " . $e->getMessage());
            } else {
                die("Erreur de connexion au service. Veuillez réessayer plus tard.");
            }
        }
    }
    
    /**
     * Tester la connexion
     * @return bool
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtenir les informations sur la base de données
     * @return array
     */
    public function getDatabaseInfo() {
        try {
            $conn = $this->getConnection();
            
            // Version MySQL
            $version = $conn->query("SELECT VERSION() as version")->fetch()['version'];
            
            // Nombre de tables
            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // Charset
            $charset = $conn->query("SELECT @@character_set_database as charset")->fetch()['charset'];
            
            return [
                'status' => 'connected',
                'version' => $version,
                'database' => $this->db_name,
                'charset' => $charset,
                'tables' => count($tables),
                'table_list' => $tables
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Fermer la connexion
     */
    public function closeConnection() {
        $this->conn = null;
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        $conn = $this->getConnection();
        return $conn->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit() {
        $conn = $this->getConnection();
        return $conn->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollBack() {
        $conn = $this->getConnection();
        return $conn->rollBack();
    }
    
    /**
     * Exécuter une requête préparée
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function prepare($query, $params = []) {
        $conn = $this->getConnection();
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->execute($params);
        }
        
        return $stmt;
    }
    
    /**
     * Obtenir le dernier ID inséré
     * @return string
     */
    public function lastInsertId() {
        $conn = $this->getConnection();
        return $conn->lastInsertId();
    }
    
    /**
     * Nettoyer et échapper une chaîne (sécurité)
     * @param string $string
     * @return string
     */
    public function escape($string) {
        $conn = $this->getConnection();
        return $conn->quote($string);
    }
}