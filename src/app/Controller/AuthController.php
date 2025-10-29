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
    private $storeModel;

    // constructor
    public function __construct(User $userModel, AuthService $authService, UserValidator $validator, CSRFService $csrfService, LoggerService $logger, $storeModel = null) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->validator = $validator;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->storeModel = $storeModel;
    }
    
    // menampilkan halaman form login
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

    // memproses data dari form login
    public function login() {
        try {
            // ambil data input
            $loginData = [
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? ''
            ];

            // validasi input
            $this->validator->validateLogin($loginData);

            // ambil user
            $user = $this->userModel->findByEmailWithPassword($loginData['email']);

            // verifikasi password
            if (!$user || !$this->userModel->verifyPassword($loginData['password'], $user['password'])) {
                throw new Exception('Invalid email or password.');
            }
            
            // set session
            $this->authService->loginUser($user);

            // bersihkan output buffer sebelum redirect
            if (ob_get_level()) {
                ob_end_clean();
            }

            // redirect berdasarkan role
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
            // write log error
            $this->logger->logError('Login failed', [
                'email' => $_POST['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->redirect('/login');
        }
    }

    // menampilkan halaman form registrasi
    public function registerForm() {
        $errorMessage = $this->authService->getFlashMessage('error');
        $successMessage = $this->authService->getFlashMessage('success');
        $errors = $this->authService->getFlashMessage('errors') ?? [];
        $oldInput = $this->authService->getFlashMessage('old_input') ?? [];

        // mengirim data ke view
        $this->view('auth/register', [
            '_token' => $this->csrfService->getToken(),
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
            'errors' => $errors,
            'oldInput' => $oldInput
        ]);
    }

    // memproses data dari form registrasi
    public function register() {
        try {
            // ambil data input
            $userData = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'address' => $_POST['address'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'role' => $_POST['role'] ?? 'BUYER'
            ];
            
            // validasi input
            $this->validator->validateRegistration($userData);

            // buat user baru
            $newUser = $this->userModel->createUser($userData);
            
            if (!$newUser) {
                throw new Exception('Registration failed due to an unknown error.');
            }

            // jika user adalah seller, auto-create toko
            if ($userData['role'] === 'SELLER' && $this->storeModel) {
                try {
                    $storeData = [
                        'user_id' => $newUser['user_id'],
                        'store_name' => $userData['name'] . "'s Store",
                        'store_description' => 'Welcome to my store!',
                        'store_logo_path' => null,
                        'balance' => 0
                    ];
                    
                    $this->storeModel->create($storeData);
                } catch (Exception $e) {
                    // log error tapi tetap lanjut registrasi
                    $this->logger->logError('Auto-create store failed', [
                        'user_id' => $newUser['user_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->authService->setFlashMessage('success', 'Registration successful! Please login.');
            $this->redirect('/login');

        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $userData);
            $this->redirect('/register');
        } catch (Exception $e) {
            // write log error
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

    // menampilkan halaman dashboard
    public function dashboard() {
        $user = $this->authService->getCurrentUser();
        if ($user['role'] === 'SELLER') {
            $this->redirect('/seller/dashboard');
        } else {
            $this->redirect('/');
        }
    }

    // menampilkan profile
    public function profile() {
        $user = $this->authService->getCurrentUser();
        $this->view('auth/profile', [
            'user' => $user,
            '_token' => $this->csrfService->getToken()
        ]);
    }

    // logout user
    public function logout() {
        $this->authService->logoutUser();
        $this->authService->setFlashMessage('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    // clear session untuk debugging
    // TODO: hapus di production
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
        echo "Session Status: " . session_status() . "\n";
        echo "Session Data:\n";
        print_r($_SESSION);
        echo "</pre>";
    }
}