<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Seller Dashboard</title>
    <link rel="stylesheet" href="/css/sellerOrders.css">
    <link rel="stylesheet" href="/css/base.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Kelola Pesanan</h1>
            <p>Kelola semua pesanan yang masuk untuk toko Anda</p>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <a href="/seller/orders" class="status-tab <?= empty($currentStatus) ? 'active' : '' ?>">
                Semua
                <span class="status-count"><?= array_sum($statusCounts) ?></span>
            </a>
            <a href="/seller/orders?status=waiting_approval" class="status-tab <?= $currentStatus === 'waiting_approval' ? 'active' : '' ?>">
                Menunggu Persetujuan
                <span class="status-count"><?= $statusCounts['waiting_approval'] ?></span>
            </a>
            <a href="/seller/orders?status=approved" class="status-tab <?= $currentStatus === 'approved' ? 'active' : '' ?>">
                Disetujui
                <span class="status-count"><?= $statusCounts['approved'] ?></span>
            </a>
            <a href="/seller/orders?status=on_delivery" class="status-tab <?= $currentStatus === 'on_delivery' ? 'active' : '' ?>">
                Dalam Pengiriman
                <span class="status-count"><?= $statusCounts['on_delivery'] ?></span>
            </a>
            <a href="/seller/orders?status=received" class="status-tab <?= $currentStatus === 'received' ? 'active' : '' ?>">
                Diterima
                <span class="status-count"><?= $statusCounts['received'] ?></span>
            </a>
            <a href="/seller/orders?status=rejected" class="status-tab <?= $currentStatus === 'rejected' ? 'active' : '' ?>">
                Ditolak
                <span class="status-count"><?= $statusCounts['rejected'] ?></span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form class="search-form" method="GET" action="/seller/orders">
                <?php if ($currentStatus): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
                <?php endif; ?>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Cari berdasarkan Order ID atau Nama Buyer..."
                    value="<?= htmlspecialchars($search) ?>"
                >
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <h3>Tidak Ada Pesanan</h3>
                    <p><?= $currentStatus ? 'Tidak ada pesanan dengan status ini' : 'Belum ada pesanan masuk' ?></p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Tanggal</th>
                            <th>Buyer</th>
                            <th>Produk</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="order-id">#<?= $order['order_id'] ?></td>
                                <td><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <div class="buyer-info"><?= htmlspecialchars($order['buyer_name']) ?></div>
                                    <div class="buyer-email"><?= htmlspecialchars($order['buyer_email']) ?></div>
                                </td>
                                <td>
                                    <div class="product-list">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="product-item">
                                                <?= htmlspecialchars($item['product_name']) ?> (<?= $item['quantity'] ?>x)
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="price">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewDetail(<?= $order['order_id'] ?>)">
                                            Detail
                                        </button>
                                        
                                        <?php if ($order['status'] === 'waiting_approval'): ?>
                                            <button class="btn btn-success btn-sm" onclick="approveOrder(<?= $order['order_id'] ?>)">
                                                Approve
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $order['order_id'] ?>)">
                                                Reject
                                            </button>
                                        <?php elseif ($order['status'] === 'approved'): ?>
                                            <button class="btn btn-primary btn-sm" onclick="showDeliveryModal(<?= $order['order_id'] ?>)">
                                                Kirim Barang
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Prev</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Pesanan</h3>
                <button class="close-btn" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tolak Pesanan</h3>
                <button class="close-btn" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan *</label>
                    <textarea 
                        id="rejectReason" 
                        class="form-control" 
                        placeholder="Masukkan alasan penolakan pesanan..."
                        required
                    ></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('rejectModal')">Batal</button>
                <button class="btn btn-danger" onclick="confirmReject()">Tolak Pesanan</button>
            </div>
        </div>
    </div>

    <!-- Delivery Modal -->
    <div id="deliveryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Kirim Barang</h3>
                <button class="close-btn" onclick="closeModal('deliveryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Estimasi Pengiriman (Hari) *</label>
                    <input 
                        type="number" 
                        id="deliveryDays" 
                        class="form-control" 
                        placeholder="Contoh: 3"
                        min="1"
                        max="30"
                        required
                    >
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deliveryModal')">Batal</button>
                <button class="btn btn-primary" onclick="confirmDelivery()">Kirim Barang</button>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Konfirmasi Persetujuan</h3>
                <button class="close-btn" onclick="closeModal('approveModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin: 0; color: #2c3e50; font-size: 16px; line-height: 1.6;">
                    Apakah Anda yakin ingin menyetujui pesanan ini?<br>
                    <span style="font-size: 14px; color: #7f8c8d;">Pesanan akan diproses dan menunggu pengiriman.</span>
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('approveModal')">Batal</button>
                <button class="btn btn-success" onclick="confirmApprove()">✅ Setujui Pesanan</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Loading Overlay -->
    <div id="loading" class="loading">
        <div class="spinner"></div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $_token ?? '' ?>';
    </script>
    <script src="/js/sellerOrders.js"></script>
</body>
</html>
