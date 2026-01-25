<!-- Footer Section -->
</main> <!-- Close main content from header -->

<footer class="main-footer">
    <div class="container">
        <!-- Footer Top -->
        <div class="footer-top">
            <div class="footer-col">
                <div class="footer-logo">
                    <div class="logo-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-main">Modern</span>
                        <span class="logo-sub">Shop</span>
                    </div>
                </div>
                <p class="footer-description">
                    Your one-stop destination for premium products. Quality, convenience, and customer satisfaction guaranteed.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="products/all.php"><i class="fas fa-chevron-right"></i> Shop</a></li>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    <li><a href="faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Customer Service</h3>
                <ul class="footer-links">
                    <li><a href="shipping.php"><i class="fas fa-chevron-right"></i> Shipping Policy</a></li>
                    <li><a href="returns.php"><i class="fas fa-chevron-right"></i> Return Policy</a></li>
                    <li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                    <li><a href="terms.php"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                    <li><a href="support.php"><i class="fas fa-chevron-right"></i> Support Center</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Contact Info</h3>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Commerce Street, Business City, BC 12345</span>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <span>+1 (555) 123-4567</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>support@modernshop.com</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                    </li>
                </ul>
                
                <div class="newsletter">
                    <h4>Newsletter</h4>
                    <p>Subscribe for latest updates</p>
                    <form class="footer-newsletter">
                        <input type="email" placeholder="Your email" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="payment-methods">
                <span>We Accept:</span>
                <div class="payment-icons">
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="MasterCard"></i>
                    <i class="fab fa-cc-amex" title="American Express"></i>
                    <i class="fab fa-cc-paypal" title="PayPal"></i>
                    <i class="fab fa-cc-apple-pay" title="Apple Pay"></i>
                    <i class="fab fa-google-pay" title="Google Pay"></i>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Modern Shop. All rights reserved.</p>
                <p>Made with <i class="fas fa-heart"></i> for better shopping experience</p>
            </div>
            
            <div class="footer-apps">
                <a href="#" class="app-link">
                    <i class="fab fa-google-play"></i>
                    <span>Get it on<br><strong>Google Play</strong></span>
                </a>
                <a href="#" class="app-link">
                    <i class="fab fa-app-store"></i>
                    <span>Download on the<br><strong>App Store</strong></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button class="back-to-top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>
</footer>

<!-- JavaScript Files -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/validation.js"></script>
<script src="assets/js/cart.js"></script>

<!-- Custom Scripts -->
<script>
// Mobile Menu Toggle
document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
    document.querySelector('.mobile-menu').classList.add('active');
    document.body.style.overflow = 'hidden';
});

document.querySelector('.mobile-menu-close')?.addEventListener('click', function() {
    document.querySelector('.mobile-menu').classList.remove('active');
    document.body.style.overflow = '';
});

// Close mobile menu when clicking outside
document.querySelector('.mobile-menu')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Dropdown functionality
document.querySelectorAll('.dropdown').forEach(dropdown => {
    dropdown.addEventListener('mouseenter', function() {
        this.querySelector('.dropdown-content').style.display = 'block';
    });
    
    dropdown.addEventListener('mouseleave', function() {
        this.querySelector('.dropdown-content').style.display = '';
    });
});

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
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Flash message close
document.querySelectorAll('.flash-close').forEach(button => {
    button.addEventListener('click', function() {
        this.closest('.flash-message').style.display = 'none';
    });
});

// Auto-hide flash messages
setTimeout(() => {
    document.querySelectorAll('.flash-message').forEach(msg => {
        msg.style.display = 'none';
    });
}, 5000);

// Footer newsletter
document.querySelector('.footer-newsletter')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    
    if (validateEmail(email)) {
        showNotification('Thank you for subscribing to our newsletter!', 'success');
        this.reset();
    } else {
        showNotification('Please enter a valid email address', 'error');
    }
});

// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Show notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    // Show
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove
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

// Initialize tooltips
document.querySelectorAll('[title]').forEach(element => {
    element.addEventListener('mouseenter', function(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.title;
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
        
        this._tooltip = tooltip;
    });
    
    element.addEventListener('mouseleave', function() {
        if (this._tooltip) {
            this._tooltip.remove();
            delete this._tooltip;
        }
    });
});
</script>

</body>
</html>