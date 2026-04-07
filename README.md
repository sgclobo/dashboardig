# AIFAESA Dashboard

Central monitoring dashboard for AIFAESA subdomains hosted on Hostinger.

## Structure

```
dashboard/
├── index.php           ← Main dashboard (requires login)
├── login.php           ← Login page
├── logout.php
├── config.php          ← DB credentials & settings (do not commit secrets!)
├── database.sql        ← Run once to create the DB schema
├── .htaccess           ← Security rules
├── includes/
│   ├── db.php          ← PDO singleton
│   ├── auth.php        ← Session / login / logout helpers
│   └── fetcher.php     ← API fetcher with cache
├── api/
│   └── section.php     ← JSON endpoint called by dashboard JS
└── assets/
    ├── css/dashboard.css
    └── js/dashboard.js
```

## Setup

### 1. Database
```sql
-- In Hostinger's phpMyAdmin or via SSH MySQL client:
mysql -u root -p < database.sql
```

### 2. Config
Edit `config.php` — set your DB credentials.

### 3. Default login
- Email: `admin@aifaesa.org`
- Password: `Admin@1234` ← **Change this immediately!**

To update the password:
```sql
UPDATE dashboard_users
SET password = '$2y$12$...'   -- generate with: php -r "echo password_hash('NewPass', PASSWORD_BCRYPT);"
WHERE email = 'admin@aifaesa.org';
```

### 4. API tokens
In `database.sql`, replace `CHANGE_TOKEN_1` etc. with long random strings:
```bash
php -r "echo bin2hex(random_bytes(32));"
```
Use the **same token** in the corresponding subdomain's `api/stats.php`.

### 5. Adding a new department/subdomain
```sql
INSERT INTO data_sources (slug, label, icon, api_url, api_token, tab_order)
VALUES ('finance', 'Finance', 'circle', 'https://finance.aifaesa.org/api/stats.php', 'YOUR_TOKEN', 7);
```
The sidebar and panel are generated automatically — no PHP changes needed.

### 6. Subdomain API endpoint (on each subdomain)
Each subdomain needs `/api/stats.php`:
```php
<?php
define('API_TOKEN', 'same-token-as-in-data_sources-table');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://dashboard.aifaesa.org');
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if (!hash_equals(API_TOKEN, $token)) { http_response_code(401); die('{"error":"Unauthorized"}'); }
$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');
echo json_encode([
    'example_metric' => $pdo->query("SELECT COUNT(*) FROM your_table")->fetchColumn(),
]);
```

## GitHub
```bash
git add .
git commit -m "Initial dashboard layout"
git push origin main
```
Remember: never commit `config.php` with real credentials. Add it to `.gitignore`.
