// state management
let currentPage = 1;
let itemsPerPage = 8;
let totalPages = 1;
let totalItems = 0;
let searchTimeout = null;
let currentFilters = {
    q: '',
    category: '',
    minPrice: '',
    maxPrice: ''
};

document.addEventListener('DOMContentLoaded', function() {
    // load produk pertama kali
    loadProducts();
    // setup event listeners
    setupEventListeners();
});

// setup semua event listeners
function setupEventListeners() {
    // search dengan debounce 500ms
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.q = this.value.trim();
                currentPage = 1;
                loadProducts();
                // TO DO (bonus advanced search) tampil suggestions menggunakan AJAX
                if (this.value.length >= 3) {
                    loadSearchSuggestions(this.value);
                }
            }, 500);
        });
        // hide suggestions saat klik di luar
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target)) {
                hideSuggestions();
            }
        });
    }
    // filter buttons
    const applyFiltersBtn = document.getElementById('applyFilters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }
    const resetFiltersBtn = document.getElementById('resetFilters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }
    // items per page selector
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function() {
            itemsPerPage = parseInt(this.value);
            currentPage = 1;
            loadProducts();
        });
    }
    // pagination buttons
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

// load produk dari server
function loadProducts() {
    showLoading();
    // build query parameters
    const params = new URLSearchParams({
        page: currentPage,
        limit: itemsPerPage
    });
    if (currentFilters.q) params.append('q', currentFilters.q);
    if (currentFilters.category) params.append('category', currentFilters.category);
    if (currentFilters.minPrice) params.append('min_price', currentFilters.minPrice);
    if (currentFilters.maxPrice) params.append('max_price', currentFilters.maxPrice);
    // update URL dengan query params (untuk bookmark dan back button)
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, '', newUrl);

    // TO DO product manage
    // fetch dari endpoint: GET /api/products
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/products?${params.toString()}`, true);
    xhr.onload = function() {
        hideLoading();  
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    renderProducts(response.data.products);
                    updatePagination(response.data.pagination);
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error parsing products:', error);
                showEmptyState();
            }
        } else {
            showEmptyState();
        }
    };

    xhr.onerror = function() {
        hideLoading();
        showNotification('Gagal memuat produk. Silakan coba lagi.', 'error');
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
                        <p class="product-store">
                            <span class="store-icon">🏪</span>
                            ${escapeHtml(product.store_name)}
                        </p>
                    </div>
                </a>
                ${renderAddToCartButton(product)}
            </div>
        `;
    }).join('');
}

// render tombol add to cart (hanya untuk buyer yang login dan produk tersedia)
function renderAddToCartButton(product) {
    // cek apakah user login dan role buyer
    if (!IS_AUTHENTICATED || USER_ROLE !== 'BUYER' || product.stock === 0) {
        return '';
    }
    return `
        <button 
            class="btn-add-to-cart" 
            onclick="addToCart(${product.product_id})"
            data-product-id="${product.product_id}"
        >
            Tambah Ke Keranjang
        </button>
    `;
}

// update pagination controls
function updatePagination(pagination) {
    if (!pagination) return;
    totalPages = pagination.total_pages || 1;
    totalItems = pagination.total_items || 0;
    currentPage = pagination.current_page || 1;

    // update info text
    const start = ((currentPage - 1) * itemsPerPage) + 1;
    const end = Math.min(currentPage * itemsPerPage, totalItems);
    document.getElementById('itemRangeStart').textContent = start;
    document.getElementById('itemRangeEnd').textContent = end;
    document.getElementById('totalItems').textContent = totalItems;

    // update button states
    document.getElementById('btnFirst').disabled = currentPage === 1;
    document.getElementById('btnPrev').disabled = currentPage === 1;
    document.getElementById('btnNext').disabled = currentPage === totalPages;
    document.getElementById('btnLast').disabled = currentPage === totalPages;
    // render page numbers
    renderPageNumbers();
}

