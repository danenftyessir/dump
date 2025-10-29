<?php
// enable error reporting untuk development
// TO DO disable di production
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/app/Core/Autoloader.php';

// register autoloader
$autoloader = Autoloader::getInstance()->register();

// load database configuration
require_once __DIR__ . '/config/database.php';

// load routes configuration
require_once __DIR__ . '/routes.php';

// ga perlu dispatch lagi di sini