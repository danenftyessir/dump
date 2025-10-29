<?php
// navbar untuk buyer yang sudah login
// TODO: ambil cart count dari database
$cartCount = $_SESSION['cart_count'] ?? 0;
$currentUser = $_SESSION['user'] ?? null;
$userBalance = $currentUser['balance'] ?? 0;
?>

<link rel="stylesheet" href="/css/navbar-buyer.css">

<nav class="navbar-buyer">
    <div class="navbar-container">
        <!-- logo / brand -->
        <a href="/" class="navbar-brand">
            <h1>Nimonspedia</h1>
        </a>

        <!-- navigation links -->
        <div class="navbar-links">
            <a href="/" class="navbar-link">Beranda</a>
        </div>

        <!-- navbar actions -->
        <div class="navbar-actions">
            <!-- cart button -->
            <a href="/cart" class="navbar-cart">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= min($cartCount, 99) ?></span>
                <?php endif; ?>
            </a>

            <!-- balance display -->
            <button class="navbar-balance" onclick="openTopUpModal()">
                <span class="balance-label">Saldo:</span>
                <span class="balance-amount">Rp <?= number_format($userBalance, 0, ',', '.') ?></span>
            </button>

            <!-- user dropdown -->
            <div class="navbar-user-wrapper">
                <button class="navbar-user" onclick="toggleUserDropdown()">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    <svg class="dropdown-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>

                <!-- dropdown menu -->
                <div id="userDropdownMenu" class="user-dropdown" style="display: none;">
                    <a href="/profile" class="dropdown-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>Profil</span>
                    </a>
                    <a href="/orders" class="dropdown-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                        <span>Riwayat Pesanan</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="/logout" method="POST" style="margin: 0;">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit" class="dropdown-item logout-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
<!-- TODO: implementasi modal top-up (Farrell - Buyer Features) -->
<div id="topUpModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeTopUpModal()"></div>
    <div class="modal-content">
        <h3>Top Up Saldo</h3>
        <p>Fitur top-up saldo akan segera hadir.</p>
        <button onclick="closeTopUpModal()" class="btn-primary">Tutup</button>
    </div>
</div>

<script>
// toggle user dropdown
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// close dropdown ketika click di luar
document.addEventListener('click', function(event) {
    const userWrapper = document.querySelector('.navbar-user-wrapper');
    const dropdown = document.getElementById('userDropdownMenu');
    
    if (userWrapper && !userWrapper.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// modal top-up functions
function openTopUpModal() {
    document.getElementById('topUpModal').style.display = 'flex';
}

function closeTopUpModal() {
    document.getElementById('topUpModal').style.display = 'none';
}
</script>