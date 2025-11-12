document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Saat form disubmit, tombol login akan di-disable dan muncul loading
    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function(e) {
            if (loginForm.checkValidity()) {
                disableFormState(true);
            }
        });
    }

    // Fungsi untuk mengubah state tombol login (loading atau normal)
    function disableFormState(loading) {
        if (!submitBtn) return;
        if (loading) {
            submitBtn.disabled = true;
            if (!submitBtn.hasAttribute('data-original-text')) {
                submitBtn.setAttribute('data-original-text', submitBtn.textContent || 'Login');
            }
            submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Login';
        }
    }
});

// Fungsi untuk toggle visibilitas password
function togglePassword() {
    const passwordField = document.getElementById('password'); 
    const passwordToggleIcon = document.getElementById('passwordToggleIcon');

    if (passwordField && passwordToggleIcon) {
        // Jika tipe password, ubah ke text dan ganti icon ke eye-off (disilangin lah)
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordToggleIcon.setAttribute('aria-label', 'Sembunyikan password');
            passwordToggleIcon.setAttribute('title', 'Sembunyikan password');
            passwordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 8 11 8a13.16 13.16 0 0 1-1.67 2.68"></path>
                    <path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 8 11 8a9.74 9.74 0 0 0 5.39-1.61"></path>
                    <line x1="2" y1="2" x2="22" y2="22"></line>
                </svg>
            `;
        } else {
            // Jika tipe text, ubah ke password dan ganti icon ke eye (jadi normal lagi)
            passwordField.type = 'password';
            passwordToggleIcon.setAttribute('aria-label', 'Tampilkan password');
            passwordToggleIcon.setAttribute('title', 'Tampilkan password');
            passwordToggleIcon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            `;
        }
    } else {
        console.error("Password field or toggle icon not found!");
    }
}