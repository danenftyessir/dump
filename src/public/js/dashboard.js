let quillEditor = null;

// inisialisasi halaman dashboard
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    initializeQuillEditor();
    loadDashboardStats();
    
    // tutup modal jika klik di luar modal content
    const modal = document.getElementById('editStoreModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeEditStoreModal();
            }
        });
    }
});

// inisialisasi quill.js editor untuk deskripsi toko
function initializeQuillEditor() {
    if (typeof Quill !== 'undefined') {
        const editorContainer = document.getElementById('storeDescriptionEditor');
        if (editorContainer) {
            quillEditor = new Quill(editorContainer, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link'],
                        ['clean']
                    ]
                },
                placeholder: 'Tulis Deskripsi Toko Anda...'
            });
        }
    } else {
        console.error('Quill.js Tidak Ditemukan. Pastikan CDN Quill.js Tersedia.');
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
        modal.classList.remove('modal-closed', 'hidden');
        modal.classList.add('modal-open');
        document.body.classList.remove('allow-scroll');
        document.body.classList.add('no-scroll');
    }
}

// tutup modal edit toko
function closeEditStoreModal() {
    const modal = document.getElementById('editStoreModal');
    if (modal) {
        modal.classList.remove('modal-open');
        modal.classList.add('modal-closed', 'hidden');
        document.body.classList.remove('no-scroll');
        document.body.classList.add('allow-scroll');
    }
}

// preview gambar logo yang diupload
function previewLogoImage(event) {
    const file = event.target.files[0];
    if (file) {
        // validasi ukuran file (max 2MB)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Ukuran File Maksimal 2MB');
            event.target.value = '';
            return;
        }

        // validasi tipe file
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipe File Harus JPG, PNG, atau WEBP');
            event.target.value = '';
            return;
        }

        // preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.getElementById('logoPreview');
            if (previewContainer) {
                previewContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Logo Preview">
                    <button type="button" class="btn-remove-preview" onclick="removeLogoPreview()">
                        Hapus Preview
                    </button>
                `;
                previewContainer.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
}

// hapus preview logo
function removeLogoPreview() {
    const logoInput = document.getElementById('storeLogo');
    const previewContainer = document.getElementById('logoPreview');
    
    if (logoInput) {
        logoInput.value = '';
    }
    
    if (previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.style.display = 'none';
    }
}

// handle update toko via AJAX
function handleStoreUpdate() {
    // ambil data dari form
    const storeName = document.getElementById('storeName').value.trim();
    
    // validasi nama toko
    if (!storeName) {
        showToast('Nama Toko Tidak Boleh Kosong', 'error');
        return;
    }

    if (storeName.length > 100) {
        showToast('Nama Toko Maksimal 100 Karakter', 'error');
        return;
    }

    // ambil deskripsi dari quill editor
    let storeDescription = '';
    if (quillEditor) {
        storeDescription = quillEditor.root.innerHTML;
        
        // cek apakah editor kosong
        if (quillEditor.getText().trim().length === 0) {
            storeDescription = '';
        }
    }

    // buat FormData untuk kirim file
    const formData = new FormData();
    formData.append('store_name', storeName);
    formData.append('store_description', storeDescription);

    // tambahkan logo jika ada file yang diupload
    const logoInput = document.getElementById('storeLogo');
    if (logoInput.files && logoInput.files[0]) {
        formData.append('store_logo', logoInput.files[0]);
    }

    // disable tombol submit
    const submitBtn = document.querySelector('#editStoreForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    }

    // kirim request PATCH via XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('PATCH', '/api/my-store', true);
    
    xhr.onload = function() {
        // enable kembali tombol submit
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Perubahan';
        }

        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showToast('Toko Berhasil Diupdate', 'success');
                    
                    // reload halaman setelah 1 detik untuk update UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(response.message || 'Gagal Mengupdate Toko', 'error');
                }
            } catch (error) {
                showToast('Terjadi Kesalahan Saat Parsing Response', 'error');
            }
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                showToast(response.message || 'Gagal Mengupdate Toko', 'error');
            } catch (error) {
                showToast('Terjadi Kesalahan Server', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        // enable kembali tombol submit
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Perubahan';
        }
        showToast('Terjadi Kesalahan Koneksi', 'error');
    };
    
    xhr.send(formData);
}

// format angka ke format mata uang rupiah
function formatCurrency(amount) {
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
}

// tampilkan toast notification
function showToast(message, type = 'info') {
    // TO DO implementasi toast notification dengan animasi smooth
    // untuk sementara gunakan alert
    alert(message);
}