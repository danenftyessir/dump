function showOrderDetail(order) {
    const statusText = {
        'waiting_approval': 'Waiting Approval',
        'approved': 'Approved',
        'on_delivery': 'On Delivery',
        'received': 'Received',
        'rejected': 'Rejected'
    };

    let itemsHtml = '';
    order.items.forEach(item => {
        itemsHtml += `
            <div class="order-item">
                <img src="${item.main_image_path || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'60\' height=\'60\'%3E%3Crect fill=\'%23f0f0f0\' width=\'60\' height=\'60\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'24\'%3E📦%3C/text%3E%3C/svg%3E'}" 
                        alt="Product" class="item-thumbnail">
                <div class="item-details">
                    <div class="item-name">${escapeHtml(item.product_name)}</div>
                    <div class="item-quantity">Qty: ${item.quantity} × Rp ${formatRupiah(item.price_at_order)}</div>
                </div>
                <div class="item-price">Rp ${formatRupiah(item.subtotal)}</div>
            </div>
        `;
    });

    let deliveryInfo = '';
    if (order.status === 'on_delivery' && order.delivery_time) {
        deliveryInfo = `
            <div class="detail-section">
                <h3>Informasi Pengiriman</h3>
                <div class="detail-row">
                    <span class="detail-label">Estimasi Tiba:</span>
                    <span class="detail-value">${formatDate(order.delivery_time)}</span>
                </div>
            </div>
        `;
    }

    let rejectInfo = '';
    if (order.status === 'rejected') {
        rejectInfo = `
            <div class="detail-section">
                <h3>Informasi Penolakan</h3>
                <div class="detail-row">
                    <span class="detail-label">Dana Dikembalikan:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: 700;">Rp ${formatRupiah(order.total_price)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alasan:</span>
                    <span class="detail-value">${escapeHtml(order.reject_reason || 'Tidak ada alasan yang diberikan')}</span>
                </div>
            </div>
        `;
    }

    const modalContent = `
        <div class="detail-section">
            <h3>Informasi Order</h3>
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value"><strong>#${order.order_id}</strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tanggal Order:</span>
                <span class="detail-value">${formatDate(order.created_at)}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="status-badge status-${order.status}">
                        ${statusText[order.status] || order.status}
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Toko:</span>
                <span class="detail-value"><strong>${escapeHtml(order.store_name)}</strong></span>
            </div>
        </div>

        <div class="detail-section">
            <h3>Produk yang Dibeli</h3>
            <div class="order-items">
                ${itemsHtml}
            </div>
        </div>

        <div class="detail-section">
            <h3>Alamat Pengiriman</h3>
            <div class="shipping-address">
                ${escapeHtml(order.shipping_address)}
            </div>
        </div>

        ${deliveryInfo}
        ${rejectInfo}

        <div class="detail-section">
            <h3>Ringkasan Pembayaran</h3>
            <div class="detail-row">
                <span class="detail-label">Total Item:</span>
                <span class="detail-value">${order.item_count} item</span>
            </div>
            <div class="detail-row" style="border-top: 2px solid #e0e0e0; padding-top: 15px; margin-top: 10px;">
                <span class="detail-label"><strong>TOTAL PEMBAYARAN:</strong></span>
                <span class="detail-value" style="color: #00a860; font-size: 20px; font-weight: 700;">
                    Rp ${formatRupiah(order.total_price)}
                </span>
            </div>
        </div>
    `;

    document.getElementById('modalBody').innerHTML = modalContent;
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

function confirmDelivery(orderId) {
    if (!confirm('Apakah Anda yakin barang sudah diterima?')) {
        return;
    }

    showLoading();

    fetch(`/orders/confirm/${orderId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast(data.message || 'Order berhasil dikonfirmasi!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Gagal konfirmasi order', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
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

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    return date.toLocaleDateString('id-ID', options);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});