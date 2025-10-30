<?php
// =================================================================
// DEBUG SCRIPT - PRODUCT STORE ISSUE
// =================================================================
// simpan file ini di: src/public/debug-product-store.php
// akses via: http://localhost/debug-product-store.php
// =================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// autoloader
require_once __DIR__ . '/../app/Core/Autoloader.php';
$autoloader = Autoloader::getInstance()->register();

// load database configuration
require_once __DIR__ . '/../config/database.php';

echo "<h1>DEBUG PRODUCT STORE ISSUE</h1>";
echo "<hr>";

// =================================================================
// TEST 1: Database Connection
// =================================================================
echo "<h2>TEST 1: Database Connection</h2>";
try {
    $db = Core\Database::getInstance()->getConnection();
    echo "✅ Database connection SUCCESS<br>";
    echo "Database type: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection FAILED: " . $e->getMessage() . "<br>";
    die();
}
echo "<hr>";

// =================================================================
// TEST 2: Session Check
// =================================================================
echo "<h2>TEST 2: Session Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
    $userId = $_SESSION['user_id'];
} else {
    echo "❌ User NOT logged in<br>";
    echo "Please login first and try again<br>";
    die();
}
echo "<hr>";

// =================================================================
// TEST 3: Store Check
// =================================================================
echo "<h2>TEST 3: Store Check</h2>";
$storeModel = new Model\Store($db);
$store = $storeModel->findByUserId($userId);

if ($store) {
    echo "✅ Store found<br>";
    echo "Store ID: " . $store['store_id'] . "<br>";
    echo "Store Name: " . $store['store_name'] . "<br>";
    $storeId = $store['store_id'];
} else {
    echo "❌ Store NOT found for this user<br>";
    die();
}
echo "<hr>";

// =================================================================
// TEST 4: Categories Check
// =================================================================
echo "<h2>TEST 4: Categories Check</h2>";
$categoryModel = new Model\Category($db);
$categories = $categoryModel->all();

if (!empty($categories)) {
    echo "✅ Categories found: " . count($categories) . "<br>";
    foreach ($categories as $cat) {
        echo "- ID: {$cat['category_id']}, Name: {$cat['name']}<br>";
    }
    $testCategoryId = $categories[0]['category_id'];
} else {
    echo "❌ No categories found<br>";
    echo "Please run database seeder first<br>";
    die();
}
echo "<hr>";

// =================================================================
// TEST 5: Direct Product Insert Test
// =================================================================
echo "<h2>TEST 5: Direct Product Insert Test</h2>";
$productModel = new Model\Product($db);

// test data
$testProductData = [
    'store_id' => $storeId,
    'product_name' => 'TEST PRODUCT - ' . date('Y-m-d H:i:s'),
    'description' => 'This is a test product description for debugging purposes',
    'price' => 10000,
    'stock' => 100,
    'main_image_path' => null
];

echo "<strong>Attempting to create product with data:</strong><br>";
echo "<pre>" . print_r($testProductData, true) . "</pre>";

try {
    $newProduct = $productModel->create($testProductData);
    
    if ($newProduct) {
        echo "✅ Product created SUCCESSFULLY<br>";
        echo "Product ID: " . $newProduct['product_id'] . "<br>";
        echo "Product Name: " . $newProduct['product_name'] . "<br>";
        
        $testProductId = $newProduct['product_id'];
        
        // test add category
        echo "<br><strong>Testing add category...</strong><br>";
        $categoryAdded = $productModel->addCategory($testProductId, $testCategoryId);
        
        if ($categoryAdded) {
            echo "✅ Category added successfully<br>";
        } else {
            echo "❌ Failed to add category<br>";
        }
        
    } else {
        echo "❌ Product creation returned NULL<br>";
        echo "This means the insert query executed but returned no result<br>";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION during product creation:<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// =================================================================
// TEST 6: Verify Product in Database
// =================================================================
echo "<h2>TEST 6: Verify Product in Database</h2>";
if (isset($testProductId)) {
    $sql = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':product_id' => $testProductId]);
    $verifyProduct = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verifyProduct) {
        echo "✅ Product verified in database<br>";
        echo "<pre>" . print_r($verifyProduct, true) . "</pre>";
    } else {
        echo "❌ Product NOT found in database after creation<br>";
    }
    
    // check category_items
    $sql = "SELECT * FROM category_items WHERE product_id = :product_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':product_id' => $testProductId]);
    $categoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($categoryItems)) {
        echo "✅ Category link found in database<br>";
        echo "<pre>" . print_r($categoryItems, true) . "</pre>";
    } else {
        echo "❌ Category link NOT found in database<br>";
    }
}
echo "<hr>";

