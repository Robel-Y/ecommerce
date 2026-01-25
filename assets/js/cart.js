/* ============================================
   SHOPPING CART SYSTEM - Procedural
   Client-side cart management
============================================ */

// Cart state
let cart = {
    items: [],
    total: 0,
    count: 0
};

// Initialize cart from localStorage
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    updateCartDisplay();
    setupCartEventListeners();
});

/* ========== CART EVENT LISTENERS ========== */

function setupCartEventListeners() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-add-cart')) {
            e.preventDefault();
            const button = e.target.closest('.btn-add-cart');
            const productId = button.getAttribute('data-product-id');
            const quantity = parseInt(button.getAttribute('data-quantity') || 1);
            addToCart(productId, quantity);
        }
        
        // Remove item
        if (e.target.closest('.remove-item')) {
            e.preventDefault();
            const button = e.target.closest('.remove-item');
            const productId = button.getAttribute('data-product-id');
            removeFromCart(productId);
        }
        
        // Update quantity
        if (e.target.closest('.quantity-btn')) {
            e.preventDefault();
            const button = e.target.closest('.quantity-btn');
            const productId = button.getAttribute('data-product-id');
            const action = button.getAttribute('data-action'); // 'increase' or 'decrease'
            
            if (action === 'increase') {
                updateQuantity(productId, 1);
            } else if (action === 'decrease') {
                updateQuantity(productId, -1);
            }
        }
        
        // Clear cart
        if (e.target.closest('.clear-cart')) {
            e.preventDefault();
            clearCart();
        }
    });
    
    // Quantity input changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const productId = input.getAttribute('data-product-id');
            const quantity = parseInt(input.value);
            
            if (quantity > 0) {
                setQuantity(productId, quantity);
            }
        }
    });
    
    // Save cart on page unload
    window.addEventListener('beforeunload', saveCart);
}

/* ========== CART OPERATIONS ========== */

function addToCart(productId, quantity = 1) {
    // Get product details from page
    const product = getProductDetails(productId);
    
    if (!product) {
        showNotification('Product not found', 'error');
        return;
    }
    
    // Check if product is already in cart
    const existingItem = cart.items.find(item => item.id === productId);
    
    if (existingItem) {
        // Update quantity
        existingItem.quantity += quantity;
        showNotification(`${product.name} quantity updated`, 'info');
    } else {
        // Add new item
        cart.items.push({
            id: productId,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: quantity,
            stock: product.stock || 99
        });
        showNotification(`${product.name} added to cart`, 'success');
    }
    
    // Update cart totals
    updateCartTotals();
    
    // Save to localStorage
    saveCart();
    
    // Update display
    updateCartDisplay();
    
    // Trigger cart update event
    triggerCartUpdate();
}

function removeFromCart(productId) {
    // Find item index
    const itemIndex = cart.items.findIndex(item => item.id === productId);
    
    if (itemIndex > -1) {
        const itemName = cart.items[itemIndex].name;
        
        // Remove item
        cart.items.splice(itemIndex, 1);
        
        // Update cart totals
        updateCartTotals();
        
        // Save to localStorage
        saveCart();
        
        // Update display
        updateCartDisplay();
        
        // Show notification
        showNotification(`${itemName} removed from cart`, 'info');
        
        // Trigger cart update event
        triggerCartUpdate();
    }
}

function updateQuantity(productId, change) {
    const item = cart.items.find(item => item.id === productId);
    
    if (item) {
        const newQuantity = item.quantity + change;
        
        if (newQuantity < 1) {
            removeFromCart(productId);
        } else if (newQuantity > item.stock) {
            showNotification(`Cannot exceed available stock (${item.stock})`, 'warning');
        } else {
            item.quantity = newQuantity;
            updateCartTotals();
            saveCart();
            updateCartDisplay();
            triggerCartUpdate();
        }
    }
}

function setQuantity(productId, quantity) {
    const item = cart.items.find(item => item.id === productId);
    
    if (item) {
        if (quantity < 1) {
            removeFromCart(productId);
        } else if (quantity > item.stock) {
            showNotification(`Cannot exceed available stock (${item.stock})`, 'warning');
            item.quantity = item.stock;
        } else {
            item.quantity = quantity;
        }
        
        updateCartTotals();
        saveCart();
        updateCartDisplay();
        triggerCartUpdate();
    }
}

function clearCart() {
    if (cart.items.length === 0) {
        showNotification('Cart is already empty', 'info');
        return;
    }
    
    if (confirm('Are you sure you want to clear your cart?')) {
        cart.items = [];
        updateCartTotals();
        saveCart();
        updateCartDisplay();
        showNotification('Cart cleared successfully', 'success');
        triggerCartUpdate();
    }
}

/* ========== CART CALCULATIONS ========== */

function updateCartTotals() {
    let total = 0;
    let count = 0;
    
    cart.items.forEach(function(item) {
        total += item.price * item.quantity;
        count += item.quantity;
    });
    
    cart.total = total;
    cart.count = count;
}

function getCartTotal() {
    return cart.total;
}

