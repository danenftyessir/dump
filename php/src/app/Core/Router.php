<?php

namespace Core;

use Core\Container;
use Exception;

class Router
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => []
    ];
    private $container;
    private $lastRoute;

    // Ctor
    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function get($uri, $handler) {
        return $this->addRoute('GET', $uri, $handler);
    }

    public function post($uri, $handler) {
        return $this->addRoute('POST', $uri, $handler);
    }

    public function put($uri, $handler) {
        return $this->addRoute('PUT', $uri, $handler);
    }

    public function patch($uri, $handler) {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete($uri, $handler) {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    private function addRoute($method, $uri, $handler) {
        // Special handling for root path
        if ($uri === '/') {
            $normalizedUri = '/';
        } else {
            $normalizedUri = $this->normalizePath($uri);
        }
        
        $this->routes[$method][$normalizedUri] = [
            'handler' => $handler,
            'middlewares' => []
        ];
        
        $this->lastRoute = &$this->routes[$method][$normalizedUri]; 
        return $this;
    }

    // Menambahkan middleware to last route
    public function middleware($middlewares) {
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->lastRoute['middlewares'] = array_merge($this->lastRoute['middlewares'], $middlewares);
        return $this;
    }

    // Handle request (dispatch)
    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $this->getRequestPath();

        $routeData = $this->findRoute($requestMethod, $requestUri);

        if ($routeData) {
            $route = $routeData['route']; 
            $params = $routeData['params'];

            $_GET = array_merge($_GET, $params);

            $pipeline = $this->runMiddlewares($route['middlewares'], $route['handler']);
            
            $pipelineResult = $pipeline();

            if ($pipelineResult === false) {
                 return;
            }
            
            $this->handleRequest($route['handler'], $params);
            return;
        }

        $this->handleNotFound();
    }

    private function findRoute($requestMethod, $requestUri) {
        // Handle root path specially
        if ($requestUri === '' && isset($this->routes[$requestMethod]['/'])) {
            return [
                'route' => $this->routes[$requestMethod]['/'],
                'params' => []
            ];
        }

        // Exact match first
        if (isset($this->routes[$requestMethod][$requestUri])) {
            return [
                'route' => $this->routes[$requestMethod][$requestUri],
                'params' => []
            ];
        }

        // Pattern matching for parameterized routes
        foreach ($this->routes[$requestMethod] as $uri => $route) {
            if (strpos($uri, '{') !== false) {
                $pattern = $this->convertUriToRegex($uri);
                
                if (preg_match($pattern, $requestUri, $matches)) {
                    array_shift($matches); // Remove full match
                    
                    // Extract named parameters only
                    $namedParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    return [
                        'route' => $route,
                        'params' => $namedParams
                    ];
                }
            }
        }

        return null;
    }

    // Konversi URI dengan parameter menjadi regex
    private function convertUriToRegex($uri) {
        // Handle root path
        if ($uri === '/') {
            return '#^$#';
        }
        
        $uri = trim($uri, '/');
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    private function getRequestPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $this->normalizePath($path);
    }

    private function normalizePath($path) {
        $path = trim($path, '/');
        return $path === '' ? '' : $path;
    }

    // Execure middleware
    private function runMiddlewares($middlewares, $handler) {
        $coreLogic = function() use ($handler) {
            return true;
        };

        $next = $coreLogic;
        foreach (array_reverse($middlewares) as $middleware) {
            $next = $this->createMiddlewareLayer($middleware, $next);
        }

        return $next;
    }

    // Create middleware layer
    private function createMiddlewareLayer($middlewareName, $next) {
        return function() use ($middlewareName, $next) {
            $parts = explode(':', $middlewareName);
            $middlewareKey = $parts[0];
            $params = array_slice($parts, 1);

            $middlewareInstance = $this->container->get('AuthMiddleware');
            
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

            if ($middlewareKey === 'rate-limit') {
                $middlewareHandler = $middlewareInstance->$handlerMethod(...$params);
                return $middlewareHandler($next);
            } else {
                return $middlewareInstance->$handlerMethod($next);
            }
        };
    }

    // Handle request 
    private function handleRequest($handler, $params) {
        if (is_callable($handler)) {
            call_user_func($handler, $params);
            return;
        }

        if (is_string($handler)) {
            list($controllerName, $method) = explode('@', $handler);
            
            if (strpos($controllerName, '\\') === false) {
                $controllerName = 'Controller\\' . $controllerName;
            }
            
            if ($this->container === null) throw new Exception("Container tidak tersedia.");
            
            try {
                $controller = $this->container->get($controllerName);
            } catch (Exception $e) {
                throw new Exception("Controller {$controllerName} tidak terdaftar di container. Error: {$e->getMessage()}");
            }
            
            if (!method_exists($controller, $method)) {
                throw new Exception("Method {$method} tidak ditemukan di {$controllerName}");
            }

            if (!empty($params)) {
                $paramValues = array_values($params);
                call_user_func_array([$controller, $method], $paramValues);
            } else {
                call_user_func([$controller, $method]);
            }
            return;
        }
        throw new Exception('Handler tidak valid: ' . print_r($handler, true));
    }

    // handle 404
    private function handleNotFound() {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>Halaman yang Anda cari tidak ditemukan.</p>";
        exit;
    }
}