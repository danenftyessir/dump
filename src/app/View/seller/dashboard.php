<?php
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="stat-icon">
                                <path d="M16.5 9.4l-9-5.19"/>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="stat-icon">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Pesanan Menunggu</p>
                            <h3 class="stat-value"><?= number_format($stats['pending_orders']) ?></h3>
                            <a href="/seller/orders?status=waiting_approval" class="stat-link">Lihat Pesanan →</a>
                        </div>
                    </div>

                    <!-- low stock products -->
                    <div class="stat-card card-danger">
                        <div class="stat-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="stat-icon">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-label">Stok Menipis</p>
                            <h3 class="stat-value"><?= number_format($stats['low_stock_products']) ?></h3>
                            <p class="stat-sublabel">Produk < 10 Stok</p>
                        </div>
                    </div>

                    <!-- total revenue -->
                    <div class="stat-card card-success">
                        <div class="stat-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="stat-icon">
                                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                            </svg>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                        </div>
                        <h3 class="action-title">Tambah Produk</h3>
                        <p class="action-desc">Upload produk baru ke toko Anda</p>
                    </a>

                    <a href="/seller/products" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16.5 9.4l-9-5.19"/>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <h3 class="action-title">Kelola Produk</h3>
                        <p class="action-desc">Edit, hapus, atau perbarui stok produk</p>
                    </a>

                    <a href="/seller/orders" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </div>
                        <h3 class="action-title">Kelola Pesanan</h3>
                        <p class="action-desc">Proses pesanan dari pembeli</p>
                    </a>

                    <button onclick="openEditStoreModal()" class="action-card">
                        <div class="action-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 280 280" fill="none" class="empty-icon-small">
                                    <circle cx="140" cy="140" r="120" fill="#F3F4F6" opacity="0.5"/>
                                    <rect x="60" y="90" width="160" height="120" rx="12" fill="white" stroke="#D1D5DB" stroke-width="2"/>
                                    <path d="M90 120h100M90 140h120M90 160h80M90 180h100" stroke="#E5E7EB" stroke-width="3" stroke-linecap="round"/>
                                    <circle cx="140" cy="230" r="25" fill="#F59E0B" opacity="0.1"/>
                                    <path d="M140 220v10M140 235h.01" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round"/>
                                </svg>
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
                <button class="modal-close" onclick="closeEditStoreModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
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