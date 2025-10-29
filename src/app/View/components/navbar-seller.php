<?php
// ambil data user dari session
$currentUser = $_SESSION['user'] ?? null;
$currentPage = $_SERVER['REQUEST_URI'] ?? '/';
?>
<nav class="navbar-seller">
    <div class="navbar-container">
        <!-- brand -->
        <a href="/seller/dashboard" class="navbar-brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none" class="navbar-logo">
                <circle cx="20" cy="20" r="18" fill="#10B981"/>
                <path d="M20 10L28 15V25L20 30L12 25V15L20 10Z" fill="white"/>
                <path d="M20 10V20M12 15L20 20L28 15" stroke="#10B981" stroke-width="1.5"/>
            </svg>
            <span class="navbar-brand-text">
                Nimon<span class="highlight">spedia</span>
            </span>
        </a>

        <!-- menu desktop -->
        <ul class="navbar-menu" id="navbarMenu">
            <li>
                <a href="/seller/dashboard" class="navbar-link <?= strpos($currentPage, '/seller/dashboard') === 0 ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-link-icon">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/seller/products" class="navbar-link <?= strpos($currentPage, '/seller/products') === 0 ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-link-icon">
                        <path d="M16.5 9.4l-9-5.19"/>
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    <span>Produk</span>
                </a>
            </li>
            <li>
                <a href="/seller/orders" class="navbar-link <?= strpos($currentPage, '/seller/orders') === 0 ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-link-icon">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <span>Pesanan</span>
                </a>
            </li>
        </ul>

        <!-- actions -->
        <div class="navbar-actions">
            <!-- balance display (desktop only) -->
            <?php if ($currentUser): ?>
                <div class="navbar-balance">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                        <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
                    </svg>
                    <span>Rp <?= number_format($currentUser['balance'] ?? 0) ?></span>
                </div>
            <?php endif; ?>

            <!-- user dropdown -->
            <?php if ($currentUser): ?>
                <div class="navbar-user" onclick="toggleUserDropdown()">
                    <div class="navbar-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="navbar-user-name"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-icon">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
                
                <!-- dropdown menu -->
                <div id="userDropdownMenu" class="user-dropdown" style="display: none;">
                    <form action="/logout" method="POST" style="width: 100%;">
                        <button type="submit" class="dropdown-item logout-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- hamburger mobile -->
            <button class="navbar-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

<script>
// toggle user dropdown
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdownMenu');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// toggle mobile menu
function toggleMobileMenu() {
    const menu = document.getElementById('navbarMenu');
    menu.classList.toggle('active');
}

// close dropdown ketika click di luar
document.addEventListener('click', function(event) {
    const userDropdown = document.querySelector('.navbar-user');
    const dropdown = document.getElementById('userDropdownMenu');
    
    if (userDropdown && !userDropdown.contains(event.target) && dropdown) {
        dropdown.style.display = 'none';
    }
});
</script>