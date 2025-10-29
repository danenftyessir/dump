document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function(e) {
            if (loginForm.checkValidity()) {
                disableFormState(true);
            }
        });
    }

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

function togglePassword() {
    const passwordField = document.getElementById('password'); 
    const passwordToggleIcon = document.getElementById('passwordToggleIcon'); // Tambahkan ID ke ikon mata di HTML

    if (passwordField && passwordToggleIcon) {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordToggleIcon.classList.remove('fa-eye');
            passwordToggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            passwordToggleIcon.classList.remove('fa-eye-slash');
            passwordToggleIcon.classList.add('fa-eye');
        }
    } else {
        console.error("Password field or toggle icon not found!");
    }
}