<?php
// pastikan ada data store dan categories dari controller
$store = $store ?? null;
$categories = $categories ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Nimonspedia</title>
    <link rel="stylesheet" href="/css/seller-products.css">
    <link rel="stylesheet" href="/css/seller-common.css">
</head>
<body>
    <!-- navbar seller -->
    <?php include __DIR__ . '/../components/navbar-seller.php'; ?>

    <div class="products-wrapper">
        <!-- header section -->
        <section class="products-header">
            <div class="products-header-content">
                <div class="header-left">
                    <h1 class="page-title">Kelola Produk</h1>
                    <p class="page-subtitle">Atur Dan Perbarui Produk Toko Anda</p>
                </div>
                <div class="header-right">
                    <a href="/seller/products/add" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        <span>Tambah Produk Baru</span>
                    </a>
                </div>
            </div>
        </section>

        <div class="products-container">
            <!-- filter dan search bar -->
            <section class="filter-section">
                <div class="filter-card">
                    <div class="search-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input" 
                            placeholder="Cari Produk Berdasarkan Nama..."
                        >
                    </div>
                    
                    <div class="filter-controls">
                        <select id="categoryFilter" class="filter-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="sortBy" class="filter-select">
                            <option value="created_at">Terbaru</option>
                            <option value="product_name">Nama (A-Z)</option>
                            <option value="price">Harga Terendah</option>
                            <option value="stock">Stok Terendah</option>
                        </select>

                        <button id="resetFilter" class="btn-reset">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 2v6h-6"/>
                                <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                                <path d="M3 22v-6h6"/>
                                <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                            </svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- products list container -->
            <section class="products-list-section">
                <!-- loading state -->
                <div id="loadingState" class="loading-state" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Memuat produk...</p>
                </div>

                <!-- empty state -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="280" height="280" viewBox="0 0 280 280" fill="none" class="empty-illustration">
                        <circle cx="140" cy="140" r="120" fill="#F3F4F6" opacity="0.5"/>
                        <rect x="80" y="100" width="120" height="100" rx="8" fill="white" stroke="#D1D5DB" stroke-width="2"/>
                        <path d="M100 120h80M100 140h60M100 160h70" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="200" cy="180" r="30" fill="#10B981" opacity="0.1"/>
                        <path d="M200 170v20M190 180h20" stroke="#10B981" stroke-width="3" stroke-linecap="round"/>
                        <path d="M140 85l-15 15h30z" fill="#E5E7EB"/>
                    </svg>
                    <h3>Belum Ada Produk</h3>
                    <p>Yuk, mulai jualan dengan menambahkan produk pertamamu!</p>
                    <a href="/seller/products/add" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                        <span>Tambah Produk Pertama</span>
                    </a>
                </div>

                <!-- products table -->
                <div id="productsTable" class="products-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- rows akan di-populate via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- pagination -->
                <div id="paginationContainer" class="pagination-container" style="display: none;">
                    <!-- pagination akan di-populate via JavaScript -->
                </div>
            </section>
        </div>
    </div>

    <!-- modal konfirmasi delete -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeDeleteModal()"></div>
        <div class="modal-content modal-small">
            <div class="modal-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="modal-icon-warning">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <h3>Hapus Produk?</h3>
                <p id="deleteModalMessage">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn-secondary">Batal</button>
                <button onclick="confirmDelete()" class="btn-danger" id="confirmDeleteBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                    <span>Hapus</span>
                </button>
            </div>
        </div>
    </div>

    <!-- toast notification -->
    <div id="toast" class="toast" style="display: none;">
        <div class="toast-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span id="toastMessage">Berhasil!</span>
        </div>
    </div>

    <script src="/js/seller-products.js"></script>
</body>
</html>