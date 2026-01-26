/* ============================================
   MAIN JAVASCRIPT FILE - Procedural
   Site-wide functionality and utilities
============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initMobileMenu();
    initDropdowns();
    initBackToTop();
    initNotifications();
    initTooltips();
    initImageLazyLoading();
    initSmoothScroll();
    initAjaxForms();
    initSessionTimeout();
    initProductGallery();
    initQuantityInputs();
    initTabs();
    initAccordions();
    initModals();
    initThemeToggle();
    initLiveSearch();
    initLiveSearchResults();
    initWishlistButtons();
    initLocalProductReviews();
});

/* ========== THEME TOGGLE (SYSTEM/LIGHT/DARK) ========== */

function initThemeToggle() {
    const button = document.querySelector('.theme-toggle');
    if (!button) return;

    // Avoid native browser tooltip sticking around from `title`.
    button.removeAttribute('title');

    const modes = ['system', 'light', 'dark'];

    function readMode() {
        try {
            const raw = localStorage.getItem('theme_mode');
            return (raw === 'light' || raw === 'dark' || raw === 'system') ? raw : 'system';
        } catch {
            return 'system';
        }
    }

    function applyMode(mode) {
        const root = document.documentElement;
        if (mode === 'light') {
            root.setAttribute('data-theme', 'light');
        } else if (mode === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }

        try { localStorage.setItem('theme_mode', mode); } catch {}

        const label = 'Theme: ' + (mode.charAt(0).toUpperCase() + mode.slice(1));
        button.setAttribute('aria-label', label);
        button.removeAttribute('title');

        const icon = button.querySelector('i');
        if (icon) {
            icon.className = 'fas ' + (mode === 'light' ? 'fa-sun' : (mode === 'dark' ? 'fa-moon' : 'fa-circle-half-stroke'));
        }
    }

    function nextMode(current) {
        const idx = modes.indexOf(current);
        return modes[(idx >= 0 ? idx + 1 : 0) % modes.length];
    }

    applyMode(readMode());

    button.addEventListener('click', function() {
        const current = readMode();
        applyMode(nextMode(current));
    });
}

/* ========== LIVE SEARCH (HEADER) ========== */

function initLiveSearch() {
    const siteUrl = (typeof window.SITE_URL === 'string' && window.SITE_URL) ? window.SITE_URL : '/';

    const inputs = Array.from(document.querySelectorAll('input[name="search"]:not([type="hidden"])'));
    if (!inputs.length) return;

    const seen = new Set();
    inputs.forEach(function(input) {
        if (!input || seen.has(input)) return;
        seen.add(input);
        wireLiveSearchInput(input, siteUrl);
    });
}

/* ========== LIVE SEARCH RESULTS (PRODUCT LISTING) ========== */

function initLiveSearchResults() {
    const siteUrl = (typeof window.SITE_URL === 'string' && window.SITE_URL) ? window.SITE_URL : '/';
    const productListing = document.querySelector('.product-listing');
    if (!productListing) return;

    const listingHeader = productListing.querySelector('.listing-header');
    const headerTitleEl = listingHeader ? listingHeader.querySelector('h1') : null;
    const headerCountEl = listingHeader ? listingHeader.querySelector('p') : null;
    const productContent = productListing.querySelector('.product-content');
    if (!productContent) return;

    // Only for all.php (to avoid impacting other pages with different backends)
    const path = String(window.location.pathname || '');
    if (!/\/products\/all\.php$/i.test(path)) return;

    const input = document.querySelector('input[name="search"]:not([type="hidden"])');
    if (!input) return;

    let debounceTimer = null;
    let abortController = null;

    function buildAjaxUrl(query) {
        const params = new URLSearchParams(window.location.search);
        params.set('search', query);
        params.set('page', '1');
        params.set('ajax', '1');
        return siteUrl + 'products/all.php?' + params.toString();
    }

    function buildBrowserUrl(query) {
        const params = new URLSearchParams(window.location.search);
        if (query) params.set('search', query);
        else params.delete('search');
        params.delete('page');
        params.delete('ajax');
        const qs = params.toString();
        return siteUrl + 'products/all.php' + (qs ? ('?' + qs) : '');
    }

    function fetchAndRender(query) {
        if (abortController) abortController.abort();
        abortController = new AbortController();

        fetch(buildAjaxUrl(query), {
            signal: abortController.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) return;

                if (headerTitleEl && typeof data.title === 'string') {
                    headerTitleEl.textContent = data.title;
                }
                if (headerCountEl && typeof data.count === 'number') {
                    headerCountEl.textContent = String(data.count) + ' products found';
                }
                if (typeof data.resultsHtml === 'string') {
                    productContent.innerHTML = data.resultsHtml;
                }

                const nextUrl = buildBrowserUrl(query);
                if (typeof window.history?.replaceState === 'function') {
                    window.history.replaceState(null, '', nextUrl);
                }
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;
            });
    }

    input.addEventListener('input', function() {
        const q = String(input.value || '').trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            // Live update even from first character
            fetchAndRender(q);
        }, 220);
    });
}

