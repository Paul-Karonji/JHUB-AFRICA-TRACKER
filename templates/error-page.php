<?php
// templates/error-page.php - Error Page Template
// Usage: Set $errorCode, $errorTitle, and $errorMessage before including this file

$errorCode = $errorCode ?? 500;
$errorTitle = $errorTitle ?? 'Error';
$errorMessage = $errorMessage ?? 'An unexpected error occurred.';
$showBackButton = $showBackButton ?? true;
$backUrl = $backUrl ?? '/';

$baseUrl = SITE_URL ?? 'http://localhost/jhub-africa-tracker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorCode; ?> - <?php echo htmlspecialchars($errorTitle); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .error-container {
            text-align: center;
            color: white;
            padding: 20px;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            line-height: 1;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .error-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.3);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: #2c409a;
        }
        
        .btn-custom {
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card text-dark">
            <div class="error-icon">
                <?php
                // Select icon based on error code
                switch($errorCode) {
                    case 403:
                        echo '<i class="fas fa-lock"></i>';
                        break;
                    case 404:
                        echo '<i class="fas fa-search"></i>';
                        break;
                    case 500:
                        echo '<i class="fas fa-exclamation-triangle"></i>';
                        break;
                    default:
                        echo '<i class="fas fa-times-circle"></i>';
                }
                ?>
            </div>
            
            <div class="error-code"><?php echo htmlspecialchars($errorCode); ?></div>
            <h1 class="error-title"><?php echo htmlspecialchars($errorTitle); ?></h1>
            <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
            
            <div class="mt-4">
                <?php if ($showBackButton): ?>
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-primary btn-custom me-2">
                    <i class="fas fa-arrow-left me-2"></i> Go Back
                </a>
                <?php endif; ?>
                <a href="<?php echo $baseUrl; ?>/" class="btn btn-outline-primary btn-custom">
                    <i class="fas fa-home me-2"></i> Home
                </a>
            </div>
            
            <?php if (isset($additionalInfo) && !empty($additionalInfo)): ?>
            <div class="alert alert-info mt-4 text-start">
                <small><strong>Additional Information:</strong><br><?php echo htmlspecialchars($additionalInfo); ?></small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
