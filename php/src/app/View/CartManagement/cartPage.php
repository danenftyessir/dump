<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Nimonspedia</title>
    <link rel="stylesheet" href="/css/cartPage.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1>
                🛒 Keranjang Belanja
                <span class="cart-badge" id="cartBadge"><?= $totalItems ?> Item</span>
            </h1>
            <a href="/" class="continue-shopping">← Lanjut Belanja</a>
        </div>
    </div>

    <!-- Container -->
    <div class="container">
        <!-- Cart Items Section -->
        <div class="cart-items-section">
            <?php if (empty($cartItems)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">🛒</div>
                    <h2>Keranjang Belanja Kosong</h2>
                    <p>Belum ada produk yang ditambahkan ke keranjang Anda</p>
                    <a href="/" class="btn-shop">🛍️ Mulai Belanja</a>
                </div>
            <?php else: ?>
                <!-- Store Groups -->
                <?php foreach ($cartItems as $storeId => $store): ?>
                    <div class="store-group" data-store-id="<?= $storeId ?>">
                        <!-- Store Header -->
                        <div class="store-header">
                            <img 
                                src="<?= htmlspecialchars($store['store_logo_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Crect width=%2240%22 height=%2240%22 fill=%22%23ddd%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22%3E🏪%3C/text%3E%3C/svg%3E') ?>" 
                                alt="Store Logo" 
                                class="store-logo"
                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22%3E%3Crect width=%2240%22 height=%2240%22 fill=%22%23ddd%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22%3E🏪%3C/text%3E%3C/svg%3E'"
                            >
                            <div class="store-info">
                                <h3><?= htmlspecialchars($store['store_name']) ?></h3>
                                <div class="store-items-count"><?= count($store['items']) ?> Produk</div>
                            </div>
                        </div>

                        <!-- Cart Items -->
                        <?php foreach ($store['items'] as $item): ?>
                            <?php 
                            // DEBUG: Check if cart_item_id exists
                            if (!isset($item['cart_item_id']) || empty($item['cart_item_id'])) {
                                error_log("WARNING: cart_item_id is missing for product_id: " . ($item['product_id'] ?? 'unknown'));
                                error_log("Item data: " . print_r($item, true));
                            }
                            ?>
                            <div class="cart-item" data-cart-id="<?= $item['cart_item_id'] ?? 'null' ?>" data-product-id="<?= $item['product_id'] ?? 'null' ?>">
                                <!-- DEBUG INFO (remove in production) -->
                                <!-- cart_item_id: <?= $item['cart_item_id'] ?? 'NULL' ?>, product_id: <?= $item['product_id'] ?? 'NULL' ?> -->
                                
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
                                </div>

                                <!-- Item Actions -->
                                <div class="item-actions">
                                    <div class="item-subtotal">
                                        Rp <?= number_format($item['item_subtotal'], 0, ',', '.') ?>
                                    </div>
                                    <button class="btn-remove" onclick="confirmRemove(<?= $item['cart_item_id'] ?>, '<?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?>')">
                                        🗑️ Hapus
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Store Subtotal -->
                        <div class="store-subtotal">
                            <span class="store-subtotal-label">Subtotal <?= htmlspecialchars($store['store_name']) ?>:</span>
                            <span class="store-subtotal-value">Rp <?= number_format($store['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Summary Panel -->
        <?php if (!empty($cartItems)): ?>
            <div class="summary-panel">
                <h2 class="summary-title">Ringkasan Belanja</h2>
                
                <div class="summary-row">
                    <span>Total Toko:</span>
                    <span><?= $storeCount ?> toko</span>
                </div>
                
                <div class="summary-row">
                    <span>Total Item:</span>
                    <span id="totalItemsDisplay"><?= $totalItems ?> item</span>
                </div>
                
                <?php foreach ($cartItems as $storeId => $store): ?>
                    <div class="summary-row">
                        <span><?= htmlspecialchars($store['store_name']) ?>:</span>
                        <span>Rp <?= number_format($store['subtotal'], 0, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-row total">
                    <span class="label">TOTAL:</span>
                    <span class="value" id="grandTotal">Rp <?= number_format($totalPrice, 0, ',', '.') ?></span>
                </div>
                
                <button class="checkout-btn" onclick="checkout()" <?= empty($cartItems) ? 'disabled' : '' ?>>
                    💳 Checkout (<?= $storeCount ?> Toko)
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icon">⚠️</span>
                <h3>Hapus dari Keranjang?</h3>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus <strong id="productNameToRemove"></strong> dari keranjang?</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal()">Batal</button>
                <button class="btn-confirm" onclick="removeItem()">Hapus</button>
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
    <script src="/js/cartPage.js"></script>
</body>
</html>
