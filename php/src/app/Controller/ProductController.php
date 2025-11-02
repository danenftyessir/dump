<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Model\Category;
use Model\Store;
use Model\Order;
use Service\AuthService;
use Service\CSRFService;
use Service\FileService;
use Service\LoggerService;
use Validator\ProductValidator;
use Exception\ValidationException;
use Exception;

class ProductController extends Controller
{
    private Product $productModel;
    private Category $categoryModel;
    private Store $storeModel;
    private AuthService $authService;
    private Order $orderModel;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private FileService $fileService;
    private ProductValidator $productValidator;

    // Ctor
    public function __construct(Product $productModel, Category $categoryModel, Store $storeModel, AuthService $authService, Order $orderModel, CSRFService $csrfService, LoggerService $logger, FileService $fileService, ProductValidator $productValidator) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->storeModel = $storeModel;
        $this->authService = $authService;
        $this->orderModel = $orderModel;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->fileService = $fileService;
        $this->productValidator = $productValidator;
    }

    // Menampilkan halaman product management(seller)
    public function index() {
        try {
            $categories = $this->categoryModel->all();

            return $this->view('seller/products', [
                'categories' => $categories,
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Gagal memuat halaman Kelola Produk', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat halaman.');
            return $this->redirect('/seller/dashboard');
        }
    }
    
    // api untuk mengambil daftar produk(seller)
    public function getProducts() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // Check if store exists
            if (!$store) {
                $this->logger->logError('Toko tidak ditemukan untuk seller', ['user_id' => $sellerId]);
                return $this->error('Toko tidak ditemukan. Silakan buat toko terlebih dahulu.', 404);
            }

            $filters = [
                'search' => $_GET['search'] ?? '',
                'category_id' => $_GET['category_id'] ?? null,
                'sort_by' => $_GET['sort_by'] ?? 'created_at',
                'sort_order' => $_GET['sort_order'] ?? 'DESC',
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 10)
            ];

            $result = $this->productModel->getProductsForSeller($store['store_id'], $filters);

            return $this->success('Produk berhasil dimuat', $result);
        } catch (Exception $e) {
            $this->logger->logError('Gagal mengambil API produk seller', ['error' => $e->getMessage()]);
            return $this->error('Gagal memuat data produk.', 500);
        }
    }

    // api untuk menambah produk(seller)
    public function create() {
        try {
            $categories = $this->categoryModel->all();
            return $this->view('seller/add-product', [
                'categories' => $categories,
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Gagal memuat form tambah produk', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat halaman.');
            return $this->redirect('/seller/products');
        }
    }

    // api untuk menghapus produk(seller)
    public function delete($params) {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // Handle both string and array params
            if (is_array($params)) {
                $productId = (int)($params['id'] ?? 0);
            } else {
                $productId = (int)$params;
            }

            $product = $this->productModel->find($productId);
            if (!$product || $product['store_id'] != $store['store_id']) {
                return $this->error('Produk tidak ditemukan atau Anda tidak memiliki akses.', 404);
            }

            if ($this->orderModel->isProductInPendingOrders($productId)) {
                return $this->error('Produk ini tidak bisa dihapus karena ada di pesanan yang sedang diproses.', 400);
            }

            $this->productModel->softDelete($productId);

            return $this->success('Produk berhasil diarsipkan.');
        } catch (Exception $e) {
            $this->logger->logError('Gagal menghapus produk', ['error' => $e->getMessage()]);
            return $this->error('Gagal menghapus produk.', 500);
        }
    }

    // api untuk mengupdate produk(seller)
    public function update($params) {
        try {
            error_log('=== UPDATE PRODUCT DEBUG ===');
            error_log('Params: ' . json_encode($params));
            error_log('POST data: ' . json_encode(array_keys($_POST)));
            error_log('FILES data: ' . json_encode(array_keys($_FILES)));

            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // Handle both string and array params
            if (is_array($params)) {
                $productId = (int)($params['id'] ?? 0);
            } else {
                $productId = (int)$params;
            }

            error_log('Product ID: ' . $productId);

            $product = $this->productModel->find($productId);
            if (!$product || $product['store_id'] != $store['store_id']) {
                error_log('ERROR: Product not found or access denied');
                return $this->error('Akses ditolak.', 403);
            }

            error_log('Product found, proceeding with update...');

            $data = $_POST;
            $fileData = $_FILES['main_image'] ?? null;

            $this->productValidator->validate($data, $fileData, $productId);

            $updateData = [
                'product_name' => $data['product_name'],
                'description' => $data['description'],
                'price' => (int)$data['price'],
                'stock' => (int)$data['stock'],
            ];

            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                $this->fileService->deleteFile($product['main_image_path']);
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $updateData['main_image_path'] = $this->fileService->handleUpload($fileData, 'products', $allowedTypes, 2*1024*1024);
            }

            $this->productModel->beginTransaction();
            
            $this->productModel->update($productId, $updateData);
            
            $this->productModel->removeAllCategories($productId);
            if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    $this->productModel->addCategory($productId, (int)$categoryId);
                }
            }
            
            $this->productModel->commit();

            return $this->success('Produk berhasil diperbarui.');

        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->getErrors());
        } catch (Exception $e) {
            $this->productModel->rollback();
            $this->logger->logError('Gagal update produk', ['product_id' => $productId, 'error' => $e->getMessage()]);
            return $this->error('Gagal update produk: ' . $e->getMessage(), 500);
        }
    }

    // api untuk menyimpan produk baru(seller)
    public function store() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // debug log
            error_log('=== PRODUCT STORE DEBUG ===');
            error_log('POST data: ' . json_encode(array_keys($_POST)));
            error_log('FILES data: ' . json_encode(array_keys($_FILES)));
            error_log('category_ids: ' . json_encode($_POST['category_ids'] ?? 'NOT SET'));
            error_log('main_image error: ' . ($_FILES['main_image']['error'] ?? 'NOT SET'));

            // validasi input
            $this->productValidator->validate($_POST, $_FILES['main_image'] ?? null, false);

            $data = $_POST;

            // handle upload image
            $imagePath = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->fileService->handleUpload($_FILES['main_image'], 'products', ['image/jpeg', 'image/png', 'image/webp'], 2*1024*1024);
            }

            $this->productModel->beginTransaction();
            
            // buat produk baru
            $newProduct = $this->productModel->create([
                'store_id' => $store['store_id'],
                'product_name' => $data['product_name'],
                'description' => $data['description'],
                'price' => (int)$data['price'],
                'stock' => (int)$data['stock'],
                'main_image_path' => $imagePath
            ]);
            
            // tambahkan kategori
            foreach ($data['category_ids'] as $categoryId) {
                $this->productModel->addCategory($newProduct['product_id'], (int)$categoryId);
            }

            $this->productModel->commit();

            return $this->success('Produk berhasil ditambahkan', $newProduct);
        } catch (ValidationException $ve) {
            return $this->error('Validasi gagal', 400, $ve->getErrors());
        } catch (Exception $e) {
            $this->productModel->rollback();
            $this->logger->logError('Gagal menyimpan produk baru', ['error' => $e->getMessage()]);
            return $this->error('Gagal menyimpan produk.', 500);
        }
    }

    // menampilkan form edit produk(seller)
    public function edit($params) {
        try {
            error_log('=== EDIT PRODUCT DEBUG ===');
            error_log('Params: ' . json_encode($params));
            error_log('Params type: ' . gettype($params));

            $sellerId = $this->authService->getCurrentUserId();
            error_log('Seller ID: ' . $sellerId);

            $store = $this->storeModel->findByUserId($sellerId);
            error_log('Store: ' . json_encode($store));

            // Handle both string and array params
            if (is_array($params)) {
                $productId = (int)($params['id'] ?? 0);
            } else {
                $productId = (int)$params;
            }
            error_log('Product ID: ' . $productId);

            if (!$productId) {
                error_log('ERROR: Product ID is 0 or invalid');
                $this->authService->setFlashMessage('error', 'ID produk tidak valid.');
                return $this->redirect('/seller/products');
            }

            $product = $this->productModel->find($productId);
            error_log('Product found: ' . json_encode($product));

            // Otorisasi: Pastikan produk milik seller ini
            if (!$product) {
                error_log('ERROR: Product not found');
                $this->authService->setFlashMessage('error', 'Produk tidak ditemukan.');
                return $this->redirect('/seller/products');
            }

            if ($product['store_id'] != $store['store_id']) {
                error_log('ERROR: Store ID mismatch. Product store: ' . $product['store_id'] . ', User store: ' . $store['store_id']);
                $this->authService->setFlashMessage('error', 'Anda tidak memiliki akses ke produk ini.');
                return $this->redirect('/seller/products');
            }

            $categories = $this->categoryModel->all();
            $productCategories = $this->productModel->getProductCategories($productId);

            return $this->view('seller/edit-product', [
                'product' => $product,
                'categories' => $categories,
                'productCategories' => $productCategories,
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Gagal memuat form edit produk', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat halaman.');
            return $this->redirect('/seller/products');
        }
    }
}