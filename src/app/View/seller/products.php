<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Nimonspedia</title>
    <link rel="stylesheet" href="/css/utility.css">
    <link rel="stylesheet" href="/css/product-management.css">
</head>
<body>
    <!-- TO DO navbar untuk seller -->
    <?php // include 'components/navbar-seller.php'; ?>

    <div class="products-container">
        <!-- Header Section -->
        <div class="page-header">
            <div class="header-content">
                <h1>Manajemen Produk</h1>
                <p>Kelola Produk Toko Anda</p>
            </div>
            <a href="/seller/products/add" class="btn-add-product">
                <span>+</span> Tambah Produk
            </a>
        </div>

        <!-- Filter dan Search Section -->
        <div class="filter-section">
            <div class="search-box-wrapper">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Cari Nama Produk..."
                    autocomplete="off"
                >
                <button class="btn-search" aria-label="Cari">🔍</button>
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
                    <label for="sortBy">Urutkan:</label>
                    <select id="sortBy" class="filter-select">
                        <option value="created_at:DESC">Terbaru</option>
                        <option value="created_at:ASC">Terlama</option>
                        <option value="product_name:ASC">Nama A-Z</option>
                        <option value="product_name:DESC">Nama Z-A</option>
                        <option value="price:ASC">Harga Terendah</option>
                        <option value="price:DESC">Harga Tertinggi</option>
                        <option value="stock:ASC">Stok Terendah</option>
                        <option value="stock:DESC">Stok Tertinggi</option>
                    </select>
                </div>

                <button class="btn-reset" id="resetFilters">Reset</button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <div id="loadingState" class="loading-state">
                <div class="spinner"></div>
                <p>Memuat Produk...</p>
            </div>

            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">📦</div>
                <h3>Belum Ada Produk</h3>
                <p>Mulai Tambahkan Produk Pertama Anda</p>
                <a href="/seller/products/add" class="btn-add-first">Tambah Produk Pertama</a>
            </div>

            <table id="productsTable" class="products-table" style="display: none;">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <!-- data produk akan diisi oleh js -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="pagination-container" style="display: none;">
            <div class="pagination-info">
                <span id="paginationInfo">Menampilkan 1-10 dari 50 produk</span>
            </div>
            <div class="pagination-controls" id="paginationControls">
                <!-- tombol pagination akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Hapus</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda Yakin Ingin Menghapus Produk Ini?</p>
                <p class="warning-text">Tindakan Ini Tidak Dapat Dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeDeleteModal()">Batal</button>
                <button class="btn-danger" id="confirmDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>

    <!-- Toast Notif -->
    <div id="toast" class="toast"></div>

    <script src="/js/product-management.js"></script>
</body>
</html>