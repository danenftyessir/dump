<?php

$categories = $categories ?? [];
$isLoggedIn = $isLoggedIn ?? false;
$isBuyer = $isBuyer ?? false;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Discovery</title>
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/buyer/home.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/navbar-guest.css">
    <link rel="stylesheet" href="/css/components/footer.css">
</head>
<body>
    <!-- navbar untuk buyer/guest -->
    <?php if ($isLoggedIn && $isBuyer): ?>
        <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/../components/navbar-guest.php'; ?>
    <?php endif; ?>

    <!-- hero section dengan background image -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Temukan Produk Yang Anda Inginkan</h1>
            <p class="hero-subtitle">Nimonspedia adalah platform e-commerce berbasis web yang memungkinkan pengguna untuk berbelanja produk dari berbagai seller dan memungkinkan seller untuk mengelola toko online mereka</p>
            
            <!-- search bar utama -->
            <div class="hero-search-wrapper">
                <div class="search-container">
                    <input 
                        type="text" 
                        id="mainSearchInput" 
                        class="search-input" 
                        placeholder="Cari Produk Berdasarkan Nama Atau Deskripsi..."
                    >
                    <button class="search-button" onclick="performSearch()">
                        <span>Cari</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- main content wrapper -->
    <div class="content-wrapper">
        <!-- filter section -->
        <section class="filter-section">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="categoryFilter" class="filter-label">Kategori:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['category_id']) ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="minPrice" class="filter-label">Harga Min:</label>
                    <input 
                        type="number" 
                        id="minPrice" 
                        class="filter-input" 
                        placeholder="Rp 0"
                        min="0"
                    >
                </div>

                <div class="filter-group">
                    <label for="maxPrice" class="filter-label">Harga Max:</label>
                    <input 
                        type="number" 
                        id="maxPrice" 
                        class="filter-input" 
                        placeholder="Rp 0"
                        min="0"
                    >
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

                <button class="btn-reset" onclick="resetFilters()">
                    Reset Filter
                </button>
            </div>
        </section>

        <!-- products section -->
        <section class="products-section">
            <div class="section-header">
                <h2 class="section-title">Produk Pilihan</h2>
                <p class="section-subtitle" id="productsCount">Memuat produk...</p>
            </div>

            <!-- loading state -->
            <div id="loadingState" class="loading-state">
                <div class="loader"></div>
                <p class="loading-text">Memuat Produk...</p>
            </div>

            <!-- products grid -->
            <div id="productsGrid" class="products-grid" style="display: none;">
                <!-- JavaScript -->
            </div>

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

            <!-- empty state -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <img src="/asset/empty-products.jpg" alt="Tidak Ada Produk" class="empty-illustration">
                <h3 class="empty-title">Tidak Ada Produk Ditemukan</h3>
                <p class="empty-description">Maaf, tidak ada produk yang sesuai dengan pencarian Anda. Silakan coba kata kunci atau filter lain.</p>
            </div>

            <!-- pagination -->
            <div id="paginationContainer" class="pagination-container" style="display: none;">
                <button id="btnPrevPage" class="pagination-btn">
                    <span>Sebelumnya</span>
                </button>
                
                <div id="paginationNumbers" class="pagination-numbers">
                    <!-- JavaScript -->
                </div>
                
                <button id="btnNextPage" class="pagination-btn">
                    <span>Selanjutnya</span>
                </button>
            </div>
        </section>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <!-- JavaScript -->
    <script src="/js/buyer/home.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>