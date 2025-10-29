<?php

namespace Model;

use Base\Model;
use PDO;

class Store extends Model
{
    protected $table = 'stores';
    protected $primaryKey = 'store_id';
    
    protected $fillable = [
        'user_id',
        'store_name',
        'store_description',
        'store_logo_path',
        'balance'
    ];

    // cari toko berdasarkan user_id
    public function findByUserId($userId) {
        return $this->first(['user_id' => $userId]);
    }

    // cari toko berdasarkan nama toko
    public function findByStoreName($storeName) {
        return $this->first(['store_name' => $storeName]);
    }

    // buat toko baru untuk seller
    public function createStore($data) {
        // validasi user_id belum memiliki toko
        $existingStore = $this->findByUserId($data['user_id']);
        if ($existingStore) {
            return false;
        }

        // validasi nama toko unik
        if (isset($data['store_name'])) {
            $existingName = $this->findByStoreName($data['store_name']);
            if ($existingName) {
                return false;
            }
        }
        
        return $this->create($data);
    }

    // update balance toko
    public function updateBalance($storeId, $amount) {
        $store = $this->find($storeId);
        if (!$store) {
            return false;
        }
        
        $newBalance = $store['balance'] + $amount;
        
        // balance tidak boleh negatif
        if ($newBalance < 0) {
            return false;
        }
        
        return $this->update($storeId, ['balance' => $newBalance]);
    }

    // ambil data toko beserta informasi user pemilik
    public function getStoreWithOwner($storeId) {
        $sql = "SELECT 
                    s.*,
                    u.user_id,
                    u.name as owner_name,
                    u.email as owner_email
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.{$this->primaryKey} = :store_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ambil semua toko dengan informasi pemilik
    public function getAllStoresWithOwner($limit = null, $offset = null) {
        $sql = "SELECT 
                    s.*,
                    u.name as owner_name
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.user_id
                ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get total produk di toko
    public function getTotalProducts($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM products 
                WHERE store_id = :store_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get produk dengan stok rendah
    public function getLowStockProducts($storeId, $threshold = 10) {
        $sql = "SELECT COUNT(*) as total 
                FROM products 
                WHERE store_id = :store_id AND stock < :threshold";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get daftar produk dengan stok rendah (detail)
    public function getLowStockProductsList($storeId, $threshold = 10) {
        $sql = "SELECT 
                    product_id,
                    product_name,
                    stock,
                    price
                FROM products 
                WHERE store_id = :store_id AND stock < :threshold
                ORDER BY stock ASC, product_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get produk toko dengan pagination
    public function getStoreProducts($storeId, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        // count total
        $countSql = "SELECT COUNT(*) as total 
                     FROM products 
                     WHERE store_id = :store_id AND stock > 0";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->bindParam(':store_id', $storeId);
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // get products
        $sql = "SELECT 
                    product_id,
                    product_name,
                    description,
                    price,
                    stock,
                    main_image_path
                FROM products
                WHERE store_id = :store_id AND stock > 0
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
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

    // get rating toko (jika ada sistem rating)
    // TODO: implementasi rating system
    public function getAverageRating($storeId) {
        // placeholder untuk future implementation
        return null;
    }

    // get total penjualan toko
    public function getTotalSales($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM orders 
                WHERE store_id = :store_id 
                AND status IN ('approved', 'on_delivery', 'received')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // search toko
    public function searchStores($keyword = '', $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        $bindings = [];
        
        $sql = "SELECT 
                    s.store_id,
                    s.store_name,
                    s.store_description,
                    s.store_logo_path,
                    COUNT(DISTINCT p.product_id) as product_count
                FROM {$this->table} s
                LEFT JOIN products p ON s.store_id = p.store_id AND p.stock > 0";
        
        if (!empty($keyword)) {
            $sql .= " WHERE (s.store_name LIKE :keyword OR s.store_description LIKE :keyword)";
            $bindings[':keyword'] = '%' . $keyword . '%';
        }
        
        $sql .= " GROUP BY s.store_id, s.store_name, s.store_description, s.store_logo_path";
        
        // count total
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as filtered";
        $countStmt = $this->db->prepare($countSql);
        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // get stores
        $sql .= " ORDER BY s.store_name ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'stores' => $stores,
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
}