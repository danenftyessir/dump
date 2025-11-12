<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "<h1>Access Denied</h1><p>Script ini hanya dapat dijalankan melalui command line.</p>";
    echo "<h1>Access Denied</h1><p>Script ini hanya dapat dijalankan melalui command line.</p>";
    exit(1);
}

$newline = "\n";

try {
    // load config database
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("File config/database.php tidak ditemukan.");
    }
    $config = require $configPath;

    // koneksi ke database
    echo "Menghubungkan ke database '{$config['database']}' di host '{$config['host']}'..." . $newline;
    $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    $db = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    echo "Koneksi Database Berhasil." . $newline;

    // cek tabel yang ada
    echo $newline . "--- Mengecek Tabel yang Ada ---" . $newline;
    // load config database
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("File config/database.php tidak ditemukan.");
    }
    $config = require $configPath;

    // koneksi ke database
    echo "Menghubungkan ke database '{$config['database']}' di host '{$config['host']}'..." . $newline;
    $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    $db = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    echo "Koneksi Database Berhasil." . $newline;

    // cek tabel yang ada
    echo $newline . "--- Mengecek Tabel yang Ada ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);


    if (count($existingTables) > 0) {
        echo "Tabel ditemukan: " . implode(', ', $existingTables) . $newline;
        echo "Warning: Ini akan menghapus semua data yang ada." . $newline;
        echo "Lanjutkan? (ketik 'yes' untuk konfirmasi): ";
        echo "Tabel ditemukan: " . implode(', ', $existingTables) . $newline;
        echo "Warning: Ini akan menghapus semua data yang ada." . $newline;
        echo "Lanjutkan? (ketik 'yes' untuk konfirmasi): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);

        if (strtolower($input) !== 'yes') {
            echo "Setup dibatalkan." . $newline;

        if (strtolower($input) !== 'yes') {
            echo "Setup dibatalkan." . $newline;
            exit(0);
        }

        // drop schema
        echo "Menghapus skema yang ada..." . $newline;

        // drop schema
        echo "Menghapus skema yang ada..." . $newline;
        $db->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
    } else {
        echo "Tidak ada tabel, melanjutkan instalasi..." . $newline;
        echo "Tidak ada tabel, melanjutkan instalasi..." . $newline;
    }

    // buat skema database
    echo $newline . "--- Membuat Skema Database ---" . $newline;

    // buat skema database
    echo $newline . "--- Membuat Skema Database ---" . $newline;
    $initSqlPath = __DIR__ . '/init.sql';
    if (!file_exists($initSqlPath)) throw new Exception("init.sql tidak ditemukan.");
    
    if (!file_exists($initSqlPath)) throw new Exception("init.sql tidak ditemukan.");
    
    $initSql = file_get_contents($initSqlPath);
    if (empty($initSql)) throw new Exception("init.sql kosong.");
    if (empty($initSql)) throw new Exception("init.sql kosong.");
    
    $db->exec($initSql);
    echo "Skema database berhasil dibuat." . $newline;

    // verifikasi tabel
    echo $newline . "--- Memverifikasi Tabel ---" . $newline;
    echo "Skema database berhasil dibuat." . $newline;

    // verifikasi tabel
    echo $newline . "--- Memverifikasi Tabel ---" . $newline;
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['users', 'stores', 'products', 'cart_items', 'categories', 'category_items', 'orders', 'order_items'];
    $missingTables = array_diff($expectedTables, $tables);


    if (!empty($missingTables)) {
        throw new Exception("Tabel berikut GAGAL dibuat: " . implode(', ', $missingTables));
        throw new Exception("Tabel berikut GAGAL dibuat: " . implode(', ', $missingTables));
    }
    echo "Semua tabel berhasil diverifikasi: " . implode(', ', $tables) . $newline;
    echo "Semua tabel berhasil diverifikasi: " . implode(', ', $tables) . $newline;
    
    // seeding kategori
    echo $newline . "--- Seeding Kategori ---" . $newline;
    // seeding kategori
    echo $newline . "--- Seeding Kategori ---" . $newline;
    $seedSqlPath = __DIR__ . '/seed/categories.sql';
    if (file_exists($seedSqlPath)) {
        $seedSql = file_get_contents($seedSqlPath);
        if (!empty($seedSql)) {
            $db->exec($seedSql);
            echo "Data kategori berhasil di-seed." . $newline;
        } else {
            echo "Warning: categories.sql kosong, seeding dilewati." . $newline;
        }
    } else {
        echo "Warning: categories.sql tidak ditemukan, seeding dilewati." . $newline;
    }
    
    echo $newline . "--- Database Setup Selesai ---" . $newline;
} catch (PDOException $e) {
    echo $newline . "[FATAL DATABASE ERROR]: " . $e->getMessage() . $newline;
    exit(1);
} catch (Exception $e) {
    echo $newline . "[FATAL SETUP ERROR]: " . $e->getMessage() . $newline;
    echo $newline . "[FATAL SETUP ERROR]: " . $e->getMessage() . $newline;
    exit(1);
}