function wireLiveSearchInput(input, siteUrl) {
    const form = input.closest('form');
    if (!form) return;

    input.setAttribute('autocomplete', 'off');

    let dropdown = form.querySelector('.search-suggest');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'search-suggest';
        dropdown.style.display = 'none';
        form.appendChild(dropdown);
    }

    let debounceTimer = null;
    let abortController = null;

    function hide() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
    }

    function render(items, query) {
        if (!items || !items.length) {
            hide();
            return;
        }

        dropdown.innerHTML = items.map(function(item) {
            const safeName = escapeHtml(String(item.name || ''));
            const price = (typeof item.price !== 'undefined') ? Number(item.price) : null;
            const priceText = (price !== null && !Number.isNaN(price)) ? ('$' + price.toFixed(2)) : '';
            const placeholder = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-family="Arial" font-size="10">No Image</text></svg>';
            const img = item.image_url ? String(item.image_url) : placeholder;

            return (
                '<button type="button" class="search-suggest-item" data-id="' + String(item.id) + '">' +
                    '<span class="search-suggest-thumb"><img src="' + escapeHtml(img) + '" alt=""></span>' +
                    '<span class="search-suggest-meta">' +
                        '<span class="search-suggest-name">' + safeName + '</span>' +
                        '<span class="search-suggest-price">' + escapeHtml(priceText) + '</span>' +
                    '</span>' +
                '</button>'
            );
        }).join('');

        dropdown.style.display = 'block';

        dropdown.querySelectorAll('.search-suggest-item').forEach(function(btn) {
            function go() {
                const id = this.getAttribute('data-id');
                if (id) {
                    window.location.href = siteUrl + 'products/details.php?id=' + encodeURIComponent(id);
                } else {
                    // fallback: submit search
                    input.value = query;
                    form.submit();
                }
            }

            btn.addEventListener('mousedown', go);
            btn.addEventListener('click', go);
        });
    }

    function fetchSuggestions(q) {
        if (abortController) abortController.abort();
        abortController = new AbortController();

        const url = siteUrl + 'process/search_suggest.php?q=' + encodeURIComponent(q);
        fetch(url, { signal: abortController.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    hide();
                    return;
                }
                render(data.items || [], q);
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return;
                hide();
            });
    }

    input.addEventListener('input', function() {
        const q = String(input.value || '').trim();
        if (q.length < 1) {
            hide();
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            fetchSuggestions(q);
        }, 180);
    });

    input.addEventListener('focus', function() {
        const q = String(input.value || '').trim();
        if (q.length >= 1) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetchSuggestions(q);
            }, 50);
        }
    });

    input.addEventListener('blur', function() {
        // allow click selection before hiding
        setTimeout(hide, 140);
    });

    form.addEventListener('submit', function(e) {
        const q = String(input.value || '').trim();
        if (!q) {
            e.preventDefault();
            input.focus();
            return;
        }
        input.value = q;
        hide();
    });
}

/* ========== WISHLIST (LOCAL) ========== */

