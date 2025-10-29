<?php

namespace Model;

use Base\Model;
use PDO;

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

    // get products untuk seller dengan filter dan pagination
    public function getProductsForSeller($storeId, $filters = []) {
        $sql = "SELECT p.* FROM {$this->table} p WHERE p.store_id = :store_id";
        $bindings = [':store_id' => $storeId];
        
        // filter search
        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_name LIKE :search OR p.description LIKE :search)";
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
        
        // validasi sort_by untuk mencegah SQL injection
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
        
        // hitung total pages
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    // cek apakah produk dimiliki oleh store tertentu
    public function isOwnedByStore($productId, $storeId) {
        $product = $this->find($productId);
        return $product && $product['store_id'] == $storeId;
    }

    // get produk dengan kategorinya
    public function getProductWithCategories($productId) {
        $product = $this->find($productId);
        
        if (!$product) {
            return null;
        }
        
        // get categories
        $sql = "SELECT c.category_id, c.name
                FROM categories c
                JOIN category_items ci ON c.category_id = ci.category_id
                WHERE ci.product_id = :product_id
                ORDER BY c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $product['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $product['category_ids'] = array_column($product['categories'], 'category_id');
        
        return $product;
    }

    // get produk dengan informasi store
    public function getProductWithStore($productId) {
        $sql = "SELECT 
                    p.*,
                    s.store_id,
                    s.store_name,
                    s.store_logo_path
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                WHERE p.{$this->primaryKey} = :product_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return null;
        }
        
        // get categories
        $sql = "SELECT c.category_id, c.name
                FROM categories c
                JOIN category_items ci ON c.category_id = ci.category_id
                WHERE ci.product_id = :product_id
                ORDER BY c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $product['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $product;
    }

    // tambah kategori ke produk
    public function addCategory($productId, $categoryId) {
        // cek apakah sudah ada
        $sql = "SELECT 1 FROM category_items 
                WHERE product_id = :product_id AND category_id = :category_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return true; // sudah ada
        }
        
        // insert baru
        $sql = "INSERT INTO category_items (product_id, category_id) 
                VALUES (:product_id, :category_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':category_id', $categoryId);
        
        return $stmt->execute();
    }

    // hapus semua kategori dari produk
    public function removeAllCategories($productId) {
        $sql = "DELETE FROM category_items WHERE product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // update stock produk
    public function updateStock($productId, $quantity) {
        $product = $this->find($productId);
        
        if (!$product) {
            return false;
        }
        
        $newStock = $product['stock'] + $quantity;
        
        // stock tidak boleh negatif
        if ($newStock < 0) {
            return false;
        }
        
        return $this->update($productId, ['stock' => $newStock]);
    }

    // cek apakah stock tersedia
    public function isStockAvailable($productId, $quantity) {
        $product = $this->find($productId);
        
        if (!$product) {
            return false;
        }
        
        return $product['stock'] >= $quantity;
    }

    // search produk untuk buyer
    public function searchProducts($keyword = '', $filters = []) {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.price,
                    p.stock,
                    p.main_image_path,
                    s.store_id,
                    s.store_name
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                WHERE p.stock > 0";
        
        $bindings = [];
        
        // filter keyword
        if (!empty($keyword)) {
            $sql .= " AND (p.product_name LIKE :keyword OR p.description LIKE :keyword)";
            $bindings[':keyword'] = '%' . $keyword . '%';
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
        
        // filter harga min
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $bindings[':min_price'] = $filters['min_price'];
        }
        
        // filter harga max
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $bindings[':max_price'] = $filters['max_price'];
        }
        
        // filter store
        if (!empty($filters['store_id'])) {
            $sql .= " AND p.store_id = :store_id";
            $bindings[':store_id'] = $filters['store_id'];
        }
        
        // count total
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
        
        $allowedSortBy = ['product_name', 'price', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        $sql .= " ORDER BY p.{$sortBy} {$sortOrder}";
        
        // pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(50, (int)($filters['limit'] ?? 12)));
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
        
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    // get produk terkait (dari kategori yang sama)
    public function getRelatedProducts($productId, $limit = 4) {
        $sql = "SELECT DISTINCT
                    p.product_id,
                    p.product_name,
                    p.price,
                    p.main_image_path,
                    s.store_name
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                JOIN category_items ci ON p.product_id = ci.product_id
                WHERE ci.category_id IN (
                    SELECT category_id 
                    FROM category_items 
                    WHERE product_id = :product_id
                )
                AND p.product_id != :product_id
                AND p.stock > 0
                ORDER BY RANDOM()
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}