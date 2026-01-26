/* ============================================
   CATEGORY SCRIPT - Category Page Functionality
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize category page
    initializeCategoryPage();
    
    function initializeCategoryPage() {
        // Price range slider functionality
        initPriceRangeSlider();
        
        // Add to cart functionality
        initAddToCartButtons();
        
        // Sort functionality
        initSorting();
        
        // Filter form submission
        initFilterForms();
        
        // Lazy load images
        initLazyLoading();
        
        // Category navigation scroll
        initCategoryNavigation();
        
        // Responsive sidebar toggle
        initSidebarToggle();
    }
    
    function initPriceRangeSlider() {
        const minPriceSlider = document.getElementById('minPrice');
        const maxPriceSlider = document.getElementById('maxPrice');
        const minPriceValue = document.getElementById('minPriceValue');
        const maxPriceValue = document.getElementById('maxPriceValue');
        
        if (!minPriceSlider || !maxPriceSlider) return;
        
        // Update slider styles
        updateSliderStyles();
        
        // Update value displays
        function updatePriceValues() {
            minPriceValue.textContent = minPriceSlider.value;
            maxPriceValue.textContent = maxPriceSlider.value;
            updateSliderStyles();
        }
        
        // Update slider fill styles
        function updateSliderStyles() {
            const min = parseInt(minPriceSlider.value);
            const max = parseInt(maxPriceSlider.value);
            const minPercent = (min / parseInt(minPriceSlider.max)) * 100;
            const maxPercent = (max / parseInt(maxPriceSlider.max)) * 100;
            
            // Update the pseudo-element styles
            const style = document.createElement('style');
            style.textContent = `
                .price-range-slider::after {
                    left: ${minPercent}% !important;
                    right: ${100 - maxPercent}% !important;
                }
            `;
            document.head.appendChild(style);
            
            // Remove old style if exists
            const oldStyle = document.getElementById('price-slider-style');
            if (oldStyle) oldStyle.remove();
            style.id = 'price-slider-style';
        }
        
        // Event listeners
        minPriceSlider.addEventListener('input', function() {
            if (parseInt(this.value) > parseInt(maxPriceSlider.value)) {
                this.value = maxPriceSlider.value;
            }
            updatePriceValues();
        });
        
        maxPriceSlider.addEventListener('input', function() {
            if (parseInt(this.value) < parseInt(minPriceSlider.value)) {
                this.value = minPriceSlider.value;
            }
            updatePriceValues();
        });
        
        // Initialize
        updatePriceValues();
    }
    
    function initAddToCartButtons() {
        const addToCartButtons = document.querySelectorAll('.btn-add-cart');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                const button = this;
                
                if (button.classList.contains('loading')) return;
                
                // Add loading state
                button.classList.add('loading');
                
                // Simulate API call
                setTimeout(() => {
                    addProductToCart(productId, productName, button);
                }, 500);
            });
        });
    }
    
    async function addProductToCart(productId, productName, button) {
        try {
            // Replace with your actual API endpoint
            const response = await fetch(SITE_URL + 'process/cart_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=1`
            });
            
            const data = await response.json();
            
            // Remove loading state
            button.classList.remove('loading');
            
            if (data.success) {
                // Update cart count
                updateCartCount(data.cart_count);
                
                // Show success notification
                showNotification(`Added "${productName}" to cart!`, 'success');
                
                // Add success animation
                button.classList.add('added');
                setTimeout(() => {
                    button.classList.remove('added');
                }, 1000);
            } else {
                showNotification(data.message || 'Failed to add to cart', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            button.classList.remove('loading');
            showNotification('Network error. Please try again.', 'error');
        }
    }
    
    function initSorting() {
        const sortSelect = document.querySelector('.sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                // Get current URL parameters
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                
                // Update sort parameter
                params.set('sort', this.value);
                
                // Remove page parameter when sorting
                params.delete('page');
                
                // Navigate to new URL
                window.location.href = url.pathname + '?' + params.toString();
            });
        }
    }
    
    function initFilterForms() {
        const filterForms = document.querySelectorAll('.filter-form');
        
        filterForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Remove page parameter when applying filters
                const pageInput = this.querySelector('input[name="page"]');
                if (pageInput) pageInput.remove();
                
                // Remove empty parameters
                const inputs = this.querySelectorAll('input');
                inputs.forEach(input => {
                    if (!input.value && input.name !== 'id') {
                        input.disabled = true;
                    }
                });
            });
        });
        
        // Auto-submit price range after slider interaction
        const priceSlider = document.querySelector('.price-range-slider');
        if (priceSlider) {
            let priceTimeout;
            priceSlider.addEventListener('input', function() {
                clearTimeout(priceTimeout);
                priceTimeout = setTimeout(() => {
                    priceSlider.closest('form').submit();
                }, 1000);
            });
        }
    }
    
    function initLazyLoading() {
        const productImages = document.querySelectorAll('.product-image img');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (!img.dataset.src) return;
                        
                        // Load image
                        img.src = img.dataset.src;
                        
                        // Add loaded class for animation
                        setTimeout(() => {
                            img.classList.add('loaded');
                        }, 100);
                        
                        // Stop observing
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });
            
            productImages.forEach(img => {
                // Store original src in data-src
                if (!img.dataset.src) {
                    img.dataset.src = img.src;
                    // Set a low-quality placeholder or loading image
                    img.src = SITE_URL + 'assets/images/loading.gif';
                    img.style.backgroundColor = '#f3f4f6';
                }
                imageObserver.observe(img);
            });
        }
    }
    
    function initCategoryNavigation() {
        const categoryLinks = document.querySelectorAll('.category-link');
        const categoryId = new URLSearchParams(window.location.search).get('id');
        
        // Highlight current category
        categoryLinks.forEach(link => {
            const linkUrl = new URL(link.href);
            const linkId = linkUrl.searchParams.get('id');
            
            if (linkId === categoryId) {
                link.classList.add('active');
                link.style.pointerEvents = 'none';
            }
        });
        
        // Smooth scroll to category section
        const categoryNav = document.querySelector('.category-navigation');
        if (categoryNav) {
            const hash = window.location.hash;
            if (hash && document.querySelector(hash)) {
                setTimeout(() => {
                    document.querySelector(hash).scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 300);
            }
        }
    }
    
    function initSidebarToggle() {
        // Only on mobile
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.product-sidebar');
            const toggleButton = document.createElement('button');
            toggleButton.className = 'sidebar-toggle';
            toggleButton.innerHTML = '<i class="fas fa-filter"></i> Filters';
            
            // Insert toggle button before sidebar
            sidebar.parentNode.insertBefore(toggleButton, sidebar);
            
            // Hide sidebar initially
            sidebar.classList.add('collapsed');
            
            // Toggle sidebar
            toggleButton.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                this.classList.toggle('active');
                
                // Update button text
                if (sidebar.classList.contains('collapsed')) {
                    this.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
                } else {
                    this.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
                }
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && !toggleButton.contains(e.target) && !sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                    toggleButton.classList.remove('active');
                    toggleButton.innerHTML = '<i class="fas fa-filter"></i> Show Filters';
                }
            });
        }
    }
    
    // Helper functions
    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('.cart-count, .mobile-cart-count');
        cartCountElements.forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
            el.style.animation = 'countPop 0.3s ease';
        });
        
        // Save to localStorage
        localStorage.setItem('cartCount', count);
    }
    
    function showNotification(message, type = 'info') {
        // Your notification system implementation
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        document.body.appendChild(notification);
        
        // Show with animation
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    // Load cart count from localStorage
    const savedCartCount = localStorage.getItem('cartCount');
    if (savedCartCount) {
        updateCartCount(parseInt(savedCartCount));
    }
    
    // Add CSS for sidebar toggle
    const style = document.createElement('style');
    style.textContent = `
        .sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 20px;
                background: linear-gradient(135deg, var(--primary-color), #818cf8);
                color: var(--white);
                border: none;
                border-radius: var(--radius-md);
                font-family: 'Inter', sans-serif;
                font-weight: 500;
                font-size: 0.95rem;
                cursor: pointer;
                margin-bottom: 20px;
                transition: all 0.3s ease;
            }
            
            .sidebar-toggle:hover {
                background: linear-gradient(135deg, var(--primary-dark), #6366f1);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            }
            
            .product-sidebar.collapsed {
                display: none;
            }
            
            .product-sidebar:not(.collapsed) {
                display: block;
                animation: slideDown 0.3s ease;
            }
            
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        }
    `;
    document.head.appendChild(style);
});