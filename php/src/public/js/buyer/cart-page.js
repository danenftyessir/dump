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

    input.value = newQty;
    updateButtonStates(cartItem, newQty, maxStock);
    cartItem.classList.add('updating');

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
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/cart/update', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    updateCartDisplay(data.data);
                    cartItem.classList.remove('updating');
                    
                    const input = cartItem.querySelector('.qty-input');
                    input.dataset.original = quantity;
                } else {
                    cartItem.classList.remove('updating');
                    showToast(data.message || 'Gagal update quantity', 'error');
                    
                    // Revert to original
                    const input = cartItem.querySelector('.qty-input');
                    input.value = input.dataset.original;
                }
            } else {
                cartItem.classList.remove('updating');
                showToast('Gagal update quantity', 'error');
                
                // Revert to original
                const input = cartItem.querySelector('.qty-input');
                input.value = input.dataset.original;
            }
        }
    };
    
    const params = `_token=${encodeURIComponent(CSRF_TOKEN)}&cart_item_id=${encodeURIComponent(cartItemId)}&quantity=${encodeURIComponent(quantity)}`;
    xhr.send(params);
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

    const cartItemId = itemToRemove;
    
    closeModal();
    showLoading();
    
    console.log('Sending DELETE request to:', `/api/cart/remove/${cartItemId}`);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', `/api/cart/remove/${cartItemId}`, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideLoading();
            
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
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
            } else {
                showToast('Gagal menghapus item', 'error');
            }
        }
    };
    
    const params = `_token=${encodeURIComponent(CSRF_TOKEN)}`;
    xhr.send(params);
}

// Checkout
function checkout() {
    window.location.href = '/checkout';
}

// Update cart display
function updateCartDisplay(data) {
    if (data.cartSummary) {
        // Update badge
        const badge = document.getElementById('cartBadge');
        if (badge) {
            badge.textContent = `${data.cartSummary.total_items_quantity} Item`;
        }
        
        const totalDisplay = document.getElementById('totalItemsDisplay');
        if (totalDisplay) {
            totalDisplay.textContent = `${data.cartSummary.total_items_quantity} item`;
        }
        
        // Update grand total
        const grandTotal = document.getElementById('grandTotal');
        if (grandTotal) {
            grandTotal.textContent = `Rp ${data.cartSummary.grand_total.toLocaleString('id-ID')}`;
        }
        
        // Update item subtotal if available
        if (data.item_subtotal && data.cart_item_id) {
            const cartItem = document.querySelector(`[data-cart-id="${data.cart_item_id}"]`);
            if (cartItem) {
                const subtotalEl = cartItem.querySelector('.item-subtotal');
                if (subtotalEl) {
                    subtotalEl.textContent = `Rp ${data.item_subtotal.toLocaleString('id-ID')}`;
                }
            }
        }
        
        // Update store subtotals
        Object.keys(data.cartSummary.stores).forEach(storeId => {
            const store = data.cartSummary.stores[storeId];
            const storeSubtotalEl = document.querySelector(`[data-store-id="${storeId}"] .store-subtotal-value`);
            if (storeSubtotalEl) {
                storeSubtotalEl.textContent = `Rp ${store.subtotal.toLocaleString('id-ID')}`;
            }
        });
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
            console.error(`PROBLEM FOUND: Item ${index + 1} has invalid cart_item_id:`, cartId);
        }
    });
    console.log('=== Total cart items:', cartItems.length, '===');
});