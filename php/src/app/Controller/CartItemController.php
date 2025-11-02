<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Service\AuthService;
use Service\CSRFService;
use Service\LoggerService;
use Service\CartService;
use Exception;

class CartItemController extends Controller
{
    private AuthService $authService;
    private CartService $cartService;
    private Product $productModel;
    private CSRFService $csrfService;
    private LoggerService $logger;

    public function __construct(AuthService $authService, CartService $cartService, Product $productModel, CSRFService $csrfService, LoggerService $logger) {
        $this->authService = $authService;
        $this->cartService = $cartService;
        $this->productModel = $productModel;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
    }

    // Menampilkan halaman keranjang belanja
    public function index() {
        $buyerId = $this->authService->getCurrentUserId();

        if ($buyerId === null) {
            $this->authService->setFlashMessage('error', 'Silakan login terlebih dahulu');
            header("Location: /login");
            exit();
        }

        try {
            $cartSummary = $this->cartService->getCartSummary($buyerId);
            
            return $this->view('buyer/cart-page', [
                'cartSummary' => $cartSummary,
                'cartItems' => $cartSummary['stores'],
                'stores' => $cartSummary['stores'],
                'storeCount' => $cartSummary['store_count'],
                'totalItems' => $cartSummary['total_items_quantity'],
                'totalPrice' => $cartSummary['grand_total'],
                'isEmpty' => $cartSummary['is_empty'],
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

        if ($buyerId === null) {
            return $this->error('Silakan login terlebih dahulu', 401);
        }

        try {
            // ambil dan validasi input
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId <= 0 || $quantity <= 0) {
                return $this->error('Data produk atau kuantitas tidak valid.', 400);
            }
            
            // Cek stok produk
            $product = $this->productModel->find($productId);
            
            if (!$product || $product['stock'] < $quantity) {
                 return $this->error('Stok tidak mencukupi atau produk tidak ditemukan.', 400);
            }

            // tambahkan ke keranjang
            $this->cartService->addToCart($buyerId, $productId, $quantity);

            // ambil ringkasan keranjang terbaru
            $summary = $this->cartService->getCartSummary($buyerId);
            
            return $this->success('Produk berhasil ditambahkan ke keranjang.', [
                'total_items' => $summary['total_items_quantity']
            ]);

        } catch (Exception $e) {
            error_log('Error di CartItemController@addToCart: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Memperbarui kuantitas item di keranjang
    public function updateQuantity($id = null) {
        $buyerId = $this->authService->getCurrentUserId();

        if ($buyerId === null) {
            return $this->error('Silakan login terlebih dahulu', 401);
        }

        $cartItemId = (int)($id ?? $_POST['cart_item_id'] ?? 0);
        $newQuantity = (int)($_POST['quantity'] ?? 0);
        
        try {
            if ($cartItemId <= 0 || $newQuantity <= 0) {
                throw new Exception('cart_item_id atau quantity tidak valid');
            }

            // Update quantity
            $this->cartService->updateItemQuantity($cartItemId, $newQuantity);

            // Ambil ringkasan keranjang terbaru
            $summary = $this->cartService->getCartSummary($buyerId);
            return $this->success('Kuantitas berhasil diperbarui', [
                'cartSummary' => $summary
            ]);

        } catch (Exception $e) {
            error_log('Error di CartItemController@updateQuantity: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Menghapus item dari keranjang
    public function removeFromCart($id) {
        $buyerId = $this->authService->getCurrentUserId();

        if ($buyerId === null) {
            return $this->error('Silakan login terlebih dahulu', 401);
        }

        $cartItemId = (int)$id;

        try {
            if ($cartItemId <= 0) {
                throw new Exception('cart_item_id tidak valid');
            }

            // Hapus item dari keranjang
            $this->cartService->removeItem($cartItemId);

            // Ambil ringkasan keranjang terbaru
            $summary = $this->cartService->getCartSummary($buyerId);
            return $this->success('Item berhasil dihapus dari keranjang', [
                'cartSummary' => $summary
            ]);
        } catch (Exception $e) {
            error_log('Error di CartItemController@removeFromCart: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}