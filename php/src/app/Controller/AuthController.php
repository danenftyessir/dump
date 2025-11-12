<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Model\Store;
use Service\AuthService;
use Service\CSRFService;
use Service\LoggerService;
use Service\FileService;
use Service\CartService;
use Validator\UserValidator;
use Exception\ValidationException;
use Core\Request;
use Exception;

class AuthController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private UserValidator $validator;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private Store $storeModel;
    private FileService $fileService;
    private CartService $cartService;
    private Request $request;

    // Ctor
    public function __construct(User $userModel, AuthService $authService, UserValidator $validator, CSRFService $csrfService, LoggerService $logger, Request $request, CartService $cartService, Store $storeModel, FileService $fileService) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->validator = $validator;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->request = $request;
        $this->cartService = $cartService;
        $this->storeModel = $storeModel;
        $this->fileService = $fileService;
    }
    
    // Menampilkan Halaman Form Login
    public function loginForm() {
        $errorMessage = $this->authService->getFlashMessage('error');
        $successMessage = $this->authService->getFlashMessage('success');
        $errors = $this->authService->getFlashMessage('errors') ?? [];
        $oldInput = $this->authService->getFlashMessage('old_input') ?? [];

        $this->view('auth/login', [
            '_token' => $this->csrfService->getToken(),
            'errorMessage' => $errorMessage, 
            'successMessage' => $successMessage,
            'errors' => $errors,
            'oldInput' => $oldInput
        ]);
    }

    // Memproses Data dari Form Login
    public function login() {
        try {
            // Ambil data input
            $loginData = [
                'email' => $this->request->post('email', ''),
                'password' => $this->request->post('password', '')
            ];

            // Validasi input
            $this->validator->validateLogin($loginData);

            // Ambil User
            $user = $this->userModel->findByEmailWithPassword($loginData['email']);

            // Verifikasi Password
            if (!$user || !$this->userModel->verifyPassword($loginData['password'], $user['password'])) {
                throw new Exception('Invalid email or password.');
            }
            
            // Set Session
            $this->authService->loginUser($user);

            // Redirect berdasarkan Role
            if ($user['role'] === 'SELLER') {
                $this->redirect('/seller/dashboard');
            } else {
                $this->redirect('/');
            }

        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $loginData);
            $this->redirect('/login');
        } catch (Exception $e) {
            // Write log error
            $this->logger->logError('Login failed', [
                'email' => $loginData['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $this->request->server('REMOTE_ADDR', 'unknown')
            ]);
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('old_input', $loginData);
            $this->redirect('/login');
        }
    }

    // Menampilkan Halaman Form Registrasi
    public function registerForm() {
        $errorMessage = $this->authService->getFlashMessage('error');
        $successMessage = $this->authService->getFlashMessage('success');
        $errors = $this->authService->getFlashMessage('errors') ?? [];
        $oldInput = $this->authService->getFlashMessage('old_input') ?? [];

        $this->view('auth/register', [
            '_token' => $this->csrfService->getToken(),
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
            'errors' => $errors,
            'oldInput' => $oldInput
        ]);
    }

    // Memproses Data dari Form Registrasi
    public function register() {
        try {
            // Ambil data input
            $userData = [
                'name' => $this->request->post('name', ''),
                'email' => $this->request->post('email', ''),
                'password' => $this->request->post('password', ''),
                'address' => $this->request->post('address', ''),
                'password_confirm' => $this->request->post('password_confirm', ''),
                'role' => $this->request->post('role', 'BUYER'),
                'store_name' => $this->request->post('store_name'),
                'store_description' => $this->request->post('store_description')
            ];

            // Ambil data file menggunakan Request class
            $fileData = $this->request->files('store_logo');

            // Validasi Input
            $this->validator->validateRegistration($userData, $fileData);
            
            // Buat user baru
            $newUser = $this->authService->registerUser($userData, $fileData);

            // Redirect berdasarkan role
            if ($newUser['role'] === 'SELLER') {
                $this->redirect('/seller/dashboard');
            } else {
                $this->redirect('/');
            }

        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $userData);
            $this->redirect('/register');
        } catch (Exception $e) {
            // Write log error
            $this->logger->logError('Registration failed', [
                'email' => $userData['email'] ?? 'unknown',
                'name' => $userData['name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $this->request->server('REMOTE_ADDR', 'unknown')
            ]);
            $this->authService->setFlashMessage('error', 'Registration failed. Please try again.');
            $this->redirect('/register');
        }
    }

    // Menampilkan Halaman Dashboard
    public function dashboard() {
        $user = $this->authService->getCurrentUser();
        if ($user['role'] === 'SELLER') {
            $this->redirect('/seller/dashboard');
        } else {
            $this->redirect('/');
        }
    }

    // Menampilkan Profile Page
    public function profile() {
        $userId = $this->authService->getCurrentUserId();

        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->authService->logout();
            $this->redirect('/login');
        }

        $successMessage = $this->authService->getFlashMessage('success');
        $errorMessage = $this->authService->getFlashMessage('error');
        $errors = $this->authService->getFlashMessage('errors') ?? [];
        $oldInput = $this->authService->getFlashMessage('old_input') ?? [];

        // Data untuk navbar buyer
        $cartCount = 0;
        if ($this->authService->isBuyer()) {
            $cartCount = $this->cartService->getCartItemCount($userId);
        }

        $this->view('auth/profile', [
            'user' => $user,
            'currentUser' => $user,
            'cartCount' => $cartCount,
            '_token' => $this->csrfService->getToken(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'errors' => $errors,
            'oldInput' => $oldInput
        ]);
    }

    // Menampilkan Password Form
    public function passwordForm() {
        $userId = $this->authService->getCurrentUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->authService->logout();
            $this->redirect('/login');
        }

        $successMessage = $this->authService->getFlashMessage('success');
        $errorMessage = $this->authService->getFlashMessage('error');
        $errors = $this->authService->getFlashMessage('errors') ?? [];

        // Data untuk navbar buyer
        $cartCount = 0;
        if ($this->authService->isBuyer()) {
            $cartCount = $this->cartService->getCartItemCount($userId);
        }

        $this->view('auth/password', [
            'currentUser' => $user,
            'cartCount' => $cartCount,
            '_token' => $this->csrfService->getToken(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'errors' => $errors
        ]);
    }

    // Update Profile
    public function updateProfile() {
        $data = [
            'name' => $this->request->post('name', ''),
            'address' => $this->request->post('address', '')
        ];
        $userId = $this->authService->getCurrentUserId();

        try {
            // Validasi
            $this->validator->validateProfileUpdate($data);

            // Update di Database
            $this->userModel->update($userId, $data);

            // Update di Session
            $this->authService->updateUserSession($data);

            $this->authService->setFlashMessage('success', 'Profile updated successfully.');
            $this->redirect('/profile');
        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $this->request->post());
            $this->redirect('/profile');
        } catch (Exception $e) {
            $this->logger->logError('Profile update failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'An error occurred while updating profile.');
            $this->redirect('/profile');
        }
    }

    // Change Password
    public function changePassword() {
        $data = [
            'current_password' => $this->request->post('current_password', ''),
            'new_password' => $this->request->post('new_password', ''),
            'new_password_confirm' => $this->request->post('new_password_confirm', '')
        ];
        $userId = $this->authService->getCurrentUserId();

        try {
            // Validasi
            $this->validator->validateChangePassword($data, $userId);

            // Update Password di Database
            $this->userModel->updatePassword($userId, $data['new_password']);

            $this->authService->setFlashMessage('success', 'Password changed successfully.');
            $this->redirect('/profile/password');
        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->redirect('/profile/password');
        } catch (Exception $e) {
            $this->logger->logError('Password change failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'An error occurred while changing password.');
            $this->redirect('/profile/password');
        }
    }

    // Logout User
    public function logout() {
        $this->authService->logoutUser();
        $this->authService->setFlashMessage('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    // Get CSRF Token for AJAX requests
    public function getCsrfToken() {
        header('Content-Type: application/json');

        $token = $this->csrfService->getToken();

        echo json_encode([
            'success' => true,
            'data' => [
                'token' => $token
            ]
        ]);
        exit;
    }
}