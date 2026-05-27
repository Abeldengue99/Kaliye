<?php
/**
 * Database Configuration Template (PostgreSQL)
 * 
 * INSTRUÇÕES:
 * 1. Copie este ficheiro para base_dados.php
 * 2. Preencha as credenciais corretas
 * 3. NÃO commite base_dados.php (está no .gitignore)
 */

class Database {
    private $host = "localhost";
    private $port = "5432";
    private $db_name = "aksanti_mentorship";
    private $username = "postgres";
    private $password = "5850";  // Senha padrão configurada
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET client_encoding TO 'UTF8'");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
