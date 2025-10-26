<?php
class StoreController extends Controller
{
    private $storeModel;
    private $userModel;
    private $orderModel;
    private $uploadDir;

    public function __construct() {
        $this->storeModel = new Store();
        $this->userModel = new User();
        $this->orderModel = new Order();
        $this->uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/stores/';       
        // buat direktori jika belum ada
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
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

            // cek user udah punya toko toko apa blm
            $existingStore = $this->storeModel->findByUserId($userId);
            if ($existingStore) {
                return $this->error('User Sudah Memiliki Toko', 400);
            }

            // cek apakah nama toko udah dipake
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
                if ($uploadResult['success']) {
                    $logoPath = $uploadResult['path'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }

            // buat toko
            $storeData = [
                'user_id' => $userId,
                'store_name' => $storeName,
                'store_description' => $storeDescription,
                'store_logo_path' => $logoPath,
                'balance' => 0
            ];

            $storeId = $this->storeModel->createStore($storeData);

            if (!$storeId) {
                return $this->error('Gagal Membuat Toko', 500);
            }

            return $this->success('Toko Berhasil Dibuat', ['store_id' => $storeId]);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // update informasi toko
    public function update() {
        try {
            // ambil user dari session
            session_start();
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
            $storeName = $this->input('store_name');
            $storeDescription = $this->input('store_description');

            // validasi input
            if (empty($storeName)) {
                return $this->error('Nama Toko Tidak Boleh Kosong', 400);
            }

            if (strlen($storeName) > 100) {
                return $this->error('Nama Toko Maksimal 100 Karakter', 400);
            }

            // cek apakah nama toko sudah digunakan oleh toko lain
            if ($storeName !== $store['store_name']) {
                $existingName = $this->storeModel->findByStoreName($storeName);
                if ($existingName && $existingName['store_id'] != $storeId) {
                    return $this->error('Nama Toko Sudah Digunakan', 400);
                }
            }

            // sanitasi deskripsi (rich text)
            if (!empty($storeDescription)) {
                $storeDescription = $this->sanitizeRichText($storeDescription);
            }

            // handle upload logo baru
            $logoPath = $store['store_logo_path'];
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleLogoUpload($_FILES['store_logo']);
                if ($uploadResult['success']) {
                    // hapus logo lama jika ada
                    if (!empty($store['store_logo_path'])) {
                        $oldLogoPath = dirname(dirname(dirname(__DIR__))) . '/public' . $store['store_logo_path'];
                        if (file_exists($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    }
                    $logoPath = $uploadResult['path'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }

            // update data toko
            $updateData = [
                'store_name' => $storeName
            ];

            if (!empty($storeDescription)) {
                $updateData['store_description'] = $storeDescription;
            }

            if ($logoPath !== $store['store_logo_path']) {
                $updateData['store_logo_path'] = $logoPath;
            }

            $result = $this->storeModel->updateStoreInfo($storeId, $updateData);

            if (!$result) {
                return $this->error('Gagal Mengupdate Toko', 500);
            }

            // ambil data toko yang sudah diupdate
            $updatedStore = $this->storeModel->find($storeId);

            return $this->success('Toko Berhasil Diupdate', $updatedStore);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // tampil halaman detail toko publik
    public function show() {
        try {
            $storeId = $this->input('store_id');
            
            if (!$storeId) {
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $parts = explode('/', trim($uri, '/'));
                $storeId = end($parts);
            }

            if (!$storeId || !is_numeric($storeId)) {
                return $this->error('Store ID Tidak Valid', 400);
            }

            // ambil data toko beserta pemilik
            $store = $this->storeModel->getStoreWithOwner($storeId);

            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }

            // untuk render view HTML
            if (!$this->isApiRequest()) {
                return $this->view('store-detail', ['store' => $store]);
            }

            // untuk API JSON response
            return $this->success('Detail Toko', $store);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // ambil data toko milik seller yang sedang login
    public function getMyStore() {
        try {
            // ambil user_id dari session
            session_start();
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
            session_start();
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

    // render halaman dashboard seller
    public function dashboard() {
        try {
            // pastiin user adalah seller dan udah login
            session_start();
            $currentUserId = $_SESSION['user']['user_id'] ?? null;            
            if (!$currentUserId) {
                return $this->redirect('/login');
            }

            // ambil data toko
            $store = $this->storeModel->findByUserId($currentUserId);            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
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

            return $this->view('dashboard', [
                'store' => $store,
                'stats' => $stats
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

    // sanitasi rich text dari quill.js
    private function sanitizeRichText($html) {
        // daftar tag yang diizinkan dari quill.js
        $allowedTags = '<p><br><strong><em><u><s><ol><ul><li><blockquote><h1><h2><h3><a>';
        
        // strip tag yang tidak diizinkan
        $cleaned = strip_tags($html, $allowedTags);
        
        // bersihkan atribut berbahaya
        $cleaned = preg_replace('/<([^>]+) on\w+="[^"]*"/i', '<$1', $cleaned);
        
        return $cleaned;
    }

    // helper untuk cek apakah request adalah API
    private function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }
}