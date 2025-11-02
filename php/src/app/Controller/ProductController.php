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
use Service\AdvancedSearchService;
use Service\CSVExportService;
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
    private AdvancedSearchService $searchService;
    private CSVExportService $csvExportService;

    // Ctor
    public function __construct(Product $productModel, Category $categoryModel, Store $storeModel, AuthService $authService, Order $orderModel, CSRFService $csrfService, LoggerService $logger, FileService $fileService, ProductValidator $productValidator, AdvancedSearchService $searchService, CSVExportService $csvExportService) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->storeModel = $storeModel;
        $this->authService = $authService;
        $this->orderModel = $orderModel;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->fileService = $fileService;
        $this->productValidator = $productValidator;
        $this->searchService = $searchService;
        $this->csvExportService = $csvExportService;
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
                return $this->error('Akses ditolak.', 403);
            }

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

            // Auto-index for advanced search
            $updatedProduct = $this->productModel->find($productId);
            $this->searchService->indexProduct(
                $productId,
                $updatedProduct['product_name'],
                $updatedProduct['description']
            );

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

            // Auto-index for advanced search
            $this->searchService->indexProduct(
                $newProduct['product_id'],
                $data['product_name'],
                $data['description']
            );

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
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // Handle both string and array params
            if (is_array($params)) {
                $productId = (int)($params['id'] ?? 0);
            } else {
                $productId = (int)$params;
            }

            if (!$productId) {
                $this->authService->setFlashMessage('error', 'ID produk tidak valid.');
                return $this->redirect('/seller/products');
            }

            $product = $this->productModel->find($productId);

            // Otorisasi: Pastikan produk milik seller ini
            if (!$product) {
                $this->authService->setFlashMessage('error', 'Produk tidak ditemukan.');
                return $this->redirect('/seller/products');
            }

            if ($product['store_id'] != $store['store_id']) {
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

    // Export products to CSV
    public function export() {
        try {
            // Middleware already checked auth & seller role, just get user ID
            $sellerId = $this->authService->getCurrentUserId();

            // Get seller's store
            $store = $this->storeModel->findByUserId($sellerId);

            if (!$store) {
                $this->authService->setFlashMessage('error', 'Toko tidak ditemukan.');
                return $this->redirect('/seller/dashboard');
            }

            // Get all products for this store (without pagination for export)
            $result = $this->productModel->getProductsForSeller($store['store_id'], [
                'page' => 1,
                'limit' => 10000  // Get all products (large limit)
            ]);
            $products = $result['products'] ?? [];

            // Add category name to each product
            foreach ($products as &$product) {
                // Get first category name if exists
                if (!empty($product['categories'])) {
                    $product['category_name'] = $product['categories'][0]['name'] ?? 'N/A';
                } else {
                    $product['category_name'] = 'N/A';
                }
            }
            unset($product); // Break reference

            // Generate CSV
            $csvContent = $this->csvExportService->exportProducts($products);

            // Generate filename with timestamp (sanitize store name for filename)
            $storeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $store['store_name']);
            $filename = 'products_' . $storeName . '_' . date('Y-m-d_His') . '.csv';

            // Send CSV download
            $this->csvExportService->downloadCSV($csvContent, $filename);

        } catch (Exception $e) {
            $this->logger->logError('Gagal export produk', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal export produk.');
            return $this->redirect('/seller/products');
        }
    }
}