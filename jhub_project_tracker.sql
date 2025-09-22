CREATE DATABASE IF NOT EXISTS `jhub_project_tracker` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `jhub_project_tracker`;

-- Set SQL mode for better compatibility
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop tables in correct order (respecting foreign key constraints)
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `project_mentors`;
DROP TABLE IF EXISTS `project_innovators`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `mentors`;
DROP TABLE IF EXISTS `admins`;

-- Admins Table (Fixed: removed deprecated INT display width and duplicate index)
CREATE TABLE `admins` (
    `admin_id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mentors Table (Fixed: removed deprecated INT display width and duplicate index)
CREATE TABLE `mentors` (
    `mentor_id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `bio` TEXT NOT NULL,
    `area_of_expertise` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `years_experience` INT DEFAULT NULL,
    PRIMARY KEY (`mentor_id`),
    UNIQUE KEY `unique_email` (`email`),
    KEY `idx_expertise` (`area_of_expertise`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_mentor_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects Table (Fixed: removed deprecated INT display width and duplicate index)
CREATE TABLE `projects` (
    `project_id` INT NOT NULL AUTO_INCREMENT,
    `project_name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `project_email` VARCHAR(255) DEFAULT NULL,
    `project_website` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `profile_name` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `current_stage` INT NOT NULL DEFAULT 1 CHECK (`current_stage` BETWEEN 1 AND 6),
    `status` ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completion_date` TIMESTAMP NULL DEFAULT NULL,
    `termination_reason` TEXT DEFAULT NULL,
    `project_logo` VARCHAR(255) DEFAULT NULL,
    `project_documents` TEXT DEFAULT NULL,
    `funding_amount` DECIMAL(15,2) DEFAULT NULL,
    `funding_currency` VARCHAR(3) DEFAULT 'USD',
    `target_market` VARCHAR(255) DEFAULT NULL,
    `business_model` TEXT DEFAULT NULL,
    PRIMARY KEY (`project_id`),
    UNIQUE KEY `unique_profile_name` (`profile_name`),
    KEY `idx_status` (`status`),
    KEY `idx_stage` (`current_stage`),
    KEY `idx_created_date` (`created_at`),
    KEY `idx_project_date` (`date`),
    KEY `idx_status_stage` (`status`, `current_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Innovators Table (Fixed: removed deprecated INT display width)
CREATE TABLE `project_innovators` (
    `pi_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `role` VARCHAR(100) NOT NULL,
    `level_of_experience` VARCHAR(100) DEFAULT NULL,
    `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by_ip` VARCHAR(45) DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    PRIMARY KEY (`pi_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_active` (`is_active`),
    KEY `idx_project_email` (`project_id`, `email`),
    CONSTRAINT `fk_pi_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Mentors Table (Fixed: removed deprecated INT display width)
CREATE TABLE `project_mentors` (
    `pm_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `mentor_id` INT NOT NULL,
    `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `assigned_by_mentor` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_interaction` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`pm_id`),
    UNIQUE KEY `unique_project_mentor` (`project_id`, `mentor_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_mentor_id` (`mentor_id`),
    KEY `idx_active` (`is_active`),
    KEY `idx_assigned_date` (`assigned_at`),
    CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pm_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments Table (Fixed: removed deprecated INT display width)
CREATE TABLE `comments` (
    `comment_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `commenter_type` ENUM('admin','mentor','innovator','investor') NOT NULL,
    `commenter_name` VARCHAR(100) NOT NULL,
    `commenter_email` VARCHAR(255) DEFAULT NULL,
    `commenter_id` INT DEFAULT NULL,
    `comment_text` TEXT NOT NULL,
    `parent_comment_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_edited` BOOLEAN DEFAULT FALSE,
    `is_deleted` BOOLEAN DEFAULT FALSE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    PRIMARY KEY (`comment_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_parent_comment` (`parent_comment_id`),
    KEY `idx_commenter_type` (`commenter_type`),
    KEY `idx_created_date` (`created_at`),
    KEY `idx_project_parent` (`project_id`, `parent_comment_id`),
    KEY `idx_active_comments` (`project_id`, `is_deleted`),
    CONSTRAINT `fk_comment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Reset Tokens Table (Fixed: removed deprecated INT display width)
CREATE TABLE `password_reset_tokens` (
    `token_id` INT NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `user_type` ENUM('admin','mentor','project') NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` TIMESTAMP NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `unique_token` (`token`),
    KEY `idx_email` (`email`),
    KEY `idx_expires` (`expires_at`),
    KEY `idx_email_type` (`email`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table (Fixed: removed deprecated INT display width)
CREATE TABLE `activity_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('admin','mentor','project','system') NOT NULL,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `project_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `additional_data` JSON DEFAULT NULL,
    PRIMARY KEY (`log_id`),
    KEY `idx_user_type_id` (`user_type`, `user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_created_date` (`created_at`),
    KEY `idx_user_action_date` (`user_type`, `action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table (Fixed: removed deprecated INT display width and duplicate index)
CREATE TABLE `system_settings` (
    `setting_id` INT NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `setting_type` ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
    `description` TEXT DEFAULT NULL,
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Composite indexes for common queries
CREATE INDEX `idx_projects_status_stage_date` ON `projects` (`status`, `current_stage`, `created_at`);
CREATE INDEX `idx_projects_search` ON `projects` (`project_name`, `status`);
CREATE INDEX `idx_mentors_search` ON `mentors` (`name`, `area_of_expertise`, `is_active`);
CREATE INDEX `idx_comments_thread` ON `comments` (`project_id`, `parent_comment_id`, `created_at`);
CREATE INDEX `idx_innovators_search` ON `project_innovators` (`name`, `email`, `role`);

-- Full-text indexes for search functionality
CREATE FULLTEXT INDEX `ft_projects_search` ON `projects` (`project_name`, `description`);
CREATE FULLTEXT INDEX `ft_mentors_search` ON `mentors` (`name`, `bio`, `area_of_expertise`);
CREATE FULLTEXT INDEX `ft_comments_search` ON `comments` (`comment_text`);

-- Insert default admin (password: admin123)
INSERT INTO `admins` (`username`, `password`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample mentors
INSERT INTO `mentors` (`name`, `email`, `password`, `bio`, `area_of_expertise`, `created_by`, `years_experience`) VALUES 
('John Kamau', 'john.kamau@jhubafrica.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Experienced technology entrepreneur with over 15 years in the African startup ecosystem. Founded three successful tech companies and helped over 50 startups scale their operations.', 'Technology & Innovation', 1, 15),
('Grace Wanjiku', 'grace.wanjiku@jhubafrica.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marketing strategist and brand expert specializing in African markets. Has worked with Fortune 500 companies and local startups to build compelling brand narratives and go-to-market strategies.', 'Marketing & Sales', 1, 12),
('Samuel Ochieng', 'samuel.ochieng@jhubafrica.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Financial advisor and investment expert with deep knowledge of African financial markets. Former investment banker who now focuses on helping startups secure funding and manage finances.', 'Finance & Fundraising', 1, 18),
('Dr. Amina Hassan', 'amina.hassan@jhubafrica.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Healthcare innovation specialist and former WHO consultant. Passionate about leveraging technology to improve healthcare access across Africa.', 'Healthcare & Life Sciences', 1, 20),
('Peter Mwangi', 'peter.mwangi@jhubafrica.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agricultural technology expert and sustainable farming advocate. Works with farmers and agtech companies to implement innovative farming solutions.', 'Agriculture & Food Systems', 1, 14);

-- Insert sample projects
INSERT INTO `projects` (`project_name`, `date`, `project_email`, `project_website`, `description`, `profile_name`, `password`, `current_stage`, `status`, `target_market`, `business_model`) VALUES 
('EcoWater Solutions', '2024-08-15', 'contact@ecowater.ke', 'www.ecowater.ke', 'Developing affordable water purification systems for rural communities in Kenya. Our innovative approach uses solar power and locally sourced materials to provide clean drinking water at scale.', 'ecowater2024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'active', 'Rural communities in East Africa', 'B2B2C - Partnership with NGOs and government agencies'),

('AgriTech Yield', '2024-07-22', 'info@agritechyield.com', 'www.agritechyield.com', 'Smart farming platform that uses IoT sensors and AI to help smallholder farmers optimize crop yields and reduce waste. Includes weather prediction, soil analysis, and market price tracking.', 'agritech2024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'active', 'Smallholder farmers in Sub-Saharan Africa', 'SaaS subscription with hardware sales'),

('HealthConnect Mobile', '2024-06-10', 'team@healthconnect.africa', 'www.healthconnect.africa', 'Telemedicine platform connecting rural patients with healthcare professionals. Features include video consultations, prescription delivery, and health record management.', 'healthconnect24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5, 'active', 'Rural communities with limited healthcare access', 'Freemium model with subscription tiers'),

('EduLearn Africa', '2024-09-01', 'hello@edulearn.africa', 'www.edulearn.africa', 'Digital learning platform providing quality education content in local languages. Focuses on STEM subjects and vocational skills training for students across Africa.', 'edulearn2024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'active', 'Students and educational institutions in Africa', 'B2B licensing and B2C subscriptions'),

('GreenEnergy Hub', '2024-05-18', 'contact@greenenergy.ke', NULL, 'Community-based renewable energy solutions focusing on solar microgrids for off-grid communities. Includes energy storage, smart distribution, and mobile payment integration.', 'greenenergy24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 6, 'completed', 'Off-grid communities in rural Africa', 'Pay-as-you-go energy services'),

('FinTech Soko', '2024-04-12', 'support@fintechsoko.com', 'www.fintechsoko.com', 'Mobile money and digital banking platform designed for informal traders and small businesses. Features include micro-loans, savings accounts, and business analytics.', 'fintechsoko24', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 'active', 'Informal traders and SMEs in East Africa', 'Transaction fees and interest on microloans');

-- Insert project innovators (team members)
INSERT INTO `project_innovators` (`project_id`, `name`, `email`, `role`, `level_of_experience`) VALUES 
(1, 'Mary Njeri', 'mary.njeri@ecowater.ke', 'Project Lead', 'Advanced'),
(1, 'David Kiprotich', 'david.kiprotich@ecowater.ke', 'Engineer', 'Intermediate'),
(1, 'Sarah Wambui', 'sarah.wambui@ecowater.ke', 'Marketing Coordinator', 'Beginner'),

(2, 'James Oduya', 'james.oduya@agritechyield.com', 'CEO & Founder', 'Expert'),
(2, 'Linda Cherotich', 'linda.cherotich@agritechyield.com', 'CTO', 'Advanced'),
(2, 'Robert Maina', 'robert.maina@agritechyield.com', 'Product Manager', 'Intermediate'),

(3, 'Dr. Faith Karanja', 'faith.karanja@healthconnect.africa', 'Founder & CEO', 'Expert'),
(3, 'Michael Omondi', 'michael.omondi@healthconnect.africa', 'Lead Developer', 'Advanced'),

(4, 'Alice Mutindi', 'alice.mutindi@edulearn.africa', 'Founder', 'Advanced'),
(4, 'Thomas Kiplagat', 'thomas.kiplagat@edulearn.africa', 'Content Developer', 'Intermediate'),
(4, 'Elizabeth Wanjiru', 'elizabeth.wanjiru@edulearn.africa', 'UX Designer', 'Intermediate'),

(5, 'Kevin Macharia', 'kevin.macharia@greenenergy.ke', 'Technical Lead', 'Expert'),
(5, 'Nancy Akinyi', 'nancy.akinyi@greenenergy.ke', 'Business Development', 'Advanced'),

(6, 'Daniel Kipruto', 'daniel.kipruto@fintechsoko.com', 'Founder & CEO', 'Expert'),
(6, 'Grace Nyokabi', 'grace.nyokabi@fintechsoko.com', 'Head of Operations', 'Advanced');

-- Assign mentors to projects
INSERT INTO `project_mentors` (`project_id`, `mentor_id`) VALUES 
(1, 1), -- EcoWater + John Kamau (Tech)
(1, 3), -- EcoWater + Samuel Ochieng (Finance)
(2, 1), -- AgriTech + John Kamau (Tech)
(2, 5), -- AgriTech + Peter Mwangi (Agriculture)
(3, 4), -- HealthConnect + Dr. Amina Hassan (Healthcare)
(3, 2), -- HealthConnect + Grace Wanjiku (Marketing)
(4, 1), -- EduLearn + John Kamau (Tech)
(4, 2), -- EduLearn + Grace Wanjiku (Marketing)
(5, 1), -- GreenEnergy + John Kamau (Tech)
(5, 3), -- GreenEnergy + Samuel Ochieng (Finance)
(6, 1), -- FinTech + John Kamau (Tech)
(6, 3); -- FinTech + Samuel Ochieng (Finance)

-- Insert sample comments
INSERT INTO `comments` (`project_id`, `commenter_type`, `commenter_name`, `commenter_email`, `commenter_id`, `comment_text`) VALUES 
(1, 'mentor', 'John Kamau', 'john.kamau@jhubafrica.com', 1, 'Excellent progress on the water purification system! The solar integration approach is innovative and well-suited for the target market. I recommend focusing on user testing in the next phase.'),
(1, 'innovator', 'Mary Njeri', 'mary.njeri@ecowater.ke', NULL, 'Thank you for the feedback! We have identified 3 communities for pilot testing and plan to start next month. The local partnerships are proving very valuable.'),
(1, 'investor', 'Anonymous Investor', 'investor@example.com', NULL, 'Very impressed with the scalability potential. What are your projected unit costs at scale?'),

(2, 'mentor', 'Peter Mwangi', 'peter.mwangi@jhubafrica.com', 5, 'The IoT sensor integration looks promising. Have you considered partnerships with existing agricultural cooperatives for faster market penetration?'),
(2, 'innovator', 'James Oduya', 'james.oduya@agritechyield.com', NULL, 'Great suggestion! We are already in discussions with 5 major cooperatives in Central Kenya. The response has been very positive.'),

(3, 'mentor', 'Dr. Amina Hassan', 'amina.hassan@jhubafrica.com', 4, 'The telemedicine platform addresses a critical gap in healthcare delivery. The prescription delivery feature is particularly innovative. Consider regulatory compliance early in your go-to-market strategy.'),
(3, 'admin', 'System Admin', NULL, NULL, 'Project showing excellent progress. Moved to Stage 5 - congratulations to the team!'),

(4, 'mentor', 'Grace Wanjiku', 'grace.wanjiku@jhubafrica.com', 2, 'The local language content is a game-changer for education in Africa. Your user acquisition strategy should leverage community leaders and educational influencers.'),

(5, 'mentor', 'John Kamau', 'john.kamau@jhubafrica.com', 1, 'Congratulations on completing the JHUB AFRICA journey! Your solar microgrid solution is now ready for scale. The impact on rural communities will be significant.'),
(5, 'investor', 'Impact Investor', 'impact@investor.com', NULL, 'Fantastic work! This is exactly the kind of solution Africa needs. Would love to discuss scaling opportunities.'),

(6, 'mentor', 'Samuel Ochieng', 'samuel.ochieng@jhubafrica.com', 3, 'The fintech solution for informal traders is well-designed. The micro-loan feature needs careful risk management - I can help with modeling.');

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES 
('site_name', 'JHUB AFRICA Project Tracker', 'string', 'Name of the platform', TRUE),
('site_version', '1.0.0', 'string', 'Current version of the platform', TRUE),
('max_projects_per_mentor', '10', 'integer', 'Maximum number of projects a mentor can be assigned to', FALSE),
('project_stages', '6', 'integer', 'Number of stages in the innovation framework', TRUE),
('enable_public_projects', 'true', 'boolean', 'Whether projects are visible to public/investors', TRUE),
('default_project_status', 'active', 'string', 'Default status for new projects', FALSE),
('session_timeout', '3600', 'integer', 'Session timeout in seconds', FALSE),
('enable_email_notifications', 'true', 'boolean', 'Whether to send email notifications', FALSE),
('platform_launch_date', '2024-01-01', 'string', 'When the platform was launched', TRUE),
('contact_email', 'support@jhubafrica.com', 'string', 'Platform contact email', TRUE);

DELIMITER $$

-- Trigger to update project completion date when status changes to completed
CREATE TRIGGER `tr_project_completion` 
    BEFORE UPDATE ON `projects` 
    FOR EACH ROW 
BEGIN 
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        SET NEW.completion_date = CURRENT_TIMESTAMP;
    END IF;
    
    IF NEW.current_stage = 6 AND OLD.current_stage < 6 THEN
        SET NEW.status = 'completed';
        SET NEW.completion_date = CURRENT_TIMESTAMP;
    END IF;
END$$

-- Trigger to log project stage changes
CREATE TRIGGER `tr_log_stage_change` 
    AFTER UPDATE ON `projects` 
    FOR EACH ROW 
BEGIN 
    IF NEW.current_stage != OLD.current_stage THEN
        INSERT INTO `activity_logs` (`user_type`, `action`, `description`, `project_id`, `additional_data`)
        VALUES ('system', 'stage_updated', 
                CONCAT('Project stage updated from ', OLD.current_stage, ' to ', NEW.current_stage),
                NEW.project_id,
                JSON_OBJECT('old_stage', OLD.current_stage, 'new_stage', NEW.current_stage));
    END IF;
END$$

-- Trigger to update comment counts
CREATE TRIGGER `tr_update_last_interaction` 
    AFTER INSERT ON `comments` 
    FOR EACH ROW 
BEGIN 
    -- Update last interaction time for project mentors
    UPDATE `project_mentors` 
    SET `last_interaction` = CURRENT_TIMESTAMP 
    WHERE `project_id` = NEW.project_id;
END$$

DELIMITER ;

-- View for project statistics
CREATE VIEW `v_project_statistics` AS
SELECT 
    p.project_id,
    p.project_name,
    p.current_stage,
    p.status,
    p.created_at,
    COUNT(DISTINCT pi.pi_id) as innovator_count,
    COUNT(DISTINCT pm.mentor_id) as mentor_count,
    COUNT(DISTINCT c.comment_id) as comment_count,
    DATEDIFF(COALESCE(p.completion_date, CURRENT_TIMESTAMP), p.created_at) as days_in_progress
FROM `projects` p
LEFT JOIN `project_innovators` pi ON p.project_id = pi.project_id AND pi.is_active = TRUE
LEFT JOIN `project_mentors` pm ON p.project_id = pm.project_id AND pm.is_active = TRUE
LEFT JOIN `comments` c ON p.project_id = c.project_id AND c.is_deleted = FALSE
GROUP BY p.project_id;

-- View for mentor performance
CREATE VIEW `v_mentor_performance` AS
SELECT 
    m.mentor_id,
    m.name,
    m.area_of_expertise,
    COUNT(DISTINCT pm.project_id) as total_projects,
    COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN pm.project_id END) as completed_projects,
    COUNT(DISTINCT CASE WHEN p.status = 'active' THEN pm.project_id END) as active_projects,
    AVG(p.current_stage) as avg_project_stage,
    MAX(pm.assigned_at) as last_assignment
FROM `mentors` m
LEFT JOIN `project_mentors` pm ON m.mentor_id = pm.mentor_id AND pm.is_active = TRUE
LEFT JOIN `projects` p ON pm.project_id = p.project_id
WHERE m.is_active = TRUE
GROUP BY m.mentor_id;

-- View for system dashboard (FIXED: ambiguous column reference)
CREATE VIEW `v_system_dashboard` AS
SELECT 
    (SELECT COUNT(*) FROM `projects`) as total_projects,
    (SELECT COUNT(*) FROM `projects` WHERE status = 'active') as active_projects,
    (SELECT COUNT(*) FROM `projects` WHERE status = 'completed') as completed_projects,
    (SELECT COUNT(*) FROM `projects` WHERE status = 'terminated') as terminated_projects,
    (SELECT COUNT(*) FROM `mentors` WHERE is_active = TRUE) as active_mentors,
    (SELECT COUNT(*) FROM `project_innovators` WHERE is_active = TRUE) as total_innovators,
    (SELECT COUNT(*) FROM `comments` WHERE is_deleted = FALSE) as total_comments,
    (SELECT COUNT(*) FROM `projects` WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)) as projects_last_30_days,
    (SELECT AVG(current_stage) FROM `projects` WHERE status = 'active') as avg_project_stage,
    -- Fixed: Added table aliases to resolve ambiguous column reference
    (SELECT ROUND((COUNT(DISTINCT pm.mentor_id) / COUNT(DISTINCT m.mentor_id)) * 100, 2)
     FROM `mentors` m 
     LEFT JOIN `project_mentors` pm ON m.mentor_id = pm.mentor_id 
     WHERE m.is_active = TRUE) as mentor_utilization_rate;

DELIMITER $

-- Procedure to get project details with all related information
CREATE PROCEDURE `sp_get_project_details`(IN p_project_id INT)
BEGIN
    -- Project basic info
    SELECT 
        p.*,
        COUNT(DISTINCT pi.pi_id) as innovator_count,
        COUNT(DISTINCT pm.mentor_id) as mentor_count,
        COUNT(DISTINCT c.comment_id) as comment_count
    FROM `projects` p
    LEFT JOIN `project_innovators` pi ON p.project_id = pi.project_id AND pi.is_active = TRUE
    LEFT JOIN `project_mentors` pm ON p.project_id = pm.project_id AND pm.is_active = TRUE
    LEFT JOIN `comments` c ON p.project_id = c.project_id AND c.is_deleted = FALSE
    WHERE p.project_id = p_project_id
    GROUP BY p.project_id;
    
    -- Project team members
    SELECT pi.*, 'innovator' as member_type
    FROM `project_innovators` pi
    WHERE pi.project_id = p_project_id AND pi.is_active = TRUE
    ORDER BY pi.added_at ASC;
    
    -- Assigned mentors
    SELECT m.*, pm.assigned_at, pm.notes
    FROM `project_mentors` pm
    JOIN `mentors` m ON pm.mentor_id = m.mentor_id
    WHERE pm.project_id = p_project_id AND pm.is_active = TRUE AND m.is_active = TRUE
    ORDER BY pm.assigned_at ASC;
    
    -- Recent comments
    SELECT c.*, 
           CASE 
               WHEN c.parent_comment_id IS NULL THEN 0
               ELSE 1
           END as is_reply
    FROM `comments` c
    WHERE c.project_id = p_project_id AND c.is_deleted = FALSE
    ORDER BY c.created_at DESC
    LIMIT 10;
END$

-- Procedure to get mentor dashboard data
CREATE PROCEDURE `sp_get_mentor_dashboard`(IN p_mentor_id INT)
BEGIN
    -- Mentor basic info
    SELECT * FROM `mentors` WHERE mentor_id = p_mentor_id AND is_active = TRUE;
    
    -- Assigned projects
    SELECT 
        p.*,
        pm.assigned_at,
        COUNT(DISTINCT pi.pi_id) as innovator_count,
        COUNT(DISTINCT c.comment_id) as comment_count
    FROM `project_mentors` pm
    JOIN `projects` p ON pm.project_id = p.project_id
    LEFT JOIN `project_innovators` pi ON p.project_id = pi.project_id AND pi.is_active = TRUE
    LEFT JOIN `comments` c ON p.project_id = c.project_id AND c.is_deleted = FALSE
    WHERE pm.mentor_id = p_mentor_id AND pm.is_active = TRUE AND p.status != 'terminated'
    GROUP BY p.project_id
    ORDER BY pm.assigned_at DESC;
    
    -- Available projects for assignment
    SELECT 
        p.*,
        COUNT(DISTINCT pi.pi_id) as innovator_count
    FROM `projects` p
    LEFT JOIN `project_innovators` pi ON p.project_id = pi.project_id AND pi.is_active = TRUE
    WHERE p.status = 'active' 
    AND p.project_id NOT IN (
        SELECT project_id FROM `project_mentors` 
        WHERE mentor_id = p_mentor_id AND is_active = TRUE
    )
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
    LIMIT 10;
END$

-- Procedure to update project stage with validation
CREATE PROCEDURE `sp_update_project_stage`(
    IN p_project_id INT,
    IN p_new_stage INT,
    IN p_mentor_id INT,
    OUT p_result VARCHAR(100)
)
BEGIN
    DECLARE v_current_stage INT DEFAULT 0;
    DECLARE v_mentor_assigned INT DEFAULT 0;
    DECLARE v_project_status VARCHAR(20);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = 'ERROR: Database error occurred';
    END;
    
    START TRANSACTION;
    
    -- Check if project exists and get current stage
    SELECT current_stage, status INTO v_current_stage, v_project_status
    FROM `projects` WHERE project_id = p_project_id;
    
    IF v_current_stage = 0 THEN
        SET p_result = 'ERROR: Project not found';
        ROLLBACK;
    ELSEIF v_project_status != 'active' THEN
        SET p_result = 'ERROR: Cannot update inactive project';
        ROLLBACK;
    ELSEIF p_new_stage < 1 OR p_new_stage > 6 THEN
        SET p_result = 'ERROR: Invalid stage number';
        ROLLBACK;
    ELSE
        -- Check if mentor is assigned to project
        SELECT COUNT(*) INTO v_mentor_assigned
        FROM `project_mentors` 
        WHERE project_id = p_project_id AND mentor_id = p_mentor_id AND is_active = TRUE;
        
        IF v_mentor_assigned = 0 THEN
            SET p_result = 'ERROR: Mentor not assigned to project';
            ROLLBACK;
        ELSE
            -- Update the project stage
            UPDATE `projects` 
            SET current_stage = p_new_stage,
                updated_at = CURRENT_TIMESTAMP
            WHERE project_id = p_project_id;
            
            -- Log the action
            INSERT INTO `activity_logs` (`user_type`, `user_id`, `action`, `description`, `project_id`)
            VALUES ('mentor', p_mentor_id, 'stage_updated', 
                    CONCAT('Updated project stage to ', p_new_stage), p_project_id);
            
            SET p_result = 'SUCCESS: Project stage updated successfully';
            COMMIT;
        END IF;
    END IF;
END$

-- Procedure for system cleanup (remove old tokens, logs, etc.)
CREATE PROCEDURE `sp_system_cleanup`()
BEGIN
    DECLARE cleanup_count INT DEFAULT 0;
    
    -- Remove expired password reset tokens
    DELETE FROM `password_reset_tokens` 
    WHERE expires_at < CURRENT_TIMESTAMP OR used_at IS NOT NULL;
    SET cleanup_count = ROW_COUNT();
    
    -- Remove old activity logs (keep last 6 months)
    DELETE FROM `activity_logs` 
    WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 6 MONTH);
    SET cleanup_count = cleanup_count + ROW_COUNT();
    
    -- Update mentor last interaction times
    UPDATE `project_mentors` pm
    SET last_interaction = (
        SELECT MAX(created_at) 
        FROM `comments` c 
        WHERE c.project_id = pm.project_id 
        AND c.commenter_type = 'mentor'
        AND c.commenter_id = pm.mentor_id
    )
    WHERE pm.is_active = TRUE;
    
    -- Return cleanup summary
    SELECT 
        cleanup_count as records_cleaned,
        CURRENT_TIMESTAMP as cleanup_time,
        'System cleanup completed successfully' as status;
END$

DELIMITER ;

DELIMITER $

-- Function to calculate project progress percentage
CREATE FUNCTION `fn_calculate_progress`(p_current_stage INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_progress INT DEFAULT 0;
    
    CASE p_current_stage
        WHEN 1 THEN SET v_progress = 10;
        WHEN 2 THEN SET v_progress = 30;
        WHEN 3 THEN SET v_progress = 50;
        WHEN 4 THEN SET v_progress = 60;
        WHEN 5 THEN SET v_progress = 80;
        WHEN 6 THEN SET v_progress = 100;
        ELSE SET v_progress = 0;
    END CASE;
    
    RETURN v_progress;
END$

-- Function to get stage name
CREATE FUNCTION `fn_get_stage_name`(p_stage INT) 
RETURNS VARCHAR(100)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_stage_name VARCHAR(100);
    
    CASE p_stage
        WHEN 1 THEN SET v_stage_name = 'Welcome and Introduction';
        WHEN 2 THEN SET v_stage_name = 'Assessment and Personalization';
        WHEN 3 THEN SET v_stage_name = 'Learning and Development';
        WHEN 4 THEN SET v_stage_name = 'Mentorship and Support';
        WHEN 5 THEN SET v_stage_name = 'Progress Tracking and Feedback';
        WHEN 6 THEN SET v_stage_name = 'Showcase and Integration';
        ELSE SET v_stage_name = 'Unknown Stage';
    END CASE;
    
    RETURN v_stage_name;
END$

-- Function to check if email exists across all user types
CREATE FUNCTION `fn_email_exists`(p_email VARCHAR(255)) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    SELECT (
        (SELECT COUNT(*) FROM `mentors` WHERE email = p_email) +
        (SELECT COUNT(*) FROM `project_innovators` WHERE email = p_email) +
        (SELECT COUNT(*) FROM `password_reset_tokens` WHERE email = p_email AND expires_at > CURRENT_TIMESTAMP)
    ) INTO v_count;
    
    RETURN v_count > 0;
END$

DELIMITER ;

-- Create application user (you should change the password in production)
CREATE USER IF NOT EXISTS 'jhub_app'@'localhost' IDENTIFIED BY 'jhub_secure_password_2024';
CREATE USER IF NOT EXISTS 'jhub_app'@'%' IDENTIFIED BY 'jhub_secure_password_2024';

-- Grant permissions to application user
GRANT SELECT, INSERT, UPDATE, DELETE ON `jhub_project_tracker`.* TO 'jhub_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON `jhub_project_tracker`.* TO 'jhub_app'@'%';
GRANT EXECUTE ON `jhub_project_tracker`.* TO 'jhub_app'@'localhost';
GRANT EXECUTE ON `jhub_project_tracker`.* TO 'jhub_app'@'%';

-- Create read-only user for reporting
CREATE USER IF NOT EXISTS 'jhub_readonly'@'localhost' IDENTIFIED BY 'jhub_readonly_2024';
GRANT SELECT ON `jhub_project_tracker`.* TO 'jhub_readonly'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Analyze tables for better performance
ANALYZE TABLE `admins`, `mentors`, `projects`, `project_innovators`, `project_mentors`, `comments`, `password_reset_tokens`, `activity_logs`, `system_settings`;

-- Verify the setup
SELECT 'Database Setup Complete' as status;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'jhub_project_tracker';
SELECT COUNT(*) as total_projects FROM `projects`;
SELECT COUNT(*) as total_mentors FROM `mentors`;
SELECT COUNT(*) as total_innovators FROM `project_innovators`;
SELECT COUNT(*) as total_comments FROM `comments`;

-- Show sample data
SELECT 'Sample Projects:' as info;
SELECT project_name, current_stage, status, created_at FROM `projects` LIMIT 5;

SELECT 'Sample Mentors:' as info;
SELECT name, area_of_expertise, created_at FROM `mentors` LIMIT 5;

-- Test views
SELECT 'System Dashboard View:' as info;
SELECT * FROM `v_system_dashboard`;

-- Test functions
SELECT 'Function Tests:' as info;
SELECT fn_calculate_progress(3) as stage_3_progress;
SELECT fn_get_stage_name(4) as stage_4_name;

-- Show database size
SELECT 
    table_schema as 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'jhub_project_tracker'
GROUP BY table_schema;

COMMIT;

-- Success message
SELECT 
    'JHUB AFRICA Project Tracker Database Setup Complete!' as message,
    NOW() as completed_at,
    '1.0.0' as schema_version;