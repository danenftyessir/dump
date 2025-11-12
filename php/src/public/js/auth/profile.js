document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi toast manager
    const toastManager = new ToastManager();
    
    // cek untuk notifikasi pesan
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        const successMessage = mainContent.dataset.successMessage;
        const errorMessage = mainContent.dataset.errorMessage;
        
        if (successMessage) {
            toastManager.show({
                type: 'success',
                title: 'Berhasil!',
                message: successMessage,
                duration: 5000
            });
        }
        
        if (errorMessage) {
            toastManager.show({
                type: 'error',
                title: 'Error!',
                message: errorMessage,
                duration: 7000
            });
        }
    }

    const profileForm = document.getElementById('profileForm');
    const profileBtn = document.getElementById('profileSubmitBtn');
    
    // Saat form disubmit, tombol akan di-disable dan muncul loading
    if (profileForm && profileBtn) {
        profileForm.addEventListener('submit', function(e) {
            if (!confirm('Anda yakin ingin menyimpan perubahan profile?')) {
                e.preventDefault();
                return;
            }
            disableFormState(profileBtn, 'Menyimpan...');
        });
    }

    const passwordForm = document.getElementById('passwordForm');
    const passwordBtn = document.getElementById('passwordSubmitBtn');

    if (passwordForm && passwordBtn) {
        passwordForm.addEventListener('submit', function(e) {
            if (!confirm('Anda yakin ingin mengganti password?')) {
                e.preventDefault();
                return;
            }
            disableFormState(passwordBtn, 'Mengganti...');
        });
    }

    // Fungsi untuk mengubah state tombol submit (loading atau normal)
    function disableFormState(button, loadingText) {
        if (!button) return;
        
        const originalText = button.getAttribute('data-original-text') || button.textContent;
        if (!button.hasAttribute('data-original-text')) {
             button.setAttribute('data-original-text', originalText);
        }

        button.disabled = true;
        button.innerHTML = `<span class="spinner"></span> ${loadingText}`;
    }
});

// Fungsi untuk toggle visibilitas password baru
function togglePassword() {
    const newPasswordField = document.getElementById('newPassword');
    const newPasswordToggleIcon = document.getElementById('newPasswordToggleIcon');

    // Jika elemen ditemukan
    if (newPasswordField && newPasswordToggleIcon) {
        if (newPasswordField.type === 'password') {
            // Jika tipe password, ubah ke text dan ganti icon ke eye-off (disilangin lah)
            newPasswordField.type = 'text';
            newPasswordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a13.16 13.16 0 0 1-1.67 2.68"></path>
                    <path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 8 11 8a9.74 9.74 0 0 0 5.39-1.61"></path>
                    <line x1="2" y1="2" x2="22" y2="22"></line>
                </svg>
            `;
        } else {
            // Jika tipe text, ubah ke password dan ganti icon ke eye (jadi normal lagi)
            newPasswordField.type = 'password';
            newPasswordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
        }
    } else {
        console.error("New password field or toggle icon not found!");
    }
}

// Function untuk toggle password dengan parameter (dipanggil dari HTML)
function togglePassword(fieldId, buttonId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = document.getElementById(buttonId);
    
    if (passwordField && toggleButton) {
        const isPassword = passwordField.type === 'password';
        
        passwordField.type = isPassword ? 'text' : 'password';
        
        // Update icon SVG
        const svgIcon = toggleButton.querySelector('svg');
        if (svgIcon) {
            if (isPassword) {
                // Show eye-off icon (password visible)
                svgIcon.innerHTML = `
                    <path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a13.16 13.16 0 0 1-1.67 2.68"></path>
                    <path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 8 11 8a9.74 9.74 0 0 0 5.39-1.61"></path>
                    <line x1="2" y1="2" x2="22" y2="22"></line>
                `;
            } else {
                // Show eye icon (password hidden)
                svgIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        }
    }
}