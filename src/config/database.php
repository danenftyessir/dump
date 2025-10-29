<?php
// Menggunakan Singleton Pattern
class Database {
    private static $instance = null;
    private $connection;
    // Database Configuration
    private $host = 'db';
    private $database = 'nimonspedia_db';
    private $username = 'nimonspedia_user'; 
    private $password = 'nimonspedia_password';
    private $port = '5432';
    
    // Ctor
    private function __construct() {
        try {
            // Build Data Source Name
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
            // Buat Koneksi PDO
            $this->connection = new PDO($dsn, $this->username, $this->password);
            // Set Error ke Exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Hasil Query sebagai Array
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT version()");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return "Connection test failed: " . $e->getMessage();
        }
    }
    
    private function __clone() {}
    public function __wakeup() {}
}
?>