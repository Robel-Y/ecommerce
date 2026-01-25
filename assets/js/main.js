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
});

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