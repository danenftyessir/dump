// =================================================================
// SELLER PRODUCT FORM - JAVASCRIPT (WITH DEBUG)
// file ini menangani form tambah produk seller
// =================================================================

// global variables
let quill;
let selectedImage = null;

// =================================================================
// INISIALISASI
// =================================================================

document.addEventListener('DOMContentLoaded', function() {
    initQuillEditor();
    initFormHandlers();
    initImageUpload();
    initCharCounters();
    fetchCsrfToken();
});

// =================================================================
// INISIALISASI QUILL EDITOR
// =================================================================

function initQuillEditor() {
    const editorElement = document.getElementById('quillEditor');
    if (!editorElement) {
        console.error('quill editor element not found');
        return;
    }

    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['clean']
    ];

    quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'tulis deskripsi produk...',
        modules: {
            toolbar: toolbarOptions
        }
    });

    // update hidden input saat konten berubah
    quill.on('text-change', function() {
        const html = quill.root.innerHTML;
        const descElement = document.getElementById('description');
        if (descElement) {
            descElement.value = html;
        }
        updateDescCharCount();
    });
}

// =================================================================
// CHARACTER COUNTERS
// =================================================================

function initCharCounters() {
    // counter untuk nama produk
    const nameInput = document.getElementById('productName');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            const count = this.value.length;
            const countElement = document.getElementById('nameCharCount');
            if (countElement) {
                countElement.textContent = count;
            }
        });
    }
}

function updateDescCharCount() {
    if (!quill) return;
    
    const text = quill.getText();
    const count = text.trim().length;
    const countElement = document.getElementById('descCharCount');
    if (countElement) {
        countElement.textContent = count;
    }
}

// =================================================================
// FORM HANDLERS
// =================================================================

function initFormHandlers() {
    const form = document.getElementById('productForm');
    if (!form) {
        console.error('form element not found');
        return;
    }
    
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
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    // show loading
    showLoading();

    try {
        // prepare form data
        const formData = new FormData();
        
        // tambahkan data form - dengan null check
        const productNameElement = document.getElementById('productName');
        const descriptionElement = document.getElementById('description');
        const priceElement = document.getElementById('price');
        const stockElement = document.getElementById('stock');
        const csrfTokenElement = document.getElementById('csrfToken');

        if (!productNameElement || !descriptionElement || !priceElement || !stockElement || !csrfTokenElement) {
            throw new Error('form elements tidak lengkap');
        }

        // debug: log csrf token
        console.log('csrf token:', csrfTokenElement.value);

        formData.append('product_name', productNameElement.value.trim());
        formData.append('description', descriptionElement.value);
        formData.append('price', priceElement.value);
        formData.append('stock', stockElement.value);
        formData.append('csrf_token', csrfTokenElement.value);

        // tambahkan kategori yang dipilih
        const selectedCategories = document.querySelectorAll('input[name="category_ids[]"]:checked');
        console.log('selected categories:', selectedCategories.length);
        selectedCategories.forEach(checkbox => {
            formData.append('category_ids[]', checkbox.value);
        });

        // tambahkan image jika ada
        const imageInput = document.getElementById('mainImage');
        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            console.log('image file:', imageInput.files[0].name, imageInput.files[0].size);
            formData.append('main_image', imageInput.files[0]);
        } else {
            console.log('no image selected');
        }

        // debug: log request
        console.log('sending request to /api/seller/products');

        // kirim data via ajax
        const response = await fetch('/api/seller/products', {
            method: 'POST',
            body: formData
        });

        // debug: log response
        console.log('response status:', response.status);
        console.log('response headers:', response.headers.get('content-type'));

        // cek content type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // response bukan json, kemungkinan error page atau redirect
            const textResponse = await response.text();
            console.error('response bukan json:', textResponse.substring(0, 500));
            throw new Error('server mengembalikan response tidak valid. cek console untuk detail.');
        }

        const data = await response.json();
        console.log('response data:', data);

        if (data.success) {
            showToast('Produk Berhasil Ditambahkan');
            // redirect ke halaman product management setelah 1.5 detik
            setTimeout(() => {
                window.location.href = '/seller/products';
            }, 1500);
        } else {
            throw new Error(data.message || 'gagal menyimpan produk');
        }

    } catch (error) {
        console.error('error:', error);
        hideLoading();
        if (submitBtn) {
            submitBtn.disabled = false;
        }
        showToast(error.message || 'terjadi kesalahan. silakan coba lagi.', 'error');
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
    const productNameElement = document.getElementById('productName');
    if (!productNameElement) {
        console.error('productName element not found');
        return false;
    }

    const productName = productNameElement.value.trim();
    if (productName.length === 0) {
        showError('nameError', 'nama produk wajib diisi');
        isValid = false;
    } else if (productName.length > 200) {
        showError('nameError', 'nama produk maksimal 200 karakter');
        isValid = false;
    }

    // validasi deskripsi
    if (!quill) {
        showError('descError', 'editor deskripsi belum siap');
        return false;
    }

    const description = quill.getText().trim();
    if (description.length === 0) {
        showError('descError', 'deskripsi produk wajib diisi');
        isValid = false;
    } else if (description.length > 1000) {
        showError('descError', 'deskripsi produk maksimal 1000 karakter');
        isValid = false;
    }

    // validasi harga
    const priceElement = document.getElementById('price');
    if (!priceElement) {
        console.error('price element not found');
        return false;
    }

    const price = parseInt(priceElement.value);
    if (isNaN(price) || price < 1000) {
        showError('priceError', 'harga minimal Rp 1.000');
        isValid = false;
    }

    // validasi stok
    const stockElement = document.getElementById('stock');
    if (!stockElement) {
        console.error('stock element not found');
        return false;
    }

    const stock = parseInt(stockElement.value);
    if (isNaN(stock) || stock < 0) {
        showError('stockError', 'stok tidak valid');
        isValid = false;
    }

    // validasi kategori
    const selectedCategories = document.querySelectorAll('input[name="category_ids[]"]:checked');
    if (selectedCategories.length === 0) {
        showError('categoryError', 'pilih minimal satu kategori');
        isValid = false;
    }

    // validasi image (optional - hanya validasi jika ada file)
    const imageInput = document.getElementById('mainImage');
    if (imageInput && imageInput.files && imageInput.files.length > 0) {
        const file = imageInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const maxSize = 2 * 1024 * 1024; // 2mb

        if (!allowedTypes.includes(file.type)) {
            showError('imageError', 'format gambar harus jpg, jpeg, png, atau webp');
            isValid = false;
        } else if (file.size > maxSize) {
            showError('imageError', 'ukuran gambar maksimal 2mb');
            isValid = false;
        }
    }

    return isValid;
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

// =================================================================
// IMAGE UPLOAD
// =================================================================

function initImageUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('mainImage');

    // check if elements exist
    if (!uploadArea || !imageInput) {
        console.warn('upload elements not found - image upload disabled');
        return;
    }

    // click handler untuk upload area
    uploadArea.addEventListener('click', function(e) {
        // jangan trigger jika user klik tombol ganti foto
        if (!e.target.closest('.btn-change-image')) {
            imageInput.click();
        }
    });

    // drag & drop handlers
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // set files ke input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            imageInput.files = dataTransfer.files;
            
            handleImageSelect(files[0]);
        }
    });

    // file input change handler
    imageInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            handleImageSelect(this.files[0]);
        }
    });
}

