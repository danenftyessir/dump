<?php

namespace Core;

class Request
{
    private array $post;
    private array $get;
    private array $files;
    private array $server;

    // Ctor
    public function __construct() {
        $this->post = $_POST ?? [];
        $this->get = $_GET ?? [];
        $this->files = $_FILES ?? [];
        $this->server = $_SERVER ?? [];
    }

    // Get POST data
    public function post(string $key = null, $default = null) {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    // Get GET data
    public function get(string $key = null, $default = null) {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }

    // Get FILES data
    public function files(string $key = null) {
        if ($key === null) {
            return $this->files;
        }
        
        return $this->files[$key] ?? null;
    }

    // Get SERVER data
    public function server(string $key = null, $default = null) {
        if ($key === null) {
            return $this->server;
        }
        
        return $this->server[$key] ?? $default;
    }

    // Check if request has specific key in POST
    public function has(string $key) {
        return isset($this->post[$key]);
    }

    // Check if request has specific file uploaded
    public function hasFile(string $key) {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    // Get HTTP method
    public function method() {
        return $this->server('REQUEST_METHOD', 'GET');
    }

    public function isPost() {
        return $this->method() === 'POST';
    }

    public function all() {
        return array_merge($this->get, $this->post);
    }

    public function only(array $keys) {
        $result = [];
        $all = $this->all();
        
        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        
        return $result;
    }

    // Get HTTP header
    public function getHeader(string $name, $default = null) {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server($serverKey, $default);
    }

    // Get referer URL
    public function getReferer($default = '/') {
        return $this->server('HTTP_REFERER', $default);
    }
}