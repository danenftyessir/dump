<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_token ?? '' ?>">
    <title>Kelola Produk - Seller Dashboard</title>
    <link rel="stylesheet" href="/css/productPage.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📦 Kelola Produk</h1>
            <a href="/seller/products/add" class="btn-add">
                ➕ Tambah Produk Baru
            </a>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Cari produk berdasarkan nama atau deskripsi..."
                >
                <span class="search-icon">🔍</span>
            </div>

            <div class="filter-box">
                <select id="categoryFilter">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>">
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="filter-icon">📂</span>
            </div>

            <div class="sort-box">
                <select id="sortSelect">
                    <option value="name_asc">Nama (A-Z)</option>
                    <option value="name_desc">Nama (Z-A)</option>
                    <option value="price_asc">Harga (Termurah)</option>
                    <option value="price_desc">Harga (Termahal)</option>
                    <option value="stock_asc">Stok (Terendah)</option>
                    <option value="stock_desc">Stok (Tertinggi)</option>
                </select>
                <span class="sort-icon">🔄</span>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📦</div>
                                    <h3>Belum Ada Produk</h3>
                                    <p>Mulai tambahkan produk pertama Anda untuk mulai berjualan</p>
                                    <a href="/seller/products/add" class="btn-add">
                                        ➕ Tambah Produk Pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                                // Parse multiple categories
                                $categoryNames = !empty($product['category_names']) 
                                    ? explode(', ', $product['category_names']) 
                                    : ['Uncategorized'];
                                $categoryIds = !empty($product['category_ids']) 
                                    ? explode(',', $product['category_ids']) 
                                    : [''];
                            ?>
                            <tr data-product-id="<?= $product['product_id'] ?>" 
                                data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>"
                                data-description="<?= strtolower(htmlspecialchars($product['description'] ?? '')) ?>"
                                data-category="<?= htmlspecialchars($product['category_ids'] ?? '') ?>"
                                data-price="<?= $product['price'] ?>"
                                data-stock="<?= $product['stock'] ?>">
                                
                                <td>
                                    <img 
                                        src="<?= htmlspecialchars($product['main_image_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%22 height=%22100%22 fill=%22%23ddd%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E') ?>" 
                                        alt="<?= htmlspecialchars($product['product_name']) ?>"
                                        class="product-thumbnail"
                                        onerror="this.style.display='none'; this.parentNode.innerHTML='📦'"
                                    >
                                </td>
                                
                                <td>
                                    <div class="product-name">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="categories-wrapper">
                                        <?php foreach ($categoryNames as $index => $categoryName): ?>
                                            <span class="product-category">
                                                <?= htmlspecialchars($categoryName) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                
                                <td class="price">
                                    Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                </td>
                                
                                <td>
                                    <span class="stock <?= $product['stock'] <= 10 ? 'low' : ($product['stock'] <= 50 ? 'medium' : 'high') ?>">
                                        <?= $product['stock'] ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="status-badge <?= $product['deleted_at'] ? 'status-inactive' : 'status-active' ?>">
                                        <?= $product['deleted_at'] ? 'Tidak Aktif' : 'Aktif' ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="action-buttons">
                                        <a href="/seller/products/edit/<?= $product['product_id'] ?>" class="btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <button 
                                            class="btn-delete" 
                                            onclick="confirmDelete(<?= $product['product_id'] ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>')">
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span style="font-size: 32px;">⚠️</span>
                <h3>Konfirmasi Hapus</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus produk <strong id="productNameToDelete"></strong>?</p>
                <p style="color: #dc3545; font-size: 14px;">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn-confirm" onclick="deleteProduct()">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="/js/productPage.js"></script>
    </style>
</head>
<body>