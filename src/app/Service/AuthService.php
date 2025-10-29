<?php

namespace Service;

class AuthService
{
    // Ctor
    public function __construct() {
    }

    // Save User Data in Session after Login
    public function loginUser($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        session_regenerate_id(true);
    }

    // Logout User by Destroying Session
    public function logoutUser() {
        session_unset();
        session_destroy();
    }

    // Check if User is Logged In
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Get Current User ID
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    // Get Current User Data
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ];
    }

    // Check if Current User is Seller
    public function isSeller() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'SELLER';
    }

    // Check if Current User is Buyer
    public function isBuyer() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'BUYER';
    }

    // Set Flash Message
    public function setFlashMessage($type, $message) {
        $_SESSION['flash_' . $type] = $message;
    }

    // Get Flash Message
    public function getFlashMessage($type) {
        $key = 'flash_' . $type;
        $message = $_SESSION[$key] ?? null;
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return $message;
    }

    // Check if Current User can Access Resource
    public function canAccessResource($resourceUserId) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $currentUserId = $this->getCurrentUserId();
        return ($currentUserId == $resourceUserId) || $this->isSeller();
    }
}