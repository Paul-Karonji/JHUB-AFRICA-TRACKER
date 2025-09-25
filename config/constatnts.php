<?php
// config/constants.php
// System Constants

// User Types
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_MENTOR', 'mentor');
define('USER_TYPE_PROJECT', 'project');
define('USER_TYPE_INNOVATOR', 'innovator');
define('USER_TYPE_INVESTOR', 'investor');

// Project Status
define('PROJECT_STATUS_ACTIVE', 'active');
define('PROJECT_STATUS_COMPLETED', 'completed');
define('PROJECT_STATUS_TERMINATED', 'terminated');

// Application Status
define('APPLICATION_STATUS_PENDING', 'pending');
define('APPLICATION_STATUS_APPROVED', 'approved');
define('APPLICATION_STATUS_REJECTED', 'rejected');

// Project Stages
define('STAGE_1', 1); // Project Creation
define('STAGE_2', 2); // Mentorship
define('STAGE_3', 3); // Assessment
define('STAGE_4', 4); // Learning and Development
define('STAGE_5', 5); // Progress Tracking and Feedback
define('STAGE_6', 6); // Showcase and Integration

$STAGE_NAMES = [
    STAGE_1 => 'Project Creation',
    STAGE_2 => 'Mentorship',
    STAGE_3 => 'Assessment',
    STAGE_4 => 'Learning and Development',
    STAGE_5 => 'Progress Tracking and Feedback',
    STAGE_6 => 'Showcase and Integration'
];

$STAGE_DESCRIPTIONS = [
    STAGE_1 => 'Initial project setup and team building',
    STAGE_2 => 'Mentor assignment and initial guidance',
    STAGE_3 => 'Project assessment and evaluation',
    STAGE_4 => 'Skills development and learning objectives',
    STAGE_5 => 'Progress monitoring and feedback collection',
    STAGE_6 => 'Final showcase and ecosystem integration'
];

// Comment Types
define('COMMENT_TYPE_GENERAL', 'general');
define('COMMENT_TYPE_FEEDBACK', 'feedback');
define('COMMENT_TYPE_QUESTION', 'question');
define('COMMENT_TYPE_UPDATE', 'update');

// Notification Types
define('NOTIFY_APPLICATION_APPROVED', 'application_approved');
define('NOTIFY_APPLICATION_REJECTED', 'application_rejected');
define('NOTIFY_MENTOR_ASSIGNED', 'mentor_assigned');
define('NOTIFY_STAGE_UPDATED', 'stage_updated');
define('NOTIFY_SYSTEM_ALERT', 'system_alert');

// Resource Types
define('RESOURCE_TYPE_LINK', 'link');
define('RESOURCE_TYPE_DOCUMENT', 'document');
define('RESOURCE_TYPE_TOOL', 'tool');
define('RESOURCE_TYPE_CONTACT', 'contact');
define('RESOURCE_TYPE_OTHER', 'other');

// Success and Error Messages
define('MSG_SUCCESS_LOGIN', 'Login successful. Welcome back!');
define('MSG_SUCCESS_LOGOUT', 'You have been successfully logged out.');
define('MSG_ERROR_LOGIN', 'Invalid username or password.');
define('MSG_ERROR_ACCESS_DENIED', 'Access denied. You don\'t have permission to view this page.');
define('MSG_ERROR_SESSION_EXPIRED', 'Your session has expired. Please log in again.');
define('MSG_ERROR_INVALID_TOKEN', 'Invalid security token. Please try again.');

// Pagination
define('ITEMS_PER_PAGE', 10);
define('PROJECTS_PER_PAGE', 12);
define('COMMENTS_PER_PAGE', 20);

?>