// public/js/seller-dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen-elemen penting
    const editModal = document.getElementById('editStoreModal'); // Modal
    const closeModalBtn = document.getElementById('modal-close-btn'); // Tombol close modal
    const editFormContainer = document.getElementById('editStoreFormContainer'); // Kontainer form di modal

    // Ambil data toko saat ini dari view
    const storeNameEl = document.getElementById('store-name-display');
    const storeDescEl = document.getElementById('store-description-display');
    const storeLogoEl = document.getElementById('store-logo-preview');

    /**
     * Membuka modal dan mengisi form
     * Function ini akan dipanggil dari button onclick="openEditStoreModal()"
     */
    window.openEditStoreModal = function() {
        // Isi form dengan data terbaru
        const currentStoreName = storeNameEl ? storeNameEl.textContent : '';
        const currentStoreDesc = storeDescEl ? storeDescEl.textContent : '';

        editFormContainer.innerHTML = `
            <form id="dynamicEditStoreForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${escapeHtml(CSRF_TOKEN)}">

                <div class="form-group">
                    <label for="store_name">Nama Toko</label>
                    <input type="text" id="store_name" name="store_name" class="form-input" value="${escapeHtml(currentStoreName)}">
                    <span class="error-message" id="error-store_name"></span>
                </div>

                <div class="form-group">
                    <label for="store_description">Deskripsi Toko</label>
                    <textarea id="store_description" name="store_description" class="form-textarea" rows="5">${escapeHtml(currentStoreDesc)}</textarea>
                    <span class="error-message" id="error-store_description"></span>
                </div>

                <div class="form-group">
                    <label for="store_logo">Ganti Logo </label>
                    <input type="file" id="store_logo" name="store_logo" class="form-input" accept="image/jpeg, image/png, image/webp">
                    <span class="error-message" id="error-store_logo"></span>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditStoreModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Simpan Perubahan</button>
                </div>
            </form>
        `;

        // Tambahkan event listener ke form yang baru dibuat
        const dynamicForm = document.getElementById('dynamicEditStoreForm');
        dynamicForm.addEventListener('submit', handleFormSubmit);

        // Handle Enter key - submit form on Enter in text inputs, but not in textarea
        const storeNameInput = document.getElementById('store_name');
        const storeDescTextarea = document.getElementById('store_description');

        if (storeNameInput) {
            storeNameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    dynamicForm.requestSubmit(); // Trigger form submit
                }
            });
        }

        // Prevent Enter in textarea from submitting form (allow newlines)
        if (storeDescTextarea) {
            storeDescTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    // Allow default behavior (insert newline)
                    e.stopPropagation();
                }
            });
        }

        editModal.classList.add('modal-open');
    };

    /**
     * Menutup modal
     * Function ini akan dipanggil dari button onclick="closeEditStoreModal()"
     */
    window.closeEditStoreModal = function() {
        editModal.classList.remove('modal-open');
        editFormContainer.innerHTML = '<p class="placeholder-text">Form edit toko akan dimuat...</p>';
    };

    // Pasang event listener ke tombol close
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', window.closeEditStoreModal);
    }

    // Tutup modal jika klik di luar
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                window.closeEditStoreModal();
            }
        });
    }

    /**
     * Menangani submit form "Edit di Tempat" via AJAX (XMLHttpRequest)
     * (AC: AJAX Wajib #1)
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        // ✅ AC: Confirmation Modal sebelum save (Spesifikasi hal 31-32)
        if (!confirm('Yakin ingin menyimpan perubahan informasi toko?')) {
            return;
        }

        const form = e.target;
        const submitBtn = document.getElementById('saveBtn');
        const formData = new FormData(form);

        // Reset error messages
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        // AC: Loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Menyimpan...';

        // ✅ AC: Progress Bar Upload (Spesifikasi hal 31 - opsional tapi bagus untuk UX)
        const progressBar = createProgressBar();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/seller/store/update', true);

        // Kita tidak set Content-Type, biarkan browser menentukannya untuk FormData
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        // ✅ Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                updateProgressBar(progressBar, percentComplete);
            }
        });

        xhr.onload = function() {
            // Hide progress bar when complete
            removeProgressBar(progressBar);

            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Simpan Perubahan';

            try {
                const response = JSON.parse(xhr.responseText);

                if (xhr.status === 422 || (xhr.status === 400 && response.data)) {
                    // --- Handle Validation Errors (400/422) ---
                    showToast('Validasi gagal, periksa input Anda.', 'error');
                    if (response.data) {
                        for (const key in response.data) {
                            const errorEl = document.getElementById(`error-${key}`);
                            if (errorEl) {
                                errorEl.textContent = response.data[key];
                            }
                        }
                    }
                } else if (xhr.status >= 200 && xhr.status < 300 && response.success) {
                    // --- Handle Success (200) ---
                    showToast(response.message, 'success');

                    // Update tampilan dashboard secara real-time
                    const updatedStore = response.data;

                    // Update store name di hero section
                    if (storeNameEl && updatedStore.store_name) {
                        storeNameEl.textContent = updatedStore.store_name;
                    }

                    // Update store description
                    if (storeDescEl && updatedStore.store_description) {
                        storeDescEl.textContent = updatedStore.store_description;
                        // Tampilkan element jika sebelumnya hidden (dari empty state)
                        storeDescEl.classList.remove('hidden');
                        // Hide empty state jika ada
                        const emptyState = document.querySelector('.empty-state-inline');
                        if (emptyState) {
                            emptyState.classList.add('hidden');
                        }
                    }

                    // Update logo di hero section jika ada perubahan
                    if (storeLogoEl && updatedStore.store_logo_path) {
                        storeLogoEl.src = updatedStore.store_logo_path;
                        storeLogoEl.classList.remove('placeholder');
                    }

                    window.closeEditStoreModal();
                } else {
                    // --- Handle Other Errors (400, 500) ---
                    showToast(response.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                // Log the actual error for debugging
                console.error('Error parsing server response:', err);
                console.error('Response status:', xhr.status);
                console.error('Response text:', xhr.responseText);
                showToast('Gagal memproses respons server. Periksa console untuk detail.', 'error');
            }
        };

        xhr.onerror = function() {
            removeProgressBar(progressBar);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Simpan Perubahan';
            showToast('Gagal terhubung ke server. Periksa koneksi Anda.', 'error');
        };

        xhr.send(formData);
    }
});


// ============================================
// HELPER FUNCTIONS (Mandiri, karena tidak ada global.js)
// ============================================

/**
 * Menampilkan toast notification (AC)
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container'; // Style dari global.css
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => { toast.classList.add('show'); }, 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 500);
    }, 3000);
}

/**
 * Helper untuk XSS prevention (Keamanan)
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return text.toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

/**
 * ✅ Progress Bar Helper Functions (AC: Spesifikasi hal 31)
 * Membuat, update, dan remove progress bar untuk upload
 */
function createProgressBar() {
    // Create progress bar container
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

    // Insert into modal body
    const modalBody = document.querySelector('.modal-body');
    if (modalBody) {
        modalBody.insertBefore(progressContainer, modalBody.firstChild);
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
        // Fade out animation
        progressContainer.style.opacity = '0';
        progressContainer.style.transition = 'opacity 0.3s ease';

        setTimeout(() => {
            if (progressContainer.parentNode) {
                progressContainer.parentNode.removeChild(progressContainer);
            }
        }, 300);
    }
}