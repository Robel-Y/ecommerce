/* ============================================
   FOOTER SCRIPT - Footer Functionality
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    // Back to Top Button
    const backToTop = document.querySelector('.back-to-top');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Add keyboard support
        backToTop.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Footer Newsletter Form
    const footerNewsletter = document.querySelector('.footer-newsletter');
    
    if (footerNewsletter) {
        footerNewsletter.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();
            
            if (validateEmail(email)) {
                // Show success notification
                showNotification('Thank you for subscribing to our newsletter!', 'success');
                
                // Reset form
                this.reset();
                emailInput.focus();
                
                // Optional: Send to server
                // submitNewsletter(email);
            } else {
                // Show error notification
                showNotification('Please enter a valid email address', 'error');
                emailInput.focus();
                emailInput.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    emailInput.style.animation = '';
                }, 500);
            }
        });
    }
    
    // Email Validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Notification System
    let notificationCount = 0;
    const notificationContainer = document.createElement('div');
    notificationContainer.className = 'notification-container';
    document.body.appendChild(notificationContainer);
    
    function showNotification(message, type = 'info', duration = 5000) {
        notificationCount++;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'info': 'info-circle',
            'warning': 'exclamation-triangle'
        };
        
        notification.innerHTML = `
            <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" aria-label="Close notification">
                <i class="fas fa-times"></i>
            </button>
            <div class="notification-progress"></div>
        `;
        
        // Add to container
        notificationContainer.appendChild(notification);
        
        // Show with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto-remove after duration
        const removeTimer = setTimeout(() => {
            closeNotification(notification);
        }, duration);
        
        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            clearTimeout(removeTimer);
            closeNotification(notification);
        });
        
        // Pause progress on hover
        notification.addEventListener('mouseenter', () => {
            notification.querySelector('.notification-progress').style.animationPlayState = 'paused';
        });
        
        notification.addEventListener('mouseleave', () => {
            notification.querySelector('.notification-progress').style.animationPlayState = 'running';
        });
        
        // Limit number of notifications
        if (notificationContainer.children.length > 5) {
            notificationContainer.removeChild(notificationContainer.firstChild);
        }
    }
    
    function closeNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    // Flash Messages
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(flash => {
        // Auto-remove flash messages after 5 seconds
        const removeTimer = setTimeout(() => {
            closeFlashMessage(flash);
        }, 5000);
        
        // Close button
        const closeBtn = flash.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(removeTimer);
                closeFlashMessage(flash);
            });
        }
        
        // Pause on hover
        flash.addEventListener('mouseenter', () => {
            clearTimeout(removeTimer);
        });
        
        flash.addEventListener('mouseleave', () => {
            setTimeout(() => {
                closeFlashMessage(flash);
            }, 5000);
        });
    });
    
    function closeFlashMessage(flash) {
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (flash.parentNode) {
                flash.parentNode.removeChild(flash);
            }
        }, 300);
    }
    
    // Tooltip System
    function initTooltips() {
        const elementsWithTooltip = document.querySelectorAll('[title]');
        
        elementsWithTooltip.forEach(element => {
            // Remove default browser tooltip
            const tooltipText = element.getAttribute('title');
            element.removeAttribute('title');
            
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = tooltipText;
                tooltip.setAttribute('role', 'tooltip');
                
                document.body.appendChild(tooltip);
                
                // Position tooltip
                const rect = this.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
                
                tooltip.style.top = (rect.top + scrollTop - tooltip.offsetHeight - 10) + 'px';
                tooltip.style.left = (rect.left + scrollLeft + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                
                // Adjust if off screen
                const tooltipRect = tooltip.getBoundingClientRect();
                if (tooltipRect.left < 10) {
                    tooltip.style.left = '10px';
                }
                if (tooltipRect.right > window.innerWidth - 10) {
                    tooltip.style.left = (window.innerWidth - tooltipRect.width - 10) + 'px';
                }
                if (tooltipRect.top < 10) {
                    tooltip.style.top = (rect.bottom + scrollTop + 10) + 'px';
                }
                
                this._tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    delete this._tooltip;
                }
            });
            
            // Keyboard support
            element.addEventListener('focus', function() {
                const event = new MouseEvent('mouseenter');
                this.dispatchEvent(event);
            });
            
            element.addEventListener('blur', function() {
                const event = new MouseEvent('mouseleave');
                this.dispatchEvent(event);
            });
        });
    }
    
    initTooltips();
    
    // Social Links Animation
    const socialLinks = document.querySelectorAll('.social-link');
    
    socialLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.1)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Current Year in Copyright
    const yearElement = document.querySelector('.copyright');
    if (yearElement) {
        const yearText = yearElement.textContent;
        const currentYear = new Date().getFullYear();
        yearElement.innerHTML = yearText.replace('<?php echo date("Y"); ?>', currentYear);
    }
    
    // Expose showNotification globally
    window.showNotification = showNotification;
    
    // Optional: Newsletter API submission
    async function submitNewsletter(email) {
        try {
            const response = await fetch(SITE_URL + 'api/newsletter/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Successfully subscribed to newsletter!', 'success');
            } else {
                showNotification(data.message || 'Subscription failed', 'error');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            showNotification('Failed to subscribe. Please try again.', 'error');
        }
    }
});