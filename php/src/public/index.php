<?php

// start session
session_start();

// autoloader
require_once __DIR__ . '/../app/Core/Autoloader.php';
(new \Core\Autoloader())->register();

// buat container untuk dependency injection
$container = new \Core\Container();

// Binding Database (PDO)
$container->set('Database', function() {
    try {
        $config = require __DIR__ . '/../config/database.php';
        
        $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        $pdo = new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
        
        return $pdo;
    } catch (\PDOException $e) {
        throw new \Exception('Database connection failed: ' . $e->getMessage());
    }
});

// Binding Services
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

$container->set('FileService', function() {
    return new Service\FileService();
});

$container->set('AdvancedSearchService', function($c) {
    return new Service\AdvancedSearchService($c->get('Database'));
});

$container->set('CSVExportService', function() {
    return new Service\CSVExportService();
});

$container->set('CartService', function($c) {
    return new Service\CartService($c->get('CartItem'));
});

$container->set('OrderService', function($c) {
    return new Service\OrderService(
        $c->get('Order'),
        $c->get('OrderItem'),
        $c->get('User'),
        $c->get('Product')
    );
});

// Binding Models
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

$container->set('CartItem', function($c) {
    return new Model\CartItem($c->get('Database'));
});

$container->set('Order', function($c) {
    return new Model\Order($c->get('Database'));
});

$container->set('OrderItem', function($c) {
    return new Model\OrderItem($c->get('Database'));
});

// Binding Validators
$container->set('UserValidator', function($c) {
    return new Validator\UserValidator($c->get('User'));
});

$container->set('StoreValidator', function($c) {
    return new Validator\StoreValidator($c->get('Store'));
});

$container->set('ProductValidator', function($c) {
    return new Validator\ProductValidator();
});

// Binding Middleware
$container->set('AuthMiddleware', function($c) {
    return new Middleware\AuthMiddleware(
        $c->get('AuthService'),
        $c->get('CSRFService'),
        $c->get('RateLimitService')
    );
});

// Binding Controllers
$container->set('Controller\AuthController', function($c) {
    return new Controller\AuthController(
        $c->get('User'),
        $c->get('AuthService'),
        $c->get('UserValidator'),
        $c->get('CSRFService'),
        $c->get('LoggerService'),
        $c->get('Store'),
        $c->get('FileService')
    );
});

$container->set('Controller\ProductDiscoveryController', function($c) {
    return new Controller\ProductDiscoveryController(
        $c->get('Product'),
        $c->get('Category'),
        $c->get('AuthService'),
        $c->get('CSRFService')
    );
});

$container->set('Controller\StoreController', function($c) {
    return new \Controller\StoreController(
        $c->get('Store'),
        $c->get('Product'),
        $c->get('Category'),
        $c->get('AuthService'),
        $c->get('CSRFService')
    );
});

$container->set('Controller\CartItemController', function($c) {
    return new \Controller\CartItemController(
        $c->get('AuthService'),
        $c->get('CartService'),
        $c->get('Product'),
        $c->get('CSRFService'),
        $c->get('LoggerService')
    );
});

$container->set('Controller\CheckoutController', function($c) {
    return new Controller\CheckoutController(
        $c->get('User'),
        $c->get('AuthService'),
        $c->get('OrderService'),
        $c->get('CartService'),
        $c->get('CSRFService'),
        $c->get('LoggerService')
    );
});

$container->set('Controller\OrderController', function($c) {
    return new \Controller\OrderController(
        $c->get('Order'),
        $c->get('AuthService'),
        $c->get('OrderService'),
        $c->get('CSRFService'),
        $c->get('LoggerService')
    );
});

$container->set('Controller\StoreController', function($c) {
    return new \Controller\StoreController(
        $c->get('Store'),
        $c->get('Product'),
        $c->get('Category'),
        $c->get('AuthService'),
        $c->get('CSRFService'),
        $c->get('StoreValidator'),
        $c->get('Order'),
        $c->get('FileService'),
        $c->get('LoggerService')
    );
});

$container->set('Controller\ProductController', function($c) {
    return new \Controller\ProductController(
        $c->get('Product'),
        $c->get('Category'),
        $c->get('Store'),
        $c->get('AuthService'),
        $c->get('Order'),
        $c->get('CSRFService'),
        $c->get('LoggerService'),
        $c->get('FileService'),
        $c->get('ProductValidator'),
        $c->get('AdvancedSearchService'),
        $c->get('CSVExportService')
    );
});

$container->set('Controller\SellerOrderController', function($c) {
    return new \Controller\SellerOrderController(
        $c->get('Order'),
        $c->get('Store'),
        $c->get('AuthService'),
        $c->get('OrderService'),
        $c->get('CSRFService'),
        $c->get('LoggerService'),
        $c->get('CSVExportService')
    );
});

$container->set('Controller\BalanceController', function($c) {
    return new \Controller\BalanceController(
        $c->get('User'),
        $c->get('AuthService'),
        $c->get('CSRFService'),
        $c->get('LoggerService')
    );
});

// Binding Router
$router = new \Core\Router($container);

// Load Routes
require_once __DIR__ . '/../route/web.php';

// Dispatch Request
$router->dispatch();