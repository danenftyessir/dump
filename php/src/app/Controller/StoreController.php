<?php

namespace Controller;

use Base\Controller;
use Model\Store;
use Model\Product;
use Model\Category;
use Model\Order;
use Service\AuthService;
use Service\CSRFService;
use Service\FileService;
use Service\LoggerService;
use Validator\StoreValidator;
use Exception;

class StoreController extends Controller
{
    private Store $storeModel;
    private Product $productModel;
    private Category $categoryModel;
    private AuthService $authService;
    private CSRFService $csrfService;
    private StoreValidator $validator;
    private Order $orderModel;
    private FileService $fileService;
    private LoggerService $logger;

    // Ctor
    public function __construct(Store $storeModel, Product $productModel, Category $categoryModel, AuthService $authService, CSRFService $csrfService, StoreValidator $validator, Order $orderModel, FileService $fileService, LoggerService $logger) {
        $this->storeModel = $storeModel;
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->authService = $authService;
        $this->csrfService = $csrfService;
        $this->validator = $validator;
        $this->orderModel = $orderModel;
        $this->fileService = $fileService;
        $this->logger = $logger;
    }

    // Menampilkan store detail page (buyer)
    public function show($id) {
        try {
            $storeId = $id;
            if (!$storeId || !is_numeric($storeId)) {
                return $this->error('ID Toko tidak valid', 400);
            }

            // ambil data toko
            $store = $this->storeModel->find((int)$storeId);
            if (!$store) {
                return $this->error('Toko tidak ditemukan', 404);
            }

            // ambil data untuk filter
            $categories = $this->categoryModel->all();

            // ambil data status login dan token
            $isLoggedIn = $this->authService->isLoggedIn();
            $isBuyer = $this->authService->isBuyer();
            $_token = $this->csrfService->getToken();
            $currentUser = $this->authService->getCurrentUser();

            // render view
            return $this->view('buyer/store-detail', [
                'store'         => $store,
                'categories'    => $categories,
                'isLoggedIn'    => $isLoggedIn,
                'isBuyer'       => $isBuyer,
                '_token'        => $_token,
                'currentUser'   => $currentUser
            ]);
        } catch (Exception $e) {
            error_log('Error di StoreController@show: ' . $e->getMessage());
            return $this->error('Terjadi kesalahan saat memuat halaman toko.', 500);
        }
    }

    // api untuk mengambil produk dari toko tertentu dengan filter (buyer)
    public function getStoreProducts($id) {
        try {
            $storeId = $id;
            if (!$storeId || !is_numeric($storeId)) {
                return $this->error('ID Toko tidak valid', 400);
            }

            $filters = [
                'search'      => $_GET['search'] ?? '',
                'category_id' => $_GET['category_id'] ?? null,
                'min_price'   => $_GET['min_price'] ?? null,
                'max_price'   => $_GET['max_price'] ?? null,
                'sort_by'     => $_GET['sort_by'] ?? 'created_at',
                'sort_order'  => $_GET['sort_order'] ?? 'DESC',
                'page'        => (int)($_GET['page'] ?? 1),
                'limit'       => (int)($_GET['limit'] ?? 12)
            ];

            $filters['store_id'] = (int)$storeId;

            $result = $this->productModel->getProductsForDiscovery($filters);
            return $this->success('Produk toko berhasil dimuat', $result);
        } catch (Exception $e) {
            error_log('Error di StoreController@getStoreProducts: ' . $e->getMessage());
            return $this->error('Terjadi kesalahan saat memuat produk.', 500);
        }
    }

    // Menampilkan dashboard seller (seller)
    public function dashboard() {
        try {
            $sellerId = $this->authService->getCurrentUserId();

            // ambil data toko
            $store = $this->storeModel->findByUserId($sellerId);
            if (!$store) {
                // Jika seller (karena lolos middleware) tapi belum punya toko (misal: proses registrasi gagal)
                $this->authService->setFlashMessage('error', 'Profil toko Anda tidak ditemukan.');
                return $this->redirect('/logout');
            }

            // ambil quick stats
            $stats = [
                'total_products' => $this->productModel->countProductsForSeller($store['store_id']),
                'pending_orders' => $this->orderModel->getPendingOrdersCount($store['store_id']),
                'low_stock_products' => count($this->productModel->getLowStockProducts($store['store_id'])),
                'total_revenue' => $this->orderModel->getTotalRevenue($store['store_id'])
            ];

            // render view
            return $this->view('seller/dashboard', [
                'store' => $store,
                'stats' => $stats,
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Error loading seller dashboard', ['seller_id' => $sellerId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Terjadi kesalahan saat memuat dashboard toko.');
            return $this->redirect('/'); 
        }
    }

    // API untuk update informasi toko (seller)
    public function update() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            if (!$store) {
                return $this->error('Toko tidak ditemukan', 404);
            }

            // ambil data toko
            $data = [
                'store_name' => $_POST['store_name'] ?? '',
                'store_description' => $_POST['store_description'] ?? ''
            ];
            $fileData = $_FILES['store_logo'] ?? null;

            // validasi data - validator melempar ValidationException jika gagal
            $this->validator->validateUpdate($data, $fileData, $store['store_id']);

            // handle file upload jika ada
            if ($fileData && $fileData['error'] === UPLOAD_ERR_OK) {
                // Panggil FileService untuk upload dan hapus yang lama
                $this->fileService->deleteFile($store['store_logo_path']);
                $data['store_logo_path'] = $this->fileService->handleUpload($fileData, 'logos', ['image/jpeg', 'image/png', 'image/webp'], 2*1024*1024);
            }

            // update database
            $this->storeModel->update($store['store_id'], $data);

            // Ambil data toko yang sudah diupdate untuk dikembalikan ke frontend
            $updatedStore = $this->storeModel->findByUserId($sellerId);

            return $this->success('Informasi toko berhasil diperbarui.', $updatedStore);

        } catch (\Exception\ValidationException $e) {
            // Handle validation errors
            return $this->error($e->getMessage(), 400, $e->getErrors());
        } catch (Exception $e) {
            $this->logger->logError('Error updating store', ['seller_id' => $sellerId ?? null, 'error' => $e->getMessage()]);
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}