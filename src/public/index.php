<?php

// enable error reporting untuk development
// TODO: disable di production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// start session
session_start();

// autoloader
require_once __DIR__ . '/../app/Core/Autoloader.php';
$autoloader = Autoloader::getInstance()->register();

// load database configuration
require_once __DIR__ . '/../config/database.php';

// buat container untuk dependency injection
$container = new Core\Container();

// ============================================
// BINDING DATABASE
// ============================================
$container->set('Database', function() {
    return Core\Database::getInstance()->getConnection();
});

// ============================================
// BINDING SERVICES
// ============================================
$container->set('AuthService', function() {
    return new Service\AuthService();
});

$container->set('CSRFService', function() {
    return new Service\CSRFService();
});

$container->set('RateLimitService', function() {
    return new Service\RateLimitService();
});

$container->set('LoggerService', function() {
    return new Service\LoggerService();
});

// ============================================
// BINDING MODELS
// ============================================
$container->set('User', function($c) {
    return new Model\User($c->get('Database'));
});

$container->set('Store', function($c) {
    return new Model\Store($c->get('Database'));
});

$container->set('Product', function($c) {
    return new Model\Product($c->get('Database'));
});

$container->set('Category', function($c) {
    return new Model\Category($c->get('Database'));
});

$container->set('Order', function($c) {
    return new Model\Order($c->get('Database'));
});

// ============================================
// BINDING VALIDATORS
// ============================================
$container->set('UserValidator', function($c) {
    return new Validator\UserValidator($c->get('User'));
});

// ============================================
// BINDING MIDDLEWARE
// ============================================
$container->set('AuthMiddleware', function($c) {
    return new Middleware\AuthMiddleware(
        $c->get('AuthService'),
        $c->get('CSRFService'),
        $c->get('RateLimitService')
    );
});

// ============================================
// BINDING CONTROLLERS
// ============================================

// auth controller dengan store model untuk auto-create toko
$container->set('Controller\AuthController', function($c) {
    return new Controller\AuthController(
        $c->get('User'),
        $c->get('AuthService'),
        $c->get('UserValidator'),
        $c->get('CSRFService'),
        $c->get('LoggerService'),
        $c->get('Store')
    );
});

// store controller
$container->set('Controller\StoreController', function($c) {
    return new Controller\StoreController(
        $c->get('Store'),
        $c->get('User'),
        $c->get('Order')
    );
});

// product controller
$container->set('Controller\ProductController', function($c) {
    return new Controller\ProductController(
        $c->get('Product'),
        $c->get('Category'),
        $c->get('Store')
    );
});

// product discovery controller
$container->set('Controller\ProductDiscoveryController', function($c) {
    return new Controller\ProductDiscoveryController(
        $c->get('Product'),
        $c->get('Category'),
        $c->get('Store')
    );
});

// ============================================
// BINDING ROUTER
// ============================================
$container->set('Router', function($c) {
    return Core\Router::getInstance($c);
});

// dapatkan router instance
$router = $container->get('Router');

// ============================================
// LOAD ROUTE DEFINITIONS
// ============================================

// cek apakah web.php ada (auth routes)
$webRoutePath = __DIR__ . '/../../route/web.php';
if (file_exists($webRoutePath)) {
    require_once $webRoutePath;
} else {
    // fallback jika struktur berbeda
    $webRoutePath = __DIR__ . '/../route/web.php';
    if (file_exists($webRoutePath)) {
        require_once $webRoutePath;
    }
}

// load seller & public routes
require_once __DIR__ . '/../routes.php';

// ============================================
// DISPATCH ROUTER
// ============================================
$router->dispatch();