function getCartCount() {
    return cart.count;
}

function getCartItems() {
    return cart.items;
}

function isCartEmpty() {
    return cart.items.length === 0;
}

/* ========== PRODUCT DETAILS ========== */

function getProductDetails(productId) {
    // Try to get from data attributes on the page
    const productElement = document.querySelector(`[data-product-id="${productId}"]`);
    
    if (productElement) {
        return {
            id: productId,
            name: productElement.getAttribute('data-product-name') || 'Product',
            price: parseFloat(productElement.getAttribute('data-product-price')) || 0,
            image: productElement.getAttribute('data-product-image') || '',
            stock: parseInt(productElement.getAttribute('data-product-stock')) || 99
        };
    }
    
    // Fallback: try to get from product card
    const productCard = document.querySelector(`.product-card[data-id="${productId}"]`);
    
    if (productCard) {
        return {
            id: productId,
            name: productCard.querySelector('.product-info h3')?.textContent || 'Product',
            price: parseFloat(productCard.querySelector('.current-price')?.textContent.replace(/[^0-9.-]+/g, '')) || 0,
            image: productCard.querySelector('.product-image img')?.src || '',
            stock: 99
        };
    }
    
    // If product not found on page, return null
    return null;
}

/* ========== CART DISPLAY ========== */

function updateCartDisplay() {
    // Update cart count in header
    updateCartCount();
    
    // Update cart page if we're on it
    if (document.querySelector('.cart-page')) {
        updateCartPage();
    }
    
    // Update checkout page if we're on it
    if (document.querySelector('.checkout-page')) {
        updateCheckoutPage();
    }
    
    // Update mini-cart if it exists
    if (document.querySelector('.mini-cart')) {
        updateMiniCart();
    }
}

function updateCartCount() {
    const cartCountElements = document.querySelectorAll('.cart-count, .cart-count-badge');
    
    cartCountElements.forEach(function(element) {
        if (cart.count > 0) {
            element.textContent = cart.count;
            element.style.display = 'flex';
        } else {
            element.style.display = 'none';
        }
    });
    
    // Update cart total in header
    const cartTotalElements = document.querySelectorAll('.cart-total');
    cartTotalElements.forEach(function(element) {
        element.textContent = formatCurrency(cart.total);
    });
}

function updateCartPage() {
    const cartItemsContainer = document.querySelector('.cart-items-container');
    const cartEmptyMessage = document.querySelector('.cart-empty');
    const cartNotEmpty = document.querySelector('.cart-not-empty');
    const cartTotalElement = document.querySelector('.cart-total-amount');
    const cartSubtotalElement = document.querySelector('.cart-subtotal');
    const taxElement = document.querySelector('.cart-tax');
    const shippingElement = document.querySelector('.cart-shipping');
    
    if (isCartEmpty()) {
        // Show empty cart message
        if (cartEmptyMessage) cartEmptyMessage.style.display = 'block';
        if (cartNotEmpty) cartNotEmpty.style.display = 'none';
        
        // Hide checkout button
        const checkoutBtn = document.querySelector('.btn-checkout');
        if (checkoutBtn) checkoutBtn.style.display = 'none';
    } else {
        // Hide empty cart message
        if (cartEmptyMessage) cartEmptyMessage.style.display = 'none';
        if (cartNotEmpty) cartNotEmpty.style.display = 'block';
        
        // Update cart items table
        if (cartItemsContainer) {
            cartItemsContainer.innerHTML = generateCartItemsHTML();
        }
        
        // Calculate totals
        const subtotal = cart.total;
        const tax = subtotal * 0.1; // 10% tax
        const shipping = subtotal > 50 ? 0 : 5.99; // Free shipping over $50
        const total = subtotal + tax + shipping;
        
        // Update total display
        if (cartTotalElement) cartTotalElement.textContent = formatCurrency(total);
        if (cartSubtotalElement) cartSubtotalElement.textContent = formatCurrency(subtotal);
        if (taxElement) taxElement.textContent = formatCurrency(tax);
        if (shippingElement) shippingElement.textContent = formatCurrency(shipping);
        
        // Show checkout button
        const checkoutBtn = document.querySelector('.btn-checkout');
        if (checkoutBtn) checkoutBtn.style.display = 'block';
    }
}

