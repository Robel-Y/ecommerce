/* ============================================
   ADMIN PANEL JAVASCRIPT - Procedural
   Admin-specific functionality
============================================ */

document.addEventListener('DOMContentLoaded', function() {
    initAdminCharts();
    initDataTables();
    initBulkActions();
    initImageUpload();
    initRichTextEditor();
    initAdminSearch();
});

/* ========== ADMIN CHARTS ========== */

function initAdminCharts() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(function(container) {
        const canvas = container.querySelector('canvas');
        if (!canvas) return;
        
        const chartType = container.getAttribute('data-chart-type') || 'line';
        const chartData = JSON.parse(container.getAttribute('data-chart-data') || '{}');
        
        // Initialize Chart.js chart
        new Chart(canvas, {
            type: chartType,
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
}

/* ========== DATA TABLES ========== */

function initDataTables() {
    const dataTables = document.querySelectorAll('.data-table');
    
    dataTables.forEach(function(table) {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sortable]');
        
        headers.forEach(function(header) {
            header.style.cursor = 'pointer';
            
            header.addEventListener('click', function() {
                const column = this.cellIndex;
                const isAscending = this.classList.contains('sort-asc');
                
                // Remove sort classes from all headers
                headers.forEach(function(h) {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Set new sort direction
                this.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
                
                // Sort table rows
                sortTable(table, column, !isAscending);
            });
        });
        
        // Add search functionality
        const searchInput = table.parentNode.querySelector('.table-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterTable(table, this.value);
            });
        }
    });
}

function sortTable(table, column, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort(function(a, b) {
        const aVal = a.cells[column].textContent.trim();
        const bVal = b.cells[column].textContent.trim();
        
        // Try to compare as numbers
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return ascending ? aNum - bNum : bNum - aNum;
        }
        
        // Compare as strings
        return ascending ? 
            aVal.localeCompare(bVal) : 
            bVal.localeCompare(aVal);
    });
    
    // Reorder rows
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchTerm.toLowerCase();
    
    rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchLower) ? '' : 'none';
    });
}

/* ========== BULK ACTIONS ========== */

function initBulkActions() {
    const selectAllCheckbox = document.querySelector('.select-all');
    const itemCheckboxes = document.querySelectorAll('.select-item');
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            
            itemCheckboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
            
            updateBulkActions();
        });
    }
    
    itemCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.select-item:checked').length;
        
        if (checkedCount > 0) {
            bulkActions.style.display = 'block';
            bulkActions.querySelector('.selected-count').textContent = checkedCount;
        } else {
            bulkActions.style.display = 'none';
        }
    }
    
    // Bulk action buttons
    document.querySelectorAll('.bulk-action-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const selectedIds = [];
            
            document.querySelectorAll('.select-item:checked').forEach(function(checkbox) {
                selectedIds.push(checkbox.value);
            });
            
            if (selectedIds.length === 0) {
                showNotification('Please select items first', 'warning');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} item(s)?`)) {
                // Send request to server
                console.log(`Performing ${action} on:`, selectedIds);
                
                // Reload page or update UI
                setTimeout(function() {
                    showNotification(`${selectedIds.length} items ${action}ed successfully`, 'success');
                    location.reload();
                }, 1000);
            }
        });
    });
}

/* ========== IMAGE UPLOAD ========== */

function initImageUpload() {
    const fileInputs = document.querySelectorAll('.image-upload input[type="file"]');
    
    fileInputs.forEach(function(input) {
        const preview = input.parentNode.querySelector('.image-preview');
        
        input.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Drag and drop
        const dropZone = input.parentNode;
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            if (file) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

/* ========== RICH TEXT EDITOR ========== */

function initRichTextEditor() {
    const editors = document.querySelectorAll('.rich-text-editor');
    
    editors.forEach(function(editor) {
        const toolbar = editor.querySelector('.editor-toolbar');
        const content = editor.querySelector('.editor-content');
        
        if (!toolbar || !content) return;
        
        // Toolbar buttons
        toolbar.addEventListener('click', function(e) {
            const button = e.target.closest('.editor-button');
            if (!button) return;
            
            e.preventDefault();
            
            const command = button.getAttribute('data-command');
            const value = button.getAttribute('data-value');
            
            // Execute command
            document.execCommand(command, false, value);
            
            // Update button states
            updateButtonStates();
        });
        
        function updateButtonStates() {
            toolbar.querySelectorAll('.editor-button').forEach(function(button) {
                const command = button.getAttribute('data-command');
                
                if (command === 'formatBlock' || command === 'fontSize') {
                    // Handle block formatting
                } else {
                    const isActive = document.queryCommandState(command);
                    button.classList.toggle('active', isActive);
                }
            });
        }
        
        // Update button states on selection
        content.addEventListener('keyup', updateButtonStates);
        content.addEventListener('mouseup', updateButtonStates);
    });
}

/* ========== ADMIN SEARCH ========== */

function initAdminSearch() {
    const searchInput = document.querySelector('.admin-search input');
    
    if (searchInput) {
        let timeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            
            timeout = setTimeout(function() {
                const query = searchInput.value.trim();
                
                if (query.length >= 2) {
                    performAdminSearch(query);
                } else {
                    clearSearchResults();
                }
            }, 300);
        });
        
        // Close search on escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearSearchResults();
                this.value = '';
            }
        });
    }
}

function performAdminSearch(query) {
    // Show loading
    showNotification('Searching...', 'info');
    
    // Simulate API call
    setTimeout(function() {
        // Update search results
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="search-result">
                    <h4>Products</h4>
                    <p>Found 5 products matching "${query}"</p>
                </div>
                <div class="search-result">
                    <h4>Orders</h4>
                    <p>Found 3 orders matching "${query}"</p>
                </div>
                <div class="search-result">
                    <h4>Users</h4>
                    <p>Found 2 users matching "${query}"</p>
                </div>
            `;
            resultsContainer.style.display = 'block';
        }
    }, 500);
}

function clearSearchResults() {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

/* ========== ADMIN UTILITIES ========== */

// Export report
function exportReport(format) {
    showNotification(`Exporting report as ${format}...`, 'info');
    
    // Simulate export
    setTimeout(function() {
        showNotification('Report exported successfully', 'success');
        
        // Create download link
        const link = document.createElement('a');
        link.href = '#'; // Replace with actual export URL
        link.download = `report_${new Date().toISOString().slice(0,10)}.${format}`;
        link.click();
    }, 1500);
}

// Quick stats update
function refreshStats() {
    const stats = document.querySelectorAll('.stat-card');
    
    stats.forEach(function(stat) {
        stat.classList.add('refreshing');
    });
    
    // Simulate API call
    setTimeout(function() {
        stats.forEach(function(stat) {
            stat.classList.remove('refreshing');
        });
        
        showNotification('Stats updated', 'success');
    }, 1000);
}

// Dark mode toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('admin-dark-mode', isDark);
    
    showNotification(`Dark mode ${isDark ? 'enabled' : 'disabled'}`, 'success');
}

// Load dark mode preference
function loadDarkModePreference() {
    const darkMode = localStorage.getItem('admin-dark-mode');
    if (darkMode === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// Initialize on load
loadDarkModePreference();

// Export admin functions
window.admin = {
    exportReport,
    refreshStats,
    toggleDarkMode
};