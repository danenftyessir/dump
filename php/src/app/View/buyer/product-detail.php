<?php

$product = $product ?? null;
$isLoggedIn = $isLoggedIn ?? false;
$isBuyer = $isBuyer ?? false;
$_token = $csrfToken ?? $_token ?? '';
$currentUser = $currentUser ?? null;

$imagePath = $product['main_image_path'] 
    ? '/uploads/products/' . htmlspecialchars($product['main_image_path']) 
    : '/asset/placeholder-product.jpg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?></title>
    <link rel="stylesheet" href="/css/buyer/base.css">
    <link rel="stylesheet" href="/css/buyer/product-detail.css">
    <link rel="stylesheet" href="/css/components/navbar-base.css">
    <link rel="stylesheet" href="/css/components/navbar-guest.css">
    <link rel="stylesheet" href="/css/components/navbar-buyer.css">
    <link rel="stylesheet" href="/css/components/toast.css">
    <link rel="stylesheet" href="/css/components/footer.css">
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
                            <!-- Debug: CSRF Token in HTML -->
                            <?php if (empty($_token)): ?>
                                <!-- WARNING: CSRF Token is EMPTY! -->
                            <?php endif; ?>

                            <div class="quantity-selector">
                                <label for="quantity" class="quantity-label">Jumlah:</label>
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" id="btn-minus">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14"/>
                                        </svg>
                                    </button>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly class="quantity-input">
                                    <button type="button" class="quantity-btn" id="btn-plus">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14m-7-7h14"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($isLoggedIn && $isBuyer): ?>
                                <button type="submit" class="btn btn-primary btn-add-to-cart" id="submitBtn">
                                    <span class="icon icon-cart"></span> Tambah ke Keranjang
                                </button>
                            <?php else: ?>
                                <a href="/login" class="btn btn-primary">Login untuk Membeli</a>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <button class="btn" disabled>Stok Habis</button>
                    <?php endif; ?>
                </div>
                

            </div>
        </section>

        <section class="product-info-tabs">
            <div class="tab-header">
                <button class="tab-link active" data-tab="description">Deskripsi</button>
                <button class="tab-link" data-tab="store-info">Informasi Toko</button>
            </div>
            <div class="tab-content-wrapper">
                
                <div id="description" class="tab-content active">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div id="store-info" class="tab-content">
                    <div class="store-card-inline">
                        <img src="<?php echo $product['store_logo_path'] ? '/uploads/logos/' . htmlspecialchars($product['store_logo_path']) : '/asset/placeholder-store-logo.jpg'; ?>" alt="<?php echo htmlspecialchars($product['store_name']); ?>">
                        <div>
                            <h3><?php echo htmlspecialchars($product['store_name']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($product['store_description'])); ?></p>
                            <a href="/store/<?php echo $product['store_id']; ?>" class="btn">Kunjungi Toko</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="toastContainer" class="toast-container"></div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script src="/js/components/toast.js"></script>
    <script src="/js/buyer/product-detail.js"></script>
    <script src="/js/components/navbar-buyer.js"></script>
</body>
</html>