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
    public function validateRegistration($data) {
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

        if (!empty($errors)) {
            throw new ValidationException('Registration validation failed', $errors);
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