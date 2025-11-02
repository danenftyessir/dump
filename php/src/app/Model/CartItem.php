<?php

namespace Model;

use Base\Model;
use PDO;

class CartItem extends Model
{
    protected $table = 'cart_items';
    protected $primaryKey = 'cart_item_id';
    
    protected $fillable = [
        'buyer_id',
        'product_id',
        'quantity',
        'created_at',
        'updated_at'
    ];

    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // Get Cart Items by Buyer ID
    public function getByBuyerId($buyerId) {
        return $this->where(['buyer_id' => $buyerId]);
    }

    // Search for same product in cart by buyer ID and product ID
    public function getSameProduct($buyerId, $productId) {
        return $this->where(['buyer_id' => $buyerId, 'product_id' => $productId]);
    }

    // Get grouped cart items by seller/store
    public function getGroupedItemsRaw($buyerId): array {
        $sql = "
            SELECT 
                s.store_id, 
                s.store_name,
                s.store_logo_path,
                ci.cart_item_id,
                ci.product_id,
                ci.quantity,
                p.product_name, 
                p.price, 
                p.main_image_path,
                p.stock,
                (p.price * ci.quantity) as item_subtotal -- Hitung subtotal di database
            FROM {$this->table} ci
            JOIN products p ON ci.product_id = p.product_id
            JOIN stores s ON p.store_id = s.store_id
            WHERE ci.buyer_id = :buyer_id
            ORDER BY s.store_id, ci.created_at
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':buyer_id', $buyerId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Clear semua item dalam keranjang buyer
    public function deleteAllByBuyerId(int $buyerId) {
        $sql = "DELETE FROM {$this->table} WHERE buyer_id = :buyer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':buyer_id', $buyerId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}