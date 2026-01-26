/* ============================================
   SHOPPING CART SYSTEM - Procedural
   Client-side cart management with AJAX sync
============================================ */

// Initialize cart from server on load
document.addEventListener('DOMContentLoaded', function() {
    setupCartEventListeners();
    refreshCartCount();
});

/* ========== CART EVENT LISTENERS ========== */

function setupCartEventListeners() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-buy-now')) {
            e.preventDefault();
            const button = e.target.closest('.btn-buy-now');
            const productId = button.getAttribute('data-product-id');
            const quantity = parseInt(document.getElementById('productQuantity')?.value || button.getAttribute('data-quantity') || 1);
            if (productId) {
                buyNow(productId, quantity);
            }
            return;
        }

        if (e.target.closest('.btn-add-cart') || e.target.closest('.btn-add-to-cart')) {
            e.preventDefault();
            const button = e.target.closest('.btn-add-cart') || e.target.closest('.btn-add-to-cart');
            const productId = button.getAttribute('data-product-id');
            const quantity = parseInt(document.getElementById('productQuantity')?.value || button.getAttribute('data-quantity') || 1);
            
            if (productId) {
                addToCart(productId, quantity);
            }
        }
        
        // Remove item
        if (e.target.closest('.remove-item')) {
            e.preventDefault();
            const button = e.target.closest('.remove-item');
            const productId = button.getAttribute('data-product-id');
            removeFromCart(productId);
        }
        
        // Update quantity buttons
        if (e.target.closest('.quantity-btn')) {
            e.preventDefault();
            const button = e.target.closest('.quantity-btn');
            const productId = button.getAttribute('data-product-id');
            const action = button.getAttribute('data-action');
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            
            if (input) {
                let newQty = parseInt(input.value);
                if (action === 'increase' || button.classList.contains('increase')) {
                    newQty++;
                } else if (action === 'decrease' || button.classList.contains('decrease')) {
                    newQty--;
                }
                
                if (newQty > 0) {
                    updateQuantity(productId, newQty);
                } else {
                    removeFromCart(productId);
                }
            }
        }
        
        // Clear cart
        if (e.target.closest('.clear-cart')) {
            e.preventDefault();
            if (confirm('Are you sure you want to clear your cart?')) {
                clearCart();
            }
        }
    });

    // Manual quantity input (type number then blur/change)
    document.addEventListener('change', function(e) {
        const input = e.target.closest('.quantity-input');
        if (!input) return;
        const productId = input.getAttribute('data-product-id');
        const qty = parseInt(input.value, 10);
        if (!productId) return;
        if (!Number.isFinite(qty) || qty <= 0) {
            removeFromCart(productId);
            return;
        }
        updateQuantity(productId, qty);
    });
}

function buyNow(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(SITE_URL + 'process/cart_process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateHeaderCart(data.cart_count);
            // Checkout requires login; if not logged in it will redirect to login.
            window.location.href = SITE_URL + 'user/checkout.php';
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

/* ========== CART OPERATIONS (AJAX) ========== */

function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(SITE_URL + 'process/cart_process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateHeaderCart(data.cart_count);
            // Refresh page if on cart page
            if (window.location.pathname.includes('cart.php')) {
                location.reload();
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function removeFromCart(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch(SITE_URL + 'process/cart_process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'info');
            updateHeaderCart(data.cart_count);
            if (window.location.pathname.includes('cart.php')) {
                location.reload();
            }
        } else {
            showNotification(data.message || 'Failed to remove item.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function updateQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch(SITE_URL + 'process/cart_process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateHeaderCart(data.cart_count);
            if (window.location.pathname.includes('cart.php')) {
                location.reload();
            }
        } else {
            showNotification(data.message, 'error');
            // Reset input if failed
            location.reload();
        }
    });
}

function clearCart() {
    const formData = new FormData();
    formData.append('action', 'clear');

    fetch(SITE_URL + 'process/cart_process.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateHeaderCart(0);
            if (window.location.pathname.includes('cart.php')) {
                location.reload();
            }
        } else {
            showNotification(data.message || 'Failed to clear cart.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function refreshCartCount() {
    fetch(SITE_URL + 'process/cart_process.php?action=get', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.cart) {
            updateHeaderCart(data.cart.count);
        }
    })
    .catch(() => {
        // ignore (header cart count will be server-rendered)
    });
}

/* ========== UI UPDATES ========== */

function updateHeaderCart(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'inline-flex' : 'none';
    });
}

/* ========== UTILITY FUNCTIONS ========== */

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
}

function showNotification(message, type) {
    // Check if notification function exists globally (from header/footer)
    if (window.showNotification) {
        window.showNotification(message, type);
        return;
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' ? 'exclamation-circle' : 'info-circle')}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);

    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// Export functions for global use
window.cartFunctions = {
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart
};