<?php
/**
 * Database Configuration
 * Konfigurasi koneksi database MySQL
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'e_spp_system';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

/**
 * Global database connection
 * @return PDO|null
 */
function getDbConnection() {
    static $database = null;
    
    if ($database === null) {
        $database = new Database();
    }
    
    return $database->getConnection();
}