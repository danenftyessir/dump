// seller orders management JavaScript

// state untuk menyimpan data order yang sedang dilihat
let currentOrderData = null;

// fungsi untuk membuka modal detail pesanan (AJAX)
function viewOrderDetail(orderId) {
    const modal = document.getElementById('orderDetailModal');
    const content = document.getElementById('orderDetailContent');
    
    // tampilkan modal dengan loading state
    modal.classList.add('active');
    content.innerHTML = `
        <div class="loading-spinner">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
            </svg>
            <p>Memuat Detail Pesanan...</p>
        </div>
    `;
    
    // fetch data pesanan via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/seller/orders/detail?order_id=${orderId}`, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    currentOrderData = response.data;
                    renderOrderDetail(response.data);
                } else {
                    showError(response.message || 'Gagal memuat detail pesanan');
                }
            } catch (e) {
                showError('Terjadi kesalahan saat memproses data');
            }
        } else {
            showError('Gagal menghubungi server');
        }
    };
    
    xhr.onerror = function() {
        showError('Terjadi kesalahan jaringan');
    };
    
    xhr.send();
}

// fungsi untuk render detail pesanan ke modal
function renderOrderDetail(order) {
    const content = document.getElementById('orderDetailContent');
    
    // format tanggal
    const orderDate = new Date(order.created_at);
    const formattedDate = orderDate.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // status labels
    const statusLabels = {
        'waiting_approval': 'Menunggu Konfirmasi',
        'approved': 'Dikonfirmasi',
        'on_delivery': 'Dalam Pengiriman',
        'received': 'Selesai',
        'rejected': 'Ditolak'
    };
    
    // render items
    let itemsHTML = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            const imagePath = item.main_image_path || '/asset/product-placeholder.jpg';
            itemsHTML += `
                <div class="order-item">
                    <div class="item-image">
                        <img src="${imagePath}" alt="${escapeHtml(item.product_name)}">
                    </div>
                    <div class="item-info">
                        <div class="item-name">${escapeHtml(item.product_name)}</div>
                        <div class="item-quantity">${item.quantity}x @ Rp ${formatPrice(item.price_at_order)}</div>
                    </div>
                    <div class="item-price">
                        Rp ${formatPrice(item.subtotal)}
                    </div>
                </div>
            `;
        });
    }
    
    // render action buttons berdasarkan status
    let actionsHTML = '';
    if (order.status === 'waiting_approval') {
        actionsHTML = `
            <button class="btn-action btn-approve" onclick="approveOrder(${order.order_id})">
                Terima Pesanan
            </button>
            <button class="btn-action btn-reject" onclick="showRejectForm(${order.order_id})">
                Tolak Pesanan
            </button>
        `;
    } else if (order.status === 'approved') {
        actionsHTML = `
            <button class="btn-action btn-ship" onclick="showShipForm(${order.order_id})">
                Kirim Pesanan
            </button>
        `;
    }
    
    // render reject reason jika ditolak
    let rejectReasonHTML = '';
    if (order.status === 'rejected' && order.reject_reason) {
        rejectReasonHTML = `
            <div class="detail-row">
                <span class="detail-label">Alasan Penolakan:</span>
                <span class="detail-value text-danger">${escapeHtml(order.reject_reason)}</span>
            </div>
        `;
    }
    
    // render delivery time jika approved
    let deliveryTimeHTML = '';
    if (order.delivery_time) {
        const deliveryDate = new Date(order.delivery_time);
        const formattedDeliveryDate = deliveryDate.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        deliveryTimeHTML = `
            <div class="detail-row">
                <span class="detail-label">Estimasi Pengiriman:</span>
                <span class="detail-value">${formattedDeliveryDate}</span>
            </div>
        `;
    }
    
    content.innerHTML = `
        <div class="order-detail-grid">
            <div class="detail-section">
                <h3 class="detail-section-title">Informasi Pesanan</h3>
                <div class="detail-row">
                    <span class="detail-label">ID Pesanan:</span>
                    <span class="detail-value">#${String(order.order_id).padStart(6, '0')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal:</span>
                    <span class="detail-value">${formattedDate}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-${order.status}">
                            ${statusLabels[order.status] || order.status}
                        </span>
                    </span>
                </div>
                ${deliveryTimeHTML}
                ${rejectReasonHTML}
            </div>
            
            <div class="detail-section">
                <h3 class="detail-section-title">Informasi Pembeli</h3>
                <div class="detail-row">
                    <span class="detail-label">Nama:</span>
                    <span class="detail-value">${escapeHtml(order.buyer_name)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${escapeHtml(order.buyer_email)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat Pengiriman:</span>
                    <span class="detail-value text-left ml-auto max-w-60">
                        ${escapeHtml(order.shipping_address)}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3 class="detail-section-title">Item Pesanan</h3>
            <div class="order-items-list">
                ${itemsHTML}
            </div>
            <div class="detail-row border-t-2 pt-4 mt-4">
                <span class="detail-label text-base font-bold">Total:</span>
                <span class="detail-value text-xl text-success">
                    Rp ${formatPrice(order.total_price)}
                </span>
            </div>
        </div>
        
        ${actionsHTML ? `<div class="modal-actions">${actionsHTML}<button class="btn-action btn-secondary" onclick="closeOrderDetailModal()">Tutup</button></div>` : '<div class="modal-actions"><button class="btn-action btn-secondary" onclick="closeOrderDetailModal()">Tutup</button></div>'}
    `;
}

