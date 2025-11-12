<?php

namespace Middleware;

use Service\AuthService;
use Service\CSRFService;
use Service\RateLimitService;
use Core\Request;

class AuthMiddleware
{
    private AuthService $authService;
    private CSRFService $csrfService;
    private RateLimitService $rateLimitService;
    private Request $request;

    // Ctor
    public function __construct(AuthService $authService, CSRFService $csrfService, RateLimitService $rateLimitService, Request $request) {
        $this->authService = $authService;
        $this->csrfService = $csrfService;
        $this->rateLimitService = $rateLimitService;
        $this->request = $request;
    }

    // Cek apakah request adalah ajax
    private function isAjaxRequest() {
        // Check header X-Requested-With
        $isXhrHeader = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                       strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Check if Content-Type is JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJsonContent = strpos($contentType, 'application/json') !== false;

        // Check if URL starts with /api/
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiPath = strpos($requestUri, '/api/') !== false;

        return $isXhrHeader || $isJsonContent || $isApiPath;
    }

    // Return json error
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }

    // cek apakah user sudah login
    public function handleAuth($next) {
        if (!$this->authService->isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('silakan login terlebih dahulu', 401);
            }
            
            $this->authService->setFlashMessage('error', 'silakan login terlebih dahulu');
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

    // cek apakah user adalah seller
    public function handleSeller($next) {
        if (!$this->authService->isSeller()) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('akses ditolak. anda bukan seller', 403);
            }

            $this->authService->setFlashMessage('error', 'akses ditolak. anda bukan seller');
            header("Location: /");
            exit();
        }

        return $next();
    }

    // cek apakah user adalah buyer
    public function handleBuyer($next) {
        if (!$this->authService->isBuyer()) {
            if ($this->isAjaxRequest()) {
                $this->jsonError('akses ditolak. anda bukan buyer', 403);
            }
            
            $this->authService->setFlashMessage('error', 'akses ditolak. anda bukan buyer');
            header("Location: /seller/dashboard");
            exit();
        }

        return $next();
    }

    // verifikasi token csrf
    public function handleCSRF($next) {
        $method = $this->request->method();
        
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // ambil token dari berbagai sumber
            $token = $_POST['csrf_token'] ?? 
                     $_POST['_token'] ?? 
                     $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                     '';

            // debug log
            error_log('csrf validation - method: ' . $method);
            error_log('csrf token received: ' . substr($token, 0, 20) . '...');
            error_log('csrf token from session: ' . substr($_SESSION['csrf_token'] ?? '', 0, 20) . '...');

            if (!$this->csrfService->verify($token)) {
                error_log('csrf validation failed!');
                
                if ($this->isAjaxRequest()) {
                    $this->jsonError('token keamanan tidak valid', 403);
                }

                $this->authService->setFlashMessage('error', 'token keamanan tidak valid. silakan coba lagi');
                $referer = $this->request->getReferer();
                header("Location: " . $referer);
                exit();
            }
            
            error_log('csrf validation success!');
        }

        return $next();
    }

    // membatasi jumlah request (rate limiting)
    public function handleRateLimit($action = 'default', $maxAttempts = 5, $timeWindow = 300) {
        return function($next) use ($action, $maxAttempts, $timeWindow) {
            if (!$this->rateLimitService->check($action, $maxAttempts, $timeWindow)) {
                if ($this->isAjaxRequest()) {
                    $this->jsonError('terlalu banyak percobaan. silakan coba lagi nanti', 429);
                }
                
                $this->authService->setFlashMessage('error', 'terlalu banyak percobaan. silakan coba lagi nanti');
                $referer = $this->request->getReferer();
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
        
        // prevent mime type sniffing
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