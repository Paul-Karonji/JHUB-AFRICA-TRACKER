document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Project-specific functionality
     */
    const ProjectDashboard = {
        
        /**
         * Initialize project dashboard
         */
        init: function() {
            this.initTeamManagement();
            this.initProgressTracking();
            this.initResourceViewing();
            this.initCommentSystem();
        },

        /**
         * Team management functionality
         */
        initTeamManagement: function() {
            const addMemberButtons = document.querySelectorAll('.btn-add-member');
            addMemberButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showAddMemberModal();
                });
            });
        },

        /**
         * Show add member modal
         */
        showAddMemberModal: function() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Team Member</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="addMemberForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" name="role" required 
                                           placeholder="e.g., Developer, Designer, Business Analyst">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Level of Experience</label>
                                    <select class="form-control" name="level_of_experience">
                                        <option value="">Select level...</option>
                                        <option value="Beginner">Beginner</option>
                                        <option value="Intermediate">Intermediate</option>
                                        <option value="Advanced">Advanced</option>
                                        <option value="Expert">Expert</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Member</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            // Handle form submission
            modal.querySelector('#addMemberForm').addEventListener('submit', (e) => {
                e.preventDefault();
                this.addTeamMember(e.target, modalInstance);
            });
        },

        /**
         * Add team member
         */
        addTeamMember: function(form, modalInstance) {
            const formData = new FormData(form);
            
            window.JHUB.Utils.showLoading('Adding team member...');
            
            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/projects/innovators.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                window.JHUB.Utils.hideLoading();
                if (response.success) {
                    window.JHUB.Utils.showAlert(response.message, 'success');
                    modalInstance.hide();
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        },

        /**
         * Progress tracking functionality
         */
        initProgressTracking: function() {
            // Progress visualization
            this.updateProgressVisualization();
            
            // Stage information tooltips
            const stageElements = document.querySelectorAll('.stage-step');
            stageElements.forEach(element => {
                if (element.dataset.stageInfo) {
                    new bootstrap.Tooltip(element, {
                        title: element.dataset.stageInfo,
                        placement: 'top'
                    });
                }
            });
        },

        /**
         * Update progress visualization
         */
        updateProgressVisualization: function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                const currentStage = parseInt(progressBar.dataset.currentStage) || 1;
                const percentage = this.calculateStagePercentage(currentStage);
                
                // Animate progress bar
                setTimeout(() => {
                    progressBar.style.width = percentage + '%';
                    progressBar.textContent = percentage.toFixed(1) + '%';
                }, 500);
            }
        },

        /**
         * Calculate stage percentage
         */
        calculateStagePercentage: function(stage) {
            const stagePercentages = {
                1: 16.67,
                2: 33.33,
                3: 50.00,
                4: 66.67,
                5: 83.33,
                6: 100.00
            };
            return stagePercentages[stage] || 0;
        },

        /**
         * Resource viewing functionality
         */
        initResourceViewing: function() {
            const resourceItems = document.querySelectorAll('.resource-item');
            resourceItems.forEach(item => {
                const viewButton = item.querySelector('.btn-view-resource');
                if (viewButton) {
                    viewButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        const resourceUrl = viewButton.dataset.resourceUrl;
                        if (resourceUrl) {
                            window.open(resourceUrl, '_blank');
                        }
                    });
                }
            });
        },

        /**
         * Comment system functionality
         */
        initCommentSystem: function() {
            const commentForms = document.querySelectorAll('.comment-form');
            commentForms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitComment(form);
                });
            });

            // Reply buttons
            const replyButtons = document.querySelectorAll('.btn-reply');
            replyButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const commentId = button.dataset.commentId;
                    this.showReplyForm(commentId);
                });
            });
        },

        /**
         * Submit comment
         */
        submitComment: function(form) {
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Posting...';
            
            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/comments/index.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.success) {
                    window.JHUB.Utils.showAlert('Comment posted successfully', 'success');
                    form.reset();
                    // Refresh comments section
                    setTimeout(() => window.location.reload(), 1000);
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        },

        /**
         * Show reply form
         */
        showReplyForm: function(commentId) {
            // Implementation for reply form
            window.JHUB.Utils.showAlert('Reply functionality - to be implemented', 'info');
        }
    };

    // Initialize project dashboard
    ProjectDashboard.init();
    
    // Make available globally
    window.ProjectDashboard = ProjectDashboard;
});