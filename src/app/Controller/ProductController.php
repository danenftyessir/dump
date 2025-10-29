<?php
class ProductController extends Controller
{
    private $productModel;
    private $categoryModel;
    private $storeModel;
    private $uploadDir;

    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->storeModel = new Store();
        $this->uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/products/';
        
        // buat direktori jika belum ada dengan suppress warning
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    // halaman product management seller
    public function index() {
        // ambil user dari session (udh di start di index.php nya)
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
            // TO DO validasi user adalah seller yang sudah login
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
        // TO DO validasi user adalah seller yang sudah login
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
            // TO DO validasi user adalah seller yang sudah login
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
            } elseif (strlen($description) > 5000) {
                $errors[] = 'Deskripsi maksimal 5000 karakter';
            }
            
            if (!is_numeric($price) || $price < 1000) {
                $errors[] = 'Harga minimal Rp 1.000';
            }
            
            if (!is_numeric($stock) || $stock < 0) {
                $errors[] = 'Stok minimal 0';
            }
            
            if (empty($categoryIds) || !is_array($categoryIds)) {
                $errors[] = 'Pilih minimal 1 kategori';
            }
            
            // validasi foto
            if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Foto produk wajib diupload';
            }
            
            if (!empty($errors)) {
                return $this->error(implode(', ', $errors), 400);
            }
            
            // sanitasi deskripsi rich text
            $description = $this->sanitizeRichText($description);
            
            // handle upload foto
            $uploadResult = $this->handleProductImageUpload($_FILES['product_image']);
            
            if (!$uploadResult['success']) {
                return $this->error($uploadResult['error'], 400);
            }
            
            // simpan produk ke database
            $productData = [
                'store_id' => $store['store_id'],
                'product_name' => $productName,
                'description' => $description,
                'price' => (int)$price,
                'stock' => (int)$stock,
                'main_image_path' => $uploadResult['path']
            ];           
            $product = $this->productModel->create($productData);            
            if (!$product) {
                return $this->error('Gagal Menyimpan Produk', 500);
            }
            
            // simpan kategori produk
            $this->productModel->updateCategories($product['product_id'], $categoryIds);           
            return $this->success('Produk Berhasil Ditambahkan', $product);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // halaman edit product
    public function edit() {
        // TO DO validasi user adalah seller yang sudah login
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
            // TO DO validasi user adalah seller yang sudah login
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
            } elseif (strlen($description) > 5000) {
                $errors[] = 'Deskripsi maksimal 5000 karakter';
            }
            
            if (!is_numeric($price) || $price < 1000) {
                $errors[] = 'Harga minimal Rp 1.000';
            }
            
            if (!is_numeric($stock) || $stock < 0) {
                $errors[] = 'Stok minimal 0';
            }
            
            if (empty($categoryIds) || !is_array($categoryIds)) {
                $errors[] = 'Pilih minimal 1 kategori';
            }
            
            if (!empty($errors)) {
                return $this->error(implode(', ', $errors), 400);
            }
            
            // sanitasi deskripsi rich text
            $description = $this->sanitizeRichText($description);
            
            // data untuk update
            $productData = [
                'product_name' => $productName,
                'description' => $description,
                'price' => (int)$price,
                'stock' => (int)$stock
            ];
            
            // handle upload foto baru jika ada
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = $this->handleProductImageUpload($_FILES['product_image']);
                
                if (!$uploadResult['success']) {
                    return $this->error($uploadResult['error'], 400);
                }
                
                // hapus foto lama
                $oldProduct = $this->productModel->find($productId);
                if (!empty($oldProduct['main_image_path'])) {
                    $oldFilePath = dirname(dirname(dirname(__DIR__))) . '/public' . $oldProduct['main_image_path'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $productData['main_image_path'] = $uploadResult['path'];
            }
            
            // update produk
            $product = $this->productModel->update($productId, $productData);
            
            if (!$product) {
                return $this->error('Gagal Mengupdate Produk', 500);
            }
            
            // update kategori produk
            $this->productModel->updateCategories($productId, $categoryIds);
            
            return $this->success('Produk Berhasil Diupdate', $product);
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API untuk delete produk (soft delete)
    public function delete() {
        try {
            // TO DO validasi user adalah seller yang sudah login
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
            
            // cek apakah produk memiliki order pending
            if ($this->productModel->hasPendingOrders($productId)) {
                return $this->error('Produk Tidak Dapat Dihapus Karena Masih Ada Order Pending', 400);
            }
            
            // soft delete produk
            $result = $this->productModel->softDelete($productId);
            
            if (!$result) {
                return $this->error('Gagal Menghapus Produk', 500);
            }           
            return $this->success('Produk Berhasil Dihapus');
            
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // handle upload foto produk
    private function handleProductImageUpload($file) {
        // validasi error upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload Gagal'];
        }
        
        // validasi ukuran
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
}