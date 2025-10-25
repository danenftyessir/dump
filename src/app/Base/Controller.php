<?php
abstract class Controller
{
    protected $data = [];

    // Return JSON Response
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        return $this;
    }

    // Return Success JSON Response
    protected function success($message = 'Success', $data = null) {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->json($response);
    }

    // Return Error JSON Response
    protected function error($message = 'Error', $status = 400, $data = null) {
        $response = ['success' => false, 'error' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $this->json($response, $status);
    }

    // Render view template
    protected function view($template, $data = []) {
        $viewData = array_merge($this->data, $data);
        
        extract($viewData);
        
        $templatePath = $this->getTemplatePath($template);
        
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new Exception("View template not found: {$template}");
        }
        
        return $this;
    }

    // Get Template Path
    private function getTemplatePath($template) {
        $viewPath = dirname(__DIR__) . '/View';
        
        // Support both .php and .html extensions
        if (!pathinfo($template, PATHINFO_EXTENSION)) {
            $template .= '.php';
        }
        
        return $viewPath . '/' . $template;
    }

    // Redirect to URL
    protected function redirect($url, $status = 302) {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    // Get Request Input
    protected function input($key = null, $default = null) {
        $input = [];
        
        // Merge GET and POST Data
        $input = array_merge($_GET ?? [], $_POST ?? []);
        
        // Parse JSON Input if Content Type is JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = json_decode(file_get_contents('php://input'), true);
            if ($jsonInput) {
                $input = array_merge($input, $jsonInput);
            }
        }
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }

    // Validate Required Fields
    protected function validate($rules) {
        $errors = [];
        $input = $this->input();
        
        foreach ($rules as $field => $rule) {
            $ruleArray = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($ruleArray as $singleRule) {
                if ($singleRule === 'required') {
                    if (!isset($input[$field]) || empty(trim($input[$field]))) {
                        $errors[$field] = "Field {$field} is required";
                        break;
                    }
                }
                
                if (strpos($singleRule, 'min:') === 0) {
                    $min = (int) substr($singleRule, 4);
                    if (isset($input[$field]) && strlen($input[$field]) < $min) {
                        $errors[$field] = "Field {$field} must be at least {$min} characters";
                        break;
                    }
                }
                
                if (strpos($singleRule, 'max:') === 0) {
                    $max = (int) substr($singleRule, 4);
                    if (isset($input[$field]) && strlen($input[$field]) > $max) {
                        $errors[$field] = "Field {$field} must not exceed {$max} characters";
                        break;
                    }
                }
                
                if ($singleRule === 'email') {
                    if (isset($input[$field]) && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Field {$field} must be a valid email";
                        break;
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            exit;
        }
        
        return $input;
    }

    // Set Data for View
    protected function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    // Get Current User (Jika Session Ada)
    protected function user()
    {
        session_start();
        return $_SESSION['user'] ?? null;
    }

    // Check if User is Authenticated
    protected function auth()
    {
        return $this->user() !== null;
    }

    // Require Authentication
    protected function requireAuth()
    {
        if (!$this->auth()) {
            $this->error('Authentication required', 401);
            exit;
        }
    }
}