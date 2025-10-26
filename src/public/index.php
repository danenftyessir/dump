<?php
// entry point aplikasi
require_once __DIR__ . '/../app/Base/Controller.php';
require_once __DIR__ . '/../app/Base/Model.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../config/database.php';

// autoload models dan controllers
spl_autoload_register(function ($class) {
    $modelPath = __DIR__ . '/../app/Model/' . $class . '.php';
    if (file_exists($modelPath)) {
        require_once $modelPath;
    }
    
    $controllerPath = __DIR__ . '/../app/Controller/' . $class . '.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    }
});

// load routes
require_once __DIR__ . '/../routes.php';