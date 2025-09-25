document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('#togglePassword');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input[type="password"], input[type="text"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Form validation
    const authForms = document.querySelectorAll('.auth-form');
    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Remove previous error messages
            const errorAlerts = form.querySelectorAll('.alert-danger');
            errorAlerts.forEach(alert => alert.remove());
            
            // Get form data
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            
            // Submit form
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Check if login was successful (redirect occurred)
                if (html.includes('dashboard') || html.includes('redirect')) {
                    window.location.reload();
                } else {
                    // Parse response and show errors
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newForm = tempDiv.querySelector('.auth-form');
                    
                    if (newForm) {
                        form.parentNode.replaceChild(newForm, form);
                        // Re-initialize password toggle for new form
                        initPasswordToggle(newForm);
                    }
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger';
                errorAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>An error occurred. Please try again.';
                form.insertBefore(errorAlert, form.firstChild);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });
    });

    function initPasswordToggle(form) {
        const toggleButton = form.querySelector('#togglePassword');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const passwordInput = this.parentElement.querySelector('input[type="password"], input[type="text"]');
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
    }
});