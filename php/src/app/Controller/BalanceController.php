<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Service\AuthService;
use Service\LoggerService;
use Service\CSRFService;
use Exception;

class BalanceController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private LoggerService $logger;
    private CSRFService $csrfService;

    // Ctor
    public function __construct(User $userModel, AuthService $authService, LoggerService $logger, CSRFService $csrfService) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->logger = $logger;
        $this->csrfService = $csrfService;
    }

    // api untuk top up balance
    public function topUp()
    {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $amount = (int)($_POST['amount'] ?? 0);

            if ($amount < 10000) {
                return $this->error('Minimal top-up adalah Rp 10.000.', 400);
            }

            $this->userModel->addBalance($buyerId, $amount);

            // Ambil data terbaru untuk dikirim kembali
            $user = $this->userModel->find($buyerId);
            $newBalance = $user['balance'];
            
            // Update session agar saldo di navbar konsisten
            $this->authService->updateUserSession(['balance' => $newBalance]);

            // Kembalikan respons JSON yang sukses
            return $this->success('Top-up berhasil!', [
                'new_balance' => $newBalance
            ]);

        } catch (Exception $e) {
            $this->logger->logError('Top-up failed', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            return $this->error('Terjadi kesalahan saat top-up: ' . $e->getMessage(), 500);
        }
    }
}