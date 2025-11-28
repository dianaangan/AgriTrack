# AgriTrack

AgriTrack is a PHP/MySQL web app that helps farmers and administrators manage farm inventory, generate reports, and oversee farmer accounts. The project now has clearly separated portals (`/farmer` and `/admin`) to keep responsibilities clean.

## Features

- **Farmer Portal**
  - Dashboard of key stats
  - CRUD inventory with image uploads and thumbnails
  - Minimalist reports view with category/status breakdowns
  - Account settings and profile editing
  - Manage stock shortcuts and success alerts

- **Admin Portal**
  - Dedicated login + landing page
  - Dashboard with platform metrics
  - Farmer management (search, sort, edit, delete)
  - Global inventory view with filters
  - Platform-wide reports (top farmers, alerts)

## Tech Stack

- PHP 8 / XAMPP
- MySQL (PDO)
- HTML/CSS (custom, Tailwind-inspired)
- Vanilla JavaScript for search, UI polish

## Project Structure

```
AgriTrack/
├── farmer/        # Farmer-facing pages (landing, login, inventory, etc.)
├── admin/         # Admin portal pages
├── css/           # Shared stylesheets
├── includes/      # Reusable PHP helpers (db, farmer/admin functions)
├── config/        # Database connection + schema bootstrap
├── uploads/       # Product images (created at runtime)
└── index.php      # Redirects to farmer/landing.php
```

## Getting Started

1. **Clone the repo**
   ```bash
   git clone https://github.com/dianaangan/AgriTrack.git
   cd AgriTrack
   ```

2. **Configure the database**
   - Update `config/database.php` with your MySQL credentials.
   - Visit `farmer/test_connection.php` once; it initializes required tables automatically.

3. **Create upload directory**
   ```bash
   mkdir -p uploads/products
   ```
   Ensure the web server user can write to this folder.

4. **Run with XAMPP**
   - Place the project under `htdocs`.
   - Start Apache & MySQL.
   - Visit `http://localhost/AgriTrack/farmer/landing.php`.

## Admin Access

Seed an admin account directly in the `admins` table or via SQL:
```sql
INSERT INTO admins (firstName, lastName, email, password)
VALUES ('Admin', 'User', 'admin@example.com', PASSWORD('yourpassword'));
```
Then log in at `http://localhost/AgriTrack/admin/admin_login.php`.

## Notes

- Images are stored on disk; the database only keeps relative paths (`uploads/products/...`). Include `uploads/` in backups or provide sample images if sharing the repo.
- Cache-busting (`?v=2`) keeps the shared `favicon.svg` in sync across portals.
- If you restructure URLs, update the `index.php` redirect and any hard-coded links between portals.

## Contributing

1. Fork + create feature branch.
2. Run existing pages (farmer & admin) to ensure no regressions.
3. Open a PR with screenshots for UI changes.

Enjoy building with AgriTrack!

