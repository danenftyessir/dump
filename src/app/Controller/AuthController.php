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
                throw new Exception('email atau password salah');
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
            $this->logger->logError('login failed', [
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
                'role' => $_POST['role'] ?? 'BUYER',
                'store_name' => $_POST['store_name'] ?? ''
            ];

            // validasi input
            $this->validator->validateRegister($userData);

            // buat user baru
            $newUser = $this->userModel->createUser([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'address' => $userData['address'],
                'role' => $userData['role']
            ]);

            if (!$newUser) {
                throw new Exception('gagal membuat akun');
            }

            // jika seller, buat toko juga
            if ($userData['role'] === 'SELLER' && !empty($userData['store_name']) && $this->storeModel) {
                $storeData = [
                    'user_id' => $newUser['user_id'],
                    'store_name' => $userData['store_name'],
                    'store_description' => 'toko ' . $userData['store_name']
                ];
                
                $this->storeModel->createStore($storeData);
            }

            // set success message
            $this->authService->setFlashMessage('success', 'registrasi berhasil! silakan login.');
            
            // write log
            $this->logger->logInfo('user registered', [
                'user_id' => $newUser['user_id'],
                'email' => $newUser['email'],
                'role' => $newUser['role']
            ]);

            $this->redirect('/login');

        } catch (ValidationException $e) {
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->authService->setFlashMessage('errors', $e->getErrors());
            $this->authService->setFlashMessage('old_input', $userData);
            $this->redirect('/register');
        } catch (Exception $e) {
            // write log error
            $this->logger->logError('registration failed', [
                'email' => $_POST['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $this->authService->setFlashMessage('error', $e->getMessage());
            $this->redirect('/register');
        }
    }

    // proses logout
    public function logout() {
        $this->authService->logout();
        $this->redirect('/login');
    }

    // redirect ke dashboard berdasarkan role
    public function dashboard() {
        $user = $this->authService->getCurrentUser();
        
        if ($user['role'] === 'SELLER') {
            $this->redirect('/seller/dashboard');
        } else {
            $this->redirect('/');
        }
    }

    // halaman profile user
    public function profile() {
        $user = $this->authService->getCurrentUser();
        
        $this->view('profile/index', [
            'user' => $user
        ]);
    }

    // clear session untuk debugging
    // TODO: hapus di production
    public function clearSession() {
        session_destroy();
        echo 'session cleared';
    }
}