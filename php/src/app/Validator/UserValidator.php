<?php

namespace Validator;

use Model\User;
use Exception\ValidationException;
use Exception;

class UserValidator
{
    private User $userModel;

    // Ctor
    public function __construct(User $userModel) {
        $this->userModel = $userModel;
    }

    // Validate Login Data
    public function validateLogin($data) {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!empty($errors)) {
            throw new ValidationException('Login validation failed', $errors);
        }

        return true;
    }

    // Validate Registration Data
    public function validateRegistration($data, ?array $fileData) {
        $errors = [];

        // Required fields
        $required = ['name', 'email', 'password', 'address'];
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Check if email already exists
        if (empty($errors['email']) && !empty($data['email'])) {
            if ($this->userModel->findByEmail($data['email'])) {
                $errors['email'] = 'Email is already registered';
            }
        }

        // Password confirmation
        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'Password confirmation does not match';
        }

        // Password validation
        if (!empty($data['password'])) {
            $passwordErrors = $this->validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = implode('. ', $passwordErrors);
            }
        }

        // Validasi khusus seller
        if (isset($data['role']) && $data['role'] === 'SELLER') {
            // validasi nama toko
            if (empty($data['store_name'] ?? '')) {
                $errors['store_name'] = 'Store name is required for sellers';
            }
            
            // validasi deskripsi toko
            if (empty($data['store_description'] ?? '')) {
                $errors['store_description'] = 'Store description is required for sellers';
            }

            // Validasi file logo toko jika ada
            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                // cek tipe file
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions)) {
                    $errors['store_logo'] = 'Invalid store logo format. Allowed formats: ' . implode(', ', $allowedExtensions);
                }
            } elseif ($fileData && $fileData['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors['store_logo'] = 'Error uploading store logo. Please try again.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Registration validation failed', $errors);
        }

        return true;
    }

    // Validate Profile Update Data
    public function validateProfileUpdate($data) {
        $errors = [];

        // Name is required
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Name is required';
        }

        // Address is required
        if (empty(trim($data['address'] ?? ''))) {
            $errors['address'] = 'Address is required';
        }

        if (!empty($errors)) {
            throw new ValidationException('Profile update validation failed', $errors);
        }

        return true;
    }

    // Validate Password Change Data
    public function validateChangePassword($data, $userId) {
        $errors = [];

        // Current password is required
        if (empty($data['current_password'])) {
            $errors['current_password'] = 'Current password is required';
        } 

        // New password is required
        if (empty($data['new_password'])) {
            $errors['new_password'] = 'New password is required';
        }

        // Confirm new password
        if (!empty($data['new_password']) && empty($data['new_password_confirm'])) {
            $errors['new_password_confirm'] = 'New password confirmation is required';
        }

        // Verify current password
        if (empty($errors['current_password']) && !empty($data['current_password'])) {
            if (!$this->userModel->checkCurrentPassword($userId, $data['current_password'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }
        }

        // New password validation
        if (empty($errors['new_password'])) {
            // Cek konfirmasi
            if (($data['new_password']) !== ($data['new_password_confirm'] ?? '')) {
                $errors['new_password_confirm'] = 'New password confirmation does not match';
            }

            $passwordErrors = $this->validatePassword($data['new_password']);
            if (!empty($passwordErrors)) {
                $errors['new_password'] = implode('. ', $passwordErrors);
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Change password validation failed', $errors);
        }

        return true;
    }

    // Password Validation
    private function validatePassword($password) {
        $errors = [];
        
        // Length check
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Uppercase letter check
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter (A-Z)';
        }
        
        // Lowercase letter check
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter (a-z)';
        }
        
        // Number check
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number (0-9)';
        }
        
        // Special character check
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&* etc.)';
        }
        
        return $errors;
    }
}