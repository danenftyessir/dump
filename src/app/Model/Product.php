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
        $sql = "SELECT p.* FROM {$this->table} p WHERE p.store_id = :store_id AND p.deleted_at IS NULL";
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

    // get kategori untuk produk
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

    // get produk dengan kategorinya
    public function getProductWithCategories($productId) {
        $product = $this->find($productId);
        
        if (!$product) {
            return null;
        }
        
        $product['categories'] = $this->getProductCategories($productId);
        
        return $product;
    }

    // get produk dengan informasi store
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

    // tambah kategori ke produk
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
            
        } catch (\Exception $e) {
            error_log('error addcategory: ' . $e->getMessage());
            return false;
        }
    }

    // hapus kategori dari produk
    public function removeCategory($productId, $categoryId) {
        $sql = "DELETE FROM category_items 
                WHERE product_id = :product_id AND category_id = :category_id";
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

    // get products untuk discovery/public dengan filter
    public function getProductsForDiscovery($filters = []) {
        $sql = "SELECT 
                    p.*,
                    s.store_name,
                    s.store_logo_path
                FROM {$this->table} p
                JOIN stores s ON p.store_id = s.store_id
                WHERE p.deleted_at IS NULL";
        $bindings = [];
        
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
        
        // filter store
        if (!empty($filters['store_id'])) {
            $sql .= " AND p.store_id = :store_id";
            $bindings[':store_id'] = $filters['store_id'];
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

    // soft delete produk
    public function softDelete($productId) {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = CURRENT_TIMESTAMP 
                WHERE {$this->primaryKey} = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // restore soft deleted product
    public function restore($productId) {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = NULL 
                WHERE {$this->primaryKey} = :product_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        
        return $stmt->execute();
    }

    // count products untuk seller
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

    // get low stock products untuk seller
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