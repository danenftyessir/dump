// =================================================================
// SELLER PRODUCT FORM - JAVASCRIPT
// file ini menangani form tambah produk seller
// =================================================================

// global variables
let quill;
let selectedImage = null;

// =================================================================
// INISIALISASI
// =================================================================

document.addEventListener('DOMContentLoaded', function() {
    // tunggu sampai Quill library loaded
    waitForQuill().then(() => {
        initQuillEditor();
        initFormHandlers();
        initImageUpload();
        initCharCounters();
    }).catch(err => {
        alert('gagal memuat editor. silakan refresh halaman.');
    });
});

// fungsi helper untuk menunggu quill library
function waitForQuill() {
    return new Promise((resolve, reject) => {
        // jika quill sudah tersedia, langsung resolve
        if (typeof Quill !== 'undefined') {
            resolve();
            return;
        }

        // tunggu maksimal 10 detik
        let attempts = 0;
        const maxAttempts = 100; // 100 x 100ms = 10 detik

        const checkQuill = setInterval(() => {
            attempts++;

            if (typeof Quill !== 'undefined') {
                clearInterval(checkQuill);
                resolve();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkQuill);
                reject(new Error('quill library tidak dimuat dalam waktu yang ditentukan. periksa koneksi internet atau coba refresh halaman.'));
            }
        }, 100);
    });
}

// =================================================================
// INISIALISASI QUILL EDITOR
// =================================================================

function initQuillEditor() {
    const editorElement = document.getElementById('quillEditor');
    if (!editorElement) {
        return;
    }

    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['clean']
    ];

    try {
        quill = new Quill('#quillEditor', {
            theme: 'snow',
            placeholder: 'Tulis deskripsi produk di sini...',
            modules: {
                toolbar: toolbarOptions
            }
        });

        // Tambahkan aria-labels untuk accessibility
        addQuillAccessibilityLabels();

        // update hidden input saat konten berubah
        quill.on('text-change', function() {
            const html = quill.root.innerHTML;
            const descElement = document.getElementById('description');
            if (descElement) {
                descElement.value = html;
            }
            updateDescCharCount();
        });

        // set focus ke editor untuk memastikan bisa langsung digunakan
        setTimeout(() => {
            if (quill) {
                quill.focus();
            }
        }, 100);

    } catch (error) {
        alert('gagal menginisialisasi editor deskripsi. silakan refresh halaman.');
    }
}

