// State management
let currentStatus = '';
let currentSort = 'desc';

// Initialize from URL params on page load
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('status')) {
    currentStatus = urlParams.get('status');
}
if (urlParams.has('sort')) {
    currentSort = urlParams.get('sort');
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeSortControl();
    updateActiveTab();
    updateSortSelect();
});

function updateActiveTab() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    filterTabs.forEach(tab => {
        const tabStatus = tab.getAttribute('data-status') || '';
        if (tabStatus === currentStatus) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
}

function updateSortSelect() {
    const sortSelect = document.getElementById('sortOrder');
    if (sortSelect) {
        sortSelect.value = currentSort;
    }
}

function initializeFilters() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get status from data attribute
            currentStatus = this.getAttribute('data-status') || '';
            
            // Load orders with new filter
            loadOrders();
            
            return false;
        });
    });
}

function initializeSortControl() {
    const sortSelect = document.getElementById('sortOrder');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value;
            loadOrders();
        });
    }
}

function loadOrders() {
    showLoading();
    
    // Build query params
    const params = new URLSearchParams();
    if (currentStatus && currentStatus !== 'all') {
        params.append('status', currentStatus);
    }
    params.append('sort', currentSort);
    
    console.log('Loading orders with params:', { status: currentStatus, sort: currentSort });
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/orders?${params.toString()}`, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoading();
            
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('Received data:', data);
                    
                    if (data.success) {
                        console.log('Rendering', data.orders.length, 'orders');
                        renderOrders(data.orders);
                    } else {
                        Toast.error('Error!', data.message || 'Gagal memuat data orders');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    Toast.error('Error!', 'Terjadi kesalahan saat memproses data');
                }
            } else {
                console.error('HTTP error! status:', xhr.status);
                Toast.error('Error!', 'Terjadi kesalahan saat memuat data');
            }
        }
    };
    
    xhr.onerror = function() {
        hideLoading();
        console.error('Network error occurred');
        Toast.error('Error!', 'Terjadi kesalahan jaringan');
    };
    
    xhr.send();
}

function renderOrders(orders) {
    const container = document.getElementById('ordersContainer');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="8" cy="21" r="1"/>
                            <circle cx="19" cy="21" r="1"/>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                        </svg>
                </div>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki riwayat pesanan. Mulai belanja sekarang!</p>
                <a href="/products" class="btn-shop">Mulai Belanja</a>
            </div>
        `;
        return;
    }
    
    let html = '';
    orders.forEach(order => {
        html += renderOrderCard(order);
    });
    
    container.innerHTML = html;
}

