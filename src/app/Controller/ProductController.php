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
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('toko tidak ditemukan', 404);
        }
        
        // ambil semua kategori untuk filter
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/products', [
            'store' => $store,
            'categories' => $categories
        ]);
    }
    
    // api untuk mendapatkan produk seller dengan filter
    public function getSellerProducts() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('unauthorized', 401);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('toko tidak ditemukan', 404);
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
            
            return $this->success('produk berhasil diambil', $result);
            
        } catch (Exception $e) {
            return $this->error('terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman add product
    public function create() {
        // ambil user id dari session
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('toko tidak ditemukan', 404);
        }
        
        // ambil semua kategori
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/add-product', [
            'store' => $store,
            'categories' => $categories
        ]);
    }

    // api untuk menyimpan produk baru (FIXED)
    public function store() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('unauthorized', 401);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('toko tidak ditemukan', 404);
            }

            // ambil data input dari POST
            $productName = trim($_POST['product_name'] ?? '');
            $description = $_POST['description'] ?? '';
            $price = (int)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            
            // handle category_ids dengan benar
            $categoryIds = [];
            if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
                $categoryIds = $_POST['category_ids'];
            }

            // validasi input
            if (empty($productName)) {
                return $this->error('nama produk wajib diisi', 400);
            }

            if (strlen($productName) > 200) {
                return $this->error('nama produk maksimal 200 karakter', 400);
            }

            if (empty($description)) {
                return $this->error('deskripsi produk wajib diisi', 400);
            }

            if ($price < 1000) {
                return $this->error('harga minimal rp 1.000', 400);
            }

            if ($stock < 0) {
                return $this->error('stok tidak valid', 400);
            }

            if (empty($categoryIds)) {
                return $this->error('pilih minimal satu kategori', 400);
            }

            // handle upload image jika ada
            $imagePath = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['main_image']);
                
                if (!$imagePath) {
                    return $this->error('gagal mengupload gambar', 500);
                }
            }

            // buat produk baru
            $productData = [
                'store_id' => $store['store_id'],
                'product_name' => $productName,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'main_image_path' => $imagePath
            ];

            // mulai transaksi
            $this->productModel->beginTransaction();

            try {
                // insert produk
                $newProduct = $this->productModel->create($productData);

                if (!$newProduct || !isset($newProduct['product_id'])) {
                    throw new Exception('gagal membuat produk');
                }

                // tambahkan kategori
                foreach ($categoryIds as $categoryId) {
                    $categoryId = (int)$categoryId;
                    $this->productModel->addCategory($newProduct['product_id'], $categoryId);
                }

                // commit transaksi
                $this->productModel->commit();

                return $this->success('produk berhasil ditambahkan', $newProduct);

            } catch (Exception $e) {
                // rollback jika ada error
                $this->productModel->rollback();
                
                // hapus file image jika sudah diupload
                if ($imagePath && file_exists($this->uploadDir . $imagePath)) {
                    unlink($this->uploadDir . $imagePath);
                }
                
                throw $e;
            }

        } catch (Exception $e) {
            error_log('error di productcontroller::store - ' . $e->getMessage());
            return $this->error('terjadi kesalahan: ' . $e->getMessage(), 500);
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
            return $this->error('product id tidak ditemukan', 400);
        }
        
        // ambil data produk
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            return $this->error('produk tidak ditemukan', 404);
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('toko tidak ditemukan', 404);
        }
        
        // pastikan produk milik seller ini
        if ($product['store_id'] != $store['store_id']) {
            return $this->error('anda tidak memiliki akses ke produk ini', 403);
        }
        
        // ambil semua kategori
        $categories = $this->categoryModel->all();
        
        // ambil kategori produk ini
        $productCategories = $this->productModel->getProductCategories($productId);
        
        return $this->view('seller/edit-product', [
            'product' => $product,
            'store' => $store,
            'categories' => $categories,
            'productCategories' => $productCategories
        ]);
    }

    // api untuk update produk
    public function update() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('unauthorized', 401);
            }
            
            // ambil product id dari post
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('product id tidak ditemukan', 400);
            }
            
            // ambil data produk
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                return $this->error('produk tidak ditemukan', 404);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('toko tidak ditemukan', 404);
            }
            
            // pastikan produk milik seller ini
            if ($product['store_id'] != $store['store_id']) {
                return $this->error('anda tidak memiliki akses ke produk ini', 403);
            }

            // ambil data input
            $productName = trim($_POST['product_name'] ?? '');
            $description = $_POST['description'] ?? '';
            $price = (int)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $categoryIds = $_POST['category_ids'] ?? [];

            // validasi
            if (empty($productName)) {
                return $this->error('nama produk wajib diisi', 400);
            }

            if ($price < 1000) {
                return $this->error('harga minimal rp 1.000', 400);
            }

            if ($stock < 0) {
                return $this->error('stok tidak valid', 400);
            }

            // handle upload image jika ada
            $imagePath = $product['main_image_path'];
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $newImagePath = $this->handleImageUpload($_FILES['main_image']);
                
                if ($newImagePath) {
                    // hapus image lama jika ada
                    if ($imagePath && file_exists($this->uploadDir . $imagePath)) {
                        unlink($this->uploadDir . $imagePath);
                    }
                    $imagePath = $newImagePath;
                }
            }

            // update produk
            $productData = [
                'product_name' => $productName,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'main_image_path' => $imagePath
            ];

            $updatedProduct = $this->productModel->update($productId, $productData);

            if (!$updatedProduct) {
                return $this->error('gagal memperbarui produk', 500);
            }

            // update kategori - hapus semua lalu insert ulang
            $this->productModel->removeAllCategories($productId);
            
            if (!empty($categoryIds) && is_array($categoryIds)) {
                foreach ($categoryIds as $categoryId) {
                    $this->productModel->addCategory($productId, $categoryId);
                }
            }

            return $this->success('produk berhasil diperbarui', $updatedProduct);

        } catch (Exception $e) {
            error_log('error di productcontroller::update - ' . $e->getMessage());
            return $this->error('terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // api untuk delete produk
    public function delete() {
        try {
            // ambil user id dari session
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('unauthorized', 401);
            }
            
            // ambil product id dari body
            $requestBody = file_get_contents('php://input');
            $data = json_decode($requestBody, true);
            $productId = $data['product_id'] ?? null;
            
            if (!$productId) {
                return $this->error('product id tidak ditemukan', 400);
            }
            
            // ambil data produk
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                return $this->error('produk tidak ditemukan', 404);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('toko tidak ditemukan', 404);
            }
            
            // pastikan produk milik seller ini
            if ($product['store_id'] != $store['store_id']) {
                return $this->error('anda tidak memiliki akses ke produk ini', 403);
            }

            // hapus produk (cascade akan menghapus category_items)
            $deleted = $this->productModel->delete($productId);

            if (!$deleted) {
                return $this->error('gagal menghapus produk', 500);
            }

            // hapus image file jika ada
            if ($product['main_image_path'] && file_exists($this->uploadDir . $product['main_image_path'])) {
                unlink($this->uploadDir . $product['main_image_path']);
            }

            return $this->success('produk berhasil dihapus');

        } catch (Exception $e) {
            error_log('error di productcontroller::delete - ' . $e->getMessage());
            return $this->error('terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // helper function untuk upload image
    private function handleImageUpload($file) {
        try {
            // validasi file
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2mb

            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('format gambar tidak valid');
            }

            if ($file['size'] > $maxSize) {
                throw new Exception('ukuran gambar terlalu besar');
            }

            // generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            // pindahkan file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return $filename;
            }

            throw new Exception('gagal memindahkan file');

        } catch (Exception $e) {
            error_log('error upload image: ' . $e->getMessage());
            return null;
        }
    }
}