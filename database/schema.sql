-- JHUB AFRICA Project Tracker Schema

CREATE DATABASE IF NOT EXISTS jhub_africa_tracker
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE jhub_africa_tracker;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS project_mentors;
DROP TABLE IF EXISTS project_innovators;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS mentors;
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT NOT NULL,
    expertise VARCHAR(160) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    last_login TIMESTAMP NULL,
    phone VARCHAR(30) NULL,
    linkedin_url VARCHAR(255) NULL,
    years_experience INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_mentors_admin FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    date DATE NOT NULL,
    email VARCHAR(180) NULL,
    website VARCHAR(255) NULL,
    profile_name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    current_stage INT NOT NULL DEFAULT 1,
    current_percentage INT NOT NULL DEFAULT 10,
    status ENUM('active','completed','terminated','draft') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    terminated_at TIMESTAMP NULL,
    terminated_by INT NULL,
    termination_reason TEXT NULL,
    CONSTRAINT fk_projects_admin FOREIGN KEY (terminated_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE project_innovators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    role VARCHAR(120) NOT NULL,
    experience_level VARCHAR(120) NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by_innovator_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    CONSTRAINT fk_innovators_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE project_mentors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    mentor_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    self_assigned TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    removed_at TIMESTAMP NULL,
    CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_pm_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
    UNIQUE KEY uq_project_mentor (project_id, mentor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    mentor_id INT NOT NULL,
    stage INT NOT NULL,
    percentage INT NOT NULL,
    previous_stage INT NULL,
    previous_percentage INT NULL,
    notes TEXT NULL,
    rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ratings_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_mentor FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_type ENUM('admin','mentor','project','public') NOT NULL,
    user_id INT NULL,
    commenter_name VARCHAR(120) NULL,
    comment_text TEXT NOT NULL,
    parent_id INT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('admin','mentor','project','public') NOT NULL,
    recipient_id INT NULL,
    notification_type VARCHAR(80) DEFAULT 'general',
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    related_project_id INT NULL,
    related_entity_type VARCHAR(60) NULL,
    related_entity_id INT NULL,
    metadata JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_project FOREIGN KEY (related_project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$
CREATE PROCEDURE UpdateProjectRating(
    IN p_project_id INT,
    IN p_mentor_id INT,
    IN p_stage INT,
    IN p_percentage INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_previous_stage INT DEFAULT NULL;
    DECLARE v_previous_percentage INT DEFAULT NULL;

    SELECT current_stage, current_percentage
      INTO v_previous_stage, v_previous_percentage
      FROM projects
     WHERE id = p_project_id
     FOR UPDATE;

    INSERT INTO ratings (
        project_id,
        mentor_id,
        stage,
        percentage,
        previous_stage,
        previous_percentage,
        notes,
        rated_at
    ) VALUES (
        p_project_id,
        p_mentor_id,
        p_stage,
        p_percentage,
        v_previous_stage,
        v_previous_percentage,
        p_notes,
        NOW()
    );

    UPDATE projects
       SET current_stage = p_stage,
           current_percentage = p_percentage,
           updated_at = NOW()
     WHERE id = p_project_id;
END $$
DELIMITER ;
