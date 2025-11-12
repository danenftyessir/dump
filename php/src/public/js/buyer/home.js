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

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadProducts();
});

// initialize event listeners
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

    // items per page
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function(e) {
            filterState.limit = parseInt(e.target.value);
            currentPage = 1;
            loadProducts();
        });
    }

    // pagination buttons
    const btnPrevPage = document.getElementById('btnPrevPage');
    if (btnPrevPage) {
        btnPrevPage.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        });
    }

    const btnNextPage = document.getElementById('btnNextPage');
    if (btnNextPage) {
        btnNextPage.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        });
    }
}

// perform search immediately
function performSearch() {
    const searchInput = document.getElementById('mainSearchInput');
    if (searchInput) {
        filterState.search = searchInput.value.trim();
        currentPage = 1;
        loadProducts();
    }
}

// Fungsi untuk mereset semua filter pencarian dan mengembalikan ke nilai awal
function resetFilters() {
    // Reset nilai filter di state JS
    filterState.search = '';
    filterState.categoryId = '';
    filterState.minPrice = '';
    filterState.maxPrice = '';
    filterState.sortBy = 'created_at';
    filterState.sortOrder = 'DESC';

    // Reset input form di halaman
    document.getElementById('mainSearchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('sortBy').value = 'created_at';
    document.getElementById('itemsPerPage').value = '12';

    // Kembali ke halaman pertama dan muat ulang produk
    currentPage = 1;
    loadProducts();
}

// load products
function loadProducts() {
    if (isLoading) return;

    isLoading = true;
    showLoadingState();

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
    
    const url = window.location.pathname + '?' + queryString;
    window.history.pushState({ path: url }, '', url);

    const xhr = new XMLHttpRequest();
    xhr.open("GET", `/api/products?${queryString}`, true);

    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            // Sukses
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    renderProducts(data.data.products);
                    updatePagination(data.data.pagination);
                    updateProductsCount(data.data.pagination.total_items);
                } else {
                    throw new Error(data.message || 'Gagal memuat produk');
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                showError('Gagal memproses respons server.');
            }
        } else {
            // Error
            console.error('Error loading products:', xhr.statusText);
            showError('Gagal memuat produk. Status: ' + xhr.status);
        }
        
        isLoading = false;
    };

    // Handle network error
    xhr.onerror = function() {
        console.error('Network error occurred');
        showError('Gagal terhubung ke server. Periksa koneksi internet Anda.');
        isLoading = false;
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

// Fungsi untuk mengambil data produk dari server sesuai filter dan pagination
function loadProducts() {
    if (isLoading) return;

    isLoading = true;
    showLoadingState(); // Tampilkan animasi loading

    // Siapkan parameter pencarian untuk API
    const params = new URLSearchParams({
        page: currentPage,
        limit: filterState.limit,
        sort_by: filterState.sortBy,
        sort_order: filterState.sortOrder
    });

    // Tambahkan filter jika ada
    if (filterState.search) params.append('search', filterState.search);
    if (filterState.categoryId) params.append('category_id', filterState.categoryId);
    if (filterState.minPrice) params.append('min_price', filterState.minPrice);
    if (filterState.maxPrice) params.append('max_price', filterState.maxPrice);

    // Update URL di browser agar bisa di-refresh/share
    const queryString = params.toString();
    const url = window.location.pathname + '?' + queryString;
    window.history.pushState({ path: url }, '', url);

    // Request data produk ke API
    const xhr = new XMLHttpRequest();
    xhr.open("GET", `/api/products?${queryString}`, true);

    xhr.onload = function() {
        // Jika request sukses
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    // Tampilkan produk dan update pagination
                    renderProducts(data.data.products);
                    updatePagination(data.data.pagination);
                    updateProductsCount(data.data.pagination.total_items);
                } else {
                    throw new Error(data.message || 'Gagal memuat produk');
                }
            } catch (e) {
                // Error parsing JSON
                console.error('Error parsing JSON:', e);
                showError('Gagal memproses respons server.');
            }
        } else {
            // Jika error dari server
            console.error('Error loading products:', xhr.statusText);
            showError('Gagal memuat produk. Status: ' + xhr.status);
        }
        isLoading = false;
    };

    // Jika gagal koneksi ke server
    xhr.onerror = function() {
        console.error('Network error occurred');
        showError('Gagal terhubung ke server. Periksa koneksi internet Anda.');
        isLoading = false;
    };

    xhr.send(); // Kirim request
}

// Fungsi untuk mengupdate tampilan pagination berdasarkan data dari server
function updatePagination(paginationData) {
    // Update variabel global pagination
    currentPage = parseInt(paginationData.current_page);
    totalPages = parseInt(paginationData.total_pages);

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

// generate page numbers HTML
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

// change page
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

// go to specific page
function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadProducts();
        scrollToTop();
    }
}

function goToProduct(productId) {
    window.location.href = `/product/${productId}`;
}

function hideAllStates() {
    const loadingState = document.getElementById('loadingState');
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    
    if (loadingState) loadingState.style.display = 'none';
    if (productsGrid) productsGrid.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
}

function formatPrice(price) { 
    return 'Rp ' + parseInt(price).toLocaleString('id-ID'); 
}

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
    hideAllStates();
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'block';
        const emptyTitle = emptyState.querySelector('.empty-title');
        const emptyDescription = emptyState.querySelector('.empty-description');
        
        if (emptyTitle) emptyTitle.textContent = 'Terjadi Kesalahan';
        if (emptyDescription) emptyDescription.textContent = message;
    }
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

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