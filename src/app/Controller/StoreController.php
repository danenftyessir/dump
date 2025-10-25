<?php
class StoreController extends Controller
{
    private $storeModel;
    private $userModel;
    private $uploadDir;

    public function __construct() {
        $this->storeModel = new Store();
        $this->userModel = new User();
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

            // siapkan data untuk disimpan
            $storeData = [
                'user_id' => $userId,
                'store_name' => $storeName,
                'store_description' => $storeDescription,
                'store_logo_path' => $logoPath,
                'balance' => 0
            ];

            // buat toko baru
            $store = $this->storeModel->create($storeData);
            if ($store) {
                return $this->success('Toko Berhasil Dibuat', $store);
            } else {
                if ($logoPath && file_exists($logoPath)) {
                    unlink($logoPath);
                }
                return $this->error('Gagal Membuat Toko', 500);
            }
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // Update informasi toko dari dashboard
    public function update() {
        try {
            // TO DO di autentikasinya buat nyimpen data user ke SESSION saat login, ngambil data user dari SESSION di controller, buat middleware untuk validasi authentication dan role
            // Ambil user_id dari session
            session_start();
            $currentUserId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$currentUserId) {
                return $this->error('User Tidak Terautentikasi', 401);
            }
            $store = $this->storeModel->findByUserId($currentUserId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }
            $updateData = [];
            
            // update nama toko
            if ($this->input('store_name')) {
                $storeName = trim($this->input('store_name'));
                
                // validasi panjang nama
                if (strlen($storeName) > 100) {
                    return $this->error('Nama Toko Maksimal 100 Karakter', 400);
                }

                // cek keunikan nama toko
                $existingStore = $this->storeModel->findByStoreName($storeName);
                if ($existingStore && $existingStore['store_id'] != $store['store_id']) {
                    return $this->error('Nama Toko Sudah Digunakan', 400);
                }

                $updateData['store_name'] = $storeName;
            }
            
            // update deskripsi toko
            if ($this->input('store_description')) {
                $description = $this->input('store_description');
                
                // sanitasi rich text untuk keamanan
                $description = $this->sanitizeRichText($description);
                
                if (strlen($description) < 10) {
                    return $this->error('Deskripsi Toko Minimal 10 Karakter', 400);
                }

                $updateData['store_description'] = $description;
            }

            // upload logo baru
            if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleLogoUpload($_FILES['store_logo'], $store['store_logo_path']);                
                if ($uploadResult['success']) {
                    $updateData['store_logo_path'] = $uploadResult['path'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }

            if (empty($updateData)) {
                return $this->error('Tidak Ada Data Yang Diupdate', 400);
            }

            // update toko
            $updatedStore = $this->storeModel->update($store['store_id'], $updateData);

            if ($updatedStore) {
                return $this->success('Informasi Toko Berhasil Diupdate', $updatedStore);
            } else {
                return $this->error('Gagal Mengupdate Toko', 500);
            }

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

            // TO DO buat order management, implementasi buat dapetin:
            // jumlah order dengan status waiting_approval dan total pendapatan dari order yang udah received
            $pendingOrders = 0; // sementara
            $totalRevenue = 0; // sementara

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
            $stats = [
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'pending_orders' => 0, // TO DO: implement
                'total_revenue' => 0 // TO DO: implement
            ];

            return $this->view('dashboard', [
                'store' => $store,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // handle upload logo toko dengan validasi keamanan
    private function handleLogoUpload($file, $oldLogoPath = null) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'Gagal Upload File'
            ];
        }

        // validasi ukuran file (max 2MB)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'Ukuran File Maksimal 2MB'
            ];
        }

        // validasi tipe file berdasarkan MIME type
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return [
                'success' => false,
                'error' => 'Format File Harus JPG, JPEG, PNG, Atau WEBP'
            ];
        }

        // validasi ekstensi file
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            return [
                'success' => false,
                'error' => 'Ekstensi File Tidak Valid'
            ];
        }
        // generate nama file unik untuk menghindari collision
        $uniqueName = uniqid('store_logo_', true) . '.' . $fileExtension;
        $uploadPath = $this->uploadDir . $uniqueName;
        // pindahkan file ke direktori upload
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // hapus file logo lama jika ada
            if ($oldLogoPath && file_exists($oldLogoPath)) {
                unlink($oldLogoPath);
            }
            // return path relatif untuk disimpan di database
            return [
                'success' => true,
                'path' => '/public/uploads/stores/' . $uniqueName
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Gagal Menyimpan File'
            ];
        }
    }

    // sanitasi rich text untuk mencegah XSS
    private function sanitizeRichText($html) {
        // whitelist tag HTML yang diperbolehkan oleh Quill.js
        $allowedTags = '<p><br><strong><em><u><s><h1><h2><h3><ul><ol><li><blockquote><a><code><pre>';
        
        // strip tag yang tidak diperbolehkan
        $cleaned = strip_tags($html, $allowedTags);
        
        // hapus semua script tags, event handlers, dan javascript protocol
        $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleaned);
        $cleaned = preg_replace('/<script\b[^>]*\/>/is', '', $cleaned);        
        $cleaned = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleaned);
        $cleaned = preg_replace('/\s*on\w+\s*=\s*\S+/i', '', $cleaned);        
        $cleaned = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href="#"', $cleaned);
        $cleaned = preg_replace('/src\s*=\s*["\']?\s*javascript:/i', 'src="#"', $cleaned);
        
        // hapus data protocol
        $cleaned = preg_replace('/href\s*=\s*["\']?\s*data:/i', 'href="#"', $cleaned);
        $cleaned = preg_replace('/src\s*=\s*["\']?\s*data:/i', 'src="#"', $cleaned);
        
        // sanitasi atribut href untuk link (hanya allow http, https, mailto)
        $cleaned = preg_replace_callback(
            '/<a\s+([^>]*?)href\s*=\s*["\']([^"\']*)["\']([^>]*?)>/i',
            function($matches) {
                $url = $matches[2];
                // Allow hanya http, https, mailto
                if (preg_match('/^(https?:\/\/|mailto:)/i', $url) || $url[0] === '/') {
                    return '<a ' . $matches[1] . 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $matches[3] . '>';
                }
                // Block URL berbahaya
                return '<a ' . $matches[1] . 'href="#"' . $matches[3] . '>';
            },
            $cleaned
        );
        
        // sanitasi atribut class (Quill dgn ql-* classes)
        $cleaned = preg_replace('/class\s*=\s*["\']([^"\']*)["\']/', '', $cleaned);        
        $cleaned = preg_replace('/style\s*=\s*["\'][^"\']*["\']/', '', $cleaned);
        $cleaned = trim($cleaned);
        
        return $cleaned;
    }

    //  untuk cek apakah request adalah API request
    private function isApiRequest() {
        $contentType = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }
}