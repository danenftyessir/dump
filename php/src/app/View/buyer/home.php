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
    <link rel="stylesheet" href="/css/buyer/home.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/navbar-guest.css">
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
            <h1 class="hero-title">Temukan Produk Kerajinan Unik</h1>
            <p class="hero-subtitle">Marketplace Produk Kerajinan Tangan Berkualitas Dari Seluruh Indonesia</p>
            
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
                <!-- products akan diisi via JavaScript -->
            </div>

            <!-- empty state -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <img src="/asset/empty-products.jpg" alt="Tidak Ada Produk" class="empty-illustration">
                <h3 class="empty-title">Tidak Ada Produk Ditemukan</h3>
                <p class="empty-description">Maaf, tidak ada produk yang sesuai dengan pencarian Anda. Silakan coba kata kunci atau filter lain.</p>
            </div>

            <!-- pagination -->
            <div id="paginationContainer" class="pagination-container" style="display: none;">
                <button id="btnPrevPage" class="pagination-btn" onclick="changePage('prev')">
                    <span>Sebelumnya</span>
                </button>
                
                <div id="paginationNumbers" class="pagination-numbers">
                    <!-- nomor halaman akan diisi via JavaScript -->
                </div>
                
                <button id="btnNextPage" class="pagination-btn" onclick="changePage('next')">
                    <span>Selanjutnya</span>
                </button>
            </div>
        </section>
    </div>

    <!-- footer -->
    <footer class="footer-section">
        <div class="footer-content">
            <div class="footer-brand">
                <h3>Nimonspedia</h3>
                <p>Marketplace Produk Kerajinan Berkualitas</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Tentang</h4>
                    <a href="#">Tentang Kami</a>
                    <a href="#">Kontak</a>
                </div>
                <div class="footer-column">
                    <h4>Bantuan</h4>
                    <a href="#">Cara Berbelanja</a>
                    <a href="#">FAQ</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Nimonspedia. Semua Hak Dilindungi.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/js/buyer/home.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>