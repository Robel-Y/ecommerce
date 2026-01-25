/* ============================================
   FORM VALIDATION SYSTEM - Procedural
   Client-side validation for all forms
============================================ */

// Initialize validation system
document.addEventListener('DOMContentLoaded', function() {
    initFormValidation();
    setupLiveValidation();
    setupPasswordStrength();
});

// Main initialization function
function initFormValidation() {
    // Auto-attach validation to all forms
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        // Add submit event listener
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Add blur validation for each input
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            // Clear errors on input
            input.addEventListener('input', function() {
                clearFieldError(input);
            });
        });
    });
}

// Setup live validation for specific fields
function setupLiveValidation() {
    const liveFields = document.querySelectorAll('[data-live-validate]');
    
    liveFields.forEach(function(field) {
        field.addEventListener('input', function() {
            validateFieldWithDebounce(field, 500);
        });
    });
}

// Setup password strength meter
function setupPasswordStrength() {
    const passwordFields = document.querySelectorAll('[data-password-strength]');
    
    passwordFields.forEach(function(field) {
        field.addEventListener('input', function() {
            updatePasswordStrength(field);
        });
    });
}

/* ========== FORM VALIDATION FUNCTIONS ========== */

// Main form validation function
function validateForm(form) {
    let isValid = true;
    
    // Clear all previous errors
    clearFormErrors(form);
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(function(field) {
        if (!validateRequired(field.value)) {
            isValid = false;
            showFieldError(field, 'This field is required');
        }
    });
    
    // Validate fields with specific rules
    const ruleFields = form.querySelectorAll('[data-validate-rule]');
    
    ruleFields.forEach(function(field) {
        const rule = field.getAttribute('data-validate-rule');
        const value = field.value.trim();
        
        // Skip empty optional fields
        if (!value && !field.required) return;
        
        let fieldValid = true;
        let message = '';
        
        switch(rule) {
            case 'email':
                fieldValid = validateEmail(value);
                message = 'Please enter a valid email address';
                break;
                
            case 'phone':
                fieldValid = validatePhone(value);
                message = 'Please enter a valid phone number (10 digits)';
                break;
                
            case 'password':
                fieldValid = validatePassword(value);
                message = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
                break;
                
            case 'confirm-password':
                const passwordField = form.querySelector('[name="password"], [data-validate-rule="password"]');
                fieldValid = validateConfirmPassword(value, passwordField.value);
                message = 'Passwords do not match';
                break;
                
            case 'credit-card':
                fieldValid = validateCreditCard(value);
                message = 'Please enter a valid credit card number';
                break;
                
            case 'expiry-date':
                fieldValid = validateExpiryDate(value);
                message = 'Please enter a valid expiry date (MM/YY)';
                break;
                
            case 'cvv':
                fieldValid = validateCVV(value);
                message = 'Please enter a valid CVV (3-4 digits)';
                break;
                
            case 'zipcode':
                fieldValid = validateZipCode(value);
                message = 'Please enter a valid ZIP code';
                break;
                
            case 'url':
                fieldValid = validateURL(value);
                message = 'Please enter a valid URL';
                break;
                
            case 'number':
                fieldValid = validateNumber(value);
                message = 'Please enter a valid number';
                break;
                
            case 'min-length':
                const min = parseInt(field.getAttribute('data-min-length'));
                fieldValid = validateMinLength(value, min);
                message = `Must be at least ${min} characters`;
                break;
                
            case 'max-length':
                const max = parseInt(field.getAttribute('data-max-length'));
                fieldValid = validateMaxLength(value, max);
                message = `Must be at most ${max} characters`;
                break;
                
            case 'range':
                const minRange = parseInt(field.getAttribute('data-min'));
                const maxRange = parseInt(field.getAttribute('data-max'));
                fieldValid = validateRange(value, minRange, maxRange);
                message = `Must be between ${minRange} and ${maxRange}`;
                break;
                
            case 'pattern':
                const pattern = new RegExp(field.getAttribute('data-pattern'));
                fieldValid = validatePattern(value, pattern);
                message = 'Please match the requested format';
                break;
        }
        
        if (!fieldValid) {
            isValid = false;
            showFieldError(field, message);
        }
    });
    
    // Validate checkbox groups (at least one checked)
    const checkboxGroups = form.querySelectorAll('[data-validate-checkbox-group]');
    
    checkboxGroups.forEach(function(group) {
        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
        const groupName = group.getAttribute('data-validate-checkbox-group');
        let atLeastOneChecked = false;
        
        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                atLeastOneChecked = true;
            }
        });
        
        if (!atLeastOneChecked) {
            isValid = false;
            showGroupError(group, `Please select at least one ${groupName}`);
        }
    });
    
    // Show success message if all valid
    if (isValid) {
        showFormSuccess(form);
    }
    
    return isValid;
}

/* ========== FIELD VALIDATION FUNCTIONS ========== */

