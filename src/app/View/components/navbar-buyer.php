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
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 40 40" fill="none" style="margin-right: 8px;">
                <circle cx="20" cy="20" r="18" fill="#10B981"/>
                <path d="M20 10L28 15V25L20 30L12 25V15L20 10Z" fill="white"/>
                <path d="M20 10V20M12 15L20 20L28 15" stroke="#10B981" stroke-width="1.5"/>
            </svg>
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
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="21" r="1"/>
                    <circle cx="19" cy="21" r="1"/>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= min($cartCount, 99) ?></span>
                <?php endif; ?>
            </a>

            <!-- balance display -->
            <button class="navbar-balance" onclick="openTopUpModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                    <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                    <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                </svg>
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
<!-- TODO: implementasi modal top-up (Farrell - Buyer Features) -->
<div id="topUpModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeTopUpModal()"></div>
    <div class="modal-content">
        <button class="modal-close-btn" onclick="closeTopUpModal()">
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