function getWishlistIds() {
    try {
        const raw = localStorage.getItem('wishlist_ids');
        const ids = raw ? JSON.parse(raw) : [];
        return Array.isArray(ids) ? ids : [];
    } catch {
        return [];
    }
}

function setWishlistIds(ids) {
    try {
        localStorage.setItem('wishlist_ids', JSON.stringify(ids));
    } catch {
        // ignore
    }
}

function isInWishlist(productId) {
    const ids = getWishlistIds();
    return ids.includes(String(productId));
}

function updateWishlistButtonUI(button, active) {
    if (!button) return;
    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('far', !active);
        icon.classList.toggle('fas', active);
        icon.classList.toggle('fa-heart', true);
    }
    button.classList.toggle('active', active);
    // Keep text simple and consistent
    const text = active ? 'Wishlisted' : 'Add to Wishlist';
    // Preserve icon
    button.innerHTML = (icon ? icon.outerHTML : '<i class="far fa-heart"></i>') + ' ' + text;
}

function initWishlistButtons() {
    document.querySelectorAll('.btn-wishlist[data-product-id]').forEach(function(btn) {
        const pid = btn.getAttribute('data-product-id');
        updateWishlistButtonUI(btn, isInWishlist(pid));
    });
}

// called from inline onclick on details page
function toggleWishlist(buttonEl) {
    const btn = buttonEl && buttonEl.nodeType === 1 ? buttonEl : document.querySelector('.btn-wishlist[data-product-id]');
    if (!btn) return;

    const pid = String(btn.getAttribute('data-product-id') || '').trim();
    if (!pid) return;

    const ids = getWishlistIds();
    const idx = ids.indexOf(pid);
    let active = false;
    if (idx >= 0) {
        ids.splice(idx, 1);
        active = false;
        showNotification('Removed from wishlist', 'info');
    } else {
        ids.push(pid);
        active = true;
        showNotification('Added to wishlist', 'success');
    }
    setWishlistIds(ids);
    updateWishlistButtonUI(btn, active);
}

window.toggleWishlist = toggleWishlist;

/* ========== PRODUCT REVIEWS (LOCAL) ========== */

function reviewsStorageKey(productId) {
    return 'reviews_product_' + String(productId);
}

function loadLocalReviews(productId) {
    try {
        const raw = localStorage.getItem(reviewsStorageKey(productId));
        const list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list : [];
    } catch {
        return [];
    }
}

function saveLocalReviews(productId, reviews) {
    try {
        localStorage.setItem(reviewsStorageKey(productId), JSON.stringify(reviews));
    } catch {
        // ignore
    }
}

function renderStarsHTML(rating) {
    const r = Math.max(0, Math.min(5, Number(rating) || 0));
    let html = '';
    for (let i = 1; i <= 5; i++) {
        html += '<i class="' + (i <= r ? 'fas' : 'far') + ' fa-star"></i>';
    }
    return html;
}

function updateReviewSummaryUI(container, reviews) {
    const count = reviews.length;
    const avg = count ? (reviews.reduce((s, it) => s + (Number(it.rating) || 0), 0) / count) : 0;

    const ratingNumber = container.querySelector('[data-rating-number]');
    if (ratingNumber) ratingNumber.textContent = avg.toFixed(1);

    const countEl = container.querySelector('[data-review-count]');
    if (countEl) countEl.textContent = String(count);

    const starsWrap = container.querySelector('.review-summary .rating-stars');
    if (starsWrap) starsWrap.innerHTML = renderStarsHTML(Math.round(avg));

    // Top header rating if present
    const topValue = document.querySelector('.product-header .rating-value');
    if (topValue) topValue.textContent = avg.toFixed(1);
    const topCount = document.querySelector('.product-header .review-count');
    if (topCount) topCount.textContent = '(' + count + ' reviews)';
    const topStars = document.querySelector('.product-header .stars');
    if (topStars) topStars.innerHTML = renderStarsHTML(Math.round(avg));
}

