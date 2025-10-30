<?php

// =================================================================
// ROUTES UNTUK SELLER & PUBLIC (BAGIAN DANEN)
// =================================================================
// file ini berisi routes untuk:
// - Seller Dashboard & Management
// - Product Discovery (Home/Public)
// - Store Management
// 
// auth routes (login, register, logout) ada di route/web.php
// =================================================================
// file ini di require setelah web.php

// ============================================
// PUBLIC API ROUTES
// ============================================

// api untuk mendapatkan csrf token
$router->get('/api/csrf-token', 'AuthController@getCsrfToken');

// ============================================
// SELLER ROUTES (khusus seller yang sudah login)
// ============================================

// halaman dashboard seller
$router->get('/seller/dashboard', 'StoreController@dashboard')
       ->middleware(['auth', 'seller']);

// halaman product management seller
$router->get('/seller/products', 'ProductController@index')
       ->middleware(['auth', 'seller']);

// api untuk mendapatkan produk seller dengan filter
$router->get('/api/seller/products', 'ProductController@getSellerProducts')
       ->middleware(['auth', 'seller']);

// halaman add product
$router->get('/seller/products/add', 'ProductController@create')
       ->middleware(['auth', 'seller']);

// api untuk menyimpan produk baru
$router->post('/api/seller/products', 'ProductController@store')
       ->middleware(['auth', 'seller', 'csrf']);

// halaman edit product
$router->get('/seller/products/edit', 'ProductController@edit')
       ->middleware(['auth', 'seller']);

// api untuk update produk
$router->post('/api/seller/products/update', 'ProductController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// api untuk delete produk
$router->delete('/api/seller/products', 'ProductController@delete')
       ->middleware(['auth', 'seller', 'csrf']);

// api untuk membuat toko (saat registrasi seller pertama kali)
$router->post('/api/stores', 'StoreController@create')
       ->middleware(['auth', 'seller', 'csrf']);

// api untuk update informasi toko seller
$router->patch('/api/my-store', 'StoreController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// api untuk mendapatkan data toko seller
$router->get('/api/my-store', 'StoreController@getMyStore')
       ->middleware(['auth', 'seller']);

// api untuk statistik dashboard seller
$router->get('/api/seller/store/stats', 'StoreController@getStats')
       ->middleware(['auth', 'seller']);

// halaman kelola pesanan seller
$router->get('/seller/orders', 'SellerOrderController@index')
       ->middleware(['auth', 'seller']);

// api untuk mendapatkan pesanan seller dengan filter
$router->get('/api/seller/orders', 'SellerOrderController@getSellerOrders')
       ->middleware(['auth', 'seller']);

// api untuk mendapatkan detail pesanan
$router->get('/api/seller/orders/detail', 'SellerOrderController@getOrderDetail')
       ->middleware(['auth', 'seller']);

// api untuk update status pesanan
$router->post('/api/seller/orders/update-status', 'SellerOrderController@updateOrderStatus')
       ->middleware(['auth', 'seller', 'csrf']);

// ============================================
// PUBLIC ROUTES (dapat diakses siapa saja)
// ============================================

// halaman home / product discovery
$router->get('/', 'ProductDiscoveryController@index');

// api untuk mendapatkan produk untuk discovery
$router->get('/api/products', 'ProductDiscoveryController@getProducts');

// halaman detail produk
$router->get('/product', 'ProductDiscoveryController@detail');

// halaman detail store / toko
$router->get('/store', 'ProductDiscoveryController@storeDetail');