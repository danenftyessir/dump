<?php
// pastikan ada data store dan stats dari controller
$store = $store ?? null;
$stats = $stats ?? [
    'total_products' => 0,
    'low_stock_products' => 0,
    'pending_orders' => 0,
    'total_revenue' => 0
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Seller - Nimonspedia</title>
    <link rel="stylesheet" href="/css/seller-dashboard.css">
    <link rel="stylesheet" href="/css/seller-common.css">
</head>
<body>
    <!-- navbar seller -->
    <?php include __DIR__ . '/../components/navbar-seller.php'; ?>

    <div class="dashboard-wrapper">
        <!-- hero section dengan background image -->
        <section class="dashboard-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="store-info-hero">
                    <div class="store-logo-large">
                        <?php if (isset($store['store_logo_path']) && !empty($store['store_logo_path'])): ?>
                            <img src="<?= htmlspecialchars($store['store_logo_path']) ?>" alt="Logo Toko" class="logo-image">
                        <?php else: ?>
                            <img src="/asset/placeholder-store-logo.jpg" alt="Default Logo" class="logo-image placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="store-details-hero">
                        <h1 class="store-name"><?= htmlspecialchars($store['store_name'] ?? 'Nama Toko') ?></h1>
                        <p class="store-tagline">Dashboard Penjualan & Manajemen Toko</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="dashboard-container">
            <!-- quick stats cards -->
            <section class="stats-section">
                <h2 class="section-title">Ringkasan Performa</h2>
                <div class="stats-grid">
                    <!-- total products -->
                    <div class="stat-card card-primary">
                        <div class="stat-icon-wrapper">
                            <img src="/asset/icon-box.svg" alt="Products" class="stat-icon">
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Produk</p>
                            <h3 class="stat-value"><?= number_format($stats['total_products']) ?></h3>
                            <a href="/seller/products" class="stat-link">Kelola Produk →</a>
                        </div>
                    </div>

                    <!-- pending orders -->
                    <div class="stat-card card-warning">
                        <div class="stat-icon-wrapper">
                            <img src="/asset/icon-clock.svg" alt="Pending" class="stat-icon">
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Pesanan Menunggu</p>
                            <h3 class="stat-value"><?= number_format($stats['pending_orders']) ?></h3>
                            <?php if ($stats['pending_orders'] > 0): ?>
                                <span class="stat-badge badge-urgent">Perlu Ditindaklanjuti</span>
                            <?php else: ?>
                                <span class="stat-badge badge-ok">Semua Terkelola</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- low stock warning -->
                    <div class="stat-card card-danger">
                        <div class="stat-icon-wrapper">
                            <img src="/asset/icon-alert.svg" alt="Alert" class="stat-icon">
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Stok Menipis</p>
                            <h3 class="stat-value"><?= number_format($stats['low_stock_products']) ?></h3>
                            <?php if ($stats['low_stock_products'] > 0): ?>
                                <a href="/seller/products?filter=low_stock" class="stat-link">Lihat Detail →</a>
                            <?php else: ?>
                                <span class="stat-badge badge-ok">Stok Aman</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- total revenue -->
                    <div class="stat-card card-success">
                        <div class="stat-icon-wrapper">
                            <img src="/asset/icon-chart.svg" alt="Revenue" class="stat-icon">
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Total Pendapatan</p>
                            <h3 class="stat-value">Rp <?= number_format($stats['total_revenue']) ?></h3>
                            <p class="stat-sublabel">Dari Pesanan Selesai</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- quick actions -->
            <section class="actions-section">
                <h2 class="section-title">Aksi Cepat</h2>
                <div class="actions-grid">
                    <a href="/seller/products/add" class="action-card">
                        <div class="action-icon">
                            <img src="/asset/icon-add-product.svg" alt="Add Product">
                        </div>
                        <h3 class="action-title">Tambah Produk</h3>
                        <p class="action-desc">Upload produk baru ke toko Anda</p>
                    </a>

                    <a href="/seller/products" class="action-card">
                        <div class="action-icon">
                            <img src="/asset/icon-manage.svg" alt="Manage">
                        </div>
                        <h3 class="action-title">Kelola Produk</h3>
                        <p class="action-desc">Edit, hapus, atau perbarui stok produk</p>
                    </a>

                    <a href="/seller/orders" class="action-card">
                        <div class="action-icon">
                            <img src="/asset/icon-orders.svg" alt="Orders">
                        </div>
                        <h3 class="action-title">Kelola Pesanan</h3>
                        <p class="action-desc">Proses pesanan dari pembeli</p>
                    </a>

                    <button onclick="openEditStoreModal()" class="action-card">
                        <div class="action-icon">
                            <img src="/asset/icon-settings.svg" alt="Settings">
                        </div>
                        <h3 class="action-title">Pengaturan Toko</h3>
                        <p class="action-desc">Edit informasi dan logo toko</p>
                    </button>
                </div>
            </section>

            <!-- store description -->
            <section class="store-description-section">
                <div class="description-card">
                    <h2 class="section-title">Deskripsi Toko</h2>
                    <div class="description-content">
                        <?php if (isset($store['store_description']) && !empty($store['store_description'])): ?>
                            <?= $store['store_description'] ?>
                        <?php else: ?>
                            <div class="empty-state-inline">
                                <img src="/asset/empty-description.svg" alt="Empty" class="empty-icon-small">
                                <p class="empty-text">Belum ada deskripsi toko. Tambahkan deskripsi untuk menarik lebih banyak pembeli.</p>
                                <button onclick="openEditStoreModal()" class="btn-secondary-small">Tambah Deskripsi</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- modal edit store (placeholder) -->
    <div id="editStoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Informasi Toko</h2>
                <button class="modal-close" onclick="closeEditStoreModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editStoreForm" enctype="multipart/form-data">
                    <!-- form fields akan ditambahkan via JavaScript -->
                    <p class="placeholder-text">Form edit toko akan dimuat...</p>
                </form>
            </div>
        </div>
    </div>

    <script src="/js/seller-dashboard.js"></script>
</body>
</html>