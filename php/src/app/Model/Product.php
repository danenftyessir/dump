<?php

namespace Model;

use Base\Model;
use PDO;
use Exception;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    
    protected $fillable = [
        'store_id',
        'product_name',
        'description',
        'price',
        'stock',
        'main_image_path'
    ];

    // Ctor
    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // Mendapatkan produk untuk discovery/public dengan filter
    public function getProductsForDiscovery($filters = []) {
        // dasar query
        $sql = "SELECT 
                    p.*,
                    s.store_name,
                    s.store_logo_path
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                WHERE p.deleted_at IS NULL";

        $bindings = [];
        
        // filter search - FIX: Case-insensitive search using LOWER()
        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(p.product_name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.search_cache) LIKE LOWER(:search))";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        
        // filter category
        if (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM category_items ci 
                        WHERE ci.product_id = p.product_id 
                        AND ci.category_id = :category_id
                    )";
            $bindings[':category_id'] = $filters['category_id'];
        }
        
        // filter store
        if (!empty($filters['store_id'])) {
            $sql .= " AND p.store_id = :store_id";
            $bindings[':store_id'] = $filters['store_id'];
        }

        // filter harga minimum
        if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $bindings[':min_price'] = (int)$filters['min_price'];
        }

        // filter harga maksimum
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $bindings[':max_price'] = (int)$filters['max_price'];
        }
        
        // count total untuk pagination
        $countSql = "SELECT COUNT(DISTINCT p.product_id) as total 
                     FROM {$this->table} p
                     JOIN stores s ON p.store_id = s.store_id 
                     WHERE p.deleted_at IS NULL";
        
        // terapkan filter yang sama ke count query
        if (!empty($filters['search'])) $countSql .= $bindings[':search'] ? " AND (p.product_name LIKE :search OR p.description LIKE :search)" : "";
        if (!empty($filters['category_id'])) $countSql .= $bindings[':category_id'] ? " AND EXISTS (SELECT 1 FROM category_items ci WHERE ci.product_id = p.product_id AND ci.category_id = :category_id)" : "";
        if (!empty($filters['store_id'])) $countSql .= $bindings[':store_id'] ? " AND p.store_id = :store_id" : "";
        if (!empty($filters['min_price'])) $countSql .= $bindings[':min_price'] ? " AND p.price >= :min_price" : "";
        if (!empty($filters['max_price'])) $countSql .= $bindings[':max_price'] ? " AND p.price <= :max_price" : "";

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($bindings);
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        
        // validasi sort_by
        $allowedSortBy = ['product_name', 'price', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        
        // validasi sort_order
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        $sql .= " ORDER BY p.{$sortBy} {$sortOrder}";
        
        // pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(50, (int)($filters['limit'] ?? 12)));
        $offset = ($page - 1) * $limit;
        
        $sql .= " LIMIT :limit OFFSET :offset";
        $bindings[':limit'] = $limit;
        $bindings[':offset'] = $offset;
        
        // prepare dan execute
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value, (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($products)) {
            // ambil semua ID produk
            $productIds = array_column($products, 'product_id');
            
            // buat placeholder
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            
            // ambil semua kategori untuk semua produk dalam satu query
            $catSql = "SELECT ci.product_id, c.category_id, c.name
                       FROM categories c
                       JOIN category_items ci ON c.category_id = ci.category_id
                       WHERE ci.product_id IN ($placeholders)
                       ORDER BY c.name ASC";
            
            $catStmt = $this->db->prepare($catSql);
            $catStmt->execute($productIds);
            $categoriesData = $catStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // petakan kategori ke ID produk
            $categoryMap = [];
            foreach ($categoriesData as $cat) {
                $categoryMap[$cat['product_id']][] = $cat;
            }
            
            // masukkan kategori ke array produk
            foreach ($products as &$product) {
                $product['categories'] = $categoryMap[$product['product_id']] ?? [];
            }
        }
        
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $totalItems,
                'total_pages' => ceil($totalItems / $limit)
            ]
        ];
    }

    // Mendapatkan kategori dari produk
    public function getProductCategories($productId) {
        $sql = "SELECT c.category_id, c.name
                FROM categories c
                JOIN category_items ci ON c.category_id = ci.category_id
                WHERE ci.product_id = :product_id
                ORDER BY c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mendapatkan produk dengan kategorinya
    public function getProductWithCategories($productId) {
        $product = $this->find($productId);
        
        if (!$product) {
            return null;
        }
        
        $product['categories'] = $this->getProductCategories($productId);
        
        return $product;
    }

    // Mendapatkan produk dengan informasi store
    public function getProductWithStore($productId) {
        $sql = "SELECT 
                    p.*,
                    s.store_id,
                    s.store_name,
                    s.store_logo_path,
                    s.store_description
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                WHERE p.{$this->primaryKey} = :product_id AND p.deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return null;
        }
        
        // get categories
        $product['categories'] = $this->getProductCategories($productId);
        
        return $product;
    }

    // Mendapatkan produk untuk seller dengan filter, sorting, dan pagination
    public function getProductsForSeller($storeId, $filters = []) {
        $sql = "SELECT p.* FROM {$this->table} p WHERE p.store_id = :store_id AND p.deleted_at IS NULL";
        $bindings = [':store_id' => $storeId];
        
        // filter search - FIX: Case-insensitive search using LOWER()
        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(p.product_name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.search_cache) LIKE LOWER(:search))";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        
        // filter category
        if (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM category_items ci 
                WHERE ci.product_id = p.product_id 
                AND ci.category_id = :category_id
            )";
            $bindings[':category_id'] = $filters['category_id'];
        }
        
        // count total untuk pagination
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as filtered";
        $countStmt = $this->db->prepare($countSql);
        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');
        
        // validasi sort_by untuk mencegah sql injection
        $allowedSortBy = ['product_name', 'price', 'stock', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        
        // validasi sort_order
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        $sql .= " ORDER BY p.{$sortBy} {$sortOrder}";
        
        // pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(50, (int)($filters['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // untuk setiap produk, ambil kategorinya
        foreach ($products as &$product) {
            $product['categories'] = $this->getProductCategories($product['product_id']);
        }
        
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $totalItems,
                'total_pages' => ceil($totalItems / $limit)
            ]
        ];
    }

    // Menambahkan kategori ke produk
    public function addCategory($productId, $categoryId) {
        try {
            // cek apakah sudah ada
            $sql = "SELECT 1 FROM category_items 
                    WHERE product_id = :product_id AND category_id = :category_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                return true; // sudah ada, skip
            }
            
            // insert baru
            $sql = "INSERT INTO category_items (product_id, category_id) 
                    VALUES (:product_id, :category_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log('error addcategory: ' . $e->getMessage());
            return false;
        }
    }

    // Menghapus kategori dari produk
    public function removeCategory($productId, $categoryId) {
        $sql = "DELETE FROM category_items 
                WHERE product_id = :product_id AND category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':category_id', $categoryId);
        
        return $stmt->execute();
    }

    // Menghapus semua kategori dari produk
    public function removeAllCategories($productId) {
        $sql = "DELETE FROM category_items WHERE product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // Update stok produk
    public function updateStock(int $productId, int $quantity) {
        if ($quantity < 0) {
            $amountAbs = abs($quantity);
            
            $sql = "UPDATE {$this->table}
                    SET stock = stock + :quantity
                    WHERE {$this->primaryKey} = :product_id AND stock >= :amount_abs";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':amount_abs', $amountAbs, PDO::PARAM_INT);
            $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);

            $stmt->execute();
            
            // Jika rowCount = 0, berarti stok tidak cukup (atau id invalid)
            if ($stmt->rowCount() === 0) {
                throw new Exception("Stok tidak mencukupi untuk memproses produk ID {$productId}.");
            }
            
            return true;
            
        } else {
            $sql = "UPDATE {$this->table}
                    SET stock = stock + :quantity
                    WHERE {$this->primaryKey} = :product_id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            
            return $stmt->execute();
        }
    }

    // Soft delete produk
    public function softDelete($productId) {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = CURRENT_TIMESTAMP 
                WHERE {$this->primaryKey} = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // Restore soft deleted product
    public function restore($productId) {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NULL 
                WHERE {$this->primaryKey} = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // Menghitung total produk untuk seller
    public function countProductsForSeller($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE store_id = :store_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Mendapatkan produk dengan stok rendah untuk seller
    public function getLowStockProducts($storeId, $threshold = 10) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE store_id = :store_id 
                AND stock <= :threshold 
                AND deleted_at IS NULL
                ORDER BY stock ASC
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}