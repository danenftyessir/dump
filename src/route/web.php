<?php
// ============================================
// File ini berisi routes untuk authentication:
// - Login
// - Register  
// - Logout
// - Profile
// - Dashboard redirect
// =============================================

// ============================================
// GUEST ROUTES (belum login)
// ============================================

// halaman login form
$router->get('/login', 'AuthController@loginForm')
       ->middleware('guest');

// submit login form
// NOTE: tidak pakai middleware 'guest' untuk menghindari redirect loop
// karena session baru di-set setelah validasi berhasil
$router->post('/login', 'AuthController@login')
       ->middleware('csrf');

// halaman register form
$router->get('/register', 'AuthController@registerForm')
       ->middleware('guest');

// submit register form
$router->post('/register', 'AuthController@register')
       ->middleware('csrf');

// ============================================
// AUTHENTICATED ROUTES (sudah login)
// ============================================

// logout
$router->post('/logout', 'AuthController@logout')
       ->middleware(['auth', 'csrf']);

// entry point setelah login - redirect berdasarkan role
$router->get('/dashboard', 'AuthController@dashboard')
       ->middleware('auth');

// halaman profile user
$router->get('/profile', 'AuthController@profile')
       ->middleware('auth');

// clear session untuk debugging
// TODO: hapus di production
$router->get('/clear-session', 'AuthController@clearSession');