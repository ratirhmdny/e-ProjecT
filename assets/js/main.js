/**
 * E-SPP System JavaScript
 * Main JavaScript file for application functionality
 */

// Global variables
let currentPage = 1;
let searchTimeout = null;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize application
 */
function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize confirmations
    initializeConfirmations();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize auto-hide alerts
    initializeAutoHideAlerts();
    
    // Initialize charts if they exist
    initializeCharts();
    
    // Initialize date pickers
    initializeDatePickers();
    
    // Initialize number formatting
    initializeNumberFormatting();
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize confirmation dialogs
 */
function initializeConfirmations() {
    // Delete confirmations
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.dataset.message || 'Are you sure you want to delete this item?';
            const form = this.closest('form');
            
            if (confirm(message)) {
                if (form) {
                    form.submit();
                } else {
                    window.location.href = this.href;
                }
            }
        });
    });
    
    // Action confirmations
    document.querySelectorAll('.btn-confirm').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.dataset.message || 'Are you sure you want to proceed?';
            
            if (confirm(message)) {
                window.location.href = this.href;
            }
        });
    });
}

/**
 * Initialize form validations
 */
function initializeFormValidations() {
    // Real-time validation
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    // Form submission validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Validate individual field
 * @param {HTMLElement} field
 * @returns {boolean}
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // Clear previous errors
    clearFieldError(field);
    
    // Check required
    if (required && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Please enter a valid email address');
            return false;
        }
    }
    
    // Phone validation (Indonesian format)
    if (field.name === 'phone' && value) {
        const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,9}$/;
        if (!phoneRegex.test(value)) {
            showFieldError(field, 'Please enter a valid Indonesian phone number');
            return false;
        }
    }
    
    // NIM validation
    if (field.name === 'nim' && value) {
        const nimRegex = /^[0-9]{8,12}$/;
        if (!nimRegex.test(value)) {
            showFieldError(field, 'NIM must be 8-12 digits');
            return false;
        }
    }
    
    // Password validation
    if (field.name === 'password' && value) {
        if (value.length < 6) {
            showFieldError(field, 'Password must be at least 6 characters');
            return false;
        }
    }
    
    // Confirm password validation
    if (field.name === 'confirm_password' && value) {
        const password = document.querySelector('input[name="password"]').value;
        if (value !== password) {
            showFieldError(field, 'Passwords do not match');
            return false;
        }
    }
    
    return true;
}

/**
 * Validate entire form
 * @param {HTMLElement} form
 * @returns {boolean}
 */
function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('.form-control');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Show field error
 * @param {HTMLElement} field
 * @param {string} message
 */
function showFieldError(field, message) {
    // Remove existing error
    clearFieldError(field);
    
    // Add error class
    field.classList.add('is-invalid');
    
    // Create error element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    // Insert after field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

/**
 * Clear field error
 * @param {HTMLElement} field
 */
function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInputs = document.querySelectorAll('input[name="search"], input[data-search]');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(() => {
                performSearch(this.value, this.dataset.target);
            }, 300);
        });
    });
}

/**
 * Perform search
 * @param {string} query
 * @param {string} target
 */
function performSearch(query, target) {
    // This would typically make an AJAX request
    // For now, we'll just update the URL and reload
    const url = new URL(window.location);
    
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    
    // Reset page to 1 when searching
    url.searchParams.set('page', '1');
    
    window.location.href = url.toString();
}

/**
 * Initialize auto-hide alerts
 */
function initializeAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    });
}

/**
 * Initialize charts
 */
