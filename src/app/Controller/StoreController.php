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
            // ambil user dari session
            $currentUserId = $_SESSION['user']['user_id'] ?? null;
            
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

    // membuat toko baru saat registrasi seller
    public function create() {
        try {
            $userId = $this->input('user_id');
            $storeName = $this->input('store_name');
            $storeDescription = $this->input('store_description');

            // validasi input server-side
            if (empty($userId)) {
                return $this->error('User ID Tidak Boleh Kosong', 400);
            }

            if (empty($storeName)) {
                return $this->error('Nama Toko Tidak Boleh Kosong', 400);
            }

            if (strlen($storeName) > 100) {
                return $this->error('Nama Toko Maksimal 100 Karakter', 400);
            }

            if (empty($storeDescription)) {
                return $this->error('Deskripsi Toko Tidak Boleh Kosong', 400);
            }

            // validasi user adalah seller
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->error('User Tidak Ditemukan', 404);
            }

            if ($user['role'] !== 'SELLER') {
                return $this->error('User Harus Berperan Sebagai Seller', 403);
            }

            // cek user sudah punya toko atau belum
            $existingStore = $this->storeModel->findByUserId($userId);
            if ($existingStore) {
                return $this->error('User Sudah Memiliki Toko', 400);
            }

            // cek apakah nama toko sudah dipakai
            $existingName = $this->storeModel->findByStoreName($storeName);
            if ($existingName) {
                return $this->error('Nama Toko Sudah Digunakan', 400);
            }

            // sanitasi deskripsi toko (rich text)
            $storeDescription = $this->sanitizeRichText($storeDescription);

            // handle upload logo toko
            $logoPath = null;
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleLogoUpload($_FILES['store_logo']);
                if (!$uploadResult['success']) {
                    return $this->error($uploadResult['error'], 400);
                }
                $logoPath = $uploadResult['path'];
            }

            // buat toko baru
            $storeData = [
                'user_id' => $userId,
                'store_name' => $storeName,
                'store_description' => $storeDescription,
                'store_logo_path' => $logoPath,
                'balance' => 0
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

    // update informasi toko seller
    public function update() {
        try {
            // ambil user_id dari session
            $currentUserId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('User Tidak Terautentikasi', 401);
            }

            // ambil toko milik user
            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            // ambil data update dari request
            $storeName = $this->input('store_name');
            $storeDescription = $this->input('store_description');

            // validasi input
            if (empty($storeName)) {
                return $this->error('Nama Toko Tidak Boleh Kosong', 400);
            }

            if (strlen($storeName) > 100) {
                return $this->error('Nama Toko Maksimal 100 Karakter', 400);
            }

            if (empty($storeDescription)) {
                return $this->error('Deskripsi Toko Tidak Boleh Kosong', 400);
            }

            // cek apakah nama toko sudah dipakai oleh toko lain
            if ($storeName !== $store['store_name']) {
                $existingName = $this->storeModel->findByStoreName($storeName);
                if ($existingName) {
                    return $this->error('Nama Toko Sudah Digunakan', 400);
                }
            }

            // sanitasi deskripsi toko
            $storeDescription = $this->sanitizeRichText($storeDescription);

            // handle upload logo baru jika ada
            $logoPath = $store['store_logo_path'];
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleLogoUpload($_FILES['store_logo']);
                if (!$uploadResult['success']) {
                    return $this->error($uploadResult['error'], 400);
                }
                
                // hapus logo lama jika ada
                if ($logoPath && file_exists($this->uploadDir . basename($logoPath))) {
                    @unlink($this->uploadDir . basename($logoPath));
                }
                
                $logoPath = $uploadResult['path'];
            }

            // update data toko
            $updateData = [
                'store_name' => $storeName,
                'store_description' => $storeDescription,
                'store_logo_path' => $logoPath
            ];

            $result = $this->storeModel->update($store['store_id'], $updateData);

            if (!$result) {
                return $this->error('Gagal Mengupdate Toko', 500);
            }

            return $this->success('Toko Berhasil Diupdate', $updateData);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // ambil data toko milik seller yang sedang login
    public function getMyStore() {
        try {
            // ambil user_id dari session
            $currentUserId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('User Tidak Terautentikasi', 401);
            }

            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            return $this->success('Data Toko', $store);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // ambil statistik toko untuk dashboard
    public function getStats() {
        try {
            // ambil user dari session
            $currentUserId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('User Tidak Terautentikasi', 401);
            }

            // ambil toko milik user
            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            $storeId = $store['store_id'];

            // ambil statistik produk
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

            return $this->success('Statistik Toko', $stats);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // tampilkan detail toko (public)
    public function show() {
        try {
            $storeId = $this->input('id');
            
            if (!$storeId) {
                return $this->error('Store ID Tidak Valid', 400);
            }

            // ambil data toko dengan informasi owner
            $store = $this->storeModel->getStoreWithOwner($storeId);
            
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

    // handle upload logo toko
    private function handleLogoUpload($file) {
        // validasi error upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload Gagal'];
        }

        // validasi ukuran file (max 2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Ukuran File Maksimal 2MB'];
        }

        // validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Tipe File Harus JPG, PNG, atau WEBP'];
        }

        // generate nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('store_logo_') . '.' . $extension;
        $filepath = $this->uploadDir . $filename;

        // pindahkan file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Gagal Menyimpan File'];
        }

        // return path relatif untuk disimpan di database
        return ['success' => true, 'path' => '/uploads/stores/' . $filename];
    }

    // sanitasi rich text dari quill editor
    private function sanitizeRichText($html) {
        // TODO: implementasi sanitasi HTML yang lebih ketat
        // untuk sementara gunakan strip_tags dengan whitelist tag
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3>';
        return strip_tags($html, $allowedTags);
    }
}