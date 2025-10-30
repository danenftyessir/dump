// =================================================================
// SELLER PRODUCT EDIT FORM - JAVASCRIPT
// File ini menangani form edit produk seller
// =================================================================

// global variables
let quill;
let selectedImage = null;
let isImageChanged = false;

// =================================================================
// INISIALISASI
// =================================================================

document.addEventListener('DOMContentLoaded', function() {
    initQuillEditor();
    initFormHandlers();
    initImageUpload();
    initCharCounters();
    fetchCsrfToken();
    loadInitialData();
});

// =================================================================
// LOAD INITIAL DATA
// =================================================================

function loadInitialData() {
    // set initial character count untuk nama produk
    const nameInput = document.getElementById('productName');
    if (nameInput.value) {
        document.getElementById('nameCharCount').textContent = nameInput.value.length;
    }

    // load description ke quill editor
    const descriptionInput = document.getElementById('description');
    if (descriptionInput.value) {
        quill.root.innerHTML = descriptionInput.value;
        updateDescCharCount();
    }
}

// =================================================================
// INISIALISASI QUILL EDITOR
// =================================================================

function initQuillEditor() {
    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['clean']
    ];

    quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Tulis deskripsi produk...',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // update hidden input saat konten berubah
    quill.on('text-change', function() {
        const html = quill.root.innerHTML;
        document.getElementById('description').value = html;
        updateDescCharCount();
    });
}

// =================================================================
// CHARACTER COUNTERS
// =================================================================

function initCharCounters() {
    // counter untuk nama produk
    const nameInput = document.getElementById('productName');
    nameInput.addEventListener('input', function() {
        const count = this.value.length;
        document.getElementById('nameCharCount').textContent = count;
    });
}

function updateDescCharCount() {
    const text = quill.getText();
    const count = text.trim().length;
    document.getElementById('descCharCount').textContent = count;
}

// =================================================================
// FORM HANDLERS
// =================================================================

function initFormHandlers() {
    const form = document.getElementById('productForm');
    form.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(e) {
    e.preventDefault();

    // validasi form
    if (!validateForm()) {
        return;
    }

    // disable submit button
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;

    // show loading
    showLoading();

    try {
        // prepare form data
        const formData = new FormData();
        
        // tambahkan data form
        formData.append('product_id', document.getElementById('productId').value);
        formData.append('product_name', document.getElementById('productName').value.trim());
        formData.append('description', document.getElementById('description').value);
        formData.append('price', document.getElementById('price').value);
        formData.append('stock', document.getElementById('stock').value);
        formData.append('csrf_token', document.getElementById('csrfToken').value);

        // tambahkan kategori yang dipilih
        const selectedCategories = document.querySelectorAll('input[name="category_ids[]"]:checked');
        selectedCategories.forEach(checkbox => {
            formData.append('category_ids[]', checkbox.value);
        });

        // tambahkan image jika ada perubahan
        const imageInput = document.getElementById('mainImage');
        if (imageInput.files.length > 0) {
            formData.append('main_image', imageInput.files[0]);
        }

        // kirim data via AJAX
        const response = await fetch('/api/seller/products/update', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast('Produk Berhasil Diperbarui');
            // redirect ke halaman product management setelah 1.5 detik
            setTimeout(() => {
                window.location.href = '/seller/products';
            }, 1500);
        } else {
            throw new Error(data.error || 'Gagal memperbarui produk');
        }

    } catch (error) {
        console.error('Error:', error);
        hideLoading();
        submitBtn.disabled = false;
        showToast(error.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
    }
}

// =================================================================
// VALIDASI FORM
// =================================================================

function validateForm() {
    let isValid = true;

    // reset error messages
    document.querySelectorAll('.error-message').forEach(el => {
        el.classList.remove('show');
        el.textContent = '';
    });

    // validasi nama produk
    const productName = document.getElementById('productName').value.trim();
    if (productName.length === 0) {
        showError('nameError', 'Nama produk wajib diisi');
        isValid = false;
    } else if (productName.length > 200) {
        showError('nameError', 'Nama produk maksimal 200 karakter');
        isValid = false;
    }

    // validasi deskripsi
    const description = quill.getText().trim();
    if (description.length === 0) {
        showError('descError', 'Deskripsi produk wajib diisi');
        isValid = false;
    } else if (description.length > 1000) {
        showError('descError', 'Deskripsi produk maksimal 1000 karakter');
        isValid = false;
    }

    // validasi harga
    const price = parseInt(document.getElementById('price').value);
    if (isNaN(price) || price < 1000) {
        showError('priceError', 'Harga minimal Rp 1.000');
        isValid = false;
    }

    // validasi stok
    const stock = parseInt(document.getElementById('stock').value);
    if (isNaN(stock) || stock < 0) {
        showError('stockError', 'Stok tidak valid');
        isValid = false;
    }

    // validasi kategori
    const selectedCategories = document.querySelectorAll('input[name="category_ids[]"]:checked');
    if (selectedCategories.length === 0) {
        showError('categoryError', 'Pilih minimal satu kategori');
        isValid = false;
    }

    return isValid;
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    errorElement.textContent = message;
    errorElement.classList.add('show');
}

// =================================================================
// IMAGE UPLOAD
// =================================================================

function initImageUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('mainImage');
    const changeImageBtn = document.getElementById('changeImageBtn');

    // click handler untuk upload area
    uploadArea.addEventListener('click', function(e) {
        // jangan trigger jika klik tombol ganti foto
        if (!e.target.closest('.btn-change-image')) {
            imageInput.click();
        }
    });

    // change image button handler
    if (changeImageBtn) {
        changeImageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            imageInput.click();
        });
    }

    // file input change handler
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleImageSelect(file);
        }
    });

    // drag and drop handlers
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#42b549';
        this.style.background = '#f0fdf4';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#e8eaed';
        this.style.background = '#ffffff';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#e8eaed';
        this.style.background = '#ffffff';
        
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            // set file to input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            imageInput.files = dataTransfer.files;
            
            handleImageSelect(file);
        }
    });
}

function handleImageSelect(file) {
    // validasi tipe file
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showError('imageError', 'Format file tidak valid. Gunakan JPG, PNG, atau WEBP');
        return;
    }

    // validasi ukuran file (max 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSize) {
        showError('imageError', 'Ukuran file maksimal 2MB');
        return;
    }

    // clear error
    document.getElementById('imageError').classList.remove('show');

    // set flag bahwa image berubah
    isImageChanged = true;

    // preview image
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewImage = document.getElementById('previewImage');
        previewImage.src = e.target.result;
        
        // show preview, hide placeholder
        document.getElementById('uploadPlaceholder').style.display = 'none';
        document.getElementById('imagePreview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

// =================================================================
// CSRF TOKEN
// =================================================================

async function fetchCsrfToken() {
    try {
        const response = await fetch('/api/csrf-token');
        const data = await response.json();
        if (data.success && data.data.token) {
            document.getElementById('csrfToken').value = data.data.token;
        }
    } catch (error) {
        console.error('Error fetching CSRF token:', error);
    }
}

// =================================================================
// UI HELPERS
// =================================================================

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = toast.querySelector('.toast-icon');

    toastMessage.textContent = message;

    // update icon berdasarkan tipe
    if (type === 'error') {
        toastIcon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        `;
    } else {
        toastIcon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#42b549" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        `;
    }

    toast.classList.add('show');

    // hide setelah 3 detik
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}