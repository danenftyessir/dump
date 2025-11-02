<?php

namespace Controller;

use Base\Controller;
use Model\Order;
use Model\Store;
use Service\AuthService;
use Service\OrderService;
use Service\CSRFService;
use Service\LoggerService;
use Service\CSVExportService;
use Exception;

class SellerOrderController extends Controller
{
    private Order $orderModel;
    private Store $storeModel;
    private AuthService $authService;
    private OrderService $orderService;
    private CSRFService $csrfService;
    private LoggerService $logger;
    private CSVExportService $csvExportService;

    public function __construct(Order $orderModel, Store $storeModel, AuthService $authService, OrderService $orderService, CSRFService $csrfService, LoggerService $logger, CSVExportService $csvExportService)
    {
        $this->orderModel = $orderModel;
        $this->storeModel = $storeModel;
        $this->authService = $authService;
        $this->orderService = $orderService;
        $this->csrfService = $csrfService;
        $this->logger = $logger;
        $this->csvExportService = $csvExportService;
    }

    // Menampilkan halaman daftar order(seller)
    public function index() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            // Ambil filter (termasuk status)
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => 10,
                // (AC: Search)
                'search' => $_GET['search'] ?? null 
            ];

            // Ambil data pesanan
            $orderData = $this->orderModel->getOrdersForSeller($store['store_id'], $filters);
            
            // Ambil statistik untuk tab filter
            $statusCounts = $this->orderModel->getOrderStatsByStatus($store['store_id']);

            return $this->view('seller/orders', [
                'orders' => $orderData['orders'],
                'pagination' => $orderData['pagination'],
                'statusCounts' => $statusCounts,
                'currentStatus' => $filters['status'],
                'currentSearch' => $filters['search'],
                '_token' => $this->csrfService->getToken()
            ]);
        } catch (Exception $e) {
            $this->logger->logError('Gagal memuat halaman daftar pesanan', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal memuat halaman.');
            return $this->redirect('/seller/dashboard');
        }
    }

    // api untuk mendapatkan detail order(seller)
    public function getOrderDetail() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);
            $orderId = (int)($params['id'] ?? 0);

            $order = $this->orderModel->getOrderDetailWithItems($orderId, $store['store_id']);

            if (!$order) {
                return $this->error('Pesanan tidak ditemukan atau Anda tidak memiliki akses.', 404);
            }
            
            return $this->success('Detail pesanan diambil', $order);

        } catch (Exception $e) {
            return $this->error('Gagal mengambil detail: ' . $e->getMessage(), 500);
        }
    }

    // api untuk approve order(seller)
    public function approve($params) {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);
            $orderId = (int)($params['id'] ?? 0);
            
            $deliveryTime = $_POST['delivery_time'] ?? null;
            if (empty($deliveryTime) || (int)$deliveryTime <= 0) {
                return $this->error('Estimasi waktu pengiriman (hari) wajib diisi.', 400);
            }

            $this->orderService->approveOrder($orderId, $store['store_id'], $deliveryTime);
            
            return $this->success('Pesanan berhasil disetujui.');

        } catch (Exception $e) {
            $this->logger->logError('Approve order gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        }
    }

    // api untuk menolak order(seller)
    public function reject($params) {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);
            $orderId = (int)($params['id'] ?? 0);
            $reason = $_POST['reject_reason'] ?? '';

            if (empty(trim($reason))) {
                return $this->error('Alasan penolakan wajib diisi.', 400);
            }
            
            $this->orderService->rejectOrder($orderId, $store['store_id'], $reason);
            
            return $this->success('Pesanan berhasil ditolak. Saldo buyer telah dikembalikan.');

        } catch (Exception $e) {
            $this->logger->logError('Reject order gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        }
    }

    // api untuk menandai order sebagai dikirim(seller)
    public function setDelivery($params)
    {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);
            $orderId = (int)($params['id'] ?? 0);

            $this->orderService->setOrderOnDelivery($orderId, $store['store_id']);
            
            return $this->success('Status pesanan diubah menjadi "Dalam Pengiriman".');

        } catch (Exception $e) {
            $this->logger->logError('Set delivery gagal', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 400);
        }
    }

    // Export orders to CSV
    public function export() {
        try {
            $sellerId = $this->authService->getCurrentUserId();
            $store = $this->storeModel->findByUserId($sellerId);

            if (!$store) {
                $this->authService->setFlashMessage('error', 'Toko tidak ditemukan.');
                return $this->redirect('/seller/dashboard');
            }

            // Get filters from query params
            $filters = [
                'status' => $_GET['status'] ?? 'all',
                'search' => $_GET['search'] ?? null,
                'page' => 1,
                'limit' => 10000  // Get all orders (large limit for export)
            ];

            // Get all orders for this store (without pagination for export)
            $orderData = $this->orderModel->getOrdersForSeller($store['store_id'], $filters);
            $orders = $orderData['orders'] ?? [];

            // Generate CSV
            $csvContent = $this->csvExportService->exportOrders($orders);

            // Generate filename with timestamp
            $storeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $store['store_name']);
            $statusLabel = $filters['status'] === 'all' ? 'semua' : $filters['status'];
            $filename = 'orders_' . $storeName . '_' . $statusLabel . '_' . date('Y-m-d_His') . '.csv';

            // Send CSV download
            $this->csvExportService->downloadCSV($csvContent, $filename);

        } catch (Exception $e) {
            $this->logger->logError('Gagal export orders', ['error' => $e->getMessage()]);
            $this->authService->setFlashMessage('error', 'Gagal export orders.');
            return $this->redirect('/seller/orders');
        }
    }
}