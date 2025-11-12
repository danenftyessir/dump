<?php

// Login Form
$router->get('/login', 'AuthController@loginForm')
       ->middleware('guest');

// Submit Login Form
$router->post('/login', 'AuthController@login')
       ->middleware(['guest', 'csrf']);

// Register Form
$router->get('/register', 'AuthController@registerForm')
       ->middleware('guest');

// Submit Register Form
$router->post('/register', 'AuthController@register')
       ->middleware(['guest', 'csrf']);

// Logout 
$router->post('/logout', 'AuthController@logout')
       ->middleware(['auth', 'csrf']);

// Profile Page
$router->get('/profile', 'AuthController@profile')
       ->middleware('auth');

// Submit Profile Update
$router->post('/profile/update', 'AuthController@updateProfile')
       ->middleware(['auth', 'csrf']);

// Change Password Page
$router->get('/profile/password', 'AuthController@passwordForm')
       ->middleware('auth');

// Submit Change Password
$router->post('/profile/change-password', 'AuthController@changePassword')
       ->middleware(['auth', 'csrf']);

// Home Page (Untuk Buyer/Guest ke home(product discovery), untuk Seller ke dashboard)
$router->get('/dashboard', 'AuthController@dashboard')
       ->middleware('auth');

// API for Get CSRF Token
$router->get('/api/csrf-token', 'AuthController@getCsrfToken');

// Home / Product Discovery Page
$router->get('/', 'ProductDiscoveryController@index');

// API for Top Up Balance (with API-specific middleware)
$router->post('/api/user/topup', 'Controller\BalanceController@topUp')
       ->middleware(['auth', 'csrf']);

// API for Get Current Balance
$router->get('/api/user/balance', 'Controller\BalanceController@getCurrentBalance')
       ->middleware(['auth']);

// API for Fetching Products with Filters
$router->get('/api/products', 'ProductDiscoveryController@getProducts');

// Product Detail Page (CSRF token needed for add to cart)
$router->get('/product/{id}', 'ProductDiscoveryController@showProduct');

// Store Detail Page
$router->get('/store/{id}', 'StoreController@show');

// API for Get Store Product
$router->get('/api/store/{id}/products', 'StoreController@getStoreProducts');

// Cart Page
$router->get('/cart', 'CartItemController@index')
       ->middleware(['auth', 'buyer']);

// API Add to Cart
$router->post('/api/cart/add', 'CartItemController@addToCart')
       ->middleware(['auth', 'buyer', 'csrf']);

// API for Update Quantity in Cart
$router->post('/api/cart/update', 'CartItemController@updateQuantity')
       ->middleware(['auth', 'buyer', 'csrf']);

// API Remove Item from Cart
$router->post('/api/cart/remove/{id}', 'CartItemController@removeFromCart')
       ->middleware(['auth', 'buyer', 'csrf']);

// Checkout Page (form checkout)
$router->get('/checkout', 'CheckoutController@index')
       ->middleware(['auth', 'buyer']);

// API Process Checkout
$router->post('/api/checkout', 'CheckoutController@process')
       ->middleware(['auth', 'buyer', 'csrf']);

// Order History Page
$router->get('/orders', 'Controller\OrderController@orderHistory')
       ->middleware(['auth', 'buyer']);

// Order Detail Page
$router->get('/orders/{id}', 'Controller\OrderController@showOrderDetail')
       ->middleware(['auth', 'buyer']);

// API Confirm Order Reception
$router->post('/orders/{id}/confirm', 'Controller\OrderController@confirmReception')
       ->middleware(['auth', 'buyer', 'csrf']);

// Seller Dashboard Page
$router->get('/seller/dashboard', 'Controller\StoreController@dashboard')
       ->middleware(['auth', 'seller']);

// API for Update Store Info
$router->post('/api/seller/store/update', 'Controller\StoreController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// Export Products to CSV (must be before /seller/products)
$router->get('/seller/products/export', 'Controller\ProductController@export')
       ->middleware(['auth', 'seller']);

// Seller Add Product Page (must be before /seller/products)
$router->get('/seller/products/add', 'Controller\ProductController@create')
       ->middleware(['auth', 'seller']);

// Seller Edit Product Page (must be before /seller/products)
$router->get('/seller/products/edit/{id}', 'Controller\ProductController@edit')
       ->middleware(['auth', 'seller']);

// Seller Product Management Page
$router->get('/seller/products', 'Controller\ProductController@index')
       ->middleware(['auth', 'seller']);

// API for Get Products
$router->get('/api/seller/products', 'Controller\ProductController@getProducts')
       ->middleware(['auth', 'seller']);

// API for Store New Product
$router->post('/api/seller/products', 'Controller\ProductController@store')
       ->middleware(['auth', 'seller', 'csrf']);

// API for Update Product
$router->post('/api/seller/products/update/{id}', 'Controller\ProductController@update')
       ->middleware(['auth', 'seller', 'csrf']);

// API for Delete Product
$router->post('/api/seller/products/delete/{id}', 'Controller\ProductController@delete')
       ->middleware(['auth', 'seller', 'csrf']);

// Export Orders to CSV (must be before /seller/orders)
$router->get('/seller/orders/export', 'Controller\SellerOrderController@export')
       ->middleware(['auth', 'seller']);

// Seller Order Management Page
$router->get('/seller/orders', 'Controller\SellerOrderController@index')
       ->middleware(['auth', 'seller']);

// API for Get Order Detail (must be before /api/seller/orders to avoid route conflict)
$router->get('/api/seller/orders/{id}', 'Controller\SellerOrderController@getOrderDetail')
       ->middleware(['auth', 'seller']);

// API for Approve Order
$router->post('/api/seller/orders/approve/{id}', 'Controller\SellerOrderController@approve')
       ->middleware(['auth', 'seller', 'csrf']);

// API for Reject Order
$router->post('/api/seller/orders/reject/{id}', 'Controller\SellerOrderController@reject')
       ->middleware(['auth', 'seller', 'csrf']);

// API for Set Delivery
$router->post('/api/seller/orders/set-delivery/{id}', 'Controller\SellerOrderController@setDelivery')
       ->middleware(['auth', 'seller', 'csrf']);

// API for Get CSRF Token
$router->get('/api/csrf-token', 'AuthController@getCsrfToken');
