// public/js/register.js

document.addEventListener('DOMContentLoaded', function() {
    const roleBuyer = document.getElementById('role_buyer');
    const roleSeller = document.getElementById('role_seller');
    const sellerFields = document.getElementById('sellerFields');
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    function toggleSellerFields() {
        if (roleSeller && sellerFields && roleSeller.checked) {
            sellerFields.style.display = 'block';
            sellerFields.querySelectorAll('input[type="text"], textarea').forEach(input => input.required = true);
        } else if (sellerFields) {
            sellerFields.style.display = 'none';
            sellerFields.querySelectorAll('input[type="text"], textarea').forEach(input => input.required = false);
        }
    }

    if (roleBuyer && roleSeller) {
        roleBuyer.addEventListener('change', toggleSellerFields);
        roleSeller.addEventListener('change', toggleSellerFields);
        toggleSellerFields();
    }

    if (registerForm && submitBtn) {
        registerForm.addEventListener('submit', function(e) {
            if (registerForm.checkValidity()) {
                disableFormState(true);
            }
        });
    }

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
});

function togglePassword(fieldId, iconId) {
    const passwordField = document.getElementById(fieldId);
    const passwordToggleIcon = document.getElementById(iconId);

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
        console.error("Password field or toggle icon not found!", fieldId, iconId);
    }
}