// Update Product Page JavaScript
// Data dari PHP sudah tersedia di global scope: PRODUCT_ID, CSRF_TOKEN, originalData

let newImageFile = null;

// Handle image selection
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
    
    // Preview new image
    const reader = new FileReader();
    reader.onload = function(event) {
        document.getElementById('productImage').src = event.target.result;
        document.getElementById('newImageInfo').classList.add('show');
        document.getElementById('newImageName').textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
    };
    reader.readAsDataURL(file);
    
    newImageFile = file;
});

// Cancel edit
function cancelEdit() {
    if (hasChanges()) {
        if (confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin membatalkan?')) {
            window.location.href = '/seller/products';
        }
    } else {
        window.location.href = '/seller/products';
    }
}

// Check if there are changes
function hasChanges() {
    const currentName = document.getElementById('product_name').value.trim();
    const currentDesc = document.getElementById('description').value.trim();
    const currentPrice = parseInt(document.getElementById('price').value);
    const currentStock = parseInt(document.getElementById('stock').value);
    
    return currentName !== originalData.product_name ||
           currentDesc !== originalData.description ||
           currentPrice !== parseInt(originalData.price) ||
           currentStock !== parseInt(originalData.stock) ||
           newImageFile !== null;
}

// Confirm save
function confirmSave() {
    // Validate required fields
    const productName = document.getElementById('product_name').value.trim();
    const price = parseInt(document.getElementById('price').value);
    const stock = parseInt(document.getElementById('stock').value);
    
    if (!productName) {
        showToast('Nama produk harus diisi', 'error');
        return;
    }
    
    if (price <= 0) {
        showToast('Harga harus lebih dari 0', 'error');
        return;
    }
    
    if (stock < 0) {
        showToast('Stok tidak boleh negatif', 'error');
        return;
    }
    
    // Build changes list
    const changes = [];
    
    if (productName !== originalData.product_name) {
        changes.push(`<div class="change-item"><strong>Nama Produk:</strong> "${originalData.product_name}" → "${productName}"</div>`);
    }
    
    const currentDesc = document.getElementById('description').value.trim();
    if (currentDesc !== originalData.description) {
        changes.push(`<div class="change-item"><strong>Deskripsi:</strong> Diubah</div>`);
    }
    
    if (price !== parseInt(originalData.price)) {
        changes.push(`<div class="change-item"><strong>Harga:</strong> Rp ${parseInt(originalData.price).toLocaleString('id-ID')} → Rp ${price.toLocaleString('id-ID')}</div>`);
    }
    
    if (stock !== parseInt(originalData.stock)) {
        changes.push(`<div class="change-item"><strong>Stok:</strong> ${parseInt(originalData.stock).toLocaleString('id-ID')} → ${stock.toLocaleString('id-ID')}</div>`);
    }
    
    if (newImageFile) {
        changes.push(`<div class="change-item"><strong>Foto Produk:</strong> Diganti dengan "${newImageFile.name}"</div>`);
    }
    
    if (changes.length === 0) {
        showToast('Tidak ada perubahan yang perlu disimpan', 'error');
        return;
    }
    
    // Show changes in modal
    document.getElementById('changesList').innerHTML = changes.join('');
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
        const formData = new FormData(document.getElementById('editProductForm'));
        
        const response = await fetch('/seller/products/update/' + PRODUCT_ID, {
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
            saveBtn.textContent = 'Simpan Perubahan';
        }
        
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loadingOverlay').classList.remove('show');
        showToast('Terjadi kesalahan saat menyimpan produk', 'error');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Simpan Perubahan';
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
