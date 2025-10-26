<?php
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

    // hitung jumlah order dengan status waiting_approval untuk toko tertentu
    public function getPendingOrdersCount($storeId) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE store_id = :store_id 
                AND status = 'waiting_approval'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
        $stmt->execute();        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // hitung total pendapatan dari order yang sudah diterima (status received)
    public function getTotalRevenue($storeId) {
        $sql = "SELECT COALESCE(SUM(total_price), 0) as total_revenue 
                FROM {$this->table} 
                WHERE store_id = :store_id 
                AND status = 'received'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
        $stmt->execute();        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total_revenue'];
    }

    // ambil order berdasarkan buyer_id
    public function getOrdersByBuyer($buyerId, $limit = null, $offset = null) {
        $sql = "SELECT o.*, s.store_name, s.store_logo_path
                FROM {$this->table} o
                JOIN stores s ON o.store_id = s.store_id
                WHERE o.buyer_id = :buyer_id
                ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':buyer_id', $buyerId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ambil order berdasarkan store_id dengan pagination
    public function getOrdersByStore($storeId, $limit = null, $offset = null, $status = null) {
        $sql = "SELECT o.*, u.name as buyer_name, u.email as buyer_email
                FROM {$this->table} o
                JOIN users u ON o.buyer_id = u.user_id
                WHERE o.store_id = :store_id";
        
        if ($status) {
            $sql .= " AND o.status = :status";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT); 
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
        }       
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // hitung total order berdasarkan store_id dan status
    public function countOrdersByStore($storeId, $status = null) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE store_id = :store_id";
        
        if ($status) {
            $sql .= " AND status = :status";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);       
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }       
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // update status order
    public function updateStatus($orderId, $status, $additionalData = []) {
        $data = array_merge(['status' => $status], $additionalData);       
        // set timestamp sesuai status
        if ($status === 'approved' && !isset($data['confirmed_at'])) {
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'on_delivery' && !isset($data['delivery_time'])) {
            $data['delivery_time'] = date('Y-m-d H:i:s');
        } elseif ($status === 'received' && !isset($data['received_at'])) {
            $data['received_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->update($orderId, $data);
    }

    // ambil detail order dengan items
    public function getOrderWithItems($orderId) {
        // ambil data order
        $order = $this->find($orderId);
        if (!$order) {
            return null;
        }
        
        // ambil order items
        $sql = "SELECT oi.*, p.product_name, p.main_image_path
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);        
        return $order;
    }
}