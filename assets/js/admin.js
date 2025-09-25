document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Admin-specific functionality
     */
    const AdminDashboard = {
        
        /**
         * Initialize admin dashboard
         */
        init: function() {
            this.initApplicationReview();
            this.initProjectManagement();
            this.initMentorManagement();
            this.initBulkActions();
        },

        /**
         * Application review functionality
         */
        initApplicationReview: function() {
            const reviewButtons = document.querySelectorAll('.btn-review-application');
            reviewButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const applicationId = button.dataset.applicationId;
                    this.showApplicationReview(applicationId);
                });
            });

            // Approve/Reject handlers
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-approve-application')) {
                    this.handleApplicationAction('approve', e.target.dataset.applicationId);
                } else if (e.target.classList.contains('btn-reject-application')) {
                    this.handleApplicationAction('reject', e.target.dataset.applicationId);
                }
            });
        },

        /**
         * Show application review modal
         */
        showApplicationReview: function(applicationId) {
            window.JHUB.Utils.showLoading('Loading application details...');
            
            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/applications/details.php?id=${applicationId}`)
                .then(response => {
                    window.JHUB.Utils.hideLoading();
                    if (response.success) {
                        this.displayApplicationModal(response.data);
                    }
                })
                .catch(error => {
                    window.JHUB.Utils.hideLoading();
                    console.error('Error loading application:', error);
                });
        },

        /**
         * Display application in modal
         */
        displayApplicationModal: function(application) {
            // Create or update modal content
            let modal = document.getElementById('applicationReviewModal');
            if (!modal) {
                modal = this.createApplicationModal();
            }

            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <h5>${application.project_name}</h5>
                        <p class="text-muted">Applied: ${window.JHUB.Utils.formatDate(application.applied_at)}</p>
                        <p><strong>Description:</strong></p>
                        <p>${application.description}</p>
                        
                        <p><strong>Project Lead:</strong> ${application.project_lead_name}</p>
                        <p><strong>Email:</strong> ${application.project_lead_email}</p>
                        
                        ${application.project_website ? `<p><strong>Website:</strong> <a href="${application.project_website}" target="_blank">${application.project_website}</a></p>` : ''}
                        
                        ${application.target_market ? `<p><strong>Target Market:</strong> ${application.target_market}</p>` : ''}
                    </div>
                    <div class="col-md-4">
                        ${application.presentation_file ? `
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Presentation</h6>
                                </div>
                                <div class="card-body text-center">
                                    <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                    <br>
                                    <a href="${window.JHUB.baseUrl}/assets/uploads/presentations/${application.presentation_file}" 
                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Presentation
                                    </a>
                                </div>
                            </div>
                        ` : '<p class="text-muted">No presentation uploaded</p>'}
                    </div>
                </div>
            `;

            // Update action buttons
            const approveBtn = modal.querySelector('.btn-approve-application');
            const rejectBtn = modal.querySelector('.btn-reject-application');
            
            approveBtn.dataset.applicationId = application.application_id;
            rejectBtn.dataset.applicationId = application.application_id;

            new bootstrap.Modal(modal).show();
        },

        /**
         * Create application review modal
         */
        createApplicationModal: function() {
            const modal = document.createElement('div');
            modal.id = 'applicationReviewModal';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Review Application</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Dynamic content -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger btn-reject-application">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                            <button type="button" class="btn btn-success btn-approve-application">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            return modal;
        },

        /**
         * Handle application approval/rejection
         */
        handleApplicationAction: function(action, applicationId) {
            const message = action === 'approve' ? 
                'Are you sure you want to approve this application? This will create a new project and send an email to the project lead.' :
                'Are you sure you want to reject this application? Please provide a reason for rejection.';

            if (action === 'reject') {
                this.showRejectionReasonModal(applicationId);
            } else {
                window.JHUB.Utils.confirm(message, () => {
                    this.submitApplicationAction(action, applicationId);
                });
            }
        },

        /**
         * Show rejection reason modal
         */
        showRejectionReasonModal: function(applicationId) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Application</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Reason for Rejection:</label>
                                <textarea class="form-control" id="rejectionReason" rows="4" 
                                         placeholder="Please provide a clear reason for rejecting this application..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" onclick="AdminDashboard.submitApplicationAction('reject', '${applicationId}', document.getElementById('rejectionReason').value)">
                                <i class="fas fa-times me-1"></i>Reject Application
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            new bootstrap.Modal(modal).show();
        },

        /**
         * Submit application action
         */
        submitApplicationAction: function(action, applicationId, reason = '') {
            window.JHUB.Utils.showLoading(`${action === 'approve' ? 'Approving' : 'Rejecting'} application...`);

            const data = {
                action: action,
                application_id: applicationId,
                rejection_reason: reason
            };

            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/applications/review.php`, {
                method: 'POST',
                body: JSON.stringify(data)
            })
            .then(response => {
                window.JHUB.Utils.hideLoading();
                if (response.success) {
                    window.JHUB.Utils.showAlert(response.message, 'success');
                    // Refresh page or update UI
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    window.JHUB.Utils.showAlert(response.message, 'danger');
                }
            });
        },

        /**
         * Project management functionality
         */
        initProjectManagement: function() {
            // Terminate project handlers
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-terminate-project')) {
                    const projectId = e.target.dataset.projectId;
                    this.handleProjectTermination(projectId);
                }
            });
        },

        /**
         * Handle project termination
         */
        handleProjectTermination: function(projectId) {
            window.JHUB.Utils.confirm(
                'Are you sure you want to terminate this project? This action cannot be undone.',
                () => {
                    this.showTerminationReasonModal(projectId);
                }
            );
        },

        /**
         * Show termination reason modal
         */
        showTerminationReasonModal: function(projectId) {
            // Similar to rejection modal but for project termination
            // Implementation would be similar to showRejectionReasonModal
        },

        /**
         * Mentor management functionality
         */
        initMentorManagement: function() {
            // Mentor activation/deactivation
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-toggle-mentor')) {
                    const mentorId = e.target.dataset.mentorId;
                    const action = e.target.dataset.action;
                    this.toggleMentorStatus(mentorId, action);
                }
            });
        },

        /**
         * Toggle mentor status
         */
        toggleMentorStatus: function(mentorId, action) {
            const actionText = action === 'activate' ? 'activate' : 'deactivate';
            
            window.JHUB.Utils.confirm(
                `Are you sure you want to ${actionText} this mentor?`,
                () => {
                    window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/mentors/toggle-status.php`, {
                        method: 'POST',
                        body: JSON.stringify({
                            mentor_id: mentorId,
                            action: action
                        })
                    })
                    .then(response => {
                        if (response.success) {
                            window.JHUB.Utils.showAlert(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    });
                }
            );
        },

        /**
         * Bulk actions functionality
         */
        initBulkActions: function() {
            // Select all checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', (e) => {
                    const checkboxes = document.querySelectorAll('.item-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                    this.updateBulkActionButtons();
                });
            }

            // Individual checkboxes
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('item-checkbox')) {
                    this.updateBulkActionButtons();
                }
            });

            // Bulk action buttons
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-bulk-action')) {
                    const action = e.target.dataset.action;
                    this.handleBulkAction(action);
                }
            });
        },

        /**
         * Update bulk action button states
         */
        updateBulkActionButtons: function() {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            const bulkButtons = document.querySelectorAll('.btn-bulk-action');
            
            bulkButtons.forEach(button => {
                button.disabled = checkedBoxes.length === 0;
            });
        },

        /**
         * Handle bulk actions
         */
        handleBulkAction: function(action) {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                window.JHUB.Utils.showAlert('Please select items to perform bulk action.', 'warning');
                return;
            }

            const actionText = action.charAt(0).toUpperCase() + action.slice(1);
            
            window.JHUB.Utils.confirm(
                `Are you sure you want to ${action} ${ids.length} selected item(s)?`,
                () => {
                    window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/admin/bulk-action.php`, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: action,
                            ids: ids
                        })
                    })
                    .then(response => {
                        if (response.success) {
                            window.JHUB.Utils.showAlert(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    });
                }
            );
        }
    };

    // Initialize admin dashboard
    AdminDashboard.init();
    
    // Make available globally
    window.AdminDashboard = AdminDashboard;
});