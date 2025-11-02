// Add Product Page JavaScript
// CSRF_TOKEN sudah tersedia dari global scope

let selectedImageFile = null;

// Handle image input change
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (!file) return;
    
    // Validate file size (max 2MB)
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        showToast('Ukuran file maksimal 2MB', 'error');
        e.target.value = '';
        return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Format file harus JPG, PNG, atau GIF', 'error');
        e.target.value = '';
        return;
    }
    
    // Preview image
    const reader = new FileReader();
    reader.onload = function(event) {
        document.getElementById('previewImg').src = event.target.result;
        document.getElementById('uploadPlaceholder').style.display = 'none';
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('imageInfo').classList.add('show');
        document.getElementById('imageName').textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
    };
    reader.readAsDataURL(file);
    
    selectedImageFile = file;
});

// Remove image
function removeImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('uploadPlaceholder').style.display = 'block';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('imageInfo').classList.remove('show');
    selectedImageFile = null;
}

// Cancel add
function cancelAdd() {
    if (hasInput()) {
        if (confirm('Anda memiliki data yang belum disimpan. Yakin ingin membatalkan?')) {
            window.location.href = '/seller/products';
        }
    } else {
        window.location.href = '/seller/products';
    }
}

// Check if form has input
function hasInput() {
    const productName = document.getElementById('product_name').value.trim();
    const description = document.getElementById('description').value.trim();
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock').value;
    const category = document.getElementById('category').value;
    
    return productName || description || price || stock || category || selectedImageFile;
}

// Confirm add
function confirmAdd() {
    // Validate required fields
    const productName = document.getElementById('product_name').value.trim();
    const price = parseInt(document.getElementById('price').value);
    const stock = parseInt(document.getElementById('stock').value);
    const categoryId = document.getElementById('category').value;
    const imageFile = document.getElementById('imageInput').files[0];
    
    if (!productName) {
        showToast('Nama produk harus diisi', 'error');
        return;
    }
    
    if (!price || price <= 0) {
        showToast('Harga harus lebih dari 0', 'error');
        return;
    }
    
    if (stock < 0) {
        showToast('Stok tidak boleh negatif', 'error');
        return;
    }
    
    if (!categoryId) {
        showToast('Kategori harus dipilih', 'error');
        return;
    }
    
    if (!imageFile) {
        showToast('Foto produk harus diupload', 'error');
        return;
    }
    
    // Build product info
    const description = document.getElementById('description').value.trim();
    const categorySelect = document.getElementById('category');
    const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
    
    const productInfo = `
        <div class="change-item"><strong>Nama Produk:</strong> ${productName}</div>
        <div class="change-item"><strong>Deskripsi:</strong> ${description || '-'}</div>
        <div class="change-item"><strong>Harga:</strong> Rp ${price.toLocaleString('id-ID')}</div>
        <div class="change-item"><strong>Stok:</strong> ${stock.toLocaleString('id-ID')} unit</div>
        <div class="change-item"><strong>Kategori:</strong> ${categoryName}</div>
        <div class="change-item"><strong>Foto:</strong> ${imageFile.name}</div>
    `;
    
    // Show confirmation modal
    document.getElementById('productInfo').innerHTML = productInfo;
    document.getElementById('confirmModal').classList.add('show');
}

// Close modal
function closeModal() {
    document.getElementById('confirmModal').classList.remove('show');
}

// Save product
async function saveProduct() {
    closeModal();
    
    // Disable save button and show loading
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Menyimpan...';
    document.getElementById('loadingOverlay').classList.add('show');
    
    try {
        const formData = new FormData(document.getElementById('addProductForm'));
        
        const response = await fetch('/seller/products/add', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: formData
        });
        
        const result = await response.json();
        
        document.getElementById('loadingOverlay').classList.remove('show');
        
        if (result.success) {
            showToast(result.message, 'success');
            
            // Redirect after 1.5 seconds
            setTimeout(() => {
                window.location.href = '/seller/products';
            }, 1500);
        } else {
            showToast(result.message, 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Tambah Produk';
        }
        
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loadingOverlay').classList.remove('show');
        showToast('Terjadi kesalahan saat menyimpan produk', 'error');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Tambah Produk';
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.className = 'toast show ' + type;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmModal');
    if (event.target === modal) {
        closeModal();
    }
};
