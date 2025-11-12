<?php

namespace Controller;

use Base\Controller;
use Service\AuthService;
use Service\CSRFService;
use Service\LoggerService;
use Service\CartService;
use Core\Request;
use Exception;

class CartItemController extends Controller
{
    private AuthService $authService;
    private CartService $cartService;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private Request $request;

    // Ctor
    public function __construct(AuthService $authService, CartService $cartService, CSRFService $csrfService, LoggerService $logger, Request $request) {
        $this->authService = $authService;
        $this->cartService = $cartService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->request = $request;
    }

    // Menampilkan halaman keranjang belanja
    public function index() {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            $currentUser = $this->authService->getCurrentUser();
            
            // Kirim data minimal
            return $this->view('buyer/cart-page', [
                'cartSummary' => $cartSummary,
                'currentUser' => $currentUser,
                '_token' => $this->csrfService->generate()
            ]);
        } catch (Exception $e) {
            error_log('Error di CartItemController@index: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Menambahkan item ke keranjang
    public function addToCart() {
        $buyerId = $this->authService->getCurrentUserId();

        try {
            // ambil dan validasi input
            $productId = (int)$this->request->post('product_id', 0);
            $quantity = (int)$this->request->post('quantity', 1);

            if ($productId <= 0 || $quantity <= 0) {
                return $this->error('Data produk atau kuantitas tidak valid.', 400);
            }

            // tambahkan item ke keranjang dengan validasi stok
            $addResult = $this->cartService->addToCartWithValidation($buyerId, $productId, $quantity);
            
            return $this->success('Produk berhasil ditambahkan ke keranjang.', [
                'total_items' => $addResult['total_items']
            ]);

        } catch (Exception $e) {
            error_log('Error di CartItemController@addToCart: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Memperbarui kuantitas item di keranjang
    public function updateQuantity($id = null) {
        $buyerId = $this->authService->getCurrentUserId();
        $cartItemId = (int)($id ?? $this->request->post('cart_item_id', 0));
        $newQuantity = (int)$this->request->post('quantity', 0);
        
        try {
            if ($cartItemId <= 0 || $newQuantity <= 0) {
                throw new Exception('cart_item_id atau quantity tidak valid');
            }

            // Update quantity dengan pengecekan kepemilikan
            $updateResult = $this->cartService->updateItemQuantityForBuyer($buyerId, $cartItemId, $newQuantity);

            // Ambil ringkasan keranjang terbaru
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            
            return $this->success('Kuantitas berhasil diperbarui', [
                'cartSummary' => $cartSummary,
                'cart_item_id' => $updateResult['cart_item_id'],
                'item_subtotal' => $updateResult['item_subtotal'],
                'deleted' => $updateResult['deleted']
            ]);

        } catch (Exception $e) {
            error_log('Error di CartItemController@updateQuantity: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Menghapus item dari keranjang
    public function removeFromCart($id) {
        $buyerId = $this->authService->getCurrentUserId();
        $cartItemId = (int)$id;

        try {
            if ($cartItemId <= 0) {
                throw new Exception('cart_item_id tidak valid');
            }

            // Hapus item dari keranjang dengan pengecekan kepemilikan
            $this->cartService->removeItemForBuyer($buyerId, $cartItemId);

            // Ambil ringkasan keranjang terbaru
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            return $this->success('Item berhasil dihapus dari keranjang', [
                'cartSummary' => $cartSummary
            ]);
        } catch (Exception $e) {
            error_log('Error di CartItemController@removeFromCart: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}