<?php
class ProductDiscoveryController extends Controller
{
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new Category();
    }

    // render halaman home/product discovery
    public function index() {
        try {
            // load semua kategori untuk filter dropdown
            $categories = $this->categoryModel->all();
            return $this->view('buyer/home', [
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API endpoint untuk mengambil produk dengan filter, search, dan pagination
    public function getProducts() {
        // TO DO implementasi logika buat:
        // 1. search produk berdasarkan keyword
        // 2. filter berdasarkan kategori
        // 3. filter berdasarkan rentang harga
        // 4. pagination (page, limit)
        // 5. join dengan tabel stores untuk mendapatkan store_name
        // 6. exclude produk yang udah di soft delete
        
        try {
            // Ambil parameter dari query string
            $keyword = $this->input('q', '');
            $categoryId = $this->input('category', '');
            $minPrice = $this->input('min_price', '');
            $maxPrice = $this->input('max_price', '');
            $page = max(1, (int)$this->input('page', 1));
            $limit = max(1, min(20, (int)$this->input('limit', 8)));
            $offset = ($page - 1) * $limit;

            // build query SQL dengan kondisi dinamis
            $conditions = ['p.deleted_at IS NULL'];
            $bindings = [];

            // filter search keyword
            if (!empty($keyword)) {
                $conditions[] = "p.product_name ILIKE :keyword";
                $bindings[':keyword'] = '%' . $keyword . '%';
            }

            // filter kategori
            if (!empty($categoryId)) {
                $conditions[] = "EXISTS (
                    SELECT 1 FROM category_items ci 
                    WHERE ci.product_id = p.product_id 
                    AND ci.category_id = :category_id
                )";
                $bindings[':category_id'] = $categoryId;
            }

            // filter harga minimum
            if (!empty($minPrice) && is_numeric($minPrice)) {
                $conditions[] = "p.price >= :min_price";
                $bindings[':min_price'] = (int)$minPrice;
            }

            // filter harga maksimum
            if (!empty($maxPrice) && is_numeric($maxPrice)) {
                $conditions[] = "p.price <= :max_price";
                $bindings[':max_price'] = (int)$maxPrice;
            }
            $whereClause = implode(' AND ', $conditions);

            // query hitung total items
            $countSql = "SELECT COUNT(*) as total 
                         FROM products p 
                         WHERE {$whereClause}";
            $db = Database::getInstance()->getConnection();
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($bindings);
            $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // query ambil produk
            $sql = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.description,
                        p.price,
                        p.stock,
                        p.main_image_path,
                        s.store_id,
                        s.store_name
                    FROM products p
                    JOIN stores s ON p.store_id = s.store_id
                    WHERE {$whereClause}
                    ORDER BY p.created_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            // bind semua parameter
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // hitung total pages
            $totalPages = ceil($totalItems / $limit);
            // response
            return $this->success('Produk Berhasil Dimuat', [
                'products' => $products,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'items_per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // API endpoint untuk search suggestions (bonus advanced search)
    public function getSearchSuggestions() {
        try {
            $keyword = $this->input('q', '');
            if (strlen($keyword) < 3) {
                return $this->success('Suggestions', []);
            }

            // TO DO (bonus, advanced search) implementasi algoritma smart searching     
            $db = Database::getInstance()->getConnection();
            
            // query suggestions
            $sql = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.price,
                        p.main_image_path
                    FROM products p
                    WHERE p.deleted_at IS NULL
                    AND p.product_name ILIKE :keyword
                    ORDER BY p.product_name
                    LIMIT 5";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->execute();    
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->success('Suggestions', $suggestions);
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }

    // render halaman detail produk
    public function showProduct() {
        // TO DO implementasi untuk menampilkan detail produk
        // info produk lengkap
        // info toko
        // kategori produk
        // form add to cart (kalo user buyer dan login)
        
        try {
            $productId = $this->input('product_id');
            if (!$productId) {
                return $this->error('Product ID Tidak Ditemukan', 400);
            }

            // query ambil detail produk dengan info toko
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT 
                        p.*,
                        s.store_id,
                        s.store_name,
                        s.store_description,
                        s.store_logo_path
                    FROM products p
                    JOIN stores s ON p.store_id = s.store_id
                    WHERE p.product_id = :product_id
                    AND p.deleted_at IS NULL";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':product_id', $productId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                return $this->error('Produk Tidak Ditemukan', 404);
            }

            // ambil kategori produk
            $categorySql = "SELECT c.category_id, c.name
                           FROM categories c
                           JOIN category_items ci ON c.category_id = ci.category_id
                           WHERE ci.product_id = :product_id";           
            $categoryStmt = $db->prepare($categorySql);
            $categoryStmt->bindValue(':product_id', $productId);
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            $product['categories'] = $categories;

            // render view atau return JSON
            // return $this->view('product-detail', ['product' => $product]);
            return $this->success('Detail Produk', $product);
        } catch (Exception $e) {
            return $this->error('Terjadi Kesalahan: ' . $e->getMessage(), 500);
        }
    }
}