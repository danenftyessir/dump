<?php

namespace Controller;

use Base\Controller;
use Model\Order;
use Service\AuthService;
use Service\OrderService;
use Service\CSRFService;
use Service\LoggerService;
use Exception;

class OrderController extends Controller
{
    private Order $orderModel;
    private AuthService $authService;
    private OrderService $orderService;
    private CSRFService $csrfService;
    private LoggerService $logger;

    // Ctor
    public function __construct(Order $orderModel, AuthService $authService, OrderService $orderService, CSRFService $csrfService, LoggerService $logger) {
        $this->orderModel = $orderModel;
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
    }

    // Menampilkan order history (buyer)
    public function orderHistory() {

        
        $buyerId = $this->authService->getCurrentUserId();

        $status = $_GET['status'] ?? 'all';
        
        $filters = [
            'status' => ($status === 'all') ? null : $status, // null untuk 'all', tidak ada filter
            'page'  => (int)($_GET['page'] ?? 1),
            'limit' => 10,
            'sort_by' => 'created_at',
            'sort_order' => 'DESC'
        ];

        try {
            $orderData = $this->orderModel->getOrdersForBuyer($buyerId, $filters);
            return $this->view('buyer/order-history', [
                'orders' => $orderData['orders'],
                'pagination' => $orderData['pagination'],
                'currentStatus' => $filters['status'],
                '_token' => $this->csrfService->getToken()
            ]); 
        } catch (Exception $e) {
            $this->logger->logError('Failed to load buyer orders', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat riwayat pesanan.');
            return $this->redirect('/');
        } 
    }

    // Menampilkan detail order (buyer)
    public function showOrderDetail($params) {
        $buyerId = $this->authService->getCurrentUserId();
        $orderId = (int)($params['id'] ?? 0);

        try {
            $order = $this->orderModel->getOrderDetailWithItems($orderId);

            if (!$order || $order['buyer_id'] != $buyerId) {
                return $this->error('Pesanan tidak ditemukan.', 404);
            }

            $canConfirm = ($order['status'] === Order::STATUS_ON_DELIVERY && 
                           strtotime($order['delivery_time']) < time());

            return $this->view('order/detail', [
                'order' => $order,
                'canConfirm' => $canConfirm,
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Failed to load order detail', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat detail pesanan.');
            return $this->redirect('/orders');
        }
    }

    // Konfirmasi penerimaan barang (buyer)
    public function confirmReception($params) {
        $buyerId = $this->authService->getCurrentUserId();
        $orderId = (int)($params['id'] ?? 0);

        try {
            $this->orderService->completeOrder($orderId, $buyerId);

            $this->authService->setFlashMessage('success', 'Pesanan telah diselesaikan. Terima kasih!');
            return $this->redirect('/orders/' . $orderId);
        } catch (Exception $e) {
            $this->authService->setFlashMessage('error', 'Gagal konfirmasi pesanan: ' . $e->getMessage());
            return $this->redirect('/orders/' . $orderId);
        }
    }
}