// Menambahkan aria-labels untuk Quill toolbar buttons
function addQuillAccessibilityLabels() {
    // Tunggu DOM update setelah Quill diinisialisasi
    setTimeout(() => {
        const toolbar = document.querySelector('.ql-toolbar');
        if (!toolbar) return;

        // Bold button
        const boldBtn = toolbar.querySelector('.ql-bold');
        if (boldBtn) boldBtn.setAttribute('aria-label', 'Tebal (Bold)');

        // Italic button
        const italicBtn = toolbar.querySelector('.ql-italic');
        if (italicBtn) italicBtn.setAttribute('aria-label', 'Miring (Italic)');

        // Underline button
        const underlineBtn = toolbar.querySelector('.ql-underline');
        if (underlineBtn) underlineBtn.setAttribute('aria-label', 'Garis Bawah (Underline)');

        // List buttons
        const listBtns = toolbar.querySelectorAll('.ql-list');
        if (listBtns[0]) listBtns[0].setAttribute('aria-label', 'Daftar Bernomor (Ordered List)');
        if (listBtns[1]) listBtns[1].setAttribute('aria-label', 'Daftar Bullet (Bullet List)');

        // Clean formatting button
        const cleanBtn = toolbar.querySelector('.ql-clean');
        if (cleanBtn) cleanBtn.setAttribute('aria-label', 'Hapus Format (Clear Formatting)');
    }, 100);
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
        const csrfTokenValue = csrfTokenElement.value;
        console.log('csrf token value:', csrfTokenValue);
        console.log('csrf token length:', csrfTokenValue.length);

        if (!csrfTokenValue || csrfTokenValue.length === 0) {
            throw new Error('csrf token kosong. silakan refresh halaman.');
        }

        formData.append('product_name', productNameElement.value.trim());
        formData.append('description', descriptionElement.value);
        formData.append('price', priceElement.value);
        formData.append('stock', stockElement.value);
        formData.append('csrf_token', csrfTokenValue);

        // tambahkan kategori yang dipilih
        const selectedCategories = document.querySelectorAll('input[name="category_ids[]"]:checked');
        console.log('selected categories:', selectedCategories.length);
        selectedCategories.forEach(checkbox => {
            formData.append('category_ids[]', checkbox.value);
        });

        // tambahkan image jika ada
        const imageInput = document.getElementById('mainImage');
        if (imageInput && imageInput.files && imageInput.files.length > 0) {
            console.log('appending image file:', imageInput.files[0].name, imageInput.files[0].size, imageInput.files[0].type);
            formData.append('main_image', imageInput.files[0]);
        } else {
            console.log('ERROR: no image selected - this should not happen!');
        }

        // debug: verify formdata AFTER all appends
        console.log('=== FormData Final Contents ===');
        let hasFile = false;
        for (let [key, value] of formData.entries()) {
            if (key === 'csrf_token') {
                console.log(`  ${key}: ${value.substring(0, 20)}...`);
            } else if (key === 'main_image') {
                hasFile = true;
                console.log(`  ${key}: [File: ${value.name}, Size: ${value.size}, Type: ${value.type}]`);
            } else if (typeof value === 'string') {
                console.log(`  ${key}: ${value.substring(0, 50)}${value.length > 50 ? '...' : ''}`);
            } else {
                console.log(`  ${key}:`, value);
            }
        }
        console.log('Has file in FormData:', hasFile);
        if (!hasFile) {
            throw new Error('CRITICAL: File not in FormData!');
        }

        // debug: log request
        console.log('sending request to /api/seller/products');

        // Gunakan XMLHttpRequest untuk progress tracking
        const data = await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Progress bar element
            const progressContainer = createProgressBar();

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    updateProgressBar(progressContainer, percentComplete);
                }
            });

            xhr.addEventListener('load', () => {
                removeProgressBar(progressContainer);

                console.log('response status:', xhr.status);
                console.log('response headers:', xhr.getResponseHeader('content-type'));

                const contentType = xhr.getResponseHeader('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('response bukan json:', xhr.responseText.substring(0, 500));
                    reject(new Error('server mengembalikan response tidak valid. cek console untuk detail.'));
                    return;
                }

                try {
                    const responseData = JSON.parse(xhr.responseText);
                    console.log('response data:', responseData);
                    resolve(responseData);
                } catch (e) {
                    console.error('error parsing json:', e);
                    reject(new Error('gagal memproses response dari server'));
                }
            });

            xhr.addEventListener('error', () => {
                removeProgressBar(progressContainer);
                reject(new Error('gagal terhubung ke server'));
            });

            xhr.open('POST', '/api/seller/products', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });

        if (data.success) {
            showToast('Produk Berhasil Ditambahkan');
            // redirect ke halaman product management setelah 1.5 detik
            setTimeout(() => {
                window.location.href = '/seller/products';
            }, 1500);
        } else {
            // cek apakah ada error validasi detail
            if (data.data && typeof data.data === 'object') {
                // tampilkan error untuk setiap field
                Object.keys(data.data).forEach(field => {
                    const errorMsg = data.data[field];
                    // map field name ke error element id
                    const errorElementMap = {
                        'product_name': 'nameError',
                        'description': 'descError',
                        'price': 'priceError',
                        'stock': 'stockError',
                        'category_ids': 'categoryError',
                        'main_image': 'imageError'
                    };

                    const errorElementId = errorElementMap[field];
                    if (errorElementId) {
                        showError(errorElementId, errorMsg);
                    }
                });
                throw new Error(data.error || 'validasi gagal, periksa form anda');
            } else {
                throw new Error(data.error || data.message || 'gagal menyimpan produk');
            }
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

    // validasi image (wajib untuk tambah produk)
    const imageInput = document.getElementById('mainImage');
    if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
        showError('imageError', 'foto produk wajib diupload');
        isValid = false;
    } else {
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

    // click handler untuk upload area dan tombol ganti foto
    uploadArea.addEventListener('click', function(e) {
        // trigger file input saat klik area atau tombol ganti foto
        if (e.target.closest('.btn-change-image') || e.target.closest('.upload-placeholder')) {
            e.preventDefault();
            e.stopPropagation();
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

    // clear error jika ada
    const imageError = document.getElementById('imageError');
    if (imageError) {
        imageError.classList.remove('show');
        imageError.textContent = '';
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
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');

    if (!uploadPlaceholder || !imagePreview || !previewImage) {
        console.error('preview elements not found');
        return;
    }

    // update preview image source
    previewImage.src = imageSrc;

    // hide placeholder, show preview
    uploadPlaceholder.classList.add('hidden');
    imagePreview.classList.remove('hidden');
    imagePreview.classList.add('flex');
}

// =================================================================
// CSRF TOKEN - Token sudah di-set dari server di view
// =================================================================

// =================================================================
// LOADING & TOAST
// =================================================================

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    if (toast && toastMessage) {
        toastMessage.textContent = message;
        toast.className = 'toast show ' + type;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }, 3000);
    }
}

// =================================================================
// PROGRESS BAR HELPERS
// =================================================================

function createProgressBar() {
    const progressContainer = document.createElement('div');
    progressContainer.className = 'upload-progress-container';
    progressContainer.innerHTML = `
        <div class="upload-progress-wrapper">
            <div class="upload-progress-info">
                <span class="upload-progress-label">Mengupload...</span>
                <span class="upload-progress-percent">0%</span>
            </div>
            <div class="upload-progress-bar">
                <div class="upload-progress-fill" style="width: 0%"></div>
            </div>
        </div>
    `;

    // Insert before form (after page title)
    const form = document.querySelector('form');
    if (form && form.parentNode) {
        form.parentNode.insertBefore(progressContainer, form);
    }

    return progressContainer;
}

function updateProgressBar(progressContainer, percent) {
    if (!progressContainer) return;

    const fill = progressContainer.querySelector('.upload-progress-fill');
    const percentText = progressContainer.querySelector('.upload-progress-percent');

    if (fill) {
        fill.style.width = percent + '%';
    }

    if (percentText) {
        percentText.textContent = percent + '%';
    }

    // Update label based on progress
    const label = progressContainer.querySelector('.upload-progress-label');
    if (label) {
        if (percent >= 100) {
            label.textContent = 'Memproses...';
        } else if (percent >= 50) {
            label.textContent = 'Mengupload... Hampir selesai';
        }
    }
}

function removeProgressBar(progressContainer) {
    if (progressContainer && progressContainer.parentNode) {
        progressContainer.style.opacity = '0';
        progressContainer.style.transition = 'opacity 0.3s ease';

        setTimeout(() => {
            if (progressContainer.parentNode) {
                progressContainer.parentNode.removeChild(progressContainer);
            }
        }, 300);
    }
}