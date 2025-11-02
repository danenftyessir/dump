<?php

namespace Service;

use Model\Order;
use Model\OrderItem;
use Model\User;
use Model\Product;
use Exception;

class OrderService
{
    private Order $orderModel;
    private OrderItem $orderItemModel;
    private User $userModel;
    private Product $productModel;

    // Ctor
    public function __construct(Order $orderModel, OrderItem $orderItemModel, User $userModel, Product $productModel) {
        $this->orderModel = $orderModel;
        $this->orderItemModel = $orderItemModel;
        $this->userModel = $userModel;
        $this->productModel = $productModel;
    }

    // Manage proses checkout (make order, order items, and reduce stok)
    public function prosessCheckoutTransaction(int $buyerId, array $cartSummary, string $shippingAddress) {
        foreach ($cartSummary['stores'] as $store) {
            $orderData = [
                'buyer_id' => $buyerId,
                'store_id' => $store['store_id'],
                'total_price' => $store['subtotal'],
                'shipping_address' => $shippingAddress,
                'status' => Order::STATUS_WAITING_APPROVAL
            ];

            // Buat record order
            $newOrder = $this->orderModel->create($orderData);
            if (!$newOrder) {
                throw new Exception('Gagal membuat order untuk toko ID: ' . $store['store_id']);
            }
            $orderId = $newOrder['order_id'];

            // Buat order items dan kurangi stok produk
            $items = $store['items'];

            foreach ($store['items'] as $item) {
                $this->orderItemModel->create([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price_at_order' => $item['price'],
                    'subtotal' => $item['item_subtotal']
                ]);
            }

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                $success = $this->productModel->updateStock($productId, -$quantity);
                if (!$success) {
                    throw new Exception('Gagal mengurangi stok untuk produk ID: ' . $productId);
                }
            }
        }
        return true;
    }

    // Memproses pembatalan order
    public function rejectOrder(int $orderId, int $storeId, string $rejectReason) {
        $order = $this->orderModel->find($orderId);

        // validasi kepemilikan dan status order
        if (!$order || $order['status'] !== Order::STATUS_WAITING_APPROVAL || $order['store_id'] !== $storeId) {
            throw new Exception("Order tidak valid untuk ditolak.");
        }
        
        $this->userModel->beginTransaction();
        try {
            // refund ke buyer
            $this->userModel->addBalanceAtomic($order['buyer_id'], $order['total_price']);
            
            // kembalikan stok produk
            $items = $this->orderModel->getOrderItems($orderId);
            foreach ($items as $item) {
                $this->productModel->updateStock($item['product_id'], $item['quantity']); 
            }
            
            // update status order
            $this->orderModel->update($orderId, [
                'status' => Order::STATUS_REJECTED, 
                'reject_reason' => $rejectReason
            ]);

            $this->userModel->commit();
            return true;
        } catch (Exception $e) {
            $this->userModel->rollback();
            throw new Exception("Gagal memproses penolakan order: " . $e->getMessage());
        }
    }

    // Memproses penerimaan order oleh buyer
    public function completeOrder(int $orderId, int $buyerId) {
        $order = $this->orderModel->find($orderId);

        // validasi kepemilikan dan status order
        if (!$order || $order['status'] !== Order::STATUS_SHIPPED || $order['buyer_id'] !== $buyerId) {
            throw new Exception("Order tidak valid untuk diselesaikan.");
        }

        $this->userModel->beginTransaction();
        try {
            // ambil data seller
            $seller = $this->userModel->findByStoreId($order['store_id']); // Asumsi ada method ini di UserModel
            if (!$seller) throw new Exception("Seller tidak ditemukan untuk order ini.");

            // transfer dana ke seller
            $this->userModel->addBalance($seller['user_id'], $order['total_price']);

            // update status order
            $this->orderModel->update($orderId, [
                'status' => Order::STATUS_RECEIVED, 
                'received_at' => 'CURRENT_TIMESTAMP'
            ]);
            
            $this->userModel->commit();
            return true;
        } catch (Exception $e) {
            $this->userModel->rollback();
            throw new Exception("Gagal menyelesaikan order: " . $e->getMessage());
        }

        return true;
    }

    // Memproses persetujuan order oleh seller
    public function approveOrder(int $orderId, int $sellerId, string $deliveryTime) {
        $order = $this->orderModel->find($orderId);
        
        if (!$order || $order['store_id'] != $this->userModel->findByUserId($sellerId)['store_id']) {
            throw new Exception('Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
        }
        if ($order['status'] !== Order::STATUS_WAITING_APPROVAL) {
            throw new Exception('Pesanan ini tidak bisa disetujui.');
        }

        $deliveryTimestamp = date('Y-m-d H:i:s', strtotime("+$deliveryTime"));

        return $this->orderModel->update($orderId, [
            'status' => Order::STATUS_APPROVED,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'delivery_time' => $deliveryTimestamp
        ]);
    }
}