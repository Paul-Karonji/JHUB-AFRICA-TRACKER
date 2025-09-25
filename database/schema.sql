-- JHUB AFRICA PROJECT TRACKER DATABASE
-- Database Name: tracker
-- Created for complete system build

CREATE DATABASE IF NOT EXISTS `tracker` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `tracker`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop tables in correct order (respecting foreign key constraints)
DROP TABLE IF EXISTS `email_notifications`;
DROP TABLE IF EXISTS `learning_objectives`;
DROP TABLE IF EXISTS `project_assessments`;
DROP TABLE IF EXISTS `mentor_resources`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `project_mentors`;
DROP TABLE IF EXISTS `project_innovators`;
DROP TABLE IF EXISTS `project_applications`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `mentors`;
DROP TABLE IF EXISTS `admins`;

-- =====================================================
-- CORE USER TABLES
-- =====================================================

-- Admins Table
CREATE TABLE `admins` (
    `admin_id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `admin_name` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT NULL,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `login_attempts` INT DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `unique_username` (`username`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mentors Table
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

-- =====================================================
-- PROJECT SYSTEM TABLES
-- =====================================================

-- Project Applications Table (NEW)
CREATE TABLE `project_applications` (
    `application_id` INT NOT NULL AUTO_INCREMENT,
    `project_name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `project_email` VARCHAR(255) DEFAULT NULL,
    `project_website` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `project_lead_name` VARCHAR(100) NOT NULL,
    `project_lead_email` VARCHAR(255) NOT NULL,
    `presentation_file` VARCHAR(255) DEFAULT NULL,
    `profile_name` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (`application_id`),
    UNIQUE KEY `unique_profile_name` (`profile_name`),
    KEY `idx_status` (`status`),
    KEY `idx_applied_date` (`applied_at`),
    KEY `idx_lead_email` (`project_lead_email`),
    CONSTRAINT `fk_application_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects Table (Updated)
CREATE TABLE `projects` (
    `project_id` INT NOT NULL AUTO_INCREMENT,
    `project_name` VARCHAR(255) NOT NULL,
    `date` DATE NOT NULL,
    `project_email` VARCHAR(255) DEFAULT NULL,
    `project_website` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `profile_name` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `project_lead_name` VARCHAR(100) NOT NULL,
    `project_lead_email` VARCHAR(255) NOT NULL,
    `current_stage` INT NOT NULL DEFAULT 1 CHECK (`current_stage` BETWEEN 1 AND 6),
    `status` ENUM('active','completed','terminated') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completion_date` TIMESTAMP NULL DEFAULT NULL,
    `termination_reason` TEXT DEFAULT NULL,
    `created_from_application` INT DEFAULT NULL,
    `created_by_admin` INT DEFAULT NULL,
    `project_logo` VARCHAR(255) DEFAULT NULL,
    `funding_amount` DECIMAL(15,2) DEFAULT NULL,
    `funding_currency` VARCHAR(3) DEFAULT 'USD',
    `target_market` VARCHAR(255) DEFAULT NULL,
    `business_model` TEXT DEFAULT NULL,
    PRIMARY KEY (`project_id`),
    UNIQUE KEY `unique_profile_name` (`profile_name`),
    KEY `idx_status` (`status`),
    KEY `idx_stage` (`current_stage`),
    KEY `idx_created_date` (`created_at`),
    KEY `idx_lead_email` (`project_lead_email`),
    KEY `idx_status_stage` (`status`, `current_stage`),
    CONSTRAINT `fk_project_from_application` FOREIGN KEY (`created_from_application`) REFERENCES `project_applications` (`application_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_project_created_by_admin` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Innovators Table
CREATE TABLE `project_innovators` (
    `pi_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `role` VARCHAR(100) NOT NULL,
    `level_of_experience` VARCHAR(100) DEFAULT NULL,
    `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `added_by_type` ENUM('project_lead','admin','mentor') NOT NULL,
    `added_by_id` INT DEFAULT NULL,
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

-- Project Mentors Table
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

-- =====================================================
-- ENHANCED FEATURES TABLES
-- =====================================================

-- Mentor Resources Table (NEW)
CREATE TABLE `mentor_resources` (
    `resource_id` INT NOT NULL AUTO_INCREMENT,
    `mentor_id` INT NOT NULL,
    `project_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `resource_type` ENUM('link','document','tool','contact','other') NOT NULL DEFAULT 'link',
    `resource_url` TEXT DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `stage_applicable` INT DEFAULT NULL CHECK (`stage_applicable` BETWEEN 1 AND 6),
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`resource_id`),
    KEY `idx_mentor_id` (`mentor_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_category` (`category`),
    KEY `idx_stage` (`stage_applicable`),
    KEY `idx_type` (`resource_type`),
    CONSTRAINT `fk_resource_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resource_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Assessments Table (NEW)
CREATE TABLE `project_assessments` (
    `assessment_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `mentor_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_completed` BOOLEAN DEFAULT FALSE,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `due_date` DATE DEFAULT NULL,
    `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
    PRIMARY KEY (`assessment_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_mentor_id` (`mentor_id`),
    KEY `idx_completion` (`is_completed`),
    KEY `idx_due_date` (`due_date`),
    CONSTRAINT `fk_assessment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_assessment_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Learning Objectives Table (NEW)
CREATE TABLE `learning_objectives` (
    `objective_id` INT NOT NULL AUTO_INCREMENT,
    `project_id` INT NOT NULL,
    `mentor_id` INT NOT NULL,
    `innovator_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_completed` BOOLEAN DEFAULT FALSE,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `due_date` DATE DEFAULT NULL,
    `skill_category` VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (`objective_id`),
    KEY `idx_project_id` (`project_id`),
    KEY `idx_mentor_id` (`mentor_id`),
    KEY `idx_innovator_id` (`innovator_id`),
    KEY `idx_completion` (`is_completed`),
    KEY `idx_skill_category` (`skill_category`),
    CONSTRAINT `fk_learning_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_learning_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_learning_innovator` FOREIGN KEY (`innovator_id`) REFERENCES `project_innovators` (`pi_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- COMMUNICATION & SYSTEM TABLES
-- =====================================================

-- Comments Table (Enhanced)
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

-- Email Notifications Table (NEW)
CREATE TABLE `email_notifications` (
    `notification_id` INT NOT NULL AUTO_INCREMENT,
    `recipient_email` VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(100) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message_body` TEXT NOT NULL,
    `notification_type` ENUM('application_approved','application_rejected','mentor_assigned','stage_updated','system_alert') NOT NULL,
    `related_project_id` INT DEFAULT NULL,
    `related_application_id` INT DEFAULT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    KEY `idx_recipient` (`recipient_email`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`notification_type`),
    KEY `idx_created_date` (`created_at`),
    CONSTRAINT `fk_email_project` FOREIGN KEY (`related_project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_email_application` FOREIGN KEY (`related_application_id`) REFERENCES `project_applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM MANAGEMENT TABLES
-- =====================================================

-- Password Reset Tokens Table
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

-- Activity Logs Table
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

-- System Settings Table
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

-- =====================================================
-- TRIGGERS AND FUNCTIONS
-- =====================================================

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

DELIMITER ;

-- =====================================================
-- INITIAL DATA
-- =====================================================

-- Insert default admin (username: admin, password: admin123)
INSERT INTO `admins` (`username`, `password`, `admin_name`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

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
('contact_email', 'support@jhubafrica.com', 'string', 'Platform contact email', TRUE),
('smtp_host', 'localhost', 'string', 'SMTP server host', FALSE),
('smtp_port', '587', 'integer', 'SMTP server port', FALSE),
('smtp_username', '', 'string', 'SMTP username', FALSE),
('smtp_password', '', 'string', 'SMTP password', FALSE),
('upload_max_size', '10485760', 'integer', 'Maximum upload file size in bytes (10MB)', FALSE);

COMMIT;

-- Success message
SELECT 'JHUB AFRICA Project Tracker Database "tracker" Setup Complete!' as message;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'tracker';