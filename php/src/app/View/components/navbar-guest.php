<?php

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

        <!-- auth buttons -->
        <div class="navbar-actions">
            <a href="/login" class="btn-login">Masuk</a>
            <a href="/register" class="btn-register">Daftar</a>
        </div>
    </div>
</nav>