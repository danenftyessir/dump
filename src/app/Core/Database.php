<?php

namespace Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static $instance = null;
    private $connection;

    // constructor private untuk singleton
    private function __construct() {
        $this->connect();
    }

    // singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // connect ke database
    private function connect() {
        try {
            // load config
            $configPath = __DIR__ . '/../../config/database.php';
            if (!file_exists($configPath)) {
                throw new Exception("Database config file not found at: {$configPath}");
            }
            
            $config = require $configPath;
            
            // buat DSN
            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            
            // buat connection
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    // get connection
    public function getConnection() {
        return $this->connection;
    }

    // prevent clone
    private function __clone() {}

    // prevent unserialize
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}