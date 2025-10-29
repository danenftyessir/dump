<?php

namespace Middleware;

use Service\AuthService;
use Service\CSRFService;
use Service\RateLimitService;

class AuthMiddleware
{
    private $authService;
    private $csrfService;
    private $rateLimitService;

    // Ctor
    public function __construct(AuthService $authService, CSRFService $csrfService, RateLimitService $rateLimitService) {
        $this->authService = $authService;
        $this->csrfService = $csrfService;
        $this->rateLimitService = $rateLimitService;
    }

    // Cek Apakah User Sudah Login
    public function handleAuth($next) {
        if (!$this->authService->isLoggedIn()) {
            header("Location: /login");
            exit();
        }

        return $next();
    }

    // Cek Apakah User Belum Login
    public function handleGuest($next) {
        if ($this->authService->isLoggedIn()) {
            header("Location: /dashboard");
            exit();
        }

        return $next();
    }

    // Cek Apakah User adalah Seller
    public function handleSeller($next) {
        if (!$this->authService->isSeller()) {
            header("Location: /dashboard");
            exit();
        }

        return $next();
    }

    // Verifikasi Token CSRF
    public function handleCSRF($next) {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (!$this->csrfService->verify($token)) {
                $this->authService->setFlashMessage('error', 'Invalid security token. Please try again.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: " . $referer);
                exit();
            }
        }

        return $next();
    }

    // Membatasi Jumlah Request
    public function handleRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        return function($next) use ($action, $maxAttempts, $timeWindow) {
            if (!$this->rateLimitService->check($action, $maxAttempts, $timeWindow)) {
                $this->authService->setFlashMessage('error', 'Too many attempts. Please try again later.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: " . $referer);
                exit();
            }

            return $next();
        };
    }

    // Security Headers
    public function handleSecurityHeaders($next) {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

        return $next();
    }
}