// Validate single field
function validateField(field) {
    const value = field.value.trim();
    
    // Clear previous error
    clearFieldError(field);
    
    // Skip validation if field is empty and not required
    if (!value && !field.required) {
        return true;
    }
    
    let isValid = true;
    let message = '';
    
    // Check required
    if (field.required && !validateRequired(value)) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Check by type
    else if (field.type === 'email') {
        if (!validateEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    else if (field.type === 'tel') {
        if (!validatePhone(value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
    }
    
    else if (field.type === 'url') {
        if (!validateURL(value)) {
            isValid = false;
            message = 'Please enter a valid URL';
        }
    }
    
    else if (field.type === 'number') {
        if (!validateNumber(value)) {
            isValid = false;
            message = 'Please enter a valid number';
        }
    }
    
    // Check by pattern
    else if (field.pattern) {
        const pattern = new RegExp(field.pattern);
        if (!validatePattern(value, pattern)) {
            isValid = false;
            message = field.getAttribute('title') || 'Please match the requested format';
        }
    }
    
    // Check min/max length
    if (field.minLength && value.length < field.minLength) {
        isValid = false;
        message = `Minimum length is ${field.minLength} characters`;
    }
    
    if (field.maxLength && value.length > field.maxLength) {
        isValid = false;
        message = `Maximum length is ${field.maxLength} characters`;
    }
    
    // Check custom validation rules
    const customRules = field.getAttribute('data-validate-custom');
    if (customRules) {
        const rules = customRules.split(',');
        
        rules.forEach(function(rule) {
            const [ruleName, ruleValue] = rule.split(':');
            
            switch(ruleName) {
                case 'min':
                    if (parseInt(value) < parseInt(ruleValue)) {
                        isValid = false;
                        message = `Minimum value is ${ruleValue}`;
                    }
                    break;
                    
                case 'max':
                    if (parseInt(value) > parseInt(ruleValue)) {
                        isValid = false;
                        message = `Maximum value is ${ruleValue}`;
                    }
                    break;
                    
                case 'minLength':
                    if (value.length < parseInt(ruleValue)) {
                        isValid = false;
                        message = `Minimum length is ${ruleValue} characters`;
                    }
                    break;
                    
                case 'maxLength':
                    if (value.length > parseInt(ruleValue)) {
                        isValid = false;
                        message = `Maximum length is ${ruleValue} characters`;
                    }
                    break;
            }
        });
    }
    
    if (!isValid) {
        showFieldError(field, message);
    } else {
        showFieldSuccess(field);
    }
    
    return isValid;
}

/* ========== VALIDATION RULES ========== */

function validateRequired(value) {
    return value.trim().length > 0;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

function validatePassword(password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return re.test(password);
}

function validateConfirmPassword(confirm, original) {
    return confirm === original;
}

function validateCreditCard(card) {
    // Remove spaces and dashes
    const cleanCard = card.replace(/[\s\-]/g, '');
    
    // Check if it's a number and has correct length
    if (!/^\d+$/.test(cleanCard)) return false;
    if (cleanCard.length < 13 || cleanCard.length > 19) return false;
    
    // Luhn algorithm check
    return validateLuhn(cleanCard);
}

function validateLuhn(cardNumber) {
    let sum = 0;
    let shouldDouble = false;
    
    // Loop through values from the rightmost digit
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i));
        
        if (shouldDouble) {
            digit *= 2;
            if (digit > 9) digit -= 9;
        }
        
        sum += digit;
        shouldDouble = !shouldDouble;
    }
    
    return (sum % 10) === 0;
}

function validateExpiryDate(date) {
    const re = /^(0[1-9]|1[0-2])\/?([0-9]{2})$/;
    if (!re.test(date)) return false;
    
    const [, month, year] = date.match(re);
    const now = new Date();
    const currentYear = now.getFullYear() % 100;
    const currentMonth = now.getMonth() + 1;
    
    if (parseInt(year) < currentYear) return false;
    if (parseInt(year) === currentYear && parseInt(month) < currentMonth) return false;
    if (parseInt(month) < 1 || parseInt(month) > 12) return false;
    
    return true;
}

function validateCVV(cvv) {
    const re = /^\d{3,4}$/;
    return re.test(cvv);
}

function validateZipCode(zip) {
    const re = /^\d{5}(-\d{4})?$/;
    return re.test(zip);
}

function validateURL(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

function validateNumber(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

function validateMinLength(value, min) {
    return value.length >= min;
}

function validateMaxLength(value, max) {
    return value.length <= max;
}

function validateRange(value, min, max) {
    const num = parseFloat(value);
    return num >= min && num <= max;
}

function validatePattern(value, pattern) {
    return pattern.test(value);
}

/* ========== PASSWORD STRENGTH METER ========== */

function updatePasswordStrength(field) {
    const password = field.value;
    const strengthMeter = field.closest('.form-group').querySelector('.strength-meter');
    const strengthText = field.closest('.form-group').querySelector('.strength-text');
    
    if (!strengthMeter || !strengthText) return;
    
    const strength = calculatePasswordStrength(password);
    const fill = strengthMeter.querySelector('.strength-fill');
    
    // Remove all strength classes
    strengthMeter.classList.remove('strength-0', 'strength-1', 'strength-2', 'strength-3', 'strength-4');
    strengthMeter.classList.add(`strength-${strength.score}`);
    
    if (fill) {
        fill.style.width = `${strength.score * 25}%`;
    }
    
    strengthText.textContent = strength.text;
    strengthText.className = 'strength-text';
    
    // Add color class based on score
    if (strength.score <= 1) {
        strengthText.classList.add('text-danger');
    } else if (strength.score <= 2) {
        strengthText.classList.add('text-warning');
    } else {
        strengthText.classList.add('text-success');
    }
}

function calculatePasswordStrength(password) {
    let score = 0;
    let text = 'Very Weak';
    
    if (!password) {
        return { score: 0, text: 'Enter a password' };
    }
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Complexity checks
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    // Cap score at 4
    score = Math.min(score, 4);
    
    // Set text based on score
    switch(score) {
        case 0:
            text = 'Very Weak';
            break;
        case 1:
            text = 'Weak';
            break;
        case 2:
            text = 'Fair';
            break;
        case 3:
            text = 'Good';
            break;
        case 4:
            text = 'Strong';
            break;
    }
    
    return { score, text };
}

/* ========== ERROR/Success DISPLAY FUNCTIONS ========== */

function showFieldError(field, message) {
    // Remove any existing error
    clearFieldError(field);
    
    // Add error class to field
    field.classList.add('is-invalid');
    
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    // Insert after field
    field.parentNode.appendChild(errorDiv);
    
    // Add error icon
    const icon = document.createElement('i');
    icon.className = 'fas fa-exclamation-circle form-error-icon';
    field.parentNode.appendChild(icon);
    
    // Focus field if it's not already focused
    if (document.activeElement !== field) {
        field.focus();
    }
    
    // Add shake animation
    field.classList.add('form-error-shake');
    setTimeout(function() {
        field.classList.remove('form-error-shake');
    }, 500);
}

function showFieldSuccess(field) {
    // Remove any existing error
    clearFieldError(field);
    
    // Add success class
    field.classList.add('is-valid');
    
    // Create success icon
    const icon = document.createElement('i');
    icon.className = 'fas fa-check-circle form-success-icon';
    field.parentNode.appendChild(icon);
}

function clearFieldError(field) {
    // Remove error classes
    field.classList.remove('is-invalid', 'is-valid');
    
    // Remove error message
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
    
    // Remove icons
    const icons = field.parentNode.querySelectorAll('.form-error-icon, .form-success-icon');
    icons.forEach(function(icon) {
        icon.remove();
    });
}

function clearFormErrors(form) {
    // Clear all field errors
    const fields = form.querySelectorAll('.is-invalid, .is-valid');
    fields.forEach(function(field) {
        clearFieldError(field);
    });
    
    // Clear group errors
    const groupErrors = form.querySelectorAll('.form-group-error');
    groupErrors.forEach(function(error) {
        error.remove();
    });
}

function showGroupError(group, message) {
    // Create error message for group
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-group-error invalid-feedback';
    errorDiv.textContent = message;
    
    group.appendChild(errorDiv);
}

function showFormSuccess(form) {
    // Show success notification
    showNotification('Form submitted successfully!', 'success');
    
    // Reset form after delay
    setTimeout(function() {
        form.reset();
        clearFormErrors(form);
    }, 2000);
}

/* ========== UTILITY FUNCTIONS ========== */

// Debounce function for live validation
function validateFieldWithDebounce(field, delay) {
    clearTimeout(field.debounceTimer);
    field.debounceTimer = setTimeout(function() {
        validateField(field);
    }, delay);
}

// Format input values
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    
    if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
    }
    
    input.value = value;
}

function formatCreditCard(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Add spaces every 4 digits
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    
    // Limit to 19 digits (16 digits + 3 spaces)
    if (value.length > 19) {
        value = value.slice(0, 19);
    }
    
    input.value = value.trim();
}

function formatExpiryDate(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2, 4);
    }
    
    if (value.length > 5) {
        value = value.slice(0, 5);
    }
    
    input.value = value;
}

// Auto-format inputs
document.addEventListener('input', function(e) {
    if (e.target.hasAttribute('data-format-phone')) {
        formatPhoneNumber(e.target);
    }
    
    if (e.target.hasAttribute('data-format-credit-card')) {
        formatCreditCard(e.target);
    }
    
    if (e.target.hasAttribute('data-format-expiry')) {
        formatExpiryDate(e.target);
    }
});

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

// Export functions for use in other files (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateForm,
        validateField,
        validateEmail,
        validatePhone,
        validatePassword,
        // ... other functions
    };
}