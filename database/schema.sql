-- ========================================================
-- JHUB AFRICA PROJECT TRACKER - PRODUCTION SCHEMA


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `tracker` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_general_ci;

USE `tracker`;

-- =====================================================
-- DROP TABLES (in correct order for foreign keys)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `email_queue`;
DROP TABLE IF EXISTS `email_notifications`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `learning_objectives`;
DROP TABLE IF EXISTS `project_assessments`;
DROP TABLE IF EXISTS `mentor_resources`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `project_mentors`;
DROP TABLE IF EXISTS `project_innovators`;
DROP TABLE IF EXISTS `project_applications`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `mentors`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABLE: activity_logs
-- =====================================================

CREATE TABLE `activity_logs` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  PRIMARY KEY (`activity_id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: admins
-- =====================================================

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: comments
-- =====================================================

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `commenter_type` enum('admin','mentor','innovator','investor') NOT NULL,
  `commenter_name` varchar(100) NOT NULL,
  `commenter_email` varchar(255) DEFAULT NULL,
  `commenter_id` int(11) DEFAULT NULL,
  `comment_text` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_edited` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_parent_comment` (`parent_comment_id`),
  KEY `idx_commenter_type` (`commenter_type`),
  KEY `idx_created_date` (`created_at`),
  KEY `idx_project_parent` (`project_id`,`parent_comment_id`),
  KEY `idx_active_comments` (`project_id`,`is_deleted`),
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: email_notifications
-- =====================================================

CREATE TABLE `email_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message_body` text NOT NULL,
  `notification_type` enum('application_approved','application_rejected','mentor_assigned','stage_updated','system_alert') NOT NULL,
  `related_project_id` int(11) DEFAULT NULL,
  `related_application_id` int(11) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`notification_type`),
  KEY `idx_created_date` (`created_at`),
  KEY `fk_email_project` (`related_project_id`),
  KEY `fk_email_application` (`related_application_id`),
  CONSTRAINT `fk_email_application` FOREIGN KEY (`related_application_id`) REFERENCES `project_applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_email_project` FOREIGN KEY (`related_project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: email_queue
-- =====================================================

CREATE TABLE `email_queue` (
  `email_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `email_type` varchar(50) DEFAULT 'general',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `status` enum('pending','sending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: learning_objectives
-- =====================================================

CREATE TABLE `learning_objectives` (
  `objective_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `innovator_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `objective_type` varchar(50) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `target_completion_date` date DEFAULT NULL,
  PRIMARY KEY (`objective_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_mentor_id` (`mentor_id`),
  KEY `idx_innovator_id` (`innovator_id`),
  KEY `idx_completion` (`is_completed`),
  KEY `idx_skill_category` (`skill_category`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_learning_innovator` FOREIGN KEY (`innovator_id`) REFERENCES `project_innovators` (`pi_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_learning_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: login_attempts
-- =====================================================

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `user_type` enum('admin','mentor','project') NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_identifier` (`identifier`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: mentors
-- =====================================================

CREATE TABLE `mentors` (
  `mentor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `area_of_expertise` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `years_experience` int(11) DEFAULT NULL,
  PRIMARY KEY (`mentor_id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_expertise` (`area_of_expertise`),
  KEY `idx_active` (`is_active`),
  KEY `fk_mentor_created_by` (`created_by`),
  CONSTRAINT `fk_mentor_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: mentor_resources
-- =====================================================

CREATE TABLE `mentor_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `mentor_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `resource_type` enum('link','document','tool','contact','other') NOT NULL DEFAULT 'link',
  `resource_url` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stage_applicable` int(11) DEFAULT NULL CHECK (`stage_applicable` between 1 and 6),
  `is_public` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`resource_id`),
  KEY `idx_mentor_id` (`mentor_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_category` (`category`),
  KEY `idx_stage` (`stage_applicable`),
  KEY `idx_type` (`resource_type`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_resource_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resource_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: notifications
-- =====================================================

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','mentor','project') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','success','warning','danger') DEFAULT 'info',
  `action_url` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TABLE: password_reset_tokens
-- =====================================================

CREATE TABLE `password_reset_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `user_type` enum('admin','mentor','project') NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_email_type` (`email`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: projects
-- =====================================================

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `project_email` varchar(255) DEFAULT NULL,
  `project_website` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `profile_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `project_lead_name` varchar(100) NOT NULL,
  `project_lead_email` varchar(255) NOT NULL,
  `current_stage` int(11) NOT NULL DEFAULT 1 CHECK (`current_stage` between 1 and 6),
  `status` enum('active','completed','terminated') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_date` timestamp NULL DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `created_from_application` int(11) DEFAULT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `project_logo` varchar(255) DEFAULT NULL,
  `funding_amount` decimal(15,2) DEFAULT NULL,
  `funding_currency` varchar(3) DEFAULT 'USD',
  `target_market` varchar(255) DEFAULT NULL,
  `business_model` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `unique_profile_name` (`profile_name`),
  KEY `idx_status` (`status`),
  KEY `idx_stage` (`current_stage`),
  KEY `idx_created_date` (`created_at`),
  KEY `idx_lead_email` (`project_lead_email`),
  KEY `idx_status_stage` (`status`,`current_stage`),
  KEY `fk_project_from_application` (`created_from_application`),
  KEY `fk_project_created_by_admin` (`created_by_admin`),
  CONSTRAINT `fk_project_created_by_admin` FOREIGN KEY (`created_by_admin`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_from_application` FOREIGN KEY (`created_from_application`) REFERENCES `project_applications` (`application_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: project_applications
-- =====================================================

CREATE TABLE `project_applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `project_email` varchar(255) DEFAULT NULL,
  `project_website` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `project_lead_name` varchar(100) NOT NULL,
  `project_lead_email` varchar(255) NOT NULL,
  `presentation_file` varchar(255) DEFAULT NULL,
  `profile_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `unique_profile_name` (`profile_name`),
  KEY `idx_status` (`status`),
  KEY `idx_applied_date` (`applied_at`),
  KEY `idx_lead_email` (`project_lead_email`),
  KEY `fk_application_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_application_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: project_assessments
-- =====================================================

CREATE TABLE `project_assessments` (
  `assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `criteria` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`assessment_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_mentor_id` (`mentor_id`),
  KEY `idx_completion` (`is_completed`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_assessment_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: project_innovators
-- =====================================================

CREATE TABLE `project_innovators` (
  `pi_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `level_of_experience` varchar(100) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `added_by_type` enum('project_lead','admin','mentor') NOT NULL,
  `added_by_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`pi_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`),
  KEY `idx_project_email` (`project_id`,`email`),
  CONSTRAINT `fk_pi_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: project_mentors
-- =====================================================

CREATE TABLE `project_mentors` (
  `pm_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by_mentor` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `last_interaction` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`pm_id`),
  UNIQUE KEY `unique_project_mentor` (`project_id`,`mentor_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_mentor_id` (`mentor_id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_assigned_date` (`assigned_at`),
  CONSTRAINT `fk_pm_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: sessions
-- =====================================================

CREATE TABLE `sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_type` enum('admin','mentor','project','innovator') NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `session_data` text DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: system_settings
-- =====================================================

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER $$

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

CREATE TRIGGER `tr_log_stage_change` 
AFTER UPDATE ON `projects` 
FOR EACH ROW 
BEGIN 
    IF NEW.current_stage != OLD.current_stage THEN
        INSERT INTO `activity_logs` (`user_type`, `action`, `description`, `target_id`, `additional_data`)
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

INSERT INTO `admins` (`username`, `password`, `admin_name`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES 
('site_name', 'JHUB AFRICA Project Tracker', 'string', 'Name of the platform', 1),
('site_version', '1.0.0', 'string', 'Current version of the platform', 1),
('max_projects_per_mentor', '10', 'integer', 'Maximum number of projects a mentor can be assigned to', 0),
('project_stages', '6', 'integer', 'Number of stages in the innovation framework', 1),
('enable_public_projects', 'true', 'boolean', 'Whether projects are visible to public/investors', 1),
('default_project_status', 'active', 'string', 'Default status for new projects', 0),
('session_timeout', '3600', 'integer', 'Session timeout in seconds', 0),
('enable_email_notifications', 'true', 'boolean', 'Whether to send email notifications', 0),
('platform_launch_date', '2024-01-01', 'string', 'When the platform was launched', 1),
('contact_email', 'support@jhubafrica.com', 'string', 'Platform contact email', 1),
('smtp_host', 'localhost', 'string', 'SMTP server host', 0),
('smtp_port', '587', 'integer', 'SMTP server port', 0),
('smtp_username', '', 'string', 'SMTP username', 0),
('smtp_password', '', 'string', 'SMTP password', 0),
('upload_max_size', '10485760', 'integer', 'Maximum upload file size in bytes (10MB)', 0);

COMMIT;

-- =====================================================
-- SCHEMA COMPLETE
-- =====================================================
-- Tables: 18
-- Default admin: username = 'admin', password = 'admin123'
-- =====================================================