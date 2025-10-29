<?php

namespace Base;

use Exception;

abstract class Controller
{
    protected $data = [];

    // Return JSON Response
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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

    // Set Data for View
    protected function set($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    // Render view template
    protected function view($template, $data = []) {
        $viewData = array_merge($this->data, $data);
        
        extract($viewData);
        
        $templatePath = $this->getTemplatePath($template);
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $content = ob_get_clean();
            echo $content;
        } else {
            throw new Exception("View template not found: {$template}");
        }
    }

    // Get Template Path
    private function getTemplatePath($template) {
        $viewPath = dirname(__DIR__) . '/View';
        
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
}