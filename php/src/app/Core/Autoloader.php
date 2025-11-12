<?php

namespace Core;

class Autoloader
{
    private $baseDir;

    // Ctor
    public function __construct() {
        $this->baseDir = dirname(__DIR__) . '/';
    }

    // Register the autoloader
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }

    // Load class file based on namespace and class name
    public function loadClass($className) {
        $className = ltrim($className, '\\');
        $classPath = str_replace('\\', '/', $className);
        $filePath = $this->baseDir . $classPath . '.php';

        if (file_exists($filePath)) {
            require $filePath;
        }
    }
}