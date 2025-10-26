// state management
let currentPage = 1;
let totalPages = 1;
let currentFilters = {
    search: '',
    category_id: '',
    sort_by: 'created_at',
    sort_order: 'DESC'
};

// debounce untuk search
let searchTimeout;

// elements
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const sortBy = document.getElementById('sortBy');
const resetFiltersBtn = document.getElementById('resetFilters');
const loadingState = document.getElementById('loadingState');
const emptyState = document.getElementById('emptyState');
const productsTable = document.getElementById('productsTable');
const productsTableBody = document.getElementById('productsTableBody');
const paginationContainer = document.getElementById('paginationContainer');
const paginationInfo = document.getElementById('paginationInfo');
const paginationControls = document.getElementById('paginationControls');
const deleteModal = document.getElementById('deleteModal');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const toast = document.getElementById('toast');

let productToDelete = null;

// event listeners
searchInput.addEventListener('input', handleSearchInput);
categoryFilter.addEventListener('change', handleFilterChange);
sortBy.addEventListener('change', handleSortChange);
resetFiltersBtn.addEventListener('click', handleResetFilters);

// load products saat halaman pertama kali dimuat
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});

// handle search input dengan debounce
function handleSearchInput(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentFilters.search = e.target.value;
        currentPage = 1;
        loadProducts();
    }, 500);
}

// handle filter change
function handleFilterChange() {
    currentFilters.category_id = categoryFilter.value;
    currentPage = 1;
    loadProducts();
}

// handle sort change
function handleSortChange() {
    const sortValue = sortBy.value.split(':');
    currentFilters.sort_by = sortValue[0];
    currentFilters.sort_order = sortValue[1];
    currentPage = 1;
    loadProducts();
}

// handle reset filters
function handleResetFilters() {
    searchInput.value = '';
    categoryFilter.value = '';
    sortBy.value = 'created_at:DESC';
    
    currentFilters = {
        search: '',
        category_id: '',
        sort_by: 'created_at',
        sort_order: 'DESC'
    };
    currentPage = 1;
    
    loadProducts();
}

// load products dari API menggunakan XMLHttpRequest
function loadProducts() {
    // tampilkan loading state
    showLoading();
    
    // build query string
    const params = new URLSearchParams({
        search: currentFilters.search,
        category_id: currentFilters.category_id,
        sort_by: currentFilters.sort_by,
        sort_order: currentFilters.sort_order,
        page: currentPage,
        limit: 10
    });
    
    // buat XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `/api/seller/products?${params.toString()}`, true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    const { products, pagination } = response.data;
                    
                    if (products.length === 0 && currentPage === 1) {
                        showEmptyState();
                    } else {
                        renderProducts(products);
                        renderPagination(pagination);
                    }
                } else {
                    showToast(response.error || 'Gagal Memuat Produk', 'error');
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error parsing response:', error);
                showToast('Terjadi Kesalahan Saat Memuat Produk', 'error');
                showEmptyState();
            }
        } else if (xhr.status === 401) {
            showToast('Sesi Anda Telah Berakhir', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else {
            showToast('Gagal Memuat Produk', 'error');
            showEmptyState();
        }
    };
    
    xhr.onerror = function() {
        showToast('Terjadi Kesalahan Jaringan', 'error');
        showEmptyState();
    };
    
    xhr.send();
}

// render products ke tabel
function renderProducts(products) {
    productsTableBody.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        
        // format harga
        const formattedPrice = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(product.price);
        
        // tentukan class untuk stok
        const stockClass = product.stock < 10 ? 'stock-low' : 'stock-normal';
        
        row.innerHTML = `
            <td>
                <img src="${product.main_image_path || '/images/placeholder.png'}" 
                     alt="${escapeHtml(product.product_name)}" 
                     class="product-image"
                     onerror="this.src='/images/placeholder.png'">
            </td>
            <td class="product-name">${escapeHtml(product.product_name)}</td>
            <td class="product-price">${formattedPrice}</td>
            <td class="${stockClass} product-stock">${product.stock}</td>
            <td>
                <div class="action-buttons">
                    <a href="/seller/products/edit?product_id=${product.product_id}" class="btn-edit">
                        Edit
                    </a>
                    <button class="btn-delete" onclick="openDeleteModal(${product.product_id})">
                        Hapus
                    </button>
                </div>
            </td>
        `;
        
        productsTableBody.appendChild(row);
    });
    
    // tampilkan tabel
    loadingState.style.display = 'none';
    emptyState.style.display = 'none';
    productsTable.style.display = 'table';
}

