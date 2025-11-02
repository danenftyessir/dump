<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Nimonspedia</title>
    <link rel="stylesheet" href="/css/orderHistory.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Order History</h1>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="/orders" class="filter-tab <?= !isset($_GET['status']) ? 'active' : '' ?>">
                All Orders
                <?php if (isset($statusCounts['all'])): ?>
                    <span class="tab-badge"><?= $statusCounts['all'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders?status=waiting_approval" class="filter-tab <?= ($_GET['status'] ?? '') === 'waiting_approval' ? 'active' : '' ?>">
                Waiting Approval
                <?php if (isset($statusCounts['waiting_approval'])): ?>
                    <span class="tab-badge"><?= $statusCounts['waiting_approval'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders?status=approved" class="filter-tab <?= ($_GET['status'] ?? '') === 'approved' ? 'active' : '' ?>">
                Approved
                <?php if (isset($statusCounts['approved'])): ?>
                    <span class="tab-badge"><?= $statusCounts['approved'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders?status=on_delivery" class="filter-tab <?= ($_GET['status'] ?? '') === 'on_delivery' ? 'active' : '' ?>">
                On Delivery
                <?php if (isset($statusCounts['on_delivery'])): ?>
                    <span class="tab-badge"><?= $statusCounts['on_delivery'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders?status=received" class="filter-tab <?= ($_GET['status'] ?? '') === 'received' ? 'active' : '' ?>">
                Received
                <?php if (isset($statusCounts['received'])): ?>
                    <span class="tab-badge"><?= $statusCounts['received'] ?></span>
                <?php endif; ?>
            </a>
            <a href="/orders?status=rejected" class="filter-tab <?= ($_GET['status'] ?? '') === 'rejected' ? 'active' : '' ?>">
                Rejected
                <?php if (isset($statusCounts['rejected'])): ?>
                    <span class="tab-badge"><?= $statusCounts['rejected'] ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon"></div>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki riwayat pesanan. Mulai belanja sekarang!</p>
                <a href="/products" class="btn-shop">Mulai Belanja</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                    // Check if delivery time passed for on_delivery orders
                    $isDeliveryOverdue = false;
                    if ($order['status'] === 'on_delivery' && $order['delivery_time']) {
                        $deliveryTime = strtotime($order['delivery_time']);
                        $isDeliveryOverdue = time() > $deliveryTime;
                    }
                    
                    // Status display text
                    $statusText = [
                        'waiting_approval' => 'Waiting Approval',
                        'approved' => 'Approved',
                        'on_delivery' => 'On Delivery',
                        'received' => 'Received',
                        'rejected' => 'Rejected'
                    ];
                ?>
                
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-meta">
                            <span class="order-id">Order #<?= $order['order_id'] ?></span>
                            <span class="order-date">
                                 <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                            </span>
                        </div>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $statusText[$order['status']] ?? ucfirst($order['status']) ?>
                        </span>
                    </div>

                    <!-- Store Info -->
                    <div class="store-info">
                        <img 
                            src="<?= htmlspecialchars($order['store_logo_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Crect fill=\'%23ddd\' width=\'40\' height=\'40\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'20\'%3E🏪%3C/text%3E%3C/svg%3E') ?>" 
                            alt="Store Logo"
                            class="store-logo"
                        >
                        <span class="store-name"><?= htmlspecialchars($order['store_name']) ?></span>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <img 
                                    src="<?= htmlspecialchars($item['main_image_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23f0f0f0\' width=\'60\' height=\'60\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'24\'%3E📦%3C/text%3E%3C/svg%3E') ?>" 
                                    alt="Product"
                                    class="item-thumbnail"
                                >
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                    <div class="item-quantity">Qty: <?= $item['quantity'] ?> × Rp <?= number_format($item['price_at_order'], 0, ',', '.') ?></div>
                                </div>
                                <div class="item-price">
                                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Refund Info (for rejected orders) -->
                    <?php if ($order['status'] === 'rejected'): ?>
                        <div class="refund-info">
                            <div class="refund-amount">
                                Dana Dikembalikan: Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
                            </div>
                            <div class="refund-reason">
                                <strong>Alasan Penolakan:</strong><br>
                                <?= htmlspecialchars($order['reject_reason'] ?? 'Tidak ada alasan yang diberikan') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Delivery Alert (for overdue delivery) -->
                    <?php if ($isDeliveryOverdue): ?>
                        <div class="delivery-alert">
                            <strong>Waktu Pengiriman Terlampaui</strong><br>
                            Estimasi pengiriman: <?= date('d M Y, H:i', strtotime($order['delivery_time'])) ?><br>
                            Silakan konfirmasi jika barang sudah diterima.
                        </div>
                    <?php endif; ?>

                    <!-- Order Footer -->
                    <div class="order-footer">
                        <div class="order-total">
                            Total: <span class="total-amount">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
                        </div>
                        <div class="order-actions">
                            <button class="btn btn-detail" onclick="showOrderDetail(<?= htmlspecialchars(json_encode($order), ENT_QUOTES) ?>)">
                                Detail Order
                            </button>
                            <?php if ($isDeliveryOverdue): ?>
                                <button class="btn btn-confirm" onclick="confirmDelivery(<?= $order['order_id'] ?>)">
                                    Konfirmasi Diterima
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Order Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Detail Order</h2>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $_token ?? '' ?>';

        
    </script>
    <script src="/js/orderHistory.js"></script>
</body>
</html>
