<?php

namespace Core;

use Core\Container;
use Exception;

class Router
{
    private $routes = [];
    private Container $container;
    private $lastRoute;

    // Ctor
    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function get($path, $handler) {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler) {
        return $this->addRoute('POST', $path, $handler);
    }

    private function addRoute($method, $path, $handler) {
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => []
        ];
        $this->lastRoute = &$this->routes[$method][$path];
        return $this;
    }

    // Menambahkan Middleware to Last Route
    public function middleware($middlewares) {
        if (!is_array($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->lastRoute['middlewares'] = array_merge($this->lastRoute['middlewares'], $middlewares);
        return $this;
    }

    // Handle Request dan Dispatch
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getRequestPath();

        $route = $this->findRoute($method, $path);

        if ($route) {
            return $this->executeRoute($route);
        }
        $this->handleNotFound();
    }

    private function findRoute($method, $path) {
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path];
        }
        
        return null;
    }

    // Execute Route
    private function executeRoute($route) {
        $handler = $route['handler'];
        $middlewares = $route['middlewares'] ?? [];

        $coreLogic = function() use ($handler) {
            // Check if handler is a closure
            if (is_callable($handler)) {
                return $handler();
            }
            
            // Check if handler is a controller method string
            if (is_string($handler) && strpos($handler, '@') !== false) {
                list($controllerName, $methodName) = explode('@', $handler);

                $controller = $this->container->get($controllerName);

                if (method_exists($controller, $methodName)) {
                    return $controller->$methodName();
                }
                throw new Exception("Method {$methodName} tidak ditemukan di {$controllerName}");
            }
            throw new Exception("Handler tidak valid: " . print_r($handler, true));
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            $this->createMiddlewareLayer(),
            $coreLogic
        );
        
        return $pipeline();
    }

    // Create Middleware Layer
    private function createMiddlewareLayer() {
        return function ($next, $middlewareName) {
            return function () use ($next, $middlewareName) {
                // Separate middleware name and parameters
                $parts = explode(':', $middlewareName);
                $middlewareKey = $parts[0];
                $params = array_slice($parts, 1);

                $middlewareInstance = $this->container->get('AuthMiddleware');
                
                // Map middleware names to method names
                $middlewareMap = [
                    'auth' => 'handleAuth',
                    'guest' => 'handleGuest',
                    'seller' => 'handleSeller',
                    'csrf' => 'handleCSRF',
                    'rate-limit' => 'handleRateLimit',
                    'security-headers' => 'handleSecurityHeaders'
                ];
                
                $handlerMethod = $middlewareMap[$middlewareKey] ?? ('handle' . str_replace('-', '', ucwords($middlewareKey, '-')));

                if (method_exists($middlewareInstance, $handlerMethod)) {
                    return $middlewareInstance->$handlerMethod($next, ...$params);
                }
                throw new Exception("Middleware handler {$handlerMethod} tidak ditemukan");
            };
        };
    }

    private function getRequestPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $this->normalizePath($path);
    }

    private function normalizePath($path) {
        $path = trim($path, '/');
        return '/' . $path;
    }

    private function handleNotFound() {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>"; // Todo buat halaman khusus 404 ntaran
        exit;
    }
}