// render pagination
function renderPagination(pagination) {
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;
    
    // update pagination info
    const start = ((currentPage - 1) * pagination.per_page) + 1;
    const end = Math.min(currentPage * pagination.per_page, pagination.total);
    paginationInfo.textContent = `Menampilkan ${start}-${end} dari ${pagination.total} produk`;
    
    // render pagination controls
    paginationControls.innerHTML = '';
    
    // tombol previous
    const prevBtn = createPageButton('‹', currentPage - 1, currentPage === 1);
    paginationControls.appendChild(prevBtn);
    
    // tombol halaman
    const pagesToShow = getPagesToShow(currentPage, totalPages);
    
    pagesToShow.forEach(page => {
        if (page === '...') {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.padding = '8px';
            paginationControls.appendChild(dots);
        } else {
            const pageBtn = createPageButton(page, page, false, page === currentPage);
            paginationControls.appendChild(pageBtn);
        }
    });
    
    // tombol next
    const nextBtn = createPageButton('›', currentPage + 1, currentPage === totalPages);
    paginationControls.appendChild(nextBtn);
    
    // tampilkan pagination
    paginationContainer.style.display = 'flex';
}

// helper untuk membuat tombol pagination
function createPageButton(label, page, disabled, active = false) {
    const btn = document.createElement('button');
    btn.textContent = label;
    btn.className = 'page-btn';
    btn.disabled = disabled;
    
    if (active) {
        btn.classList.add('active');
    }
    
    if (!disabled) {
        btn.addEventListener('click', () => {
            currentPage = page;
            loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    return btn;
}

// helper untuk menentukan halaman mana yang ditampilkan
function getPagesToShow(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    
    if (current <= 4) {
        return [1, 2, 3, 4, 5, '...', total];
    }
    
    if (current >= total - 3) {
        return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
    }
    
    return [1, '...', current - 1, current, current + 1, '...', total];
}

// tampilkan loading state
function showLoading() {
    loadingState.style.display = 'flex';
    emptyState.style.display = 'none';
    productsTable.style.display = 'none';
    paginationContainer.style.display = 'none';
}

// tampilkan empty state
function showEmptyState() {
    loadingState.style.display = 'none';
    emptyState.style.display = 'block';
    productsTable.style.display = 'none';
    paginationContainer.style.display = 'none';
}

// open delete modal
function openDeleteModal(productId) {
    productToDelete = productId;
    deleteModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// close delete modal
function closeDeleteModal() {
    productToDelete = null;
    deleteModal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// confirm delete
confirmDeleteBtn.addEventListener('click', function() {
    if (!productToDelete) return;
    
    // disable button dan tampilkan loading
    confirmDeleteBtn.disabled = true;
    confirmDeleteBtn.innerHTML = '<span class="loading-spinner"></span> Menghapus...';
    
    // buat XMLHttpRequest untuk delete
    const xhr = new XMLHttpRequest();
    xhr.open('DELETE', '/api/seller/products', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onload = function() {
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.textContent = 'Hapus';
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    showToast('Produk Berhasil Dihapus', 'success');
                    closeDeleteModal();
                    loadProducts();
                } else {
                    showToast(response.error || 'Gagal Menghapus Produk', 'error');
                }
            } catch (error) {
                showToast('Terjadi Kesalahan', 'error');
            }
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                showToast(response.error || 'Gagal Menghapus Produk', 'error');
            } catch (error) {
                showToast('Gagal Menghapus Produk', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.textContent = 'Hapus';
        showToast('Terjadi Kesalahan Jaringan', 'error');
    };
    
    xhr.send(JSON.stringify({ product_id: productToDelete }));
});

// close modal saat klik di luar modal
deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) {
        closeDeleteModal();
    }
});

// show toast notification
function showToast(message, type = 'success') {
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// escape HTML untuk mencegah XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}