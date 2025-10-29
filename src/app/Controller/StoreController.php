<?php

namespace Controller;

use Base\Controller;
use Model\Store;
use Model\User;
use Model\Order;
use Exception;

class StoreController extends Controller
{
    private $storeModel;
    private $userModel;
    private $orderModel;
    private $uploadDir;

    // constructor dengan dependency injection
    public function __construct(Store $storeModel, User $userModel, Order $orderModel) {
        $this->storeModel = $storeModel;
        $this->userModel = $userModel;
        $this->orderModel = $orderModel;
        $this->uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/stores/';
        
        // buat direktori jika belum ada dengan suppress warning
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    // render halaman dashboard seller
    public function dashboard() {
        try {
            // ambil user id dari session
            // NOTE: session structure adalah flat: $_SESSION['user_id'], bukan $_SESSION['user']['user_id']
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->redirect('/login');
            }

            // ambil data toko
            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                // seller belum punya toko, redirect ke halaman buat toko
                // TODO: buat halaman untuk create store pertama kali
                return $this->error('Toko Tidak Ditemukan. Silakan Buat Toko Terlebih Dahulu.', 404);
            }

            // ambil statistik
            $storeId = $store['store_id'];
            $totalProducts = $this->storeModel->getTotalProducts($storeId);
            $lowStockProducts = $this->storeModel->getLowStockProducts($storeId, 10);
            
            // ambil statistik order menggunakan orderModel
            $pendingOrders = $this->orderModel->getPendingOrdersCount($storeId);
            $totalRevenue = $this->orderModel->getTotalRevenue($storeId);
            
            $stats = [
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'pending_orders' => $pendingOrders,
                'total_revenue' => $totalRevenue
            ];

            // render view dashboard seller
            return $this->view('seller/dashboard', [
                'store' => $store,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk mendapatkan data toko seller yang sedang login
    public function getMyStore() {
        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('Unauthorized', 401);
            }

            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            return $this->success('Data Toko Berhasil Diambil', $store);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk membuat toko baru
    public function create() {
        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('Unauthorized', 401);
            }

            // cek apakah seller sudah punya toko
            $existingStore = $this->storeModel->findByUserId($currentUserId);
            if ($existingStore) {
                return $this->error('Anda Sudah Memiliki Toko', 400);
            }

            // ambil data input
            $storeName = $_POST['store_name'] ?? '';
            $storeDescription = $_POST['store_description'] ?? '';

            // validasi
            if (empty($storeName)) {
                return $this->error('Nama Toko Wajib Diisi', 400);
            }

            // handle upload logo jika ada
            $logoPath = null;
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->handleLogoUpload($_FILES['store_logo']);
            }

            // buat toko baru
            $storeData = [
                'user_id' => $currentUserId,
                'store_name' => $storeName,
                'store_description' => $storeDescription,
                'store_logo_path' => $logoPath
            ];

            $newStore = $this->storeModel->create($storeData);

            if (!$newStore) {
                return $this->error('Gagal Membuat Toko', 500);
            }

            return $this->success('Toko Berhasil Dibuat', $newStore);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk update informasi toko
    public function update() {
        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('Unauthorized', 401);
            }

            // ambil data toko
            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            // ambil data input
            $updateData = [];
            
            if (isset($_POST['store_name'])) {
                $updateData['store_name'] = $_POST['store_name'];
            }
            
            if (isset($_POST['store_description'])) {
                $updateData['store_description'] = $_POST['store_description'];
            }

            // handle upload logo baru jika ada
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = $this->handleLogoUpload($_FILES['store_logo']);
                $updateData['store_logo_path'] = $logoPath;
                
                // hapus logo lama jika ada
                if (!empty($store['store_logo_path']) && file_exists($this->uploadDir . basename($store['store_logo_path']))) {
                    @unlink($this->uploadDir . basename($store['store_logo_path']));
                }
            }

            if (empty($updateData)) {
                return $this->error('Tidak Ada Data yang Diubah', 400);
            }

            // update toko
            $updated = $this->storeModel->update($store['store_id'], $updateData);

            if (!$updated) {
                return $this->error('Gagal Mengupdate Toko', 500);
            }

            return $this->success('Toko Berhasil Diupdate');

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman detail toko untuk public
    public function show() {
        try {
            $storeId = $_GET['id'] ?? null;
            
            if (!$storeId) {
                return $this->error('Store ID Tidak Valid', 400);
            }

            $store = $this->storeModel->find($storeId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            // render view detail toko
            return $this->view('store/detail', [
                'store' => $store
            ]);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk mendapatkan statistik dashboard
    public function getStats() {
        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('Unauthorized', 401);
            }

            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            $storeId = $store['store_id'];
            
            $stats = [
                'total_products' => $this->storeModel->getTotalProducts($storeId),
                'low_stock_products' => $this->storeModel->getLowStockProducts($storeId, 10),
                'pending_orders' => $this->orderModel->getPendingOrdersCount($storeId),
                'total_revenue' => $this->orderModel->getTotalRevenue($storeId)
            ];

            return $this->success('Statistik Berhasil Diambil', $stats);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // helper function untuk handle upload logo
    private function handleLogoUpload($file) {
        // validasi file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Format File Tidak Didukung. Gunakan JPG, PNG, atau GIF');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('Ukuran File Terlalu Besar. Maksimal 2MB');
        }

        // generate nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'store_' . uniqid() . '.' . $extension;
        $targetPath = $this->uploadDir . $filename;

        // upload file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Gagal Mengupload File');
        }

        // return relative path
        return '/uploads/stores/' . $filename;
    }
}