<?php
/**
 * Database Configuration for R-SEC Academy
 */

class Database {
    private $host = "localhost";
    private $db_name = "rsec_academy";
    private $username = "root";  // Change this in production
    private $password = "";      // Change this in production
    private $charset = "utf8mb4";
    
    public $conn = null;
    
    public function getConnection() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            die("Database connection failed: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
    
    /**
     * Check if connection is active
     */
    public function isConnected() {
        return $this->conn !== null;
    }
    
    /**
     * Get database info for debugging
     */
    public function getInfo() {
        if ($this->conn) {
            return [
                'host' => $this->host,
                'database' => $this->db_name,
                'charset' => $this->charset,
                'version' => $this->conn->query('SELECT VERSION()')->fetchColumn()
            ];
        }
        return null;
    }
}
?>