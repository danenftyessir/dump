<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/buyer/cart-page.css">
</head>
<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>

    <div class="main-content">
        <div class="container">
            <!-- Left Column: Cart Items -->
            <div class="cart-section">
                <h1>Keranjang Belanja</h1>
                <a href="/" class="continue-shopping">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Lanjut Belanja
                </a>
            <?php if ($cartSummary['is_empty']): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="8" cy="21" r="1"/>
                            <circle cx="19" cy="21" r="1"/>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                        </svg>
                    </div>
                    <h2>Keranjang Belanja Kosong</h2>
                    <p>Belum ada produk yang ditambahkan ke keranjang Anda</p>
                    <a href="/" class="btn-shop"> Mulai Belanja</a>
                </div>
            <?php else: ?>
                <!-- Store Groups -->
                <?php foreach ($cartSummary['stores'] as $storeId => $store): ?>
                    <div class="store-group" data-store-id="<?= $storeId ?>">
                        <!-- Store Header -->
                        <div class="store-header">
                            <img 
                                src="<?= htmlspecialchars($store['store_logo_path'] ?? '/asset/placeholder-store-logo.jpg') ?>" 
                                alt="Store Logo" 
                                class="store-logo"
                                onerror="this.src='/asset/placeholder-store-logo.jpg'"
                            >
                            <div class="store-info">
                                <h3><?= htmlspecialchars($store['store_name']) ?></h3>
                                <div class="store-items-count"><?= count($store['items']) ?> Produk</div>
                            </div>
                        </div>

                        <!-- Cart Items -->
                        <div class="cart-items-section">
                            <?php foreach ($store['items'] as $item): ?>
                                <div class="cart-item" data-cart-id="<?= $item['cart_item_id'] ?>" data-product-id="<?= $item['product_id'] ?>">
                                    
                                    <!-- Product Image -->
                                    <img 
                                        src="<?= htmlspecialchars($item['main_image_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%22 height=%22100%22 fill=%22%23ddd%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E') ?>" 
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        class="item-image"
                                        onerror="this.style.display='none'"
                                    >

                                    <!-- Product Details -->
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                        <div class="item-stock <?= $item['stock'] < 10 ? 'low' : '' ?>">
                                            Stok: <?= $item['stock'] ?> tersedia
                                        </div>
                                        
                                        <!-- Quantity Selector -->
                                        <div class="quantity-selector">
                                            <div class="qty-controls">
                                            <button 
                                                class="qty-btn" 
                                                onclick="updateQuantity(<?= $item['cart_item_id'] ?>, -1)"
                                                <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>
                                            >−</button>
                                            <input 
                                                type="number" 
                                                class="qty-input" 
                                                value="<?= $item['quantity'] ?>"
                                                min="1"
                                                max="<?= $item['stock'] ?>"
                                                onchange="updateQuantityDirect(<?= $item['cart_item_id'] ?>, this.value)"
                                                data-original="<?= $item['quantity'] ?>"
                                            >
                                            <button 
                                                class="qty-btn" 
                                                onclick="updateQuantity(<?= $item['cart_item_id'] ?>, 1)"
                                                <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>
                                            >+</button>
                                            </div>
                                            
                                            <button class="btn-remove" onclick="confirmRemove(<?= $item['cart_item_id'] ?>, '<?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?>')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3,6 5,6 21,6"/>
                                                    <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"/>
                                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Item Actions -->
                                    <div class="item-actions">
                                        <div class="item-subtotal">
                                            Rp <?= number_format($item['item_subtotal'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Store Subtotal -->
                        <div class="store-subtotal">
                            <span class="store-subtotal-label">Subtotal <?= htmlspecialchars($store['store_name']) ?>:</span>
                            <span class="store-subtotal-value">Rp <?= number_format($store['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>

            <!-- Right Column: Summary Panel -->
            <?php if (!$cartSummary['is_empty']): ?>
                <div class="summary-panel">
                <h2 class="summary-title">Ringkasan Belanja</h2>
                
                <div class="summary-row">
                    <span>Total Toko:</span>
                    <span><?= $cartSummary['store_count'] ?> toko</span>
                </div>
                
                <div class="summary-row">
                    <span>Total Item:</span>
                    <span id="totalItemsDisplay"><?= $cartSummary['total_items_quantity'] ?> item</span>
                </div>
                
                <?php foreach ($cartSummary['stores'] as $storeId => $store): ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($store['store_name']) ?>:</span>
                        <span>Rp <?= number_format($store['subtotal'], 0, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-row total">
                    <span class="label">TOTAL:</span>
                    <span class="value" id="grandTotal">Rp <?= number_format($cartSummary['grand_total'], 0, ',', '.') ?></span>
                </div>
                
                <button class="checkout-btn" onclick="checkout()" <?= $cartSummary['is_empty'] ? 'disabled' : '' ?>>
                    Checkout (<?= $cartSummary['store_count'] ?> Toko)
                </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </span>
                <h3>Hapus dari Keranjang?</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus <strong id="productNameToRemove"></strong> dari keranjang?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn btn-confirm" onclick="removeItem()">Hapus</button>
            </div>
        </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        const CSRF_TOKEN = '<?= $_token ?? '' ?>';
    </script>
    <script src="/js/components/navbar-buyer.js"></script>
    <script src="/js/buyer/cart-page.js"></script>
</body>
</html>