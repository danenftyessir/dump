<?php

namespace Model;

use Base\Model;
use PDO;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    
    protected $fillable = [
        'name'
    ];

    // Ctor
    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // cari kategori berdasarkan nama
    public function findByName($name) {
        return $this->first(['name' => $name]);
    }

    // get semua kategori dengan jumlah produk
    public function getAllWithProductCount() {
        $sql = "SELECT 
                    c.category_id,
                    c.name,
                    COUNT(DISTINCT ci.product_id) as product_count
                FROM {$this->table} c
                LEFT JOIN category_items ci ON c.category_id = ci.category_id
                GROUP BY c.category_id, c.name
                ORDER BY c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get produk dalam kategori ini
    public function getProducts($categoryId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*
                FROM products p
                JOIN category_items ci ON p.product_id = ci.product_id
                WHERE ci.category_id = :category_id
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // hitung jumlah produk dalam kategori
    public function countProducts($categoryId) {
        $sql = "SELECT COUNT(*) as total
                FROM category_items
                WHERE category_id = :category_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}