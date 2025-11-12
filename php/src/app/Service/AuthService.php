<?php

namespace Service;

use Model\User;
use Model\Store;
use Service\FileService;
use Exception;

class AuthService
{
    private User $userModel;
    private Store $storeModel;
    private FileService $fileService;

    // Ctor
    public function __construct(User $userModel = null, Store $storeModel = null, FileService $fileService = null) {
        $this->userModel = $userModel;
        $this->storeModel = $storeModel;
        $this->fileService = $fileService;
    }

    // Saveuser data to session
    public function loginUser($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_balance'] = $user['balance'] ?? 0;
        $_SESSION['user_address'] = $user['address'] ?? '';
        $_SESSION['logged_in'] = true;

        session_regenerate_id(true);
    }

    // Logout user from session
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
            'role' => $_SESSION['user_role'],
            'balance' => $_SESSION['user_balance'] ?? 0,
            'address' => $_SESSION['user_address'] ?? ''
        ];
    }

    // Register user baru
    public function registerUser(array $userData, ?array $fileData = null) {
        $logoPath = null;
        
        // upload logo jika role seller
        if ($userData['role'] === 'SELLER' && $fileData && $fileData['error'] === UPLOAD_ERR_OK) {
            $logoPath = $this->fileService->upload($fileData, 'logos');
        }

        // buat user baru
        $newUser = $this->userModel->createUser($userData);
        if (!$newUser) {
            throw new Exception('Failed to create user account.');
        }

        // jika seller, buat store baru
        if ($newUser['role'] === 'SELLER') {
            $storeCreated = $this->storeModel->createStore([
                'user_id' => $newUser['user_id'],
                'store_name' => $userData['store_name'],
                'store_description' => $userData['store_description'],
                'store_logo_path' => $logoPath,
                'balance' => 0.00
            ]);
            
            if (!$storeCreated) {
                $this->userModel->delete($newUser['user_id']);
                throw new Exception('Failed to create store for seller account.');
            }
        }

        // remove password
        unset($newUser['password']);
        
        // login user baru
        $this->loginUser($newUser);
        
        return $newUser;
    }

    // Update user session data
    public function updateUserSession($data) {
        if (isset($data['name'])) {
            $_SESSION['user_name'] = $data['name'];
        }
        if (isset($data['balance'])) {
            $_SESSION['user_balance'] = $data['balance'];
        }
        if (isset($data['address'])) {
            $_SESSION['user_address'] = $data['address'];
        }
    }

    // Check if current user is seller
    public function isSeller() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'SELLER';
    }

    // Check if current user is buyer
    public function isBuyer() {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'BUYER';
    }

    // Set flash message
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