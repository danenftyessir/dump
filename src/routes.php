<?php
$router = Router::getInstance();

// halaman utama
$router->get('/', 'ProductDiscoveryController@index');
$router->get('/home', 'ProductDiscoveryController@index');

// halaman detail produk
$router->get('/product/{id}', 'ProductDiscoveryController@showProduct');

// halaman detail toko
$router->get('/store/{id}', 'StoreController@show');

// API utk mendapatkan produk dengan filter, search, pagination
$router->get('/api/products', 'ProductDiscoveryController@getProducts');

// API utk detail produk
$router->get('/api/products/{id}', 'ProductDiscoveryController@showProduct');

// API utk search suggestions BONUS
$router->get('/api/search-suggestions', 'ProductDiscoveryController@getSearchSuggestions');

// API utk detail toko
$router->get('/api/stores/{id}', 'StoreController@show');

// API utk membuat toko
$router->post('/api/stores', 'StoreController@create');

// API utk update informasi toko seller
$router->patch('/api/my-store', 'StoreController@update');

// API utk mendapatkan data toko seller
$router->get('/api/my-store', 'StoreController@getMyStore');

// API utk statistik dashboard seller
$router->get('/api/seller/store/stats', 'StoreController@getStats');

// halaman dashboard seller
$router->get('/dashboard', 'StoreController@dashboard');

// routes untuk product management seller
$router->get('/seller/products', 'ProductController@index');
$router->get('/api/seller/products', 'ProductController@getSellerProducts');
$router->get('/seller/products/add', 'ProductController@create');
$router->post('/api/seller/products', 'ProductController@store');
$router->get('/seller/products/edit', 'ProductController@edit');
$router->post('/api/seller/products/update', 'ProductController@update');
$router->delete('/api/seller/products', 'ProductController@delete');

$router->dispatch();