<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Nimonspedia</title>
    <link rel="stylesheet" href="/css/utility.css">
    <link rel="stylesheet" href="/css/product-management.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <!-- TO DO navbar untuk seller -->
    <?php // include 'components/navbar-seller.php'; ?>

    <div class="product-form-container">
        <div class="form-header">
            <h1>Edit Produk</h1>
            <a href="/seller/products" class="btn-back">← Kembali</a>
        </div>

        <form id="productForm" enctype="multipart/form-data">
            <input type="hidden" id="productId" value="<?= htmlspecialchars($product['product_id'] ?? '') ?>">
            
            <!-- Foto Produk -->
            <div class="form-section">
                <h2>Foto Produk</h2>
                <div class="image-upload-section">
                    <input 
                        type="file" 
                        id="productImage" 
                        name="product_image" 
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        style="display: none;"
                    >
                    <div id="imagePreviewContainer" class="image-preview-container">
                        <?php if (!empty($product['main_image_path'])): ?>
                            <img id="imagePreview" class="image-preview" src="<?= htmlspecialchars($product['main_image_path']) ?>" style="display: block;">
                            <div class="upload-placeholder" id="uploadPlaceholder" style="display: none;">
                                <span class="upload-icon">📷</span>
                                <p>Klik Untuk Upload Foto</p>
                                <span class="upload-hint">JPG, PNG, WEBP (Max 2MB)</span>
                            </div>
                        <?php else: ?>
                            <div class="upload-placeholder" id="uploadPlaceholder">
                                <span class="upload-icon">📷</span>
                                <p>Klik Untuk Upload Foto</p>
                                <span class="upload-hint">JPG, PNG, WEBP (Max 2MB)</span>
                            </div>
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        <?php endif; ?>
                        <button type="button" class="btn-change-image" id="changeImageBtn">
                            Ganti Foto
                        </button>
                    </div>
                    <span class="error-message" id="imageError"></span>
                </div>
            </div>

            <!-- Informasi Produk -->
            <div class="form-section">
                <h2>Informasi Produk</h2>
                
                <div class="form-group">
                    <label for="productName">Nama Produk <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="productName" 
                        name="product_name" 
                        maxlength="200"
                        required
                        placeholder="Masukkan Nama Produk"
                        value="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                    >
                    <span class="char-counter" id="nameCounter">0/200</span>
                    <span class="error-message" id="nameError"></span>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi <span class="required">*</span></label>
                    <div id="quillEditor" class="quill-editor"></div>
                    <input type="hidden" id="description" name="description" required value="<?= htmlspecialchars($product['description'] ?? '') ?>">
                    <span class="char-counter" id="descCounter">0/5000</span>
                    <span class="error-message" id="descError"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Harga <span class="required">*</span></label>
                        <div class="input-with-prefix">
                            <span class="input-prefix">Rp</span>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                min="1000"
                                step="100"
                                required
                                placeholder="0"
                                value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                            >
                        </div>
                        <span class="error-message" id="priceError"></span>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stok <span class="required">*</span></label>
                        <input 
                            type="number" 
                            id="stock" 
                            name="stock" 
                            min="0"
                            required
                            placeholder="0"
                            value="<?= htmlspecialchars($product['stock'] ?? '') ?>"
                        >
                        <span class="error-message" id="stockError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kategori <span class="required">*</span></label>
                    <div class="checkbox-group">
                        <?php 
                        $selectedCategories = array_column($product['categories'] ?? [], 'category_id');
                        ?>
                        <?php if (isset($categories) && is_array($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <label class="checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        name="category_ids[]" 
                                        value="<?= htmlspecialchars($category['category_id']) ?>"
                                        <?= in_array($category['category_id'], $selectedCategories) ? 'checked' : '' ?>
                                    >
                                    <span><?= htmlspecialchars($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <span class="error-message" id="categoryError"></span>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="window.location.href='/seller/products'">
                    Batal
                </button>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <span id="submitText">Simpan Perubahan</span>
                    <span id="submitLoading" class="loading-spinner" style="display: none;"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        const isEditMode = true;
        const existingDescription = <?= json_encode($product['description'] ?? '') ?>;
    </script>
    <script src="/js/product-form.js"></script>
</body>
</html>