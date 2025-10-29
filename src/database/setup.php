<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
Autoloader::getInstance()->register();

// load database configuration
require_once __DIR__ . '/../config/database.php';

// cek apakah dijalankan dari CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>Access Denied</h1>";
    echo "<p>Script ini hanya dapat dijalankan melalui command line.</p>";
    echo "</body></html>";
    exit(1);
}

$newline = "\n";

try {
    // test database connection
    echo "--- Testing Database Connection ---" . $newline;
    
    // gunakan Core\Database dengan namespace
    $db = Core\Database::getInstance()->getConnection();
    
    echo "Database Connection Successful" . $newline;
    
    // check existing tables
    echo $newline . "--- Checking Existing Tables ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // kondisi jika ada existing tables
    if (count($existingTables) > 0) {
        echo "Found existing tables: " . implode(', ', $existingTables) . $newline;
        
        // konfirmasi drop tables
        echo "Do you want to drop and recreate all tables? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        // langsung keluar jika tidak
        if (strtolower($input) !== 'y') {
            echo "Setup cancelled." . $newline;
            exit(0);
        }
        
        // drop existing tables
        echo "Dropping existing tables..." . $newline;
        $db->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
        echo "All tables dropped successfully" . $newline;
    } else {
        echo "No existing tables found" . $newline;
    }
    
    // create database schema
    echo $newline . "--- Creating Database Schema ---" . $newline;
    $initSqlPath = __DIR__ . '/init.sql';
    
    // validasi file init.sql
    if (!file_exists($initSqlPath)) {
        throw new Exception("init.sql file not found at: " . $initSqlPath);
    }
    
    // validasi isi file init.sql
    $initSql = file_get_contents($initSqlPath);
    if (empty($initSql)) {
        throw new Exception("init.sql file is empty");
    }
    
    // create tables
    $db->exec($initSql);
    echo "Database schema created successfully" . $newline;
    
    // verify tables created
    echo $newline . "--- Verifying Tables Created ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = ['users', 'stores', 'products', 'cart_items', 'categories', 'category_items', 'orders', 'order_items'];
    
    echo "Created tables (" . count($tables) . "): " . implode(', ', $tables) . $newline;
    
    // cek missing tables
    $missingTables = array_diff($expectedTables, $tables);
    if (!empty($missingTables)) {
        throw new Exception("Missing tables: " . implode(', ', $missingTables));
    }
    
    // seeding category data
    echo $newline . "--- Seeding Category Data ---" . $newline;
    $seedSqlPath = __DIR__ . '/seed/categories.sql';
    
    // validasi file categories.sql
    if (!file_exists($seedSqlPath)) {
        echo "Warning: categories.sql file not found at: " . $seedSqlPath . $newline;
        echo "Skipping category seeding..." . $newline;
    } else {
        // validasi isi file categories.sql
        $seedSql = file_get_contents($seedSqlPath);
        if (empty($seedSql)) {
            echo "Warning: categories.sql file is empty" . $newline;
            echo "Skipping category seeding..." . $newline;
        } else {
            // insert seed data
            $db->exec($seedSql);
            echo "Category seed data inserted successfully" . $newline;
            
            // verify categories inserted
            echo $newline . "--- Verifying Categories Data ---" . $newline;
            $stmt = $db->query("SELECT COUNT(*) FROM categories");
            $categoryCount = $stmt->fetchColumn();
            echo "Total categories: " . $categoryCount . $newline;
            
            // list all categories
            if ($categoryCount > 0) {
                $stmt = $db->query("SELECT name FROM categories ORDER BY category_id");
                $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "Categories: " . implode(', ', $categories) . $newline;
            }
        }
    }
    
    echo $newline . "--- Database Setup Completed Successfully ---" . $newline;
    echo $newline . "Anda sekarang dapat menjalankan aplikasi." . $newline;
    
} catch (PDOException $e) {
    echo $newline . "Database Error: " . $e->getMessage() . $newline;
    echo "Stack trace:" . $newline . $e->getTraceAsString() . $newline;
    exit(1);
} catch (Exception $e) {
    echo $newline . "Error during setup: " . $e->getMessage() . $newline;
    echo "Stack trace:" . $newline . $e->getTraceAsString() . $newline;
    exit(1);
}