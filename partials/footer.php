<?php
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}
?>
        </div>
    </main>
    <footer class="app-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo AppConfig::COMPANY_NAME; ?>. All rights reserved.</p>
            <p><a href="mailto:info@jhubafrica.com">Contact</a> · <a href="<?php echo AppConfig::COMPANY_WEBSITE; ?>" target="_blank">Visit Site</a></p>
        </div>
    </footer>
    <script src="<?php echo AppConfig::getAsset('js/main.js'); ?>"></script>
</body>
</html>
