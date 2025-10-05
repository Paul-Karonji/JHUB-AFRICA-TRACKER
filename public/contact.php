<?php
// public/contact.php
// Contact Page
require_once '../includes/init.php';

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Validator::validateCSRF()) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('name', 'Name is required')
                 ->required('email', 'Email is required')
                 ->email('email')
                 ->required('subject', 'Subject is required')
                 ->required('message', 'Message is required')
                 ->min('message', 20);
        
        if ($validator->isValid()) {
            // Store contact message in database
            $contactData = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'subject' => trim($_POST['subject']),
                'message' => trim($_POST['message']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            // Create contacts table if it doesn't exist (optional)
            // For now, send email to admin
            $emailBody = "New contact form submission:\n\n";
            $emailBody .= "Name: {$contactData['name']}\n";
            $emailBody .= "Email: {$contactData['email']}\n";
            $emailBody .= "Subject: {$contactData['subject']}\n\n";
            $emailBody .= "Message:\n{$contactData['message']}\n";
            
            if (sendEmailNotification(
                ADMIN_NOTIFICATION_EMAIL,
                'New Contact Form: ' . $contactData['subject'],
                $emailBody,
                'contact_form',
                $contactData
            )) {
                $success = 'Thank you for contacting us! We will get back to you within 24-48 hours.';
                $_POST = []; // Clear form
            } else {
                $errors[] = 'There was an error sending your message. Please try again or email us directly.';
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}

$pageTitle = "Contact Us";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - JHUB AFRICA</title>
    <meta name="description" content="Get in touch with JHUB AFRICA. Contact us for inquiries about our innovation programs, partnerships, or general questions.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-contact {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .contact-card {
            border: none;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .contact-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/submit.php">Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-contact text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Get In Touch</h1>
            <p class="lead mb-0">
                Have questions or want to learn more about our programs?<br>
                We'd love to hear from you!
            </p>
        </div>
    </div>

    <!-- Contact Info Cards -->
    <div class="container my-5">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="contact-card text-center">
                    <div class="contact-icon mx-auto">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5 class="mb-3">Email Us</h5>
                    <p class="text-muted mb-2">General Inquiries:</p>
                    <a href="mailto:info@jhubafrica.com" class="d-block mb-2">info@jhubafrica.com</a>
                    <p class="text-muted mb-2">Applications:</p>
                    <a href="mailto:applications@jhubafrica.com" class="d-block">applications@jhubafrica.com</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card text-center">
                    <div class="contact-icon mx-auto">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5 class="mb-3">Call Us</h5>
                    <p class="text-muted mb-2">Office Hours: Mon-Fri, 9AM-5PM EAT</p>
                    <a href="tel:+254XXXXXXXXX" class="d-block mb-2">+254 XXX XXX XXX</a>
                    <p class="text-muted small mb-0">WhatsApp available</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card text-center">
                    <div class="contact-icon mx-auto">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h5 class="mb-3">Visit Us</h5>
                    <p class="mb-2">
                        JHUB AFRICA Headquarters<br>
                        Nairobi, Kenya<br>
                        East Africa
                    </p>
                    <small class="text-muted">By appointment only</small>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="mb-0">Send Us a Message</h3>
                        <p class="mb-0">Fill out the form below and we'll get back to you soon</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo e($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo is_array($error) ? implode(', ', $error) : $error; ?></div>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo Validator::csrfInput(); ?>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name" 
                                           value="<?php echo isset($_POST['name']) ? e($_POST['name']) : ''; ?>" 
                                           placeholder="John Doe" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Your Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>" 
                                           placeholder="john@example.com" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="subject" name="subject" required>
                                    <option value="">-- Select a subject --</option>
                                    <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                                    <option value="Application Status" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Application Status') ? 'selected' : ''; ?>>Application Status</option>
                                    <option value="Mentorship Opportunity" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Mentorship Opportunity') ? 'selected' : ''; ?>>Mentorship Opportunity</option>
                                    <option value="Partnership Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Partnership Inquiry') ? 'selected' : ''; ?>>Partnership Inquiry</option>
                                    <option value="Investment Opportunity" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Investment Opportunity') ? 'selected' : ''; ?>>Investment Opportunity</option>
                                    <option value="Technical Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          placeholder="Tell us more about your inquiry... (minimum 20 characters)" 
                                          required><?php echo isset($_POST['message']) ? e($_POST['message']) : ''; ?></textarea>
                                <div class="form-text">Please be as detailed as possible</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="card mt-4 border-0 bg-light">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-question-circle me-2"></i>Quick Answers</h5>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How long does the application review take?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We review applications within 5-7 business days. You'll receive an email notification with our decision.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Can I reapply if rejected?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes! We encourage you to refine your project based on our feedback and reapply after 3 months.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        How can I become a mentor?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We're always looking for experienced professionals to mentor our innovators. Please use the contact form above with the subject "Mentorship Opportunity" and tell us about your expertise.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Section -->
    <div class="bg-primary text-white py-5">
        <div class="container text-center">
            <h3 class="mb-4">Connect With Us on Social Media</h3>
            <div class="d-flex justify-content-center gap-4">
                <a href="#" class="text-white" style="font-size: 2rem;">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-white" style="font-size: 2rem;">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="#" class="text-white" style="font-size: 2rem;">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="#" class="text-white" style="font-size: 2rem;">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="text-white" style="font-size: 2rem;">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JHUB AFRICA</h5>
                    <p class="mb-0">Nurturing African Innovations from Conception to Market Success</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="projects.php" class="text-white-50">Projects</a></li>
                        <li><a href="about.php" class="text-white-50">About</a></li>
                        <li><a href="contact.php" class="text-white-50">Contact</a></li>
                        <li><a href="../applications/submit.php" class="text-white-50">Apply</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Office Hours</h6>
                    <p class="text-white-50 mb-0">
                        Monday - Friday<br>
                        9:00 AM - 5:00 PM EAT
                    </p>
                </div>
            </div>
            <hr class="bg-secondary my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>