let itemToRemove = null;
let updateTimeout = null;

// Update quantity with debouncing
function updateQuantity(cartItemId, change) {
    if (!cartItemId || cartItemId === 'null' || cartItemId === null) {
        console.error('Invalid cart_item_id:', cartItemId);
        showToast('Error: ID item tidak valid', 'error');
        return;
    }
    
    const cartItem = document.querySelector(`[data-cart-id="${cartItemId}"]`);
    if (!cartItem) {
        console.error('Cart item not found for ID:', cartItemId);
        showToast('Error: Item tidak ditemukan', 'error');
        return;
    }
    
    const input = cartItem.querySelector('.qty-input');
    const currentQty = parseInt(input.value);
    const newQty = currentQty + change;
    const maxStock = parseInt(input.max);

    if (newQty < 1 || newQty > maxStock) return;

    // Optimistic UI update
    input.value = newQty;
    updateButtonStates(cartItem, newQty, maxStock);
    cartItem.classList.add('updating');

    // Debounce the API call
    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        sendQuantityUpdate(cartItemId, newQty, cartItem);
    }, 500);
}

// Direct quantity input
function updateQuantityDirect(cartItemId, value) {
    const cartItem = document.querySelector(`[data-cart-id="${cartItemId}"]`);
    const input = cartItem.querySelector('.qty-input');
    const newQty = parseInt(value);
    const maxStock = parseInt(input.max);

    if (isNaN(newQty) || newQty < 1) {
        input.value = input.dataset.original;
        return;
    }

    if (newQty > maxStock) {
        input.value = maxStock;
        showToast('Jumlah melebihi stok tersedia', 'error');
        return;
    }

    updateButtonStates(cartItem, newQty, maxStock);
    cartItem.classList.add('updating');

    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        sendQuantityUpdate(cartItemId, newQty, cartItem);
    }, 800);
}

// Send quantity update to server
function sendQuantityUpdate(cartItemId, quantity, cartItem) {
    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    formData.append('quantity', quantity);

    fetch(`/cart/update/${cartItemId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            updateCartDisplay(data);
            updateTotalPrice(data.data.total_price)
            cartItem.classList.remove('updating');
            
            // Update original value
            const input = cartItem.querySelector('.qty-input');
            input.dataset.original = quantity;
        } else {
            throw new Error(data.message || 'Gagal update quantity');
        }
    })
    .catch(error => {
        cartItem.classList.remove('updating');
        showToast(error.message, 'error');
        
        // Revert to original
        const input = cartItem.querySelector('.qty-input');
        input.value = input.dataset.original;
    });
}

// Update button states
function updateButtonStates(cartItem, quantity, maxStock) {
    const minusBtn = cartItem.querySelector('.qty-btn:first-child');
    const plusBtn = cartItem.querySelector('.qty-btn:last-child');
    
    minusBtn.disabled = quantity <= 1;
    plusBtn.disabled = quantity >= maxStock;
}

// Confirm remove item
function confirmRemove(cartItemId, productName) {
    if (!cartItemId || cartItemId === 'null' || cartItemId === null) {
        console.error('Invalid cart_item_id:', cartItemId);
        showToast('Error: ID item tidak valid', 'error');
        return;
    }
    
    itemToRemove = cartItemId;
    document.getElementById('productNameToRemove').textContent = productName;
    document.getElementById('deleteModal').classList.add('active');
}

// Remove item from cart
function removeItem() {
    console.log('removeItem called, itemToRemove:', itemToRemove);
    
    if (!itemToRemove || itemToRemove === 'null') {
        console.error('Invalid itemToRemove:', itemToRemove);
        showToast('Error: ID item tidak valid', 'error');
        closeModal();
        return;
    }

    // Save to local variable BEFORE closeModal resets it to null
    const cartItemId = itemToRemove;
    
    closeModal();
    showLoading();

    const formData = new FormData();
    formData.append('_token', CSRF_TOKEN);
    
    console.log('Sending DELETE request to:', `/cart/remove/${cartItemId}`);

    fetch(`/cart/remove/${cartItemId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showToast('Item berhasil dihapus');
            
            // Remove item from DOM with animation
            const cartItem = document.querySelector(`[data-cart-id="${cartItemId}"]`);
            cartItem.style.opacity = '0';
            cartItem.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                // Reload page to update all totals
                location.reload();
            }, 300);
        } else {
            showToast(data.message || 'Gagal menghapus item', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showToast('Terjadi kesalahan', 'error');
    });
}

// Checkout
function checkout() {
    window.location.href = '/checkout';
}

// Update cart display
function updateCartDisplay(data) {
    if (data.cartSummary) {
        // Update badge
        document.getElementById('cartBadge').textContent = `${data.cartSummary.total_items} Item`;
        document.getElementById('totalItemsDisplay').textContent = `${data.cartSummary.total_items} item`;
        
        // Update grand total
        document.getElementById('grandTotal').textContent = 
            `Rp ${data.cartSummary.total_price.toLocaleString('id-ID')}`;
        
        // Update item subtotal
        if (data.item_subtotal && data.cart_item_id) {
            const cartItem = document.querySelector(`[data-cart-id="${data.cart_item_id}"]`);
            if (cartItem) {
                const subtotalEl = cartItem.querySelector('.item-subtotal');
                subtotalEl.textContent = `Rp ${data.item_subtotal.toLocaleString('id-ID')}`;
            }
        }
    }
}
function updateTotalPrice(newTotalPrice) {
    const grandTotalElement = document.querySelector('.summary-row.total .total-price');
    if (grandTotalElement) {
        grandTotalElement.textContent = formatRupiah(newTotalPrice);
    }
}

// Modal functions
function closeModal() {
    document.getElementById('deleteModal').classList.remove('active');
    itemToRemove = null;
}

// Loading functions
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

// Toast function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Prevent form resubmission on page reload
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Debug: Log all cart item IDs on page load
document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.querySelectorAll('.cart-item');
    console.log('=== DEBUG: Cart Items on Page ===');
    cartItems.forEach((item, index) => {
        const cartId = item.getAttribute('data-cart-id');
        const productId = item.getAttribute('data-product-id');
        console.log(`Item ${index + 1}:`, {
            cartItemId: cartId,
            productId: productId,
            element: item
        });
        
        // Check if cart_item_id is null or undefined
        if (!cartId || cartId === 'null' || cartId === '') {
            console.error(`⚠️ PROBLEM FOUND: Item ${index + 1} has invalid cart_item_id:`, cartId);
        }
    });
    console.log('=== Total cart items:', cartItems.length, '===');
});