/* ============================================
   PRODUCTS SCRIPT - Product Listing Functionality
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize product listing
    initializeProductListing();
    
    function initializeProductListing() {
        // Mobile filter toggle
        const filterToggle = document.querySelector('.filter-toggle');
        const filtersSidebar = document.querySelector('.filters-sidebar');
        const filterOverlay = document.createElement('div');
        filterOverlay.className = 'filter-overlay';
        document.body.appendChild(filterOverlay);
        
        if (filterToggle && filtersSidebar) {
            filterToggle.addEventListener('click', function() {
                filtersSidebar.classList.toggle('active');
                filterOverlay.classList.toggle('active');
                document.body.classList.toggle('no-scroll');
                
                // Update button icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-filter');
                    icon.classList.toggle('fa-times');
                }
            });
            
            // Close filters when clicking overlay
            filterOverlay.addEventListener('click', function() {
                filtersSidebar.classList.remove('active');
                this.classList.remove('active');
                document.body.classList.remove('no-scroll');
                
                // Reset button icon
                if (filterToggle) {
                    const icon = filterToggle.querySelector('i');
                    if (icon) {
                        icon.classList.add('fa-filter');
                        icon.classList.remove('fa-times');
                    }
                }
            });
        }
        
        // Cart actions are handled globally in assets/js/cart.js
        
        // Sort functionality
        const sortSelect = document.querySelector('.sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('sort', this.value);
                window.location.href = url.toString();
            });
        }
        
        // Price range filter
        const minPriceInput = document.getElementById('min_price');
        const maxPriceInput = document.getElementById('max_price');
        const priceSlider = document.querySelector('.slider');
        
        if (priceSlider && minPriceInput && maxPriceInput) {
            // Initialize slider values
            priceSlider.min = 0;
            priceSlider.max = 10000;
            priceSlider.value = maxPriceInput.value;
            
            priceSlider.addEventListener('input', function() {
                maxPriceInput.value = this.value;
                updatePriceDisplay();
            });
            
            minPriceInput.addEventListener('change', updatePriceDisplay);
            maxPriceInput.addEventListener('change', updatePriceDisplay);
            
            function updatePriceDisplay() {
                // You could update a visual display here
                console.log('Price range:', minPriceInput.value, '-', maxPriceInput.value);
            }
        }
        
        // Filter tags removal
        const filterTagRemoves = document.querySelectorAll('.filter-tag-remove');
        
        filterTagRemoves.forEach(button => {
            button.addEventListener('click', function() {
                const tag = this.closest('.filter-tag');
                const filterType = tag.getAttribute('data-filter-type');
                const filterValue = tag.getAttribute('data-filter-value');
                
                removeFilter(filterType, filterValue);
                tag.remove();
                
                // Update URL and reload
                updateURL();
            });
        });
        
        // Clear all filters
        const clearFiltersBtn = document.querySelector('.clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                window.location.href = window.location.pathname;
            });
        }
        
        // View toggle (grid/list)
        const viewButtons = document.querySelectorAll('.view-btn');
        const productGrid = document.querySelector('.products-grid-3');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const viewType = this.getAttribute('data-view');
                
                // Update active state
                viewButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update grid layout
                if (productGrid) {
                    if (viewType === 'list') {
                        productGrid.classList.add('list-view');
                        productGrid.classList.remove('grid-view');
                    } else {
                        productGrid.classList.add('grid-view');
                        productGrid.classList.remove('list-view');
                    }
                }
                
                // Save preference to localStorage
                localStorage.setItem('productView', viewType);
            });
        });
        
        // Load saved view preference
        const savedView = localStorage.getItem('productView') || 'grid';
        const activeViewBtn = document.querySelector(`.view-btn[data-view="${savedView}"]`);
        if (activeViewBtn) {
            activeViewBtn.click();
        }
        
        // Lazy load images
        const productImages = document.querySelectorAll('.product-image img');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            productImages.forEach(img => {
                if (img.dataset.src) {
                    imageObserver.observe(img);
                }
            });
        }
        
        // Infinite scroll disabled: keeps filtered listings predictable
    }
    
    function removeFilter(type, value) {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        
        switch (type) {
            case 'category':
                params.delete('category');
                break;
            case 'price':
                params.delete('min_price');
                params.delete('max_price');
                break;
            case 'search':
                params.delete('search');
                break;
        }
        
        window.location.href = url.toString();
    }
    
    function updateURL() {
        // Reserved for future URL sync
    }
});