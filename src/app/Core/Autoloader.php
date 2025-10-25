<?php
class Autoloader
{
    private static $instance = null;
    private $appPath;
    private $namespaces = [];

    // Ctor
    private function __construct() {
        $this->appPath = __DIR__ . '/..';
        $this->addNamespace('Base\\', $this->appPath . '/Base');
        $this->addNamespace('Core\\', $this->appPath . '/Core');
        $this->addNamespace('Controller\\', $this->appPath . '/Controller');
        $this->addNamespace('Model\\', $this->appPath . '/Model');
        $this->addNamespace('Middleware\\', $this->appPath . '/Middleware');
        $this->addNamespace('View\\', $this->appPath . '/View');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register() {
        require_once $this->appPath . '/Base/Controller.php';
        require_once $this->appPath . '/Base/Model.php';

        spl_autoload_register([$this, 'loadClass']);
        return $this;
    }

    public function addNamespace($namespace, $path) {
        $namespace = trim($namespace, '\\') . '\\';
        $this->namespaces[$namespace] = rtrim($path, '/\\');
        return $this;
    }

    public function loadClass($className) {
        // Cek Kelas dengan Namespace
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

        // Cari Kelas Secara Manual
        $possiblePaths = [
            $this->appPath . '/Base/' . $className . '.php',
            $this->appPath . '/Core/' . $className . '.php',
            $this->appPath . '/Controller/' . $className . '.php',
            $this->appPath . '/Model/' . $className . '.php',
            $this->appPath . '/Middleware/' . $className . '.php',
            $this->appPath . '/View/' . $className . '.php',
        ];

        // Load File
        foreach ($possiblePaths as $file) {
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }

    public function getAppPath() {
        return $this->appPath;
    }

    public function getNamespaces() {
        return $this->namespaces;
    }
}