# Installation Guide

1. **Clone the repository**
   ```bash
   git clone https://example.com/jhub-africa-tracker.git
   cd jhub-africa-tracker
   ```

2. **Install dependencies**
   - PHP 8.1+
   - MySQL 8+
   - Composer (optional for future packages)

3. **Database setup**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p jhub_africa_tracker < database/seed_data.sql
   ```

4. **Configure environment**
   - Update `config/database.php` with database credentials.
   - Adjust `config/app.php` `BASE_URL` to match deployment path.

5. **Web server**
   - Point Apache/Nginx document root to the project directory.
   - Ensure `/logs` and `/assets/uploads` are writable by the web server user.

6. **Default credentials**
   - Admin username/password are seeded via `database/seed_data.sql`.
   - After login, update credentials via the admin dashboard.
