<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Model\Store;
use Service\AuthService;
use Service\CSRFService;
use Service\LoggerService;
use Service\FileService;
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
    private Store $storeModel;
    private FileService $fileService;

    // constructor
    public function __construct(User $userModel, AuthService $authService, UserValidator $validator, CSRFService $csrfService, LoggerService $logger, Store $storeModel = null, FileService $fileService = null) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->validator = $validator;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
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
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'address' => $_POST['address'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'role' => $_POST['role'] ?? 'BUYER',
                'store_name' => $_POST['store_name'] ?? null,
                'store_description' => $_POST['store_description'] ?? null
            ];

            // Ambil data file
            $fileData = $_FILES['store_logo'] ?? null;

            // Inisialisasi logoPath
            $logoPath = null;

            // Validasi Input
            $this->validator->validateRegistration($userData, $fileData);
            
            // Upload file jika ada
            if ($userData['role'] === 'SELLER' && $fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->fileService->upload($fileData, 'logos');
            }

            // Buat User Baru
            $newUser = $this->userModel->createUser($userData);

            // Jika seller, buat toko baru
            if ($newUser && $newUser['role'] === 'SELLER') {
                $this->storeModel->createStore([
                    'user_id' => $newUser['user_id'],
                    'store_name' => $userData['store_name'],
                    'store_description' => $userData['store_description'],
                    'store_logo_path' => $logoPath,
                    'balance' => 0.00
                ]);
            }
            
            if ($newUser) {
                unset($newUser['password']);
                $this->authService->loginUser($newUser);

                if ($newUser['role'] === 'SELLER') {
                    $this->redirect('/seller/dashboard');
                } else {
                    $this->redirect('/');
                }
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

    // api untuk mendapatkan csrf token
    public function getCsrfToken() {
        try {
            $token = $this->csrfService->getToken();
            
            return $this->success('csrf token berhasil diambil', [
                'token' => $token
            ]);
        } catch (Exception $e) {
            return $this->error('gagal mengambil csrf token: ' . $e->getMessage(), 500);
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

        $this->view('auth/profile', [
            'user' => $user,
            '_token' => $this->csrfService->getToken(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'errors' => $errors,
            'oldInput' => $oldInput
        ]);
    }

    // Menampilkan Password Form
    public function passwordForm() {
        $successMessage = $this->authService->getFlashMessage('success');
        $errorMessage = $this->authService->getFlashMessage('error');
        $errors = $this->authService->getFlashMessage('errors') ?? [];

        $this->view('auth/password', [
            '_token' => $this->csrfService->getToken(),
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
            'errors' => $errors
        ]);
    }

    // Update Profile
    public function updateProfile() {
        $data = [
            'name' => $_POST['name'] ?? '',
            'address' => $_POST['address'] ?? ''
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
            $this->authService->setFlashMessage('old_input', $_POST);
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
            'current_password' => $_POST['current_password'] ?? '',
            'new_password' => $_POST['new_password'] ?? '',
            'new_password_confirm' => $_POST['new_password_confirm'] ?? ''
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
        echo "Session Data: " . print_r($_SESSION, true);
        echo "</pre>";
    }
}