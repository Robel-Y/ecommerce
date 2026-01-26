/* ============================================
   BREADCRUMBS SCRIPT - Dynamic Breadcrumbs
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize breadcrumbs
    initializeBreadcrumbs();
    
    function initializeBreadcrumbs() {
        const breadcrumbsContainer = document.querySelector('.breadcrumbs');
        if (!breadcrumbsContainer) return;
        
        // Get current page path
        const currentPath = window.location.pathname;
        const currentPage = currentPath.split('/').pop() || 'index.php';
        const queryParams = new URLSearchParams(window.location.search);
        
        // Define breadcrumb structure
        const breadcrumbMap = {
            'index.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' }
            ],
            'products.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Products', url: '', icon: 'fas fa-shopping-bag' }
            ],
            'product.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Products', url: SITE_URL + 'products.php', icon: 'fas fa-shopping-bag' },
                { text: 'Product Details', url: '', icon: 'fas fa-info-circle' }
            ],
            'cart.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Cart', url: '', icon: 'fas fa-shopping-cart' }
            ],
            'checkout.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Cart', url: SITE_URL + 'cart.php', icon: 'fas fa-shopping-cart' },
                { text: 'Checkout', url: '', icon: 'fas fa-credit-card' }
            ],
            'profile.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'My Account', url: '', icon: 'fas fa-user' },
                { text: 'Profile', url: '', icon: 'fas fa-user-circle' }
            ],
            'orders.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'My Account', url: SITE_URL + 'profile.php', icon: 'fas fa-user' },
                { text: 'Orders', url: '', icon: 'fas fa-box' }
            ],
            'login.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Login', url: '', icon: 'fas fa-sign-in-alt' }
            ],
            'register.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Register', url: '', icon: 'fas fa-user-plus' }
            ],
            'about.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'About Us', url: '', icon: 'fas fa-info-circle' }
            ],
            'contact.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Contact', url: '', icon: 'fas fa-envelope' }
            ],
            'faq.php': [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'FAQ', url: '', icon: 'fas fa-question-circle' }
            ]
        };
        
        // Get breadcrumbs for current page or default
        let breadcrumbs = breadcrumbMap[currentPage] || [
            { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
            { text: currentPage.replace('.php', '').replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()), url: '', icon: 'fas fa-file' }
        ];
        
        // Check for category parameter
        const category = queryParams.get('category');
        if (category && currentPage === 'all.php') {
            breadcrumbs = [
                { text: 'Home', url: SITE_URL + 'index.php', icon: 'fas fa-home' },
                { text: 'Products', url: SITE_URL + 'products/all.php', icon: 'fas fa-shopping-bag' },
                { text: category.charAt(0).toUpperCase() + category.slice(1), url: '', icon: getCategoryIcon(category) }
            ];
        }
        
        // Generate breadcrumb HTML
        const breadcrumbList = breadcrumbsContainer.querySelector('ol') || document.createElement('ol');
        breadcrumbList.innerHTML = '';
        
        breadcrumbs.forEach((crumb, index) => {
            const li = document.createElement('li');
            li.className = index === breadcrumbs.length - 1 ? 'active' : '';
            
            if (index === breadcrumbs.length - 1) {
                // Last item (current page)
                li.innerHTML = `
                    <i class="${crumb.icon}"></i>
                    ${crumb.text}
                `;
            } else {
                // Link item
                li.innerHTML = `
                    <a href="${crumb.url}">
                        <i class="${crumb.icon}"></i>
                        ${crumb.text}
                    </a>
                `;
            }
            
            breadcrumbList.appendChild(li);
        });
        
        // Add animation to breadcrumbs
        const items = breadcrumbList.querySelectorAll('li');
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('animate-in');
        });
    }
    
    function getCategoryIcon(category) {
        const icons = {
            'electronics': 'fas fa-laptop',
            'fashion': 'fas fa-tshirt',
            'home': 'fas fa-home',
            'sports': 'fas fa-basketball-ball',
            'beauty': 'fas fa-spa',
            'books': 'fas fa-book',
            'all': 'fas fa-th-list'
        };
        return icons[category] || 'fas fa-tag';
    }
    
    // Add dynamic page titles to breadcrumbs
    function updateBreadcrumbForProduct() {
        const productName = document.querySelector('.product-name');
        if (productName && window.location.pathname.includes('product.php')) {
            const productTitle = productName.textContent.trim();
            const lastBreadcrumb = document.querySelector('.breadcrumbs li:last-child');
            if (lastBreadcrumb) {
                lastBreadcrumb.innerHTML = `<i class="fas fa-cube"></i> ${productTitle}`;
            }
        }
    }
    
    // Update breadcrumb when product page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBreadcrumbForProduct);
    } else {
        updateBreadcrumbForProduct();
    }
    
    // Handle breadcrumb clicks for analytics
    document.addEventListener('click', function(e) {
        const breadcrumbLink = e.target.closest('.breadcrumbs a');
        if (breadcrumbLink) {
            const breadcrumbText = breadcrumbLink.textContent.trim();
            console.log(`Breadcrumb clicked: ${breadcrumbText}`);
            
            // You can send this to analytics
            // trackBreadcrumbClick(breadcrumbText);
        }
    });
    
    // Add breadcrumb animation styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        .breadcrumbs li.animate-in {
            animation: breadcrumbSlideIn 0.3s ease forwards;
            opacity: 0;
            transform: translateX(-10px);
        }
        
        @keyframes breadcrumbSlideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .breadcrumbs li a {
            position: relative;
            overflow: hidden;
        }
        
        .breadcrumbs li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
            transition: left 0.3s ease;
        }
        
        .breadcrumbs li a:hover::after {
            left: 0;
        }
    `;
    document.head.appendChild(style);
    
    // Add back button functionality for mobile
    function addBackButton() {
        if (window.innerWidth <= 768) {
            const breadcrumbs = document.querySelector('.breadcrumbs');
            if (breadcrumbs && history.length > 1) {
                const backButton = document.createElement('a');
                backButton.className = 'breadcrumb-back';
                backButton.innerHTML = '<i class="fas fa-arrow-left"></i> Back';
                backButton.href = 'javascript:history.back()';
                backButton.style.marginRight = '15px';
                
                const ol = breadcrumbs.querySelector('ol');
                if (ol) {
                    breadcrumbs.insertBefore(backButton, ol);
                }
            }
        }
    }
    
    addBackButton();
    window.addEventListener('resize', addBackButton);
});

// Function to dynamically update breadcrumb (can be called from other scripts)
function updateBreadcrumb(items) {
    const breadcrumbsContainer = document.querySelector('.breadcrumbs');
    if (!breadcrumbsContainer) return;
    
    const breadcrumbList = breadcrumbsContainer.querySelector('ol');
    if (!breadcrumbList) return;
    
    breadcrumbList.innerHTML = '';
    
    items.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = index === items.length - 1 ? 'active' : '';
        
        if (item.url && index < items.length - 1) {
            li.innerHTML = `
                <a href="${item.url}">
                    ${item.icon ? `<i class="${item.icon}"></i>` : ''}
                    ${item.text}
                </a>
            `;
        } else {
            li.innerHTML = `
                ${item.icon ? `<i class="${item.icon}"></i>` : ''}
                ${item.text}
            `;
        }
        
        breadcrumbList.appendChild(li);
    });
}

// Example usage from other scripts:
// updateBreadcrumb([
//     { text: 'Home', url: 'index.php', icon: 'fas fa-home' },
//     { text: 'Search Results', url: 'search.php?q=shoes', icon: 'fas fa-search' },
//     { text: 'Nike Running Shoes', url: '', icon: 'fas fa-shoe-prints' }
// ]);