function renderOrderCard(order) {
    const statusText = {
        'waiting_approval': 'Waiting Approval',
        'approved': 'Approved',
        'on_delivery': 'On Delivery',
        'received': 'Received',
        'rejected': 'Rejected'
    };
    
    let isDeliveryOverdue = false;
    if (order.status === 'on_delivery' && order.delivery_time) {
        const deliveryTime = new Date(order.delivery_time).getTime();
        isDeliveryOverdue = Date.now() > deliveryTime;
    }
    
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <div class="order-item">
                    <img 
                        src="${escapeHtml(item.main_image_path || '/asset/images/default-product.svg')}" 
                        alt="Product"
                        class="item-thumbnail"
                        onerror="this.src='/asset/images/default-product.svg'"
                    >
                    <div class="item-details">
                        <div class="item-name">${escapeHtml(item.product_name)}</div>
                        <div class="item-quantity">Qty: ${item.quantity} × Rp ${formatRupiah(item.price_at_order)}</div>
                    </div>
                    <div class="item-price">
                        Rp ${formatRupiah(item.subtotal)}
                    </div>
                </div>
            `;
        });
    } else {
        itemsHtml = `
            <div class="order-item">
                <div class="item-details">
                    <div class="item-name">Tidak ada item dalam pesanan ini</div>
                </div>
            </div>
        `;
    }
    
    let refundInfoHtml = '';
    if (order.status === 'rejected') {
        refundInfoHtml = `
            <div class="refund-info">
                <div class="refund-amount">
                    Dana Dikembalikan: Rp ${formatRupiah(order.total_price)}
                </div>
                <div class="refund-reason">
                    <strong>Alasan Penolakan:</strong><br>
                    ${escapeHtml(order.reject_reason || 'Tidak ada alasan yang diberikan')}
                </div>
            </div>
        `;
    }
    
    let deliveryAlertHtml = '';
    if (isDeliveryOverdue) {
        deliveryAlertHtml = `
            <div class="delivery-alert">
                <strong>Waktu Pengiriman Terlampaui</strong><br>
                Estimasi pengiriman: ${formatDate(order.delivery_time)}<br>
                Silakan konfirmasi jika barang sudah diterima.
            </div>
        `;
    }
    
    let confirmButtonHtml = '';
    if (isDeliveryOverdue) {
        confirmButtonHtml = `
            <button class="btn btn-confirm" onclick="confirmDelivery(${order.order_id})">
                Konfirmasi Diterima
            </button>
        `;
    }
    
    return `
        <div class="order-card">
            <!-- Order Header -->
            <div class="order-header">
                <div class="order-meta">
                    <span class="order-id">Order #${order.order_id}</span>
                    <span class="order-date">
                        ${formatDate(order.created_at)}
                    </span>
                </div>
                <span class="status-badge status-${order.status}">
                    ${statusText[order.status] || order.status}
                </span>
            </div>

            <!-- Store Info -->
            <div class="store-info">
                <img 
                    src="${escapeHtml(order.store_logo_path || '/asset/images/default-store.svg')}" 
                    alt="Store Logo"
                    class="store-logo"
                    onerror="this.src='/asset/images/default-store.svg'"
                >
                <span class="store-name">${escapeHtml(order.store_name)}</span>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                ${itemsHtml}
            </div>

            ${refundInfoHtml}
            ${deliveryAlertHtml}

            <!-- Order Footer -->
            <div class="order-footer">
                <div class="order-total">
                    Total: <span class="total-amount">Rp ${formatRupiah(order.total_price)}</span>
                </div>
                <div class="order-actions">
                    <button class="btn btn-detail" onclick='showOrderDetail(${JSON.stringify(order).replace(/'/g, "&apos;")})'>
                        Detail Order
                    </button>
                    ${confirmButtonHtml}
                </div>
            </div>
        </div>
    `;
}

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
                <img src="${item.main_image_path || '/asset/images/default-product.svg'}" 
                        alt="Product" class="item-thumbnail"
                        onerror="this.src='/asset/images/default-product.svg'">
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

// Confirmation modal functions
let pendingOrderId = null;

function showConfirmModal(orderId) {
    pendingOrderId = orderId;
    const modal = document.getElementById('confirmModal');
    modal.classList.add('active');
}

function closeConfirmModal() {
    pendingOrderId = null;
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');
}

function confirmDelivery(orderId) {
    // Show custom modal instead of browser confirm
    showConfirmModal(orderId);
}

async function proceedConfirmDelivery() {
    if (!pendingOrderId) return;

    const confirmBtn = document.getElementById('confirmDeliveryBtn');
    const cancelBtn = document.getElementById('cancelDeliveryBtn');
    
    confirmBtn.disabled = true;
    cancelBtn.disabled = true;
    confirmBtn.textContent = 'Memproses...';

    showLoading();

    const csrfToken = getCsrfToken();

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/orders/${pendingOrderId}/confirm`, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoading();
            
            // Reset buttons
            confirmBtn.disabled = false;
            cancelBtn.disabled = false;
            confirmBtn.textContent = 'Ya, Saya Sudah Terima';
            
            if (xhr.status === 200) {
                const contentType = xhr.getResponseHeader('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        closeConfirmModal();
                        
                        if (data.success) {
                            Toast.success('Berhasil!', data.message || 'Pesanan telah diselesaikan. Terima kasih!');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Toast.error('Gagal!', data.message || 'Gagal konfirmasi order');
                        }
                    } catch (e) {
                        closeConfirmModal();
                        console.error('JSON Parse Error:', e);
                        Toast.error('Error!', 'Terjadi kesalahan saat memproses data');
                    }
                } else {
                    closeConfirmModal();
                    console.error('Server returned non-JSON response');
                    Toast.error('Error!', 'Server returned non-JSON response');
                }
            } else {
                closeConfirmModal();
                console.error('HTTP error! status:', xhr.status);
                
                let errorMessage = 'Terjadi kesalahan';
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    errorMessage = `HTTP error! status: ${xhr.status}`;
                }
                
                Toast.error('Error!', errorMessage);
            }
        }
    };
    
    xhr.onerror = function() {
        hideLoading();
        closeConfirmModal();
        
        confirmBtn.disabled = false;
        cancelBtn.disabled = false;
        confirmBtn.textContent = 'Ya, Saya Sudah Terima';
        
        console.error('Network error occurred');
        Toast.error('Error!', 'Terjadi kesalahan jaringan');
    };
    
    xhr.send();
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

// showToast function is now provided by toast.js component

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

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirmModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
        closeConfirmModal();
    }
});