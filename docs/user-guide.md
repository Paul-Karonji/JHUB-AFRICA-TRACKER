# User Guide

## Roles

- **Admin**: Manage mentors, oversee projects, terminate inactive initiatives.
- **Mentor**: Join projects, rate progress, leave comments.
- **Project Team**: Manage team members, view ratings, collaborate via comments.
- **Investor/Public**: Browse projects and follow progress.

## Getting Started

1. Visit `/auth/login.php` and pick your role tab.
2. Admins land on the dashboard with project metrics and mentor tools.
3. Mentors see assigned projects and can join new ones.
4. Projects access team management, progress timeline, and comment threads.

## Navigation

- **Dashboards**: Role-specific located under `/dashboards/{role}/`.
- **Public area**: `/public/projects.php` and `/public/project-details.php`.
- **API**: `/api/index.php` powers SPA/third-party integrations.

## Commenting

- Project teams, mentors, admins, and public guests (with name) can post threaded comments per project.

## Ratings

- Only mentors assigned to a project can submit stage updates through the mentor dashboard or `/api/ratings` endpoint.
