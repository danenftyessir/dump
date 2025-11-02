<?php

namespace Service;

class CSRFService
{
    // Ctor
    public function __construct() {
    }

    // Membuat Token CSRF
    public function generate() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    // Verifikasi Token CSRF
    public function verify($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Get Token CSRF
    public function getToken() {
        return $this->generate();
    }
}