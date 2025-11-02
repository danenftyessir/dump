document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const profileBtn = document.getElementById('profileSubmitBtn');
    
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

function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const passwordToggleIcon = document.getElementById(iconId);

    if (passwordField && passwordToggleIcon) {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordToggleIcon.classList.remove('icon-eye');
            passwordToggleIcon.classList.add('icon-eye-slash');
        } else {
            passwordField.type = 'password';
            passwordToggleIcon.classList.remove('icon-eye-slash');
            passwordToggleIcon.classList.add('icon-eye');
        }
    } else {
        console.error("Password toggle error: Field or Icon not found.", fieldId, iconId);
    }
}