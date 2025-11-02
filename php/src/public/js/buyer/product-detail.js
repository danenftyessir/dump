document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addToCartForm');
    
    handleQuantitySelector();
    
    initializeTabs();
    
    if (form) {
        handleFormSubmission();
    }
});

function handleQuantitySelector() {
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus = document.getElementById('btn-plus');
    const quantityInput = document.getElementById('quantity');
    
    // Pastikan elemen ada sebelum menambah listener
    if (!btnMinus || !btnPlus || !quantityInput) return;
    
    const maxStock = parseInt(quantityInput.max, 10);

    btnMinus.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value, 10);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    btnPlus.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value, 10);
        if (currentValue < maxStock) {
            quantityInput.value = currentValue + 1;
        }
    });
}

function handleFormSubmission() {
    const form = document.getElementById('addToCartForm');
    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = 'Menambahkan...';
        submitButton.disabled = true;
        
        // Ambil data form
        const productId = form.elements['product_id'].value;
        const quantity = form.elements['quantity'].value;
        const token = form.elements['_token'].value;

        // Siapkan data untuk dikirim (format x-www-form-urlencoded)
        const data = "product_id=" + encodeURIComponent(productId) + 
                     "&quantity=" + encodeURIComponent(quantity) +
                     "&_token=" + encodeURIComponent(token);

        const xhr = new XMLHttpRequest();

        xhr.open('POST', '/api/cart/add', true); 
        
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.setRequestHeader("X-CSRF-TOKEN", token);
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
                showToast('Terjadi kesalahan server: ' + xhr.status, 'error');
            }
            
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        };
        
        xhr.onerror = function() {
            showToast('Gagal terhubung ke server.', 'error');
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        };

        xhr.send(data);
    });
}

function initializeTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.dataset.tab;

            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
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