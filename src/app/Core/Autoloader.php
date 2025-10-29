<?php

class Autoloader
{
    private static $instance = null;
    private $appPath;
    private $namespaces = [];

    // constructor
    private function __construct() {
        $this->appPath = __DIR__ . '/..';
        
        // register namespaces
        $this->addNamespace('Core\\', $this->appPath . '/Core');
        $this->addNamespace('Base\\', $this->appPath . '/Base');
        $this->addNamespace('Controller\\', $this->appPath . '/Controller');
        $this->addNamespace('Model\\', $this->appPath . '/Model');
        $this->addNamespace('Middleware\\', $this->appPath . '/Middleware');
        $this->addNamespace('Service\\', $this->appPath . '/Service');
        $this->addNamespace('Validator\\', $this->appPath . '/Validator');
        $this->addNamespace('Exception\\', $this->appPath . '/Exception');
    }

    // singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // register autoloader
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
        return $this;
    }

    // tambah namespace
    public function addNamespace($namespace, $path) {
        $namespace = trim($namespace, '\\') . '\\';
        $this->namespaces[$namespace] = rtrim($path, '/\\');
        return $this;
    }

    // load class berdasarkan namespace
    public function loadClass($className) {
        // cek kelas dengan namespace
        foreach ($this->namespaces as $namespace => $path) {
            if (strpos($className, $namespace) === 0) {
                $relativeClass = substr($className, strlen($namespace));
                $file = $path . '/' . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }

        // cari kelas tanpa namespace (fallback untuk backward compatibility)
        $possiblePaths = [
            $this->appPath . '/Core/' . $className . '.php',
            $this->appPath . '/Base/' . $className . '.php',
            $this->appPath . '/Controller/' . $className . '.php',
            $this->appPath . '/Model/' . $className . '.php',
            $this->appPath . '/Middleware/' . $className . '.php',
            $this->appPath . '/Service/' . $className . '.php',
            $this->appPath . '/Validator/' . $className . '.php',
        ];

        // load file
        foreach ($possiblePaths as $file) {
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }

    // get app path
    public function getAppPath() {
        return $this->appPath;
    }

    // get namespaces
    public function getNamespaces() {
        return $this->namespaces;
    }
}