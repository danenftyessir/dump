<?php

namespace Controller;

use Base\Controller;
use Model\User;
use Service\AuthService;
use Service\CartService;
use Service\CSRFService;
use Service\LoggerService;
use Service\OrderService;
use Exception;
use PDOException;

class CheckoutController extends Controller
{
    private User $userModel;
    private AuthService $authService;
    private OrderService $orderService;
    private CartService $cartService;
    private CSRFService $csrfService;
    private LoggerService $logger;

    // Ctor
    public function __construct(User $userModel, AuthService $authService, OrderService $orderService, CartService $cartService, CSRFService $csrfService, LoggerService $logger) {
        $this->userModel = $userModel;
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
    }

    public function index() {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            $buyer = $this->userModel->find($buyerId);

            if ($cartSummary['is_empty']) {
                $this->authService->setFlashMessage('error', 'Keranjang belanja kosong.');
                return $this->redirect('/cart');
            }

            $grandTotal = $cartSummary['grand_total'];
            $currentBalance = $buyer['balance'];
            $newBalance = $currentBalance - $grandTotal;

            return $this->view('buyer/checkout-page', [
                'cartSummary' => $cartSummary,
                'buyer' => $buyer,
                'user' => $buyer,
                'stores' => $cartSummary['stores'],
                'storeCount' => $cartSummary['store_count'],
                'totalItems' => $cartSummary['total_items_quantity'],
                'totalPrice' => $cartSummary['grand_total'],
                'currentBalance' => $currentBalance,
                'newBalance' => $newBalance,
                'isBalanceSufficient' => $newBalance >= 0,
                '_token' => $this->csrfService->getToken()
            ]);

        } catch (Exception $e) {
            $this->logger->logError('Error loading checkout page', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Terjadi kesalahan saat memuat halaman checkout.');
            return $this->redirect('/cart');
        }
    }

    // Memproses transaksi
    public function process() {
        $buyerId = $this->authService->getCurrentUserId();
        
        $shippingAddress = $_POST['shipping_address'] ?? '';
        $confirmedTotalPrice = (int)($_POST['grand_total_confirmation'] ?? 0); 
        $buyer = $this->userModel->find($buyerId);

        try {
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            $grandTotal = $cartSummary['grand_total'];

            // validasi data checkout
            if ($grandTotal === 0) throw new Exception('Keranjang kosong.');
            if ($confirmedTotalPrice != $grandTotal) throw new Exception('Total harga tidak cocok. Silakan ulangi checkout.', 400); 
            if ($buyer['balance'] < $grandTotal) {
                 throw new Exception('Saldo tidak mencukupi. Silakan top up.', 400);
            }
            if (!$shippingAddress) {
                 throw new Exception('Alamat pengiriman wajib diisi.', 400);
            }
            
            // proses transaksi
            $this->userModel->beginTransaction();
            
            // proses pengurangan saldo buyer
            $this->userModel->deductBalanceAtomic($buyerId, $grandTotal); 

            // proses pembuatan order & pengurangan stok produk
            $this->orderService->processCheckoutTransaction($buyerId, $cartSummary, $shippingAddress);

            // bersihkan cart
            $this->cartService->clearCart($buyerId);
            
            // commit transaksi
            $this->userModel->commit(); 
            
            $this->logger->logInfo('Checkout successful', ['buyer_id' => $buyerId, 'total_price' => $grandTotal]);
            $this->authService->setFlashMessage('success', 'Checkout berhasil! Pesanan Anda sedang menunggu konfirmasi.');
            
            return $this->redirect('/orders'); 
        } catch (\PDOException $e) {
            $this->userModel->rollback();
            $this->logger->logError('Checkout failed (DB)', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Transaksi gagal. Stok atau Saldo tidak valid. Coba lagi.');
            return $this->redirect('/cart');
            
        } catch (Exception $e) {
             $this->userModel->rollback();
             $this->authService->setFlashMessage('error', $e->getMessage());
             return $this->redirect('/cart');
        }
    }  
}