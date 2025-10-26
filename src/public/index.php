<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$_SESSION['user'] = [
    'user_id' => 1,
    'role' => 'SELLER',
    'name' => 'Test Seller',
    'email' => 'seller@test.com'
];

require_once __DIR__ . '/../app/Core/Autoloader.php';
$autoloader = Autoloader::getInstance()->register();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../routes.php';