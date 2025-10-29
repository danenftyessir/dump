<?php
// Load Database Configuration
require_once __DIR__ . '/../config/database.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>Access Denied</h1>";
    echo "</body></html>";
    exit(1);
}

$newline = "\n";

try {
    // Test Database Connection
    echo "--- Testing Database Connection ---" . $newline;
    $db = Database::getInstance()->getConnection();
    echo "Database Connection Successful" . $newline;
    
    // Check Tables
    echo $newline . "--- Checking Existing Tables ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Kondisi jika ada existing tables
    if (count($existingTables) > 0) {
        echo "Found existing tables: " . implode(', ', $existingTables) . $newline;
        
        // Konfirmasi
        echo "Do you want to drop and recreate all tables? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        // Langsung Keluar jika Tidak
        if (strtolower($input) !== 'y') {
            echo "Setup cancelled." . $newline;
            exit(0);
        }
        
        // Drop Existing Tables
        echo "Dropping existing tables" . $newline;
        $db->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
        echo "All Tables Dropped" . $newline;
    } else {
        echo "No Existing Tables Found" . $newline;
    }
    
    // Create Database Schema
    echo $newline . "--- Creating Database Schema ---" . $newline;
    $initSqlPath = __DIR__ . '/init.sql';
    
    // Validasi file init.sql
    if (!file_exists($initSqlPath)) {
        throw new Exception("init.sql file not found at: " . $initSqlPath);
    }
    
    // Validasi isi file init.sql
    $initSql = file_get_contents($initSqlPath);
    if (empty($initSql)) {
        throw new Exception("init.sql file is empty");
    }
    
    // Create Tables
    $db->exec($initSql);
    echo "Database schema created successfully" . $newline;
    
    // Verify Tables Created
    echo $newline . "--- Verifying Tables Created ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = ['users', 'stores', 'products', 'cart_items', 'categories', 'category_items', 'orders', 'order_items'];
    
    echo "Created Tables (" . count($tables) . "): " . implode(', ', $tables) . $newline;
    
    $missingTables = array_diff($expectedTables, $tables);
    if (!empty($missingTables)) {
        throw new Exception("Missing tables: " . implode(', ', $missingTables));
    }
    
    // Seeding Category Data
    echo $newline . "--- Seeding Category Data ---" . $newline;
    $seedSqlPath = __DIR__ . '/seed/categories.sql';
    
    // Validasi file categories.sql
    if (!file_exists($seedSqlPath)) {
        throw new Exception("categories.sql file not found at: " . $seedSqlPath);
    }
    
    // Validasi isi file categories.sql
    $seedSql = file_get_contents($seedSqlPath);
    if (empty($seedSql)) {
        throw new Exception("categories.sql file is empty");
    }
    
    // Insert Seed Data
    $db->exec($seedSql);
    echo "Category seed data inserted" . $newline;
    
    // Verify Categories Inserted
    echo $newline . "--- Verifying Categories Data ---" . $newline;
    $stmt = $db->query("SELECT COUNT(*) FROM categories");
    $categoryCount = $stmt->fetchColumn();
    echo "Total categories: " . $categoryCount . $newline;

    // List All Categories
    $stmt = $db->query("SELECT name FROM categories ORDER BY category_id");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Categories: " . implode(', ', $categories) . $newline;
    
    echo $newline . "--- Database Setup Completed Successfully ---" . $newline;
    
} catch (Exception $e) {
    echo $newline . "Error during setup: " . $e->getMessage() . $newline;
    echo "Stack trace:" . $newline . $e->getTraceAsString() . $newline;
    
    exit(1);
}
?>