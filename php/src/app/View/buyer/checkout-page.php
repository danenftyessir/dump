<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="/css/buyer/checkout-page.css">
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/toast.css">
    <link rel="stylesheet" href="/css/components/footer.css">
</head>
<body>
     <!-- Navbar -->
    <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    
    <div class="main-content">
        <div class="container">
        <!-- Main Checkout Section -->
        <div class="checkout-section">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                    <circle cx="8" cy="21" r="1"/>
                    <circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
                Checkout
            </h1>

            <!-- Delivery Address Form -->
            <div class="delivery-form">
                <h2>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Alamat Pengiriman
                </h2>
                <div class="form-group">
                    <label>Alamat Saat Ini</label>
                    <div class="current-address">
                        <?= htmlspecialchars($currentUser['address'] ?? 'Alamat belum diisi') ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="deliveryAddress">Ubah Alamat Pengiriman (Opsional)</label>
                    <textarea 
                        id="deliveryAddress" 
                        name="delivery_address" 
                        placeholder="Kosongkan jika menggunakan alamat saat ini, atau masukkan alamat baru (Jalan, Nomor, Kecamatan, Kota, Provinsi, Kode Pos)"
                    ></textarea>
                    <div class="note">* Alamat ini hanya untuk pengiriman pesanan ini</div>
                </div>
            </div>

            <!-- Order Items Grouped by Store -->
            <h2> Pesanan Anda (<?= $cartSummary['store_count'] ?> Toko)</h2>
            
            <?php foreach ($cartSummary['stores'] as $storeId => $store): ?>
                <div class="store-group">
                    <div class="store-header">
                        <img 
                            src="<?= htmlspecialchars($store['store_logo_path'] ?? '/asset/placeholder-store-logo.jpg') ?>" 
                            alt="Store Logo"
                            class="store-logo"
                            onerror="this.src='/asset/placeholder-store-logo.jpg'"
                        >
                        <span class="store-name"><?= htmlspecialchars($store['store_name']) ?></span>
                    </div>

                    <?php foreach ($store['items'] as $item): ?>
                        <div class="checkout-item">
                            <img 
                                src="<?= htmlspecialchars($item['main_image_path'] ?? '/asset/placeholder-product.jpg') ?>" 
                                alt="Product"
                                class="item-image"
                                onerror="this.src='/asset/placeholder-product.jpg'"
                            >
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                <div class="item-quantity">Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="item-subtotal">
                                Rp <?= number_format($item['item_subtotal'], 0, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="store-subtotal">
                        <span>Subtotal <?= htmlspecialchars($store['store_name']) ?>:</span>
                        <span>Rp <?= number_format($store['subtotal'], 0, ',', '.') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Panel -->
        <div class="summary-panel">
            <div class="summary-title">Ringkasan Belanja</div>
            
            <div class="summary-row">
                <span>Total Toko:</span>
                <span><?= $cartSummary['store_count'] ?> toko</span>
            </div>
            
            <div class="summary-row">
                <span>Total Item:</span>
                <span><?= $cartSummary['total_items_quantity'] ?> item</span>
            </div>

            <div class="balance-info">
                <div class="balance-row">
                    <span>Saldo Saat Ini:</span>
                    <strong>Rp <?= number_format($currentUser['balance'], 0, ',', '.') ?></strong>
                </div>
                <div class="balance-row">
                    <span>Total Belanja:</span>
                    <span>Rp <?= number_format($cartSummary['grand_total'], 0, ',', '.') ?></span>
                </div>
                <div class="balance-row">
                    <span>Saldo Setelah Checkout:</span>
                    <strong style="color: <?= $currentUser['balance'] >= $cartSummary['grand_total'] ? '#28a745' : '#dc3545' ?>">
                        Rp <?= number_format($currentUser['balance'] - $cartSummary['grand_total'], 0, ',', '.') ?>
                    </strong>
                </div>
            </div>

            <?php if ($currentUser['balance'] < $cartSummary['grand_total']): ?>
                <div class="balance-warning">
                    <strong>Saldo Tidak Cukup!</strong><br>
                    Kekurangan: Rp <?= number_format($cartSummary['grand_total'] - $currentUser['balance'], 0, ',', '.') ?><br>
                    <a href="#" class="topup-link" onclick="openTopupModal(); return false;">Top-up Saldo</a>
                </div>
            <?php endif; ?>

            <div class="summary-row total">
                <span>TOTAL PEMBAYARAN:</span>
                <span class="total-price">Rp <?= number_format($cartSummary['grand_total'], 0, ',', '.') ?></span>
            </div>

            <button 
                class="checkout-btn" 
                onclick="showConfirmModal()"
                <?= $currentUser['balance'] < $cartSummary['grand_total'] ? 'disabled' : '' ?>
            >
                <?= $currentUser['balance'] < $cartSummary['grand_total'] ? 'Saldo Tidak Cukup' : 'Bayar Sekarang' ?>
            </button>

            <p class="terms">
                Dengan melakukan checkout, Anda menyetujui syarat dan ketentuan yang berlaku
            </p>
        </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal" onclick="closeConfirmModal()">
        <div class="modal-overlay"></div>
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="m1 1 4 4 6 1 3 10h11"></path>
                </svg>
                Konfirmasi Pembayaran
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Pastikan detail pesanan Anda sudah benar:</p>
                
                <div class="confirm-row">
                    <span style="color: black">Total Toko:</span>
                    <strong><?= $cartSummary['store_count'] ?> toko</strong>
                </div>
                <div class="confirm-row">
                    <span style="color: black" >Total Item:</span>
                    <strong><?= $cartSummary['total_items_quantity'] ?> item</strong>
                </div>
                <div class="confirm-row">
                    <span style="color: black" >Total Pembayaran:</span>
                    <strong>Rp <?= number_format($cartSummary['grand_total'], 0, ',', '.') ?></strong>
                </div>
                <div class="confirm-row">
                    <span style="color: black" >Saldo Setelah Checkout:</span>
                    <strong style="color: #28a745;">Rp <?= number_format($currentUser['balance'] - $cartSummary['grand_total'], 0, ',', '.') ?></strong>
                </div>

                <p style="margin-top: 15px; color: #666; font-size: 14px;">
                    Pesanan akan dibuat terpisah untuk setiap toko dan menunggu persetujuan dari seller.
                </p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeConfirmModal()">Batal</button>
                <button class="btn btn-confirm" onclick="processCheckout()">Bayar Sekarang</button>
            </div>
        </div>
    </div>



    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text">Memproses pembayaran...</div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $_token ?>';
        const USER_BALANCE = <?= $currentUser['balance'] ?>;
        const TOTAL_PRICE = <?= $cartSummary['grand_total'] ?>;
        const DEFAULT_ADDRESS = '<?= htmlspecialchars($currentUser['address'] ?? '', ENT_QUOTES) ?>';
        
        // Initialize topup modal with custom quick amounts for checkout
        document.addEventListener('DOMContentLoaded', function() {
            const shortage = TOTAL_PRICE - USER_BALANCE;
            const customQuickAmounts = [50000, 100000, 200000, 500000, 1000000];
            
            // Add shortage amount if user doesn't have enough balance
            if (shortage > 0) {
                customQuickAmounts.push(shortage);
            }
            
            // Override default topup modal with custom amounts for checkout
            if (window.topupModalInstance) {
                window.topupModalInstance.options.quickAmounts = customQuickAmounts;
                window.topupModalInstance.createQuickAmountButtons();
            }
        });
    </script>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script src="/js/components/toast.js"></script>
    <script src="/js/buyer/checkout-page.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>
