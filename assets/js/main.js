(function() {
    'use strict';

    // Global JHUB object (configured in footer template)
    window.JHUB = window.JHUB || {};

    /**
     * Utility Functions
     */
    const Utils = {
        /**
         * Make AJAX request with CSRF protection
         */
        ajax: function(url, options = {}) {
            const defaults = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': window.JHUB.csrfToken
                },
                credentials: 'same-origin'
            };

            const config = Object.assign({}, defaults, options);
            
            // Add CSRF token to POST data if present
            if (config.method === 'POST' && config.body) {
                if (config.body instanceof FormData) {
                    config.body.append('csrf_token', window.JHUB.csrfToken);
                } else if (typeof config.body === 'string') {
                    try {
                        const data = JSON.parse(config.body);
                        data.csrf_token = window.JHUB.csrfToken;
                        config.body = JSON.stringify(data);
                    } catch (e) {
                        // If not JSON, assume it's form data
                        config.body += (config.body ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.JHUB.csrfToken);
                    }
                }
            }

            return fetch(url, config)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    this.showAlert('An error occurred. Please try again.', 'danger');
                    throw error;
                });
        },

        /**
         * Show alert message
         */
        showAlert: function(message, type = 'info', duration = 5000) {
            const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();
            
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alert);

            // Auto dismiss
            if (duration > 0) {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, duration);
            }

            return alert;
        },

        /**
         * Create alert container if it doesn't exist
         */
        createAlertContainer: function() {
            let container = document.getElementById('alert-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'alert-container';
                container.style.position = 'fixed';
                container.style.top = '20px';
                container.style.right = '20px';
                container.style.zIndex = '9999';
                container.style.maxWidth = '400px';
                document.body.appendChild(container);
            }
            return container;
        },

        /**
         * Show confirmation modal
         */
        confirm: function(message, callback) {
            const modal = document.getElementById('confirmationModal');
            if (!modal) {
                console.error('Confirmation modal not found');
                return;
            }

            const messageElement = document.getElementById('confirmationMessage');
            const confirmButton = document.getElementById('confirmationButton');

            messageElement.textContent = message;
            
            // Remove previous event listeners
            confirmButton.replaceWith(confirmButton.cloneNode(true));
            const newConfirmButton = document.getElementById('confirmationButton');
            
            newConfirmButton.addEventListener('click', () => {
                bootstrap.Modal.getInstance(modal).hide();
                if (callback) callback();
            });

            new bootstrap.Modal(modal).show();
        },

        /**
         * Show loading modal
         */
        showLoading: function(message = 'Processing...') {
            const modal = document.getElementById('loadingModal');
            if (modal) {
                document.getElementById('loadingMessage').textContent = message;
                new bootstrap.Modal(modal).show();
            }
        },

        /**
         * Hide loading modal
         */
        hideLoading: function() {
            const modal = document.getElementById('loadingModal');
            if (modal) {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) {
                    instance.hide();
                }
            }
        },

        /**
         * Format date for display
         */
        formatDate: function(dateString, options = {}) {
            const date = new Date(dateString);
            const defaults = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };
            const config = Object.assign({}, defaults, options);
            
            return date.toLocaleDateString('en-US', config);
        },

        /**
         * Format relative time (time ago)
         */
        timeAgo: function(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            const intervals = [
                { label: 'year', seconds: 31536000 },
                { label: 'month', seconds: 2592000 },
                { label: 'day', seconds: 86400 },
                { label: 'hour', seconds: 3600 },
                { label: 'minute', seconds: 60 }
            ];

            for (const interval of intervals) {
                const count = Math.floor(seconds / interval.seconds);
                if (count >= 1) {
                    return `${count} ${interval.label}${count > 1 ? 's' : ''} ago`;
                }
            }

            return 'just now';
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func.apply(this, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(this, args);
            };
        }
    };

    /**
     * Form Handling
     */
    const Forms = {
        /**
         * Initialize form validation
         */
        initValidation: function() {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        },

        /**
         * Submit form via AJAX
         */
        submitAjax: function(form, options = {}) {
            const formData = new FormData(form);
            const url = form.action || window.location.href;
            const method = form.method || 'POST';

            const config = {
                method: method.toUpperCase(),
                body: formData
            };

            if (options.beforeSubmit) {
                options.beforeSubmit(form);
            }

            Utils.showLoading('Submitting...');

            return Utils.ajax(url, config)
                .then(response => {
                    Utils.hideLoading();
                    
                    if (response.success) {
                        Utils.showAlert(response.message || 'Operation completed successfully', 'success');
                        if (options.onSuccess) {
                            options.onSuccess(response, form);
                        }
                    } else {
                        Utils.showAlert(response.message || 'An error occurred', 'danger');
                        if (options.onError) {
                            options.onError(response, form);
                        }
                    }
                    
                    return response;
                })
                .catch(error => {
                    Utils.hideLoading();
                    if (options.onError) {
                        options.onError(error, form);
                    }
                    throw error;
                });
        }
    };

    /**
     * File Upload Handling
     */
    const FileUpload = {
        /**
         * Initialize file upload components
         */
        init: function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                this.enhanceFileInput(input);
            });
        },

        /**
         * Enhance file input with drag and drop
         */
        enhanceFileInput: function(input) {
            const wrapper = document.createElement('div');
            wrapper.className = 'file-upload-wrapper';
            wrapper.innerHTML = `
                <div class="file-upload-area">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                    <p class="mb-2">Drop files here or click to browse</p>
                    <small class="text-muted">Maximum file size: 10MB</small>
                </div>
                <div class="file-upload-list mt-3"></div>
            `;

            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            input.style.display = 'none';

            const uploadArea = wrapper.querySelector('.file-upload-area');
            const fileList = wrapper.querySelector('.file-upload-list');

            // Click to browse
            uploadArea.addEventListener('click', () => input.click());

            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, this.preventDefaults);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'));
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'));
            });

            uploadArea.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                this.handleFiles(files, input, fileList);
            });

            // File selection
            input.addEventListener('change', (e) => {
                this.handleFiles(e.target.files, input, fileList);
            });
        },

        /**
         * Prevent default drag behaviors
         */
        preventDefaults: function(e) {
            e.preventDefault();
            e.stopPropagation();
        },

        /**
         * Handle selected files
         */
        handleFiles: function(files, input, fileList) {
            fileList.innerHTML = '';
            Array.from(files).forEach(file => {
                this.displayFile(file, fileList);
            });
        },

        /**
         * Display file in list
         */
        displayFile: function(file, container) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex justify-content-between align-items-center p-2 border rounded mb-2';
            
            const fileInfo = document.createElement('div');
            fileInfo.innerHTML = `
                <i class="fas fa-file me-2"></i>
                <span class="filename">${file.name}</span>
                <small class="text-muted ms-2">(${this.formatFileSize(file.size)})</small>
            `;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger';
            removeButton.innerHTML = '<i class="fas fa-times"></i>';
            removeButton.addEventListener('click', () => fileItem.remove());

            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeButton);
            container.appendChild(fileItem);
        },

        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };

    /**
     * Table Enhancements
     */
    const Tables = {
        /**
         * Initialize sortable tables
         */
        initSortable: function() {
            const tables = document.querySelectorAll('.sortable-table');
            tables.forEach(table => {
                this.makeSortable(table);
            });
        },

        /**
         * Make table sortable
         */
        makeSortable: function(table) {
            const headers = table.querySelectorAll('th[data-sortable]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header);
                });
            });
        },

        /**
         * Sort table by column
         */
        sortTable: function(table, header) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const isNumeric = header.dataset.sortable === 'numeric';
            const currentDirection = header.dataset.sortDirection || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

            // Clear all sort indicators
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
                delete th.dataset.sortDirection;
            });

            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();

                let comparison = 0;
                if (isNumeric) {
                    comparison = parseFloat(aValue) - parseFloat(bValue);
                } else {
                    comparison = aValue.localeCompare(bValue);
                }

                return newDirection === 'desc' ? -comparison : comparison;
            });

            // Update DOM
            rows.forEach(row => tbody.appendChild(row));
            
            // Update header
            header.classList.add(`sort-${newDirection}`);
            header.dataset.sortDirection = newDirection;
        }
    };

    /**
     * Search Functionality
     */
    const Search = {
        /**
         * Initialize search
         */
        init: function() {
            const searchInputs = document.querySelectorAll('.search-input');
            searchInputs.forEach(input => {
                const targetSelector = input.dataset.searchTarget;
                if (targetSelector) {
                    const target = document.querySelector(targetSelector);
                    if (target) {
                        input.addEventListener('input', Utils.debounce((e) => {
                            this.performSearch(e.target.value, target);
                        }, 300));
                    }
                }
            });
        },

        /**
         * Perform search
         */
        performSearch: function(query, target) {
            const items = target.querySelectorAll('.searchable-item');
            const searchTerm = query.toLowerCase();

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                item.style.display = matches ? '' : 'none';
                
                if (matches && searchTerm) {
                    this.highlightText(item, searchTerm);
                } else {
                    this.removeHighlight(item);
                }
            });
        },

        /**
         * Highlight search term
         */
        highlightText: function(element, term) {
            // Simple highlight implementation
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            const textNodes = [];
            let node;
            
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }

            textNodes.forEach(textNode => {
                const parent = textNode.parentNode;
                if (parent.tagName === 'MARK') return; // Skip already highlighted
                
                const text = textNode.textContent;
                const regex = new RegExp(`(${term})`, 'gi');
                const highlightedText = text.replace(regex, '<mark>$1</mark>');
                
                if (highlightedText !== text) {
                    const temp = document.createElement('div');
                    temp.innerHTML = highlightedText;
                    
                    while (temp.firstChild) {
                        parent.insertBefore(temp.firstChild, textNode);
                    }
                    parent.removeChild(textNode);
                }
            });
        },

        /**
         * Remove highlight
         */
        removeHighlight: function(element) {
            const marks = element.querySelectorAll('mark');
            marks.forEach(mark => {
                mark.replaceWith(mark.textContent);
            });
        }
    };

    /**
     * Initialize everything when DOM is ready
     */
    function init() {
        // Initialize form validation
        Forms.initValidation();
        
        // Initialize file uploads
        FileUpload.init();
        
        // Initialize sortable tables
        Tables.initSortable();
        
        // Initialize search
        Search.init();

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose utilities globally
    window.JHUB.Utils = Utils;
    window.JHUB.Forms = Forms;
    window.JHUB.FileUpload = FileUpload;
    window.JHUB.Tables = Tables;
    window.JHUB.Search = Search;

})();