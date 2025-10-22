</main>
    <!-- End Main Content -->

    <!-- Public Footer -->
    <footer class="footer public-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <a class="footer-logo-link" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/index.php" rel="home">
                            <img src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/assets/images/logo/JHUB Africa Logo.png"
                                 alt="JHUB AFRICA"
                                 class="footer-logo"
                                 onerror="this.style.display='none'; this.insertAdjacentHTML('afterend','<span class=&quot;footer-logo-text&quot;>JHUB AFRICA</span>');">
                        </a>
                    </div>
                    <p class="footer-description">
                        Africa's premier innovation acceleration platform. We nurture African innovations from conception to market success with mentorship, resources, and community support.
                    </p>
                    <div class="social-links">
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/index.php"><i class="fas fa-angle-right"></i>Home</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/projects.php"><i class="fas fa-angle-right"></i>Projects</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/about.php"><i class="fas fa-angle-right"></i>About Us</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/contact.php"><i class="fas fa-angle-right"></i>Contact</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/applications/submit.php"><i class="fas fa-angle-right"></i>Apply</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h6 class="footer-heading">For Innovators</h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/applications/submit.php"><i class="fas fa-rocket"></i>Start Application</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/auth/login.php?type=project"><i class="fas fa-user"></i>Project Login</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/faq.php"><i class="fas fa-question-circle"></i>FAQs</a></li>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>/public/guidelines.php"><i class="fas fa-lightbulb"></i>Guidelines</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h6 class="footer-heading">Contact Us</h6>
                    <ul class="footer-links">
                        <li><a href="mailto:info@jhubafrica.com"><i class="fas fa-envelope"></i>info@jhubafrica.com</a></li>
                        <li><a href="tel:+254700000000"><i class="fas fa-phone"></i>+254 700 000 000</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i>JKUAT, Nairobi, Kenya</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JHUB AFRICA. All Rights Reserved. Built with passion for African innovation.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

    <!-- Custom page-specific scripts -->
    <?php if (isset($customScripts)): ?>
        <?php echo $customScripts; ?>
    <?php endif; ?>

    <!-- Footer Logo Styling -->
    <style>
        .footer-logo {
            max-width: 150px;
            height: auto;
            display: block;
            margin-bottom: 1rem;
        }

        .footer-logo-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: inherit;
            display: block;
            margin-bottom: 1rem;
        }

        /* Responsive sizing for mobile devices */
        @media (max-width: 768px) {
            .footer-logo {
                max-width: 120px;
            }
        }

        @media (max-width: 576px) {
            .footer-logo {
                max-width: 100px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.public-navbar .nav-link');
            const currentPath = window.location.pathname.replace(/\/+$/, '');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (!href) {
                    return;
                }

                const linkPath = new URL(href, window.location.origin).pathname.replace(/\/+$/, '');
                const isHomeLink = /\/index\.php$/.test(linkPath) || linkPath === '/';
                const onHome = currentPath === '' || currentPath === '/' || /\/index\.php$/.test(currentPath);

                if (linkPath === currentPath || (isHomeLink && onHome)) {
                    link.classList.add('active');
                }
            });

            const navbar = document.querySelector('.public-navbar');
            const toggleScrolled = () => {
                if (!navbar) {
                    return;
                }
                if (window.pageYOffset > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            };

            toggleScrolled();
            window.addEventListener('scroll', toggleScrolled);

            const flashAlerts = document.querySelectorAll('.alert');
            flashAlerts.forEach(alert => {
                setTimeout(() => {
                    const instance = bootstrap.Alert.getOrCreateInstance(alert);
                    instance.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>