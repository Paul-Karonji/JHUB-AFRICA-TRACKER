</main>
    <!-- End Main Content -->

    <!-- Public Footer -->
    <footer class="public-footer">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3" style="color: white; font-weight: 600;">
                        <img src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/images/logo/JHUB Africa Logo.png" 
                             alt="JHUB AFRICA" 
                             style="max-height: 40px; filter: brightness(0) invert(1);"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display: none;">JHUB AFRICA</span>
                    </h5>
                    <p style="color: rgba(255, 255, 255, 0.8);">
                        Empowering African innovations through structured mentorship, 
                        resources, and community support. Join us in transforming ideas 
                        into impactful solutions.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3" style="color: white; font-weight: 600;">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/index.php">Home</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/projects.php">Projects</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/about.php">About Us</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <!-- For Innovators -->
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3" style="color: white; font-weight: 600;">For Innovators</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/applications/submit.php">Apply Now</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/auth/login.php?type=project">Project Login</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/faq.php">FAQs</a></li>
                        <li class="mb-2"><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/guidelines.php">Guidelines</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3" style="color: white; font-weight: 600;">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:info@jhubafrica.org">info@jhubafrica.org</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <a href="tel:+254700000000">+254 700 000 000</a>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span style="color: rgba(255, 255, 255, 0.8);">Nairobi, Kenya</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0;">
            
            <!-- Copyright -->
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0" style="color: rgba(255, 255, 255, 0.8);">
                        &copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/privacy.php" class="me-3">Privacy Policy</a>
                    <a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/terms.php">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom page-specific scripts -->
    <?php if (isset($customScripts)): ?>
        <?php echo $customScripts; ?>
    <?php endif; ?>
    
    <script>
        // Active nav link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.public-navbar .nav-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref && currentPath.includes(linkHref.split('/').pop())) {
                    link.classList.add('active');
                }
            });
            
            // Auto-hide flash messages after 5 seconds
            const flashAlerts = document.querySelectorAll('.alert');
            flashAlerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>