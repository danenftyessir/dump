<?php
// ambil data user dari session
$currentUser = $_SESSION['user'] ?? null;
$currentPage = $_SERVER['REQUEST_URI'] ?? '/';
?>
<nav class="navbar-seller">
    <div class="navbar-container">
        <!-- brand -->
        <a href="/seller/dashboard" class="navbar-brand">
            <img src="/asset/nimonspedia-logo.svg" alt="Nimonspedia" class="navbar-logo">
            <span class="navbar-brand-text">
                Nimon<span class="highlight">spedia</span>
            </span>
        </a>

        <!-- menu desktop -->
        <ul class="navbar-menu" id="navbarMenu">
            <li>
                <a href="/seller/dashboard" class="navbar-link <?= strpos($currentPage, '/seller/dashboard') === 0 ? 'active' : '' ?>">
                    <img src="/asset/icon-dashboard.svg" alt="Dashboard" class="navbar-link-icon">
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/seller/products" class="navbar-link <?= strpos($currentPage, '/seller/products') === 0 ? 'active' : '' ?>">
                    <img src="/asset/icon-products.svg" alt="Products" class="navbar-link-icon">
                    <span>Produk</span>
                </a>
            </li>
            <li>
                <a href="/seller/orders" class="navbar-link <?= strpos($currentPage, '/seller/orders') === 0 ? 'active' : '' ?>">
                    <img src="/asset/icon-orders.svg" alt="Orders" class="navbar-link-icon">
                    <span>Pesanan</span>
                </a>
            </li>
        </ul>

        <!-- actions -->
        <div class="navbar-actions">
            <!-- balance display (desktop only) -->
            <?php if ($currentUser): ?>
                <div class="navbar-balance">
                    <img src="/asset/icon-wallet.svg" alt="Balance" style="width: 20px; height: 20px;">
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
                </div>

                <!-- dropdown menu (akan ditambahkan via JavaScript) -->
                <div id="userDropdown" class="user-dropdown" style="display: none;">
                    <a href="/profile" class="dropdown-item">
                        <img src="/asset/icon-user.svg" alt="Profile">
                        <span>Profil Saya</span>
                    </a>
                    <a href="/" class="dropdown-item">
                        <img src="/asset/icon-home.svg" alt="Home">
                        <span>Kembali Ke Beranda</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="/logout" method="POST" style="margin: 0;">
                        <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit" class="dropdown-item dropdown-logout">
                            <img src="/asset/icon-logout.svg" alt="Logout">
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- mobile menu toggle -->
            <button class="navbar-toggle" onclick="toggleNavbarMenu()" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</nav>

<!-- user dropdown styles -->
<style>
.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 24px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    min-width: 220px;
    overflow: hidden;
    z-index: 200;
    animation: dropdownSlide 0.2s ease-out;
}

@keyframes dropdownSlide {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    color: #31353b;
    transition: background 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 0.95rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item img {
    width: 18px;
    height: 18px;
}

.dropdown-divider {
    height: 1px;
    background: #e8eaed;
    margin: 8px 0;
}

.dropdown-logout {
    color: #e74c3c;
}

.dropdown-logout:hover {
    background: #ffe5e5;
}
</style>

<!-- navbar scripts -->
<script>
// toggle mobile menu
function toggleNavbarMenu() {
    const menu = document.getElementById('navbarMenu');
    menu.classList.toggle('navbar-menu-open');
}

// toggle user dropdown
let dropdownOpen = false;
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdownOpen = !dropdownOpen;
    dropdown.style.display = dropdownOpen ? 'block' : 'none';
}

// close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userDiv = document.querySelector('.navbar-user');
    const dropdown = document.getElementById('userDropdown');
    
    if (dropdown && !userDiv.contains(event.target) && !dropdown.contains(event.target)) {
        dropdownOpen = false;
        dropdown.style.display = 'none';
    }
});

// close mobile menu when resizing to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 968) {
        const menu = document.getElementById('navbarMenu');
        menu.classList.remove('navbar-menu-open');
    }
});
</script>