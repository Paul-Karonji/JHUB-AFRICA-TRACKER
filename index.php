<?php
// index.php
// JHUB AFRICA Landing Page - Unique Modern Design
require_once 'includes/init.php';

// Get latest projects
$latestProjects = $database->getRows("
    SELECT p.*,
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
    FROM projects p
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 6
");

// Get statistics
$stats = [
    'total_projects' => $database->count('projects', "status = 'active'"),
    'total_mentors' => $database->count('mentors', 'is_active = 1'),
    'total_innovators' => $database->count('project_innovators', 'is_active = 1'),
    'completed_projects' => $database->count('projects', "status = 'completed'")
];

$pageTitle = "Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JHUB AFRICA - Nurturing African Innovations</title>
    <meta name="description" content="JHUB AFRICA is Africa's premier innovation acceleration platform. Join our ecosystem and transform your innovative ideas into market-ready solutions through expert mentorship and structured development.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        :root {
            --jhub-blue: #2c409a;
            --jhub-red: #fd1616;
            --jhub-green: #3fa845;
            --jhub-black: #000000;
            --jhub-dark: #0e015b;
            --jhub-light: #F0F9FF;
            --jhub-gray: #64748B;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--jhub-dark);
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--jhub-green);
            border-radius: 5px;
        }

        /* Navigation - Floating Style */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 1.2rem 0;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            padding: 0.8rem 0;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand img {
            height: 55px;
            transition: height 0.3s;
        }

        .navbar.scrolled .navbar-brand img {
            height: 45px;
        }

        .nav-link {
            color: var(--jhub-dark) !important;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 1.2rem !important;
            position: relative;
            transition: all 0.3s;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--jhub-green);
            transition: all 0.3s;
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 60%;
        }

        .nav-link.active::after {
            width: 60%;
        }

        .btn-nav-cta {
            background: var(--jhub-green) !important;
            color: white !important;
            padding: 0.7rem 1.8rem !important;
            border-radius: 30px !important;
            font-weight: 700;
            margin-left: 1rem;
            box-shadow: 0 4px 15px rgba(63, 168, 69, 0.3);
            transition: all 0.3s;
        }

        .btn-nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(63, 168, 69, 0.4);
        }

        /* Login Dropdown Styling */
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 0.5rem 0;
            margin-top: 1rem;
            min-width: 220px;
        }

        .dropdown-item {
            padding: 0.8rem 1.5rem;
            color: var(--jhub-dark);
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .dropdown-item i {
            color: var(--jhub-green);
            font-size: 1rem;
        }

        .dropdown-item:hover {
            background: var(--jhub-light);
            color: var(--jhub-dark);
            padding-left: 2rem;
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: rgba(0, 0, 0, 0.08);
        }

        .nav-link.dropdown-toggle::after {
            display: none;
        }

        .nav-link i {
            margin-right: 0.3rem;
        }

        /* Hero Section - Asymmetric Design */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: var(--jhub-dark);
            position: relative;
            overflow: hidden;
            padding: 100px 0 80px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 150%;
            background: radial-gradient(circle, rgba(63, 168, 69, 0.15) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 60%;
            height: 100%;
            background: radial-gradient(circle, rgba(253, 22, 22, 0.1) 0%, transparent 70%);
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(30px, 30px) rotate(5deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(63, 168, 69, 0.15);
            border: 2px solid var(--jhub-green);
            color: var(--jhub-green);
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            animation: pulse-border 2s infinite;
        }

        @keyframes pulse-border {
            0%, 100% { border-color: var(--jhub-green); box-shadow: 0 0 0 0 rgba(63, 168, 69, 0.4); }
            50% { border-color: var(--jhub-green); box-shadow: 0 0 0 8px rgba(63, 168, 69, 0); }
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.15;
            color: white;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-title .highlight {
            background: var(--jhub-green);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.25rem;
            line-height: 1.8;
            color: white;
            margin-bottom: 3rem;
        }

        .hero-cta-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: var(--jhub-green);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 10px 40px rgba(63, 168, 69, 0.3);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(63, 168, 69, 0.4);
        }

        .btn-hero-secondary {
            background: var(--jhub-light);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
        }

        .hero-features {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .hero-feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 500;
        }

        .hero-feature-item i {
            color: var(--jhub-green);
            font-size: 1.2rem;
        }

        /* Stats Cards - Floating */
        .stats-floating {
            position: relative;
            margin-top: -80px;
            z-index: 3;
        }

        .stat-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            border-color: var(--jhub-green);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: var(--jhub-blue);
            color: white;
        }

        .stat-card:nth-child(2) .stat-icon {
            background: var(--jhub-green);
            color: white;
        }

        .stat-card:nth-child(3) .stat-icon {
            background: var(--jhub-red);
            color: white;
        }

        .stat-card:nth-child(4) .stat-icon {
            background: var(--jhub-dark);
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: var(--jhub-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--jhub-gray);
            font-weight: 600;
            font-size: 1rem;
        }

        /* About Section - Split Design */
        .about-section {
            padding: 8rem 0;
            background: var(--jhub-light);
            position: relative;
        }

        .about-decorative {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 217, 163, 0.1) 0%, transparent 70%);
            filter: blur(80px);
        }

        .about-decorative:nth-child(1) {
            top: 10%;
            left: -10%;
        }

        .about-decorative:nth-child(2) {
            bottom: 10%;
            right: -10%;
            background: radial-gradient(circle, rgba(44, 64, 154, 0.1) 0%, transparent 70%);
        }

        .section-label {
            display: inline-block;
            background: var(--jhub-green);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1.2;
            color: var(--jhub-black);
            margin-bottom: 2rem;
        }

        .section-description {
            font-size: 1.15rem;
            line-height: 1.8;
            color: var(--jhub-gray);
            margin-bottom: 2.5rem;
        }

        .about-image-wrapper {
            position: relative;
        }

        .about-image {
            border-radius: 30px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 2;
        }

        .about-image-decoration {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100%;
            height: 100%;
            border: 3px solid var(--jhub-green);
            border-radius: 30px;
            z-index: 1;
        }

        .feature-list {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature-list-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .feature-list-item:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-list-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--jhub-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .feature-list-content h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--jhub-black);
            margin-bottom: 0.5rem;
        }

        .feature-list-content p {
            color: var(--jhub-gray);
            margin: 0;
            line-height: 1.6;
        }

        /* Features Grid - Card Style */
        .features-section {
            padding: 8rem 0;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .feature-card {
            background: #ffffff;
            border-radius: 25px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s;
            border: 2px solid transparent;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--jhub-green);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            border-color: rgba(63, 168, 69, 0.2);
        }

        .feature-card-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: var(--jhub-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .feature-card:hover .feature-card-icon {
            transform: rotate(10deg) scale(1.1);
        }

        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--jhub-black);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--jhub-gray);
            line-height: 1.7;
            margin: 0;
        }

        /* How It Works - Timeline */
        .process-section {
            padding: 8rem 0;
            background: var(--jhub-light);
        }

        .timeline {
            position: relative;
            max-width: 900px;
            margin: 4rem auto 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--jhub-green);
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
        }

        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            width: 45%;
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .timeline-content:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            border: 4px solid var(--jhub-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.3rem;
            color: var(--jhub-green);
            z-index: 2;
            box-shadow: 0 5px 20px rgba(0, 217, 163, 0.3);
        }

        .timeline-content h4 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--jhub-black);
            margin-bottom: 1rem;
        }

        .timeline-content p {
            color: var(--jhub-gray);
            line-height: 1.7;
            margin: 0;
        }

        /* Projects Section - Modern Cards */
        .projects-section {
            padding: 8rem 0;
            background: #2b3f99;
            position: relative;
            overflow: hidden;
        }

        .projects-section::before {
            display: none;
        }

        .projects-section .section-label {
            background: rgba(63, 168, 69, 0.2);
            color: var(--jhub-green);
        }

        .projects-section .section-title,
        .projects-section .section-description {
            color: white;
        }

        .projects-section .section-description {
            opacity: 0.8;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
        }

        .project-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            overflow: hidden;
            transition: all 0.4s;
        }

        .project-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--jhub-green);
            box-shadow: 0 20px 60px rgba(63, 168, 69, 0.2);
        }

        .project-header {
            background: rgba(63, 168, 69, 0.2);
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .project-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .project-stage-badge {
            display: inline-block;
            background: var(--jhub-green);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .project-body {
            padding: 2rem;
        }

        .project-description {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .project-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--jhub-gray);
            font-size: 0.9rem;
        }

        .project-meta i {
            color: var(--jhub-green);
        }

        .project-progress {
            margin-bottom: 1.5rem;
        }

        .project-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .progress {
            height: 10px;
            border-radius: 10px;
            background: var(--jhub-light);
        }

        .progress-bar {
            background: var(--jhub-green);
            border-radius: 10px;
            transition: width 1s ease;
        }

        .btn-project {
            width: 100%;
            background: var(--jhub-green);
            color: white;
            padding: 1rem;
            border-radius: 15px;
            border: none;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-project:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(63, 168, 69, 0.3);
        }

        /* CTA Section - Bold */
        .cta-section {
            padding: 8rem 0;
            background: #2b3f99;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            display: none;
        }

        .cta-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: white;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .cta-title .highlight {
            background: var(--jhub-green);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-description {
            font-size: 1.2rem;
            color: white;
            line-height: 1.8;
            margin-bottom: 3rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-cta-large {
            padding: 1.3rem 3.5rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-cta-primary {
            background: var(--jhub-green);
            color: white;
            box-shadow: 0 10px 40px rgba(0, 217, 163, 0.3);
        }

        .btn-cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 50px rgba(0, 217, 163, 0.4);
        }

        .btn-cta-outline {
            background: transparent;
            color: white;
            border: 3px solid white;
        }

        .btn-cta-outline:hover {
            background: white;
            color: var(--jhub-dark);
            transform: translateY(-3px);
        }

        /* Footer - Modern */
        .footer {
            background: white;
            color: white;
            padding: 4rem 0 2rem;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
        }

        .footer-brand img {
            height: 50px;
            margin-bottom: 1.5rem;
        }

        .footer-description {
            color: var(--jhub-gray);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: var(--jhub-light);
            color: var(--jhub-dark);
            border-radius: 12px;
            margin-right: 0.8rem;
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        .social-links a:hover {
            background: var(--jhub-green);
            color: white;
            transform: translateY(-3px);
        }

        .footer-heading {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--jhub-dark);
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 15px;
        }

        .footer-heading::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: var(--jhub-green);
            border-radius: 2px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: var(--jhub-gray);
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .footer-links a:hover {
            color: var(--jhub-green);
            padding-left: 10px;
        }

        .footer-links a i {
            margin-right: 0.5rem;
            font-size: 0.85rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin-top: 3rem;
            padding-top: 2rem;
            text-align: center;
        }

        .footer-bottom p {
            color: var(--jhub-gray);
            margin: 0;
        }

        /* Scroll to Top */
        #scrollTopBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 55px;
            height: 55px;
            background: var(--jhub-green);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 5px 25px rgba(63, 168, 69, 0.4);
            cursor: pointer;
            display: none;
            z-index: 1000;
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        #scrollTopBtn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 35px rgba(63, 168, 69, 0.5);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .cta-title {
                font-size: 2.5rem;
            }

            .timeline::before {
                left: 30px;
            }

            .timeline-item {
                flex-direction: row !important;
                padding-left: 80px;
            }

            .timeline-content {
                width: 100%;
            }

            .timeline-dot {
                left: 30px;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .hero-description {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .stats-floating {
                margin-top: 2rem;
            }

            .features-grid,
            .projects-grid {
                grid-template-columns: 1fr;
            }

            .hero-cta-group {
                flex-direction: column;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/JHUB Africa Logo.png" alt="JHUB AFRICA">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/contact.php">Contact</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> Login
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                            <li><a class="dropdown-item" href="auth/project-login.php"><i class="fas fa-project-diagram"></i> Project Access</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/mentor-login.php"><i class="fas fa-chalkboard-teacher"></i> Mentor Login</a></li>
                            <li><a class="dropdown-item" href="auth/admin-login.php"><i class="fas fa-user-shield"></i> Admin Portal</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-nav-cta" href="applications/submit.php">
                            Apply Now
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-tag">
                        <i class="fas fa-rocket"></i>
                        <span>Africa's Innovation Accelerator</span>
                    </div>
                    <h1 class="hero-title">
                        Transform Your Ideas Into 
                        <span class="highlight">Market-Ready</span> 
                        Innovations
                    </h1>
                    <p class="hero-description">
                        Join Africa's premier innovation ecosystem. Get expert mentorship, structured support, 
                        and connect with a thriving community of innovators building the future.
                    </p>
                    <div class="hero-cta-group">
                        <button class="btn-hero-primary" onclick="window.location.href='applications/submit.php'">
                            <span>Start Your Journey</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <button class="btn-hero-secondary" onclick="window.location.href='public/projects.php'">
                            <span>Explore Projects</span>
                            <i class="fas fa-compass"></i>
                        </button>
                    </div>
                    <div class="hero-features">
                        <div class="hero-feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>100% Free</span>
                        </div>
                        <div class="hero-feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Expert Mentors</span>
                        </div>
                        <div class="hero-feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Proven Framework</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Stats -->
    <section class="stats-floating">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <span class="stat-number"><?php echo $stats['total_projects']; ?>+</span>
                        <div class="stat-label">Active Projects</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <span class="stat-number"><?php echo $stats['total_mentors']; ?>+</span>
                        <div class="stat-label">Expert Mentors</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="stat-number"><?php echo $stats['total_innovators']; ?>+</span>
                        <div class="stat-label">Innovators</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <span class="stat-number"><?php echo $stats['completed_projects']; ?>+</span>
                        <div class="stat-label">Success Stories</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="about-decorative"></div>
        <div class="about-decorative"></div>
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="section-label">Who We Are</span>
                    <h2 class="section-title">
                        Nurturing Africa's Next Generation of Innovators
                    </h2>
                    <p class="section-description">
                        JHUB AFRICA is more than an innovation hub – we're a movement. Based at JKUAT, 
                        we provide the mentorship, resources, and community support needed to transform 
                        groundbreaking ideas into market-ready solutions.
                    </p>
                    <div class="feature-list">
                        <div class="feature-list-item">
                            <div class="feature-list-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="feature-list-content">
                                <h4>Innovation-First Approach</h4>
                                <p>We believe in the power of African innovation to solve local and global challenges.</p>
                            </div>
                        </div>
                        <div class="feature-list-item">
                            <div class="feature-list-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="feature-list-content">
                                <h4>Community-Driven</h4>
                                <p>Join a network of passionate innovators, mentors, and supporters.</p>
                            </div>
                        </div>
                        <div class="feature-list-item">
                            <div class="feature-list-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="feature-list-content">
                                <h4>Results-Oriented</h4>
                                <p>Our structured 6-stage framework ensures measurable progress and outcomes.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-wrapper">
                        <img src="assets/images/backgrounds/Background 1.JPG" alt="JHUB Community" class="about-image">
                        <div class="about-image-decoration"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="text-center">
                <span class="section-label">Why Choose JHUB</span>
                <h2 class="section-title">Everything You Need to Succeed</h2>
                <p class="section-description">
                    We provide comprehensive support to take your innovation from concept to market
                </p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Expert Mentorship</h3>
                    <p>Connect with experienced mentors who provide personalized guidance, industry insights, and practical support throughout your innovation journey.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Structured Framework</h3>
                    <p>Follow our proven 6-stage development process with clear milestones, assessments, and learning objectives to ensure steady progress.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <h3>Thriving Ecosystem</h3>
                    <p>Join a network of innovators, mentors, and investors. Share knowledge, collaborate on projects, and build lasting connections.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3>Resource Library</h3>
                    <p>Access curated educational materials, development tools, templates, and industry contacts to accelerate your innovation development.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Monitor your advancement with built-in assessments, feedback systems, and transparent progress indicators at every stage.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-card-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Investor Network</h3>
                    <p>Showcase your innovation to potential investors, strategic partners, and stakeholders through our public platform.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <div class="text-center">
                <span class="section-label">Our Process</span>
                <h2 class="section-title">Your Journey to Success</h2>
                <p class="section-description">
                    A clear roadmap from idea to market-ready innovation in 6 structured stages
                </p>
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot">1</div>
                    <div class="timeline-content">
                        <h4>Apply & Get Approved</h4>
                        <p>Submit your innovation idea through our simple application form. Our expert team reviews and approves viable projects within 5-7 business days.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot">2</div>
                    <div class="timeline-content">
                        <h4>Match with Mentors</h4>
                        <p>Expert mentors review active projects and join teams they're passionate about. Build your personalized advisory team based on your needs.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot">3</div>
                    <div class="timeline-content">
                        <h4>Develop & Build</h4>
                        <p>Work through structured assessments, complete learning objectives, and build your innovation with continuous mentor guidance and support.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot">4</div>
                    <div class="timeline-content">
                        <h4>Track Progress</h4>
                        <p>Monitor your advancement through our 6-stage framework. Receive regular feedback, iterate on your solution, and hit key milestones.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot">5</div>
                    <div class="timeline-content">
                        <h4>Showcase & Launch</h4>
                        <p>Present your innovation to investors, partners, and the market. Get ready for commercial launch with full ecosystem support.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    <?php if (!empty($latestProjects)): ?>
    <section class="projects-section">
        <div class="container position-relative">
            <div class="text-center">
                <span class="section-label">Featured Innovations</span>
                <h2 class="section-title">Discover Our Latest Projects</h2>
                <p class="section-description">
                    See what innovators in our ecosystem are building right now
                </p>
            </div>
            <div class="projects-grid">
                <?php foreach (array_slice($latestProjects, 0, 3) as $project): ?>
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title"><?php echo e($project['project_name']); ?></h3>
                        <span class="project-stage-badge"><?php echo getStageName($project['current_stage']); ?></span>
                    </div>
                    <div class="project-body">
                        <p class="project-description">
                            <?php echo e(substr($project['description'], 0, 130)); ?>...
                        </p>
                        <div class="project-meta">
                            <span><i class="fas fa-users"></i> <?php echo $project['team_count']; ?> Team Members</span>
                            <span><i class="fas fa-chalkboard-teacher"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                        </div>
                        <div class="project-progress">
                            <div class="project-progress-label">
                                <span>Progress</span>
                                <span><?php echo round(($project['current_stage'] / 6) * 100); ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo ($project['current_stage'] / 6) * 100; ?>%"></div>
                            </div>
                        </div>
                        <button class="btn-project" onclick="window.location.href='public/project-details.php?id=<?php echo $project['project_id']; ?>'">
                            View Project Details
                            <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <button class="btn-hero-primary" onclick="window.location.href='public/projects.php'">
                    <span>View All Projects</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">
                    Ready to Turn Your 
                    <span class="highlight">Big Idea</span> 
                    Into Reality?
                </h2>
                <p class="cta-description">
                    Join hundreds of African innovators who are building impactful solutions. 
                    Apply now and get access to expert mentorship, valuable resources, and a 
                    supportive community dedicated to your success.
                </p>
                <div class="cta-buttons">
                    <button class="btn-cta-large btn-cta-primary" onclick="window.location.href='applications/submit.php'">
                        <i class="fas fa-rocket"></i>
                        <span>Start Your Application</span>
                    </button>
                    <button class="btn-cta-large btn-cta-outline" onclick="window.location.href='public/about.php'">
                        <i class="fas fa-info-circle"></i>
                        <span>Learn More</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <img src="assets/images/logo/JHUB Africa Logo.png" alt="JHUB AFRICA">
                    </div>
                    <p class="footer-description">
                        Africa's premier innovation acceleration platform. Nurturing African innovations 
                        from conception to market success through mentorship, resources, and community support.
                    </p>
                    <div class="social-links">
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-angle-right"></i>Home</a></li>
                        <li><a href="public/projects.php"><i class="fas fa-angle-right"></i>Projects</a></li>
                        <li><a href="public/about.php"><i class="fas fa-angle-right"></i>About Us</a></li>
                        <li><a href="public/contact.php"><i class="fas fa-angle-right"></i>Contact</a></li>
                        <li><a href="applications/submit.php"><i class="fas fa-angle-right"></i>Apply Now</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="footer-heading">For Users</h6>
                    <ul class="footer-links">
                        <li><a href="auth/project-login.php"><i class="fas fa-angle-right"></i>Project Access</a></li>
                        <li><a href="auth/mentor-login.php"><i class="fas fa-angle-right"></i>Mentor Login</a></li>
                        <li><a href="auth/admin-login.php"><i class="fas fa-angle-right"></i>Admin Portal</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="footer-heading">Contact Us</h6>
                    <ul class="footer-links">
                        <li><a href="mailto:info@jhubafrica.com"><i class="fas fa-envelope"></i>info@jhubafrica.com</a></li>
                        <li><a href="tel:+254XXXXXXXXX"><i class="fas fa-phone"></i>+254 XXX XXX XXX</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i>JKUAT, Nairobi, Kenya</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> JHUB AFRICA. All Rights Reserved. Built with passion for African innovation.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top -->
    <button id="scrollTopBtn" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to top functionality
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        const navbar = document.querySelector('.navbar');
        
        window.addEventListener('scroll', () => {
            // Show/hide scroll button
            if (window.pageYOffset > 300) {
                scrollTopBtn.style.display = 'block';
            } else {
                scrollTopBtn.style.display = 'none';
            }

            // Navbar scroll effect
            if (window.pageYOffset > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Animate progress bars when in view
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.width = entry.target.getAttribute('data-width');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.progress-bar').forEach(bar => {
            const width = bar.style.width;
            bar.setAttribute('data-width', width);
            bar.style.width = '0%';
            observer.observe(bar);
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>