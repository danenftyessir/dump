<?php

namespace Controller;

use Base\Controller;
use Model\Product;
use Model\Category;
use Model\Store;
use Exception;

class ProductController extends Controller
{
    private $productModel;
    private $categoryModel;
    private $storeModel;
    private $uploadDir;

    // constructor dengan dependency injection
    public function __construct(Product $productModel, Category $categoryModel, Store $storeModel) {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->storeModel = $storeModel;
        $this->uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/products/';
        
        // buat direktori jika belum ada dengan suppress warning
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    // halaman product management seller
    public function index() {
        // ambil user id dari session
        // NOTE: session structure adalah flat: $_SESSION['user_id']
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? 'User';
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        // FALLBACK LOGIC: jika seller belum punya toko, auto-create
        if (!$store) {
            try {
                // buat toko otomatis menggunakan nama dari session
                $storeData = [
                    'user_id' => $userId,
                    'store_name' => $userName . "'s Store",
                    'store_description' => 'Selamat Datang Di Toko Saya!',
                    'store_logo_path' => null,
                    'balance' => 0
                ];
                
                $store = $this->storeModel->create($storeData);
                
                if (!$store) {
                    return $this->error('Gagal Membuat Toko. Silakan Hubungi Administrator.', 500);
                }
                
            } catch (Exception $e) {
                return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
            }
        }
        
        // ambil semua kategori untuk filter
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/products', [
            'store' => $store,
            'categories' => $categories
        ]);
    }
    
    // API untuk mendapatkan produk seller dengan filter
    public function getSellerProducts() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_name'] ?? 'User';
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            // FALLBACK LOGIC: auto-create toko jika belum ada
            if (!$store) {
                try {
                    // buat toko otomatis menggunakan nama dari session
                    $storeData = [
                        'user_id' => $userId,
                        'store_name' => $userName . "'s Store",
                        'store_description' => 'Selamat Datang Di Toko Saya!',
                        'store_logo_path' => null,
                        'balance' => 0
                    ];
                    
                    $store = $this->storeModel->create($storeData);
                    
                    if (!$store) {
                        return $this->error('Gagal Membuat Toko', 500);
                    }
                    
                } catch (Exception $e) {
                    return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
                }
            }
            
            // ambil filter dari query string
            $filters = [
                'search' => $_GET['search'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'sort_by' => $_GET['sort_by'] ?? 'created_at',
                'sort_order' => $_GET['sort_order'] ?? 'DESC',
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)($_GET['limit'] ?? 10)
            ];
            
            $result = $this->productModel->getProductsForSeller($store['store_id'], $filters);
            
            return $this->success('Produk Berhasil Diambil', $result);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman add product
    public function create() {
        // ambil user id dari session
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? 'User';
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        // FALLBACK LOGIC: auto-create toko jika belum ada
        if (!$store) {
            try {
                // buat toko otomatis menggunakan nama dari session
                $storeData = [
                    'user_id' => $userId,
                    'store_name' => $userName . "'s Store",
                    'store_description' => 'Selamat Datang Di Toko Saya!',
                    'store_logo_path' => null,
                    'balance' => 0
                ];
                
                $store = $this->storeModel->create($storeData);
                
                if (!$store) {
                    return $this->error('Gagal Membuat Toko', 500);
                }
                
            } catch (Exception $e) {
                return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
            }
        }
        
        // ambil semua kategori
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/add-product', [
            'store' => $store,
            'categories' => $categories
        ]);
    }

    // API untuk menyimpan produk baru
    public function store() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_name'] ?? 'User';
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            // FALLBACK LOGIC: auto-create toko jika belum ada
            if (!$store) {
                try {
                    // buat toko otomatis menggunakan nama dari session
                    $storeData = [
                        'user_id' => $userId,
                        'store_name' => $userName . "'s Store",
                        'store_description' => 'Selamat Datang Di Toko Saya!',
                        'store_logo_path' => null,
                        'balance' => 0
                    ];
                    
                    $store = $this->storeModel->create($storeData);
                    
                    if (!$store) {
                        return $this->error('Gagal Membuat Toko', 500);
                    }
                    
                } catch (Exception $e) {
                    return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
                }
            }

            // ambil data input
            $productName = $_POST['product_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $stock = $_POST['stock'] ?? 0;
            $categoryIds = $_POST['category_ids'] ?? [];

            // validasi input
            if (empty($productName)) {
                return $this->error('Nama Produk Wajib Diisi', 400);
            }

            if ($price <= 0) {
                return $this->error('Harga Harus Lebih Dari 0', 400);
            }

            if ($stock < 0) {
                return $this->error('Stok Tidak Boleh Negatif', 400);
            }

            // handle upload image
            $imagePath = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['main_image']);
            }

            // buat produk baru
            $productData = [
                'store_id' => $store['store_id'],
                'product_name' => $productName,
                'description' => $description,
                'price' => (int)$price,
                'stock' => (int)$stock,
                'main_image_path' => $imagePath
            ];

            $newProduct = $this->productModel->create($productData);

            if (!$newProduct) {
                return $this->error('Gagal Membuat Produk', 500);
            }

            // tambahkan kategori jika ada
            if (!empty($categoryIds) && is_array($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    $this->productModel->addCategory($newProduct['product_id'], $categoryId);
                }
            }

            return $this->success('Produk Berhasil Ditambahkan', $newProduct);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman edit product
    public function edit() {
        // ambil user id dari session
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil product id dari query string
        $productId = $_GET['id'] ?? null;
        
        if (!$productId) {
            return $this->error('Product ID Tidak Valid', 400);
        }
        
        // ambil data produk
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            return $this->error('Produk Tidak Ditemukan', 404);
        }
        
        // validasi bahwa produk milik seller ini
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store || $product['store_id'] != $store['store_id']) {
            return $this->error('Unauthorized', 403);
        }
        
        // ambil kategori produk
        $productCategories = $this->productModel->getCategories($productId);
        
        // ambil semua kategori
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/edit-product', [
            'product' => $product,
            'productCategories' => $productCategories,
            'categories' => $categories,
            'store' => $store
        ]);
    }

    // API untuk update produk
    public function update() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            // ambil product id
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Product ID Tidak Valid', 400);
            }
            
            // ambil data produk
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }
            
            // validasi bahwa produk milik seller ini
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store || $product['store_id'] != $store['store_id']) {
                return $this->error('Unauthorized', 403);
            }

            // ambil data update
            $updateData = [];
            
            if (isset($_POST['product_name'])) {
                $updateData['product_name'] = $_POST['product_name'];
            }
            
            if (isset($_POST['description'])) {
                $updateData['description'] = $_POST['description'];
            }
            
            if (isset($_POST['price'])) {
                $updateData['price'] = $_POST['price'];
            }
            
            if (isset($_POST['stock'])) {
                $updateData['stock'] = $_POST['stock'];
            }

            // handle upload image baru jika ada
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['main_image']);
                $updateData['main_image_path'] = $imagePath;
                
                // hapus image lama jika ada
                if (!empty($product['main_image_path']) && file_exists($this->uploadDir . basename($product['main_image_path']))) {
                    @unlink($this->uploadDir . basename($product['main_image_path']));
                }
            }

            // update produk
            $result = $this->productModel->update($productId, $updateData);

            if (!$result) {
                return $this->error('Gagal Mengupdate Produk', 500);
            }

            // update kategori jika ada
            if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
                // hapus kategori lama
                $this->productModel->removeAllCategories($productId);
                
                // tambah kategori baru
                foreach ($_POST['category_ids'] as $categoryId) {
                    $this->productModel->addCategory($productId, $categoryId);
                }
            }

            return $this->success('Produk Berhasil Diupdate', $result);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk delete produk
    public function delete() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            // ambil product id dari body request
            $input = json_decode(file_get_contents('php://input'), true);
            $productId = $input['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('Product ID Tidak Valid', 400);
            }
            
            // ambil data produk
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }
            
            // validasi bahwa produk milik seller ini
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store || $product['store_id'] != $store['store_id']) {
                return $this->error('Unauthorized', 403);
            }

            // soft delete produk
            $result = $this->productModel->softDelete($productId);

            if (!$result) {
                return $this->error('Gagal Menghapus Produk', 500);
            }

            return $this->success('Produk Berhasil Dihapus', null);

        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // helper function untuk handle upload image
    private function handleImageUpload($file) {
        // validasi file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Format File Tidak Didukung. Gunakan JPG, PNG, Atau GIF');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('Ukuran File Terlalu Besar. Maksimal 5MB');
        }

        // generate nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . uniqid() . '.' . $extension;
        $targetPath = $this->uploadDir . $filename;

        // upload file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Gagal Mengupload File');
        }

        // return relative path
        return '/uploads/products/' . $filename;
    }
}