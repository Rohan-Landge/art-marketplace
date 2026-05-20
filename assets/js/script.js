/**
 * ART MARKETPLACE - CUSTOM JAVASCRIPT
 */

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function () {
    try {
        // Initialize Bootstrap tooltips and popovers if Bootstrap is loaded
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                try { new bootstrap.Tooltip(tooltipTriggerEl); } catch (e) { console.error(e); }
            });

            const popoverTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (popoverTriggerEl) {
                try { new bootstrap.Popover(popoverTriggerEl); } catch (e) { console.error(e); }
            });
        }
    } catch (err) {
        console.error('UI init error:', err);
    }

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

/**
 * Image Preview on File Upload
 */
function previewImage(inputElement, previewElement) {
    const file = inputElement.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewElement.src = e.target.result;
            previewElement.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Format price to USD currency
 */
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toastHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const toastContainer = document.createElement('div');
    toastContainer.innerHTML = toastHTML;
    document.body.appendChild(toastContainer);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toastContainer.remove();
    }, 5000);
}

/**
 * Debounce function for search
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
 * Validate form before submission
 */
function validateForm(formElement) {
    if (!formElement.checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
    }
    formElement.classList.add('was-validated');
}

/**
 * Search handler with debounce
 */
const handleSearch = debounce(function (query) {
    console.log('Searching for:', query);
    // Add your search logic here
}, 500);

/**
 * Handle file upload with validation
 */
function handleFileUpload(file, maxSizeMB = 5, allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    const maxSize = maxSizeMB * 1024 * 1024;
    
    if (file.size > maxSize) {
        showToast(`File size exceeds ${maxSizeMB}MB limit`, 'danger');
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showToast('Invalid file type. Only JPG, PNG, and GIF allowed.', 'danger');
        return false;
    }
    
    return true;
}

/**
 * Lazy load images
 */
function lazyLoadImages() {
    if ('IntersectionObserver' in window) {
        const imageElements = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        imageElements.forEach(img => imageObserver.observe(img));
    }
}

/**
 * Track page events
 */
function trackEvent(eventName, eventData = {}) {
    console.log(`Event: ${eventName}`, eventData);
    // You can send this to Google Analytics or other tracking service
}

/**
 * Format date to readable format
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        showToast('Failed to copy', 'danger');
    });
}

/**
 * Add active class to current page in navigation
 */
document.addEventListener('DOMContentLoaded', function () {
    const currentLocation = location.pathname;
    const menuItems = document.querySelectorAll('.nav-link');
    
    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentLocation) {
            item.classList.add('active');
        }
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    lazyLoadImages();
});

// Password toggle and client-side strength check
// Note: actual toggle logic is provided in the footer as the universal page-level script.

function isStrongPassword(pwd) {
    if (!pwd || pwd.length < 8) return false;
    if (!/[A-Z]/.test(pwd)) return false;
    if (!/[a-z]/.test(pwd)) return false;
    if (!/[0-9]/.test(pwd)) return false;
    if (!/[\W_]/.test(pwd)) return false;
    return true;
}

// Attach form submit handler to validate password strength when both password and confirm fields exist
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const pwdInput = form.querySelector('.password-field[name="password"]');
            const confirmInput = form.querySelector('.password-field[name="confirm_password"]');
            if (pwdInput && confirmInput) {
                const pwd = pwdInput.value;
                const conf = confirmInput.value;
                if (!isStrongPassword(pwd)) {
                    e.preventDefault();
                    e.stopPropagation();
                    showToast('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 'danger');
                    return false;
                }
                if (pwd !== conf) {
                    e.preventDefault();
                    e.stopPropagation();
                    showToast('Passwords do not match.', 'danger');
                    return false;
                }
            }
        }, { passive: false });
    });
});