// fungsi untuk approve order
function approveOrder(orderId) {
    if (!confirm('Apakah Anda yakin ingin menerima pesanan ini?')) {
        return;
    }
    
    // tampilkan form untuk input delivery time
    const content = document.getElementById('orderDetailContent');
    const originalContent = content.innerHTML;
    
    content.innerHTML = `
        <div class="detail-section">
            <h3 class="detail-section-title">Terima Pesanan</h3>
            <p class="mb-5 text-secondary">Masukkan estimasi waktu pengiriman untuk pesanan ini:</p>

            <div class="input-group">
                <label class="input-label" for="deliveryTime">Estimasi Waktu Pengiriman</label>
                <input type="date" id="deliveryTime" class="input-field"
                       min="${new Date().toISOString().split('T')[0]}" required>
                <small class="text-secondary mt-1 visible">
                    Pilih tanggal perkiraan barang sampai ke pembeli
                </small>
            </div>
            
            <div class="modal-actions">
                <button class="btn-action btn-approve" onclick="submitApproveOrder(${orderId})">
                    Konfirmasi
                </button>
                <button class="btn-action btn-secondary" onclick="viewOrderDetail(${orderId})">
                    Batal
                </button>
            </div>
        </div>
    `;
    
    // set minimum date to tomorrow
    const deliveryInput = document.getElementById('deliveryTime');
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    deliveryInput.min = tomorrow.toISOString().split('T')[0];
}

// fungsi untuk submit approve order (AJAX)
function submitApproveOrder(orderId) {
    const deliveryTime = document.getElementById('deliveryTime').value;
    
    if (!deliveryTime) {
        alert('Silakan pilih estimasi waktu pengiriman');
        return;
    }
    
    // kirim request via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/seller/orders/update-status', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert('Pesanan berhasil diterima!');
                    closeOrderDetailModal();
                    // reload halaman untuk update data
                    location.reload();
                } else {
                    alert(response.message || 'Gagal memperbarui status pesanan');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memproses respons');
            }
        } else {
            alert('Gagal menghubungi server');
        }
    };
    
    xhr.onerror = function() {
        alert('Terjadi kesalahan jaringan');
    };
    
    xhr.send(JSON.stringify({
        order_id: orderId,
        status: 'approved',
        delivery_time: deliveryTime
    }));
}

