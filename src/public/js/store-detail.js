// state management untuk produk toko
let currentPage = 1;
let itemsPerPage = 8;
let totalPages = 1;
let totalItems = 0;
let searchTimeout = null;
let currentFilters = {
    q: '',
    category: '',
    sortBy: ''
};

document.addEventListener('DOMContentLoaded', function() {
    // load produk toko pertama kali
    loadStoreProducts();
    // setup event listeners
    setupEventListeners();
    // update total products display
    updateTotalProductsDisplay();
});

// setup semua event listeners
function setupEventListeners() {
    // search dengan debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.q = this.value.trim();
                currentPage = 1;
                loadStoreProducts();
            }, 500);
        });
    }
    // filter kategori
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            currentFilters.category = this.value;
            currentPage = 1;
            loadStoreProducts();
        });
    }
    // sort by
    const sortBySelect = document.getElementById('sortBy');
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function() {
            currentFilters.sortBy = this.value;
            currentPage = 1;
            loadStoreProducts();
        });
    }
    // pagination controls
    setupPaginationListeners();
}

// setup pagination event listeners
function setupPaginationListeners() {
    const btnFirst = document.getElementById('btnFirst');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const btnLast = document.getElementById('btnLast');
    if (btnFirst) btnFirst.addEventListener('click', () => goToPage(1));
    if (btnPrev) btnPrev.addEventListener('click', () => goToPage(currentPage - 1));
    if (btnNext) btnNext.addEventListener('click', () => goToPage(currentPage + 1));
    if (btnLast) btnLast.addEventListener('click', () => goToPage(totalPages));
}

// load produk dari toko
function loadStoreProducts() {
    if (typeof STORE_ID === 'undefined') {
        console.error('STORE_ID tidak terdefinisi');
        return;
    }
    showLoading();
    // build query parameters
    const params = new URLSearchParams({
        store_id: STORE_ID,
        page: currentPage,
        limit: itemsPerPage
    });
    if (currentFilters.q) params.append('q', currentFilters.q);
    if (currentFilters.category) params.append('category', currentFilters.category);
    if (currentFilters.sortBy) params.append('sort', currentFilters.sortBy);

    // fetch produk
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/products?${params.toString()}`, true);
    xhr.onload = function() {
        hideLoading();
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.data.products) {
                    renderProducts(response.data.products);
                    updatePagination(response.data.pagination);
                    updateTotalProductsDisplay(response.data.pagination.total_items);
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error parsing products:', error);
                showEmptyState();
            }
        } else {
            showEmptyState();
            showNotification('Gagal memuat produk', 'error');
        }
    };
    xhr.onerror = function() {
        hideLoading();
        showEmptyState();
        showNotification('Terjadi kesalahan jaringan', 'error');
    };
    xhr.send();
}

// render produk ke grid
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    if (!products || products.length === 0) {
        grid.innerHTML = '';
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    grid.style.display = 'grid';
    emptyState.style.display = 'none';
    grid.innerHTML = products.map(product => {
        const isOutOfStock = product.stock === 0;
        const cardClass = isOutOfStock ? 'product-card out-of-stock' : 'product-card';

        return `
            <div class="${cardClass}">
                <a href="/product/${product.product_id}">
                    <div class="product-image">
                        <img 
                            src="${product.main_image_path || '/public/images/placeholder.jpg'}" 
                            alt="${escapeHtml(product.product_name)}"
                            loading="lazy"
                        >
                        ${isOutOfStock ? '<div class="out-of-stock-badge">Stok Habis</div>' : ''}
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">${escapeHtml(product.product_name)}</h3>
                        <p class="product-price">Rp ${formatPrice(product.price)}</p>
                        ${product.stock > 0 ? `<p class="product-stock">Stok: ${product.stock}</p>` : ''}
                    </div>
                </a>
            </div>
        `;
    }).join('');
}

// update pagination controls
function updatePagination(pagination) {
    if (!pagination) return;
    totalPages = pagination.total_pages || 1;
    totalItems = pagination.total_items || 0;
    currentPage = pagination.current_page || 1;

    // update pagination info
    const start = ((currentPage - 1) * itemsPerPage) + 1;
    const end = Math.min(currentPage * itemsPerPage, totalItems);
    const paginationInfo = document.querySelector('.pagination-info');
    if (paginationInfo) {
        paginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari ${totalItems} Produk`;
    }

    // update button states
    const btnFirst = document.getElementById('btnFirst');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const btnLast = document.getElementById('btnLast');
    if (btnFirst) btnFirst.disabled = currentPage === 1;
    if (btnPrev) btnPrev.disabled = currentPage === 1;
    if (btnNext) btnNext.disabled = currentPage === totalPages;
    if (btnLast) btnLast.disabled = currentPage === totalPages;
    renderPageNumbers();
}

// render nomor halaman
function renderPageNumbers() {
    const container = document.getElementById('pageNumbers');
    if (!container) return;
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    let html = '';
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage ? 'active' : '';
        html += `
            <button 
                class="btn-page-number ${isActive}" 
                onclick="goToPage(${i})"
                ${i === currentPage ? 'disabled' : ''}
            >
                ${i}
            </button>
        `;
    }
    container.innerHTML = html;
}

// navigasi ke halaman tertentu
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    loadStoreProducts();
    // smooth scroll ke atas
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// update display total products
function updateTotalProductsDisplay(count) {
    const display = document.getElementById('totalProductsDisplay');
    if (display) {
        const productCount = count !== undefined ? count : totalItems;
        display.textContent = `${productCount} Produk`;
    }
}

function showLoading() {
    const loadingState = document.getElementById('loadingProducts');
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    if (loadingState) loadingState.style.display = 'block';
    if (productsGrid) productsGrid.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
}

function hideLoading() {
    const loadingState = document.getElementById('loadingProducts');
    if (loadingState) loadingState.style.display = 'none';
}

function showEmptyState() {
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    const paginationControls = document.getElementById('paginationControls');
    if (productsGrid) productsGrid.style.display = 'none';
    if (emptyState) emptyState.style.display = 'block';
    if (paginationControls) paginationControls.style.display = 'none';
}

function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    // TO DO buat implementasi toast notificationnya, sementara alert buat error
    console.log(`[${type.toUpperCase()}] ${message}`);    
    if (type === 'error') {
        alert(message);
    }
}