// render nomor halaman
function renderPageNumbers() {
    const container = document.getElementById('pageNumbers');
    const maxButtons = 5; // tampilkan maksimal 5 tombol nomor
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    // adjust jika di ujung
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
    loadProducts();
    // scroll ke atas
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function applyFilters() {
    currentFilters.category = document.getElementById('categoryFilter').value;
    currentFilters.minPrice = document.getElementById('minPrice').value;
    currentFilters.maxPrice = document.getElementById('maxPrice').value;
    // validasi rentang harga
    if (currentFilters.minPrice && currentFilters.maxPrice) {
        const min = parseInt(currentFilters.minPrice);
        const max = parseInt(currentFilters.maxPrice);   
        if (min > max) {
            showNotification('Harga minimum tidak boleh lebih besar dari harga maksimum', 'error');
            return;
        }
    }
    currentPage = 1;
    loadProducts();
}

// reset filters
function resetFilters() {
    currentFilters = {
        q: '',
        category: '',
        minPrice: '',
        maxPrice: ''
    };
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    currentPage = 1;
    loadProducts();
}

// TO DO (Bonus - Advanced Search) load search suggestions dgn AJAX
function loadSearchSuggestions(query) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/search-suggestions?q=${encodeURIComponent(query)}`, true);

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success && response.data.length > 0) {
                    renderSuggestions(response.data);
                }
            } catch (error) {
                console.error('Error parsing suggestions:', error);
            }
        }
    };
    xhr.send();
}

// render search suggestions
function renderSuggestions(suggestions) {
    const container = document.getElementById('searchSuggestions');
    container.innerHTML = suggestions.map(item => `
        <div class="suggestion-item" onclick="selectSuggestion('${escapeHtml(item.product_name)}')">
            <img src="${item.main_image_path || '/public/images/placeholder.jpg'}" alt="${escapeHtml(item.product_name)}">
            <div class="suggestion-info">
                <p class="suggestion-name">${escapeHtml(item.product_name)}</p>
                <p class="suggestion-price">Rp ${formatPrice(item.price)}</p>
            </div>
        </div>
    `).join('');

    container.style.display = 'block';
}

function selectSuggestion(productName) {
    document.getElementById('searchInput').value = productName;
    currentFilters.q = productName;
    currentPage = 1;
    loadProducts();
    hideSuggestions();
}

function hideSuggestions() {
    const container = document.getElementById('searchSuggestions');
    if (container) {
        container.style.display = 'none';
    }
}

// add product to cart
function addToCart(productId) {
    // TO DO shopping cart
    // POST /api/cart dengan body: { product_id, quantity: 1 }
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/cart', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showNotification('Produk berhasil ditambahkan ke keranjang', 'success');              
                    // Update cart badge counter
                    updateCartBadge();
                } else {
                    showNotification(response.error || 'Gagal menambahkan ke keranjang', 'error');
                }
            } catch (error) {
                showNotification('Terjadi kesalahan', 'error');
            }
        } else if (xhr.status === 401) {
            showNotification('Silakan login terlebih dahulu', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);
        } else {
            showNotification('Gagal menambahkan ke keranjang', 'error');
        }
    };
    xhr.send(JSON.stringify({
        product_id: productId,
        quantity: 1
    }));
}

// update cart badge counter
function updateCartBadge() {
    // TO DO shopping cart
    // GET /api/cart/count untuk update badge di navbar
    const xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/cart/count', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const badge = document.querySelector('.cart-badge');
                    if (badge) {
                        badge.textContent = response.data.count;
                        badge.style.display = response.data.count > 0 ? 'block' : 'none';
                    }
                }
            } catch (error) {
                console.error('Error updating cart badge:', error);
            }
        }
    };
    xhr.send();
}

function showLoading() {
    document.getElementById('loadingProducts').style.display = 'block';
    document.getElementById('productsGrid').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
}

function hideLoading() {
    document.getElementById('loadingProducts').style.display = 'none';
}

function showEmptyState() {
    document.getElementById('productsGrid').style.display = 'none';
    document.getElementById('emptyState').style.display = 'block';
}

// utility functions
function formatPrice(price) {
    return new Intl.NumberFormat('id-ID').format(price);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    // TO DO implementasi toast notification
    console.log(`[${type.toUpperCase()}] ${message}`);
    alert(message);
}

// load initial state dari URL parameters
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('q')) {
        currentFilters.q = urlParams.get('q');
        document.getElementById('searchInput').value = currentFilters.q;
    }
    if (urlParams.get('category')) {
        currentFilters.category = urlParams.get('category');
        document.getElementById('categoryFilter').value = currentFilters.category;
    }
    if (urlParams.get('min_price')) {
        currentFilters.minPrice = urlParams.get('min_price');
        document.getElementById('minPrice').value = currentFilters.minPrice;
    }
    if (urlParams.get('max_price')) {
        currentFilters.maxPrice = urlParams.get('max_price');
        document.getElementById('maxPrice').value = currentFilters.maxPrice;
    }
    if (urlParams.get('page')) {
        currentPage = parseInt(urlParams.get('page'));
    }   
    if (urlParams.get('limit')) {
        itemsPerPage = parseInt(urlParams.get('limit'));
        document.getElementById('itemsPerPage').value = itemsPerPage;
    }
});