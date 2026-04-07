# AIFAESA Dashboard — Project Summary & Next Steps

**Date:** April 4, 2026
**Repository:** [github.com/sgclobo/dashboardig](https://github.com/sgclobo/dashboardig)
**Production URL:** https://dashboard.aifaesa.org
**Local dev URL:** http://localhost:8000

---

## What Has Been Done

### 1. Architecture Design

Defined the overall system architecture: a **central API aggregator pattern** where the dashboard subdomain (`dashboard.aifaesa.org`) fetches summarized data from lightweight REST API endpoints hosted on each subdomain, rather than connecting directly to their databases. This approach keeps the system secure, decoupled, and easy to extend.

The domains involved are:

| Subdomain                         | Department          |
| --------------------------------- | ------------------- |
| `seksaunit.aifaesa.org`           | Inspections & Fines |
| `personalia.aifaesa.org`          | Human Resources     |
| `lojistika.aifaesa.org`           | Logistics           |
| `aifaesa.gov.tl`                  | Public Portal       |
| `it.aifaesa.org` _(future)_       | IT                  |
| Additional subdomains _(planned)_ | TBD                 |

---

### 2. Database Schema

Created `database.sql` — a MySQL schema for the production dashboard database (`aifaesa_dashboard`) containing four tables:

- `dashboard_users` — login accounts for dashboard access (admin / viewer roles)
- `data_sources` — registry of all connected subdomains with their API URLs and tokens
- `api_cache` — stores the last fetched JSON response from each subdomain to avoid hammering them on every page load (5-minute TTL in production)
- `access_log` — records who accessed which section and when

---

### 3. Project File Structure

Built a complete native PHP project (no framework) with the following structure:

```
dashboardig/
├── .vscode/
│   └── tasks.json          ← VSCode "Start Local Server" task
├── api/
│   └── section.php         ← JSON endpoint called by dashboard JS
├── assets/
│   ├── css/
│   │   └── dashboard.css   ← All dashboard styles
│   └── js/
│       └── dashboard.js    ← Sidebar toggle, section switching, data loading
├── includes/
│   ├── auth.php            ← Session, login, logout helpers
│   ├── db.php              ← PDO singleton (SQLite locally, MySQL on production)
│   └── fetcher.php         ← API fetcher with cache logic
├── index.php               ← Main dashboard page
├── login.php               ← Login page
├── logout.php
├── config.php              ← Smart loader (auto-detects localhost vs production)
├── config.local.php        ← Local dev settings (SQLite, short cache TTL)
├── router.php              ← PHP built-in server router (static files + PHP routing)
├── start.bat               ← One-click start for Windows
├── start.sh                ← One-click start for macOS / Linux
├── database.sql            ← MySQL schema for production
├── .htaccess               ← Security rules for production Apache
├── .gitignore              ← Excludes credentials and local DB from Git
└── README.md               ← Setup instructions
```

---

### 4. Dashboard UI

Built a responsive dashboard layout with:

- **Dark navy and gold theme** suited to a government monitoring application
- **Toggleable sidebar** — collapses to icon-only on desktop, slides off-screen on mobile; state remembered in `localStorage`
- **Sidebar navigation** with icons for each department: Inspections, Fines, Human Resources, Logistics, IT, Public Portal, Overview, Settings
- **Topbar** with live clock (Dili timezone, UTC+9), Refresh button, and Sign out button
- **Section panels** — one per department, loaded lazily via AJAX on first click (no full page reload)
- **Stat cards** — auto-rendered from JSON keys returned by each subdomain's API
- **Overview panel** — shows connection status for all sources at a glance
- **Settings panel** — visible to admin role only

---

### 5. Authentication

Implemented a secure session-based login system:

- Passwords stored as bcrypt hashes (never plain text)
- Session cookie is `httponly` and `secure` (HTTPS-only on production, relaxed on localhost)
- `session_regenerate_id()` called on login to prevent session fixation
- `require_login()` guard on all protected pages
- Admin vs. viewer roles control access to the Settings panel

---

### 6. Local Development Environment

Configured the project to run **without XAMPP or any external server**:

- Uses **PHP's built-in development server** (`php -S localhost:8000 router.php`)
- Uses **SQLite** as the local database — created automatically on first run, no MySQL installation needed
- `config.php` auto-detects localhost and switches to SQLite settings via `config.local.php`
- `db.php` includes an auto-migration function that creates all tables and seeds default data on first run
- `start.bat` (Windows) and `start.sh` (macOS/Linux) start the server with one click and open the browser automatically
- Default local login: `admin@aifaesa.org` / `Admin@1234`

---

### 7. Security Measures in Place

| Measure               | Implementation                                                                     |
| --------------------- | ---------------------------------------------------------------------------------- |
| Credential protection | `config.php` and `config.local.php` excluded from Git via `.gitignore`             |
| Direct file access    | `.htaccess` blocks `/includes/`, `config.php`, `database.sql`                      |
| SQL injection         | All queries use PDO prepared statements                                            |
| XSS                   | All output uses `htmlspecialchars()`                                               |
| CSRF (basic)          | Login form is POST-only; session validated on every request                        |
| Security headers      | `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection` set in `.htaccess` |
| API authentication    | Each subdomain API uses a shared secret token (`X-Api-Token` header)               |

---

## Next Steps

### Step 1 — Commit Current Work to GitHub

Before moving forward, save the current working state:

```bash
git add .
git commit -m "Working dashboard layout with auth, sidebar, and local SQLite dev env"
git push origin main
```

---

### Step 2 — Build API Endpoints on Each Subdomain

Each subdomain needs a small file at `/api/stats.php` that returns a JSON summary of its data. The dashboard fetches this file using the shared secret token.

**Template for each subdomain:**

```php
<?php
define('API_TOKEN', 'your-shared-secret-token');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://dashboard.aifaesa.org');

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if (!hash_equals(API_TOKEN, $token)) {
    http_response_code(401);
    die('{"error":"Unauthorized"}');
}

$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');

echo json_encode([
    'your_metric' => $pdo->query("SELECT COUNT(*) FROM your_table")->fetchColumn(),
]);
```

**Priority order:**

| Subdomain                | File to create   | Key metrics to return                                          |
| ------------------------ | ---------------- | -------------------------------------------------------------- |
| `seksaunit.aifaesa.org`  | `/api/stats.php` | Inspections this month, pending inspections, total inspections |
| `seksaunit.aifaesa.org`  | `/api/fines.php` | Fines issued, fines pending, total value                       |
| `personalia.aifaesa.org` | `/api/stats.php` | Total staff, active contracts, pending requests                |
| `lojistika.aifaesa.org`  | `/api/stats.php` | Assets tracked, pending deliveries, fleet status               |

---

### Step 3 — Generate API Tokens

Replace the placeholder tokens (`CHANGE_TOKEN_1` etc.) in the `data_sources` table with real random tokens. Run this once per token:

```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

Update both the `data_sources` table on the dashboard and the `define('API_TOKEN', ...)` constant on each subdomain.

---

### Step 4 — Deploy to Hostinger

1. Upload all files to `dashboard.aifaesa.org` public_html via FTP or Git pull
2. Run `database.sql` in Hostinger's phpMyAdmin to create the production MySQL database
3. Edit `config.php` with the correct Hostinger MySQL credentials
4. Change the default admin password:
   ```bash
   php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
   ```
   Then run:
   ```sql
   UPDATE dashboard_users SET password = '...' WHERE email = 'admin@aifaesa.org';
   ```
5. Uncomment the HTTPS redirect lines in `.htaccess`

---

### Step 5 — Add Charts and Tables to Each Section

Currently each section panel renders generic stat cards from whatever keys the API returns. The next enhancement is adding visual charts and data tables per department. Suggested tools (no npm/Node required — CDN links only):

- **Chart.js** — bar, line, and doughnut charts for trends
- **DataTables** — sortable, searchable tables for inspection or fine records

---

### Step 6 — Add New Departments

When a new subdomain is created, adding it to the dashboard takes two steps:

1. Create `/api/stats.php` on the new subdomain
2. Insert one row into the `data_sources` table:

```sql
INSERT INTO data_sources (slug, label, icon, api_url, api_token, tab_order)
VALUES ('finance', 'Finance', 'circle', 'https://finance.aifaesa.org/api/stats.php', 'TOKEN', 7);
```

The sidebar entry and content panel are generated automatically — no PHP or HTML changes needed.

---

### Step 7 — Settings UI

The Settings panel currently shows a placeholder. Planned features:

- Add / edit / deactivate data sources from the browser (no SQL needed)
- Manage dashboard users (create viewer accounts for department heads)
- Configure cache TTL per source
- View the access log

---

## Reference

| Item                    | Value                                      |
| ----------------------- | ------------------------------------------ |
| Default admin email     | `admin@aifaesa.org`                        |
| Default admin password  | `Admin@1234` (change on first deploy)      |
| Local server command    | `php -S localhost:8000 router.php`         |
| Local database location | `local_dev/database.sqlite` (auto-created) |
| Production database     | `aifaesa_dashboard` (MySQL on Hostinger)   |
| Cache TTL (production)  | 5 minutes                                  |
| Cache TTL (local dev)   | 30 seconds                                 |
| Timezone                | Asia/Dili (UTC+9)                          |
