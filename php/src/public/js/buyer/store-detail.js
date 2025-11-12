const APP_CONFIG = window.APP_CONFIG || {};
const IS_LOGGED_IN = APP_CONFIG.isLoggedIn || false;
const IS_BUYER = APP_CONFIG.isBuyer || false;
const CSRF_TOKEN = APP_CONFIG.csrfToken || '';

// global state
let currentPage = 1;
let totalPages = 1;
let isLoading = false;
let searchTimeout = null;
let STORE_ID = null;

// filter state
const filterState = {
    search: '',
    categoryId: '',
    minPrice: '',
    maxPrice: '',
    sortBy: 'created_at',
    sortOrder: 'DESC',
    limit: 12
};

document.addEventListener('DOMContentLoaded', function() {
    // Ambil ID Toko dari elemen span di view
    const storeDataEl = document.getElementById('store-data');
    if (storeDataEl) {
        STORE_ID = storeDataEl.dataset.storeId;
        initializeEventListeners();
        loadProducts(true); // Muat produk pertama kali
    } else {
        console.error("Kesalahan kritis: ID Toko tidak ditemukan di halaman.");
        showError("Gagal memuat informasi toko.");
    }
});

// mendaftarkan event listener
function initializeEventListeners() {
    const searchInput = document.getElementById('mainSearchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const sortBySelect = document.getElementById('sortBy');
    const resetFiltersBtn = document.getElementById('resetFiltersButton');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterState.search = e.target.value.trim();
                currentPage = 1;
                loadProducts();
            }, 500);
        });
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', () => { 
            currentPage = 1; 
            loadProducts(); 
        });
    }
    
    if (minPriceInput) {
        minPriceInput.addEventListener('change', () => { 
            currentPage = 1; 
            loadProducts(); 
        });
    }
    
    if (maxPriceInput) {
        maxPriceInput.addEventListener('change', () => { 
            currentPage = 1; 
            loadProducts(); 
        });
    }
    
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            filterState.sortBy = selectedOption.value;
            filterState.sortOrder = selectedOption.dataset.order || 'DESC';
            currentPage = 1;
            loadProducts();
        });
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }

    // items per page
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            filterState.limit = parseInt(e.target.value);
            currentPage = 1;
            loadProducts();
        });
    }

    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');
    
    if (btnPrevPage) btnPrevPage.addEventListener('click', () => changePage('prev'));
    if (btnNextPage) btnNextPage.addEventListener('click', () => changePage('next'));
}

function loadProducts(isInitialLoad = false) {
    if (isLoading || !STORE_ID) return;
    isLoading = true;

    if (isInitialLoad) {
        showLoadingState();
    }

    // collect filter values safely
    const categoryFilter = document.getElementById('categoryFilter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const searchInput = document.getElementById('mainSearchInput');
    const sortSelect = document.getElementById('sortBy');
    
    filterState.categoryId = categoryFilter ? categoryFilter.value : '';
    filterState.minPrice = minPriceInput ? minPriceInput.value : '';
    filterState.maxPrice = maxPriceInput ? maxPriceInput.value : '';
    filterState.search = searchInput ? searchInput.value : '';

    if (sortSelect && sortSelect.selectedIndex >= 0) {
        const selectedSort = sortSelect.options[sortSelect.selectedIndex];
        filterState.sortBy = selectedSort.value;
        filterState.sortOrder = selectedSort.dataset.order || 'DESC';
    }

    // build query parameters
    const params = new URLSearchParams({
        page: currentPage,
        limit: filterState.limit,
        sort_by: filterState.sortBy,
        sort_order: filterState.sortOrder
    });

    if (filterState.search) params.append('search', filterState.search);
    if (filterState.categoryId) params.append('category_id', filterState.categoryId);
    if (filterState.minPrice) params.append('min_price', filterState.minPrice);
    if (filterState.maxPrice) params.append('max_price', filterState.maxPrice);

    const queryString = params.toString();
    
    const url = `/store/${STORE_ID}?${queryString}`;
    window.history.pushState({ path: url }, '', url);

    // make and send AJAX request
    const apiUrl = `/api/store/${STORE_ID}/products?${queryString}`;
    const xhr = new XMLHttpRequest();
    xhr.open("GET", apiUrl, true);

    xhr.onload = function() {
        isLoading = false;
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    renderProducts(data.data.products);
                    updatePagination(data.data.pagination);
                    updateProductsCount(data.data.pagination.total_items);
                } else {
                    showError(data.message || 'Gagal memuat data produk.');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                showError('Gagal memproses respons dari server.');
            }
        } else {
            showError(`Gagal mengambil data. Status: ${xhr.status}`);
        }
    };

    xhr.onerror = function() {
        isLoading = false;
        showError('Gagal terhubung ke server.');
    };

    xhr.send();
}

function renderProducts(products) {
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    
    hideAllStates();

    if (!products || products.length === 0) {
        emptyState.style.display = 'block';
        return;
    }

    productsGrid.style.display = 'grid';
    
    // Bersihkan grid dan isi dengan kartu produk dari template
    productsGrid.innerHTML = '';
    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
    
    initializeAddToCartButtons();
}

