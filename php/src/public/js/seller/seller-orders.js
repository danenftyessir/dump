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
    xhr.open('GET', `/api/seller/orders/${orderId}`, true);
    
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

// fungsi untuk generate status timeline
function generateStatusTimeline(status, createdAt, confirmAt, deliveryTime, receivedAt, rejectReason) {
    const statusLabels = {
        'waiting_approval': 'Menunggu Konfirmasi',
        'approved': 'Dikonfirmasi',
        'on_delivery': 'Dalam Pengiriman',
        'received': 'Selesai',
        'rejected': 'Ditolak'
    };

    const formatDateTime = (dateStr) => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    let timelineHTML = '';

    // 1. Order Created (always present)
    timelineHTML += `
        <div class="timeline-item active">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <div class="timeline-title">Pesanan Dibuat</div>
                <div class="timeline-date">${formatDateTime(createdAt)}</div>
            </div>
        </div>
    `;

    // 2. Handle different status paths
    if (status === 'rejected') {
        // Rejected path
        timelineHTML += `
            <div class="timeline-item active rejected">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Ditolak</div>
                    <div class="timeline-date">${rejectReason ? `Alasan: ${rejectReason}` : ''}</div>
                </div>
            </div>
        `;
    } else {
        // Normal flow: waiting → approved → on_delivery → received

        // 2. Approved
        const isApproved = ['approved', 'on_delivery', 'received'].includes(status);
        timelineHTML += `
            <div class="timeline-item ${isApproved ? 'active' : ''}">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Dikonfirmasi</div>
                    <div class="timeline-date">${isApproved && confirmAt ? formatDateTime(confirmAt) : ''}</div>
                    ${isApproved && deliveryTime ? `<div class="timeline-info">Est. Pengiriman: ${formatDateTime(deliveryTime)}</div>` : ''}
                </div>
            </div>
        `;

        // 3. On Delivery
        const isOnDelivery = ['on_delivery', 'received'].includes(status);
        timelineHTML += `
            <div class="timeline-item ${isOnDelivery ? 'active' : ''}">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">Dalam Pengiriman</div>
                    <div class="timeline-date">${isOnDelivery ? 'Pesanan sedang dikirim' : ''}</div>
                </div>
            </div>
        `;

        // 4. Received
        const isReceived = status === 'received';
        timelineHTML += `
            <div class="timeline-item ${isReceived ? 'active' : ''}">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-title">Pesanan Diterima</div>
                    <div class="timeline-date">${isReceived && receivedAt ? formatDateTime(receivedAt) : ''}</div>
                </div>
            </div>
        `;
    }

    return timelineHTML;
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

    // Render Status History Timeline
    const statusHistory = generateStatusTimeline(
        order.status,
        order.created_at,
        order.confirmed_at,
        order.delivery_time,
        order.received_at,
        order.reject_reason
    );
    const statusHistoryHTML = `
        <div class="detail-section">
            <h3 class="detail-section-title">Riwayat Status</h3>
            <div class="status-timeline">
                ${statusHistory}
            </div>
        </div>
    `;

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

        ${statusHistoryHTML}

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

    // calculate quick-select dates
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const minDate = tomorrow.toISOString().split('T')[0];

    const threeDays = new Date();
    threeDays.setDate(threeDays.getDate() + 3);
    const threeDaysDate = threeDays.toISOString().split('T')[0];
    const threeDaysFormatted = threeDays.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

    const fiveDays = new Date();
    fiveDays.setDate(fiveDays.getDate() + 5);
    const fiveDaysDate = fiveDays.toISOString().split('T')[0];
    const fiveDaysFormatted = fiveDays.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

    const sevenDays = new Date();
    sevenDays.setDate(sevenDays.getDate() + 7);
    const sevenDaysDate = sevenDays.toISOString().split('T')[0];
    const sevenDaysFormatted = sevenDays.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

    content.innerHTML = `
        <div class="detail-section">
            <h3 class="detail-section-title">Terima Pesanan</h3>
            <p class="mb-5 text-secondary">Masukkan estimasi waktu pengiriman untuk pesanan ini:</p>

            <div class="input-group">
                <label class="input-label" for="deliveryTime">Estimasi Waktu Pengiriman</label>
                <input type="date" id="deliveryTime" class="input-field date-picker-input"
                       min="${minDate}" required>
                <small class="text-secondary mt-1 visible">
                    Pilih tanggal perkiraan barang sampai ke pembeli
                </small>
            </div>

            <!-- Quick Select Buttons -->
            <div class="quick-select-container">
                <span class="quick-select-label">Pilih Cepat:</span>
                <div class="quick-select-buttons">
                    <button type="button" class="btn-quick-select" data-date="${threeDaysDate}">
                        3 Hari<br><small>${threeDaysFormatted}</small>
                    </button>
                    <button type="button" class="btn-quick-select" data-date="${fiveDaysDate}">
                        5 Hari<br><small>${fiveDaysFormatted}</small>
                    </button>
                    <button type="button" class="btn-quick-select" data-date="${sevenDaysDate}">
                        7 Hari<br><small>${sevenDaysFormatted}</small>
                    </button>
                </div>
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

    // add event listeners for quick select buttons
    const quickSelectBtns = document.querySelectorAll('.btn-quick-select');
    const deliveryInput = document.getElementById('deliveryTime');

    quickSelectBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedDate = this.getAttribute('data-date');
            deliveryInput.value = selectedDate;

            // remove active class from all buttons
            quickSelectBtns.forEach(b => b.classList.remove('active'));

            // add active class to clicked button
            this.classList.add('active');
        });
    });

    // remove active class when manual date is selected
    deliveryInput.addEventListener('change', function() {
        quickSelectBtns.forEach(b => b.classList.remove('active'));
    });
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
    xhr.open('POST', `/api/seller/orders/approve/${orderId}`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

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

    // Get CSRF token from meta tag or hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                     document.querySelector('input[name="_token"]')?.value || '';

    xhr.send(`delivery_time=${encodeURIComponent(deliveryTime)}&_token=${encodeURIComponent(csrfToken)}`);
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
    xhr.open('POST', `/api/seller/orders/reject/${orderId}`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

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

    // Get CSRF token from meta tag or hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                     document.querySelector('input[name="_token"]')?.value || '';

    xhr.send(`reject_reason=${encodeURIComponent(rejectReason)}&_token=${encodeURIComponent(csrfToken)}`);
}

// fungsi untuk menampilkan form ship order
function showShipForm(orderId) {
    if (!confirm('Apakah Anda yakin pesanan sudah siap dikirim?')) {
        return;
    }

    // kirim request via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/api/seller/orders/set-delivery/${orderId}`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

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

    // Get CSRF token from meta tag or hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                     document.querySelector('input[name="_token"]')?.value || '';

    xhr.send(`_token=${encodeURIComponent(csrfToken)}`);
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

    // SEARCH FUNCTIONALITY
    const searchInput = document.getElementById('searchInput');
    const btnClearSearch = document.getElementById('btnClearSearch');
    let searchTimeout;

    if (searchInput) {
        // Debounced search (500ms delay)
        searchInput.addEventListener('input', function(e) {
            const searchValue = e.target.value.trim();

            // Show/hide clear button
            if (searchValue) {
                btnClearSearch.style.display = 'block';
            } else {
                btnClearSearch.style.display = 'none';
            }

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Set new timeout
            searchTimeout = setTimeout(function() {
                performSearch(searchValue);
            }, 500);
        });

        // Clear search button
        if (btnClearSearch) {
            btnClearSearch.addEventListener('click', function() {
                searchInput.value = '';
                btnClearSearch.style.display = 'none';
                performSearch('');
            });
        }

        // Enter key to search immediately
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch(searchInput.value.trim());
            }
        });
    }

    function performSearch(searchQuery) {
        // Get current URL params
        const urlParams = new URLSearchParams(window.location.search);

        // Update search param
        if (searchQuery) {
            urlParams.set('search', searchQuery);
        } else {
            urlParams.delete('search');
        }

        // Reset to page 1 when searching
        urlParams.set('page', '1');

        // Redirect to new URL
        window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
    }
});

// TODO: implementasi real-time notification untuk pesanan baru
// TODO: implementasi export data pesanan ke CSV/Excel
// TODO: implementasi print invoice pesanan