<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nimonspedia - Marketplace Untuk Semua Nimons</title>
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/utility.css">
</head>
<body>
    <!-- TO DO navbar:
         Include navbar responsif untuk semua role:
         - Guest: Logo, Login, Daftar
         - Buyer: Logo/Home, Cart, Balance, User Menu
         - Seller: Redirect ke dashboard
    -->
    <?php // include 'components/navbar.php'; ?>

    <div class="home-container">
        <!-- Hero Section (Optional) -->
        <div class="hero-section">
            <div class="hero-content">
                <h1>Selamat Datang Di Nimonspedia</h1>
                <p>Marketplace Terlengkap Untuk Semua Kebutuhan Nimons</p>
            </div>
        </div>

        <!-- Search dan Filter Section -->
        <div class="search-filter-section">
            <div class="search-container">
                <div class="search-box-wrapper">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Cari Produk..."
                        autocomplete="off"
                        aria-label="Pencarian produk"
                    >
                    <button class="btn-search" aria-label="Tombol pencarian">
                        🔍
                    </button>
                </div>
                
                <!-- Advanced Search Results (AJAX) -->
                <!-- TO DO (Bonus - Advanced Search):
                     Implementasi dropdown hasil pencarian menggunakan AJAX (XMLHttpRequest)
                     dengan debounce 500ms
                -->
                <div id="searchSuggestions" class="search-suggestions" style="display: none;">
                    <!-- Hasil pencarian akan muncul di sini -->
                </div>
            </div>

            <div class="filter-controls">
                <div class="filter-group">
                    <label for="categoryFilter">Kategori:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <?php if (isset($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['category_id']) ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="minPrice">Harga Min:</label>
                    <input 
                        type="number" 
                        id="minPrice" 
                        placeholder="Rp 0"
                        min="0"
                        step="1000"
                    >
                </div>

                <div class="filter-group">
                    <label for="maxPrice">Harga Max:</label>
                    <input 
                        type="number" 
                        id="maxPrice" 
                        placeholder="Rp 999.999.999"
                        min="0"
                        step="1000"
                    >
                </div>

                <button class="btn-filter" id="applyFilters">
                    Terapkan Filter
                </button>
                
                <button class="btn-reset" id="resetFilters">
                    Reset
                </button>
            </div>
        </div>

        <!-- Products Section -->
        <div class="products-section">
            <div class="section-header">
                <h2>Jelajahi Produk</h2>
                <div class="view-options">
                    <select id="itemsPerPage" class="items-select">
                        <option value="4">4 Per Halaman</option>
                        <option value="8" selected>8 Per Halaman</option>
                        <option value="12">12 Per Halaman</option>
                        <option value="20">20 Per Halaman</option>
                    </select>
                </div>
            </div>

            <!-- Loading Skeleton -->
            <div id="loadingProducts" class="loading-state">
                <div class="skeleton-grid">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="skeleton-card">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-text"></div>
                            <div class="skeleton-text short"></div>
                            <div class="skeleton-text"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div id="productsGrid" class="products-grid">
                <!-- TO DO:
                     Struktur Product Card:
                     <div class="product-card [out-of-stock jika stock=0]">
                         <a href="/product/{product_id}">
                             <div class="product-image">
                                 <img src="{main_image_path}" alt="{product_name}">
                                 {jika stock=0: <div class="out-of-stock-badge">Stok Habis</div>}
                             </div>
                             <div class="product-info">
                                 <h3 class="product-name">{product_name}</h3>
                                 <p class="product-price">Rp {price}</p>
                                 <p class="product-store">{store_name}</p>
                             </div>
                         </a>
                         {jika user login sebagai buyer dan stock > 0:
                             <button class="btn-add-to-cart" onclick="addToCart({product_id})">
                                 Tambah Ke Keranjang
                             </button>
                         }
                     </div>
                -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">🔍</div>
                <h3>Tidak Ada Produk Ditemukan</h3>
                <p>Coba ubah filter atau kata kunci pencarian Anda</p>
            </div>

            <!-- Pagination Controls -->
            <div id="paginationControls" class="pagination">
                <div class="pagination-info">
                    Menampilkan <span id="itemRangeStart">1</span> sampai 
                    <span id="itemRangeEnd">8</span> dari 
                    <span id="totalItems">118</span> Produk
                </div>

                <div class="pagination-buttons">
                    <button class="btn-page" id="btnFirst" disabled>
                        &laquo; Pertama
                    </button>
                    <button class="btn-page" id="btnPrev" disabled>
                        &lsaquo; Sebelumnya
                    </button>
                    
                    <div id="pageNumbers" class="page-numbers">
                        <!-- Nomor halaman akan di-generate oleh JavaScript -->
                    </div>

                    <button class="btn-page" id="btnNext">
                        Selanjutnya &rsaquo;
                    </button>
                    <button class="btn-page" id="btnLast">
                        Terakhir &raquo;
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TO DO: 
         Include notification/toast component
    -->

    <script src="/public/js/home.js"></script>

    <script>
        // Pass user authentication status ke JavaScript
        const IS_AUTHENTICATED = <?= json_encode(isset($_SESSION['user']) ? true : false) ?>;
        const USER_ROLE = <?= json_encode($_SESSION['user']['role'] ?? null) ?>;
    </script>
</body>
</html>