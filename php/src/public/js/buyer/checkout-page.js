function showConfirmModal() {
    document.getElementById('confirmModal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

function openTopUpModal() {
    document.getElementById('topupModal').classList.add('active');
}

function closeTopUpModal() {
    document.getElementById('topupModal').classList.remove('active');
    document.getElementById('topupAmount').value = '';
}

function setTopUpAmount(amount) {
    document.getElementById('topupAmount').value = amount;
}

function processTopUp() {
    const amount = parseInt(document.getElementById('topupAmount').value);

    if (!amount || amount < 10000) {
        showToast('Minimal top-up Rp 10.000', 'error');
        return;
    }

    showToast('Fitur top-up akan segera hadir! (Demo: saldo otomatis bertambah)', 'success');
    
    // Simulate top-up (in production, this would be a real payment gateway)
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}

function processCheckout() {
    const deliveryAddress = document.getElementById('deliveryAddress').value.trim() || DEFAULT_ADDRESS;

    if (!deliveryAddress) {
        showToast('Mohon isi alamat pengiriman', 'error');
        return;
    }

    console.log('Processing checkout with address:', deliveryAddress);

    closeConfirmModal();
    showLoading();

    const formData = new FormData();
    formData.append('delivery_address', deliveryAddress);
    formData.append('_token', CSRF_TOKEN);

    fetch('/checkout/process', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('Content-Type'));
        
        // Clone response to read text for debugging
        return response.text().then(text => {
            console.log('Response text:', text);
            
            // Try to parse as JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response is not valid JSON:', text);
                throw new Error('Invalid JSON response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        hideLoading();
        console.log('Response data:', data);

        if (data.success) {
            showToast(data.message, 'success');
            
            setTimeout(() => {
                window.location.href = '/orders';
            }, 2000);
        } else {
            console.error('Checkout failed:', data.message);
            showToast(data.message || 'Checkout failed', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Checkout error:', error);
        showToast('Network error: ' + error.message, 'error');
    });
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}