function appendReviewToList(listEl, review) {
    const item = document.createElement('div');
    item.className = 'review-item';
    item.innerHTML =
        '<div class="review-header">' +
            '<div class="reviewer-info">' +
                '<div class="reviewer-name">You</div>' +
                '<div class="review-date">' + escapeHtml(new Date(review.created_at).toLocaleDateString()) + '</div>' +
            '</div>' +
            '<div class="review-rating">' + renderStarsHTML(review.rating) + '</div>' +
        '</div>' +
        '<div class="review-title">' + escapeHtml(review.title) + '</div>' +
        '<div class="review-content">' + escapeHtml(review.content).replace(/\n/g, '<br>') + '</div>';
    listEl.prepend(item);
}

function initLocalProductReviews() {
    // Only activates on product details page where the form exists
    const form = document.querySelector('[data-review-form]');
    const toggleBtn = document.querySelector('[data-toggle-review-form]');
    const cancelBtn = document.querySelector('[data-cancel-review]');
    const listWrap = document.querySelector('.reviews-list');
    const productId = document.querySelector('.btn-add-to-cart[data-product-id]')?.getAttribute('data-product-id');
    if (!productId) return;

    const container = document.querySelector('.product-details');
    const reviews = loadLocalReviews(productId);

    if (container) updateReviewSummaryUI(container, reviews);

    if (listWrap && reviews.length) {
        // remove server-side empty state
        const empty = listWrap.querySelector('.no-reviews');
        if (empty) empty.remove();
        reviews.slice().reverse().forEach(function(r) {
            appendReviewToList(listWrap, r);
        });
    }

    if (toggleBtn && form) {
        toggleBtn.addEventListener('click', function() {
            form.style.display = (form.style.display === 'none' || !form.style.display) ? 'block' : 'none';
            if (form.style.display === 'block') {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    if (cancelBtn && form) {
        cancelBtn.addEventListener('click', function() {
            form.style.display = 'none';
        });
    }

    // Interactive star input
    const ratingInput = form ? form.querySelector('[data-rating-input]') : null;
    const ratingValue = form ? form.querySelector('[data-rating-value]') : null;
    if (ratingInput && ratingValue) {
        ratingInput.querySelectorAll('.star').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const val = Number(btn.getAttribute('data-value') || 0);
                ratingValue.value = String(val);
                ratingInput.querySelectorAll('.star i').forEach(function(icon, idx) {
                    const active = (idx + 1) <= val;
                    icon.classList.toggle('fas', active);
                    icon.classList.toggle('far', !active);
                });
            });
        });
    }

    // Submit saves locally
    if (form && listWrap) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(form);
            const rating = Number(fd.get('rating') || 0);
            const title = String(fd.get('title') || '').trim();
            const content = String(fd.get('content') || '').trim();
            if (!rating || rating < 1 || rating > 5) {
                showNotification('Please select a rating', 'error');
                return;
            }
            if (!title || !content) {
                showNotification('Please complete your review', 'error');
                return;
            }

            const review = { rating, title, content, created_at: new Date().toISOString() };
            const current = loadLocalReviews(productId);
            current.push(review);
            saveLocalReviews(productId, current);

            // UI
            const empty = listWrap.querySelector('.no-reviews');
            if (empty) empty.remove();
            appendReviewToList(listWrap, review);
            if (container) updateReviewSummaryUI(container, current);

            form.reset();
            if (ratingInput) {
                ratingInput.querySelectorAll('.star i').forEach(function(icon) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                });
            }
            form.style.display = 'none';
            showNotification('Review saved', 'success');
        });
    }
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ========== MOBILE MENU ========== */

function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const menuClose = document.querySelector('.mobile-menu-close');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (!menuToggle || !mobileMenu) return;
    
    // Toggle mobile menu
    menuToggle.addEventListener('click', function() {
        mobileMenu.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    // Close mobile menu
    if (menuClose) {
        menuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close menu when clicking outside
    mobileMenu.addEventListener('click', function(e) {
        if (e.target === mobileMenu) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Close menu when clicking links
    const menuLinks = mobileMenu.querySelectorAll('a');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

/* ========== DROPDOWNS ========== */

function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (!toggle || !menu) return;
        
        // Toggle on click
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close other dropdowns
            closeAllDropdownsExcept(dropdown);
            
            // Toggle current dropdown
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                menu.style.display = 'none';
            }
        });
    });
}

