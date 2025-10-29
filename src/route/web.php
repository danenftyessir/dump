<?php

// Product Discovery
$router->get('/', function() {
    echo "<h1>Selamat Datang di Nimonspedia!</h1> Halaman Product Discovery."; // Todo buat halaman home/product discovery
});

// Login Form
$router->get('/login', 'AuthController@loginForm')->middleware('guest');

// Submit Login Form
$router->post('/login', 'AuthController@login')->middleware(['guest', 'csrf']);

// Register Form
$router->get('/register', 'AuthController@registerForm')->middleware('guest');

// Submit Register Form
$router->post('/register', 'AuthController@register')->middleware(['guest', 'csrf']);

// Logout
$router->post('/logout', 'AuthController@logout')->middleware(['auth', 'csrf']);

// Entry Point for User after Login
$router->get('/dashboard', 'AuthController@dashboard')->middleware('auth');

// Profile Page
$router->get('/profile', 'AuthController@profile')->middleware('auth');

// Seller Dashboard
$router->get('/seller/dashboard', function() {
    echo "<h1>Dashboard Seller</h1>";
})->middleware(['auth', 'seller']);

// Debugging
$router->get('/clear-session', 'AuthController@clearSession');