function initializeCharts() {
    // Payment chart
    const paymentChartCanvas = document.getElementById('paymentChart');
    if (paymentChartCanvas && typeof Chart !== 'undefined') {
        const ctx = paymentChartCanvas.getContext('2d');
        
        // Get data from data attributes
        const chartData = JSON.parse(paymentChartCanvas.dataset.chart || '{}');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    label: 'Pembayaran',
                    data: chartData.data || [],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Program chart
    const programChartCanvas = document.getElementById('programChart');
    if (programChartCanvas && typeof Chart !== 'undefined') {
        const ctx = programChartCanvas.getContext('2d');
        
        const chartData = JSON.parse(programChartCanvas.dataset.chart || '{}');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    data: chartData.data || [],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(6, 182, 212, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(6, 182, 212, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

/**
 * Initialize date pickers
 */
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set min date to today for due dates
        if (input.name === 'due_date') {
            const today = new Date().toISOString().split('T')[0];
            input.setAttribute('min', today);
        }
    });
}

/**
 * Initialize number formatting
 */
function initializeNumberFormatting() {
    // Format currency inputs
    document.querySelectorAll('input[data-currency]').forEach(input => {
        input.addEventListener('input', function() {
            formatCurrencyInput(this);
        });
        
        input.addEventListener('blur', function() {
            formatCurrencyDisplay(this);
        });
    });
    
    // Format currency displays
    document.querySelectorAll('[data-format="currency"]').forEach(element => {
        const value = parseFloat(element.textContent);
        if (!isNaN(value)) {
            element.textContent = formatCurrency(value);
        }
    });
}

/**
 * Format currency input
 * @param {HTMLElement} input
 */
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        value = parseInt(value, 10);
        input.value = value.toLocaleString('id-ID');
    }
}

/**
 * Format currency display
 * @param {HTMLElement} input
 */
function formatCurrencyDisplay(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        value = parseInt(value, 10);
        input.value = 'Rp ' + value.toLocaleString('id-ID');
    }
}

/**
 * Format currency
 * @param {number} amount
 * @returns {string}
 */
function formatCurrency(amount) {
    return 'Rp ' + amount.toLocaleString('id-ID');
}

/**
 * Show loading state
 * @param {HTMLElement} element
 */
function showLoading(element) {
    element.innerHTML = '<div class="spinner"></div>';
    element.disabled = true;
}

/**
 * Hide loading state
 * @param {HTMLElement} element
 * @param {string} originalText
 */
function hideLoading(element, originalText) {
    element.innerHTML = originalText;
    element.disabled = false;
}

/**
 * Show toast notification
 * @param {string} message
 * @param {string} type
 */
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

/**
 * Export data to CSV
 * @param {Array} data
 * @param {Array} headers
 * @param {string} filename
 */
function exportToCSV(data, headers, filename) {
    let csvContent = headers.join(',') + '\n';
    
    data.forEach(row => {
        csvContent += row.join(',') + '\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Get URL parameter
 * @param {string} name
 * @returns {string|null}
 */
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Update URL parameter
 * @param {string} name
 * @param {string} value
 */
function updateUrlParameter(name, value) {
    const url = new URL(window.location);
    
    if (value) {
        url.searchParams.set(name, value);
    } else {
        url.searchParams.delete(name);
    }
    
    window.history.replaceState({}, '', url);
}

/**
 * Debounce function
 * @param {Function} func
 * @param {number} wait
 * @returns {Function}
 */
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

/**
 * Format date
 * @param {string} dateString
 * @returns {string}
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Calculate days until due date
 * @param {string} dueDate
 * @returns {number}
 */
function daysUntilDue(dueDate) {
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays;
}

/**
 * Get status badge class
 * @param {string} status
 * @returns {string}
 */
function getStatusBadgeClass(status) {
    const classes = {
        'pending': 'badge-warning',
        'paid': 'badge-success',
        'overdue': 'badge-danger',
        'cancelled': 'badge-secondary',
        'confirmed': 'badge-info',
        'rejected': 'badge-danger'
    };
    
    return classes[status.toLowerCase()] || 'badge-secondary';
}

// Export functions for use in other scripts
window.ESSPP = {
    showToast,
    showLoading,
    hideLoading,
    formatCurrency,
    exportToCSV,
    getUrlParameter,
    updateUrlParameter,
    debounce,
    formatDate,
    daysUntilDue,
    getStatusBadgeClass,
    validateField,
    validateForm
};