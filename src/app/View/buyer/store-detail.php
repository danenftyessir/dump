<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($store['store_name'] ?? 'Detail Toko') ?> - Nimonspedia</title>
    <link rel="stylesheet" href="/public/css/store-detail.css">
</head>
<body>
    <!-- TO DO: 
         include navbar sesuai role user:
         - Guest: Logo, Login, Daftar
         - Buyer: Logo, Cart, Balance, User Menu
         - Seller: Logo, Dashboard Menu, Store Balance, Logout
    -->
    <?php // include 'components/navbar.php'; ?>

    <div class="store-detail-container">
        <!-- Store Header -->
        <div class="store-header">
            <div class="store-banner">
                <!-- background banner image -->
            </div>
            
            <div class="store-info-section">
                <div class="store-logo-large">
                    <?php if (isset($store['store_logo_path']) && !empty($store['store_logo_path'])): ?>
                        <img src="<?= htmlspecialchars($store['store_logo_path']) ?>" alt="Logo <?= htmlspecialchars($store['store_name']) ?>">
                    <?php else: ?>
                        <div class="logo-placeholder-large">
                            <span>🏪</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="store-info-content">
                    <h1 class="store-name"><?= htmlspecialchars($store['store_name']) ?></h1>                  
                    <div class="store-meta">
                        <span class="meta-item">
                            <span class="icon">📦</span>
                            <span id="totalProductsDisplay">0 Produk</span>
                        </span>
                        <span class="meta-item">
                            <span class="icon">📅</span>
                            Bergabung <?= date('F Y', strtotime($store['created_at'])) ?>
                        </span>
                    </div>
                    <div class="store-description">
                        <h3>Tentang Toko</h3>
                        <div class="description-content">
                            <?= $store['store_description'] ?? '<p>Belum ada deskripsi toko.</p>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="store-products-section">
            <div class="section-header">
                <h2>Produk Toko Ini</h2>
                
                <!-- Filter dan Search -->
                <div class="products-controls">
                    <div class="search-box">
                        <input 
                            type="text" 
                            id="searchInput" 
                            placeholder="Cari Produk Di Toko Ini..."
                            autocomplete="off"
                        >
                        <button class="btn-search">🔍</button>
                    </div>

                    <div class="filter-group">
                        <select id="sortBy" class="filter-select">
                            <option value="">Urutkan</option>
                            <option value="newest">Terbaru</option>
                            <option value="price_asc">Harga Terendah</option>
                            <option value="price_desc">Harga Tertinggi</option>
                            <option value="name_asc">Nama A-Z</option>
                            <option value="name_desc">Nama Z-A</option>
                        </select>
                        <select id="categoryFilter" class="filter-select">
                            <option value="">Semua Kategori</option>
                            <!-- TO DO: populate dari database categories -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingProducts" class="loading-state" style="display: none;">
                <div class="skeleton-grid">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="skeleton-card">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-text"></div>
                            <div class="skeleton-text short"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- products Grid -->
            <div id="productsGrid" class="products-grid">
                <!-- TO DO product management
                     produk dari endpoint: GET /api/products?store_id={storeId}
                -->
            </div>

            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">📦</div>
                <h3>Belum Ada Produk</h3>
                <p>Toko ini belum memiliki produk untuk dijual.</p>
            </div>

            <!-- Pagination -->
            <div id="paginationControls" class="pagination">
                <!-- TO DO: implementasi pagination controls
                     - tombol Previous
                     - nomor halaman (1, 2, 3, ...)
                     - tombol Next
                     - dropdown jumlah item per halaman (4, 8, 12, 20)
                -->
            </div>
        </div>
    </div>

    <!-- TO DO 
         include notification/toast untuk feedback ke user
    -->

    <script src="/public/js/store-detail.js"></script>
    
    <script>
        // Pass store_id ke JavaScript
        const STORE_ID = <?= json_encode($store['store_id']) ?>;
        const STORE_NAME = <?= json_encode($store['store_name']) ?>;
    </script>
</body>
</html>