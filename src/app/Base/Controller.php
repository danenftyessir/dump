<?php

namespace Base;

class Controller
{
    // render view dengan data
    protected function view($viewPath, $data = []) {
        // extract data menjadi variables
        extract($data);
        
        // construct path ke view file
        $viewFile = dirname(dirname(__FILE__)) . '/View/' . $viewPath . '.php';
        
        // cek apakah file view ada
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            // log error jika view tidak ditemukan
            error_log("view tidak ditemukan: {$viewFile}");
            http_response_code(404);
            echo "view tidak ditemukan";
        }
    }

    // redirect ke url lain
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    // return json response dengan status success
    protected function success($message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }

    // return json response dengan status error
    protected function error($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    // return json response
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // get request method
    protected function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    // check if request is ajax
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    // get all input data
    protected function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }
        
        return array_merge($_GET, $_POST);
    }

    // validate csrf token
    protected function validateCsrf() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        
        return $token === $sessionToken;
    }
}