<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Service\AuthService;
use Service\LoggerService;
use Service\CSRFService;
use Core\Request;
use Exception;

class BalanceController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private LoggerService $logger;
    private CSRFService $csrfService;
    private Request $request;

    // Ctor
    public function __construct(User $userModel, AuthService $authService, LoggerService $logger, CSRFService $csrfService, Request $request) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->logger = $logger;
        $this->csrfService = $csrfService;
        $this->request = $request;
    }

    // api untuk top up balance
    public function topUp()
    {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $amount = (int)($this->request->post('amount', 0));

            // validasi jumlah minimum
            if ($amount < 10000) {
                return $this->error('Minimal top-up adalah Rp 10.000.', 400);
            }

            //validasi jumlah maksimum
            if ($amount > 100000000) {
                return $this->error('Maksimal top-up adalah Rp 100.000.000.', 400);
            }

            $this->userModel->addBalance($buyerId, $amount);
            $user = $this->userModel->find($buyerId);
            $newBalance = $user['balance'];
            $this->authService->updateUserSession(['balance' => $newBalance]);

            $this->logger->logInfo('TopUp success', ['buyer_id' => $buyerId, 'amount' => $amount, 'new_balance' => $newBalance]);

            return $this->success('Top-up berhasil!', [
                'new_balance' => $newBalance
            ]);

        } catch (Exception $e) {
            $this->logger->logError('Top-up failed', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            return $this->error('Terjadi kesalahan saat top-up: ' . $e->getMessage(), 500);
        }
    }

    // Mendapatkan balance saat ini
    public function getCurrentBalance()
    {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $user = $this->userModel->find($buyerId);
            
            if (!$user) {
                return $this->error('User tidak ditemukan.', 404);
            }
            
            return $this->success('Balance retrieved successfully', [
                'balance' => $user['balance']
            ]);

        } catch (Exception $e) {
            $this->logger->logError('Get balance failed', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            return $this->error('Terjadi kesalahan saat mengambil balance: ' . $e->getMessage(), 500);
        }
    }
}