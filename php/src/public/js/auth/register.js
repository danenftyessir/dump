let quill = null;

// Inisialisasi Quill editor untuk deskripsi toko
function initQuill() {
    if (!quill) {
        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Deskripsi Toko Anda'
        });

        quill.on('text-change', function() {
            document.getElementById('store_description').value = quill.root.innerHTML;
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const roleBuyer = document.getElementById('role_buyer');
    const roleSeller = document.getElementById('role_seller');
    const sellerFields = document.getElementById('sellerFields');
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    // Saat form disubmit, tombol register akan di-disable dan muncul loading
    if (registerForm && submitBtn) {
        registerForm.addEventListener('submit', function(e) {
            if (registerForm.checkValidity()) {
                disableFormState(true);
            }
        });
    }

    // Fungsi untuk mengubah state tombol register (loading atau normal)
    function disableFormState(loading) {
        if (!submitBtn) return;
        if (loading) {
            submitBtn.disabled = true;
            if (!submitBtn.hasAttribute('data-original-text')) {
                submitBtn.setAttribute('data-original-text', submitBtn.textContent || 'Daftar');
            }
            submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Daftar';
        }
    }

    function toggleSellerFields() {
        if (roleSeller && sellerFields && roleSeller.checked) {
            sellerFields.style.display = 'block';
            sellerFields.querySelectorAll('input[type="text"], textarea').forEach(input => input.required = true);
            // Initialize Quill when seller is selected
            setTimeout(initQuill, 100);
        } else if (sellerFields) {
            sellerFields.style.display = 'none';
            sellerFields.querySelectorAll('input[type="text"], textarea').forEach(input => input.required = false);
        }
    }

    if (roleBuyer && roleSeller) {
        roleBuyer.addEventListener('change', toggleSellerFields);
        roleSeller.addEventListener('change', toggleSellerFields);
        toggleSellerFields();
        
        // Initialize Quill if seller is already selected on page load
        if (roleSeller.checked) {
            initQuill();
        }
    }
});

// Fungsi untuk toggle visibilitas password
function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const passwordToggleIcon = document.getElementById(iconId);

    if (passwordField && passwordToggleIcon) {
        // Jika tipe password, ubah ke text dan ganti icon ke eye-off (disilangin lah)
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a13.16 13.16 0 0 1-1.67 2.68"></path>
                    <path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 8 11 8a9.74 9.74 0 0 0 5.39-1.61"></path>
                    <line x1="2" y1="2" x2="22" y2="22"></line>
                </svg>
            `;
        } else {
            // Jika tipe text, ubah ke password dan ganti icon ke eye (jadi normal lagi)
            passwordField.type = 'password';
            passwordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
        }
    } else {
        console.error("Password field or toggle icon not found!", fieldId, iconId);
    }
}