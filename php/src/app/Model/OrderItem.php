<?php

namespace Model;

use Base\Model;
use PDO;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price_at_order',
        'subtotal'
    ];

    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // get items untuk order tertentu
    public function getItemsByOrderId($orderId) {
        $sql = "SELECT 
                    oi.*,
                    p.product_name,
                    p.main_image_path,
                    p.stock as current_stock
                FROM {$this->table} oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.order_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // delete all items dalam order (untuk cancel order)
    public function deleteByOrderId($orderId) {
        $sql = "DELETE FROM {$this->table} WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        
        return $stmt->execute();
    }

    // get total items dalam order
    public function getTotalItems($orderId) {
        $sql = "SELECT COALESCE(SUM(quantity), 0) as total 
                FROM {$this->table} 
                WHERE order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get total harga dalam order
    public function getTotalPrice($orderId) {
        $sql = "SELECT COALESCE(SUM(subtotal), 0) as total 
                FROM {$this->table} 
                WHERE order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get popular products berdasarkan order items
    public function getPopularProducts($storeId = null, $limit = 10) {
        $sql = "SELECT 
                    p.product_id,
                    p.product_name,
                    p.main_image_path,
                    p.price,
                    COUNT(oi.order_item_id) as order_count,
                    COALESCE(SUM(oi.quantity), 0) as total_sold
                FROM {$this->table} oi
                JOIN products p ON oi.product_id = p.product_id
                JOIN orders o ON oi.order_id = o.order_id";
        
        $bindings = [];
        
        if ($storeId) {
            $sql .= " WHERE p.store_id = :store_id";
            $bindings[':store_id'] = $storeId;
        }
        
        $sql .= " GROUP BY p.product_id, p.product_name, p.main_image_path, p.price
                  ORDER BY total_sold DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // check apakah product pernah dibeli oleh buyer
    public function hasProductBeenOrdered($productId, $buyerId) {
        $sql = "SELECT 1 
                FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.product_id = :product_id 
                AND o.buyer_id = :buyer_id
                AND o.status = 'received'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':buyer_id', $buyerId);
        $stmt->execute();
        
        return (bool)$stmt->fetch();
    }
}