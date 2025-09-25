
const FormValidation = {
    
    /**
     * Initilize all validation
     */
    init: function() {
        this.initBootstrapValidation();
        this.initCustomValidation();
        this.initRealTimeValidation();
    },

    /**
     * Initialize Bootstrap validation
     */
    initBootstrapValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },

    /**
     * Initialize custom validation rules
     */
    initCustomValidation: function() {
        // Password strength validation
        const passwordInputs = document.querySelectorAll('input[data-validate="password-strength"]');
        passwordInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.validatePasswordStrength(e.target);
            });
        });

        // Email domain validation
        const emailInputs = document.querySelectorAll('input[data-validate="email-domain"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', (e) => {
                this.validateEmailDomain(e.target);
            });
        });

        // File size validation
        const fileInputs = document.querySelectorAll('input[type="file"][data-max-size]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.validateFileSize(e.target);
            });
        });
    },

    /**
     * Initialize real-time validation
     */
    initRealTimeValidation: function() {
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', (e) => {
                this.validateField(e.target);
            });
        });
    },

    /**
     * Validate individual field
     */
    validateField: function(field) {
        const isValid = field.checkValidity();
        const feedback = field.parentElement.querySelector('.invalid-feedback') || 
                        field.parentElement.querySelector('.valid-feedback');

        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }

        return isValid;
    },

    /**
     * Validate password strength
     */
    validatePasswordStrength: function(input) {
        const password = input.value;
        const minLength = 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasNonalphas = /\W/.test(password);

        let score = 0;
        if (password.length >= minLength) score++;
        if (hasUpperCase) score++;
        if (hasLowerCase) score++;
        if (hasNumbers) score++;
        if (hasNonalphas) score++;

        const strengthIndicator = input.parentElement.querySelector('.password-strength') ||
                                this.createPasswordStrengthIndicator(input);

        let strengthText = '';
        let strengthClass = '';

        switch (score) {
            case 0:
            case 1:
                strengthText = 'Very Weak';
                strengthClass = 'text-danger';
                input.setCustomValidity('Password is too weak');
                break;
            case 2:
                strengthText = 'Weak';
                strengthClass = 'text-warning';
                input.setCustomValidity('Password is weak');
                break;
            case 3:
                strengthText = 'Fair';
                strengthClass = 'text-info';
                input.setCustomValidity('');
                break;
            case 4:
                strengthText = 'Good';
                strengthClass = 'text-success';
                input.setCustomValidity('');
                break;
            case 5:
                strengthText = 'Strong';
                strengthClass = 'text-success';
                input.setCustomValidity('');
                break;
        }

        strengthIndicator.textContent = strengthText;
        strengthIndicator.className = `password-strength small ${strengthClass}`;
    },

    /**
     * Create password strength indicator
     */
    createPasswordStrengthIndicator: function(input) {
        const indicator = document.createElement('div');
        indicator.className = 'password-strength small';
        input.parentElement.appendChild(indicator);
        return indicator;
    },

    /**
     * Validate email domain
     */
    validateEmailDomain: function(input) {
        const email = input.value;
        const allowedDomains = input.dataset.allowedDomains?.split(',') || [];
        const blockedDomains = input.dataset.blockedDomains?.split(',') || [];

        if (!email) return;

        const domain = email.split('@')[1]?.toLowerCase();
        
        if (allowedDomains.length > 0 && !allowedDomains.includes(domain)) {
            input.setCustomValidity(`Email domain must be one of: ${allowedDomains.join(', ')}`);
        } else if (blockedDomains.includes(domain)) {
            input.setCustomValidity(`Email domain ${domain} is not allowed`);
        } else {
            input.setCustomValidity('');
        }
    },

    /**
     * Validate file size
     */
    validateFileSize: function(input) {
        const maxSize = parseInt(input.dataset.maxSize);
        const files = input.files;

        for (const file of files) {
            if (file.size > maxSize) {
                const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(2);
                input.setCustomValidity(`File size must not exceed ${maxSizeMB}MB`);
                return;
            }
        }

        input.setCustomValidity('');
    },

    /**
     * Validate form before submission
     */
    validateForm: function(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    FormValidation.init();
});

// Make available globally
window.FormValidation = FormValidation;