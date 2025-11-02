<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Model\Category;
use Service\AuthService;
use Service\CSRFService;
use Exception;

class ProductDiscoveryController extends Controller
{
    private Product $productModel;
    private Category $categoryModel;
    private AuthService $authService;
    private CSRFService $csrfService;

    // Ctor
    public function __construct(Product $productModel, Category $categoryModel, AuthService $authService, CSRFService $csrfService) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->authService = $authService;
        $this->csrfService = $csrfService;
    }

    // Home / Produk Discovery Page
    public function index() {
        try {
            // ambil semua kategori untuk filter
            $categories = $this->categoryModel->all();
            
            // cek status login
            $isLoggedIn = $this->authService->isLoggedIn();
            $isBuyer = $this->authService->isBuyer();

            // render view
            return $this->view('buyer/home', [ # Home/product discovery page
                'categories' => $categories,
                'isLoggedIn' => $isLoggedIn,
                'isBuyer'    => $isBuyer,
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
                'search'        => $_GET['search'] ?? null,
                'category_id'   => $_GET['category_id'] ?? null,
                'min_price'     => $_GET['min_price'] ?? null,
                'max_price'     => $_GET['max_price'] ?? null,
                'sort_by'       => $_GET['sort_by'] ?? 'newest',
                'sort_order'    => $_GET['sort_order'] ?? 'desc',
                'page'          => (int)($_GET['page'] ?? 1),
                'limit'         => (int)($_GET['limit'] ?? 10)
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

            // render view
            return $this->view('buyer/product-detail', [
                'product' => $product,
                'isLoggedIn' => $isLoggedIn,
                'isBuyer' => $isBuyer,
                'csrfToken' => $csrfToken
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
            $keyword = $this->input('q', '');
            
            if (strlen($keyword) < 2) {
                return $this->success('Search Suggestions', ['suggestions' => []]);
            }
            
            // query untuk mendapatkan suggestions
            $sql = "SELECT DISTINCT product_name 
                    FROM products 
                    WHERE product_name LIKE :keyword 
                    AND stock > 0
                    ORDER BY product_name ASC 
                    LIMIT 10";
            
            $db = $this->productModel->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->execute();
            
            $suggestions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            return $this->success('Search Suggestions', ['suggestions' => $suggestions]);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}