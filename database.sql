-- ============================================================
--  AIFAESA Dashboard — Central Database
--  Run this on dashboard.aifaesa.org's MySQL server
--  Database: aifaesa_dashboard
-- ============================================================

-- Database must already exist on Hostinger (created via control panel)
-- Run this script against: u781534777_dashboard
CREATE DATABASE IF NOT EXISTS u781534777_dashboard
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE u781534777_dashboard;

-- ------------------------------------------------------------
-- Dashboard users (login to the dashboard itself)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dashboard_users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,             -- bcrypt
    role        ENUM('admin','viewer') DEFAULT 'viewer',
    last_login  DATETIME      NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert a default admin user (password: Admin@1234 — change immediately!)
INSERT INTO dashboard_users (name, email, password, role) VALUES
('Administrator', 'admin@aifaesa.org',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ------------------------------------------------------------
-- Data sources (one row per subdomain/app)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS data_sources (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(50)   NOT NULL UNIQUE,       -- e.g. 'hr', 'logistics'
    label       VARCHAR(100)  NOT NULL,              -- display name
    icon        VARCHAR(50)   DEFAULT 'circle',      -- icon name
    api_url     VARCHAR(255)  NOT NULL,              -- https://personalia.aifaesa.org/api/stats.php
    api_token   VARCHAR(128)  NOT NULL,              -- shared secret
    tab_order   TINYINT       DEFAULT 99,
    active      TINYINT(1)    DEFAULT 1,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pre-populate with your known subdomains
INSERT INTO data_sources (slug, label, icon, api_url, api_token, tab_order) VALUES
('inspections', 'Inspections',    'clipboard-check',   'https://dashboard.aifaesa.org/api/inspections.php', 'AIFAESA_INSP_API_2026',    1),
('fines',       'Fines',          'exclamation-circle', 'https://dashboard.aifaesa.org/api/fines.php',        'AIFAESA_KOIMAS_API_2026',  2),
('hr',          'Human Resources','users',             'https://personalia.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_2', 3),
('logistics',   'Logistics',      'truck',             'https://lojistika.aifaesa.org/api/stats.php',  'CHANGE_TOKEN_3', 4),
('it',          'IT',             'server',            'https://it.aifaesa.org/api/stats.php',         'CHANGE_TOKEN_4', 5),
('portal',      'S.I.P',          'globe',             'https://aifaesa.gov.tl/api/stats.php',         'CHANGE_TOKEN_5', 6);

-- ------------------------------------------------------------
-- Cached API responses (refresh every N minutes)
-- Avoids hammering every subdomain on each page load
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_cache (
    source_id   INT UNSIGNED NOT NULL,
    payload     MEDIUMTEXT   NOT NULL,               -- raw JSON from the API
    fetched_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (source_id),
    FOREIGN KEY (source_id) REFERENCES data_sources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Audit log (who viewed what, when)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS access_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    section     VARCHAR(50)  NULL,
    ip          VARCHAR(45)  NOT NULL,
    accessed_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES dashboard_users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