// =================================================================
// TEST 7: CSRF Token Test
// =================================================================
echo "<h2>TEST 7: CSRF Token Test</h2>";
$csrfService = new Service\CSRFService();
$token = $csrfService->getToken();
echo "✅ CSRF Token generated: " . substr($token, 0, 20) . "...<br>";
echo "Session CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "<br>";

// test verify
$verifyTest = $csrfService->verify($token);
echo "Token verify test: " . ($verifyTest ? "✅ VALID" : "❌ INVALID") . "<br>";
echo "<hr>";

// =================================================================
// TEST 8: Controller Test Simulation
// =================================================================
echo "<h2>TEST 8: Controller Test Simulation</h2>";
echo "<p>Simulating ProductController::store() method...</p>";

// simulate POST data
$_POST = [
    'product_name' => 'Controller Test Product',
    'description' => 'Test description from controller simulation',
    'price' => 15000,
    'stock' => 50,
    'category_ids' => [$testCategoryId],
    'csrf_token' => $token
];

echo "POST data:<br>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

try {
    $categoryModel = new Model\Category($db);
    $storeModel = new Model\Store($db);
    $productModel = new Model\Product($db);
    
    $controller = new Controller\ProductController($productModel, $categoryModel, $storeModel);
    
    // call store method
    ob_start();
    $controller->store();
    $output = ob_get_clean();
    
    echo "Controller output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // try to parse as JSON
    $response = json_decode($output, true);
    if ($response) {
        if ($response['success'] ?? false) {
            echo "✅ Controller returned SUCCESS<br>";
        } else {
            echo "❌ Controller returned ERROR: " . ($response['error'] ?? 'Unknown') . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception in controller: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "<hr>";

// =================================================================
// TEST 9: Raw SQL Insert Test
// =================================================================
echo "<h2>TEST 9: Raw SQL Insert Test</h2>";
echo "Testing direct SQL INSERT...<br>";

try {
    $sql = "INSERT INTO products (store_id, product_name, description, price, stock, main_image_path) 
            VALUES (:store_id, :product_name, :description, :price, :stock, :main_image_path) 
            RETURNING *";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':store_id' => $storeId,
        ':product_name' => 'RAW SQL TEST - ' . time(),
        ':description' => 'Raw SQL insert test',
        ':price' => 20000,
        ':stock' => 75,
        ':main_image_path' => null
    ]);
    
    if ($result) {
        $rawProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rawProduct) {
            echo "✅ Raw SQL insert SUCCESSFUL<br>";
            echo "Product ID: " . $rawProduct['product_id'] . "<br>";
        } else {
            echo "❌ Raw SQL execute succeeded but no data returned<br>";
        }
    } else {
        echo "❌ Raw SQL insert FAILED<br>";
        $errorInfo = $stmt->errorInfo();
        echo "SQL Error: " . print_r($errorInfo, true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception in raw SQL: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// =================================================================
// TEST 10: Check All Products
// =================================================================
echo "<h2>TEST 10: Current Products in Database</h2>";
$sql = "SELECT product_id, product_name, store_id, created_at FROM products ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($sql);
$stmt->execute();
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total products in database: " . count($allProducts) . "<br>";
if (!empty($allProducts)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Store ID</th><th>Created At</th></tr>";
    foreach ($allProducts as $p) {
        echo "<tr>";
        echo "<td>{$p['product_id']}</td>";
        echo "<td>{$p['product_name']}</td>";
        echo "<td>{$p['store_id']}</td>";
        echo "<td>{$p['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "<hr>";

// =================================================================
// SUMMARY
// =================================================================
echo "<h2>DEBUGGING SUMMARY</h2>";
echo "<p>If all tests passed above, the issue is likely in:</p>";
echo "<ul>";
echo "<li>CSRF middleware blocking the request</li>";
echo "<li>JavaScript not sending data correctly</li>";
echo "<li>Route not matching correctly</li>";
echo "</ul>";

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Check browser console for errors</li>";
echo "<li>Check Network tab to see if request reaches server</li>";
echo "<li>Check PHP error logs</li>";
echo "<li>Try form submission and check what happens</li>";
echo "</ol>";
?>