// Buat kartu produk menggunakan template HTML
function createProductCard(product) {
    // Ambil template dari HTML
    const template = document.getElementById('productCardTemplate');
    const clone = template.content.cloneNode(true);
    
    // Data produk
    const price = formatPrice(product.price);
    const imagePath = product.main_image_path || '/asset/placeholder-product.jpg';
    const stockText = product.stock > 0 ? `Stok: ${product.stock}` : 'Stok Habis';
    
    // Ambil elemen dari template
    const cardElement = clone.querySelector('.product-card');
    const imageElement = clone.querySelector('.product-image');
    const badgeElement = clone.querySelector('.product-badge');
    const nameElement = clone.querySelector('.product-name');
    const storeElement = clone.querySelector('.product-store');
    const priceElement = clone.querySelector('.product-price');
    const stockElement = clone.querySelector('.product-stock');
    
    // Set class untuk out of stock
    if (product.stock === 0) {
        cardElement.classList.add('out-of-stock');
    }
    
    // Set click handler
    cardElement.onclick = () => goToProduct(product.product_id);
    
    // Isi data ke template
    imageElement.src = imagePath;
    imageElement.alt = product.product_name;
    imageElement.onerror = function() { this.src = '/asset/placeholder-product.jpg'; };
    
    nameElement.textContent = product.product_name;
    storeElement.textContent = product.store_name;
    priceElement.textContent = price;
    
    // Set stock text hanya jika ada stok
    if (product.stock > 0) {
        stockElement.textContent = stockText;
    } else {
        stockElement.style.display = 'none';
    }
    
    // Set badge jika diperlukan
    if (product.stock === 0) {
        badgeElement.textContent = 'Stok Habis';
        badgeElement.className = 'product-badge out-of-stock-badge';
        badgeElement.style.display = 'block';
    } else if (product.stock < 10 && product.stock > 0) {
        badgeElement.textContent = 'Stok Terbatas';
        badgeElement.className = 'product-badge';
        badgeElement.style.display = 'block';
    }
    
    return clone;
}

function updatePagination(pagination) {
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;
    const paginationContainer = document.getElementById('paginationContainer');

    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    paginationContainer.style.display = 'flex';

    document.getElementById('btnPrevPage').disabled = (currentPage === 1);
    document.getElementById('btnNextPage').disabled = (currentPage === totalPages);

    const paginationNumbers = document.getElementById('paginationNumbers');
    paginationNumbers.innerHTML = generatePageNumbers();
}

function generatePageNumbers() {
    let html = '';
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    if (startPage > 1) {
        html += `<div class="pagination-number" onclick="goToPage(1)">1</div>`;
        if (startPage > 2) html += `<div class="pagination-number">...</div>`;
    }
    for (let i = startPage; i <= endPage; i++) {
        html += `<div class="pagination-number ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</div>`;
    }
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<div class="pagination-number">...</div>`;
        html += `<div class="pagination-number" onclick="goToPage(${totalPages})">${totalPages}</div>`;
    }
    return html;
}

function changePage(direction) {
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
    }
    loadProducts();
    scrollToTop();
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadProducts();
        scrollToTop();
    }
}

function resetFilters() {
    document.getElementById('mainSearchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sortBy').value = 'created_at';
    document.getElementById('itemsPerPage').value = '12';
    
    // Reset filter state
    filterState.search = '';
    filterState.categoryId = '';
    filterState.minPrice = '';
    filterState.maxPrice = '';
    filterState.sortBy = 'created_at';
    filterState.sortOrder = 'DESC';
    filterState.limit = 12;
    
    currentPage = 1;
    loadProducts();
}

// mendaftarkan event listener ke tombol 'Add to Cart'
function initializeAddToCartButtons() {
    const cartButtons = document.querySelectorAll('.btn-add-to-cart');
    cartButtons.forEach(button => {
        if (button.dataset.listenerAttached) return;

        button.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            handleAddToCartClick(productId, this);
        });
        button.dataset.listenerAttached = true;
    });
}

// menangani klik tombol 'Add to Cart'
function handleAddToCartClick(productId, buttonElement) {
    buttonElement.disabled = true;
    const originalIcon = buttonElement.innerHTML;
    buttonElement.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner-cart"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>';

    const data = "product_id=" + encodeURIComponent(productId) + 
                 "&quantity=1" + // Default tambah 1 dari grid
                 "&_token=" + encodeURIComponent(CSRF_TOKEN);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/cart/add', true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-CSRF-TOKEN", CSRF_TOKEN); // Kirim token CSRF
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showToast(response.message, 'success');
                updateCartBadge(response.data.total_items);
            } else {
                showToast(response.message || 'Gagal menambahkan produk.', 'error');
            }
        } else {
            showToast('Gagal menambahkan ke keranjang.', 'error');
        }
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalIcon;
    };

    xhr.onerror = function() {
        showToast('Gagal terhubung ke server.', 'error');
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalIcon;
    };

    xhr.send(data);
}

function showLoadingState() {
    const loadingState = document.getElementById('loadingState');
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (loadingState) loadingState.style.display = 'block';
    if (productsGrid) productsGrid.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';
}

function hideAllStates() {
    const loadingState = document.getElementById('loadingState');
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (loadingState) loadingState.style.display = 'none';
    if (productsGrid) productsGrid.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
}

function showError(message) {
    hideAllStates();
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'block';
        const h3Element = emptyState.querySelector('h3');
        const pElement = emptyState.querySelector('p');
        if (h3Element) h3Element.textContent = 'Terjadi Kesalahan';
        if (pElement) pElement.textContent = message;
    }
}

function updateProductsCount(total) {
    const productsCountEl = document.getElementById('productsCount');
    if (productsCountEl) {
        productsCountEl.textContent = `Menampilkan ${total} Produk`;
    }
}

function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }
function formatPrice(price) { return 'Rp ' + parseInt(price).toLocaleString('id-ID'); }
function escapeHtml(text) {
    if (!text) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => { toast.classList.add('show'); }, 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 500); 
    }, 3000);
}

function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

function goToProduct(productId) {
    window.location.href = `/product/${productId}`;
}