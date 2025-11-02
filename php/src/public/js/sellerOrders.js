
let currentOrderId = null;

// View Order Detail
function viewDetail(orderId) {
    console.log('Viewing detail for order:', orderId);
    showLoading();
    
    fetch(`/seller/orders/detail?id=${orderId}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('Content-Type'));
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                hideLoading();
                
                if (data.success) {
                    const order = data.order;
                    let html = `
                    <div class="detail-row">
                        <span class="detail-label">Order ID</span>
                        <span class="detail-value">#${order.order_id}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tanggal</span>
                        <span class="detail-value">${new Date(order.created_at).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Buyer</span>
                        <span class="detail-value">${order.buyer_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">${order.buyer_email}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Alamat Pengiriman</span>
                        <span class="detail-value">${order.shipping_address || '-'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-badge status-${order.status}">
                                ${order.status.replace('_', ' ')}
                            </span>
                        </span>
                    </div>
                    <h4 style="margin: 20px 0 10px; color: #2c3e50;">Produk</h4>
                `;
                
                order.items.forEach(item => {
                    html += `
                        <div class="detail-row">
                            <span class="detail-label">${item.product_name} x${item.quantity}</span>
                            <span class="detail-value">Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</span>
                        </div>
                    `;
                });
                
                html += `
                    <div class="detail-row" style="border-top: 2px solid #2c3e50; margin-top: 10px; padding-top: 10px;">
                        <span class="detail-label" style="font-size: 16px; color: #2c3e50;">Total</span>
                        <span class="detail-value" style="font-size: 18px; font-weight: 700; color: #28a745;">
                            Rp ${parseInt(order.total_price).toLocaleString('id-ID')}
                        </span>
                    </div>
                `;
                
                if (order.reject_reason) {
                    html += `
                        <div class="detail-row">
                            <span class="detail-label">Alasan Penolakan</span>
                            <span class="detail-value">${order.reject_reason}</span>
                        </div>
                    `;
                }
                
                document.getElementById('detailContent').innerHTML = html;
                openModal('detailModal');
            } else {
                showToast(data.message, 'error');
            }
            } catch (e) {
                hideLoading();
                console.error('JSON Parse Error:', e);
                console.error('Response text:', text);
                showToast('Invalid response from server', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            showToast('Terjadi kesalahan: ' + error.message, 'error');
        });
}

// Show Approve Modal
function approveOrder(orderId) {
    currentOrderId = orderId;
    openModal('approveModal');
}

// Confirm Approve
function confirmApprove() {
    showLoading();
    closeModal('approveModal');
    
    const formData = new FormData();
    formData.append('order_id', currentOrderId);
    formData.append('_token', CSRF_TOKEN);
    
    fetch('/seller/orders/approve', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast(data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Terjadi kesalahan', 'error');
    });
}

// Show Reject Modal
function showRejectModal(orderId) {
    currentOrderId = orderId;
    document.getElementById('rejectReason').value = '';
    openModal('rejectModal');
}

// Confirm Reject
function confirmReject() {
    const reason = document.getElementById('rejectReason').value.trim();
    
    if (!reason) {
        alert('Alasan penolakan harus diisi');
        return;
    }
    
    showLoading();
    closeModal('rejectModal');
    
    fetch('/seller/orders/reject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast(data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Terjadi kesalahan', 'error');
    });
}

// Show Delivery Modal
function showDeliveryModal(orderId) {
    currentOrderId = orderId;
    document.getElementById('deliveryDays').value = '';
    openModal('deliveryModal');
}

// Confirm Delivery
function confirmDelivery() {
    const days = document.getElementById('deliveryDays').value;
    
    if (!days || days < 1) {
        alert('Estimasi pengiriman harus diisi');
        return;
    }
    
    showLoading();
    closeModal('deliveryModal');
    
    fetch('/seller/orders/set-delivery', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        body: JSON.stringify({
            order_id: currentOrderId,
            delivery_days: parseInt(days)
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast(data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Terjadi kesalahan', 'error');
    });
}

// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Loading Functions
function showLoading() {
    document.getElementById('loading').classList.add('active');
}

function hideLoading() {
    document.getElementById('loading').classList.remove('active');
}

// Toast Function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});