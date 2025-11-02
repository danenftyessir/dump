document.addEventListener('DOMContentLoaded', function() {
    
    // ambil token csrf
    const logoutForm = document.querySelector('.logout-form');
    const csrfToken = logoutForm ? logoutForm.querySelector('input[name="_token"]').value : '';
    
    // dropdown user navbar
    const userWrapper = document.querySelector('.navbar-user-wrapper');
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            const isHidden = userDropdownMenu.style.display === 'none' || userDropdownMenu.style.display === '';
            userDropdownMenu.style.display = isHidden ? 'block' : 'none';
        });
    }
    
    // close dropdown
    document.addEventListener('click', function(event) {
        if (userWrapper && !userWrapper.contains(event.target) && userDropdownMenu) {
            userDropdownMenu.style.display = 'none';
        }
    });

    // Top-Up Modal
    const topUpModal = document.getElementById('topUpModal');
    const openTopUpModalBtn = document.getElementById('openTopUpModalBtn');
    const closeTopUpModalBtn = document.getElementById('closeTopUpModalBtn');
    const cancelTopUpBtn = document.getElementById('cancelTopUpBtn');
    const topUpModalOverlay = document.getElementById('topUpModalOverlay');
    const topUpForm = document.getElementById('topUpForm');
    const topUpAmountInput = document.getElementById('topUpAmount');
    const presetButtons = document.querySelectorAll('.preset-btn');
    const topUpSubmitBtn = document.getElementById('topUpSubmitBtn');
    const topUpError = document.getElementById('topUpError');

    function openTopUpModal() {
        if (topUpModal) topUpModal.style.display = 'flex';
    }

    function closeTopUpModal() {
        if (topUpModal) topUpModal.style.display = 'none';
        if (topUpForm) topUpForm.reset();
        if (topUpError) topUpError.style.display = 'none';
        presetButtons.forEach(btn => btn.classList.remove('selected'));
    }

    if (openTopUpModalBtn) openTopUpModalBtn.addEventListener('click', openTopUpModal);
    if (closeTopUpModalBtn) closeTopUpModalBtn.addEventListener('click', closeTopUpModal);
    if (cancelTopUpBtn) cancelTopUpBtn.addEventListener('click', closeTopUpModal);
    if (topUpModalOverlay) topUpModalOverlay.addEventListener('click', closeTopUpModal);

    presetButtons.forEach(button => {
        button.addEventListener('click', function() {
            const amount = this.dataset.amount;
            if (topUpAmountInput) topUpAmountInput.value = amount;
            presetButtons.forEach(btn => btn.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    if (topUpAmountInput) {
        topUpAmountInput.addEventListener('input', function() {
            presetButtons.forEach(btn => btn.classList.remove('selected'));
        });
    }

    if (topUpForm) {
        topUpForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleTopUp(event.target);
        });
    }

    // handle top-up
    function handleTopUp(formElement) {
        const submitBtn = document.getElementById('topUpSubmitBtn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses...';
        topUpError.style.display = 'none';

        const formData = new FormData(formElement);
        
        // validasi client-side
        const amount = parseInt(formData.get('amount'), 10);
        if (isNaN(amount) || amount < 10000) {
            topUpError.textContent = 'Minimal top-up adalah Rp 10.000.';
            topUpError.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/user/topup', true);
        
        // set headers
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        // Update balance di navbar
                        const balanceElement = document.getElementById('navbar-balance');
                        if (balanceElement) {
                            balanceElement.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(result.data.new_balance);
                        }
                        // todo: toast notification
                        alert('Top-up berhasil! Saldo Anda telah bertambah.');
                        closeTopUpModal();
                    } else {
                        topUpError.textContent = result.message || 'Terjadi kesalahan saat top-up';
                        topUpError.style.display = 'block';
                    }
                } catch (e) {
                    topUpError.textContent = 'Gagal memproses respons server.';
                    topUpError.style.display = 'block';
                }
            } else {
                // Handle error response
                try {
                    const result = JSON.parse(xhr.responseText);
                    topUpError.textContent = 'Error ' + xhr.status + ': ' + result.message;
                    topUpError.style.display = 'block';
                } catch (e) {
                    topUpError.textContent = 'Error server: ' + xhr.status;
                    topUpError.style.display = 'block';
                }
            }
        };
        
        xhr.onerror = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            topUpError.textContent = 'Gagal terhubung ke server. Periksa koneksi Anda.';
            topUpError.style.display = 'block';
        };

        // kirim request
        xhr.send(formData);
    }
});