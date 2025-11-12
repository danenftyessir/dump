<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_token ?? ''); ?>">
    <title>Order History</title>
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/buyer/order-history.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/toast.css">
    <link rel="stylesheet" href="/css/components/footer.css">
</head>
<body>
    <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Order History</h1>
            <div class="sort-control">
                <label for="sortOrder">Urutkan:</label>
                <select id="sortOrder" class="sort-select">
                    <option value="desc">Terbaru</option>
                    <option value="asc">Terlama</option>
                </select>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button type="button" class="filter-tab active" data-status="">
                All Orders
                <?php if (isset($statusCounts['all'])): ?>
                    <span class="tab-badge"><?= $statusCounts['all'] ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="filter-tab" data-status="waiting_approval">
                Waiting Approval
                <?php if (isset($statusCounts['waiting_approval'])): ?>
                    <span class="tab-badge"><?= $statusCounts['waiting_approval'] ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="filter-tab" data-status="approved">
                Approved
                <?php if (isset($statusCounts['approved'])): ?>
                    <span class="tab-badge"><?= $statusCounts['approved'] ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="filter-tab" data-status="on_delivery">
                On Delivery
                <?php if (isset($statusCounts['on_delivery'])): ?>
                    <span class="tab-badge"><?= $statusCounts['on_delivery'] ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="filter-tab" data-status="received">
                Received
                <?php if (isset($statusCounts['received'])): ?>
                    <span class="tab-badge"><?= $statusCounts['received'] ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="filter-tab" data-status="rejected">
                Rejected
                <?php if (isset($statusCounts['rejected'])): ?>
                    <span class="tab-badge"><?= $statusCounts['rejected'] ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Orders List -->
        <div id="ordersContainer">
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="8" cy="21" r="1"/>
                            <circle cx="19" cy="21" r="1"/>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                </div>
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
                            src="<?= htmlspecialchars($order['store_logo_path'] ?? '/asset/images/default-store.svg') ?>" 
                            alt="Store Logo"
                            class="store-logo"
                            onerror="this.src='/asset/images/default-store.svg'"
                        >
                        <span class="store-name"><?= htmlspecialchars($order['store_name']) ?></span>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items">
                        <?php if (!empty($order['items'])): ?>
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <img 
                                    src="<?= htmlspecialchars($item['main_image_path'] ?? '/asset/images/default-product.svg') ?>" 
                                    alt="Product"
                                    class="item-thumbnail"
                                    onerror="this.src='/asset/images/default-product.svg'"
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
                        <?php else: ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-name">Tidak ada item dalam pesanan ini</div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                            <?php // Tombol konfirmasi hanya muncul jika waktu pengiriman sudah terlewat ?>
                            <?php if ($isDeliveryOverdue): ?>
                                <button class="btn btn-confirm" onclick="confirmDelivery(<?= $order['order_id'] ?>)">
                                    Konfirmasi Diterima
                                </button>
                            <?php endif; ?>
                            <?php // TESTING ONLY: Uncomment line below to allow confirmation anytime ?>
                            <?php // if ($order['status'] === 'on_delivery'): ?>
                            <?php //     <button class="btn btn-confirm" onclick="confirmDelivery(<?= $order['order_id'] ?>)">Konfirmasi Diterima</button> ?>
                            <?php // endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
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

    <!-- Confirm Delivery Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 class="modal-title">Konfirmasi Penerimaan</h2>
                <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin: 0 auto 20px;">
                        <circle cx="40" cy="40" r="36" fill="#FFF3CD" stroke="#FFC107" stroke-width="2"/>
                        <path d="M40 25V45" stroke="#FFC107" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="40" cy="55" r="3" fill="#FFC107"/>
                    </svg>
                    <p style="font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #333;">Apakah Anda yakin?</p>
                    <p style="color: #666; margin-bottom: 0;">Barang sudah diterima dalam kondisi baik?</p>
                    <p style="color: #999; font-size: 14px; margin-top: 10px;">Dana akan ditransfer ke seller setelah konfirmasi</p>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; padding: 20px; border-top: 1px solid #e0e0e0;">
                <button id="cancelDeliveryBtn" onclick="closeConfirmModal()" class="btn btn-secondary" style="flex: 1; padding: 12px; border: 1px solid #ddd; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Batal
                </button>
                <button id="confirmDeliveryBtn" onclick="proceedConfirmDelivery()" class="btn btn-primary" style="flex: 1; padding: 12px; border: none; background: #00a860; color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Ya, Saya Sudah Terima
                </button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="/js/components/toast.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
    <script src="/js/buyer/order-history.js"></script>
</body>
</html>
