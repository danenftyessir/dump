<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - WBD Store</title>
    <link rel="stylesheet" href="/css/addUpdatePage.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Produk</h1>
            <p>Perbarui informasi produk Anda</p>
        </div>

        <div class="form-container">
            <form id="editProductForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($data['_token']) ?>">
                
                <!-- Image Section -->
                <div class="image-section">
                    <div class="current-image">
                        <label>Foto Produk Saat Ini</label>
                        <div class="image-preview">
                            <img id="productImage" 
                                 src="/<?= htmlspecialchars($data['product']['main_image_path']) ?>" 
                                 alt="<?= htmlspecialchars($data['product']['product_name']) ?>">
                        </div>
                        <button type="button" class="change-image-btn" onclick="document.getElementById('imageInput').click()">
                            📷 Ganti Foto
                        </button>
                        <input type="file" 
                               id="imageInput" 
                               name="main_image" 
                               accept="image/jpeg,image/jpg,image/png,image/gif"
                               class="image-upload-input">
                    </div>
                    
                    <div class="new-image-info" id="newImageInfo">
                        <p>✓ Foto baru dipilih</p>
                        <small id="newImageName"></small>
                    </div>
                </div>

                <!-- Product Name -->
                <div class="form-group">
                    <label for="product_name">Nama Produk <span class="required">*</span></label>
                    <input type="text" 
                           id="product_name" 
                           name="product_name" 
                           value="<?= htmlspecialchars($data['product']['product_name']) ?>"
                           required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" 
                              name="description"><?= htmlspecialchars($data['product']['description'] ?? '') ?></textarea>
                </div>

                <!-- Price -->
                <div class="form-group">
                    <label for="price">Harga (Rp) <span class="required">*</span></label>
                    <input type="number" 
                           id="price" 
                           name="price" 
                           value="<?= htmlspecialchars($data['product']['price']) ?>"
                           min="0" 
                           step="1000"
                           required>
                </div>

                <!-- Stock -->
                <div class="form-group">
                    <label for="stock">Stok <span class="required">*</span></label>
                    <input type="number" 
                           id="stock" 
                           name="stock" 
                           value="<?= htmlspecialchars($data['product']['stock']) ?>"
                           min="0"
                           required>
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                        ℹ️ Stok akan diganti dengan nilai baru (bukan ditambah)
                    </small>
                </div>

                <!-- Button Group -->
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="saveBtn" onclick="confirmSave()">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Perubahan</h2>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menyimpan perubahan berikut?</p>
                <div id="changesList"></div>
                <p style="margin-top: 15px; font-size: 13px; color: #666;">
                    ℹ️ <strong>Catatan:</strong> Perubahan harga tidak akan mempengaruhi transaksi yang sudah ada.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Ya, Simpan</button>
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
        const PRODUCT_ID = <?= json_encode($data['product']['product_id']) ?>;
        const CSRF_TOKEN = <?= json_encode($data['_token']) ?>;
        const originalData = {
            product_name: <?= json_encode($data['product']['product_name']) ?>,
            description: <?= json_encode($data['product']['description'] ?? '') ?>,
            price: <?= json_encode($data['product']['price']) ?>,
            stock: <?= json_encode($data['product']['stock']) ?>,
            main_image_path: <?= json_encode($data['product']['main_image_path']) ?>
        };
    </script>
    <script src="/js/updatePage.js"></script>
</body>
</html>
