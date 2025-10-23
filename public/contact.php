<?php
require_once '../includes/init.php';

$errors = [];
$success = '';

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
            $contactData = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'subject' => trim($_POST['subject']),
                'message' => trim($_POST['message']),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];

            $emailBody = "New contact form submission:\n\n";
            $emailBody .= "Name: {$contactData['name']}\n";
            $emailBody .= "Email: {$contactData['email']}\n";
            $emailBody .= "Subject: {$contactData['subject']}\n\n";
            $emailBody .= "Message:\n{$contactData['message']}\n";

            if (sendEmailNotification(
                ADMIN_NOTIFICATION_EMAIL,
                'New Contact Form: ' . $contactData['subject'],
                $emailBody,
                'contact_form'
            )) {
                $success = 'Thank you for contacting us! We will get back to you within 24-48 hours.';
                $_POST = [];
            } else {
                $errors[] = 'There was an error sending your message. Please try again or email us directly.';
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}

$pageTitle = "Contact Us";

$customStyles = <<<CSS
    <style>
        .hero-contact {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #ffffff;
            padding: 110px 0;
        }
        .contact-card {
            border: none;
            border-radius: 16px;
            padding: 30px;
            height: 100%;
            background: #ffffff;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(44, 64, 154, 0.18);
        }
        .contact-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            box-shadow: 0 12px 30px rgba(44, 64, 154, 0.3);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 64, 154, 0.25);
        }
    </style>
CSS;

require_once '../templates/public-header.php';

?>

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
                <p class="text-muted mb-2">Support Hotline:</p>
                <a href="tel:+254700000000" class="d-block mb-2">+254 700 000 000</a>
                <p class="text-muted mb-2">Hours:</p>
                <span>Mon - Fri, 9:00 AM - 5:00 PM EAT</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-card text-center">
                <div class="contact-icon mx-auto">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h5 class="mb-3">Visit Us</h5>
                <p class="mb-1">JHUB AFRICA Innovation Hub</p>
                <p class="text-muted mb-1">JKUAT, Nairobi Campus</p>
                <p class="text-muted mb-0">Nairobi, Kenya</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-4">Send Us a Message</h2>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo is_array($error) ? implode(', ', $error) : $error; ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <?php echo Validator::csrfInput(); ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                           placeholder="Jane Doe">
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo is_array($errors['name']) ? implode(', ', $errors['name']) : $errors['name']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="you@example.com">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo is_array($errors['email']) ? implode(', ', $errors['email']) : $errors['email']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?>" 
                                   id="subject" 
                                   name="subject" 
                                   value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                   placeholder="How can we help you?">
                            <?php if (isset($errors['subject'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo is_array($errors['subject']) ? implode(', ', $errors['subject']) : $errors['subject']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control <?php echo isset($errors['message']) ? 'is-invalid' : ''; ?>" 
                                      id="message" 
                                      name="message" 
                                      rows="6" 
                                      placeholder="Tell us more about what you need help with..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo is_array($errors['message']) ? implode(', ', $errors['message']) : $errors['message']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4">Frequently Asked Questions</h3>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How long does the application review take?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
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

<?php require_once '../templates/public-footer.php'; ?>
