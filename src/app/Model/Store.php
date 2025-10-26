<?php
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
        return $this->update($storeId, ['balance' => $newBalance]);
    }

    // ambil data toko beserta informasi user pemilik
    public function getStoreWithOwner($storeId) {
        $sql = "SELECT s.*, u.name as owner_name, u.email as owner_email 
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
        $sql = "SELECT s.*, u.name as owner_name 
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.user_id
                ORDER BY s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }       
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // update info toko (nama, deskripsi, logo)
    public function updateStoreInfo($storeId, $data) {
        // jika ada perubahan nama toko, validasi keunikan
        if (isset($data['store_name'])) {
            $existing = $this->findByStoreName($data['store_name']);
            if ($existing && $existing['store_id'] != $storeId) {
                return false;
            }
        }
        return $this->update($storeId, $data);
    }

    // hitung total produk aktif di toko
    public function getTotalProducts($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM products 
                WHERE store_id = :store_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // hitung produk dengan stok menipis (< threshold)
    public function getLowStockProducts($storeId, $threshold = 10) {
        $sql = "SELECT COUNT(*) as total 
                FROM products 
                WHERE store_id = :store_id 
                AND deleted_at IS NULL 
                AND stock < :threshold";       
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }
}