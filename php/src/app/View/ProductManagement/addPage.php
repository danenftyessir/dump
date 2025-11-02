<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - WBD Store</title>
    <link rel="stylesheet" href="/css/addUpdatePage.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tambah Produk Baru</h1>
            <p>Tambahkan produk baru ke toko Anda</p>
        </div>

        <div class="form-container">
            <form id="addProductForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($data['_token']) ?>">
                
                <!-- Image Section -->
                <div class="image-section">
                    <label>Foto Produk <span class="required">*</span></label>
                    
                    <div class="image-upload-area" id="uploadArea" onclick="document.getElementById('imageInput').click()">
                        <div id="uploadPlaceholder">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">Klik untuk upload foto produk</div>
                            <div class="upload-hint">Format: JPG, PNG, GIF (Max 2MB)</div>
                        </div>
                        
                        <div class="image-preview" id="imagePreview">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="remove-image" onclick="event.stopPropagation(); removeImage();">
                                ✕
                            </button>
                        </div>
                    </div>
                    
                    <input type="file" 
                           id="imageInput" 
                           name="main_image" 
                           accept="image/jpeg,image/jpg,image/png,image/gif"
                           class="image-upload-input"
                           required>
                    
                    <div class="image-info" id="imageInfo">
                        <p>✓ Foto dipilih</p>
                        <small id="imageName"></small>
                    </div>
                </div>

                <!-- Product Name -->
                <div class="form-group">
                    <label for="product_name">Nama Produk <span class="required">*</span></label>
                    <input type="text" 
                           id="product_name" 
                           name="product_name" 
                           placeholder="Masukkan nama produk"
                           required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" 
                              name="description"
                              placeholder="Masukkan deskripsi produk (opsional)"></textarea>
                </div>

                <!-- Price -->
                <div class="form-group">
                    <label for="price">Harga (Rp) <span class="required">*</span></label>
                    <input type="number" 
                           id="price" 
                           name="price" 
                           placeholder="0"
                           min="0" 
                           step="1000"
                           required>
                    <small>💡 Gunakan kelipatan 1000 untuk harga yang lebih rapi</small>
                </div>

                <!-- Stock -->
                <div class="form-group">
                    <label for="stock">Stok Awal <span class="required">*</span></label>
                    <input type="number" 
                           id="stock" 
                           name="stock" 
                           placeholder="0"
                           min="0"
                           required>
                    <small>📦 Jumlah stok produk yang tersedia</small>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="category">Kategori <span class="required">*</span></label>
                    <select id="category" name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($data['categories'] as $category): ?>
                            <option value="<?= $category['category_id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Button Group -->
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="cancelAdd()">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="saveBtn" onclick="confirmAdd()">
                        Tambah Produk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Tambah Produk</h2>
            </div>
            <div class="modal-body">
                <p>Apakah data produk berikut sudah benar?</p>
                <div id="productInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Ya, Tambahkan</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-message" id="toastMessage"></div>
    </div>

    <script>
        const CSRF_TOKEN = <?= json_encode($data['_token']) ?>;
    </script>
    <script src="/js/addPage.js"></script>
</body>
</html>
