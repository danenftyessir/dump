// quill editor instance
let quill;

// elements
const productForm = document.getElementById('productForm');
const productImage = document.getElementById('productImage');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const imagePreview = document.getElementById('imagePreview');
const changeImageBtn = document.getElementById('changeImageBtn');
const productName = document.getElementById('productName');
const nameCounter = document.getElementById('nameCounter');
const descCounter = document.getElementById('descCounter');
const price = document.getElementById('price');
const stock = document.getElementById('stock');
const submitBtn = document.getElementById('submitBtn');
const submitText = document.getElementById('submitText');
const submitLoading = document.getElementById('submitLoading');
const toast = document.getElementById('toast');

// error elements
const imageError = document.getElementById('imageError');
const nameError = document.getElementById('nameError');
const descError = document.getElementById('descError');
const priceError = document.getElementById('priceError');
const stockError = document.getElementById('stockError');
const categoryError = document.getElementById('categoryError');

// inisialisasi Quill editor
document.addEventListener('DOMContentLoaded', function() {
    quill = new Quill('#quillEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'header': [1, 2, 3, false] }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Tuliskan deskripsi produk...'
    });
    
    // load existing description jika edit mode
    if (typeof isEditMode !== 'undefined' && isEditMode && typeof existingDescription !== 'undefined') {
        quill.root.innerHTML = existingDescription;
        updateDescCounter();
    }
    
    // update counter saat konten berubah
    quill.on('text-change', function() {
        updateDescCounter();
    });
});

// handle image upload
uploadPlaceholder.addEventListener('click', function() {
    productImage.click();
});

imagePreview.addEventListener('click', function() {
    productImage.click();
});

changeImageBtn.addEventListener('click', function() {
    productImage.click();
});

productImage.addEventListener('change', function(e) {
    const file = e.target.files[0];
    
    if (!file) return;
    
    // validasi ukuran file
    if (file.size > 2 * 1024 * 1024) {
        imageError.textContent = 'Ukuran file maksimal 2MB';
        productImage.value = '';
        return;
    }
    
    // validasi tipe file
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        imageError.textContent = 'Tipe file harus JPG, PNG, atau WEBP';
        productImage.value = '';
        return;
    }
    
    // clear error
    imageError.textContent = '';
    
    // preview image
    const reader = new FileReader();
    reader.onload = function(e) {
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'block';
        uploadPlaceholder.style.display = 'none';
        changeImageBtn.style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// character counter untuk nama produk
productName.addEventListener('input', function() {
    const length = this.value.length;
    nameCounter.textContent = `${length}/200`;
    
    if (length > 200) {
        nameCounter.style.color = '#e74c3c';
    } else {
        nameCounter.style.color = '#6d7588';
    }
});

// update description counter
function updateDescCounter() {
    const text = quill.getText();
    const length = text.trim().length;
    descCounter.textContent = `${length}/5000`;
    
    if (length > 5000) {
        descCounter.style.color = '#e74c3c';
    } else {
        descCounter.style.color = '#6d7588';
    }
}

// form submission
productForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // clear semua error
    clearErrors();
    
    // validasi form
    if (!validateForm()) {
        return;
    }
    
    // disable submit button
    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitLoading.style.display = 'inline-block';
    
    // ambil data dari form
    const formData = new FormData();
    
    // check jika edit mode
    const isEdit = typeof isEditMode !== 'undefined' && isEditMode;
    
    if (isEdit) {
        const productId = document.getElementById('productId').value;
        formData.append('product_id', productId);
    }
    
    formData.append('product_name', productName.value.trim());
    formData.append('description', quill.root.innerHTML);
    formData.append('price', price.value);
    formData.append('stock', stock.value);
    
    // ambil kategori yang dipilih
    const categoryCheckboxes = document.querySelectorAll('input[name="category_ids[]"]:checked');
    categoryCheckboxes.forEach(checkbox => {
        formData.append('category_ids[]', checkbox.value);
    });
    
    // tambahkan foto jika ada
    if (productImage.files.length > 0) {
        formData.append('product_image', productImage.files[0]);
    }
    
    // tentukan URL dan method
    const url = isEdit ? '/api/seller/products/update' : '/api/seller/products';
    const method = 'POST';
    
    // submit menggunakan XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    xhr.onload = function() {
        submitBtn.disabled = false;
        submitText.style.display = 'inline';
        submitLoading.style.display = 'none';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    const message = isEdit ? 'Produk Berhasil Diupdate' : 'Produk Berhasil Ditambahkan';
                    showToast(message, 'success');
                    
                    setTimeout(() => {
                        window.location.href = '/seller/products';
                    }, 1500);
                } else {
                    showToast(response.error || 'Gagal Menyimpan Produk', 'error');
                }
            } catch (error) {
                console.error('Error parsing response:', error);
                showToast('Terjadi Kesalahan', 'error');
            }
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                showToast(response.error || 'Gagal Menyimpan Produk', 'error');
            } catch (error) {
                showToast('Gagal Menyimpan Produk', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        submitBtn.disabled = false;
        submitText.style.display = 'inline';
        submitLoading.style.display = 'none';
        showToast('Terjadi Kesalahan Jaringan', 'error');
    };
    
    xhr.send(formData);
});

// validasi form
function validateForm() {
    let isValid = true;
    
    // validasi foto (hanya untuk add mode)
    const isEdit = typeof isEditMode !== 'undefined' && isEditMode;
    if (!isEdit && productImage.files.length === 0) {
        imageError.textContent = 'Foto produk wajib diupload';
        isValid = false;
    }
    
    // validasi nama produk
    if (productName.value.trim() === '') {
        nameError.textContent = 'Nama produk tidak boleh kosong';
        isValid = false;
    } else if (productName.value.length > 200) {
        nameError.textContent = 'Nama produk maksimal 200 karakter';
        isValid = false;
    }
    
    // validasi deskripsi
    const descText = quill.getText().trim();
    if (descText === '') {
        descError.textContent = 'Deskripsi tidak boleh kosong';
        isValid = false;
    } else if (descText.length > 5000) {
        descError.textContent = 'Deskripsi maksimal 5000 karakter';
        isValid = false;
    }
    
    // validasi harga
    if (price.value === '' || price.value < 1000) {
        priceError.textContent = 'Harga minimal Rp 1.000';
        isValid = false;
    }
    
    // validasi stok
    if (stock.value === '' || stock.value < 0) {
        stockError.textContent = 'Stok minimal 0';
        isValid = false;
    }
    
    // validasi kategori
    const categoryCheckboxes = document.querySelectorAll('input[name="category_ids[]"]:checked');
    if (categoryCheckboxes.length === 0) {
        categoryError.textContent = 'Pilih minimal 1 kategori';
        isValid = false;
    }
    
    return isValid;
}

// clear semua error messages
function clearErrors() {
    imageError.textContent = '';
    nameError.textContent = '';
    descError.textContent = '';
    priceError.textContent = '';
    stockError.textContent = '';
    categoryError.textContent = '';
}

// show toast notification
function showToast(message, type = 'success') {
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// set initial character counters
if (productName.value) {
    nameCounter.textContent = `${productName.value.length}/200`;
}