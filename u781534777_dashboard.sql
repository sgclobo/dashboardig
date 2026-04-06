-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Waktu pembuatan: 07 Apr 2026 pada 06.20
-- Versi server: 11.8.6-MariaDB-log
-- Versi PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u781534777_dashboard`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `access_log`
--

CREATE TABLE `access_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `accessed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `access_log`
--

INSERT INTO `access_log` (`id`, `user_id`, `section`, `ip`, `accessed_at`) VALUES
(1, 1, 'dashboard', '150.228.169.61', '2026-04-07 05:36:07'),
(2, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:01:48'),
(3, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:18:22'),
(4, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:18:34'),
(5, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:19:16'),
(6, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:19:36'),
(7, 1, 'dashboard', '150.228.169.61', '2026-04-07 06:19:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `api_cache`
--

CREATE TABLE `api_cache` (
  `source_id` int(10) UNSIGNED NOT NULL,
  `payload` mediumtext NOT NULL,
  `fetched_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dashboard_users`
--

CREATE TABLE `dashboard_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','viewer') DEFAULT 'viewer',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `dashboard_users`
--

INSERT INTO `dashboard_users` (`id`, `name`, `email`, `password`, `role`, `last_login`, `created_at`) VALUES
(1, 'Administrator', 'admin@aifaesa.org', '$2y$10$Y4gdIeXQ1DZIgd0bsop/aur59p6g3mi0ZpFCum4ojCwhyLe.du7Dm', 'admin', NULL, '2026-04-07 05:29:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_sources`
--

CREATE TABLE `data_sources` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'circle',
  `api_url` varchar(255) NOT NULL,
  `api_token` varchar(128) NOT NULL,
  `tab_order` tinyint(4) DEFAULT 99,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `data_sources`
--

INSERT INTO `data_sources` (`id`, `slug`, `label`, `icon`, `api_url`, `api_token`, `tab_order`, `active`, `created_at`) VALUES
(1, 'inspections', 'Inspections', 'clipboard-check', 'https://seksaunit.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_1', 1, 1, '2026-04-07 05:29:04'),
(2, 'fines', 'Fines', 'exclamation-circle', 'https://seksaunit.aifaesa.org/api/fines.php', 'CHANGE_TOKEN_1', 2, 1, '2026-04-07 05:29:04'),
(3, 'hr', 'Human Resources', 'users', 'https://personalia.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_2', 3, 1, '2026-04-07 05:29:04'),
(4, 'logistics', 'Logistics', 'truck', 'https://lojistika.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_3', 4, 1, '2026-04-07 05:29:04'),
(5, 'it', 'IT', 'server', 'https://it.aifaesa.org/api/stats.php', 'CHANGE_TOKEN_4', 5, 1, '2026-04-07 05:29:04'),
(6, 'portal', 'S.I.P', 'globe', 'https://aifaesa.gov.tl/api/stats.php', 'CHANGE_TOKEN_5', 6, 1, '2026-04-07 05:29:04');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `access_log`
--
ALTER TABLE `access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `api_cache`
--
ALTER TABLE `api_cache`
  ADD PRIMARY KEY (`source_id`);

--
-- Indeks untuk tabel `dashboard_users`
--
ALTER TABLE `dashboard_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `data_sources`
--
ALTER TABLE `data_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `access_log`
--
ALTER TABLE `access_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `dashboard_users`
--
ALTER TABLE `dashboard_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `data_sources`
--
ALTER TABLE `data_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `access_log`
--
ALTER TABLE `access_log`
  ADD CONSTRAINT `access_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `dashboard_users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `api_cache`
--
ALTER TABLE `api_cache`
  ADD CONSTRAINT `api_cache_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `data_sources` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
