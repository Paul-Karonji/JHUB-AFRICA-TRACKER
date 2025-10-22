<?php
require_once '../includes/init.php';

$pageTitle = "Terms & Conditions";

$customStyles = <<<CSS
    <style>
        .terms-page {
            padding: 60px 15px;
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
        }
        .container-custom {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .header {
            background: #ffffff;
            padding: 50px 40px;
            text-align: center;
            border-bottom: 4px solid var(--primary-color);
        }
        .logo-container {
            margin-bottom: 25px;
        }
        .logo-container img {
            max-width: 240px;
            height: auto;
        }
        .logo-fallback {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8em;
            color: var(--primary-color);
        }
        .logo-fallback i {
            font-size: 2em;
            color: var(--secondary-color);
        }
        .header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary-color);
        }
        .header p {
            font-size: 1.1em;
            color: #6c757d;
        }
        .content {
            padding: 40px;
        }
        .last-updated {
            background: linear-gradient(135deg, rgba(44, 64, 154, 0.12) 0%, rgba(14, 1, 91, 0.1) 100%);
            padding: 15px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 30px;
            border-radius: 6px;
        }
        h2 {
            color: var(--primary-color);
            font-size: 1.6em;
            margin: 35px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        h3 {
            color: var(--secondary-color);
            font-size: 1.3em;
            margin: 25px 0 15px;
        }
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        ul,
        ol {
            margin: 15px 0 15px 30px;
        }
        li {
            margin-bottom: 10px;
        }
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #fd1616;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .info-box {
            background: linear-gradient(135deg, rgba(44, 64, 154, 0.12) 0%, rgba(63, 168, 69, 0.12) 100%);
            border-left: 4px solid var(--accent-color);
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .signature-box {
            border: 2px dashed rgba(44, 64, 154, 0.4);
            padding: 25px;
            border-radius: 6px;
            margin-top: 30px;
            background: rgba(44, 64, 154, 0.03);
        }
        .signature-box h3 {
            margin-top: 0;
        }
        .table-section {
            overflow-x: auto;
            margin: 25px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.95em;
        }
        table th,
        table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        table th {
            background: rgba(44, 64, 154, 0.1);
            font-weight: 600;
        }
        .acceptance-section {
            background: rgba(44, 64, 154, 0.05);
            border-radius: 8px;
            padding: 25px;
            margin-top: 35px;
            border: 1px solid rgba(44, 64, 154, 0.12);
        }
        .acceptance-section .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            margin-right: 12px;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            border-top: 1px solid #e9ecef;
        }
        @media (max-width: 768px) {
            .content {
                padding: 25px;
            }
            .header {
                padding: 40px 20px;
            }
        }
    </style>
CSS;

require_once '../templates/public-header.php';

?>