function closeAllDropdownsExcept(exceptDropdown) {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(function(dropdown) {
        if (dropdown !== exceptDropdown) {
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });
}

/* ========== BACK TO TOP ========== */

function initBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if (!backToTopBtn) return;
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });
    
    // Smooth scroll to top
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/* ========== NOTIFICATIONS ========== */

function initNotifications() {
    // Close buttons for existing notifications
    document.querySelectorAll('.notification-close, .flash-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const notification = this.closest('.notification, .flash-message');
            if (notification) {
                notification.style.display = 'none';
            }
        });
    });
    
    // Auto-hide notifications after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.notification, .flash-message').forEach(function(notification) {
            notification.style.display = 'none';
        });
    }, 5000);
}

/* ========== TOOLTIPS ========== */

function initTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    
    tooltipElements.forEach(function(element) {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.title;
            document.body.appendChild(tooltip);
            
            // Position tooltip
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            // Store reference
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
    });
}

/* ========== IMAGE LAZY LOADING ========== */

function initImageLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img.lazy').forEach(function(img) {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

/* ========== SMOOTH SCROLL ========== */

function initSmoothScroll() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                
                window.scrollTo({
                    top: target.offsetTop - 80, // Adjust for header
                    behavior: 'smooth'
                });
                
                // Update URL hash without scrolling
                history.pushState(null, null, targetId);
            }
        });
    });
}

/* ========== AJAX FORMS ========== */

function initAjaxForms() {
    const ajaxForms = document.querySelectorAll('form[data-ajax]');
    
    ajaxForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading"></span> Processing...';
            }
            
            // Simulate AJAX request (replace with actual fetch)
            setTimeout(function() {
                // Handle response
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
                
                showNotification('Form submitted successfully!', 'success');
                form.reset();
            }, 1500);
        });
    });
}

/* ========== SESSION TIMEOUT ========== */

function initSessionTimeout() {
    let timeout;
    const timeoutDuration = 30 * 60 * 1000; // 30 minutes
    
    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(showTimeoutWarning, timeoutDuration);
    }
    
    function showTimeoutWarning() {
        // Create modal or notification
        showNotification('Your session will expire soon. Click to extend.', 'warning');
        
        // Add click listener to extend session
        document.addEventListener('click', resetTimer, { once: true });
    }
    
    // Reset timer on user activity
    ['click', 'mousemove', 'keypress', 'scroll'].forEach(function(event) {
        document.addEventListener(event, resetTimer);
    });
    
    // Start timer
    resetTimer();
}

/* ========== PRODUCT GALLERY ========== */

