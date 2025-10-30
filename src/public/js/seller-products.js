// =================================================================
// SELLER PRODUCTS MANAGEMENT - JAVASCRIPT
// File ini menangani halaman kelola produk seller
// =================================================================

// global variables
let currentPage = 1;
let totalPages = 1;
let filterState = {
    search: '',
    categoryId: '',
    sortBy: 'created_at',
    sortOrder: 'DESC',
    limit: 10
};
let deleteProductId = null;

// =================================================================
// INISIALISASI
// =================================================================

document.addEventListener('DOMContentLoaded', function() {
    initFilterHandlers();
    loadProducts();
});

// =================================================================
// FILTER HANDLERS
// =================================================================

function initFilterHandlers() {
    // search input dengan debounce
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterState.search = this.value.trim();
            currentPage = 1;
            loadProducts();
        }, 500);
    });

    // category filter
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterState.categoryId = this.value;
            currentPage = 1;
            loadProducts();
        });
    }

    // sort filter
    const sortFilter = document.getElementById('sortFilter');
    if (sortFilter) {
        sortFilter.addEventListener('change', function() {
            const value = this.value;
            if (value === 'name_asc') {
                filterState.sortBy = 'product_name';
                filterState.sortOrder = 'ASC';
            } else if (value === 'name_desc') {
                filterState.sortBy = 'product_name';
                filterState.sortOrder = 'DESC';
            } else if (value === 'price_asc') {
                filterState.sortBy = 'price';
                filterState.sortOrder = 'ASC';
            } else if (value === 'price_desc') {
                filterState.sortBy = 'price';
                filterState.sortOrder = 'DESC';
            } else if (value === 'stock_asc') {
                filterState.sortBy = 'stock';
                filterState.sortOrder = 'ASC';
            } else if (value === 'stock_desc') {
                filterState.sortBy = 'stock';
                filterState.sortOrder = 'DESC';
            } else {
                filterState.sortBy = 'created_at';
                filterState.sortOrder = 'DESC';
            }
            currentPage = 1;
            loadProducts();
        });
    }
}

// =================================================================
// LOAD PRODUCTS
// =================================================================