<div class="terms-page">
    <div class="container-custom">
        <div class="header">
            <div class="logo-container">
                <img src="<?php echo SITE_URL ?? ''; ?>/assets/images/logo/JHUB Africa Logo.png" 
                     alt="JHUB AFRICA"
                     onerror="this.style.display='none'; this.insertAdjacentHTML('afterend','<span class=\'logo-fallback\'><i class=\'fas fa-lightbulb\'></i>JHUB AFRICA</span>');">
            </div>
            <h1>Terms &amp; Conditions</h1>
            <p>Mutual Non-Disclosure Agreement (NDA) &amp; Participation Terms for Innovators</p>
        </div>

        <div class="content">
            <div class="last-updated">
                <strong>Last Updated:</strong> <?php echo date('F d, Y'); ?><br>
                These Terms &amp; Conditions represent a legally binding agreement between you (the innovator) and JHUB AFRICA, a program of Jomo Kenyatta University of Agriculture and Technology (JKUAT).
            </div>

            <div class="section">
                <h2>1. Purpose of Agreement</h2>
                <p>The purpose of this agreement is to establish the terms under which innovators participate in the JHUB AFRICA program, ensure mutual protection of confidential information, and define responsibilities for both parties during the innovation acceleration process.</p>
            </div>

            <div class="section">
                <h2>2. Definitions</h2>
                <h3>2.1 JHUB AFRICA</h3>
                <p>Refers to the innovation acceleration program operated by JKUAT, including its staff, mentors, partners, investors, and authorized representatives.</p>

                <h3>2.2 Innovator</h3>
                <p>Any individual or team that submits an innovation idea or project for evaluation, mentorship, or support through the JHUB AFRICA platform.</p>

                <h3>2.3 Confidential Information</h3>
                <p>Includes all non-public information related to:</p>
                <ul>
                    <li>Innovation ideas, concepts, prototypes, and business models</li>
                    <li>Technical data, designs, algorithms, and research data</li>
                    <li>Financial projections, funding strategies, and investor lists</li>
                    <li>Mentorship advice, development plans, and evaluation results</li>
                    <li>Any information designated as confidential or that reasonably should be understood as confidential</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Mutual Non-Disclosure Agreement (NDA)</h2>
                <div class="info-box">
                    <p>The NDA ensures that both JHUB AFRICA and the Innovator agree to keep each other's confidential information private and secure.</p>
                </div>

                <h3>3.1 Innovator's Obligations</h3>
                <p>You agree to:</p>
                <ul>
                    <li>Maintain the confidentiality of any proprietary JHUB AFRICA materials</li>
                    <li>Not disclose mentor feedback, evaluation reports, or internal processes without permission</li>
                    <li>Use the JHUB platform responsibly and ethically</li>
                </ul>

                <h3>3.2 JHUB AFRICA's Obligations</h3>
                <p>We agree to:</p>
                <ul>
                    <li>Keep your innovation details confidential</li>
                    <li>Use your information only for evaluation, mentorship, and program support</li>
                    <li>Not disclose your project information to external parties without your consent</li>
                </ul>

                <h3>3.3 Exceptions to Confidentiality</h3>
                <p>Information is not considered confidential if it:</p>
                <ul>
                    <li>Was publicly known before disclosure</li>
                    <li>Becomes public through no fault of the receiving party</li>
                    <li>Is independently developed without using confidential information</li>
                    <li>Is required by law or court order (with prior notice, if possible)</li>
                </ul>
            </div>

            <div class="section">
                <h2>4. Innovation Submission Process</h2>
                <h3>4.1 Eligibility Criteria</h3>
                <p>By submitting your innovation, you confirm that:</p>
                <ul>
                    <li>You are the original creator or have rights to the idea</li>
                    <li>You have authority to share the idea with JHUB AFRICA</li>
                    <li>The idea complies with all applicable laws</li>
                    <li>The innovation does not infringe on any third-party rights</li>
                </ul>

                <h3>4.2 Submission Requirements</h3>
                <ol>
                    <li>Complete application form with accurate information</li>
                    <li>Provide a clear description of the innovation</li>
                    <li>Submit supporting documents (if requested)</li>
                    <li>Agree to these Terms &amp; Conditions</li>
                </ol>

                <h3>4.3 Evaluation Process</h3>
                <ul>
                    <li>Applications are reviewed by JHUB AFRICA staff and mentors</li>
                    <li>Evaluation criteria include innovation, feasibility, impact, and team capability</li>
                    <li>Results are communicated via email within 14 working days</li>
                    <li>Not all applications will be accepted; acceptance is at JHUB AFRICA's discretion</li>
                </ul>

                <div class="highlight-box">
                    <strong><i class="fas fa-bell"></i> Important:</strong> Submission does not guarantee acceptance into the program or any funding. Selection is competitive and based on multiple criteria.
                </div>
            </div>

            <div class="section">
                <h2>5. Participation in the Program</h2>
                <h3>5.1 Innovator Obligations</h3>
                <p>Once accepted, you agree to:</p>
                <ul>
                    <li>Actively participate in mentorship sessions and workshops</li>
                    <li>Respond to communication within 48 hours</li>
                    <li>Provide updates on project progress when requested</li>
                    <li>Maintain accurate and up-to-date information on your profile</li>
                    <li>Respect other innovators, mentors, and staff</li>
                </ul>

                <h3>5.2 Intellectual Property Rights</h3>
                <ul>
                    <li>You retain full ownership of your innovation</li>
                    <li>JHUB AFRICA does not claim any intellectual property rights</li>
                    <li>You grant JHUB AFRICA permission to showcase your innovation (with your consent) for promotional purposes</li>
                </ul>

                <h3>5.3 Program Support</h3>
                <p>JHUB AFRICA provides:</p>
                <ul>
                    <li>Mentorship from industry experts</li>
                    <li>Access to innovation resources and tools</li>
                    <li>Networking opportunities with investors and partners</li>
                    <li>Potential access to funding opportunities (subject to separate agreements)</li>
                </ul>
            </div>

            <div class="section">
                <h2>6. Data Privacy &amp; Protection</h2>
                <p>We are committed to protecting your personal and innovation data. By using the JHUB platform, you consent to the collection, use, and storage of your data for program purposes.</p>

                <h3>6.1 Data We Collect</h3>
                <ul>
                    <li>Personal information (name, email, contacts)</li>
                    <li>Innovation details and documents</li>
                    <li>Mentorship session notes and feedback</li>
                    <li>Usage data from the platform</li>
                </ul>

                <h3>6.2 Data Usage</h3>
                <ul>
                    <li>To evaluate your innovation</li>
                    <li>To connect you with mentors and partners</li>
                    <li>To improve the JHUB platform and services</li>
                    <li>To communicate important updates</li>
                </ul>

                <h3>6.3 Data Protection</h3>
                <p>We implement appropriate technical and organizational measures to secure your data. However, no system is 100% secure. You are responsible for safeguarding your login credentials.</p>
            </div>

            <div class="section">
                <h2>7. Termination</h2>
                <h3>7.1 Voluntary Withdrawal</h3>
                <p>You may withdraw from the program by submitting a written notice to JHUB AFRICA. Upon withdrawal, all confidential information must be returned or destroyed.</p>

                <h3>7.2 Termination by JHUB AFRICA</h3>
                <p>We may terminate your participation if you:</p>
                <ul>
                    <li>Violate these Terms &amp; Conditions</li>
                    <li>Engage in unethical or fraudulent behavior</li>
                    <li>Misuse the program resources</li>
                    <li>Fail to participate actively</li>
                </ul>

                <h3>7.3 Effect of Termination</h3>
                <p>Upon termination:</p>
                <ul>
                    <li>Access to JHUB resources is revoked</li>
                    <li>Confidential information must be returned or destroyed</li>
                    <li>All pending obligations remain enforceable</li>
                </ul>
            </div>

            <div class="section">
                <h2>8. Limitation of Liability</h2>
                <p>JHUB AFRICA is not liable for any:</p>
                <ul>
                    <li>Loss of intellectual property or business opportunity</li>
                    <li>Damage caused by third parties (mentors, partners, or investors)</li>
                    <li>Indirect, incidental, or consequential damages</li>
                </ul>

                <div class="highlight-box">
                    <strong><i class="fas fa-shield-alt"></i> Disclaimer:</strong> Participation in the program does not guarantee funding, commercial success, or investor partnerships.
                </div>
            </div>

            <div class="section">
                <h2>9. Dispute Resolution</h2>
                <p>In the event of a dispute:</p>
                <ul>
                    <li>Parties will first attempt amicable resolution</li>
                    <li>If unresolved, disputes will be mediated in Nairobi, Kenya</li>
                    <li>If mediation fails, disputes will be settled through arbitration under Kenyan law</li>
                </ul>
            </div>

            <div class="section">
                <h2>10. Legal Remedies</h2>

                <h3>10.1 Breach of Agreement</h3>
                <p>Violation of this agreement may result in:</p>
                <ul>
                    <li>Immediate termination from the JHUB program</li>
                    <li>Legal action for damages</li>
                    <li>Injunctive relief to prevent further disclosure</li>
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
                <h3><span class="icon-circle"><i class="fas fa-pen-nib"></i></span>Acknowledgment &amp; Acceptance</h3>
                <p><strong>By checking the acceptance box and submitting your application, you confirm that:</strong></p>
                <ol>
                    <li>You have read and understood these Terms &amp; Conditions in their entirety</li>
                    <li>You agree to be legally bound by all provisions herein</li>
                    <li>You accept the mutual non-disclosure obligations</li>
                    <li>You authorize JHUB AFRICA to use your information as described</li>
                    <li>You understand your rights and responsibilities</li>
                    <li>You acknowledge that this forms a legally binding contract</li>
                </ol>
            </div>

            <div class="section">
                <h2>13. Contact Information</h2>
                <p>For questions about these Terms &amp; Conditions, please contact:</p>
                <p style="background: linear-gradient(135deg, rgba(44, 64, 154, 0.12) 0%, rgba(14, 1, 91, 0.1) 100%); padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                    <strong style="color: var(--primary-color);">JHUB AFRICA</strong><br>
                    Jomo Kenyatta Innovations and Accelerator Network Hub<br>
                    P.O. Box 62000-00200<br>
                    City Square, Nairobi, Kenya<br><br>
                    <i class="fas fa-envelope" style="color: var(--primary-color);"></i> <strong>Email:</strong> info.jhub@jkuat.ac.ke<br>
                    <i class="fas fa-globe" style="color: var(--primary-color);"></i> <strong>Website:</strong> https://jhubafrica.com/
                </p>
            </div>

            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <a href="../applications/submit.php" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none; padding: 12px 30px; font-size: 1.1em; text-decoration: none; color: white; border-radius: 8px; display: inline-block;">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Application
                </a>
            </div>
        </div>

        <div class="footer">
            <p style="text-align: center; color: #6c757d;"><strong style="color: var(--primary-color);">© <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</strong></p>
            <p style="text-align: center; color: #6c757d;">Jomo Kenyatta Innovations and Accelerator Network Hub</p>
            <p style="margin-top: 15px; font-size: 0.9em; text-align: center; color: #6c757d;">This is a legally binding agreement. Please read carefully before accepting.</p>
        </div>
    </div>
</div>

<?php require_once '../templates/public-footer.php'; ?>
