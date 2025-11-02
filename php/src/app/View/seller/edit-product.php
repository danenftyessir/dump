<?php
// pastikan ada data produk, store, categories, dan productCategories dari controller
$product = $product ?? null;
$store = $store ?? null;
$categories = $categories ?? [];
$productCategories = $productCategories ?? [];

// array id kategori produk untuk mempermudah pengecekan
$productCategoryIds = array_column($productCategories, 'category_id');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Nimonspedia</title>
    <link rel="stylesheet" href="/css/seller/seller-product-form.css">
    <link rel="stylesheet" href="/css/seller/seller-common.css">
    <!-- quill editor untuk rich text -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <!-- navbar seller -->
    <?php include __DIR__ . '/../components/navbar-seller.php'; ?>

    <div class="form-wrapper">
        <!-- header section -->
        <section class="form-header">
            <div class="form-header-content">
                <h1 class="page-title">Edit Produk</h1>
                <p class="page-subtitle">Perbarui Informasi Produk Anda</p>
            </div>
        </section>

        <div class="form-container">
            <form id="productForm" class="product-form" enctype="multipart/form-data">
                <!-- input hidden -->
                <input type="hidden" name="product_id" id="productId" value="<?= htmlspecialchars($product['product_id'] ?? '') ?>">
                <input type="hidden" name="csrf_token" id="csrfToken" value="">
                <input type="hidden" name="current_image" id="currentImage" value="<?= htmlspecialchars($product['main_image_path'] ?? '') ?>">

                <!-- section: informasi dasar -->
                <div class="form-section">
                    <h2 class="section-title">Informasi Dasar</h2>
                    
                    <div class="form-group">
                        <label for="productName" class="form-label">
                            Nama Produk <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="productName" 
                            name="product_name" 
                            class="form-input" 
                            maxlength="200" 
                            placeholder="Masukkan Nama Produk"
                            value="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                            required
                        >
                        <div class="char-counter">
                            <span id="nameCharCount">0</span>/200 Karakter
                        </div>
                        <div class="error-message" id="nameError"></div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">
                            Deskripsi Produk <span class="required">*</span>
                        </label>
                        <div id="quillEditor" class="quill-editor"></div>
                        <input type="hidden" id="description" name="description" value="<?= htmlspecialchars($product['description'] ?? '') ?>">
                        <div class="char-counter">
                            <span id="descCharCount">0</span>/1000 Karakter
                        </div>
                        <div class="error-message" id="descError"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price" class="form-label">
                                Harga (Rp) <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                class="form-input" 
                                min="1000" 
                                placeholder="Minimal Rp 1.000"
                                value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                                required
                            >
                            <div class="error-message" id="priceError"></div>
                        </div>

                        <div class="form-group">
                            <label for="stock" class="form-label">
                                Stok <span class="required">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="stock" 
                                name="stock" 
                                class="form-input" 
                                min="0" 
                                placeholder="Jumlah Stok"
                                value="<?= htmlspecialchars($product['stock'] ?? '') ?>"
                                required
                            >
                            <div class="error-message" id="stockError"></div>
                        </div>
                    </div>
                </div>

                <!-- section: kategori -->
                <div class="form-section">
                    <h2 class="section-title">Kategori Produk</h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Pilih Kategori <span class="required">*</span>
                        </label>
                        <div class="categories-grid" id="categoriesGrid">
                            <?php foreach ($categories as $category): ?>
                                <?php $isChecked = in_array($category['category_id'], $productCategoryIds); ?>
                                <label class="category-checkbox">
                                    <input 
                                        type="checkbox" 
                                        name="category_ids[]" 
                                        value="<?= htmlspecialchars($category['category_id']) ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                    >
                                    <span class="category-label">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="error-message" id="categoryError"></div>
                    </div>
                </div>

                <!-- section: foto produk -->
                <div class="form-section">
                    <h2 class="section-title">Foto Produk</h2>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Upload Foto <span class="text-muted">(Opsional - Kosongkan jika tidak ingin mengganti)</span>
                        </label>
                        <div class="upload-area" id="uploadArea">
                            <input 
                                type="file" 
                                id="mainImage" 
                                name="main_image" 
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                hidden
                            >
                            <?php if (!empty($product['main_image_path'])): ?>
                                <!-- show current image -->
                                <div class="image-preview flex" id="imagePreview">
                                    <img src="<?= htmlspecialchars($product['main_image_path']) ?>" alt="Current Product" id="previewImage">
                                    <div class="preview-overlay">
                                        <button type="button" class="btn-change-image" id="changeImageBtn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="17 8 12 3 7 8"/>
                                                <line x1="12" y1="3" x2="12" y2="15"/>
                                            </svg>
                                            <span>Ganti Foto</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="upload-placeholder hidden" id="uploadPlaceholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                    <p class="upload-text">Klik Untuk Upload Foto</p>
                                    <p class="upload-hint">Format: JPG, PNG, WEBP (Max 2MB)</p>
                                </div>
                            <?php else: ?>
                                <!-- no image yet -->
                                <div class="upload-placeholder" id="uploadPlaceholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21 15 16 10 5 21"/>
                                    </svg>
                                    <p class="upload-text">Klik Untuk Upload Foto</p>
                                    <p class="upload-hint">Format: JPG, PNG, WEBP (Max 2MB)</p>
                                </div>
                                <div class="image-preview hidden" id="imagePreview">
                                    <img src="" alt="Preview" id="previewImage">
                                    <button type="button" class="btn-change-image" id="changeImageBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                        <span>Ganti Foto</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="error-message" id="imageError"></div>
                    </div>
                </div>

                <!-- form actions -->
                <div class="form-actions">
                    <a href="/seller/products" class="btn-secondary">
                        <span>Batal</span>
                    </a>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- loading overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-content">
            <div class="loader"></div>
            <p>Menyimpan Perubahan...</p>
        </div>
    </div>

    <!-- toast notification -->
    <div id="toast" class="toast hidden">
        <div class="toast-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast-icon">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span id="toastMessage">Berhasil!</span>
        </div>
    </div>

    <!-- quill editor library -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="/js/seller/seller-product-edit.js?v=<?= time() ?>"></script>
</body>
</html>