async function loadProducts() {
    // show loading
    showLoadingState();

    try {
        // build query string
        const params = new URLSearchParams({
            page: currentPage,
            limit: filterState.limit,
            sort_by: filterState.sortBy,
            sort_order: filterState.sortOrder
        });

        if (filterState.search) params.append('search', filterState.search);
        if (filterState.categoryId) params.append('category_id', filterState.categoryId);

        // fetch products
        const response = await fetch(`/api/seller/products?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            renderProducts(data.data.products);
            renderPagination(data.data.pagination);
        } else {
            throw new Error(data.error || 'Gagal memuat produk');
        }

    } catch (error) {
        console.error('Error loading products:', error);
        showErrorState();
    }
}

// =================================================================
// RENDER PRODUCTS
// =================================================================

function renderProducts(products) {
    const tableBody = document.getElementById('productsTableBody');
    const emptyState = document.getElementById('emptyState');
    const loadingState = document.getElementById('loadingState');
    const productsTable = document.getElementById('productsTable');

    // hide loading
    loadingState.style.display = 'none';

    if (!products || products.length === 0) {
        // show empty state
        productsTable.style.display = 'none';
        emptyState.style.display = 'flex';
        return;
    }

    // show table
    emptyState.style.display = 'none';
    productsTable.style.display = 'block';

    // render table rows
    tableBody.innerHTML = products.map(product => createProductRow(product)).join('');
}

function createProductRow(product) {
    const imagePath = product.main_image_path || '/asset/placeholder-product.jpg';
    const price = formatPrice(product.price);
    const status = product.stock > 0 ? 'Tersedia' : 'Habis';
    const statusClass = product.stock > 0 ? 'status-available' : 'status-out';
    const stockClass = product.stock < 10 && product.stock > 0 ? 'stock-low' : '';
    
    // format kategori
    let categoriesText = '-';
    if (product.categories && product.categories.length > 0) {
        categoriesText = product.categories.map(cat => cat.name).join(', ');
    }

    return `
        <tr class="product-row">
            <td class="product-info-cell">
                <div class="product-info">
                    <div class="product-thumbnail">
                        <img 
                            src="${escapeHtml(imagePath)}" 
                            alt="${escapeHtml(product.product_name)}"
                            onerror="this.src='/asset/placeholder-product.jpg'"
                        >
                    </div>
                    <div class="product-details">
                        <h4 class="product-name">${escapeHtml(product.product_name)}</h4>
                        <p class="product-id">ID: ${product.product_id}</p>
                    </div>
                </div>
            </td>
            <td class="category-cell">
                <span class="category-badge">${escapeHtml(categoriesText)}</span>
            </td>
            <td class="price-cell">
                <span class="product-price">Rp ${price}</span>
            </td>
            <td class="stock-cell">
                <span class="product-stock ${stockClass}">${product.stock}</span>
            </td>
            <td class="status-cell">
                <span class="status-badge ${statusClass}">${status}</span>
            </td>
            <td class="action-cell">
                <div class="action-buttons">
                    <button 
                        class="btn-action btn-edit" 
                        onclick="editProduct(${product.product_id})"
                        title="Edit Produk"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <span>Edit</span>
                    </button>
                    <button 
                        class="btn-action btn-delete" 
                        onclick="showDeleteModal(${product.product_id}, '${escapeHtml(product.product_name)}')"
                        title="Hapus Produk"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// =================================================================
// PAGINATION
// =================================================================

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    
    if (!pagination || pagination.total_pages <= 1) {
        container.style.display = 'none';
        return;
    }

    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;

    container.style.display = 'flex';
    container.innerHTML = `
        <div class="pagination-info">
            Halaman ${currentPage} dari ${totalPages} (Total: ${pagination.total_items} Produk)
        </div>
        <div class="pagination-buttons">
            <button 
                class="pagination-btn" 
                onclick="changePage(${currentPage - 1})"
                ${currentPage <= 1 ? 'disabled' : ''}
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <span>Sebelumnya</span>
            </button>
            <button 
                class="pagination-btn" 
                onclick="changePage(${currentPage + 1})"
                ${currentPage >= totalPages ? 'disabled' : ''}
            >
                <span>Selanjutnya</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>
        </div>
    `;
}

function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// =================================================================
// DELETE PRODUCT
// =================================================================

function showDeleteModal(productId, productName) {
    deleteProductId = productId;
    const modal = document.getElementById('deleteModal');
    const message = document.getElementById('deleteModalMessage');
    
    message.textContent = `Apakah Anda yakin ingin menghapus "${productName}"? Tindakan ini tidak dapat dibatalkan.`;
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    deleteProductId = null;
    document.getElementById('deleteModal').style.display = 'none';
}

async function confirmDelete() {
    if (!deleteProductId) return;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.disabled = true;

    try {
        // ambil csrf token
        const csrfResponse = await fetch('/api/csrf-token');
        const csrfData = await csrfResponse.json();
        const csrfToken = csrfData.data.token;

        // delete product
        const response = await fetch('/api/seller/products', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                product_id: deleteProductId
            })
        });

        const data = await response.json();

        if (data.success) {
            closeDeleteModal();
            showToast('Produk Berhasil Dihapus');
            loadProducts();
        } else {
            throw new Error(data.error || 'Gagal menghapus produk');
        }

    } catch (error) {
        console.error('Error deleting product:', error);
        showToast(error.message || 'Gagal menghapus produk', 'error');
    } finally {
        confirmBtn.disabled = false;
    }
}

// =================================================================
// EDIT PRODUCT
// =================================================================

function editProduct(productId) {
    window.location.href = `/seller/products/edit?id=${productId}`;
}

// =================================================================
// UI STATES
// =================================================================

function showLoadingState() {
    document.getElementById('loadingState').style.display = 'flex';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('productsTable').style.display = 'none';
}

function showErrorState() {
    document.getElementById('loadingState').style.display = 'none';
    document.getElementById('emptyState').style.display = 'flex';
    document.getElementById('productsTable').style.display = 'none';
}

// =================================================================
// HELPERS
// =================================================================

function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    toastMessage.textContent = message;
    toast.classList.add('toast-show');

    setTimeout(() => {
        toast.classList.remove('toast-show');
    }, 3000);
}