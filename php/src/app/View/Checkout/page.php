<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Nimonspedia</title>
    <link rel="stylesheet" href="/css/checkout.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <!-- Main Checkout Section -->
        <div class="checkout-section">
            <h1>🛒 Checkout</h1>

            <!-- Delivery Address Form -->
            <div class="delivery-form">
                <h2>📍 Alamat Pengiriman</h2>
                <div class="form-group">
                    <label>Alamat Saat Ini</label>
                    <div class="current-address">
                        <?= htmlspecialchars($user['address'] ?? 'Alamat belum diisi') ?>
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
            <h2>📦 Pesanan Anda (<?= $storeCount ?> Toko)</h2>
            
            <?php foreach ($stores as $storeId => $store): ?>
                <div class="store-group">
                    <div class="store-header">
                        <img 
                            src="<?= htmlspecialchars($store['store_logo_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Crect fill=\'%23ddd\' width=\'40\' height=\'40\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'24\'%3E🏪%3C/text%3E%3C/svg%3E') ?>" 
                            alt="Store Logo"
                            class="store-logo"
                        >
                        <span class="store-name"><?= htmlspecialchars($store['store_name']) ?></span>
                    </div>

                    <?php foreach ($store['items'] as $item): ?>
                        <div class="checkout-item">
                            <img 
                                src="<?= htmlspecialchars($item['main_image_path'] ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23f0f0f0\' width=\'60\' height=\'60\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'24\'%3E📦%3C/text%3E%3C/svg%3E') ?>" 
                                alt="Product"
                                class="item-image"
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
                <span><?= $storeCount ?> toko</span>
            </div>
            
            <div class="summary-row">
                <span>Total Item:</span>
                <span><?= $totalItems ?> item</span>
            </div>

            <div class="balance-info">
                <div class="balance-row">
                    <span>Saldo Saat Ini:</span>
                    <strong>Rp <?= number_format($user['balance'], 0, ',', '.') ?></strong>
                </div>
                <div class="balance-row">
                    <span>Total Belanja:</span>
                    <span>Rp <?= number_format($totalPrice, 0, ',', '.') ?></span>
                </div>
                <div class="balance-row">
                    <span>Saldo Setelah Checkout:</span>
                    <strong style="color: <?= $user['balance'] >= $totalPrice ? '#28a745' : '#dc3545' ?>">
                        Rp <?= number_format($user['balance'] - $totalPrice, 0, ',', '.') ?>
                    </strong>
                </div>
            </div>

            <?php if ($user['balance'] < $totalPrice): ?>
                <div class="balance-warning">
                    <strong>⚠️ Saldo Tidak Cukup!</strong><br>
                    Kekurangan: Rp <?= number_format($totalPrice - $user['balance'], 0, ',', '.') ?><br>
                    <a href="#" class="topup-link" onclick="openTopUpModal(); return false;">💳 Top-up Saldo</a>
                </div>
            <?php endif; ?>

            <div class="summary-row total">
                <span>TOTAL PEMBAYARAN:</span>
                <span class="total-price">Rp <?= number_format($totalPrice, 0, ',', '.') ?></span>
            </div>

            <button 
                class="checkout-btn" 
                onclick="showConfirmModal()"
                <?= $user['balance'] < $totalPrice ? 'disabled' : '' ?>
            >
                💳 <?= $user['balance'] < $totalPrice ? 'Saldo Tidak Cukup' : 'Bayar Sekarang' ?>
            </button>

            <p class="terms">
                Dengan melakukan checkout, Anda menyetujui syarat dan ketentuan yang berlaku
            </p>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">🛍️ Konfirmasi Pembayaran</div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Pastikan detail pesanan Anda sudah benar:</p>
                
                <div class="confirm-row">
                    <span>Total Toko:</span>
                    <strong><?= $storeCount ?> toko</strong>
                </div>
                <div class="confirm-row">
                    <span>Total Item:</span>
                    <strong><?= $totalItems ?> item</strong>
                </div>
                <div class="confirm-row">
                    <span>Total Pembayaran:</span>
                    <strong style="color: #667eea;">Rp <?= number_format($totalPrice, 0, ',', '.') ?></strong>
                </div>
                <div class="confirm-row">
                    <span>Saldo Setelah Checkout:</span>
                    <strong style="color: #28a745;">Rp <?= number_format($user['balance'] - $totalPrice, 0, ',', '.') ?></strong>
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

    <!-- Top-up Modal -->
    <div id="topupModal" class="modal">
        <div class="modal-content topup-modal-content">
            <div class="modal-header">💰 Top-up Saldo</div>
            <div class="modal-body">
                <p style="margin-bottom: 10px;">Pilih nominal atau masukkan jumlah:</p>
                
                <div class="quick-amounts">
                    <div class="quick-amount" onclick="setTopUpAmount(50000)">Rp 50.000</div>
                    <div class="quick-amount" onclick="setTopUpAmount(100000)">Rp 100.000</div>
                    <div class="quick-amount" onclick="setTopUpAmount(200000)">Rp 200.000</div>
                    <div class="quick-amount" onclick="setTopUpAmount(500000)">Rp 500.000</div>
                    <div class="quick-amount" onclick="setTopUpAmount(1000000)">Rp 1.000.000</div>
                    <div class="quick-amount" onclick="setTopUpAmount(<?= $totalPrice - $user['balance'] ?>)">
                        Kekurangan<br>
                        <small>Rp <?= number_format($totalPrice - $user['balance'], 0, ',', '.') ?></small>
                    </div>
                </div>

                <input 
                    type="number" 
                    id="topupAmount" 
                    class="topup-input" 
                    placeholder="Atau masukkan nominal manual"
                    min="10000"
                    step="10000"
                >
            </div>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeTopUpModal()">Batal</button>
                <button class="btn btn-confirm" onclick="processTopUp()">Top-up</button>
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
        const USER_BALANCE = <?= $user['balance'] ?>;
        const TOTAL_PRICE = <?= $totalPrice ?>;
        const DEFAULT_ADDRESS = '<?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES) ?>';
    </script>
    <script src="/js/checkout.js"></script>
</body>
</html>