// fungsi untuk menampilkan form reject
function showRejectForm(orderId) {
    const content = document.getElementById('orderDetailContent');
    
    content.innerHTML = `
        <div class="detail-section">
            <h3 class="detail-section-title">Tolak Pesanan</h3>
            <p class="mb-5 text-secondary">Berikan alasan penolakan pesanan ini:</p>

            <div class="input-group">
                <label class="input-label" for="rejectReason">Alasan Penolakan</label>
                <textarea id="rejectReason" class="input-field"
                          placeholder="Contoh: Stok produk habis, produk tidak tersedia lagi, dll."
                          required></textarea>
            </div>
            
            <div class="modal-actions">
                <button class="btn-action btn-reject" onclick="submitRejectOrder(${orderId})">
                    Tolak Pesanan
                </button>
                <button class="btn-action btn-secondary" onclick="viewOrderDetail(${orderId})">
                    Batal
                </button>
            </div>
        </div>
    `;
}

// fungsi untuk submit reject order (AJAX)
function submitRejectOrder(orderId) {
    const rejectReason = document.getElementById('rejectReason').value.trim();
    
    if (!rejectReason) {
        alert('Silakan berikan alasan penolakan');
        return;
    }
    
    if (!confirm('Apakah Anda yakin ingin menolak pesanan ini?')) {
        return;
    }
    
    // kirim request via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/seller/orders/update-status', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert('Pesanan berhasil ditolak');
                    closeOrderDetailModal();
                    // reload halaman untuk update data
                    location.reload();
                } else {
                    alert(response.message || 'Gagal memperbarui status pesanan');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memproses respons');
            }
        } else {
            alert('Gagal menghubungi server');
        }
    };
    
    xhr.onerror = function() {
        alert('Terjadi kesalahan jaringan');
    };
    
    xhr.send(JSON.stringify({
        order_id: orderId,
        status: 'rejected',
        reject_reason: rejectReason
    }));
}

// fungsi untuk menampilkan form ship order
function showShipForm(orderId) {
    if (!confirm('Apakah Anda yakin pesanan sudah siap dikirim?')) {
        return;
    }
    
    // kirim request via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/seller/orders/update-status', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert('Status pesanan berhasil diperbarui menjadi "Dalam Pengiriman"');
                    closeOrderDetailModal();
                    // reload halaman untuk update data
                    location.reload();
                } else {
                    alert(response.message || 'Gagal memperbarui status pesanan');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memproses respons');
            }
        } else {
            alert('Gagal menghubungi server');
        }
    };
    
    xhr.onerror = function() {
        alert('Terjadi kesalahan jaringan');
    };
    
    xhr.send(JSON.stringify({
        order_id: orderId,
        status: 'on_delivery'
    }));
}

// fungsi untuk menutup modal
function closeOrderDetailModal() {
    const modal = document.getElementById('orderDetailModal');
    modal.classList.remove('active');
    currentOrderData = null;
}

// fungsi untuk menampilkan error
function showError(message) {
    const content = document.getElementById('orderDetailContent');
    content.innerHTML = `
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger mb-4">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <h3 class="empty-title">Terjadi Kesalahan</h3>
            <p class="empty-desc">${escapeHtml(message)}</p>
            <div class="mt-6">
                <button class="btn-action btn-secondary" onclick="closeOrderDetailModal()">Tutup</button>
            </div>
        </div>
    `;
}

// utility function untuk escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// utility function untuk format price
function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
}

// event listener untuk menutup modal saat klik di luar
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('orderDetailModal');
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeOrderDetailModal();
        }
    });
    
    // keyboard shortcut untuk menutup modal (ESC)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeOrderDetailModal();
        }
    });
    
    // smooth scroll untuk page load
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// TODO: implementasi real-time notification untuk pesanan baru
// TODO: implementasi export data pesanan ke CSV/Excel
// TODO: implementasi print invoice pesanan