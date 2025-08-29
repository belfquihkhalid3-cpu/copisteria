<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'copisteria_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
        } catch(PDOException $e) {
            die("Erreur de connexion");
        }
        return $this->conn;
    }
}