function initProductGallery() {
    const galleries = document.querySelectorAll('.product-gallery');
    
    galleries.forEach(function(gallery) {
        const mainImage = gallery.querySelector('.gallery-main-image');
        const thumbnails = gallery.querySelectorAll('.gallery-thumbnail');
        const zoomContainer = gallery.querySelector('.gallery-zoom');
        
        if (!mainImage || thumbnails.length === 0) return;
        
        // Thumbnail click handler
        thumbnails.forEach(function(thumbnail) {
            thumbnail.addEventListener('click', function() {
                // Update main image
                const newSrc = this.getAttribute('data-image-src') || this.src;
                mainImage.src = newSrc;
                
                // Update active thumbnail
                thumbnails.forEach(function(t) {
                    t.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Image zoom (if enabled)
        if (zoomContainer && mainImage) {
            mainImage.addEventListener('mousemove', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const xPercent = (x / rect.width) * 100;
                const yPercent = (y / rect.height) * 100;
                
                zoomContainer.style.backgroundImage = `url(${this.src})`;
                zoomContainer.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
                zoomContainer.style.display = 'block';
            });
            
            mainImage.addEventListener('mouseleave', function() {
                zoomContainer.style.display = 'none';
            });
        }
    });
}

/* ========== QUANTITY INPUTS ========== */

function initQuantityInputs() {
    document.querySelectorAll('.quantity-input').forEach(function(input) {
        const min = parseInt(input.getAttribute('min')) || 1;
        const max = parseInt(input.getAttribute('max')) || 99;
        
        input.addEventListener('change', function() {
            let value = parseInt(this.value) || min;
            
            if (value < min) value = min;
            if (value > max) value = max;
            
            this.value = value;
        });
        
        // Add increment/decrement buttons if they don't exist
        if (!input.parentNode.querySelector('.quantity-btn')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'quantity-control';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            
            const decreaseBtn = document.createElement('button');
            decreaseBtn.className = 'quantity-btn decrease';
            decreaseBtn.innerHTML = '<i class="fas fa-minus"></i>';
            wrapper.insertBefore(decreaseBtn, input);
            
            const increaseBtn = document.createElement('button');
            increaseBtn.className = 'quantity-btn increase';
            increaseBtn.innerHTML = '<i class="fas fa-plus"></i>';
            wrapper.appendChild(increaseBtn);
            
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || min;
                if (value > min) {
                    input.value = value - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || min;
                if (value < max) {
                    input.value = value + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });
}

/* ========== TABS ========== */

function initTabs() {
    const tabContainers = document.querySelectorAll('.tabs');
    
    tabContainers.forEach(function(container) {
        const tabs = container.querySelectorAll('.tab');
        const contents = container.querySelectorAll('.tab-content');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                
                // Update active tab
                tabs.forEach(function(t) {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                
                // Show target content
                contents.forEach(function(content) {
                    content.classList.remove('active');
                    if (content.getAttribute('data-tab') === target) {
                        content.classList.add('active');
                    }
                });
            });
        });
    });
}

/* ========== ACCORDIONS ========== */

function initAccordions() {
    const accordions = document.querySelectorAll('.accordion');
    
    accordions.forEach(function(accordion) {
        const headers = accordion.querySelectorAll('.accordion-header');
        
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isOpen = content.style.display === 'block';
                
                // Close all accordion items in this group
                if (accordion.hasAttribute('data-single-open')) {
                    accordion.querySelectorAll('.accordion-content').forEach(function(item) {
                        item.style.display = 'none';
                        item.previousElementSibling.classList.remove('active');
                    });
                }
                
                // Toggle current item
                if (isOpen) {
                    content.style.display = 'none';
                    this.classList.remove('active');
                } else {
                    content.style.display = 'block';
                    this.classList.add('active');
                }
            });
        });
    });
}

/* ========== MODALS ========== */

function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    const modalCloses = document.querySelectorAll('.modal-close, .modal-overlay');
    
    // Open modal
    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const target = this.getAttribute('data-modal-target');
            const modal = document.querySelector(target);
            
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Close modal
    modalCloses.forEach(function(close) {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(function(modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });
}

/* ========== UTILITY FUNCTIONS ========== */

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Format date
function formatDate(date, format = 'YYYY-MM-DD') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('Copied to clipboard!', 'success');
    }).catch(function(err) {
        showNotification('Failed to copy: ' + err, 'error');
    });
}

// Get URL parameters
function getUrlParams() {
    const params = {};
    const queryString = window.location.search.slice(1);
    const pairs = queryString.split('&');
    
    pairs.forEach(function(pair) {
        const [key, value] = pair.split('=');
        if (key) {
            params[decodeURIComponent(key)] = decodeURIComponent(value || '');
        }
    });
    
    return params;
}

// Set cookie
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

// Get cookie
function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
    }
    
    return null;
}

// Delete cookie
function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
}

// Show notification (reusable)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                         type === 'error' ? 'exclamation-circle' : 
                         type === 'warning' ? 'exclamation-triangle' : 
                         'info-circle'}"></i>
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

// Export functions globally
window.utils = {
    debounce,
    throttle,
    formatDate,
    formatCurrency,
    copyToClipboard,
    getUrlParams,
    setCookie,
    getCookie,
    deleteCookie,
    showNotification
};