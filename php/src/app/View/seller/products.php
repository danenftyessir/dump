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
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-seller.css">
    <link rel="stylesheet" href="/css/seller/seller-products.css">
    <link rel="stylesheet" href="/css/seller/seller-common.css">
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
                    <a href="/seller/products/export" class="btn-secondary" style="margin-right: 12px;" aria-label="Export data produk ke file CSV">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>Export CSV</span>
                    </a>
                    <a href="/seller/products/add" class="btn-primary" aria-label="Tambah produk baru ke toko Anda">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon" aria-hidden="true">
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
            <section class="filter-section" aria-label="Filter dan Pencarian Produk">
                <div class="filter-card">
                    <div class="search-wrapper">
                        <label for="searchInput" class="visually-hidden">Cari Produk</label>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input
                            type="text"
                            id="searchInput"
                            class="search-input"
                            placeholder="Cari produk..."
                            aria-label="Cari produk berdasarkan nama"
                        >
                    </div>

                    <div class="filter-controls">
                        <label for="categoryFilter" class="visually-hidden">Filter Kategori</label>
                        <select id="categoryFilter" class="filter-select" aria-label="Filter berdasarkan kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['category_id']) ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="sortFilter" class="visually-hidden">Urutkan Produk</label>
                        <select id="sortFilter" class="filter-select" aria-label="Urutkan produk">
                            <option value="created_at">Terbaru</option>
                            <option value="name_asc">Nama: A-Z</option>
                            <option value="name_desc">Nama: Z-A</option>
                            <option value="price_asc">Harga: Terendah</option>
                            <option value="price_desc">Harga: Tertinggi</option>
                            <option value="stock_asc">Stok: Terendah</option>
                            <option value="stock_desc">Stok: Tertinggi</option>
                        </select>

                        <label for="limitFilter" class="visually-hidden">Jumlah Per Halaman</label>
                        <select id="limitFilter" class="filter-select" aria-label="Jumlah produk per halaman">
                            <option value="4">4 per halaman</option>
                            <option value="8">8 per halaman</option>
                            <option value="12" selected>12 per halaman</option>
                            <option value="20">20 per halaman</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- loading state -->
            <section id="loadingState" class="loading-state hidden" aria-live="polite" aria-busy="true">
                <div class="loading-spinner" role="status" aria-label="Memuat"></div>
                <p>Memuat Produk...</p>
            </section>

            <!-- empty state -->
            <section id="emptyState" class="empty-state hidden" aria-live="polite">
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200" fill="none" class="empty-illustration" aria-hidden="true">
                    <circle cx="100" cy="100" r="80" fill="#F3F4F6" opacity="0.5"/>
                    <rect x="60" y="70" width="80" height="60" rx="8" fill="white" stroke="#D1D5DB" stroke-width="2"/>
                    <path d="M75 85h50M75 95h40M75 105h45" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="140" cy="130" r="20" fill="#10B981" opacity="0.1"/>
                    <path d="M140 125v10M135 130h10" stroke="#10B981" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <h3>Belum Ada Produk</h3>
                <p>Yuk, Mulai Jualan Dengan Menambahkan Produk Pertamamu!</p>
                <a href="/seller/products/add" class="btn-primary" aria-label="Tambah produk pertama ke toko Anda">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <span>Tambah Produk Pertama</span>
                </a>
            </section>

            <!-- products table -->
            <section id="productsTable" class="products-table hidden">
                <table role="table" aria-label="Daftar produk toko">
                    <caption class="visually-hidden">Daftar produk yang dijual di toko Anda</caption>
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Harga</th>
                            <th scope="col">Stok</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <!-- rows akan di-populate via javascript -->
                    </tbody>
                </table>
            </section>

            <!-- pagination -->
            <div id="paginationContainer" class="pagination-container hidden">
                <!-- pagination akan di-populate via javascript -->
            </div>
        </div>
    </div>

    <!-- modal konfirmasi delete -->
    <div id="deleteModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalMessage">
        <div class="modal-overlay" onclick="closeDeleteModal()" aria-hidden="true"></div>
        <div class="modal-content" role="document">
            <div class="modal-header">
                <h3 class="modal-title" id="deleteModalTitle">Hapus Produk?</h3>
                <button class="modal-close" onclick="closeDeleteModal()" aria-label="Tutup modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="modal-icon" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <p class="modal-text" id="deleteModalMessage">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-actions">
                <button onclick="closeDeleteModal()" class="btn-secondary" aria-label="Batal hapus produk">Batal</button>
                <button onclick="confirmDelete()" class="btn-danger" id="confirmDeleteBtn" aria-label="Konfirmasi hapus produk">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
    <div id="toast" class="toast hidden" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon" aria-hidden="true">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span id="toastMessage">Berhasil!</span>
        </div>
    </div>

    <script src="/js/seller/seller-products.js?v=<?= time() ?>"></script>
</body>
</html>