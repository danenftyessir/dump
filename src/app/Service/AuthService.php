<?php

namespace Service;

class AuthService
{
    // constructor
    public function __construct() {
    }

    // save user data in session after login
    public function loginUser($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // regenerate session id untuk keamanan
        // PENTING: gunakan false agar session data tidak hilang
        session_regenerate_id(false);
        
        // commit session sebelum redirect
        // ini memastikan session ter-write ke storage
        session_write_close();
        
        // restart session untuk request berikutnya
        session_start();
    }

    // logout user by destroying session
    public function logoutUser() {
        session_unset();
        session_destroy();
    }

    // check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // get current user id
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    // get current user data
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

    // check if current user is seller
    public function isSeller() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'SELLER';
    }

    // check if current user is buyer
    public function isBuyer() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'BUYER';
    }

    // set flash message
    public function setFlashMessage($type, $message) {
        $_SESSION['flash_' . $type] = $message;
    }

    // get flash message
    public function getFlashMessage($type) {
        $key = 'flash_' . $type;
        $message = $_SESSION[$key] ?? null;
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return $message;
    }

    // check if current user can access resource
    public function canAccessResource($resourceUserId) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $currentUserId = $this->getCurrentUserId();
        return ($currentUserId == $resourceUserId) || $this->isSeller();
    }
}