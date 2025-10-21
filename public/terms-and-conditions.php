<?php
// public/terms-and-conditions.php
// Terms & Conditions and Mutual NDA Page
$pageTitle = "Terms & Conditions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - JHUB AFRICA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.7;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .container-custom {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: white;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 3px solid #2c409a;
        }
        
        .logo-container {
            margin-bottom: 25px;
        }
        
        .logo-container img {
            max-width: 250px;
            height: auto;
        }
        
        .logo-fallback {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8em;
            color: #2c409a;
        }
        
        .logo-fallback i {
            font-size: 2em;
            color: #3b54c7;
        }
        
        .header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #2c409a;
        }
        
        .header p {
            font-size: 1.1em;
            color: #6c757d;
        }
        
        .content {
            padding: 40px;
        }
        
        .last-updated {
            background: linear-gradient(135deg, rgba(59, 84, 199, 0.1) 0%, rgba(14, 1, 91, 0.1) 100%);
            padding: 15px;
            border-left: 4px solid #2c409a;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        h2 {
            color: #2c409a;
            font-size: 1.6em;
            margin: 35px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        h3 {
            color: #0e015b;
            font-size: 1.3em;
            margin: 25px 0 15px 0;
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        ul, ol {
            margin: 15px 0 15px 30px;
        }
        
        li {
            margin-bottom: 10px;
        }
        
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        
        .info-box {
            background: linear-gradient(135deg, rgba(59, 84, 199, 0.1) 0%, rgba(86, 192, 92, 0.1) 100%);
            border-left: 4px solid #3fa845;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        
        .acceptance-section {
            background: linear-gradient(135deg, rgba(59, 84, 199, 0.15) 0%, rgba(14, 1, 91, 0.15) 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            border: 2px solid #2c409a;
        }
        
        .definition-term {
            font-weight: bold;
            color: #2c409a;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            text-align: center;
            border-top: 3px solid #2c409a;
        }
        
        .icon-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            color: white;
            margin-right: 8px;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 30px 20px; }
            .content { padding: 25px; }
            .logo-container img { max-width: 180px; }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <div class="header">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="JHUB AFRICA Logo" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                <div class="logo-fallback" style="display: none;">
                    <i class="fas fa-lightbulb"></i>
                    <span>JHUB AFRICA</span>
                </div>
            </div>
            <h1>Terms & Conditions</h1>
            <p>Jomo Kenyatta Innovations and Accelerator Network Hub</p>
            <p style="font-size: 0.95em; margin-top: 10px; color: #0e015b; font-weight: 500;">Mutual Non-Disclosure Agreement</p>
        </div>
        
        <div class="content">
            <div class="last-updated">
                <strong>Last Updated:</strong> October 2025<br>
                <strong>Effective Date:</strong> Upon Application Submission
            </div>
            
            <div class="acceptance-section">
                <h3><span class="icon-circle"><i class="fas fa-check"></i></span>Agreement to Terms</h3>
                <p>By submitting an application to JHUB AFRICA, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions and our Mutual Non-Disclosure Agreement. Your submission constitutes your acceptance of this legally binding agreement.</p>
            </div>
            
            <div class="section">
                <h2>1. Introduction & Purpose</h2>
                <p>This agreement establishes the terms governing the relationship between <span class="definition-term">JHUB AFRICA</span> (Jomo Kenyatta Innovations and Accelerator Network Hub) and <span class="definition-term">You</span> (the Applicant/Client) regarding the submission, evaluation, and potential acceptance of innovation projects.</p>
                
                <p><strong>Our Mission:</strong> JHUB AFRICA is committed to fostering innovation, providing mentorship, and protecting the intellectual property of all participants in our ecosystem.</p>
            </div>
            
            <div class="section">
                <h2>2. Definitions</h2>
                
                <h3>2.1 Key Terms</h3>
                <ul>
                    <li><span class="definition-term">"Proprietary Information"</span> includes all information you disclose during the application process, including but not limited to:
                        <ul>
                            <li>Project concepts, ideas, and methodologies</li>
                            <li>Technical specifications, algorithms, and designs</li>
                            <li>Business models, marketing strategies, and financial projections</li>
                            <li>Research data, prototypes, and future development plans</li>
                            <li>Team member information and organizational structures</li>
                        </ul>
                    </li>
                    
                    <li><span class="definition-term">"Disclosing Party"</span> refers to the party (you or JHUB) sharing information</li>
                    
                    <li><span class="definition-term">"Receiving Party"</span> refers to the party (JHUB or you) receiving information</li>
                    
                    <li><span class="definition-term">"Project"</span> means your innovation submission and all associated materials</li>
                </ul>
                
                <h3>2.2 Information NOT Considered Proprietary</h3>
                <p>The following types of information are excluded from protection:</p>
                <ol>
                    <li>Information already publicly available through no fault of the Receiving Party</li>
                    <li>Information already known by the Receiving Party before disclosure</li>
                    <li>Information independently developed without reference to disclosed materials</li>
                    <li>Information received from a third party without confidentiality restrictions</li>
                    <li>Information for which written permission to disclose has been granted</li>
                </ol>
            </div>
            
            <div class="section">
                <h2>3. Confidentiality & Non-Disclosure Obligations</h2>
                
                <h3>3.1 Mutual Protection</h3>
                <p>Both parties agree to maintain the confidentiality of all Proprietary Information exchanged. This includes:</p>
                <ul>
                    <li>Maintaining information in strict confidence</li>
                    <li>Not disclosing information to unauthorized third parties</li>
                    <li>Using information solely for evaluating project potential and providing services</li>
                    <li>Implementing reasonable security measures to prevent unauthorized access</li>
                </ul>
                
                <div class="highlight-box">
                    <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong> JHUB AFRICA will never share your project details, business plans, or proprietary information with external parties without your explicit written consent.
                </div>
                
                <h3>3.2 Limited Disclosure</h3>
                <p>Proprietary Information may only be shared with:</p>
                <ul>
                    <li>JHUB staff members directly involved in project evaluation</li>
                    <li>Assigned mentors (with your consent)</li>
                    <li>Internal review committees</li>
                    <li>Individuals who have signed confidentiality agreements</li>
                </ul>
                
                <h3>3.3 Legal Disclosure Requirements</h3>
                <p>Disclosure of Proprietary Information is permitted when:</p>
                <ul>
                    <li>Required by valid court order or governmental body in Kenya</li>
                    <li>Mandated by Kenyan law</li>
                    <li>Necessary to enforce rights under this agreement</li>
                </ul>
                <p><strong>Notice Requirement:</strong> If legally compelled to disclose information, the Receiving Party will promptly notify the Disclosing Party to allow seeking protective orders.</p>
            </div>
            
            <div class="section">
                <h2>4. Use of Information</h2>
                
                <h3>4.1 Permitted Uses</h3>
                <p>JHUB AFRICA will use your Proprietary Information exclusively for:</p>
                <ul>
                    <li>Evaluating your project application</li>
                    <li>Providing mentorship and guidance if accepted</li>
                    <li>Facilitating project development and support</li>
                    <li>Connecting you with relevant resources and opportunities</li>
                </ul>
                
                <h3>4.2 Prohibited Uses</h3>
                <p>Your information will NOT be used to:</p>
                <ul>
                    <li>Develop competing products or services</li>
                    <li>Share with competitors or third parties for commercial gain</li>
                    <li>Violate Kenyan export control laws or regulations</li>
                    <li>Create derivative works without permission</li>
                </ul>
                
                <h3>4.3 Intellectual Property</h3>
                <p><strong>You retain all rights</strong> to your intellectual property. This agreement does not grant JHUB AFRICA any:</p>
                <ul>
                    <li>Patent rights</li>
                    <li>Copyright ownership</li>
                    <li>Trademark licenses</li>
                    <li>Ownership of innovations or inventions</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>5. Non-Circumvention</h2>
                
                <p>Both parties agree not to:</p>
                <ul>
                    <li>Exploit disclosed information for personal or third-party gain</li>
                    <li>Directly or indirectly solicit business relationships revealed through this agreement</li>
                    <li>Bypass the other party to engage with disclosed vendors, customers, or partners</li>
                    <li>Use confidential information to gain competitive advantage</li>
                </ul>
                
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> What This Means:</strong> If JHUB introduces you to potential partners, investors, or collaborators, you agree not to circumvent JHUB to work directly with them without proper acknowledgment and agreement.
                </div>
            </div>
            
            <div class="section">
                <h2>6. Return of Materials</h2>
                
                <h3>6.1 Upon Request or Termination</h3>
                <p>All Proprietary Information and copies must be:</p>
                <ul>
                    <li>Returned immediately upon request</li>
                    <li>Returned upon completion or termination of the relationship</li>
                    <li>Destroyed if return is not feasible (with written confirmation)</li>
                </ul>
                
                <h3>6.2 Destruction of Records</h3>
                <p>The Receiving Party must destroy:</p>
                <ul>
                    <li>All notes containing Proprietary Information</li>
                    <li>Copies made by officers, employees, or agents</li>
                    <li>Digital files and backup copies</li>
                    <li>Any derivative materials based on disclosed information</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>7. Application Process & Project Acceptance</h2>
                
                <h3>7.1 Application Review</h3>
                <p>Your application will be reviewed by JHUB AFRICA's evaluation committee. Possible outcomes include:</p>
                <ul>
                    <li><strong style="color: #3fa845;">Approval:</strong> Project accepted into JHUB ecosystem</li>
                    <li><strong style="color: #2c409a;">Request for More Information:</strong> Additional details needed</li>
                    <li><strong style="color: #dc3545;">Rejection:</strong> Project does not meet current criteria</li>
                </ul>
                
                <div class="info-box">
                    <strong><i class="fas fa-clock"></i> Timeline:</strong> You will receive notification of our decision via email within a reasonable timeframe. Check your email regularly for updates.
                </div>
                
                <h3>7.2 No Guarantee of Acceptance</h3>
                <p>Submission of an application does not guarantee:</p>
                <ul>
                    <li>Project acceptance or approval</li>
                    <li>Access to funding or resources</li>
                    <li>Mentorship assignment</li>
                    <li>Future business opportunities</li>
                </ul>
                
                <h3>7.3 Account Creation</h3>
                <p>If approved, the username and password you provide will grant access to:</p>
                <ul>
                    <li>Your project dashboard</li>
                    <li>Mentorship resources</li>
                    <li>Progress tracking tools</li>
                    <li>Communication platforms</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>8. Duration & Termination</h2>
                
                <h3>8.1 Agreement Duration</h3>
                <p>This agreement remains in effect for <strong style="color: #2c409a;">three (3) years</strong> from the date of your application submission.</p>
                
                <h3>8.2 Confidentiality Survival</h3>
                <p><strong>Important:</strong> Confidentiality obligations continue even after:</p>
                <ul>
                    <li>Termination of this agreement</li>
                    <li>Completion of your project</li>
                    <li>Withdrawal from the JHUB program</li>
                </ul>
                
                <h3>8.3 Termination Rights</h3>
                <p>Either party may terminate this agreement by providing <strong>seven (7) days written notice</strong>. However, confidentiality obligations remain in force.</p>
            </div>
            
            <div class="section">
                <h2>9. Governing Law & Jurisdiction</h2>
                
                <h3>9.1 Applicable Law</h3>
                <p>This agreement is governed by the <strong>Laws of Kenya</strong>.</p>
                
                <h3>9.2 Dispute Resolution</h3>
                <p>In the event of a dispute:</p>
                <ol>
                    <li><strong>Amicable Resolution:</strong> Parties will attempt to resolve disputes amicably within fourteen (14) days</li>
                    <li><strong>Legal Action:</strong> If unresolved, either party may refer the matter to a Kenyan Court of competent jurisdiction</li>
                    <li><strong>Exclusive Jurisdiction:</strong> Kenyan courts have exclusive jurisdiction over all disputes</li>
                </ol>
            </div>
            
            <div class="section">
                <h2>10. Legal Remedies</h2>
                
                <h3>10.1 Breach of Agreement</h3>
                <p>Violation of this agreement may result in:</p>
                <ul>
                    <li>Immediate termination from the JHUB program</li>
                    <li>Legal action for damages</li>
                    <li>Injunctive relief to prevent further disclosure</li>
                    <li>Specific performance of obligations</li>
                </ul>
                
                <div class="highlight-box">
                    <strong><i class="fas fa-gavel"></i> Irreparable Harm:</strong> Both parties acknowledge that breach of confidentiality provisions causes irreparable harm that cannot be adequately compensated by monetary damages alone.
                </div>
            </div>
            
            <div class="section">
                <h2>11. General Provisions</h2>
                
                <h3>11.1 Entire Agreement</h3>
                <p>This document constitutes the complete agreement between you and JHUB AFRICA regarding confidentiality and supersedes all prior discussions or agreements.</p>
                
                <h3>11.2 Amendments</h3>
                <p>Changes to this agreement must be:</p>
                <ul>
                    <li>Made in writing</li>
                    <li>Signed by both parties</li>
                    <li>Explicitly referenced as amendments</li>
                </ul>
                
                <h3>11.3 Severability</h3>
                <p>If any provision is found invalid or unenforceable, the remaining provisions remain in full effect.</p>
                
                <h3>11.4 Non-Transferability</h3>
                <p>You cannot transfer your rights or obligations under this agreement without JHUB AFRICA's prior written consent.</p>
                
                <h3>11.5 Binding Agreement</h3>
                <p>This agreement binds and benefits:</p>
                <ul>
                    <li>You and your successors</li>
                    <li>JHUB AFRICA and its successors</li>
                    <li>Legal representatives and assigns (with proper authorization)</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>12. Your Responsibilities</h2>
                
                <h3>12.1 Accurate Information</h3>
                <p>You agree to provide:</p>
                <ul>
                    <li>Truthful and accurate information in your application</li>
                    <li>Valid contact details for communication</li>
                    <li>Legitimate project documentation</li>
                    <li>Updates if circumstances change materially</li>
                </ul>
                
                <h3>12.2 Account Security</h3>
                <p>You are responsible for:</p>
                <ul>
                    <li>Maintaining the confidentiality of your login credentials</li>
                    <li>All activities conducted under your account</li>
                    <li>Notifying JHUB immediately of unauthorized access</li>
                </ul>
                
                <h3>12.3 Professional Conduct</h3>
                <p>You agree to:</p>
                <ul>
                    <li>Treat JHUB staff and mentors with respect</li>
                    <li>Engage constructively in the program</li>
                    <li>Follow program guidelines and requirements</li>
                    <li>Represent your project honestly</li>
                </ul>
            </div>
            
            <div class="acceptance-section">
                <h3><span class="icon-circle"><i class="fas fa-pen-nib"></i></span>Acknowledgment & Acceptance</h3>
                <p><strong>By checking the acceptance box and submitting your application, you confirm that:</strong></p>
                <ol>
                    <li>You have read and understood these Terms & Conditions in their entirety</li>
                    <li>You agree to be legally bound by all provisions herein</li>
                    <li>You accept the mutual non-disclosure obligations</li>
                    <li>You authorize JHUB AFRICA to use your information as described</li>
                    <li>You understand your rights and responsibilities</li>
                    <li>You acknowledge that this forms a legally binding contract</li>
                </ol>
            </div>
            
            <div class="section">
                <h2>13. Contact Information</h2>
                <p>For questions about these Terms & Conditions, please contact:</p>
                <p style="background: linear-gradient(135deg, rgba(59, 84, 199, 0.1) 0%, rgba(14, 1, 91, 0.1) 100%); padding: 20px; border-radius: 8px; border-left: 4px solid #2c409a;">
                    <strong style="color: #2c409a;">JHUB AFRICA</strong><br>
                    Jomo Kenyatta Innovations and Accelerator Network Hub<br>
                    P.O. Box 62000-00200<br>
                    City Square, Nairobi, Kenya<br>
                    <br>
                    <i class="fas fa-envelope" style="color: #2c409a;"></i> <strong>Email:</strong> info.jhub@jkuat.ac.ke<br>
                    <i class="fas fa-globe" style="color: #2c409a;"></i> <strong>Website:</strong> https://jhubafrica.com/
                </p>
            </div>

            <!-- Back to Application Button -->
            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <a href="../applications/submit.php" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%); border: none; padding: 12px 30px; font-size: 1.1em; text-decoration: none; color: white; border-radius: 8px; display: inline-block;">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Application
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p style="text-align: center; color: #6c757d;"><strong style="color: #2c409a;">© 2025 JHUB AFRICA. All rights reserved.</strong></p>
            <p style="text-align: center; color: #6c757d;">Jomo Kenyatta Innovations and Accelerator Network Hub</p>
            <p style="margin-top: 15px; font-size: 0.9em; text-align: center; color: #6c757d;">This is a legally binding agreement. Please read carefully before accepting.</p>
        </div>
    </div>
</body>
</html>