<?php
// navbar untuk guest (belum login)
?>

<link rel="stylesheet" href="/css/navbar-guest.css">

<nav class="navbar-guest">
    <div class="navbar-container">
        <!-- logo / brand -->
        <a href="/" class="navbar-brand">
            <h1>Nimonspedia</h1>
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