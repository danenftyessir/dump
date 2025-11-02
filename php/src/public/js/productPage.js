// Product Page JavaScript
let deleteProductId = null;

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productTable tbody tr');
    
    rows.forEach(row => {
        const productName = row.querySelector('.product-name').textContent.toLowerCase();
        const match = productName.includes(searchValue);
        row.style.display = match ? '' : 'none';
    });
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('#productTable tbody tr');
    
    rows.forEach(row => {
        if (filterValue === '') {
            row.style.display = '';
        } else {
            const categories = row.querySelectorAll('.product-category');
            let hasCategory = false;
            
            categories.forEach(cat => {
                if (cat.textContent.trim() === filterValue) {
                    hasCategory = true;
                }
            });
            
            row.style.display = hasCategory ? '' : 'none';
        }
    });
});

// Sort functionality
document.getElementById('sortSelect').addEventListener('change', function() {
    const sortValue = this.value;
    const tbody = document.querySelector('#productTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aValue, bValue;
        
        switch(sortValue) {
            case 'name-asc':
                aValue = a.querySelector('.product-name').textContent;
                bValue = b.querySelector('.product-name').textContent;
                return aValue.localeCompare(bValue);
            
            case 'name-desc':
                aValue = a.querySelector('.product-name').textContent;
                bValue = b.querySelector('.product-name').textContent;
                return bValue.localeCompare(aValue);
            
            case 'price-asc':
                aValue = parseFloat(a.querySelector('.price').textContent.replace(/[^0-9]/g, ''));
                bValue = parseFloat(b.querySelector('.price').textContent.replace(/[^0-9]/g, ''));
                return aValue - bValue;
            
            case 'price-desc':
                aValue = parseFloat(a.querySelector('.price').textContent.replace(/[^0-9]/g, ''));
                bValue = parseFloat(b.querySelector('.price').textContent.replace(/[^0-9]/g, ''));
                return bValue - aValue;
            
            case 'stock-asc':
                aValue = parseInt(a.querySelector('.stock').textContent);
                bValue = parseInt(b.querySelector('.stock').textContent);
                return aValue - bValue;
            
            case 'stock-desc':
                aValue = parseInt(a.querySelector('.stock').textContent);
                bValue = parseInt(b.querySelector('.stock').textContent);
                return bValue - aValue;
            
            default:
                return 0;
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
});

// Delete confirmation modal
function confirmDelete(productId, productName) {
    deleteProductId = productId;
    document.getElementById('productNameToDelete').textContent = productName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteProductId = null;
}

async function deleteProduct() {
    if (!deleteProductId) return;
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.add('active');
    
    try {
        const response = await fetch(`/seller/products/delete/${deleteProductId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-product-id="${deleteProductId}"]`);
            if (row) {
                row.remove();
            }
            
            // Check if table is empty
            const tbody = document.querySelector('#productTable tbody');
            if (tbody.children.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h3>Belum Ada Produk</h3>
                            <p>Mulai tambahkan produk pertama Anda</p>
                        </td>
                    </tr>
                `;
            }
            
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Terjadi kesalahan saat menghapus produk');
    } finally {
        loadingOverlay.classList.remove('active');
        closeDeleteModal();
    }
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
