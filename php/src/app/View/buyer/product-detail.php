<?php

$product = $product ?? null;
$isLoggedIn = $isLoggedIn ?? false;
$isBuyer = $isBuyer ?? false;
$_token = $csrfToken ?? '';
$currentUser = $currentUser ?? null;

if (!$product) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Produk tidak ditemukan.</p>";
    exit();
}

function sanitize_html($html) {
    $allowed_tags = '<p><strong><em><u><ul><ol><li><b><i><br><h2><h3><h4><h5><h6><blockquote>';
    return strip_tags($html, $allowed_tags);
}

$imagePath = $product['main_image_path'] 
    ? '/uploads/products/' . htmlspecialchars($product['main_image_path']) 
    : '/asset/placeholder-product.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Nimonspedia</title>
    
    <link rel="stylesheet" href="/css/buyer/product-detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php if ($isLoggedIn && $isBuyer): ?>
        <?php include __DIR__ . '/../components/navbar-buyer.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/../components/navbar-guest.php'; ?>
    <?php endif; ?>

    <main class="detail-container">
        <section class="product-main-card">
            
            <div class="product-gallery">
                <img src="<?php echo $imagePath; ?>" 
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                     class="main-product-image"
                     onerror="this.src='/asset/placeholder-product.jpg'">
            </div>

            <div class="product-details">
                <div class="product-header">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <div class="product-categories">
                        <?php foreach ($product['categories'] as $category): ?>
                            <span class="category-badge"><?php echo htmlspecialchars($category['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <p class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                
                <div class="product-meta">
                    <span>Stok:</span>
                    <span class="<?php echo $product['stock'] > 0 ? 'stock-available' : 'stock-unavailable'; ?>">
                        <?php echo $product['stock'] > 0 ? $product['stock'] . ' Tersedia' : 'Habis'; ?>
                    </span>
                </div>

                <div class="add-to-cart-section">
                    <?php if ($product['stock'] > 0): ?>
                        <form id="addToCartForm">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="hidden" name="_token" id="csrf_token_input" value="<?php echo $_token; ?>">

                            <div class="quantity-selector">
                                <label for="quantity">Jumlah:</label>
                                <button type="button" class="quantity-btn" id="btn-minus">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly>
                                <button type="button" class="quantity-btn" id="btn-plus">+</button>
                            </div>
                            
                            <?php if ($isLoggedIn && $isBuyer): ?>
                                <button type="submit" class="btn btn-primary btn-add-to-cart" id="submitBtn">
                                    <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                                </button>
                            <?php else: ?>
                                <a href="/login" class="btn btn-primary">Login untuk Membeli</a>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <button class="btn" disabled>Stok Habis</button>
                    <?php endif; ?>
                </div>
                
                <?php if (!$isLoggedIn): ?>
                    <p class="guest-login-prompt">
                        Sudah punya akun? <a href="/login">Login di sini</a>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="product-info-tabs">
            <div class="tab-header">
                <button class="tab-link active" data-tab="description">Deskripsi</button>
                <button class="tab-link" data-tab="store-info">Informasi Toko</button>
            </div>
            <div class="tab-content-wrapper">
                
                <div id="description" class="tab-content active">
                    <?php echo sanitize_html($product['description']); ?>
                </div>
                
                <div id="store-info" class="tab-content">
                    <div class="store-card-inline">
                        <img src="<?php echo $product['store_logo_path'] ? '/uploads/logos/' . htmlspecialchars($product['store_logo_path']) : '/asset/placeholder-store.png'; ?>" alt="<?php echo htmlspecialchars($product['store_name']); ?>">
                        <div>
                            <h3><?php echo htmlspecialchars($product['store_name']); ?></h3>
                            <p><?php echo sanitize_html($product['store_description']); ?></p>
                            <a href="/store/<?php echo $product['store_id']; ?>" class="btn">Kunjungi Toko</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="toastContainer" class="toast-container"></div>

    <script src="/js/buyer/product-detail.js"></script>
</body>
</html>