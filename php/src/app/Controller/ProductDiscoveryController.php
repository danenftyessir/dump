<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Model\Category;
use Service\AuthService;
use Service\CSRFService;
use Core\Request;
use Exception;

class ProductDiscoveryController extends Controller
{
    private Product $productModel;
    private Category $categoryModel;
    private AuthService $authService;
    private CSRFService $csrfService;
    private Request $request;

    // Ctor
    public function __construct(Product $productModel, Category $categoryModel, AuthService $authService, CSRFService $csrfService, Request $request) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->authService = $authService;
        $this->csrfService = $csrfService;
        $this->request = $request;
    }

    // Home / Produk Discovery Page
    public function index() {
        try {
            // ambil semua kategori untuk filter
            $categories = $this->categoryModel->all();
            
            // cek status login
            $isLoggedIn = $this->authService->isLoggedIn();
            $isBuyer = $this->authService->isBuyer();
            $currentUser = $this->authService->getCurrentUser();

            // render view
            return $this->view('buyer/home', [ // Home/product discovery page
                'categories' => $categories,
                'isLoggedIn' => $isLoggedIn,
                'isBuyer'    => $isBuyer,
                'currentUser'=> $currentUser,
                '_token'     => $this->csrfService->getToken(),
            ]);
            
        } catch (Exception $e) {
            error_log('Error di ProductDiscoveryController@index: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Mendapatkan daftar produk dengan filter
    public function getProducts() {
        try {
            $filters = [
                'search'        => $this->request->get('search'),
                'category_id'   => $this->request->get('category_id'),
                'min_price'     => $this->request->get('min_price'),
                'max_price'     => $this->request->get('max_price'),
                'sort_by'       => $this->request->get('sort_by', 'newest'),
                'sort_order'    => $this->request->get('sort_order', 'desc'),
                'page'          => (int)($this->request->get('page', 1)),
                'limit'         => (int)($this->request->get('limit', 10))
            ];

            $result = $this->productModel->getProductsForDiscovery($filters);

            return $this->success('Daftar produk berhasil dimuat', $result);
        } catch (Exception $e) {
            error_log('Error di ProductDiscoveryController@getProducts: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan saat memuat produk: ' . $e->getMessage(), 500);
        }
    }

    // Halaman detail produk
    public function showProduct($id) {
        try {
            // ambil product_id (now receives as direct parameter)
            $productId = $id;

            if (!$productId || !is_numeric($productId)) {
                return $this->error('ID produk tidak valid', 400);
            }

            // ambil detail produk
            $product = $this->productModel->getProductWithStore($productId);

            if (!$product) {
                return $this->error('Produk tidak ditemukan', 404);
            }

            // cek status login
            $isLoggedIn = $this->authService->isLoggedIn();
            $isBuyer = $this->authService->isBuyer();
            $csrfToken = $this->csrfService->getToken();
            $currentUser = $this->authService->getCurrentUser();
            
            // Debug CSRF token
            error_log('=== ProductDiscoveryController@showProduct DEBUG ===');
            error_log('CSRF Token generated: ' . substr($csrfToken, 0, 20) . '...');
            error_log('Session ID: ' . session_id());
            error_log('Is logged in: ' . ($isLoggedIn ? 'yes' : 'no'));
            error_log('Is buyer: ' . ($isBuyer ? 'yes' : 'no'));

            // render view
            return $this->view('buyer/product-detail', [
                'product' => $product,
                'isLoggedIn' => $isLoggedIn,
                'isBuyer' => $isBuyer,
                '_token' => $csrfToken,
                'csrfToken' => $csrfToken,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log('Error di ProductDiscoveryController@showProduct: ' . $e->getMessage());
            return $this->error('Terjadi Kesalahan saat memuat detail produk: ' . $e->getMessage(), 500);
        }
    }

    // ----------------------------------------------------------------------------------------------------------------------------

    // API untuk search suggestions - BONUS
    public function getSearchSuggestions() {
        try {
            $keyword = $this->request->get('q', '');
            
            if (strlen($keyword) < 2) {
                return $this->success('Search Suggestions', ['suggestions' => []]);
            }
            
            // ambil suggestions dari model
            $suggestions = $this->productModel->getProductSuggestions($keyword, 10);
            
            return $this->success('Search Suggestions', ['suggestions' => $suggestions]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}