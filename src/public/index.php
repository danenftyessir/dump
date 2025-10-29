<?php

// Start Session
session_start();

// Autoloader
require_once __DIR__ . '/../app/Core/Autoloader.php';
(new \Core\Autoloader())->register();

// Make Container
$container = new \Core\Container();

// Make Binding for Database
$container->set('Database', function() {
    $config = require __DIR__ . '/../config/database.php';

    $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    try {
        $pdo = new PDO ($dsn, $config['username'], $config['password'], $config['options']);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
});

// Make Binding for Services
$container->set('AuthService', function() {
    return new \Service\AuthService();
});

$container->set('CSRFService', function() {
    return new \Service\CSRFService();
});

$container->set('RateLimitService', function() {
    return new \Service\RateLimitService();
});

$container->set('LoggerService', function() {
    return new \Service\LoggerService();
});

// Make Binding for Models & Validators
$container->set('User', function($c) {
    return new \Model\User($c->get('Database'));
});

$container->set('UserValidator', function($c) {
    return new \Validator\UserValidator($c->get('User'));
});

// Make Binding for Middleware
$container->set('AuthMiddleware', function($c) {
    return new \Middleware\AuthMiddleware(
        $c->get('AuthService'),
        $c->get('CSRFService'),
        $c->get('RateLimitService')
    );
});


// Make Binding for Controllers
$container->set('AuthController', function($c) {
    return new \Controller\AuthController(
        $c->get('User'),
        $c->get('AuthService'),
        $c->get('UserValidator'),
        $c->get('CSRFService'),
        $c->get('LoggerService')
    );
});

// Make instance Router and inject Container
$router = new \Core\Router($container);

// Load all route definitions from separate file
require_once __DIR__ . '/../route/web.php';

// Run the Router
$router->dispatch();