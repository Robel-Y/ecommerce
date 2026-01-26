/* ============================================
   HEADER SCRIPT - Navigation Functionality
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');
    const body = document.body;
    
    // Toggle Mobile Menu
    function toggleMobileMenu() {
        mobileMenu.classList.toggle('active');
        body.classList.toggle('menu-open');
        document.querySelector('.mobile-menu-overlay')?.classList.toggle('active');
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', toggleMobileMenu);
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu.classList.contains('active') && 
            !mobileMenu.contains(event.target) && 
            !mobileMenuToggle.contains(event.target)) {
            toggleMobileMenu();
        }
    });
    
    // Close mobile menu on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && mobileMenu.classList.contains('active')) {
            toggleMobileMenu();
        }
    });
    
    // Create overlay for mobile menu
    const overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    document.body.appendChild(overlay);
    
    // Sticky Header
    const header = document.querySelector('.main-header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('sticky');
            
            if (currentScroll > lastScroll && currentScroll > 200) {
                header.style.transform = 'translateY(-100%)';
            } else {
                header.style.transform = 'translateY(0)';
            }
        } else {
            header.classList.remove('sticky');
            header.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // Active Category Highlight
    const currentPage = window.location.pathname;
    const categoryLinks = document.querySelectorAll('.categories-list a, .mobile-categories a');
    
    categoryLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPage.includes(href.split('?')[0])) {
            link.parentElement.classList.add('active');
        }
    });
    
    // Search Form Enhancement
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-input');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
                searchInput.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    searchInput.style.animation = '';
                }, 500);
            }
        });
    }

    // Live Search Suggestions (desktop + mobile)
    const suggestionRoots = Array.from(document.querySelectorAll('.search-form, .mobile-search form'));

    suggestionRoots.forEach((form) => {
        const input = form.querySelector('input[name="search"]');
        const box = form.querySelector('.search-suggestions');
        if (!input || !box) return;

        let abortController = null;
        let debounceTimer = null;
        let activeIndex = -1;
        let lastItems = [];

        function hideBox() {
            box.hidden = true;
            box.innerHTML = '';
            activeIndex = -1;
            lastItems = [];
        }

        function setActive(index) {
            activeIndex = index;
            const items = Array.from(box.querySelectorAll('.search-suggestion-item'));
            items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
        }

        function formatPrice(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) return '';
            return '$' + n.toFixed(2);
        }

        function render(items) {
            lastItems = items;
            if (!items || items.length === 0) {
                hideBox();
                return;
            }

            box.innerHTML = items.map((p, idx) => {
                const img = p.image_url ? String(p.image_url) : (SITE_URL + 'assets/images/products/default.jpg');
                const name = String(p.name || '');
                const price = formatPrice(p.price);
                const detailsUrl = SITE_URL + 'products/details.php?id=' + encodeURIComponent(p.id);
                return `
                    <div class="search-suggestion-item" role="option" data-index="${idx}" data-url="${detailsUrl}">
                        <img class="search-suggestion-thumb" src="${img}" alt="" onerror="this.src='${SITE_URL}assets/images/products/default.jpg'">
                        <div class="search-suggestion-meta">
                            <div class="search-suggestion-name">${name.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>
                            <div class="search-suggestion-price">${price}</div>
                        </div>
                    </div>
                `;
            }).join('');

            box.hidden = false;
            setActive(-1);
        }

        function fetchSuggestions(query) {
            if (abortController) abortController.abort();
            abortController = new AbortController();

            const url = SITE_URL + 'process/search_suggest.php?q=' + encodeURIComponent(query);
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortController.signal
            })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) render(data.items || []);
                    else hideBox();
                })
                .catch(err => {
                    if (err && err.name === 'AbortError') return;
                    hideBox();
                });
        }

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clearTimeout(debounceTimer);
            if (q.length < 1) {
                hideBox();
                return;
            }
            debounceTimer = setTimeout(() => fetchSuggestions(q), 180);
        });

        input.addEventListener('keydown', (e) => {
            if (box.hidden) return;
            const items = Array.from(box.querySelectorAll('.search-suggestion-item'));
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = Math.min(activeIndex + 1, items.length - 1);
                setActive(next);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = Math.max(activeIndex - 1, 0);
                setActive(prev);
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0 && items[activeIndex]) {
                    e.preventDefault();
                    window.location.href = items[activeIndex].getAttribute('data-url');
                }
            } else if (e.key === 'Escape') {
                hideBox();
            }
        });

        box.addEventListener('mousedown', (e) => {
            const item = e.target.closest('.search-suggestion-item');
            if (!item) return;
            e.preventDefault();
            const url = item.getAttribute('data-url');
            if (url) window.location.href = url;
        });

        document.addEventListener('click', (e) => {
            if (form.contains(e.target)) return;
            hideBox();
        });
    });
    
    // Cart Count Update Animation
    function updateCartCount(newCount) {
        const cartCount = document.querySelector('.cart-count');
        const mobileCartCount = document.querySelector('.mobile-cart-count');
        
        if (cartCount) {
            cartCount.textContent = newCount;
            cartCount.style.display = newCount > 0 ? 'flex' : 'none';
            cartCount.style.animation = 'countPop 0.3s ease';
        }
        
        if (mobileCartCount) {
            mobileCartCount.textContent = newCount;
            mobileCartCount.style.display = newCount > 0 ? 'flex' : 'none';
            mobileCartCount.style.animation = 'countPop 0.3s ease';
        }
    }
    
    // Expose update function globally
    window.updateCartCount = updateCartCount;
    
    // Dropdown Accessibility
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.user-dropdown');
        
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            dropdown.classList.remove('open');
        });
        
        // Keyboard navigation
        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                dropdown.classList.toggle('open');
            } else if (e.key === 'Escape') {
                dropdown.classList.remove('open');
            }
        });
    });
    
    // Smooth scroll for anchor links in header
    const headerLinks = document.querySelectorAll('.main-header a[href^="#"]');
    
    headerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                if (mobileMenu.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        });
    });
    
    // Initialize cart count from localStorage (if available)
    const savedCartCount = localStorage.getItem('cartCount');
    if (savedCartCount) {
        updateCartCount(parseInt(savedCartCount));
    }
});