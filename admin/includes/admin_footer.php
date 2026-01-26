<?php
/* ============================================
   ADMIN FOOTER - Reusable Component
   Closes main content and includes scripts
============================================ */
?>
    </main> <!-- Closes admin-main from sidebar -->
</div> <!-- Closes admin-container from header -->

<!-- Admin Scripts -->
<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const adminContainer = document.querySelector('.admin-container');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
            if (isMobile) {
                sidebar.classList.toggle('show');
                return;
            }

            sidebar.classList.toggle('collapsed');
            adminContainer.classList.toggle('sidebar-collapsed');

            // Save preference to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Restore sidebar state from localStorage
        const savedState = localStorage.getItem('sidebarCollapsed');
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile && savedState === 'true') {
            sidebar.classList.add('collapsed');
            adminContainer.classList.add('sidebar-collapsed');
        }
    }
    
    // User dropdown toggle
    const userMenu = document.querySelector('.admin-user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userMenu.classList.remove('active');
        });
    }

    // Notifications dropdown toggle
    const notificationsMenu = document.getElementById('notificationsMenu');
    const notificationBtn = document.getElementById('notificationBtn');
    if (notificationsMenu && notificationBtn) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsMenu.classList.toggle('active');
        });

        document.addEventListener('click', function() {
            notificationsMenu.classList.remove('active');
        });
    }

    // Theme toggle (system/light/dark)
    (function initThemeToggle() {
        const button = document.querySelector('.theme-toggle');
        if (!button) return;

        // Avoid native browser tooltip from `title`.
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

            try { localStorage.setItem('theme_mode', mode); } catch (e) {}

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
    })();
    
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.admin-flash-message');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.animation = 'slideUp 0.3s ease forwards';
            setTimeout(function() {
                message.remove();
            }, 300);
        }, 5000);
    });
});

// Global notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type} show`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 4000);
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}
</script>

<style>
@keyframes slideUp {
    to {
        transform: translateY(-100%);
        opacity: 0;
    }
}
</style>

</body>
</html>
