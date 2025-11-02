let currentPage = 1;
let totalPages = 1;
let isLoading = false;
let searchTimeout = null;

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

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadProducts();
});

// ============================================
// EVENT LISTENERS
// ============================================

function initializeEventListeners() {
    // search input dengan debounce
    const searchInput = document.getElementById('mainSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterState.search = e.target.value.trim();
                currentPage = 1;
                loadProducts();
            }, 500);
        });

        // enter key untuk search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch();
            }
        });
    }

    // filter kategori
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function(e) {
            filterState.categoryId = e.target.value;
            currentPage = 1;
            loadProducts();
        });
    }

    // filter harga min
    const minPriceInput = document.getElementById('minPrice');
    if (minPriceInput) {
        minPriceInput.addEventListener('change', function(e) {
            filterState.minPrice = e.target.value;
            currentPage = 1;
            loadProducts();
        });
    }

    // filter harga max
    const maxPriceInput = document.getElementById('maxPrice');
    if (maxPriceInput) {
        maxPriceInput.addEventListener('change', function(e) {
            filterState.maxPrice = e.target.value;
            currentPage = 1;
            loadProducts();
        });
    }

    // sort by
    const sortBySelect = document.getElementById('sortBy');
    if (sortBySelect) {
        sortBySelect.addEventListener('change', function(e) {
            filterState.sortBy = e.target.value;
            // harga terendah = ASC, yang lain = DESC
            filterState.sortOrder = e.target.value === 'price' ? 'ASC' : 'DESC';
            currentPage = 1;
            loadProducts();
        });
    }
}

// ============================================
// SEARCH FUNCTION
// ============================================

function performSearch() {
    const searchInput = document.getElementById('mainSearchInput');
    if (searchInput) {
        filterState.search = searchInput.value.trim();
        currentPage = 1;
        loadProducts();
    }
}

// ============================================
// RESET FILTERS
// ============================================

function resetFilters() {
    // reset filter state
    filterState.search = '';
    filterState.categoryId = '';
    filterState.minPrice = '';
    filterState.maxPrice = '';
    filterState.sortBy = 'created_at';
    filterState.sortOrder = 'DESC';

    // reset form inputs
    document.getElementById('mainSearchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sortBy').value = 'created_at';

    // reload products
    currentPage = 1;
    loadProducts();
}

// ============================================
// LOAD PRODUCTS VIA AJAX
// ============================================

function loadProducts() {
    if (isLoading) return;

    isLoading = true;
    showLoadingState();

    // build query string
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

    // fetch products via AJAX
    fetch(`/api/products?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memuat produk');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderProducts(data.data.products);
                updatePagination(data.data.pagination);
                updateProductsCount(data.data.pagination.total_items);
            } else {
                throw new Error(data.error || 'Gagal memuat produk');
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            showError('Gagal memuat produk. Silakan coba lagi.');
        })
        .finally(() => {
            isLoading = false;
        });
}

// ============================================
// RENDER PRODUCTS
// ============================================

function renderProducts(products) {
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    const loadingState = document.getElementById('loadingState');

    // hide loading
    loadingState.style.display = 'none';

    if (!products || products.length === 0) {
        // show empty state
        productsGrid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }

    // show products
    emptyState.style.display = 'none';
    productsGrid.style.display = 'grid';

    // render product cards
    productsGrid.innerHTML = products.map(product => createProductCard(product)).join('');
}

// ============================================
// CREATE PRODUCT CARD
// ============================================

function createProductCard(product) {
    const price = formatPrice(product.price);
    const imagePath = product.main_image_path || '/asset/placeholder-product.jpg';
    const stockText = product.stock > 0 ? `Stok: ${product.stock}` : 'Stok Habis';
    
    return `
        <div class="product-card" onclick="goToProduct(${product.product_id})">
            <div class="product-image-wrapper">
                <img 
                    src="${escapeHtml(imagePath)}" 
                    alt="${escapeHtml(product.product_name)}" 
                    class="product-image"
                    onerror="this.src='/asset/placeholder-product.jpg'"
                >
                ${product.stock < 10 && product.stock > 0 ? '<span class="product-badge">Stok Terbatas</span>' : ''}
            </div>
            <div class="product-content">
                <h3 class="product-name">${escapeHtml(product.product_name)}</h3>
                <p class="product-store">${escapeHtml(product.store_name)}</p>
                <p class="product-price">${price}</p>
                <p class="product-stock">${stockText}</p>
            </div>
        </div>
    `;
}

// ============================================
// UPDATE PAGINATION
// ============================================

function updatePagination(pagination) {
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;

    const paginationContainer = document.getElementById('paginationContainer');
    const paginationNumbers = document.getElementById('paginationNumbers');
    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');

    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';

    // update prev/next buttons
    btnPrevPage.disabled = currentPage === 1;
    btnNextPage.disabled = currentPage === totalPages;

    // render page numbers
    paginationNumbers.innerHTML = generatePageNumbers();
}

// ============================================
// GENERATE PAGE NUMBERS
// ============================================

function generatePageNumbers() {
    let html = '';
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    // first page
    if (startPage > 1) {
        html += `<div class="pagination-number" onclick="goToPage(1)">1</div>`;
        if (startPage > 2) {
            html += `<div class="pagination-number">...</div>`;
        }
    }

    // page numbers
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        html += `<div class="pagination-number ${activeClass}" onclick="goToPage(${i})">${i}</div>`;
    }

    // last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<div class="pagination-number">...</div>`;
        }
        html += `<div class="pagination-number" onclick="goToPage(${totalPages})">${totalPages}</div>`;
    }

    return html;
}

// ============================================
// PAGINATION NAVIGATION
// ============================================

function changePage(direction) {
    if (direction === 'prev' && currentPage > 1) {
        currentPage--;
        loadProducts();
        scrollToTop();
    } else if (direction === 'next' && currentPage < totalPages) {
        currentPage++;
        loadProducts();
        scrollToTop();
    }
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadProducts();
        scrollToTop();
    }
}

// ============================================
// NAVIGATION FUNCTIONS
// ============================================

function goToProduct(productId) {
    window.location.href = `/product/${productId}`;
}

// ============================================
// UI HELPER FUNCTIONS
// ============================================

function showLoadingState() {
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('productsGrid').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
}

function updateProductsCount(total) {
    const countElement = document.getElementById('productsCount');
    if (countElement) {
        countElement.textContent = `Menampilkan ${total} Produk`;
    }
}

function showError(message) {
    const productsGrid = document.getElementById('productsGrid');
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');

    loadingState.style.display = 'none';
    productsGrid.style.display = 'none';
    emptyState.style.display = 'block';

    const emptyTitle = emptyState.querySelector('.empty-title');
    const emptyDescription = emptyState.querySelector('.empty-description');
    
    if (emptyTitle) emptyTitle.textContent = 'Terjadi Kesalahan';
    if (emptyDescription) emptyDescription.textContent = message;
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatPrice(price) {
    return 'Rp ' + parseInt(price).toLocaleString('id-ID');
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