function handleImageSelect(file) {
    // validasi file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showError('imageError', 'format gambar harus jpg, jpeg, png, atau webp');
        return;
    }

    // validasi file size (2mb)
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        showError('imageError', 'ukuran gambar maksimal 2mb');
        return;
    }

    // preview image
    const reader = new FileReader();
    reader.onload = function(e) {
        selectedImage = e.target.result;
        showImagePreview(e.target.result);
    };
    reader.readAsDataURL(file);
}

function showImagePreview(imageSrc) {
    const uploadArea = document.getElementById('uploadArea');
    if (!uploadArea) return;

    uploadArea.innerHTML = `
        <div class="image-preview">
            <img src="${imageSrc}" alt="preview">
            <div class="preview-overlay">
                <button type="button" class="btn-change-image" onclick="changeImage(event)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <span>ganti foto</span>
                </button>
            </div>
        </div>
    `;
}

function changeImage(e) {
    e.stopPropagation();
    const imageInput = document.getElementById('mainImage');
    if (imageInput) {
        imageInput.click();
    }
}

// =================================================================
// CSRF TOKEN
// =================================================================

async function fetchCsrfToken() {
    try {
        console.log('fetching csrf token...');
        const response = await fetch('/api/csrf-token');
        
        console.log('csrf token response status:', response.status);
        
        const data = await response.json();
        console.log('csrf token data:', data);
        
        if (data.success && data.data && data.data.token) {
            const csrfTokenElement = document.getElementById('csrfToken');
            if (csrfTokenElement) {
                csrfTokenElement.value = data.data.token;
                console.log('csrf token set:', data.data.token.substring(0, 10) + '...');
            }
        } else {
            console.error('gagal mendapatkan csrf token:', data);
        }
    } catch (error) {
        console.error('error fetching csrf token:', error);
    }
}

// =================================================================
// LOADING & TOAST
// =================================================================

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    if (toast && toastMessage) {
        toastMessage.textContent = message;
        toast.className = 'toast show ' + type;
        toast.style.display = 'block';

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }, 3000);
    }
}