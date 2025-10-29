<?php

namespace Model;

use Base\Model;
use PDO;
use Exception;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    
    protected $fillable = [
        'buyer_id',
        'store_id',
        'total_price',
        'shipping_address',
        'status',
        'reject_reason',
        'confirmed_at',
        'delivery_time',
        'received_at'
    ];

    // status constants
    const STATUS_WAITING_APPROVAL = 'waiting_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ON_DELIVERY = 'on_delivery';
    const STATUS_RECEIVED = 'received';

    // get pending orders count untuk seller
    public function getPendingOrdersCount($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE store_id = :store_id 
                AND status = :status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindValue(':status', self::STATUS_WAITING_APPROVAL);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get total revenue untuk seller
    public function getTotalRevenue($storeId) {
        $sql = "SELECT COALESCE(SUM(total_price), 0) as total 
                FROM {$this->table} 
                WHERE store_id = :store_id 
                AND status IN (:approved, :on_delivery, :received)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindValue(':approved', self::STATUS_APPROVED);
        $stmt->bindValue(':on_delivery', self::STATUS_ON_DELIVERY);
        $stmt->bindValue(':received', self::STATUS_RECEIVED);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // get orders untuk seller dengan filter
    public function getOrdersForSeller($storeId, $filters = []) {
        $sql = "SELECT 
                    o.*,
                    u.name as buyer_name,
                    u.email as buyer_email
                FROM {$this->table} o
                JOIN users u ON o.buyer_id = u.user_id
                WHERE o.store_id = :store_id";
        
        $bindings = [':store_id' => $storeId];
        
        // filter status
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $bindings[':status'] = $filters['status'];
        }
        
        // filter date range
        if (!empty($filters['date_from'])) {
            $sql .= " AND o.created_at >= :date_from";
            $bindings[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND o.created_at <= :date_to";
            $bindings[':date_to'] = $filters['date_to'];
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
        
        $allowedSortBy = ['created_at', 'total_price', 'status'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        }
        
        $sql .= " ORDER BY o.{$sortBy} {$sortOrder}";
        
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
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'orders' => $orders,
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

    // get orders untuk buyer
    public function getOrdersForBuyer($buyerId, $filters = []) {
        $sql = "SELECT 
                    o.*,
                    s.store_name,
                    s.store_logo_path
                FROM {$this->table} o
                JOIN stores s ON o.store_id = s.store_id
                WHERE o.buyer_id = :buyer_id";
        
        $bindings = [':buyer_id' => $buyerId];
        
        // filter status
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $bindings[':status'] = $filters['status'];
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
        $sql .= " ORDER BY o.created_at DESC";
        
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
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'orders' => $orders,
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

    // get order detail dengan items
    public function getOrderWithItems($orderId) {
        // get order
        $order = $this->find($orderId);
        
        if (!$order) {
            return null;
        }
        
        // get order items
        $sql = "SELECT 
                    oi.*,
                    p.product_name,
                    p.main_image_path
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.order_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // get buyer info
        $sql = "SELECT user_id, name, email FROM users WHERE user_id = :buyer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':buyer_id', $order['buyer_id']);
        $stmt->execute();
        $order['buyer'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // get store info
        $sql = "SELECT store_id, store_name, store_logo_path FROM stores WHERE store_id = :store_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $order['store_id']);
        $stmt->execute();
        $order['store'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $order;
    }

    // approve order (seller)
    public function approveOrder($orderId, $deliveryTime) {
        $order = $this->find($orderId);
        
        if (!$order) {
            throw new Exception('Order tidak ditemukan');
        }
        
        if ($order['status'] !== self::STATUS_WAITING_APPROVAL) {
            throw new Exception('Order tidak bisa diapprove');
        }
        
        return $this->update($orderId, [
            'status' => self::STATUS_APPROVED,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'delivery_time' => $deliveryTime
        ]);
    }

    // reject order (seller)
    public function rejectOrder($orderId, $rejectReason) {
        $order = $this->find($orderId);
        
        if (!$order) {
            throw new Exception('Order tidak ditemukan');
        }
        
        if ($order['status'] !== self::STATUS_WAITING_APPROVAL) {
            throw new Exception('Order tidak bisa direject');
        }
        
        return $this->update($orderId, [
            'status' => self::STATUS_REJECTED,
            'reject_reason' => $rejectReason
        ]);
    }

    // set order on delivery (seller)
    public function setOnDelivery($orderId) {
        $order = $this->find($orderId);
        
        if (!$order) {
            throw new Exception('Order tidak ditemukan');
        }
        
        if ($order['status'] !== self::STATUS_APPROVED) {
            throw new Exception('Order harus approved dulu');
        }
        
        return $this->update($orderId, [
            'status' => self::STATUS_ON_DELIVERY
        ]);
    }

    // confirm receive (buyer)
    public function confirmReceive($orderId) {
        $order = $this->find($orderId);
        
        if (!$order) {
            throw new Exception('Order tidak ditemukan');
        }
        
        if ($order['status'] !== self::STATUS_ON_DELIVERY) {
            throw new Exception('Order belum dikirim');
        }
        
        return $this->update($orderId, [
            'status' => self::STATUS_RECEIVED,
            'received_at' => date('Y-m-d H:i:s')
        ]);
    }

    // check if order belongs to buyer
    public function isOwnedByBuyer($orderId, $buyerId) {
        $order = $this->find($orderId);
        return $order && $order['buyer_id'] == $buyerId;
    }

    // check if order belongs to store
    public function isOwnedByStore($orderId, $storeId) {
        $order = $this->find($orderId);
        return $order && $order['store_id'] == $storeId;
    }

    // get order statistics by status untuk seller
    public function getOrderStatsByStatus($storeId) {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    COALESCE(SUM(total_price), 0) as total_price
                FROM {$this->table}
                WHERE store_id = :store_id
                GROUP BY status
                ORDER BY 
                    CASE status
                        WHEN 'waiting_approval' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'on_delivery' THEN 3
                        WHEN 'received' THEN 4
                        WHEN 'rejected' THEN 5
                    END";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['status']] = [
                'count' => $row['count'],
                'total_price' => $row['total_price']
            ];
        }
        
        return $stats;
    }

    // get recent orders untuk dashboard
    public function getRecentOrders($storeId, $limit = 5) {
        $sql = "SELECT 
                    o.order_id,
                    o.total_price,
                    o.status,
                    o.created_at,
                    u.name as buyer_name
                FROM {$this->table} o
                JOIN users u ON o.buyer_id = u.user_id
                WHERE o.store_id = :store_id
                ORDER BY o.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}