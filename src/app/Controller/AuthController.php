<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Service\AuthService;
use Service\CSRFService;
use Service\LoggerService;
use Validator\UserValidator;
use Exception\ValidationException;
use Exception;

class AuthController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private UserValidator $validator;
    private CSRFService $csrfService;
    private LoggerService $logger;

    // Ctor
    public function __construct(User $userModel, AuthService $authService, UserValidator $validator, CSRFService $csrfService, LoggerService $logger) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->validator = $validator;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
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
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? ''
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
                'email' => $_POST['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->redirect('/login');
        }
    }

    // Menampilkan Halaman Form Registrasi
    public function registerForm() {
        $errorMessage = $this->authService->getFlashMessage('error');
        $successMessage = $this->authService->getFlashMessage('success');
        $errors = $this->authService->getFlashMessage('errors') ?? [];
        $oldInput = $this->authService->getFlashMessage('old_input') ?? [];



        // 2. Mengirim data ke View
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
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'address' => $_POST['address'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'role' => $_POST['role'] ?? 'BUYER' // Ambil role dari form, default BUYER
            ];
            
            // Validasi Input
            $this->validator->validateRegistration($userData);

            // Buat User Baru
            $newUser = $this->userModel->createUser($userData);
            
            if ($newUser) {
                $this->authService->setFlashMessage('success', 'Registration successful! Please login.');
                $this->redirect('/login');
            } else {
                throw new Exception('Registration failed due to an unknown error.');
            }

        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $userData);
            $this->redirect('/register');
        } catch (Exception $e) {
            // Write log error
            $this->logger->logError('Registration failed', [
                'email' => $_POST['email'] ?? 'unknown',
                'name' => $_POST['name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
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

    // Menampilkan Profile
    public function profile() {
        $user = $this->authService->getCurrentUser();
        $this->view('auth/profile', [
            'user' => $user,
            '_token' => $this->csrfService->getToken()
        ]);
    }

    // Logout User
    public function logout() {
        $this->authService->logoutUser();
        $this->authService->setFlashMessage('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    // Clear Session (untuk debugging)
    public function clearSession() {
        session_destroy();
        session_start();
        session_regenerate_id(true);
        
        echo "<h1>Session Cleared!</h1>";
        echo "<p>All session data has been cleared.</p>";
        echo "<p><a href='/login'>Go to Login</a> | <a href='/register'>Go to Register</a></p>";
        echo "<hr>";
        echo "<h3>Debug Info:</h3>";
        echo "<pre>";
        echo "Session ID: " . session_id() . "\n";
        echo "Session Status: " . print_r(session_status(), true) . "\n"; // Lebih informatif
        echo "Session Data: " . print_r($_SESSION, true);
        echo "</pre>";
    }
}