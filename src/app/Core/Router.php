<?php

namespace Core;

use Exception;

class Router
{
    private static $instance = null;
    private $routes = [];
    private $currentRoute = null;
    private $container;

    // constructor menerima container untuk dependency injection
    private function __construct($container = null) {
        $this->container = $container;
    }

    // singleton pattern dengan container support
    public static function getInstance($container = null) {
        if (self::$instance === null) {
            self::$instance = new self($container);
        } else if ($container !== null && self::$instance->container === null) {
            self::$instance->container = $container;
        }
        return self::$instance;
    }

    // register GET route
    public function get($uri, $handler) {
        $this->currentRoute = [
            'method' => 'GET',
            'uri' => $uri,
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    // register POST route
    public function post($uri, $handler) {
        $this->currentRoute = [
            'method' => 'POST',
            'uri' => $uri,
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    // register PUT route
    public function put($uri, $handler) {
        $this->currentRoute = [
            'method' => 'PUT',
            'uri' => $uri,
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    // register PATCH route
    public function patch($uri, $handler) {
        $this->currentRoute = [
            'method' => 'PATCH',
            'uri' => $uri,
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    // register DELETE route
    public function delete($uri, $handler) {
        $this->currentRoute = [
            'method' => 'DELETE',
            'uri' => $uri,
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->routes[] = $this->currentRoute;
        return $this;
    }

    // tambahkan middleware ke route terakhir yang didefinisikan
    public function middleware($middlewares) {
        if ($this->currentRoute === null) {
            throw new Exception('Tidak ada route yang didefinisikan untuk middleware');
        }
        
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        
        // update middleware di route terakhir
        $lastIndex = count($this->routes) - 1;
        $this->routes[$lastIndex]['middlewares'] = array_merge(
            $this->routes[$lastIndex]['middlewares'],
            $middlewares
        );
        
        return $this;
    }

    // dispatch request ke handler yang sesuai
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $this->getRequestPath();

        // cari route yang cocok
        foreach ($this->routes as $route) {
            $pattern = $this->convertUriToRegex($route['uri']);
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                // ekstrak parameter dari URI
                array_shift($matches);
                $_GET = array_merge($_GET, $matches);
                
                // jalankan middleware
                if (!empty($route['middlewares'])) {
                    $middlewareResult = $this->runMiddlewares($route['middlewares'], $route['handler']);
                    if ($middlewareResult === false) {
                        return;
                    }
                }
                
                // jalankan handler
                $this->handleRequest($route['handler']);
                return;
            }
        }

        // route tidak ditemukan
        $this->handleNotFound();
    }

    // konversi URI pattern ke regex
    private function convertUriToRegex($uri) {
        $uri = trim($uri, '/');
        // ubah {param} menjadi named capture group
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    // dapatkan request path
    private function getRequestPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $this->normalizePath($path);
    }

    // normalisasi path
    private function normalizePath($path) {
        $path = trim($path, '/');
        return $path === '' ? '' : $path;
    }

    // jalankan middlewares
    private function runMiddlewares($middlewares, $handler) {
        // buat middleware pipeline
        $next = function() use ($handler) {
            return true; // lanjutkan ke handler
        };

        // jalankan middleware dari belakang
        foreach (array_reverse($middlewares) as $middleware) {
            $next = $this->createMiddlewareLayer($middleware, $next);
        }

        // eksekusi pipeline
        return $next();
    }

    // buat layer middleware
    private function createMiddlewareLayer($middlewareName, $next) {
        return function() use ($middlewareName, $next) {
            // pisahkan nama middleware dan parameter
            $parts = explode(':', $middlewareName);
            $middlewareKey = $parts[0];
            $params = array_slice($parts, 1);

            // dapatkan middleware instance dari container
            if ($this->container === null) {
                throw new Exception('Container tidak tersedia untuk middleware');
            }

            $middlewareInstance = $this->container->get('AuthMiddleware');
            
            // mapping nama middleware ke method
            $middlewareMap = [
                'auth' => 'handleAuth',
                'guest' => 'handleGuest',
                'seller' => 'handleSeller',
                'buyer' => 'handleBuyer',
                'csrf' => 'handleCSRF',
                'rate-limit' => 'handleRateLimit',
                'security-headers' => 'handleSecurityHeaders'
            ];
            
            $handlerMethod = $middlewareMap[$middlewareKey] ?? null;
            
            if ($handlerMethod === null || !method_exists($middlewareInstance, $handlerMethod)) {
                throw new Exception("Middleware handler {$middlewareKey} tidak ditemukan");
            }

            // jalankan middleware dengan parameter jika ada
            if ($middlewareKey === 'rate-limit') {
                // rate-limit mengembalikan closure
                $middlewareHandler = $middlewareInstance->$handlerMethod(...$params);
                return $middlewareHandler($next);
            } else {
                // middleware lain langsung menerima $next
                return $middlewareInstance->$handlerMethod($next);
            }
        };
    }

    // handle request dengan controller dan method
    private function handleRequest($handler) {
        if (is_callable($handler)) {
            // handler adalah closure/function
            call_user_func($handler);
            return;
        }

        if (is_string($handler)) {
            // handler adalah string "Controller@method"
            list($controllerName, $method) = explode('@', $handler);
            
            // tambahkan namespace jika belum ada
            if (strpos($controllerName, '\\') === false) {
                $controllerName = 'Controller\\' . $controllerName;
            }
            
            // WAJIB gunakan container untuk dependency injection
            if ($this->container === null) {
                throw new Exception("Container tidak tersedia. Controller {$controllerName} membutuhkan dependency injection.");
            }
            
            // dapatkan controller instance dari container
            try {
                $controller = $this->container->get($controllerName);
            } catch (Exception $e) {
                // error jika controller tidak terdaftar di container
                throw new Exception("Controller {$controllerName} tidak terdaftar di container. Error: {$e->getMessage()}");
            }
            
            // panggil method
            if (!method_exists($controller, $method)) {
                throw new Exception("Method {$method} tidak ditemukan di {$controllerName}");
            }
            
            call_user_func([$controller, $method]);
            return;
        }

        throw new Exception('Handler tidak valid: ' . print_r($handler, true));
    }

    // handle 404 not found
    private function handleNotFound() {
        http_response_code(404);
        // TODO: buat halaman khusus 404
        echo "<h1>404 Not Found</h1>";
        echo "<p>Halaman yang Anda cari tidak ditemukan.</p>";
        exit;
    }

    // debug: tampilkan semua route
    public function debugRoutes() {
        echo "<h2>Registered Routes:</h2>";
        echo "<pre>";
        foreach ($this->routes as $route) {
            echo "{$route['method']} {$route['uri']}";
            if (!empty($route['middlewares'])) {
                echo " [" . implode(', ', $route['middlewares']) . "]";
            }
            echo "\n";
        }
        echo "</pre>";
    }
}