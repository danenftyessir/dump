function showConfirmModal() {
    console.log('showConfirmModal function called');
    const modal = document.getElementById('confirmModal');
    console.log('Modal element:', modal);
    if (modal) {
        modal.classList.add('active');
        console.log('Added active class to modal');
        Toast.info('Info', 'Periksa detail pesanan Anda sebelum melanjutkan');
    } else {
        console.error('confirmModal element not found!');
        Toast.error('Error', 'Modal tidak ditemukan');
    }
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
        Toast.error('Error', 'Minimal top-up Rp 10.000');
        return;
    }

    Toast.success('Top-up', 'Fitur top-up akan segera hadir! (Demo: saldo otomatis bertambah)');
    
    // Simulate top-up (in production, this would be a real payment gateway)
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}

function processCheckout() {
    console.log('processCheckout() function called');
    
    const deliveryAddressField = document.getElementById('deliveryAddress');
    const enteredAddress = deliveryAddressField ? deliveryAddressField.value.trim() : '';
    const finalAddress = enteredAddress || DEFAULT_ADDRESS;
    
    console.log('Delivery address field value:', enteredAddress);
    console.log('DEFAULT_ADDRESS:', DEFAULT_ADDRESS);
    console.log('Final address to use:', finalAddress);
    console.log('CSRF_TOKEN:', CSRF_TOKEN);
    console.log('TOTAL_PRICE:', TOTAL_PRICE);

    // Use the final address for delivery
    const deliveryAddress = finalAddress;

    console.log('Processing checkout with address:', deliveryAddress);

    closeConfirmModal();
    showLoading();
    Toast.info('Processing', 'Memproses pembayaran...');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/checkout', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoading();
            
            console.log('Response status:', xhr.status);
            console.log('Response headers Content-Type:', xhr.getResponseHeader('Content-Type'));
            console.log('Response text:', xhr.responseText);
            
            if (xhr.status === 200) {
                // Check if response is HTML (redirect to login)
                const contentType = xhr.getResponseHeader('Content-Type');
                if (contentType && contentType.includes('text/html')) {
                    console.error('Authentication required - received HTML instead of JSON');
                    Toast.error('Authentication Error', 'Authentication required. Please login first. Redirecting to login...');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                    return;
                }
                
                // Try to parse JSON response
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        Toast.success('Checkout Berhasil', data.message || 'Pesanan Anda sedang diproses.');
                        
                        // Update navbar balance jika ada new_balance dari server
                        if (data.data && data.data.new_balance !== undefined) {
                            if (typeof updateNavbarBalance === 'function') {
                                updateNavbarBalance(data.data.new_balance);
                            }
                        }
                        
                        setTimeout(() => {
                            window.location.href = '/orders';
                        }, 2500);
                    } else {
                        console.error('Checkout failed:', data.message);
                        Toast.error('Checkout Gagal', data.message || 'Silakan coba lagi.');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response is not valid JSON:', xhr.responseText);
                    
                    // Check if it's a login page
                    if (xhr.responseText.includes('<title>Login</title>') || xhr.responseText.includes('loginForm')) {
                        Toast.error('Authentication Error', 'Session expired. Please login again. Redirecting to login...');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                    } else {
                        Toast.error('Checkout Gagal', 'Invalid response from server. Please try again.');
                    }
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                Toast.error('Checkout Gagal', 'Terjadi kesalahan. Silakan coba lagi.');
            }
        }
    };

    xhr.onerror = function() {
        hideLoading();
        console.error('Network error occurred');
        Toast.error('Checkout Gagal', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
    };

    const params = `delivery_address=${encodeURIComponent(deliveryAddress)}&grand_total_confirmation=${encodeURIComponent(TOTAL_PRICE)}&_token=${encodeURIComponent(CSRF_TOKEN)}`;
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(params);
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

// Toast notifications now use the global Toast component

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// Add debugging for button clicks
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up event listeners');
    
    // Debug: check if the button exists
    const confirmButton = document.querySelector('.btn-confirm');
    if (confirmButton) {
        console.log('Confirm button found:', confirmButton);
        
        // Add additional event listener as backup
        confirmButton.addEventListener('click', function(e) {
            console.log('Confirm button clicked via event listener');
            e.preventDefault();
            processCheckout();
        });
    } else {
        console.error('Confirm button not found!');
    }
    
    // Check if all required variables are defined
    console.log('JavaScript variables check:');
    console.log('CSRF_TOKEN exists:', typeof CSRF_TOKEN !== 'undefined');
    console.log('USER_BALANCE exists:', typeof USER_BALANCE !== 'undefined');
    console.log('TOTAL_PRICE exists:', typeof TOTAL_PRICE !== 'undefined');
    console.log('DEFAULT_ADDRESS exists:', typeof DEFAULT_ADDRESS !== 'undefined');
});