# Developer Notes

## Architecture

- **Bootstrap**: `includes/init.php` loads configuration, autoloading, helpers, and session middleware.
- **Domain Services**: Located in `classes/` (`Project`, `Rating`, `Comment`, `Mentor`, `Notification`, `Auth`, `Database`, `Validator`).
- **API**: Single entry `api/index.php` with router + dedicated files under `api/*` for granular endpoints.
- **UI**: Public pages in `/public`, role dashboards in `/dashboards`, shared chrome in `/partials`.

## Coding Conventions

- Namespaces omitted for simplicity; rely on autoloader in `init.php`.
- Use `Database::getInstance()` for DB access; transactions handled within service methods.
- Validation centralized via `Validator` class.

## Adding Features

1. Create service methods in `classes/*.php`.
2. Expose via router handler or dedicated `api/...` endpoint.
3. Update dashboards/public pages as necessary.
4. Add tests under `/tests` (PHPUnit scaffolding recommended).

## Logging

- Use `logActivity($level, $message, $context)` for audit trail (`logs/app.log`).

## Notifications

- `Notification::create()` persists notifications; consumers pull via API or dashboards.
