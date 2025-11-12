<?php

namespace Controller;

use Base\Controller;
use Service\AuthService;
use Service\CartService;
use Service\CSRFService;
use Service\LoggerService;
use Service\OrderService;
use Core\Request;
use Exception;

class CheckoutController extends Controller
{
    private AuthService $authService;
    private OrderService $orderService;
    private CartService $cartService;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private Request $request;

    // Ctor
    public function __construct(AuthService $authService, OrderService $orderService, CartService $cartService, CSRFService $csrfService, LoggerService $logger, Request $request) {
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->request = $request;
    }

    // Menampilkan halaman checkout
    public function index() {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            $currentUser = $this->authService->getCurrentUser();

            if ($cartSummary['is_empty']) {
                $this->authService->setFlashMessage('error', 'Keranjang belanja kosong.');
                return $this->redirect('/cart');
            }

            return $this->view('buyer/checkout-page', [
                'cartSummary' => $cartSummary,
                'currentUser' => $currentUser,
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
        
        $shippingAddress = $this->request->post('delivery_address', '');
        $confirmedTotalPrice = (int)($this->request->post('grand_total_confirmation', 0)); 

        try {
            // Place order
            $result = $this->orderService->placeOrder($buyerId, $shippingAddress, $confirmedTotalPrice);
            
            // Update session with new balance
            $this->authService->updateUserSession(['balance' => $result['new_balance']]);
            
            $this->logger->logInfo('Checkout successful', [
                'buyer_id' => $buyerId, 
                'total_price' => $result['total_price']
            ]);
            
            return $this->success('Checkout berhasil! Pesanan Anda sedang menunggu konfirmasi.', [
                'redirect_url' => '/orders',
                'new_balance' => $result['new_balance']
            ]); 
            
        } catch (Exception $e) {
            $this->logger->logError('Checkout failed', [
                'buyer_id' => $buyerId, 
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }  
}