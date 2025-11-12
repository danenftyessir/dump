<?php

namespace Controller;

use Base\Controller;
use Model\Order;
use Service\AuthService;
use Service\OrderService;
use Service\CSRFService;
use Service\LoggerService;
use Core\Request;
use Exception;

class OrderController extends Controller
{
    private Order $orderModel;
    private AuthService $authService;
    private OrderService $orderService;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private Request $request;

    // Ctor
    public function __construct(Order $orderModel, AuthService $authService, OrderService $orderService, CSRFService $csrfService, LoggerService $logger, Request $request) {
        $this->orderModel = $orderModel;
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->request = $request;
    }

    // Menampilkan order history (buyer)
    public function orderHistory() {
        $buyerId = $this->authService->getCurrentUserId();

        $status = $this->request->get('status', 'all');
        $sortOrder = $this->request->get('sort', 'desc'); // 'asc' or 'desc'

        $filters = [
            'status' => ($status === 'all') ? null : $status,
            'page'  => (int)($this->request->get('page', 1)),
            'limit' => 10,
            'sort_by' => 'created_at',
            'sort_order' => strtoupper($sortOrder)
        ];

        try {
            $orderData = $this->orderModel->getOrdersForBuyer($buyerId, $filters);
            $statusCounts = $this->orderModel->getOrderStatusCounts($buyerId);
            $currentUser = $this->authService->getCurrentUser();
            
            if ($this->isAjax()) {
                return $this->json([
                    'success' => true,
                    'orders' => $orderData['orders'],
                    'pagination' => $orderData['pagination']
                ]);
            }
            
            return $this->view('buyer/order-history', [
                'orders' => $orderData['orders'],
                'pagination' => $orderData['pagination'],
                'statusCounts' => $statusCounts,
                'currentStatus' => $filters['status'],
                'currentUser' => $currentUser,
                '_token' => $this->csrfService->getToken()
            ]); 
        } catch (Exception $e) {
            $this->logger->logError('Failed to load buyer orders', ['buyer_id' => $buyerId, 'error' => $e->getMessage()]);
            
            if ($this->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Gagal memuat riwayat pesanan.'
                ], 500);
            }
            
            $this->authService->setFlashMessage('error', 'Gagal memuat riwayat pesanan.');
            return $this->redirect('/');
        } 
    }

    // Menampilkan detail order (buyer)
    public function showOrderDetail($params) {
        $buyerId = $this->authService->getCurrentUserId();
        $orderId = (int)($params['id'] ?? 0);

        try {
            // Get order by ID first, then validate buyer ownership
            $order = $this->orderModel->getOrderById($orderId);

            if (!$order || $order['buyer_id'] != $buyerId) {
                return $this->error('Pesanan tidak ditemukan.', 404);
            }

            // Get full order details with items
            $order = $this->orderModel->getOrderDetailForBuyer($orderId, $buyerId);

            if (!$order) {
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
    public function confirmReception($id = null) {
        $buyerId = $this->authService->getCurrentUserId();
        $orderId = (int)$id;

        try {
            $this->orderService->completeOrder($orderId, $buyerId);

            // Check if request is AJAX
            if ($this->isAjax()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Pesanan telah diselesaikan. Terima kasih!'
                ]);
            }

            $this->authService->setFlashMessage('success', 'Pesanan telah diselesaikan. Terima kasih!');
            return $this->redirect('/orders/' . $orderId);
        } catch (Exception $e) {
            $this->logger->logError('Failed to confirm order reception', [
                'order_id' => $orderId, 
                'buyer_id' => $buyerId, 
                'error' => $e->getMessage()
            ]);

            // Check if request is AJAX
            if ($this->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Gagal konfirmasi pesanan: ' . $e->getMessage()
                ], 400);
            }

            $this->authService->setFlashMessage('error', 'Gagal konfirmasi pesanan: ' . $e->getMessage());
            return $this->redirect('/orders/' . $orderId);
        }
    }
}