<?php
class Router
{
    private static $instance = null;
    private $routes = [];
    private $currentRoute = null;

    // Ctor
    private function __construct() {
        $this->routes = [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'PATCH' => [],
            'DELETE' => [],
            'OPTIONS' => []
        ];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Register GET route
    public function get($path, $handler) {
        return $this->addRoute('GET', $path, $handler);
    }

    // Register POST route
    public function post($path, $handler) {
        return $this->addRoute('POST', $path, $handler);
    }

    // Register PUT route
    public function put($path, $handler) {
        return $this->addRoute('PUT', $path, $handler);
    }

    // Register PATCH route
    public function patch($path, $handler) {
        return $this->addRoute('PATCH', $path, $handler);
    }

    // Register DELETE route
    public function delete($path, $handler) {
        return $this->addRoute('DELETE', $path, $handler);
    }

    // Register OPTIONS route
    public function options($path, $handler) {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    // Add Route to Router
    private function addRoute($method, $path, $handler) {
        $path = $this->normalizePath($path);
        
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'path' => $path,
            'method' => $method
        ];
        
        return $this;
    }

    // Normalize Path
    private function normalizePath($path) {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    // Get Current Request Method
    private function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    // Get Current Request Path
    private function getRequestPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        return $this->normalizePath($path);
    }

    // Dispatch Request
    public function dispatch() {
        try {
            $method = $this->getRequestMethod();
            $path = $this->getRequestPath();

            // Find exact match first
            if (isset($this->routes[$method][$path])) {
                $route = $this->routes[$method][$path];
                $this->currentRoute = $route;
                return $this->executeRoute($route);
            }

            // Try to match routes with parameters
            foreach ($this->routes[$method] as $routePath => $route) {
                if ($this->matchRoute($routePath, $path)) {
                    $this->currentRoute = $route;
                    return $this->executeRoute($route);
                }
            }

            // No route found (404)
            return $this->handleNotFound();

        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    // Match Route with Parameters
    private function matchRoute($routePath, $requestPath) {
        // Convert to regex pattern
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';
        
        return preg_match($pattern, $requestPath);
    }

    // Execute Matched Route
    private function executeRoute($route) {
        $handler = $route['handler'];

        // If handler is a callable
        if (is_callable($handler)) {
            return call_user_func($handler);
        }

        // If handler is Controller@method format
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controllerName, $methodName) = explode('@', $handler);
            
            // Load controller
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                
                if (method_exists($controller, $methodName)) {
                    return call_user_func([$controller, $methodName]);
                } else {
                    throw new Exception("Method {$methodName} not found in {$controllerName}");
                }
            } else {
                throw new Exception("Controller {$controllerName} not found");
            }
        }

        throw new Exception("Invalid route handler: " . print_r($handler, true));
    }

    // Handle 404 Not Found
    private function handleNotFound() {
        http_response_code(404);
        
        if (class_exists('ErrorController')) {
            $controller = new ErrorController();
            if (method_exists($controller, 'notFound')) {
                return $controller->notFound();
            }
        }
        
        // Default 404 response
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Not Found',
            'message' => 'The requested route was not found',
            'path' => $this->getRequestPath(),
            'method' => $this->getRequestMethod()
        ]);
    }

    // Handle Route Execution Errors
    private function handleError($exception) {
        http_response_code(500);
        
        if (class_exists('ErrorController')) {
            $controller = new ErrorController();
            if (method_exists($controller, 'internalError')) {
                return $controller->internalError($exception);
            }
        }
        
        // Default error response
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }

    // Get All Registered Routes
    public function getRoutes() {
        return $this->routes;
    }

    // Get Current Matched Route
    public function getCurrentRoute() {
        return $this->currentRoute;
    }
}