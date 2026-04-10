// Enhanced form interactions and validation
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and enhancement
    const forms = document.querySelectorAll('.auth-form');
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    const submitButtons = document.querySelectorAll('.btn-primary');
    
    // Email domain validation for @llcc.edu.ph
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !email.endsWith('@llcc.edu.ph')) {
                showFieldError(this, 'Only @llcc.edu.ph email addresses are allowed');
            } else {
                clearFieldError(this);
            }
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    // Password confirmation validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordInput = document.getElementById('password');
    
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('blur', function() {
            if (this.value && this.value !== passwordInput.value) {
                showFieldError(this, 'Passwords do not match');
            } else {
                clearFieldError(this);
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value) {
                if (this.value !== confirmPasswordInput.value) {
                    showFieldError(confirmPasswordInput, 'Passwords do not match');
                } else {
                    clearFieldError(confirmPasswordInput);
                }
            }
        });
    }
    
    // Form submission with loading state
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-primary');
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.textContent = 'Processing...';
            }
        });
    });
    
    // Real-time password strength indicator
    passwordInputs.forEach(input => {
        if (input.id === 'password') {
            input.addEventListener('input', function() {
                const strength = getPasswordStrength(this.value);
                updatePasswordStrength(this, strength);
            });
        }
    });
    
    // Auto-focus first input
    const firstInput = document.querySelector('input[required]');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Smooth scrolling for any anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add ripple effect to buttons
    submitButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            createRipple(e, this);
        });
    });
});

// Helper functions
function showFieldError(input, message) {
    clearFieldError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e53e3e';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '5px';
    
    input.style.borderColor = '#e53e3e';
    input.parentNode.appendChild(errorDiv);
}

function clearFieldError(input) {
    const existingError = input.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    input.style.borderColor = '#e2e8f0';
}

function getPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}

function updatePasswordStrength(input, strength) {
    let strengthText = '';
    let strengthColor = '';
    
    if (strength < 2) {
        strengthText = 'Weak';
        strengthColor = '#e53e3e';
    } else if (strength < 4) {
        strengthText = 'Medium';
        strengthColor = '#ed8936';
    } else {
        strengthText = 'Strong';
        strengthColor = '#48bb78';
    }
    
    let strengthIndicator = input.parentNode.querySelector('.password-strength');
    if (!strengthIndicator) {
        strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength';
        strengthIndicator.style.fontSize = '0.85rem';
        strengthIndicator.style.marginTop = '5px';
        input.parentNode.appendChild(strengthIndicator);
    }
    
    strengthIndicator.textContent = `Password strength: ${strengthText}`;
    strengthIndicator.style.color = strengthColor;
}

function createRipple(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .btn {
        position: relative;
        overflow: hidden;
    }
`;
document.head.appendChild(style);
