<?php

namespace Service;

use Model\Order;
use Model\OrderItem;
use Model\User;
use Model\Product;
use Service\CartService;
use Exception;

class OrderService
{
    private Order $orderModel;
    private OrderItem $orderItemModel;
    private User $userModel;
    private Product $productModel;
    private CartService $cartService;

    // Ctor
    public function __construct(Order $orderModel, OrderItem $orderItemModel, User $userModel, Product $productModel, CartService $cartService) {
        $this->orderModel = $orderModel;
        $this->orderItemModel = $orderItemModel;
        $this->userModel = $userModel;
        $this->productModel = $productModel;
        $this->cartService = $cartService;
    }

    // Complete order placement process
    public function placeOrder(int $buyerId, string $shippingAddress, int $confirmedTotalPrice): array {
        $buyer = $this->userModel->find($buyerId);
        $cartSummary = $this->cartService->getCartSummary($buyerId);
        $grandTotal = $cartSummary['grand_total'];

        // Validate checkout data
        if ($grandTotal === 0) {
            throw new Exception('Keranjang kosong.');
        }
        if ($confirmedTotalPrice != $grandTotal) {
            throw new Exception('Total harga tidak cocok. Silakan ulangi checkout.');
        }
        if ($buyer['balance'] < $grandTotal) {
            throw new Exception('Saldo tidak mencukupi. Silakan top up.');
        }
        if (!$shippingAddress) {
            throw new Exception('Alamat pengiriman wajib diisi.');
        }

        // Process transaction atomically
        $this->userModel->beginTransaction();
        
        try {
            // Deduct buyer balance
            $this->userModel->deductBalance($buyerId, $grandTotal);

            // Create orders and reduce stock
            $this->prosessCheckoutTransaction($buyerId, $cartSummary, $shippingAddress);

            // Clear cart
            $this->cartService->clearCart($buyerId);
            
            // Commit transaction
            $this->userModel->commit();
            
            // Get updated buyer data
            $updatedBuyer = $this->userModel->find($buyerId);
            
            return [
                'total_price' => $grandTotal,
                'new_balance' => $updatedBuyer['balance']
            ];
            
        } catch (Exception $e) {
            $this->userModel->rollback();
            throw $e;
        }
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
        $order = $this->orderModel->getOrderById($orderId);

        // validasi order exists
        if (!$order) {
            throw new Exception("Order tidak ditemukan.");
        }

        // validasi kepemilikan
        if ($order['buyer_id'] !== $buyerId) {
            throw new Exception("Anda tidak memiliki akses ke order ini.");
        }

        // validasi status order
        if ($order['status'] !== Order::STATUS_ON_DELIVERY) {
            throw new Exception("Order hanya bisa dikonfirmasi jika status sedang On Delivery. Status saat ini: " . $order['status']);
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
                'received_at' => date('Y-m-d H:i:s')
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
    public function approveOrder(int $orderId, int $storeId, string $deliveryTime) {
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['store_id'] != $storeId) {
            throw new Exception('Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
        }
        if ($order['status'] !== Order::STATUS_WAITING_APPROVAL) {
            throw new Exception('Pesanan ini tidak bisa disetujui.');
        }

        // Convert date string (YYYY-MM-DD) to datetime timestamp
        $deliveryTimestamp = date('Y-m-d H:i:s', strtotime($deliveryTime . ' 23:59:59'));

        return $this->orderModel->update($orderId, [
            'status' => Order::STATUS_APPROVED,
            'confirmed_at' => date('Y-m-d H:i:s'),
            'delivery_time' => $deliveryTimestamp
        ]);
    }

    // Mengubah status order menjadi on_delivery oleh seller
    public function setOrderOnDelivery(int $orderId, int $storeId) {
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['store_id'] !== $storeId) {
            throw new Exception('Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
        }
        if ($order['status'] !== Order::STATUS_APPROVED) {
            throw new Exception('Pesanan hanya bisa dikirim jika statusnya sudah disetujui.');
        }

        return $this->orderModel->update($orderId, [
            'status' => Order::STATUS_ON_DELIVERY
        ]);
    }
}