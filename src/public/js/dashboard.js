let quillEditor;
document.addEventListener('DOMContentLoaded', function() {
    initializeQuillEditor();    
    loadDashboardStats();
    setupEventListeners();
});

// inisialisasi quill editor untuk deskripsi toko
function initializeQuillEditor() {
    if (typeof Quill !== 'undefined') {
        quillEditor = new Quill('#storeDescriptionEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    // format text yang aman (sesuai whitelist backend)
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    // lists
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],                    
                    // headers
                    [{ 'header': [1, 2, 3, false] }],
                    // link: quill akan auto-sanitize link
                    ['link'],                    
                    ['clean']
                ],
                clipboard: {
                    matchVisual: false
                }
            },
            placeholder: 'Tulis deskripsi toko Anda...',
            formats: [
                'bold', 'italic', 'underline', 'strike',
                'blockquote', 'code-block',
                'list', 'bullet',
                'header',
                'link'
            ]
        });

        // load konten yang ada (sudah di-sanitize dari backend)
        const existingDescription = document.querySelector('#storeDescription');
        if (existingDescription && existingDescription.value) {
            quillEditor.root.innerHTML = existingDescription.value;
        }

        // limit panjang konten
        const MAX_LENGTH = 5000;
        quillEditor.on('text-change', function() {
            if (quillEditor.getLength() > MAX_LENGTH) {
                quillEditor.deleteText(MAX_LENGTH, quillEditor.getLength());
                showNotification('Deskripsi maksimal ' + MAX_LENGTH + ' karakter', 'warning');
            }
        });
    } else {
        console.error('Quill.js belum dimuat. Pastikan CDN Quill.js tersedia.');
    }
}

// setup event listeners
function setupEventListeners() {
    // character counter untuk nama toko
    const storeNameInput = document.getElementById('storeName');
    if (storeNameInput) {
        storeNameInput.addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('nameCharCount').textContent = charCount;
        });
        // trigger sekali untuk set nilai awal
        storeNameInput.dispatchEvent(new Event('input'));
    }

    // preview logo upload
    const logoInput = document.getElementById('storeLogo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            previewLogoImage(e);
        });
    }

    // form submit handler
    const editStoreForm = document.getElementById('editStoreForm');
    if (editStoreForm) {
        editStoreForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleStoreUpdate();
        });
    }
}

// load statistik dashboard
function loadDashboardStats() {
    // TO DO order management fetch data statistik dari endpoint:
    // GET /api/seller/store/stats   
    // sementara ini
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/seller/store/stats', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    updateStatsDisplay(response.data);
                }
            } catch (error) {
                console.error('Error parsing stats:', error);
            }
        }
    };
    
    xhr.onerror = function() {
        console.error('Error loading stats');
    };
    
    xhr.send();
}

// update tampilan statistik
function updateStatsDisplay(stats) {
    const elements = {
        'totalProducts': stats.total_products,
        'pendingOrders': stats.pending_orders,
        'lowStockProducts': stats.low_stock_products,
        'totalRevenue': formatCurrency(stats.total_revenue)
    };
    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }
}

// buka modal edit toko
function openEditStoreModal() {
    const modal = document.getElementById('editStoreModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// tutup modal edit toko
function closeEditStoreModal() {
    const modal = document.getElementById('editStoreModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// preview gambar logo yang diupload
function previewLogoImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('logoPreview');   
    if (!file) {
        preview.innerHTML = '';
        return;
    }

    // validasi tipe file
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showNotification('Format file harus JPG, JPEG, PNG, atau WEBP', 'error');
        event.target.value = '';
        return;
    }

    // validasi ukuran file (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        showNotification('Ukuran file maksimal 2MB', 'error');
        event.target.value = '';
        return;
    }

    // tampilkan preview
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.innerHTML = `
            <img src="${e.target.result}" alt="Preview Logo">
            <p class="preview-filename">${file.name}</p>
        `;
    };
    reader.readAsDataURL(file);
}

// update informasi toko
function handleStoreUpdate() {
    const form = document.getElementById('editStoreForm');
    const submitBtn = document.getElementById('btnSaveStore');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    // validasi form
    if (!validateStoreForm()) {
        return;
    }
    // ambil data dari Quill editor
    if (quillEditor) {
        const descriptionHTML = quillEditor.root.innerHTML;
        document.getElementById('storeDescription').value = descriptionHTML;
    }

    // disable tombol dan tampilkan loading
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    const formData = new FormData(form);
    // kirim request menggunakan XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('PATCH', '/api/my-store', true);

    xhr.onload = function() {
        // Re-enable tombol
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('Informasi toko berhasil diupdate', 'success');
                    closeEditStoreModal();
                    // reload halaman untuk menampilkan perubahan
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(response.error || 'Gagal mengupdate toko', 'error');
                }
            } catch (error) {
                showNotification('Terjadi kesalahan saat memproses response', 'error');
            }
        } else {
            showNotification('Gagal mengupdate toko. Silakan coba lagi.', 'error');
        }
    };

    xhr.onerror = function() {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
        showNotification('Terjadi kesalahan jaringan', 'error');
    };

    xhr.send(formData);
}

// validasi form toko
function validateStoreForm() {
    const storeName = document.getElementById('storeName').value.trim();    
    if (!storeName) {
        showNotification('Nama toko tidak boleh kosong', 'error');
        return false;
    }
    if (storeName.length > 100) {
        showNotification('Nama toko maksimal 100 karakter', 'error');
        return false;
    }
    if (quillEditor) {
        const description = quillEditor.getText().trim();
        if (!description || description.length < 10) {
            showNotification('Deskripsi toko minimal 10 karakter', 'error');
            return false;
        }
    }

    return true;
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// munculin notif
function showNotification(message, type = 'info') {
    // TO DO Implementasi notification/toast component
    // sementara gunakan alert
    console.log(`[${type.toUpperCase()}] ${message}`);
    alert(message);
}

// close modal ketika klik di luar modal
window.onclick = function(event) {
    const modal = document.getElementById('editStoreModal');
    if (event.target === modal) {
        closeEditStoreModal();
    }
};