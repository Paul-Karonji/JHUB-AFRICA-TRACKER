</div>
            <!-- End Content Wrapper -->

            <!-- Footer -->
            <footer class="footer mt-auto py-3 bg-light border-top">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 text-center text-md-start">
                            <small class="text-muted">
                                &copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.
                            </small>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <small class="text-muted">
                                Version <?php echo SITE_VERSION ?? '1.0.0'; ?> | 
                                <a href="<?php echo SITE_URL ?? ''; ?>/public/about.php" class="text-decoration-none">About</a> | 
                                <a href="<?php echo SITE_URL ?? ''; ?>/public/contact.php" class="text-decoration-none">Contact</a>
                            </small>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End Main Content -->
    </div>
    <!-- End Main Wrapper -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (optional, for legacy code compatibility) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL ?? ''; ?>/assets/js/main.js"></script>
    
    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Save preference to localStorage
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        }

        // Mobile Sidebar Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const collapsed = localStorage.getItem('sidebarCollapsed');
            
            if (collapsed === 'true') {
                sidebar.classList.add('collapsed');
            }

            // Highlight active nav link
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
            
            navLinks.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                if (currentPath === linkPath) {
                    link.classList.add('active');
                }
            });

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Global AJAX Error Handler
        function handleAjaxError(error) {
            console.error('AJAX Error:', error);
            alert('An error occurred. Please try again.');
            hideSpinner();
        }

        // Loading Spinner Functions
        function showSpinner() {
            document.getElementById('globalSpinner').classList.add('show');
        }

        function hideSpinner() {
            document.getElementById('globalSpinner').classList.remove('show');
        }

        // Confirmation Dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Format Date
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        // Format Time Ago
        function timeAgo(dateString) {
            const seconds = Math.floor((new Date() - new Date(dateString)) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            
            return Math.floor(seconds) + " seconds ago";
        }

        // Toast Notification
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Copy to Clipboard
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('Copied to clipboard!', 'success');
            }
        }

        // Form Validation Helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return false;
            }
            
            return true;
        }

        // Debounce Function for Search
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

        // Safe HTML Escape
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Print Page
        function printPage() {
            window.print();
        }

        // Export Table to CSV (simple implementation)
        function exportTableToCSV(tableId, filename = 'export.csv') {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvString = csv.join('\n');
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
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

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768) {
                    document.getElementById('sidebar').classList.remove('show');
                }
            }, 250);
        });
    </script>

    <!-- Custom page-specific scripts -->
    <?php if (isset($customScripts)): ?>
        <?php echo $customScripts; ?>
    <?php endif; ?>

    <!-- Analytics (if needed) -->
    <?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>');
    </script>
    <?php endif; ?>

</body>
</html>