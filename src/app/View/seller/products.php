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
                        <img src="/asset/icon-plus.svg" alt="Add" class="btn-icon">
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
                        <img src="/asset/icon-search.svg" alt="Search" class="search-icon">
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
                            <img src="/asset/icon-refresh.svg" alt="Reset" class="btn-icon-small">
                            Reset
                        </button>
                    </div>
                </div>
            </section>

            <!-- products list -->
            <section class="products-list-section">
                <div id="productsContainer">
                    <!-- loading state -->
                    <div id="loadingState" class="loading-state">
                        <div class="loader"></div>
                        <p class="loading-text">Memuat Produk...</p>
                    </div>

                    <!-- empty state (akan ditampilkan via JavaScript jika tidak ada produk) -->
                    <div id="emptyState" class="empty-state" style="display: none;">
                        <img src="/asset/empty-products.svg" alt="No Products" class="empty-illustration">
                        <h3 class="empty-title">Belum Ada Produk</h3>
                        <p class="empty-description">
                            Mulai jualan dengan menambahkan produk pertama Anda. 
                            Produk yang menarik akan membantu meningkatkan penjualan!
                        </p>
                        <a href="/seller/products/add" class="btn-primary">
                            <img src="/asset/icon-plus.svg" alt="Add" class="btn-icon">
                            <span>Tambah Produk Pertama</span>
                        </a>
                    </div>

                    <!-- products grid (akan diisi via JavaScript) -->
                    <div id="productsGrid" class="products-grid"></div>
                </div>

                <!-- pagination -->
                <div id="paginationContainer" class="pagination-wrapper" style="display: none;">
                    <div class="pagination-info">
                        <span id="paginationText">Menampilkan 1-10 Dari 50 Produk</span>
                    </div>
                    <div id="paginationButtons" class="pagination-buttons">
                        <!-- buttons akan diisi via JavaScript -->
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- modal confirm delete -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Konfirmasi Hapus</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <img src="/asset/icon-warning.svg" alt="Warning" class="modal-icon">
                <p class="modal-text">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="modal-actions">
                    <button onclick="closeDeleteModal()" class="btn-secondary">Batal</button>
                    <button onclick="confirmDelete()" class="btn-danger">Hapus Produk</button>
                </div>
            </div>
        </div>
    </div>

    <!-- notification toast -->
    <div id="toast" class="toast">
        <img src="/asset/icon-check.svg" alt="Success" class="toast-icon">
        <span id="toastMessage" class="toast-message"></span>
    </div>

    <script src="/js/seller-products.js"></script>
</body>
</html>