<?php

$store = $store ?? null;
$categories = $categories ?? [];
$isLoggedIn = $isLoggedIn ?? false;
$isBuyer = $isBuyer ?? false;
$_token = $_token ?? '';
$currentUser = $currentUser ?? null;
$logoPath = $store['store_logo_path'] 
    ? htmlspecialchars($store['store_logo_path']) 
    : '/asset/placeholder-store-logo.jpg'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store['store_name']); ?></title>
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/buyer/store-detail.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-guest.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/footer.css">
</head>
<body>

    <?php if ($isLoggedIn && $isBuyer): ?>
        <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/../components/navbar-guest.php'; ?>
    <?php endif; ?>

    <span id="store-data" data-store-id="<?php echo $store['store_id']; ?>" style="display:none;"></span>

    <header class="store-header">
        <div class="store-info-container">
            <img src="<?php echo $logoPath; ?>" 
                 alt="Logo <?php echo htmlspecialchars($store['store_name']); ?>" class="store-logo">
            <div class="store-details">
                <h1 class="store-name-title"><?php echo htmlspecialchars($store['store_name']); ?></h1>
                <p class="store-description-header"><?php echo htmlspecialchars($store['store_description']); ?></p>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        
        <section class="filter-section">
            <div class="filter-container" id="filterForm">
                <div class="filter-group">
                    <label for="mainSearchInput" class="filter-label">Cari Produk:</label>
                    <input type="text" id="mainSearchInput" class="search-input" placeholder="Nama produk...">
                </div>

                <div class="filter-group">
                    <label for="categoryFilter" class="filter-label">Kategori:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="minPrice" class="filter-label">Harga Min:</label>
                    <input type="number" id="minPrice" class="filter-input" placeholder="Rp 0" min="0">
                </div>
                <div class="filter-group">
                    <label for="maxPrice" class="filter-label">Harga Max:</label>
                    <input type="number" id="maxPrice" class="filter-input" placeholder="Rp 0" min="0">
                </div>

                <div class="filter-group">
                    <label for="sortBy" class="filter-label">Urutkan:</label>
                    <select id="sortBy" class="filter-select">
                        <option value="created_at">Terbaru</option>
                        <option value="product_name">Nama (A-Z)</option>
                        <option value="price">Harga Terendah</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="itemsPerPage" class="filter-label">Per Halaman:</label>
                    <select id="itemsPerPage" class="filter-select">
                        <option value="4">4 Produk</option>
                        <option value="8">8 Produk</option>
                        <option value="12" selected>12 Produk</option>
                        <option value="20">20 Produk</option>
                    </select>
                </div>

                <button class="btn-reset" id="resetFiltersButton">
                    Reset Filter
                </button>
            </div>
        </section>
        
        <section class="products-section">
            <div class="section-header">
                <h2 class="section-title">Semua Produk Toko</h2>
                <p class="section-subtitle" id="productsCount">Memuat produk...</p>
            </div>

            <div id="loadingState" class="loading-state">
                <div class="loading-spinner">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner-icon">
                        <path d="M21 12a9 9 0 11-6.219-8.56"/>
                    </svg>
                    <p>Memuat produk...</p>
                </div>
            </div>
            
            <div id="productsGrid" class="products-grid" style="display: none;"></div>
            
            <!-- template untuk kartu produk -->
            <template id="productCardTemplate">
                <div class="product-card">
                    <div class="product-image-wrapper">
                        <img class="product-image" alt="">
                        <span class="product-badge" style="display: none;"></span>
                    </div>
                    <div class="product-content">
                        <h3 class="product-name"></h3>
                        <p class="product-store"></p>
                        <p class="product-price"></p>
                        <p class="product-stock"></p>
                    </div>
                </div>
            </template>
            
            <div id="emptyState" class="empty-state" style="display: none;">
                <h3>Produk Tidak Ditemukan</h3>
                <p>Toko ini belum memiliki produk yang sesuai dengan filter Anda.</p>
            </div>
            <div id="paginationContainer" class="pagination-container" style="display: none;">
                <button id="btnPrevPage" class="pagination-btn" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Sebelumnya
                </button>
                <div id="paginationNumbers" class="pagination-numbers"></div>
                <button id="btnNextPage" class="pagination-btn" disabled>
                    Selanjutnya
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </button>
            </div>
        </section>
    </div>

    <script>
        window.APP_CONFIG = {
            isLoggedIn: <?php echo json_encode($isLoggedIn); ?>,
            isBuyer: <?php echo json_encode($isBuyer); ?>,
            csrfToken: '<?php echo htmlspecialchars($_token); ?>'
        };
    </script>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script src="/js/buyer/store-detail.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>