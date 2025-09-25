<?php
// templates/footer.php
// Common Footer Template
?>

    <?php if ($auth->isValidSession()): ?>
            </div> <!-- .container-fluid -->
        </main> <!-- .main-content -->
    </div> <!-- .dashboard-wrapper -->
    <?php else: ?>
        </div> <!-- .container -->
    </main> <!-- .public-content -->
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 <?php echo $auth->isValidSession() ? 'dashboard-footer' : 'public-footer'; ?>">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted">
                        Version <?php echo SITE_VERSION; ?>
                        <?php if ($auth->isValidSession()): ?>
                        | Logged in as: <?php echo ucfirst($auth->getUserType()); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo getBaseUrl(); ?>/assets/js/main.js"></script>
    
    <?php if ($auth->getUserType() === USER_TYPE_ADMIN): ?>
    <script src="<?php echo getBaseUrl(); ?>/assets/js/admin.js"></script>
    <?php elseif ($auth->getUserType() === USER_TYPE_MENTOR): ?>
    <script src="<?php echo getBaseUrl(); ?>/assets/js/mentor.js"></script>
    <?php elseif ($auth->getUserType() === USER_TYPE_PROJECT): ?>
    <script src="<?php echo getBaseUrl(); ?>/assets/js/project.js"></script>
    <?php endif; ?>
    
    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- API Configuration for JavaScript -->
    <script>
        window.JHUB = {
            baseUrl: '<?php echo getBaseUrl(); ?>',
            apiUrl: '<?php echo getBaseUrl(); ?>/api',
            userType: '<?php echo $auth->getUserType(); ?>',
            userId: <?php echo $auth->getUserId() ?: 'null'; ?>,
            csrfToken: '<?php echo $auth->generateCSRFToken(); ?>'
        };
    </script>

    <?php if (DEBUG_MODE): ?>
    <!-- Debug Information -->
    <div class="debug-info" style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">
        <strong>Debug Mode</strong><br>
        Memory: <?php echo number_format(memory_get_usage() / 1024 / 1024, 2); ?> MB<br>
        Time: <?php echo number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2); ?> ms
    </div>
    <?php endif; ?>

</body>
</html>