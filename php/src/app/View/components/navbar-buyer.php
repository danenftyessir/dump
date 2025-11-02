<?php
$cartCount = $cartCount ?? 0;
$currentUser = $currentUser ?? ['name' => 'User', 'balance' => 0];
$csrfToken = $_token ?? '';

$userName = htmlspecialchars($currentUser['name']);
$userBalance = (int)($currentUser['balance']);
?>

<nav class="navbar-base">
    <div class="navbar-container">
        <!-- logo / brand -->
        <a href="/" class="navbar-brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" class="navbar-logo">
                <circle cx="20" cy="20" r="18" fill="#10B981"/>
                <path d="M20 10L28 15V25L20 30L12 25V15L20 10Z" fill="white"/>
                <path d="M20 10V20M12 15L20 20L28 15" stroke="#10B981" stroke-width="1.5"/>
            </svg>
            <span class="navbar-brand-text">
                Nimonspedia
            </span>
        </a>

        <!-- navigation links -->
        <div class="navbar-links">
            <a href="/" class="navbar-link">Beranda</a>
        </div>

        <!-- navbar actions -->
        <div class="navbar-actions">
            <!-- cart button -->
            <a href="/cart" class="navbar-cart">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="21" r="1"/>
                    <circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
                <span class="cart-badge" id="cart-badge" style="<?= $cartCount > 0 ? 'display: flex;' : 'display: none;' ?>">
                    <?= min($cartCount, 99) ?>
                </span>
            </a>

            <!-- balance display -->
            <button class="navbar-balance" id="openTopUpModalBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                </svg>
                <span class="balance-label">Saldo:</span>
                <span class="balance-amount" id="navbar-balance">Rp <?= number_format($userBalance, 0, ',', '.') ?></span>
            </button>

            <!-- user dropdown -->
            <div class="navbar-user-wrapper">
                <button class="navbar-user" id="userDropdownToggle">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-icon">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>

                <!-- dropdown menu -->
                <div id="userDropdownMenu" class="user-dropdown" style="display: none;">
                    <a href="/profile" class="dropdown-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>Profil</span>
                    </a>
                    <a href="/orders" class="dropdown-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        <span>Riwayat Pesanan</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="/logout" method="POST" style="width: 100%;">
                        <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <button type="submit" class="dropdown-item logout-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- modal top-up saldo -->
<div id="topUpModal" class="modal" style="display: none;">
    <div class="modal-overlay" id="topUpModalOverlay"></div>
    <div class="modal-content">
        <button class="modal-close-btn" id="closeTopUpModalBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="modal-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
            </svg>
        </div>
        <h3>Top Up Saldo</h3>
        <p>Pilih nominal top-up atau masukkan jumlah sendiri (minimal Rp 10.000)</p>
        
        <!-- Preset amounts -->
        <div class="topup-presets">
            <button type="button" class="preset-btn" data-amount="10000">Rp 10.000</button>
            <button type="button" class="preset-btn" data-amount="25000">Rp 25.000</button>
            <button type="button" class="preset-btn" data-amount="50000">Rp 50.000</button>
            <button type="button" class="preset-btn" data-amount="100000">Rp 100.000</button>
            <button type="button" class="preset-btn" data-amount="200000">Rp 200.000</button>
            <button type="button" class="preset-btn" data-amount="500000">Rp 500.000</button>
        </div>

        <!-- Custom amount input -->
        <form id="topUpForm" onsubmit="handleTopUp(event)">
            <input type="hidden" name="_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div class="input-group">
                <label for="topUpAmount">Jumlah Top-Up:</label>
                <div class="currency-input">
                    <span class="currency-prefix">Rp</span>
                    <input type="number" id="topUpAmount" name="amount" min="10000" step="1000" placeholder="10000" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelTopUpBtn" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" id="topUpSubmitBtn">Top Up Sekarang</button>
            </div>
        </form>
    </div>
</div>