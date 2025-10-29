<?php

// =================================================================
// ROUTES UNTUK SELLER & PUBLIC (BAGIAN DANEN)
// =================================================================
// File ini berisi routes untuk:
// - Seller Dashboard & Management
// - Product Discovery (Home/Public)
// - Store Management
// 
// Auth routes (login, register, logout) ada di route/web.php (Fayadh)
// =================================================================

// gunakan router instance dari container yang sudah dibuat di index.php
// file ini di-require setelah web.php

// ============================================
// SELLER ROUTES (khusus seller yang sudah login)
// ============================================

// halaman dashboard seller
$router->get('/seller/dashboard', 'StoreController@dashboard')
       ->middleware(['auth', 'seller']);

// halaman product management seller
$router->get('/seller/products', 'ProductController@index')
       ->middleware(['auth', 'seller']);

// API untuk mendapatkan produk seller dengan filter
$router->get('/api/seller/products', 'ProductController@getSellerProducts')
       ->middleware(['auth', 'seller']);

// halaman add product
$router->get('/seller/products/add', 'ProductController@create')
       ->middleware(['auth', 'seller']);

// API untuk menyimpan produk baru
$router->post('/api/seller/products', 'ProductController@store')
       ->middleware(['auth', 'seller', 'csrf']);

// halaman edit product
$router->get('/seller/products/edit', 'ProductController@edit')
       ->middleware(['auth', 'seller']);

// API untuk update produk
$router->post('/api/seller/products/update', 'ProductController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// API untuk delete produk
$router->delete('/api/seller/products', 'ProductController@delete')
       ->middleware(['auth', 'seller', 'csrf']);

// API untuk membuat toko (saat registrasi seller pertama kali)
$router->post('/api/stores', 'StoreController@create')
       ->middleware(['auth', 'seller', 'csrf']);

// API untuk update informasi toko seller
$router->patch('/api/my-store', 'StoreController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// API untuk mendapatkan data toko seller
$router->get('/api/my-store', 'StoreController@getMyStore')
       ->middleware(['auth', 'seller']);

// API untuk statistik dashboard seller
$router->get('/api/seller/store/stats', 'StoreController@getStats')
       ->middleware(['auth', 'seller']);

// ============================================
// PUBLIC ROUTES (dapat diakses semua user)
// ============================================

// halaman utama - product discovery
$router->get('/', 'ProductDiscoveryController@index');
$router->get('/home', 'ProductDiscoveryController@index');

// halaman detail produk
$router->get('/product/{id}', 'ProductDiscoveryController@showProduct');

// halaman detail toko
$router->get('/store/{id}', 'StoreController@show');

// API untuk mendapatkan produk dengan filter, search, pagination
$router->get('/api/products', 'ProductDiscoveryController@getProducts');

// API untuk detail produk
$router->get('/api/products/{id}', 'ProductDiscoveryController@showProduct');

// API untuk search suggestions - BONUS
$router->get('/api/search-suggestions', 'ProductDiscoveryController@getSearchSuggestions');

// API untuk detail toko
$router->get('/api/stores/{id}', 'StoreController@show');