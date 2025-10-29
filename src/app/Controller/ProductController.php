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
        // ambil user dari session
        $userId = $_SESSION['user']['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // ambil data toko seller
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('Toko Tidak Ditemukan', 404);
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
            // ambil user dari session
            $userId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            // ambil store_id seller
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }
            
            // ambil filter dari query string
            $filters = [
                'search' => $this->input('search', ''),
                'category_id' => $this->input('category_id', ''),
                'sort_by' => $this->input('sort_by', 'created_at'),
                'sort_order' => $this->input('sort_order', 'DESC'),
                'page' => $this->input('page', 1),
                'limit' => $this->input('limit', 10)
            ];
            
            $result = $this->productModel->getProductsForSeller($store['store_id'], $filters);
            
            return $this->success('Produk Berhasil Diambil', $result);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman add product
    public function create() {
        // ambil user dari session
        $userId = $_SESSION['user']['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('Toko Tidak Ditemukan', 404);
        }
        
        // ambil semua kategori untuk dropdown
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/product-add', [
            'store' => $store,
            'categories' => $categories
        ]);
    }

    // API untuk menyimpan produk baru
    public function store() {
        try {
            // ambil user dari session
            $userId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }
            
            // ambil data dari request
            $productName = $this->input('product_name');
            $description = $this->input('description');
            $price = $this->input('price');
            $stock = $this->input('stock');
            $categoryIds = $this->input('category_ids', []);
            
            // validasi input server-side
            $errors = [];
            
            if (empty($productName)) {
                $errors[] = 'Nama produk tidak boleh kosong';
            } elseif (strlen($productName) > 200) {
                $errors[] = 'Nama produk maksimal 200 karakter';
            }
            
            if (empty($description)) {
                $errors[] = 'Deskripsi tidak boleh kosong';
            } elseif (strlen($description) > 1000) {
                $errors[] = 'Deskripsi maksimal 1000 karakter';
            }
            
            if (empty($price) || !is_numeric($price)) {
                $errors[] = 'Harga harus berupa angka';
            } elseif ($price < 1000) {
                $errors[] = 'Harga minimal Rp 1.000';
            }
            
            if (!isset($stock) || !is_numeric($stock)) {
                $errors[] = 'Stok harus berupa angka';
            } elseif ($stock < 0) {
                $errors[] = 'Stok tidak boleh negatif';
            }
            
            if (empty($categoryIds) || !is_array($categoryIds)) {
                $errors[] = 'Pilih minimal satu kategori';
            }
            
            // validasi upload foto
            if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Foto produk wajib diupload';
            }
            
            if (!empty($errors)) {
                return $this->error('Validasi Gagal', 400, ['errors' => $errors]);
            }
            
            // handle upload foto
            $uploadResult = $this->handleImageUpload($_FILES['product_image']);
            if (!$uploadResult['success']) {
                return $this->error($uploadResult['error'], 400);
            }
            
            // sanitasi deskripsi
            $description = $this->sanitizeRichText($description);
            
            // buat produk baru
            $productData = [
                'store_id' => $store['store_id'],
                'product_name' => $productName,
                'description' => $description,
                'price' => (int)$price,
                'stock' => (int)$stock,
                'main_image_path' => $uploadResult['path']
            ];
            
            $newProduct = $this->productModel->create($productData);
            
            if (!$newProduct) {
                return $this->error('Gagal Membuat Produk', 500);
            }
            
            // simpan kategori produk
            foreach ($categoryIds as $categoryId) {
                $this->productModel->addCategory($newProduct['product_id'], $categoryId);
            }
            
            return $this->success('Produk Berhasil Ditambahkan', $newProduct);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman edit product
    public function edit() {
        // ambil user dari session
        $userId = $_SESSION['user']['user_id'] ?? null;
        
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        $store = $this->storeModel->findByUserId($userId);
        
        if (!$store) {
            return $this->error('Toko Tidak Ditemukan', 404);
        }
        
        // ambil product_id dari URL
        $productId = $this->input('product_id');
        
        if (!$productId) {
            return $this->redirect('/seller/products');
        }
        
        // validasi kepemilikan produk
        if (!$this->productModel->isOwnedByStore($productId, $store['store_id'])) {
            return $this->error('Produk Tidak Ditemukan', 404);
        }
        
        // ambil data produk dengan kategorinya
        $product = $this->productModel->getProductWithCategories($productId);
        
        // ambil semua kategori untuk dropdown
        $categories = $this->categoryModel->all();
        
        return $this->view('seller/product-edit', [
            'store' => $store,
            'product' => $product,
            'categories' => $categories
        ]);
    }

    // API untuk update produk
    public function update() {
        try {
            // ambil user dari session
            $userId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }
            
            // ambil product_id
            $productId = $this->input('product_id');
            
            if (!$productId) {
                return $this->error('Product ID Tidak Valid', 400);
            }
            
            // validasi kepemilikan produk
            if (!$this->productModel->isOwnedByStore($productId, $store['store_id'])) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }
            
            // ambil data dari request
            $productName = $this->input('product_name');
            $description = $this->input('description');
            $price = $this->input('price');
            $stock = $this->input('stock');
            $categoryIds = $this->input('category_ids', []);
            
            // validasi input server-side
            $errors = [];
            
            if (empty($productName)) {
                $errors[] = 'Nama produk tidak boleh kosong';
            } elseif (strlen($productName) > 200) {
                $errors[] = 'Nama produk maksimal 200 karakter';
            }
            
            if (empty($description)) {
                $errors[] = 'Deskripsi tidak boleh kosong';
            } elseif (strlen($description) > 1000) {
                $errors[] = 'Deskripsi maksimal 1000 karakter';
            }
            
            if (empty($price) || !is_numeric($price)) {
                $errors[] = 'Harga harus berupa angka';
            } elseif ($price < 1000) {
                $errors[] = 'Harga minimal Rp 1.000';
            }
            
            if (!isset($stock) || !is_numeric($stock)) {
                $errors[] = 'Stok harus berupa angka';
            } elseif ($stock < 0) {
                $errors[] = 'Stok tidak boleh negatif';
            }
            
            if (empty($categoryIds) || !is_array($categoryIds)) {
                $errors[] = 'Pilih minimal satu kategori';
            }
            
            if (!empty($errors)) {
                return $this->error('Validasi Gagal', 400, ['errors' => $errors]);
            }
            
            // ambil data produk lama
            $oldProduct = $this->productModel->find($productId);
            $imagePath = $oldProduct['main_image_path'];
            
            // handle upload foto baru jika ada
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleImageUpload($_FILES['product_image']);
                if (!$uploadResult['success']) {
                    return $this->error($uploadResult['error'], 400);
                }
                
                // hapus foto lama
                if ($imagePath && file_exists($this->uploadDir . basename($imagePath))) {
                    @unlink($this->uploadDir . basename($imagePath));
                }
                
                $imagePath = $uploadResult['path'];
            }
            
            // sanitasi deskripsi
            $description = $this->sanitizeRichText($description);
            
            // update produk
            $updateData = [
                'product_name' => $productName,
                'description' => $description,
                'price' => (int)$price,
                'stock' => (int)$stock,
                'main_image_path' => $imagePath
            ];
            
            $result = $this->productModel->update($productId, $updateData);
            
            if (!$result) {
                return $this->error('Gagal Mengupdate Produk', 500);
            }
            
            // update kategori produk
            $this->productModel->removeAllCategories($productId);
            foreach ($categoryIds as $categoryId) {
                $this->productModel->addCategory($productId, $categoryId);
            }
            
            return $this->success('Produk Berhasil Diupdate', $updateData);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk delete produk
    public function delete() {
        try {
            // ambil user dari session
            $userId = $_SESSION['user']['user_id'] ?? null;
            
            if (!$userId) {
                return $this->error('Unauthorized', 401);
            }
            
            $store = $this->storeModel->findByUserId($userId);
            
            if (!$store) {
                return $this->error('Toko Tidak Ditemukan', 404);
            }
            
            // ambil product_id
            $productId = $this->input('product_id');
            
            if (!$productId) {
                return $this->error('Product ID Tidak Valid', 400);
            }
            
            // validasi kepemilikan produk
            if (!$this->productModel->isOwnedByStore($productId, $store['store_id'])) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }
            
            // cek apakah ada order pending dengan produk ini
            // TODO: implementasi validasi cascade delete
            
            // ambil data produk untuk hapus foto
            $product = $this->productModel->find($productId);
            
            // hapus produk dari database
            $result = $this->productModel->delete($productId);
            
            if (!$result) {
                return $this->error('Gagal Menghapus Produk', 500);
            }
            
            // hapus foto produk
            if ($product['main_image_path'] && file_exists($this->uploadDir . basename($product['main_image_path']))) {
                @unlink($this->uploadDir . basename($product['main_image_path']));
            }
            
            return $this->success('Produk Berhasil Dihapus');
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // handle upload image produk
    private function handleImageUpload($file) {
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
        $filename = uniqid('product_') . '.' . $extension;
        $filepath = $this->uploadDir . $filename;

        // pindahkan file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Gagal Menyimpan File'];
        }

        // return path relatif untuk disimpan di database
        return ['success' => true, 'path' => '/uploads/products/' . $filename];
    }

    // sanitasi rich text dari quill editor
    private function sanitizeRichText($html) {
        // TODO: implementasi sanitasi HTML yang lebih ketat
        // untuk sementara gunakan strip_tags dengan whitelist tag
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3>';
        return strip_tags($html, $allowedTags);
    }
}