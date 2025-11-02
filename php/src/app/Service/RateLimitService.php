<?php

namespace Service;

class RateLimitService
{
    // Ctor
    public function __construct() {
    }
    
    // Check Rate Limit for an Action
    public function check($action, $maxAttempts = 5, $timeWindow = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = "rate_limit_{$action}";
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => $now];
            return true;
        }

        $attempts = $_SESSION[$key];
        
        // Reset counter if time window has passed
        if (($now - $attempts['first_attempt']) > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => $now];
            return true;
        }

        // Check if limit exceeded
        if ($attempts['count'] >= $maxAttempts) {
            return false;
        }

        // Increment counter
        $_SESSION[$key]['count']++;
        return true;
    }

    // Get Remaining Attempts for an Action
    public function getRemainingAttempts($action, $maxAttempts = 5) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = "rate_limit_{$action}";
        
        if (!isset($_SESSION[$key])) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $_SESSION[$key]['count']);
    }

    // Reset Rate Limit for an Action
    public function reset($action) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = "rate_limit_{$action}";
        unset($_SESSION[$key]);
    }
}