<?php
require_once __DIR__ . '/../includes/init.php';
jsonResponse([
    'success' => true,
    'message' => 'API is running',
    'timestamp' => time()
]);
?>
