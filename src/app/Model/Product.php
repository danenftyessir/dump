<?php
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

    // ambil produk berdasarkan store_id dengan soft delete check
    public function getByStoreId($storeId, $excludeDeleted = true) {
        $sql = "SELECT * FROM {$this->table} WHERE store_id = :store_id";
        
        if ($excludeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " ORDER BY created_at DESC";       
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ambil produk dengan filter, search, sort, pagination untuk seller
    public function getProductsForSeller($storeId, $filters = []) {
        $sql = "SELECT p.*, 
                COUNT(ci.category_id) as category_count
                FROM {$this->table} p
                LEFT JOIN category_items ci ON p.product_id = ci.product_id
                WHERE p.store_id = :store_id AND p.deleted_at IS NULL";
        
        $bindings = [':store_id' => $storeId];
        
        // filter search
        if (!empty($filters['search'])) {
            $sql .= " AND p.product_name ILIKE :search";
            $bindings[':search'] = '%' . $filters['search'] . '%';
        }
        
        // filter kategori
        if (!empty($filters['category_id'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM category_items ci2 
                WHERE ci2.product_id = p.product_id 
                AND ci2.category_id = :category_id
            )";
            $bindings[':category_id'] = $filters['category_id'];
        }
        
        $sql .= " GROUP BY p.product_id";
        
        // sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        
        // validasi sort field untuk keamanan
        $allowedSortFields = ['product_name', 'price', 'stock', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // validasi sort order
        $sortOrder = strtoupper($sortOrder);
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        $sql .= " ORDER BY p.{$sortField} {$sortOrder}";
        
        // pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(50, (int)($filters['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;        
        $sql .= " LIMIT :limit OFFSET :offset";        
        $stmt = $this->db->prepare($sql);
        
        // bind semua parameter
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // hitung total untuk pagination
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} p 
                     WHERE p.store_id = :store_id AND p.deleted_at IS NULL";
        
        if (!empty($filters['search'])) {
            $countSql .= " AND p.product_name ILIKE :search";
        }
        
        if (!empty($filters['category_id'])) {
            $countSql .= " AND EXISTS (
                SELECT 1 FROM category_items ci 
                WHERE ci.product_id = p.product_id 
                AND ci.category_id = :category_id
            )";
        }
        
        $countStmt = $this->db->prepare($countSql);
        $countStmt->bindValue(':store_id', $storeId);
        
        if (!empty($filters['search'])) {
            $countStmt->bindValue(':search', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['category_id'])) {
            $countStmt->bindValue(':category_id', $filters['category_id']);
        }
        
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];       
        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    // soft delete produk
    public function softDelete($productId) {
        $sql = "UPDATE {$this->table} 
                SET deleted_at = CURRENT_TIMESTAMP 
                WHERE {$this->primaryKey} = :id";        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $productId);
        return $stmt->execute();
    }

    // validasi kepemilikan produk oleh store
    public function isOwnedByStore($productId, $storeId) {
        $product = $this->find($productId);
        
        if (!$product) {
            return false;
        }
        
        return $product['store_id'] == $storeId && $product['deleted_at'] === null;
    }

    // ambil produk dengan kategorinya
    public function getProductWithCategories($productId) {
        $product = $this->find($productId);      
        if (!$product) {
            return null;
        }
        
        // ambil kategori produk
        $sql = "SELECT c.category_id, c.name 
                FROM categories c
                JOIN category_items ci ON c.category_id = ci.category_id
                WHERE ci.product_id = :product_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();   
        $product['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $product;
    }

    // update kategori produk
    public function updateCategories($productId, $categoryIds) {
        try {
            // hapus kategori lama
            $deleteSql = "DELETE FROM category_items WHERE product_id = :product_id";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->bindParam(':product_id', $productId);
            $deleteStmt->execute();
            
            // insert kategori baru
            if (!empty($categoryIds)) {
                $insertSql = "INSERT INTO category_items (category_id, product_id) 
                             VALUES (:category_id, :product_id)";
                $insertStmt = $this->db->prepare($insertSql);              
                foreach ($categoryIds as $categoryId) {
                    $insertStmt->bindValue(':category_id', $categoryId);
                    $insertStmt->bindValue(':product_id', $productId);
                    $insertStmt->execute();
                }
            }          
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // cek apakah produk memiliki order pending
    public function hasPendingOrders($productId) {
        $sql = "SELECT COUNT(*) as count 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.product_id = :product_id 
                AND o.status IN ('waiting_approval', 'approved', 'on_delivery')";        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();       
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}