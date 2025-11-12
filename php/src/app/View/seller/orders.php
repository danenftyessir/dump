<?php
$store = $store ?? null;
$orders = $orders ?? [];
$currentStatus = $currentStatus ?? 'all';
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalOrders = $totalOrders ?? 0;
$stats = $stats ?? [
    'waiting_approval' => 0,
    'approved' => 0,
    'on_delivery' => 0,
    'received' => 0,
    'rejected' => 0
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
=======
    <meta name="csrf-token" content="<?= htmlspecialchars($_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
>>>>>>> origin/master
    <title>Kelola Pesanan - Nimonspedia</title>
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-seller.css">
    <link rel="stylesheet" href="/css/seller/seller-orders.css">
    <link rel="stylesheet" href="/css/seller/seller-common.css">
</head>
<body>
    <!-- navbar seller -->
    <?php include __DIR__ . '/../components/navbar-seller.php'; ?>

    <div class="orders-wrapper">
        <!-- header section -->
        <section class="orders-header">
            <div class="orders-header-content">
                <div class="header-left">
                    <h1 class="page-title">Kelola Pesanan</h1>
                    <p class="page-subtitle">Pantau Dan Proses Pesanan Dari Pembeli</p>
                </div>
                <div class="header-right">
                    <a href="/seller/orders/export" class="btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- container utama -->
        <div class="orders-container">
            <!-- statistik pesanan -->
            <section class="stats-section">
                <h2 class="stats-title">Statistik Pesanan</h2>
                <div class="stats-bar">
                    <div class="stat-item waiting">
                        <span class="stat-label">Menunggu Konfirmasi</span>
                        <span class="stat-value"><?= $stats['waiting_approval'] ?></span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item approved">
                        <span class="stat-label">Dikonfirmasi</span>
                        <span class="stat-value"><?= $stats['approved'] ?></span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item delivery">
                        <span class="stat-label">Dalam Pengiriman</span>
                        <span class="stat-value"><?= $stats['on_delivery'] ?></span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item received">
                        <span class="stat-label">Selesai</span>
                        <span class="stat-value"><?= $stats['received'] ?></span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item rejected">
                        <span class="stat-label">Ditolak</span>
                        <span class="stat-value"><?= $stats['rejected'] ?></span>
                    </div>
                </div>
            </section>

            <!-- filter section -->
            <section class="filter-section">
                <div class="filter-header">
                    <h2 class="filter-title">Filter Pesanan</h2>
                    <div class="order-count"><?= $totalOrders ?> Pesanan</div>
                </div>
                
                <div class="filter-tabs">
                    <a href="/seller/orders?status=all&page=1" class="filter-tab <?= $currentStatus === 'all' ? 'active' : '' ?>">
                        Semua
                    </a>
                    <a href="/seller/orders?status=waiting_approval&page=1" class="filter-tab <?= $currentStatus === 'waiting_approval' ? 'active' : '' ?>">
                        Menunggu Konfirmasi
                    </a>
                    <a href="/seller/orders?status=approved&page=1" class="filter-tab <?= $currentStatus === 'approved' ? 'active' : '' ?>">
                        Dikonfirmasi
                    </a>
                    <a href="/seller/orders?status=on_delivery&page=1" class="filter-tab <?= $currentStatus === 'on_delivery' ? 'active' : '' ?>">
                        Dalam Pengiriman
                    </a>
                    <a href="/seller/orders?status=received&page=1" class="filter-tab <?= $currentStatus === 'received' ? 'active' : '' ?>">
                        Selesai
                    </a>
                    <a href="/seller/orders?status=rejected&page=1" class="filter-tab <?= $currentStatus === 'rejected' ? 'active' : '' ?>">
                        Ditolak
                    </a>
                </div>
            </section>

            <!-- search section -->
            <section class="search-section">
                <div class="search-container">
                    <input type="text"
                           id="searchInput"
                           class="search-input"
                           placeholder="Cari Order ID atau Nama Buyer..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn-clear-search" id="btnClearSearch" style="display: <?= !empty($_GET['search']) ? 'block' : 'none' ?>">
                        ✕
                    </button>
                </div>
            </section>

            <!-- daftar pesanan -->
            <section class="orders-list-section">
                <?php if (empty($orders)): ?>
                    <!-- empty state -->
                    <div class="empty-state">
                        <div class="empty-illustration">
                            <img src="/asset/empty-orders-placeholder.jpg" alt="Tidak Ada Pesanan">
                        </div>
                        <p class="empty-desc">
                            <?php if ($currentStatus === 'all'): ?>
                                Belum ada pesanan yang masuk ke toko Anda. Pastikan produk Anda menarik dan aktif!
                            <?php else: ?>
                                Tidak ada pesanan dengan status "<?= ucwords(str_replace('_', ' ', $currentStatus)) ?>".
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <!-- tabel pesanan -->
                    <div class="orders-table-wrapper">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Pembeli</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="order-row" data-order-id="<?= $order['order_id'] ?>">
                                        <td class="order-id-cell">
                                            <span class="order-id-badge">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td class="order-date-cell">
                                            <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td class="buyer-cell">
                                            <div class="buyer-info">
                                                <div class="buyer-name"><?= htmlspecialchars($order['buyer_name']) ?></div>
                                                <div class="buyer-email"><?= htmlspecialchars($order['buyer_email']) ?></div>
                                            </div>
                                        </td>
                                        <td class="price-cell">
                                            <span class="price-value">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
                                        </td>
                                        <td class="status-cell">
                                            <span class="status-badge status-<?= $order['status'] ?>">
                                                <?php
                                                $statusLabels = [
                                                    'waiting_approval' => 'Menunggu Konfirmasi',
                                                    'approved' => 'Dikonfirmasi',
                                                    'on_delivery' => 'Dalam Pengiriman',
                                                    'received' => 'Selesai',
                                                    'rejected' => 'Ditolak'
                                                ];
                                                echo $statusLabels[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <button class="btn-view-detail" onclick="viewOrderDetail(<?= $order['order_id'] ?>)">
                                                Lihat Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Menampilkan <?= count($orders) ?> Dari <?= $totalOrders ?> Pesanan
                            </div>
                            <div class="pagination-controls">
                                <?php if ($currentPage > 1): ?>
                                    <a href="/seller/orders?status=<?= $currentStatus ?>&page=1" class="pagination-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="11 17 6 12 11 7"/>
                                            <polyline points="18 17 13 12 18 7"/>
                                        </svg>
                                    </a>
                                    <a href="/seller/orders?status=<?= $currentStatus ?>&page=<?= $currentPage - 1 ?>" class="pagination-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15 18 9 12 15 6"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php
                                // tampilkan 5 halaman: current-2, current-1, current, current+1, current+2
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="/seller/orders?status=<?= $currentStatus ?>&page=<?= $i ?>" 
                                       class="pagination-btn <?= $i === $currentPage ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="/seller/orders?status=<?= $currentStatus ?>&page=<?= $currentPage + 1 ?>" class="pagination-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"/>
                                        </svg>
                                    </a>
                                    <a href="/seller/orders?status=<?= $currentStatus ?>&page=<?= $totalPages ?>" class="pagination-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="13 17 18 12 13 7"/>
                                            <polyline points="6 17 11 12 6 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- modal detail pesanan -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 class="modal-title">Detail Pesanan</h2>
                <button class="modal-close" onclick="closeOrderDetailModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- konten akan dimuat via JavaScript -->
                <div class="loading-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    <p>Memuat Detail Pesanan...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/seller/seller-orders.js"></script>
</body>
</html>