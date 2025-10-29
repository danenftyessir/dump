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

    // constructor
    public function __construct(AuthService $authService, CSRFService $csrfService, RateLimitService $rateLimitService) {
        $this->authService = $authService;
        $this->csrfService = $csrfService;
        $this->rateLimitService = $rateLimitService;
    }

    // cek apakah user sudah login
    public function handleAuth($next) {
        if (!$this->authService->isLoggedIn()) {
            $this->authService->setFlashMessage('error', 'Silakan Login Terlebih Dahulu');
            header("Location: /login");
            exit();
        }

        return $next();
    }

    // cek apakah user belum login (untuk halaman guest)
    public function handleGuest($next) {
        if ($this->authService->isLoggedIn()) {
            // redirect berdasarkan role
            $user = $this->authService->getCurrentUser();
            if ($user['role'] === 'SELLER') {
                header("Location: /seller/dashboard");
            } else {
                header("Location: /");
            }
            exit();
        }

        return $next();
    }

    // cek apakah user adalah seller
    public function handleSeller($next) {
        if (!$this->authService->isLoggedIn()) {
            $this->authService->setFlashMessage('error', 'Silakan Login Sebagai Seller');
            header("Location: /login");
            exit();
        }

        if (!$this->authService->isSeller()) {
            $this->authService->setFlashMessage('error', 'Akses Ditolak. Anda Bukan Seller.');
            header("Location: /");
            exit();
        }

        return $next();
    }

    // cek apakah user adalah buyer
    public function handleBuyer($next) {
        if (!$this->authService->isLoggedIn()) {
            $this->authService->setFlashMessage('error', 'Silakan Login Terlebih Dahulu');
            header("Location: /login");
            exit();
        }

        if (!$this->authService->isBuyer()) {
            $this->authService->setFlashMessage('error', 'Akses Ditolak. Anda Bukan Buyer.');
            header("Location: /seller/dashboard");
            exit();
        }

        return $next();
    }

    // verifikasi token CSRF
    public function handleCSRF($next) {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (!$this->csrfService->verify($token)) {
                $this->authService->setFlashMessage('error', 'Token Keamanan Tidak Valid. Silakan Coba Lagi.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: " . $referer);
                exit();
            }
        }

        return $next();
    }

    // membatasi jumlah request (rate limiting)
    public function handleRateLimit($action = 'default', $maxAttempts = 5, $timeWindow = 300) {
        return function($next) use ($action, $maxAttempts, $timeWindow) {
            if (!$this->rateLimitService->check($action, $maxAttempts, $timeWindow)) {
                $this->authService->setFlashMessage('error', 'Terlalu Banyak Percobaan. Silakan Coba Lagi Nanti.');
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: " . $referer);
                exit();
            }

            return $next();
        };
    }

    // security headers
    public function handleSecurityHeaders($next) {
        // prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // xss protection
        header('X-XSS-Protection: 1; mode=block');
        
        // referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // content security policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.quilljs.com; style-src 'self' 'unsafe-inline' https://cdn.quilljs.com; img-src 'self' data: https:;");

        return $next();
    }
}