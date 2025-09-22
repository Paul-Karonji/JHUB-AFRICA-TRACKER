# API Overview

The JHUB AFRICA Project Tracker exposes a RESTful API via `api/index.php`.

## Authentication

- `POST /api/auth/login` — authenticate admins, mentors, or projects.
- `POST /api/auth/logout` — destroy current session.
- `GET /api/auth/me` — returns the authenticated user profile.

## Projects

- `GET /api/projects` — list projects (filter by status, stage, search).
- `POST /api/projects` — create a new project.
- `GET /api/projects/{id}` — retrieve project details with mentors + team.
- `POST /api/projects/{id}/team` — add innovator (project owners only).
- `DELETE /api/projects/{id}/team/{innovatorId}` — remove innovator.
- `POST /api/projects/{id}/mentors` — mentor self-assignment.
- `DELETE /api/projects/{id}/terminate` — admin termination.

## Mentors

- `GET /api/mentors` — admin-only mentor listing.
- `POST /api/mentors` — admin mentor registration.
- `GET /api/mentors/{id}/projects` — mentor assignments.

## Ratings

- `GET /api/ratings?project_id=ID` — rating history.
- `POST /api/ratings` — mentor rating update.
- `GET /api/ratings/{id}/timeline` — project timeline.

## Comments

- `GET /api/comments?project_id=ID` — threaded comments.
- `POST /api/comments` — create comment.
- `PUT /api/comments/{id}` — edit comment.
- `DELETE /api/comments/{id}` — delete comment.

## Notifications

- `GET /api/notifications` — list notifications for current user.
- `POST /api/notifications?action=mark-read` — mark notification as read.
- `POST /api/notifications?action=mark-all-read` — mark all as read.
- `POST /api/notifications` — admin broadcast notification.