function generateCartItemsHTML() {
    let html = '';
    
    cart.items.forEach(function(item) {
        const itemTotal = item.price * item.quantity;
        
        html += `
            <div class="cart-item" data-product-id="${item.id}">
                <div class="cart-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item-info">
                    <div class="cart-item-header">
                        <h4 class="cart-item-title">${item.name}</h4>
                        <button class="remove-item" data-product-id="${item.id}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="cart-item-price">${formatCurrency(item.price)} each</div>
                    <div class="cart-item-actions">
                        <div class="quantity-control">
                            <button class="quantity-btn" data-action="decrease" data-product-id="${item.id}">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" data-product-id="${item.id}" 
                                   value="${item.quantity}" min="1" max="${item.stock}">
                            <button class="quantity-btn" data-action="increase" data-product-id="${item.id}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="cart-item-total">
                            <strong>${formatCurrency(itemTotal)}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    return html;
}

function updateCheckoutPage() {
    const orderSummary = document.querySelector('.order-summary-items');
    const orderTotal = document.querySelector('.order-total');
    
    if (orderSummary) {
        orderSummary.innerHTML = generateOrderSummaryHTML();
    }
    
    if (orderTotal) {
        orderTotal.textContent = formatCurrency(cart.total);
    }
}

function generateOrderSummaryHTML() {
    let html = '';
    
    cart.items.forEach(function(item) {
        const itemTotal = item.price * item.quantity;
        
        html += `
            <div class="order-summary-item">
                <div class="order-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="order-item-details">
                    <h5>${item.name}</h5>
                    <div class="order-item-meta">
                        <span>Qty: ${item.quantity}</span>
                        <span>${formatCurrency(item.price)} each</span>
                    </div>
                </div>
                <div class="order-item-total">
                    ${formatCurrency(itemTotal)}
                </div>
            </div>
        `;
    });
    
    return html;
}

function updateMiniCart() {
    const miniCart = document.querySelector('.mini-cart');
    if (!miniCart) return;
    
    const itemsContainer = miniCart.querySelector('.mini-cart-items');
    const totalElement = miniCart.querySelector('.mini-cart-total');
    
    if (itemsContainer) {
        if (isCartEmpty()) {
            itemsContainer.innerHTML = '<div class="mini-cart-empty">Your cart is empty</div>';
        } else {
            itemsContainer.innerHTML = generateMiniCartHTML();
        }
    }
    
    if (totalElement) {
        totalElement.textContent = formatCurrency(cart.total);
    }
}

function generateMiniCartHTML() {
    let html = '';
    
    // Show only last 3 items
    const recentItems = cart.items.slice(-3);
    
    recentItems.forEach(function(item) {
        html += `
            <div class="mini-cart-item">
                <div class="mini-cart-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="mini-cart-item-info">
                    <h6>${item.name}</h6>
                    <div class="mini-cart-item-meta">
                        <span>${item.quantity} × ${formatCurrency(item.price)}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Show count of remaining items
    if (cart.items.length > 3) {
        const remaining = cart.items.length - 3;
        html += `<div class="mini-cart-more">+${remaining} more item${remaining > 1 ? 's' : ''}</div>`;
    }
    
    return html;
}

/* ========== STORAGE FUNCTIONS ========== */

function saveCart() {
    try {
        localStorage.setItem('cart', JSON.stringify(cart));
    } catch (e) {
        console.error('Failed to save cart to localStorage:', e);
    }
}

function loadCart() {
    try {
        const savedCart = localStorage.getItem('cart');
        if (savedCart) {
            const parsedCart = JSON.parse(savedCart);
            
            // Validate loaded cart data
            if (Array.isArray(parsedCart.items)) {
                cart.items = parsedCart.items;
                updateCartTotals();
            }
        }
    } catch (e) {
        console.error('Failed to load cart from localStorage:', e);
        // Clear corrupted cart data
        localStorage.removeItem('cart');
    }
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
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(function() {
        notification.classList.add('show');
    }, 10);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.classList.remove('show');
        setTimeout(function() {
            notification.remove();
        }, 300);
    });
}

function triggerCartUpdate() {
    // Dispatch custom event for other scripts to listen to
    const event = new CustomEvent('cartUpdated', {
        detail: { cart: cart }
    });
    document.dispatchEvent(event);
}

/* ========== CHECKOUT FUNCTIONS ========== */

function validateCheckout() {
    // Validate cart is not empty
    if (isCartEmpty()) {
        showNotification('Your cart is empty', 'error');
        return false;
    }
    
    // Validate all items are in stock
    for (let i = 0; i < cart.items.length; i++) {
        const item = cart.items[i];
        
        // Here you would typically make an API call to check stock
        // For now, we'll just check against the stored stock value
        if (item.quantity > item.stock) {
            showNotification(`${item.name} only has ${item.stock} items in stock`, 'error');
            return false;
        }
    }
    
    return true;
}

function processCheckout(formData) {
    // This function would typically send data to a server
    // For now, we'll simulate a checkout process
    
    if (!validateCheckout()) {
        return false;
    }
    
    // Show loading state
    showNotification('Processing your order...', 'info');
    
    // Simulate API call
    setTimeout(function() {
        // Clear cart on successful checkout
        clearCart();
        
        // Show success message
        showNotification('Order placed successfully!', 'success');
        
        // Redirect to order confirmation page
        setTimeout(function() {
            window.location.href = 'order-confirmation.php';
        }, 2000);
    }, 2000);
    
    return true;
}

// Export functions for global use
window.cartFunctions = {
    addToCart,
    removeFromCart,
    updateQuantity,
    setQuantity,
    clearCart,
    getCartTotal,
    getCartCount,
    getCartItems,
    isCartEmpty,
    validateCheckout,
    processCheckout
};