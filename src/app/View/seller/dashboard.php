<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nimonspedia</title>
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/utility.css">
</head>
<body>
    <!-- TO DO navbar: 
         Include navbar untuk seller dengan menu:
         - Logo/Home (link ke Dashboard)
         - Navigation menu: Dashboard, Produk, Orders
         - Logout button
    -->
    <?php // include 'components/navbar-seller.php'; ?>

    <div class="dashboard-container">
        <!-- Header Section dengan Info Toko -->
        <div class="store-header">
            <div class="store-info">
                <div class="store-logo">
                    <?php if (isset($store['store_logo_path']) && !empty($store['store_logo_path'])): ?>
                        <img src="<?= htmlspecialchars($store['store_logo_path']) ?>" alt="Logo Toko">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <span>📦</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="store-details">
                    <h1><?= htmlspecialchars($store['store_name'] ?? 'Nama Toko', ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="store-description">
                        <?php 
                        // Safe rendering rich text dari database
                        // HTML sudah di-sanitize di backend, tapi tetap escape output
                        if (isset($store['store_description']) && !empty($store['store_description'])) {
                            // Gunakan output dari Quill yang sudah di-sanitize
                            echo $store['store_description'];
                        } else {
                            echo '<p>Deskripsi toko belum diatur</p>';
                        }
                        ?>
                    </div>
                    <button class="btn-edit-store" onclick="openEditStoreModal()">
                        ✏️ Edit Informasi Toko
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <h3>Total Produk</h3>
                    <p class="stat-number" id="totalProducts">
                        <?= $stats['total_products'] ?? 0 ?>
                    </p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3>Pending Orders</h3>
                    <p class="stat-number" id="pendingOrders">
                        <?= $stats['pending_orders'] ?? 0 ?>
                    </p>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3>Produk Stok Menipis</h3>
                    <p class="stat-number" id="lowStockProducts">
                        <?= $stats['low_stock_products'] ?? 0 ?>
                    </p>
                    <span class="stat-subtitle">Stok &lt; 10</span>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3>Total Pendapatan</h3>
                    <p class="stat-number" id="totalRevenue">
                        Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Aksi Cepat</h2>
            <div class="actions-grid">
                <a href="/seller/products" class="action-card">
                    <div class="action-icon">🏪</div>
                    <h3>Kelola Produk</h3>
                    <p>Lihat Dan Edit Produk Anda</p>
                </a>

                <a href="/seller/orders" class="action-card">
                    <div class="action-icon">📋</div>
                    <h3>Lihat Orders</h3>
                    <p>Kelola Pesanan Masuk</p>
                </a>

                <a href="/seller/products/add" class="action-card highlight">
                    <div class="action-icon">➕</div>
                    <h3>Tambah Produk Baru</h3>
                    <p>Buat Listing Produk</p>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <!-- TO DO: Implementasi recent activity -->
    </div>

    <!-- Modal Edit Store -->
    <div id="editStoreModal" class="modal modal-closed hidden">
        <div class="modal-content">
            <span class="close" onclick="closeEditStoreModal()">&times;</span>
            <h2>Edit Informasi Toko</h2>
            
            <form id="editStoreForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="storeName">Nama Toko *</label>
                    <input 
                        type="text" 
                        id="storeName" 
                        name="store_name" 
                        value="<?= htmlspecialchars($store['store_name'] ?? '') ?>"
                        maxlength="100"
                        required
                    >
                    <span class="char-count">
                        <span id="nameCharCount">0</span>/100
                    </span>
                </div>

                <div class="form-group">
                    <label for="storeDescription">Deskripsi Toko *</label>
                    <!-- TO DO: Implementasi Rich Text Editor menggunakan Quill.js -->
                    <div id="storeDescriptionEditor"></div>
                    <input type="hidden" id="storeDescription" name="store_description">
                </div>

                <div class="form-group">
                    <label for="storeLogo">Logo Toko</label>
                    <input 
                        type="file" 
                        id="storeLogo" 
                        name="store_logo" 
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                    >
                    <p class="help-text">Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB</p>
                    <div id="logoPreview" class="image-preview"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditStoreModal()">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary" id="btnSaveStore">
                        <span class="btn-text show-inline">Simpan Perubahan</span>
                        <span class="btn-loader hidden">⏳ Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TO DO
         sukses/error toast notification
    -->

    <script src="/public/js/dashboard.js"></script>
    
    <!-- TO DO: include Quill.js untuk rich text editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</body>
</html>