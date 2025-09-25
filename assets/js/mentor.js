document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Mentor-specific functionality
     */
    const MentorDashboard = {
        
        /**
         * Initialize mentor dashboard
         */
        init: function() {
            this.initProjectAssignment();
            this.initResourceManagement();
            this.initAssessmentCreation();
            this.initLearningObjectives();
        },

        /**
         * Project assignment functionality
         */
        initProjectAssignment: function() {
            const joinButtons = document.querySelectorAll('.btn-join-project');
            joinButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const projectId = button.dataset.projectId;
                    this.joinProject(projectId);
                });
            });

            const leaveButtons = document.querySelectorAll('.btn-leave-project');
            leaveButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const projectId = button.dataset.projectId;
                    this.leaveProject(projectId);
                });
            });
        },

        /**
         * Join a project
         */
        joinProject: function(projectId) {
            window.JHUB.Utils.confirm(
                'Are you sure you want to join this project as a mentor?',
                () => {
                    window.JHUB.Utils.showLoading('Joining project...');
                    
                    window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/projects/mentors.php`, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'join',
                            project_id: projectId
                        })
                    })
                    .then(response => {
                        window.JHUB.Utils.hideLoading();
                        if (response.success) {
                            window.JHUB.Utils.showAlert(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    });
                }
            );
        },

        /**
         * Leave a project
         */
        leaveProject: function(projectId) {
            window.JHUB.Utils.confirm(
                'Are you sure you want to leave this project? Your resources and assessments will remain but you won\'t be able to manage them.',
                () => {
                    window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/projects/mentors.php`, {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'leave',
                            project_id: projectId
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
         * Resource management functionality
         */
        initResourceManagement: function() {
            // Resource sharing
            const shareButtons = document.querySelectorAll('.btn-share-resource');
            shareButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const resourceId = button.dataset.resourceId;
                    this.showResourceShareModal(resourceId);
                });
            });

            // Quick resource creation
            const quickResourceForm = document.getElementById('quickResourceForm');
            if (quickResourceForm) {
                quickResourceForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.createQuickResource(quickResourceForm);
                });
            }
        },

        /**
         * Show resource sharing modal
         */
        showResourceShareModal: function(resourceId) {
            // Implementation for resource sharing modal
            window.JHUB.Utils.showAlert('Resource sharing modal - to be implemented', 'info');
        },

        /**
         * Create quick resource
         */
        createQuickResource: function(form) {
            const formData = new FormData(form);
            
            window.JHUB.Utils.showLoading('Creating resource...');
            
            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/resources/create.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                window.JHUB.Utils.hideLoading();
                if (response.success) {
                    window.JHUB.Utils.showAlert(response.message, 'success');
                    form.reset();
                    // Refresh resources list
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        },

        /**
         * Assessment creation functionality
         */
        initAssessmentCreation: function() {
            const createAssessmentButtons = document.querySelectorAll('.btn-create-assessment');
            createAssessmentButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const projectId = button.dataset.projectId;
                    this.showAssessmentModal(projectId);
                });
            });
        },

        /**
         * Show assessment creation modal
         */
        showAssessmentModal: function(projectId) {
            // Create assessment modal dynamically
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Assessment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="assessmentForm">
                            <div class="modal-body">
                                <input type="hidden" name="project_id" value="${projectId}">
                                <div class="mb-3">
                                    <label class="form-label">Assessment Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="form-control" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Due Date (Optional)</label>
                                    <input type="date" class="form-control" name="due_date">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Create Assessment</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            // Handle form submission
            modal.querySelector('#assessmentForm').addEventListener('submit', (e) => {
                e.preventDefault();
                this.createAssessment(e.target, modalInstance);
            });
        },

        /**
         * Create assessment
         */
        createAssessment: function(form, modalInstance) {
            const formData = new FormData(form);
            
            window.JHUB.Utils.showLoading('Creating assessment...');
            
            window.JHUB.Utils.ajax(`${window.JHUB.apiUrl}/assessments/create.php`, {
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
         * Learning objectives functionality
         */
        initLearningObjectives: function() {
            const createObjectiveButtons = document.querySelectorAll('.btn-create-objective');
            createObjectiveButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const projectId = button.dataset.projectId;
                    this.showLearningObjectiveModal(projectId);
                });
            });
        },

        /**
         * Show learning objective creation modal
         */
        showLearningObjectiveModal: function(projectId) {
            // Similar to assessment modal - implementation would follow same pattern
            window.JHUB.Utils.showAlert('Learning objective modal - to be implemented', 'info');
        }
    };

    // Initialize mentor dashboard
    MentorDashboard.init();
    
    // Make available globally
    window.MentorDashboard = MentorDashboard;
});