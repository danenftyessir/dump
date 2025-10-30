<?php

namespace Controller;

use Base\Controller;
use Model\Order;
use Model\Store;
use Service\AuthService;
use Exception;

class SellerOrderController extends Controller
{
    private $orderModel;
    private $storeModel;
    private $authService;

    public function __construct(Order $orderModel, Store $storeModel, AuthService $authService)
    {
        $this->orderModel = $orderModel;
        $this->storeModel = $storeModel;
        $this->authService = $authService;
    }

    // menampilkan halaman daftar pesanan seller
    public function index()
    {
        // cek autentikasi
        $user = $this->authService->getCurrentUser();
        if (!$user || $user['role'] !== 'SELLER') {
            header('Location: /login');
            exit;
        }

        // ambil store seller menggunakan findByUserId
        $store = $this->storeModel->findByUserId($user['user_id']);
        
        // FALLBACK LOGIC: auto-create toko jika belum ada
        if (!$store) {
            try {
                // buat toko otomatis menggunakan nama dari session
                $storeData = [
                    'user_id' => $user['user_id'],
                    'store_name' => $user['name'] . "'s Store",
                    'store_description' => 'Selamat Datang Di Toko Saya!',
                    'store_logo_path' => null,
                    'balance' => 0
                ];
                
                $store = $this->storeModel->create($storeData);
                
                if (!$store) {
                    header('Location: /seller/dashboard');
                    exit;
                }
                
            } catch (Exception $e) {
                header('Location: /seller/dashboard');
                exit;
            }
        }

        // ambil parameter filter dan pagination
        $status = $_GET['status'] ?? 'all';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // ambil data pesanan dengan pagination
        $ordersData = $this->orderModel->getOrdersBySeller($store['store_id'], $status, $perPage, $offset);
        
        // hitung total halaman
        $totalOrders = $this->orderModel->countOrdersBySeller($store['store_id'], $status);
        $totalPages = ceil($totalOrders / $perPage);

        // ambil statistik pesanan
        $stats = [
            'waiting_approval' => $this->orderModel->countOrdersByStatus($store['store_id'], 'waiting_approval'),
            'approved' => $this->orderModel->countOrdersByStatus($store['store_id'], 'approved'),
            'on_delivery' => $this->orderModel->countOrdersByStatus($store['store_id'], 'on_delivery'),
            'received' => $this->orderModel->countOrdersByStatus($store['store_id'], 'received'),
            'rejected' => $this->orderModel->countOrdersByStatus($store['store_id'], 'rejected'),
        ];

        $this->view('seller/orders/index', [
            'store' => $store,
            'orders' => $ordersData,
            'currentStatus' => $status,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders,
            'stats' => $stats
        ]);
    }

    // API untuk mendapatkan detail pesanan (AJAX)
    public function getOrderDetail()
    {
        header('Content-Type: application/json');

        // cek autentikasi
        $user = $this->authService->getCurrentUser();
        if (!$user || $user['role'] !== 'SELLER') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // ambil order_id dari query string
        $orderId = $_GET['order_id'] ?? null;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Order ID Required']);
            exit;
        }

        // ambil store seller
        $store = $this->storeModel->findByUserId($user['user_id']);
        
        // FALLBACK LOGIC: auto-create toko jika belum ada
        if (!$store) {
            try {
                // buat toko otomatis menggunakan nama dari session
                $storeData = [
                    'user_id' => $user['user_id'],
                    'store_name' => $user['name'] . "'s Store",
                    'store_description' => 'Selamat Datang Di Toko Saya!',
                    'store_logo_path' => null,
                    'balance' => 0
                ];
                
                $store = $this->storeModel->create($storeData);
                
                if (!$store) {
                    echo json_encode(['success' => false, 'message' => 'Failed To Create Store']);
                    exit;
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
        }

        // ambil detail pesanan dengan items
        $orderDetail = $this->orderModel->getOrderDetailWithItems($orderId, $store['store_id']);
        
        if (!$orderDetail) {
            echo json_encode(['success' => false, 'message' => 'Order Not Found Or Unauthorized']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $orderDetail]);
    }

    // API untuk update status pesanan (AJAX)
    public function updateOrderStatus()
    {
        header('Content-Type: application/json');

        // cek autentikasi
        $user = $this->authService->getCurrentUser();
        if (!$user || $user['role'] !== 'SELLER') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // ambil data dari request body
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['order_id'] ?? null;
        $newStatus = $input['status'] ?? null;
        $deliveryTime = $input['delivery_time'] ?? null;
        $rejectReason = $input['reject_reason'] ?? null;

        if (!$orderId || !$newStatus) {
            echo json_encode(['success' => false, 'message' => 'Order ID And Status Required']);
            exit;
        }

        // ambil store seller
        $store = $this->storeModel->findByUserId($user['user_id']);
        
        // FALLBACK LOGIC: auto-create toko jika belum ada
        if (!$store) {
            try {
                // buat toko otomatis menggunakan nama dari session
                $storeData = [
                    'user_id' => $user['user_id'],
                    'store_name' => $user['name'] . "'s Store",
                    'store_description' => 'Selamat Datang Di Toko Saya!',
                    'store_logo_path' => null,
                    'balance' => 0
                ];
                
                $store = $this->storeModel->create($storeData);
                
                if (!$store) {
                    echo json_encode(['success' => false, 'message' => 'Failed To Create Store']);
                    exit;
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
        }

        // validasi bahwa pesanan milik toko ini
        $order = $this->orderModel->getOrderById($orderId);
        if (!$order || $order['store_id'] !== $store['store_id']) {
            echo json_encode(['success' => false, 'message' => 'Order Not Found Or Unauthorized']);
            exit;
        }

        // validasi status transition
        $validTransitions = [
            'waiting_approval' => ['approved', 'rejected'],
            'approved' => ['on_delivery'],
            'on_delivery' => [],
            'received' => [],
            'rejected' => []
        ];

        if (!in_array($newStatus, $validTransitions[$order['status']] ?? [])) {
            echo json_encode(['success' => false, 'message' => 'Invalid Status Transition']);
            exit;
        }

        // update status
        try {
            $updateData = ['status' => $newStatus];
            
            if ($newStatus === 'approved') {
                $updateData['confirmed_at'] = date('Y-m-d H:i:s');
                
                if ($deliveryTime) {
                    $updateData['delivery_time'] = $deliveryTime;
                }
            } elseif ($newStatus === 'rejected') {
                $updateData['reject_reason'] = $rejectReason;
                
                // refund balance ke buyer
                $this->orderModel->refundOrder($orderId);
            } elseif ($newStatus === 'on_delivery') {
                // tidak ada field tambahan untuk on_delivery
            }
            
            $result = $this->orderModel->updateOrderStatus($orderId, $updateData);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Status Berhasil Diupdate']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal Update Status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}