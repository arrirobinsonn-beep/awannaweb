-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 14, 2026 at 09:54 AM
-- Server version: 8.0.46-0ubuntu0.24.04.3
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `aggregator_sync_batches`
--

CREATE TABLE `aggregator_sync_batches` (
  `id` bigint UNSIGNED NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `processed_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `matched_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `unmatched_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `phone_mismatch_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `status_updated_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache_spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:32:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"supplier.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:15:\"supplier.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:13:\"supplier.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"supplier.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:11:\"produk.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:13:\"produk.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:11:\"produk.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:13:\"produk.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:14:\"whitelist.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:16:\"whitelist.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:14:\"whitelist.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:16:\"whitelist.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:13:\"spending.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"spending.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:7;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:13:\"spending.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:7;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:15:\"spending.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:16:\"spending.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:9:\"user.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:11:\"user.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:9:\"user.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:11:\"user.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:9:\"role.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:11:\"role.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:9:\"role.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:11:\"role.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:12:\"laporan.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;i:4;i:6;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:14:\"laporan.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:10:\"topup.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:12:\"topup.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:13:\"topup.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:9:\"topup.pay\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:7:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"owner\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:10:\"advertiser\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:6:\"mentor\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:8:\"keuangan\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:2:\"cs\";s:1:\"c\";s:3:\"web\";}}}', 1786612945);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courier_rules`
--

CREATE TABLE `courier_rules` (
  `id` bigint UNSIGNED NOT NULL,
  `sort_order` int UNSIGNED NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courier_rules`
--

INSERT INTO `courier_rules` (`id`, `sort_order`, `payment_method`, `province`, `courier`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'bank_transfer', NULL, 'flix-tf', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(2, 2, 'cod', 'BENGKULU', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(3, 3, 'cod', 'JAMBI', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(4, 4, 'cod', 'LAMPUNG', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(5, 5, 'cod', 'RIAU', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(6, 6, 'cod', 'SUMATRA BARAT', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(7, 7, 'cod', 'SUMATRA SELATAN', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(8, 8, 'cod', 'SUMATRA UTARA', 'flix-idx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(9, 9, 'cod', 'BANTEN', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(10, 10, 'cod', 'DKI JAKARTA', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(11, 11, 'cod', 'JAWA BARAT', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(12, 12, 'cod', 'JAWA TENGAH', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(13, 13, 'cod', 'JAWA TIMUR', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(14, 14, 'cod', 'DI YOGYAKARTA', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(15, 15, 'cod', 'BALI', 'sicepat', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(16, 16, 'cod', 'NANGGROE ACEH DARUSSALAM (NAD)', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(17, 17, 'cod', 'BANGKA BELITUNG', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(18, 18, 'cod', 'KEPULAUAN RIAU', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(19, 19, 'cod', 'GORONTALO', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(20, 20, 'cod', 'KALIMANTAN BARAT', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(21, 21, 'cod', 'KALIMANTAN SELATAN', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(22, 22, 'cod', 'KALIMANTAN TENGAH', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(23, 23, 'cod', 'KALIMANTAN TIMUR', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(24, 24, 'cod', 'KALIMANTAN UTARA', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(25, 25, 'cod', 'SULAWESI BARAT', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(26, 26, 'cod', 'SULAWESI SELATAN', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(27, 27, 'cod', 'SULAWESI TENGAH', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(28, 28, 'cod', 'SULAWESI TENGGARA', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(29, 29, 'cod', 'SULAWESI UTARA', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(30, 30, 'cod', 'MALUKU', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(31, 31, 'cod', 'MALUKU UTARA', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(32, 32, 'cod', 'NUSA TENGGARA BARAT (NTB)', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(33, 33, 'cod', 'NUSA TENGGARA TIMUR (NTT)', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(34, 34, 'cod', 'PAPUA', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(35, 35, 'cod', 'PAPUA BARAT', 'flix-spx', 1, '2026-08-10 22:33:24', '2026-08-10 22:33:24');

-- --------------------------------------------------------

--
-- Table structure for table `cs_assignments`
--

CREATE TABLE `cs_assignments` (
  `id` bigint UNSIGNED NOT NULL,
  `cs_user_id` bigint UNSIGNED NOT NULL,
  `advertiser_id` bigint UNSIGNED NOT NULL,
  `bulan` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cs_assignments`
--

INSERT INTO `cs_assignments` (`id`, `cs_user_id`, `advertiser_id`, `bulan`, `created_by`, `created_at`, `updated_at`) VALUES
(13, 10, 6, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(14, 11, 5, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(15, 12, 4, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(16, 13, 4, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(17, 14, 5, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(18, 15, 7, '2026-08', 2, '2026-08-11 02:13:30', '2026-08-11 02:13:30'),
(19, 10, 5, '2026-07', 2, '2026-08-11 02:13:55', '2026-08-11 02:13:55'),
(20, 11, 4, '2026-07', 2, '2026-08-11 02:13:55', '2026-08-11 02:13:55'),
(21, 12, 5, '2026-07', 2, '2026-08-11 02:13:56', '2026-08-11 02:13:56'),
(22, 13, 4, '2026-07', 2, '2026-08-11 02:13:56', '2026-08-11 02:13:56'),
(23, 14, 7, '2026-07', 2, '2026-08-11 02:13:56', '2026-08-11 02:13:56'),
(24, 15, 6, '2026-07', 2, '2026-08-11 02:13:56', '2026-08-11 02:13:56');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

CREATE TABLE `inventories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventories`
--

INSERT INTO `inventories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Gudang Pusat', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(2, 'GTM', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(3, 'Aurora', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(4, 'GUdang Bojong 2', '2026-08-11 04:33:37', '2026-08-11 04:33:37'),
(5, 'GUDANG KUNINGAN', '2026-08-11 04:50:52', '2026-08-11 04:50:52'),
(6, 'Gudang Konoha', '2026-08-11 04:53:25', '2026-08-11 04:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_06_154247_create_permission_tables', 1),
(5, '2026_07_06_154252_create_inventories_table', 1),
(6, '2026_07_06_154253_create_suppliers_table', 1),
(7, '2026_07_06_154254_create_products_table', 1),
(8, '2026_07_06_154255_create_whitelists_table', 1),
(9, '2026_07_06_154256_create_spending_harians_table', 1),
(10, '2026_07_08_160000_create_top_up_proposals_table', 1),
(11, '2026_07_08_160001_create_top_up_proposal_items_table', 1),
(12, '2026_07_09_160000_add_menunggu_pembayaran_status_to_proposals', 1),
(13, '2026_07_09_160001_add_sisa_saldo_dilaporkan_to_proposal_items', 1),
(14, '2026_07_09_160002_create_notifications_table', 1),
(15, '2026_07_09_160003_add_va_paid_to_top_up_proposals', 1),
(16, '2026_07_09_160004_create_regional_reports_table', 1),
(17, '2026_07_13_103534_add_advertiser_id_to_users_table', 1),
(18, '2026_07_13_105256_create_regional_cs_stats_table', 1),
(19, '2026_07_25_020000_create_order_online_contacts_table', 1),
(20, '2026_08_02_000001_create_cs_assignments_table', 1),
(21, '2026_08_02_100000_create_pengirimans_table', 1),
(22, '2026_08_02_100001_create_pengiriman_status_histories_table', 1),
(23, '2026_08_03_000000_rename_pengiriman_tables_to_english', 1),
(24, '2026_08_03_090000_create_product_variants_table', 1),
(25, '2026_08_03_090001_add_jenis_and_bom_to_product_variants', 1),
(26, '2026_08_03_100000_create_stock_movements_table', 1),
(27, '2026_08_03_100001_create_purchases_table', 1),
(28, '2026_08_03_100002_add_product_id_to_shipments_table', 1),
(29, '2026_08_07_120000_create_order_online_schema', 1),
(30, '2026_08_09_000000_add_meta_account_to_shipping_orders_table', 1),
(31, '2026_08_09_100000_extend_stock_movements_unique_for_packaging', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 6),
(4, 'App\\Models\\User', 7),
(5, 'App\\Models\\User', 8),
(6, 'App\\Models\\User', 9),
(7, 'App\\Models\\User', 10),
(7, 'App\\Models\\User', 11),
(7, 'App\\Models\\User', 12),
(7, 'App\\Models\\User', 13),
(7, 'App\\Models\\User', 14),
(7, 'App\\Models\\User', 15),
(4, 'App\\Models\\User', 16),
(4, 'App\\Models\\User', 17);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `from_user_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_online_contacts`
--

CREATE TABLE `order_online_contacts` (
  `id` bigint UNSIGNED NOT NULL,
  `advertiser_id` bigint UNSIGNED DEFAULT NULL,
  `phone_normalized` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cs_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_online_contacts`
--

INSERT INTO `order_online_contacts` (`id`, `advertiser_id`, `phone_normalized`, `cs_name`, `order_id`, `buyer_name`, `created_at`, `updated_at`) VALUES
(957, NULL, '6285298540122', 'FERI CS', '278247928', 'Deny', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(958, NULL, '6281347412602', 'FERI CS', '278247802', 'Ishak', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(959, NULL, '6285238191235', 'FERI CS', '278247343', 'Ida Nurlatifah', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(960, NULL, '6282316011417', 'MAYANG CS', '278247279', 'Aan Gorden', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(961, NULL, '6281339995858', 'FERI CS', '278246350', 'Hendrik', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(962, NULL, '6285210637202', 'MAYANG CS', '278244830', 'Indah', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(963, NULL, '628118384611', 'MAYANG CS', '278243258', 'Edi fermana', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(964, NULL, '6289514531976', 'MAYANG CS', '278239312', 'As ac / Ansori', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(965, NULL, '6287877946776', 'FERI CS', '278238428', 'M.Bramnas Hede', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(966, NULL, '6282299888266', 'FERI CS', '278213204', 'Ivan Khacank', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(967, NULL, '6285266691436', 'FERI CS', '278212626', 'Ansari', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(968, NULL, '6285206666622', 'MAYANG CS', '278546563', 'Herdyanoor', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(969, NULL, '6287852942168', 'MAYANG CS', '278545355', 'Rusedi', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(970, NULL, '6282162711234', 'MAYANG CS', '278545349', 'Syahlan dongoran', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(971, NULL, '628124210443', 'MAYANG CS', '278544111', 'Ninzar Akib', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(972, NULL, '6282120312095', 'MAYANG CS', '278543828', 'Enung Nuryadi', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(973, NULL, '6281280234030', 'MAYANG CS', '278542356', 'Hery', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(974, NULL, '6281240168093', 'MAYANG CS', '278542100', 'Sarifuddin', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(975, NULL, '6281338626191', 'MAYANG CS', '278542031', 'Widiasa', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(976, NULL, '628126750179', 'MAYANG CS', '278541668', 'Muhammad Fiqhi Fahmi', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(977, NULL, '6282323057173', 'MAYANG CS', '278541034', 'Sapruddin', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(978, NULL, '6281212330515', 'MAYANG CS', '278541008', 'Halik', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(979, NULL, '62811994909', 'MAYANG CS', '278540242', 'Suwandi', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(980, NULL, '62895635388522', 'MAYANG CS', '278540231', 'Muhammad Edi', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(981, NULL, '62882020120982', 'MAYANG CS', '278539865', 'wilda', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(982, NULL, '6283809711486', 'MAYANG CS', '278539400', 'Ahmad Mujahid', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(983, NULL, '6285820668298', 'MAYANG CS', '278537450', 'Ipit', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(984, NULL, '6281251339976', 'MAYANG CS', '278537417', 'Irwan Mpapuarenda', '2026-08-11 04:57:19', '2026-08-11 04:57:19'),
(985, NULL, '6282300025622', 'MAYANG CS', '278521401', 'Sumardi', '2026-08-11 04:57:19', '2026-08-11 04:57:19'),
(986, NULL, '6281354611243', 'MAYANG CS', '278489447', 'Nur indra', '2026-08-11 04:57:19', '2026-08-11 04:57:19'),
(987, NULL, '6285274593663', 'MAYANG CS', '278485864', 'Margo Siswoyo', '2026-08-11 04:57:19', '2026-08-11 04:57:19'),
(4861, 7, '6281239411107', 'MUKLAS CS', '278258635', 'Yusuf', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4862, 7, '6282190294724', 'MUKLAS CS', '278258365', 'Harjo Haidar', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4863, 7, '6282341482882', 'MUKLAS CS', '278257882', 'Uton Wuhangara', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4864, 7, '6282278757988', 'MUKLAS CS', '278257466', 'asep.faiz1972@gmail.com', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4865, 7, '6282276143532', 'MUKLAS CS', '278256404', 'Puti louren hulu', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4866, 7, '6282296753431', 'MUKLAS CS', '278255850', 'Ilham', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4867, 7, '6282382096656', 'MUKLAS CS', '278255832', 'Juninto malau', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4868, 7, '6281326051273', 'MUKLAS CS', '278253982', 'Wagiman', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4869, 7, '6281360012304', 'MUKLAS CS', '278253097', 'MAHFUD', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4870, 7, '6282169080065', 'MUKLAS CS', '278250727', 'Binsar simatupang', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4871, 7, '6281246574705', 'MUKLAS CS', '278249582', 'Ike Langoday', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4872, 7, '6281338397786', 'MUKLAS CS', '278249457', 'Rinnie Naolin', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4873, 7, '6281362608499', 'MUKLAS CS', '278249446', 'Abdal Spd', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4874, 7, '6285249933189', 'MUKLAS CS', '278248996', 'Hajar kadir', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4875, 7, '6282341425163', 'MUKLAS CS', '278248381', 'IGede Swija', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4876, 7, '6285165023391', 'MUKLAS CS', '278248234', 'Maria Bernadeta Kune', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4877, 7, '6281216844909', 'MUKLAS CS', '278248232', 'Mala Hayati', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4878, 7, '6281338289865', 'MUKLAS CS', '278247261', 'Toni Lopes', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4879, 7, '6282157647847', 'MUKLAS CS', '278247083', 'chandra', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4880, 7, '6285276165415', 'MUKLAS CS', '278246492', 'Na Sajan Sajan', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4881, 7, '6281353102150', 'MUKLAS CS', '278245651', 'Nurdin', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4882, 7, '6281364274975', 'MUKLAS CS', '278245024', 'Ihsana', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4883, 7, '6282287064332', 'MUKLAS CS', '278244654', 'Sahatmarulitua Sihombing', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4884, 7, '6281351910081', 'MUKLAS CS', '278244226', 'SITTI Suriati', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4885, 7, '6285864339907', 'MUKLAS CS', '278243489', 'Dede Supriatna', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4886, 7, '6282245178489', 'MUKLAS CS', '278242649', 'Ritha endro', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4887, 7, '6281246430987', 'MUKLAS CS', '278242426', 'LAURENSIUS GEPA MAWAR', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4888, 7, '6285338211926', 'MUKLAS CS', '278242119', 'andry bria', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4889, 7, '6281238997773', 'MUKLAS CS', '278242026', 'Kacmata', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4890, 7, '6287711313361', 'MUKLAS CS', '278240412', 'Mar Lani', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4891, 7, '6281337767274', 'MUKLAS CS', '278237992', 'Mateus Geli', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4892, 7, '6282142145364', 'MUKLAS CS', '278237949', 'Sugirah Mimi', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4893, 7, '6282259170088', 'MUKLAS CS', '278235669', 'Omah Gitar', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4894, 7, '6281246682112', 'MUKLAS CS', '278232067', 'Dominggus Dt', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4895, 7, '6281325264774', 'MUKLAS CS', '278230448', 'Linda Novasari', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4896, 7, '6285339490835', 'MUKLAS CS', '278229302', 'Anderias Taek', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4897, 7, '6282144010952', 'MUKLAS CS', '278228903', 'mone', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4898, 7, '628129755359', 'MUKLAS CS', '278228831', 'Tjen Yulie', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4899, 7, '6285399715457', 'MUKLAS CS', '278221479', 'Esther silamba\'', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4900, 7, '6282312105810', 'MUKLAS CS', '278214877', 'Dhelestin Eflin Meha', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4901, 7, '62811929160', 'MUKLAS CS', '278212576', 'Engkoy Engkoy', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4902, 7, '6285253286700', 'MUKLAS CS', '278211908', 'Darmawati Maria', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4903, 7, '6281270227731', 'MUKLAS CS', '278210456', 'Indra horas', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4904, 7, '6282237102088', 'MUKLAS CS', '278204563', 'John raga', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4905, 7, '6281352757267', 'MUKLAS CS', '278199603', 'Yunita sriyana', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4906, 7, '6281297228399', 'MUKLAS CS', '278199215', 'Ahmad dwi', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4907, 7, '628112881318', 'MUKLAS CS', '278198266', 'Totok afrianto', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4908, 7, '6281382787856', 'MUKLAS CS', '278196690', 'Isak Lele', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4909, 7, '6281371992469', 'MUKLAS CS', '278195798', 'Ramlan mukhtar', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4910, 7, '6281331300638', 'MUKLAS CS', '278191965', 'Marten', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4911, 7, '6281338884004', 'MUKLAS CS', '278191636', 'Eduardus Leky', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4912, 7, '6282236341401', 'MUKLAS CS', '278191118', 'Esa ndolu', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4913, 7, '628123733173', 'MUKLAS CS', '278190314', 'Anci Carbonilla', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4914, 7, '6282277935828', 'MUKLAS CS', '278189481', 'Aaron jalukhu', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4915, 7, '6282144784373', 'MUKLAS CS', '278186919', 'Agung Sumadi', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4916, 7, '6285237002666', 'MUKLAS CS', '278185307', 'Yefrid Edison Bia', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4917, 7, '6281239392646', 'MUKLAS CS', '278184566', 'Selfi Thene', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(4918, 7, '6281246642822', 'MUKLAS CS', '278184290', 'Petrus Paulus', '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(8125, 6, '6281213448489', 'MUKLAS CS', '278644571', 'Djoni usril', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8126, 6, '6282160461078', 'OPUS CS', '278644210', 'Gabe', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8127, 6, '6285656751040', 'OPUS CS', '278644191', 'Kelvin khen', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8128, 6, '6282344631828', 'OPUS CS', '278644029', 'goksu goksu', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8129, 6, '62881374930009', 'OPUS CS', '278642739', 'Erlandy Tumewu', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8130, 6, '6281256679088', 'OPUS CS', '278642208', 'Min khiong', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8131, 6, '6285391960603', 'OPUS CS', '278641773', 'Markus Ipun7', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8132, 6, '6281237414646', 'OPUS CS', '278639212', 'Baselius abi', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8133, 6, '6282197089204', 'OPUS CS', '278635455', 'Abus', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8134, 6, '6282175219665', 'OPUS CS', '278633261', 'Martin', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8135, 6, '6289506957447', 'OPUS CS', '278632185', 'Wauran Rivo', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8136, 6, '6281228652171', 'OPUS CS', '278631002', 'Sukaryadi', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8137, 6, '6282345421655', 'OPUS CS', '278630959', 'Ismail Tellong', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8138, 6, '6281337226094', 'OPUS CS', '278630012', 'Hidayatullah', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8139, 6, '6281343760899', 'OPUS CS', '278628911', 'Rahmat Leo', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8140, 6, '6281364142714', 'OPUS CS', '278625922', 'rolasta', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8141, 6, '6282236889079', 'OPUS CS', '278624401', 'Made sukarda', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8142, 6, '6285238653312', 'OPUS CS', '278623182', 'Andi suwanto', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8143, 6, '6282340227400', 'OPUS CS', '278623036', 'Savely faot', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8144, 6, '62081311197704', 'OPUS CS', '278620395', 'Danil', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8145, 6, '6281363559105', 'OPUS CS', '278620139', 'Nerisamsuar', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8146, 6, '6281326057564', 'OPUS CS', '278619814', 'Terto', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8147, 6, '6281241688906', 'OPUS CS', '278617576', 'Jumahari', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8148, 6, '6281337793933', 'OPUS CS', '278616382', 'Kt edy budiartha', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8149, 6, '6281373514898', 'OPUS CS', '278612083', 'Abdin Tinambunan', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8150, 6, '6281338990710', 'OPUS CS', '278609698', 'Arinny wt', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8151, 6, '6282145167880', 'OPUS CS', '278609185', 'Abdara', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8152, 6, '6282148493677', 'OPUS CS', '278608933', 'TAMRIN', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8153, 6, '6282182633137', 'OPUS CS', '278608214', 'triyudo Hendro Sasongko', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8154, 6, '6282191702884', 'OPUS CS', '278603077', 'Rusli Sjamsuddin', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8155, 6, '6282189665613', 'OPUS CS', '278600438', 'Hamka Daeng makka', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8156, 6, '6281311228649', 'OPUS CS', '278599774', 'Asthin Bere', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8157, 6, '6281243973897', 'OPUS CS', '278599530', 'Ferdi', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8158, 6, '6285274793175', 'OPUS CS', '278598796', 'MANSYUHR', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8159, 6, '628123637992', 'OPUS CS', '278596813', 'Sukandia', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8160, 6, '6282348661270', 'OPUS CS', '278596601', 'Haruna Harsan24', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8161, 6, '6285296617355', 'MUKLAS CS', '278594412', 'Sinaga dragon', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8162, 6, '6285246476105', 'MUKLAS CS', '278594092', 'Sudinz', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8163, 6, '6281250088844', 'MUKLAS CS', '278589209', 'ramli Ramli', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8164, 6, '6282123309959', 'MUKLAS CS', '278585007', 'Rudi Syaputra Putra', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8165, 6, '6281216428977', 'MUKLAS CS', '278584544', 'Ratno sari', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8166, 6, '6282162531429', 'MUKLAS CS', '278582839', 'HERMANSYAH MAKMUR DIAH', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8167, 6, '6281295554675', 'MUKLAS CS', '278582264', 'Ady', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8168, 6, '6281264807036', 'MUKLAS CS', '278581906', 'Holmes malau', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8169, 6, '6285376347449', 'MUKLAS CS', '278579407', 'Gusri', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8170, 6, '6285738760886', 'MUKLAS CS', '278578836', 'Pak Eko', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8171, 6, '628127024695', 'MUKLAS CS', '278578795', 'Gubah Griya', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8172, 6, '6281371714563', 'MUKLAS CS', '278578707', 'Ahmad sahmir', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8173, 6, '6281339889634', 'MUKLAS CS', '278578070', 'frits Laydoma', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8174, 6, '6282180988828', 'MUKLAS CS', '278577353', 'SAIMAN SUTANTO', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8175, 6, '6281238428538', 'MUKLAS CS', '278575620', 'Yosef Kia Lolon', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8176, 6, '6282185467009', 'MUKLAS CS', '278575286', 'Nyomanadi Warna', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8177, 6, '6282358591673', 'MUKLAS CS', '278574415', 'Tajudin,NR', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8178, 6, '6282223432029', 'MUKLAS CS', '278574114', 'Hamsa ode Nue', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8179, 6, '6282191298282', 'MUKLAS CS', '278573171', 'Muhlis Daeng Ngero', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8180, 6, '6281247104573', 'MUKLAS CS', '278572503', 'Ferdy', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8181, 6, '6285738048458', 'MUKLAS CS', '278572430', 'Edwin', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8182, 6, '6281349089142', 'MUKLAS CS', '278572368', 'Desianie', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8183, 6, '6281337629941', 'MUKLAS CS', '278572063', 'Bonefasius woko', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8184, 6, '6287731320662', 'MUKLAS CS', '278571851', 'Novan Jeremy', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8185, 6, '6285241618777', 'MUKLAS CS', '278570260', 'Abd Rahim', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8186, 6, '628138096754', 'MUKLAS CS', '278569476', 'Sardi', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8187, 6, '6282196652113', 'MUKLAS CS', '278567954', 'DECKY PILONGO', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8188, 6, '628124444421', 'MUKLAS CS', '278566809', 'Hamdany Janis', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8189, 6, '6285183171117', 'MUKLAS CS', '278566608', 'Thalia ruchban', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8190, 6, '6282252060308', 'MUKLAS CS', '278565953', 'Razes Hasibuan', '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(8191, 5, '6282181163571', 'PUTRI CS', '278644869', 'Anwar', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8192, 5, '6285241106107', 'ASEP PACE CS', '278644855', 'Yanix', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8193, 5, '6282193999941', 'PUTRI CS', '278644654', 'Susi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8194, 5, '6281260553255', 'ASEP PACE CS', '278643933', 'Sriwahyuni Yuni', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8195, 5, '6285351007100', 'ASEP PACE CS', '278643528', 'may ( dapur mah Baim)', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8196, 5, '6281366329997', 'ASEP PACE CS', '278643493', 'Muslim', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8197, 5, '6285345897661', 'ASEP PACE CS', '278643452', 'ulianty', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8198, 5, '6285367694195', 'ASEP PACE CS', '278643383', 'Erda Damayanti', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8199, 5, '6285805339033', 'PUTRI CS', '278643266', 'Dawes', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8200, 5, '6281378434045', 'ASEP PACE CS', '278643104', 'Rifqi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8201, 5, '6285261045541', 'PUTRI CS', '278642476', 'Budi Hartono', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8202, 5, '6281318833024', 'PUTRI CS', '278642431', 'Sandra', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8203, 5, '6281241636680', 'ASEP PACE CS', '278642319', 'Andi Awaluddin S.HI', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8204, 5, '6287888958481', 'PUTRI CS', '278641436', 'Rudolly Dolly', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8205, 5, '6287795523180', 'PUTRI CS', '278641317', 'Handayani', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8206, 5, '6285177219733', 'PUTRI CS', '278641127', 'Muhammad Zais', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8207, 5, '6281350447736', 'PUTRI CS', '278641094', 'Piter', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8208, 5, '6285271635606', 'PUTRI CS', '278640438', 'Muhammad Nur', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8209, 5, '6282395966894', 'ASEP PACE CS', '278640383', 'Ping Azzam', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8210, 5, '6281310473968', 'PUTRI CS', '278640295', 'Rudy Ch', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8211, 5, '6281265801021', 'ASEP PACE CS', '278640086', 'Liasna maretha br barus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8212, 5, '6282326081310', 'PUTRI CS', '278640002', 'Siti faisah', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8213, 5, '628128137120', 'ASEP PACE CS', '278639933', 'Alex', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8214, 5, '6282124412200', 'ASEP PACE CS', '278639541', 'Giyarso', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8215, 5, '6281267070793', 'ASEP PACE CS', '278639475', 'Donny', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8216, 5, '6285345223455', 'PUTRI CS', '278639405', 'Andi DEDi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8217, 5, '6282236287867', 'ASEP PACE CS', '278638819', 'Wied Paramartha', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8218, 5, '6281270816288', 'PUTRI CS', '278638719', 'Hilim Berita Siregar', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8219, 5, '6283866543662', 'ASEP PACE CS', '278637980', 'Efendi Endi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8220, 5, '6281338176662', 'PUTRI CS', '278637500', 'Alit Triadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8221, 5, '6281364045571', 'ASEP PACE CS', '278637421', 'Togi Manurung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8222, 5, '6281337089396', 'ASEP PACE CS', '278637157', 'Buhari muslim', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8223, 5, '6282154281960', 'PUTRI CS', '278637087', 'Fika', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8224, 5, '6285230532207', 'PUTRI CS', '278636900', 'Andri Eka Ardika', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8225, 5, '6282187880375', 'ASEP PACE CS', '278636636', 'Yuchy', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8226, 5, '6282159030014', 'PUTRI CS', '278635960', 'Ujang bitung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8227, 5, '6285234152310', 'PUTRI CS', '278635902', 'Rajuni Ni', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8228, 5, '6287810700183', 'PUTRI CS', '278635774', 'Lalu NASARAHUM', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8229, 5, '6281770111116', 'ASEP PACE CS', '278635562', 'Robain', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8230, 5, '6285256861666', 'PUTRI CS', '278635495', 'Chiko Jetzet', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8231, 5, '628551115999', 'ASEP PACE CS', '278635343', 'Joky Tejo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8232, 5, '6287860052055', 'ASEP PACE CS', '278634997', 'Ujang dedi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8233, 5, '6283155913018', 'ASEP PACE CS', '278634822', 'Yanne', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8234, 5, '6285756522249', 'ASEP PACE CS', '278634704', 'Ramlah Husain', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8235, 5, '6281366862421', 'PUTRI CS', '278634634', 'Eri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8236, 5, '6285124114668', 'PUTRI CS', '278634405', 'Frans Taka', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8237, 5, '6285845887992', 'PUTRI CS', '278634323', 'Nikodimus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8238, 5, '628979061733', 'ASEP PACE CS', '278633907', 'ADI KUMIS / ADI NIPIN', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8239, 5, '6285143037838', 'PUTRI CS', '278633748', 'Puji Anto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8240, 5, '6282353081399', 'PUTRI CS', '278633538', 'Yuli Kdw', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8241, 5, '6285382646672', 'ASEP PACE CS', '278633513', 'Neri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8242, 5, '6281339523060', 'ASEP PACE CS', '278633180', 'Lily mokolensang', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8243, 5, '6285188162272', 'PUTRI CS', '278633027', 'Joko purnomo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8244, 5, '6282180494106', 'PUTRI CS', '278632708', 'Rohani Simbolon', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8245, 5, '6282162922285', 'ASEP PACE CS', '278631788', 'Elfreda Manurung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8246, 5, '6281316701668', 'ASEP PACE CS', '278631554', 'Mispar', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8247, 5, '6282189463672', 'ASEP PACE CS', '278631543', 'Lukman', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8248, 5, '6285182726871', 'PUTRI CS', '278631012', 'Olke f pontoh', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8249, 5, '6282234031411', 'ASEP PACE CS', '278630273', 'Rudianto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8250, 5, '6285845192737', 'ASEP PACE CS', '278630256', '. Muhammad shafwan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8251, 5, '6285265610801', 'ASEP PACE CS', '278629813', 'Sumarno', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8252, 5, '62817776322', 'PUTRI CS', '278629615', 'Gani Sjahrir Imanto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8253, 5, '6281351185253', 'PUTRI CS', '278629398', 'Hari Yadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8254, 5, '6281246448229', 'PUTRI CS', '278629252', 'Stefaniaaa', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8255, 5, '6282271277949', 'ASEP PACE CS', '278628988', 'Loisa Karimela', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8256, 5, '6282161294194', 'PUTRI CS', '278628976', 'Amirul Hamdi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8257, 5, '6285361749644', 'ASEP PACE CS', '278628582', 'Bari', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8258, 5, '6282384220969', 'PUTRI CS', '278628009', 'Raden Ridwan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8259, 5, '6285341582145', 'PUTRI CS', '278627922', 'Agnes bolung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8260, 5, '6285337881117', 'PUTRI CS', '278627777', 'Zalbiawati', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8261, 5, '628157772603', 'ASEP PACE CS', '278627122', 'Yantie/+62895401554661', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8262, 5, '6282253729498', 'ASEP PACE CS', '278627095', 'Angga', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8263, 5, '6282121338666', 'ASEP PACE CS', '278627009', 'Muhaimin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8264, 5, '6285769088865', 'ASEP PACE CS', '278626967', 'Ramayani', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8265, 5, '6282195003881', 'ASEP PACE CS', '278626837', 'Muhlis Sibua', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8266, 5, '6281339051060', 'ASEP PACE CS', '278626628', 'Rofinus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8267, 5, '6285256787476', 'PUTRI CS', '278626442', 'Yaprihart Naram', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8268, 5, '6281271577739', 'PUTRI CS', '278626272', 'Arman/Jumi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8269, 5, '6281396327522', 'PUTRI CS', '278626262', 'Rully .', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8270, 5, '6285397354844', 'PUTRI CS', '278626115', 'Yosias Djinat', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8271, 5, '6282239939963', 'PUTRI CS', '278625908', 'Grace ntee', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8272, 5, '6282211768820', 'ASEP PACE CS', '278625898', 'Pak Ramu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8273, 5, '62812111197704', 'PUTRI CS', '278625787', 'Rio', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8274, 5, '6281346271965', 'ASEP PACE CS', '278625728', 'Martatik', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8275, 5, '6281999140555', 'PUTRI CS', '278625622', 'Dewa Tirta', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8276, 5, '6281239339988', 'ASEP PACE CS', '278625603', 'Wikartana Nyoman', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8277, 5, '6285376347449', 'PUTRI CS', '278625005', 'Sari', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8278, 5, '6282190346694', 'ASEP PACE CS', '278624874', 'Sulham', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8279, 5, '6281258085186', 'PUTRI CS', '278624787', 'Natalina R', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8280, 5, '6281808260320', 'ASEP PACE CS', '278624291', 'Ajin kia', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8281, 5, '6282210973980', 'ASEP PACE CS', '278623200', 'David', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8282, 5, '6285359635122', 'PUTRI CS', '278622006', 'Razali', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8283, 5, '6282129361354', 'PUTRI CS', '278621865', 'R. Sanjaya', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8284, 5, '6282235397280', 'ASEP PACE CS', '278621434', 'Mikael Senin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8285, 5, '6282243699022', 'PUTRI CS', '278620826', 'aziz firmanto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8286, 5, '6282151542799', 'ASEP PACE CS', '278620745', 'Frida mahuri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8287, 5, '62895339316441', 'ASEP PACE CS', '278620501', 'Tanri Adeng', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8288, 5, '6282165504377', 'ASEP PACE CS', '278620488', 'S.GINTING', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8289, 5, '6281347313572', 'PUTRI CS', '278619566', 'M.Ismail.u', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8290, 5, '6282157205385', 'PUTRI CS', '278618631', 'Ferry Navalin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8291, 5, '6281340225771', 'ASEP PACE CS', '278617836', 'Paula Wahongan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8292, 5, '6281261010225', 'PUTRI CS', '278617789', 'Syamsul Arief', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8293, 5, '6282194856158', 'PUTRI CS', '278616794', 'marlen lapian', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8294, 5, '6285269342466', 'PUTRI CS', '278616327', 'Padi Penginapan JNE', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8295, 5, '6287762756081', 'ASEP PACE CS', '278615908', 'Anom wiryadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8296, 5, '6282196875745', 'PUTRI CS', '278614995', 'Jumari', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8297, 5, '6282174144232', 'ASEP PACE CS', '278613288', 'Agustina Lumban Tobing', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8298, 5, '6281211009768', 'ASEP PACE CS', '278611922', 'Margriet Rembet', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8299, 5, '6285867759178', 'ASEP PACE CS', '278611279', 'Lutfiana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8300, 5, '6282228553920', 'PUTRI CS', '278611212', 'ASNAWI', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8301, 5, '6285245781227', 'PUTRI CS', '278611059', 'Margareta pata', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8302, 5, '628192021282', 'ASEP PACE CS', '278610191', 'Muhammad benny', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8303, 5, '6282188274832', 'ASEP PACE CS', '278609803', 'Maykel Senduk', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8304, 5, '6281240293489', 'PUTRI CS', '278609721', 'amel', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8305, 5, '6282353339881', 'PUTRI CS', '278609070', 'Hj.Vilawati Marwan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8306, 5, '6285784562250', 'PUTRI CS', '278608904', 'Heri purnomo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8307, 5, '6281545356516', 'ASEP PACE CS', '278608853', 'Antonius awet', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8308, 5, '6281241578188', 'PUTRI CS', '278608767', 'Icad', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8309, 5, '6281287309159', 'PUTRI CS', '278608717', 'Hadi suryono', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8310, 5, '6281241376156', 'ASEP PACE CS', '278608647', 'H.Idham Khalid', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8311, 5, '6281348444408', 'ASEP PACE CS', '278608575', 'Puja rahmat', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8312, 5, '6282380513775', 'PUTRI CS', '278608393', 'Budi Tamtomo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8313, 5, '6285253980678', 'ASEP PACE CS', '278608331', 'Wati', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8314, 5, '6281319994976', 'PUTRI CS', '278606737', 'Renita', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8315, 5, '6285885440482', 'PUTRI CS', '278606562', 'Bertha dwiyani', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8316, 5, '6282230505232', 'ASEP PACE CS', '278606308', 'Ade alharis', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8317, 5, '6281243636550', 'PUTRI CS', '278606209', 'Jeny Laluyan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8318, 5, '6281348208818', 'PUTRI CS', '278606112', 'NENGAH MAS WINARA', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8319, 5, '6289630478715', 'ASEP PACE CS', '278605939', 'Yuliana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8320, 5, '6282188525343', 'PUTRI CS', '278604892', 'Fadlin Repadjori', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8321, 5, '628126581841', 'ASEP PACE CS', '278604829', 'H.Surianto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8322, 5, '6281214010175', 'PUTRI CS', '278603519', 'Priska Tarigan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8323, 5, '6282162531429', 'PUTRI CS', '278603446', 'HERMANSYAH MAKMUR DIAH', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8324, 5, '6281375604806', 'ASEP PACE CS', '278603228', 'Irawati Harefa', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8325, 5, '6282144602723', 'ASEP PACE CS', '278602755', 'Agnes mangkung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8326, 5, '6281280701516', 'PUTRI CS', '278602036', 'Arman Adjis', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8327, 5, '6283863934795', 'PUTRI CS', '278601763', 'Linda Nurhayati', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8328, 5, '6282272178880', 'PUTRI CS', '278600952', 'Leo Wemay', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8329, 5, '6285333733455', 'ASEP PACE CS', '278599887', 'Musmualim', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8330, 5, '6283124480944', 'ASEP PACE CS', '278599481', 'Racjmad kurniawan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8331, 5, '628175713607', 'PUTRI CS', '278598867', 'Imam Afandi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8332, 5, '6285752358752', 'ASEP PACE CS', '278598636', 'Nur Yoto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8333, 5, '6282132300082', 'ASEP PACE CS', '278597646', 'Santoso', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8334, 5, '6282277236525', 'ASEP PACE CS', '278597549', 'Iyen', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8335, 5, '628117251716', 'PUTRI CS', '278597480', 'Herizal', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8336, 5, '6281275473809', 'ASEP PACE CS', '278597240', 'Mochamad Fithrah', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8337, 5, '6281252477747', 'PUTRI CS', '278596848', 'Moch suud', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8338, 5, '6281264527861', 'ASEP PACE CS', '278596488', 'M yusup', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8339, 5, '6282236613011', 'ASEP PACE CS', '278595896', 'Agung krisna', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8340, 5, '6281917015298', 'ASEP PACE CS', '278595814', 'Subayil', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8341, 5, '6285624102683', 'PUTRI CS', '278595354', 'Sogol', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8342, 5, '6281228810606', 'PUTRI CS', '278594991', 'Asnan anigara', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8343, 5, '6285240017906', 'ASEP PACE CS', '278594720', 'Brusdi panegoro', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8344, 5, '6282398666685', 'PUTRI CS', '278594671', 'Yosmina', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8345, 5, '6282276270873', 'ASEP PACE CS', '278594472', 'Fitrah hadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8346, 5, '6289654270193', 'PUTRI CS', '278594070', 'Dodikwaluyo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8347, 5, '628125491047', 'PUTRI CS', '278594002', 'Idunia', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8348, 5, '6285282577760', 'PUTRI CS', '278593747', 'Bima Kuryana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8349, 5, '628159976246', 'ASEP PACE CS', '278593460', 'Bima Kuryana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8350, 5, '6285298971440', 'ASEP PACE CS', '278593443', 'Ibu Rike', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8351, 5, '6285540000532', 'ASEP PACE CS', '278592774', 'Agustin Kartika', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8352, 5, '6281271524529', 'PUTRI CS', '278592391', 'Daniel', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8353, 5, '6285373519825', 'ASEP PACE CS', '278592074', 'Faoaro Zai', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8354, 5, '6281345541610', 'PUTRI CS', '278591580', 'Arinsius', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8355, 5, '6282213471489', 'ASEP PACE CS', '278591387', 'Ibrahim', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8356, 5, '6281317715827', 'PUTRI CS', '278590457', 'Ilva Maizon', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8357, 5, '6282254464809', 'ASEP PACE CS', '278590391', 'AlerusAlerus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8358, 5, '6282146322285', 'PUTRI CS', '278590253', 'Rambu Ami', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8359, 5, '6281805642293', 'ASEP PACE CS', '278589964', 'I Gede Suantara', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8360, 5, '6281244540948', 'PUTRI CS', '278589373', 'Hamzi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8361, 5, '6281805482079', 'ASEP PACE CS', '278589109', 'Wayan Sumiarsih', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8362, 5, '6282225088295', 'ASEP PACE CS', '278589013', 'Muh safri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8363, 5, '6281549155251', 'PUTRI CS', '278588739', 'Bahru saputra', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8364, 5, '6282217966667', 'ASEP PACE CS', '278588686', 'Abu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8365, 5, '6281342692853', 'PUTRI CS', '278587871', 'M. IBRAHIM koordinasi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8366, 5, '6281295554675', 'ASEP PACE CS', '278587749', 'Doni', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8367, 5, '628125871213', 'ASEP PACE CS', '278587720', 'Aprilyanus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8368, 5, '6285647365944', 'PUTRI CS', '278587529', 'Sahrul iwan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8369, 5, '628134171428', 'PUTRI CS', '278587468', 'Atika Tehubijuluw', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8370, 5, '6282385993332', 'ASEP PACE CS', '278587170', 'Zakaria', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8371, 5, '6285704178486', 'ASEP PACE CS', '278586961', 'Herdi Thomas', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8372, 5, '6282277799000', 'PUTRI CS', '278586933', 'Heni Lay', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8373, 5, '628114500822', 'PUTRI CS', '278586421', 'Sulfikat', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8374, 5, '6282272191529', 'PUTRI CS', '278586386', 'Ekarius Gaho', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8375, 5, '6285271937194', 'ASEP PACE CS', '278586256', 'marvi tomi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8376, 5, '6285261314011', 'ASEP PACE CS', '278586059', 'JUlIADI', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8377, 5, '6281262113322', 'PUTRI CS', '278586003', 'Asri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8378, 5, '6287778099409', 'PUTRI CS', '278585992', 'Altje Tolidunde', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8379, 5, '6282259872901', 'PUTRI CS', '278585953', 'Anis', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8380, 5, '6281287086341', 'PUTRI CS', '278585346', 'Hasmayani ani', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8381, 5, '6282160168365', 'ASEP PACE CS', '278585106', 'firdaus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8382, 5, '6281354724988', 'ASEP PACE CS', '278585038', 'Kiki Alexander', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8383, 5, '6281340269523', 'PUTRI CS', '278584963', 'Braen k', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8384, 5, '6282299304281', 'ASEP PACE CS', '278584419', 'Muzahid', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8385, 5, '6281311904789', 'PUTRI CS', '278584354', 'Asan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8386, 5, '62811728141', 'PUTRI CS', '278584286', 'Hans Christian', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8387, 5, '6282253106451', 'ASEP PACE CS', '278584146', 'ABJAN MANAF', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8388, 5, '6285249612777', 'PUTRI CS', '278583737', 'Danny Dektos', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8389, 5, '6281649354414', 'ASEP PACE CS', '278583695', 'Herman', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8390, 5, '6282297702820', 'PUTRI CS', '278583208', 'Rusman jalan simpang sambung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8391, 5, '6281377259292', 'PUTRI CS', '278582867', 'NILAWATI', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8392, 5, '6281228431993', 'PUTRI CS', '278582618', 'Ani wulandari', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8393, 5, '6285920520255', 'ASEP PACE CS', '278582489', 'Jason Ignasius Paska', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8394, 5, '6281995895005', 'PUTRI CS', '278582373', 'nila kartika', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8395, 5, '6282147188344', 'ASEP PACE CS', '278582099', 'Dan Taebonat Oben', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8396, 5, '6281337370025', 'PUTRI CS', '278581868', 'Katharina Flora', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8397, 5, '6281937830529', 'ASEP PACE CS', '278581647', 'Nuraeni', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8398, 5, '62811438005', 'PUTRI CS', '278581574', 'Ibu Umi M', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8399, 5, '6282260829728', 'ASEP PACE CS', '278581438', 'Valanio momongan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8400, 5, '6281263453363', 'ASEP PACE CS', '278581173', 'Asnita Diana Nasution', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8401, 5, '6285281280559', 'PUTRI CS', '278581063', 'SARMAHANDI SARAGIH', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8402, 5, '6285215101923', 'ASEP PACE CS', '278580798', 'Aphin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8403, 5, '6282280148351', 'PUTRI CS', '278580586', 'Antarianus Zebua', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8404, 5, '6282154156635', 'ASEP PACE CS', '278580488', 'Dian Moriki', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8405, 5, '6285342886572', 'PUTRI CS', '278580472', 'Munandar', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8406, 5, '6281324091527', 'ASEP PACE CS', '278579561', 'Triyanto/Bolo', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8407, 5, '628126618980', 'ASEP PACE CS', '278579543', 'mei kongadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8408, 5, '6282297162788', 'PUTRI CS', '278579512', 'Al Purkon', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8409, 5, '6288269596714', 'ASEP PACE CS', '278579382', 'yasir', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8410, 5, '6281326596598', 'PUTRI CS', '278579364', 'Rival', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8411, 5, '628124108712', 'ASEP PACE CS', '278579061', 'Nony Tandiayu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8412, 5, '6281361097344', 'PUTRI CS', '278579033', 'Ramayanto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8413, 5, '6281257756610', 'PUTRI CS', '278578956', 'Junaidi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8414, 5, '6282394243094', 'ASEP PACE CS', '278578939', 'Novi wengkang', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8415, 5, '6285379570700', 'ASEP PACE CS', '278578544', 'Mukhlisin Lisin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8416, 5, '6283107209726', 'ASEP PACE CS', '278578339', 'Haryono winoto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8417, 5, '6285210461916', 'ASEP PACE CS', '278578217', 'ronal', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8418, 5, '6287760766222', 'PUTRI CS', '278577518', 'Nana sutisna', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8419, 5, '6281380112122', 'ASEP PACE CS', '278577493', 'Francyn Rompas', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8420, 5, '628985009702', 'ASEP PACE CS', '278577464', 'Nurdin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8421, 5, '6282245835888', 'PUTRI CS', '278577202', 'Andi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8422, 5, '6283188895485', 'PUTRI CS', '278576567', 'Jana Harahap', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8423, 5, '6282116305358', 'ASEP PACE CS', '278576489', 'Herman', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8424, 5, '6285242440222', 'PUTRI CS', '278576036', 'frans eda', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8425, 5, '6285271004082', 'PUTRI CS', '278576014', 'Christin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8426, 5, '6285657207670', 'ASEP PACE CS', '278575965', 'Rafika mendame', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8427, 5, '6282156212077', 'ASEP PACE CS', '278575768', 'Imam Bakori', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8428, 5, '6285343964418', 'PUTRI CS', '278575732', 'Agus Agustinusmonu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8429, 5, '6282343654086', 'PUTRI CS', '278575653', 'Mama ikbal', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8430, 5, '6282236077090', 'ASEP PACE CS', '278575632', 'Donnie Ferdinand', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8431, 5, '6281243399000', 'ASEP PACE CS', '278575327', 'Mardjun Niode', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8432, 5, '6289505647009', 'PUTRI CS', '278575070', 'Petrus kurniawan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8433, 5, '6281349706684', 'ASEP PACE CS', '278574974', 'Abianhin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8434, 5, '6285395843853', 'PUTRI CS', '278574968', 'Almince Ruba', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8435, 5, '6282191806307', 'PUTRI CS', '278574862', 'Farida Mahengkeng', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8436, 5, '6281364959119', 'PUTRI CS', '278574776', 'Suwandi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8437, 5, '6285263894646', 'ASEP PACE CS', '278574763', 'Williyan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8438, 5, '6281649042370', 'PUTRI CS', '278574705', 'Lusius Ante', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8439, 5, '6285256975300', 'PUTRI CS', '278574289', 'aljufri buchari', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8440, 5, '628116680986', 'ASEP PACE CS', '278574161', 'Dewi indah', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8441, 5, '6281341623807', 'ASEP PACE CS', '278574080', 'ERFINA POKO', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8442, 5, '6285349609168', 'PUTRI CS', '278574050', 'Adriana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8443, 5, '6281330313816', 'PUTRI CS', '278574006', 'Solun', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8444, 5, '6281341704411', 'ASEP PACE CS', '278573968', 'Jacob Sampe', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8445, 5, '6282156202300', 'ASEP PACE CS', '278573946', 'Rosna laiya', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8446, 5, '6282292800490', 'ASEP PACE CS', '278573706', 'Nia Ilyas', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8447, 5, '6289652283206', 'PUTRI CS', '278573638', 'Masye Pongantung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8448, 5, '6285242762105', 'ASEP PACE CS', '278573559', 'Andi mutia', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8449, 5, '628999974062', 'ASEP PACE CS', '278573417', 'Budiarto Kartiko', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8450, 5, '6287770390386', 'PUTRI CS', '278573322', 'Zainal abidin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8451, 5, '6282323666612', 'PUTRI CS', '278573264', 'Mohammad isra indra Setiawan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8452, 5, '6282190366424', 'ASEP PACE CS', '278573263', 'Daniel', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8453, 5, '6281347227127', 'PUTRI CS', '278573006', 'Selvianus kawuwung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8454, 5, '6285399535505', 'PUTRI CS', '278572973', 'Purnama Jaya', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8455, 5, '6285796254502', 'ASEP PACE CS', '278572837', 'Tria Altox Arsad', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8456, 5, '6281289308852', 'PUTRI CS', '278572710', 'Mohamad taufik', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8457, 5, '6282255997676', 'ASEP PACE CS', '278572658', 'Aya', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8458, 5, '6289624151050', 'PUTRI CS', '278572605', 'Tajuh Albert', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8459, 5, '6285299652023', 'PUTRI CS', '278572591', 'Yulianto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8460, 5, '628174870335', 'ASEP PACE CS', '278572404', 'Doddy Laksmayana', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8461, 5, '6282192980397', 'ASEP PACE CS', '278572334', 'Muhammad Husni', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8462, 5, '6289695300522', 'ASEP PACE CS', '278572159', 'Margo Tieneke', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8463, 5, '6281375161520', 'PUTRI CS', '278572141', 'Maria Linda', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8464, 5, '6285319108923', 'ASEP PACE CS', '278572018', 'Kusria', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8465, 5, '6282291999656', 'PUTRI CS', '278571761', 'Amanda Maisura', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8466, 5, '6285244968531', 'PUTRI CS', '278571317', 'Jerry MANOREK', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8467, 5, '6281379685094', 'PUTRI CS', '278571204', 'Suwarto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8468, 5, '6281343795043', 'ASEP PACE CS', '278571200', 'Muhammad Ridwan(Wawan)', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8469, 5, '6285255566136', 'ASEP PACE CS', '278571086', 'Taufiq', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8470, 5, '6281804421983', 'ASEP PACE CS', '278571016', 'Clarissa', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8471, 5, '6285393327189', 'ASEP PACE CS', '278570992', 'M tahir', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8472, 5, '62895630141328', 'PUTRI CS', '278570555', 'Manan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8473, 5, '6281218523354', 'ASEP PACE CS', '278570414', 'Benaya Yunus', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8474, 5, '6285340372176', 'ASEP PACE CS', '278570379', 'Treisje Wowor', '2026-08-13 14:59:31', '2026-08-13 14:59:31');
INSERT INTO `order_online_contacts` (`id`, `advertiser_id`, `phone_normalized`, `cs_name`, `order_id`, `buyer_name`, `created_at`, `updated_at`) VALUES
(8475, 5, '6281239522539', 'PUTRI CS', '278570218', 'I Gusti Putu Arka', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8476, 5, '6281245738989', 'PUTRI CS', '278570199', 'Maskurin Rantung', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8477, 5, '6281352182682', 'ASEP PACE CS', '278570191', 'Anggelina simba', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8478, 5, '6285251644302', 'PUTRI CS', '278570031', 'Ibrahim Syaibi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8479, 5, '6281298592176', 'ASEP PACE CS', '278569625', 'Carlos Pangguruang', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8480, 5, '6285269776006', 'PUTRI CS', '278569504', 'Bp Talsi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8481, 5, '6285242087812', 'ASEP PACE CS', '278569418', 'Tanty', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8482, 5, '6281368854095', 'PUTRI CS', '278569368', 'Lili Watie', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8483, 5, '628983692135', 'ASEP PACE CS', '278569347', 'Siti Nuriyah', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8484, 5, '6281342571999', 'PUTRI CS', '278569216', 'ROBERTHO LATUPARISA', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8485, 5, '6281933005958', 'ASEP PACE CS', '278569031', 'Nyoman Lemes', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8486, 5, '6285237106788', 'PUTRI CS', '278568686', 'Mangku Arsa', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8487, 5, '6285394631033', 'ASEP PACE CS', '278568652', 'Lusi Mongilong', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8488, 5, '6281341425578', 'ASEP PACE CS', '278568574', 'Silvana Rumagit', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8489, 5, '6282271115358', 'PUTRI CS', '278568530', 'Pipin rizkilah', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8490, 5, '628114052610', 'PUTRI CS', '278568502', 'Isla', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8491, 5, '6282191904318', 'ASEP PACE CS', '278568471', 'Rosita Rajak', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8492, 5, '6282192238441', 'ASEP PACE CS', '278568310', 'Berty Sonny Terok', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8493, 5, '6287791884596', 'PUTRI CS', '278567957', 'Ramdhani Rondonuwu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8494, 5, '6282152716542', 'PUTRI CS', '278567902', 'Ramdhani Rondonuwu', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8495, 5, '6285255553711', 'ASEP PACE CS', '278567791', 'Tamrin', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8496, 5, '6285240053606', 'ASEP PACE CS', '278567724', 'Weidy  A. F . Rasuh', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8497, 5, '6281226229319', 'PUTRI CS', '278567645', 'Sugeng riyadi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8498, 5, '6281294161743', 'ASEP PACE CS', '278567439', 'Zulkarnain Purba', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8499, 5, '6289687483403', 'ASEP PACE CS', '278567427', 'Samsul bahri', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8500, 5, '6282148138737', 'ASEP PACE CS', '278567408', 'Maxwell Abraham Gysbert Togas', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8501, 5, '6281385268746', 'PUTRI CS', '278567360', 'AKHMAD ZULLIYANTO', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8502, 5, '6282348928997', 'PUTRI CS', '278567112', 'Lina', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8503, 5, '6282110420348', 'ASEP PACE CS', '278567055', 'Slamet Haryanto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8504, 5, '628987730646', 'PUTRI CS', '278566824', 'MERKY LANGELO', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8505, 5, '6282381511260', 'PUTRI CS', '278566768', 'Sahudi Adi Saputra', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8506, 5, '6282130152175', 'PUTRI CS', '278566753', 'Menas wesara .', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8507, 5, '6285260285393', 'PUTRI CS', '278566527', 'Muhammad Jonsmardi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8508, 5, '6282190072392', 'PUTRI CS', '278566380', 'HAEDIR', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8509, 5, '6282228957968', 'ASEP PACE CS', '278566148', 'Junanta sianturi', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8510, 5, '6281258455321', 'PUTRI CS', '278565776', 'Sutiawan Darmawanto', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(8511, 5, '6282160477996', 'ASEP PACE CS', '278565772', 'Susan', '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(9164, 4, '6281277217634', 'FERI CS', '278724942', 'Nadirsyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9165, 4, '6285141486317', 'FERI CS', '278724857', 'Inggrid Kambey', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9166, 4, '6285210234853', 'MAYANG CS', '278724834', 'JOELOEIS S', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9167, 4, '6285182400086', 'MAYANG CS', '278724760', 'iskandarl daeng', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9168, 4, '6281273227428', 'FERI CS', '278724704', 'Rosdi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9169, 4, '6281369354747', 'MAYANG CS', '278724675', 'Hendri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9170, 4, '6282138735057', 'MAYANG CS', '278724433', 'Restu pamuji elfina', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9171, 4, '6287899926227', 'MAYANG CS', '278724291', 'fujiyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9172, 4, '6282387558755', 'MAYANG CS', '278724287', 'Fendy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9173, 4, '6282357835115', 'FERI CS', '278724273', 'Khalid syafrani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9174, 4, '6285789761046', 'FERI CS', '278724236', 'Jaka saputra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9175, 4, '628128834260', 'MAYANG CS', '278724029', 'Adrian', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9176, 4, '6285261532132', 'FERI CS', '278723801', 'Misrianto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9177, 4, '6285220778777', 'MAYANG CS', '278723779', 'Arief Sya\'bani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9178, 4, '6281380318682', 'MAYANG CS', '278723749', 'Dicky Widyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9179, 4, '628164534864', 'FERI CS', '278723736', 'Alex Sy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9180, 4, '6285290443249', 'FERI CS', '278723672', 'Nanda Jenny Jenny', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9181, 4, '6281336232769', 'FERI CS', '278723614', 'Herin Setiawati', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9182, 4, '6282284191092', 'MAYANG CS', '278723599', 'Suryani Yadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9183, 4, '6282347234017', 'MAYANG CS', '278723569', 'Ucik sangkalia', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9184, 4, '6287862883466', 'MAYANG CS', '278723568', 'Wardana i', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9185, 4, '6287809402146', 'FERI CS', '278723454', 'hendra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9186, 4, '6281280896399', 'FERI CS', '278723150', 'Maryoto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9187, 4, '6282155050105', 'MAYANG CS', '278722673', 'Endah sri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9188, 4, '6256986060088', 'MAYANG CS', '278722612', 'Amritua Siregar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9189, 4, '628213370560', 'FERI CS', '278722464', 'Amritua Siregar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9190, 4, '6281373276354', 'MAYANG CS', '278722430', 'Awalludin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9191, 4, '6285240646370', 'FERI CS', '278722409', 'Imran paputungan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9192, 4, '6282285786055', 'FERI CS', '278722376', 'INDESSYAHPUTRA BATUBARA', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9193, 4, '6282260797552', 'MAYANG CS', '278722360', 'Apandi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9194, 4, '6282331488994', 'MAYANG CS', '278722313', 'Hadinya Radja', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9195, 4, '6281361335761', 'FERI CS', '278722197', 'Syahrul', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9196, 4, '6281310500835', 'MAYANG CS', '278721990', 'Ully pangaribuan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9197, 4, '628117778528', 'MAYANG CS', '278721652', 'Happy Saida', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9198, 4, '6285277512371', 'FERI CS', '278721634', 'Sejahteraa Devari', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9199, 4, '6282364960343', 'FERI CS', '278721521', 'Dul Hasan mk', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9200, 4, '6281368126660', 'FERI CS', '278721441', 'Alam Cahyogi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9201, 4, '6285298777291', 'FERI CS', '278721425', 'Rosmina Mokodompit', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9202, 4, '6282250789861', 'MAYANG CS', '278721414', 'Parizal', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9203, 4, '6287887093827', 'MAYANG CS', '278721155', 'Andi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9204, 4, '6281230320100', 'MAYANG CS', '278720798', 'Roy Sutrisno', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9205, 4, '6282379119514', 'FERI CS', '278720766', 'Spyono', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9206, 4, '6282371854950', 'FERI CS', '278720334', 'sandra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9207, 4, '6281578378155', 'MAYANG CS', '278720295', 'Benie emde', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9208, 4, '628121027340', 'MAYANG CS', '278720232', 'Nurdin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9209, 4, '6282118567741', 'MAYANG CS', '278719748', 'Alfa Kaunang', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9210, 4, '6281366223370', 'MAYANG CS', '278719527', 'Hendrix yusuf', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9211, 4, '628179781170', 'MAYANG CS', '278719363', 'I gede eka adi saputra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9212, 4, '6282165557159', 'FERI CS', '278719189', 'Feriilham', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9213, 4, '6282280062598', 'FERI CS', '278718965', 'Sukri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9214, 4, '6282312874106', 'MAYANG CS', '278718793', 'Suranto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9215, 4, '6282191944207', 'FERI CS', '278718588', 'Hendry novin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9216, 4, '6281383710573', 'FERI CS', '278718296', 'Nasrun', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9217, 4, '628161600040', 'MAYANG CS', '278718145', 'dulloh', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9218, 4, '6285171019736', 'MAYANG CS', '278717845', 'vonny lumintang', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9219, 4, '628111022568', 'FERI CS', '278717645', 'I Nyoman Ana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9220, 4, '6285399335757', 'FERI CS', '278717608', 'Bahrul', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9221, 4, '6282125970169', 'MAYANG CS', '278717573', 'Sihombing', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9222, 4, '62887437087337', 'FERI CS', '278717102', 'Jonlifuadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9223, 4, '6281361568746', 'FERI CS', '278717063', 'Sumarto Sihite', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9224, 4, '6281367118822', 'MAYANG CS', '278716718', 'Iskandir', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9225, 4, '6289681137139', 'MAYANG CS', '278716644', 'Ni Made wilisantini', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9226, 4, '6281910824066', 'MAYANG CS', '278716288', 'Sarwan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9227, 4, '6285240763135', 'MAYANG CS', '278716239', 'Nyimpung Donda', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9228, 4, '6282197118469', 'FERI CS', '278716237', 'Agus Salim Agus', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9229, 4, '6283119544516', 'MAYANG CS', '278715606', 'Ayu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9230, 4, '6285756512009', 'MAYANG CS', '278715418', 'Melky waha', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9231, 4, '6285189951995', 'FERI CS', '278715319', 'Tedy Susanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9232, 4, '628124500587', 'MAYANG CS', '278715246', 'Wilsonpulo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9233, 4, '6281511019862', 'FERI CS', '278715142', 'Kuwatno baiq', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9234, 4, '6282328882224', 'FERI CS', '278715074', 'Takwin Wien', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9235, 4, '6281247615969', 'FERI CS', '278715047', 'Madran.', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9236, 4, '6282353338216', 'MAYANG CS', '278714857', 'Nasrul Anas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9237, 4, '6285255551445', 'FERI CS', '278714809', 'H jemma', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9238, 4, '6281355880115', 'MAYANG CS', '278714802', 'Rama ruhyana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9239, 4, '6281268829861', 'MAYANG CS', '278714554', 'Amsarajaa Amsarajaa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9240, 4, '6282315016486', 'FERI CS', '278714457', 'Rosmalinda', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9241, 4, '6281237249207', 'FERI CS', '278714410', 'Trisnajaya', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9242, 4, '6282129925677', 'MAYANG CS', '278714303', 'Pak alika', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9243, 4, '6281374127078', 'MAYANG CS', '278713867', 'Ahmad Faizh', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9244, 4, '6283866614418', 'MAYANG CS', '278713527', 'Edi nurohman', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9245, 4, '6281398211710', 'FERI CS', '278713480', 'Aziz budianto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9246, 4, '6282178992000', 'FERI CS', '278713326', 'Decki Inu Indawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9247, 4, '628388311875', 'MAYANG CS', '278713255', 'Ridho Santoso damanik', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9248, 4, '6282348847878', 'MAYANG CS', '278713026', 'Agustinus Liongke', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9249, 4, '6285764940418', 'FERI CS', '278712908', 'Budi Suswanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9250, 4, '6285935209797', 'FERI CS', '278712705', 'Made Putra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9251, 4, '6282283029217', 'FERI CS', '278712674', 'Tian', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9252, 4, '6285920149449', 'FERI CS', '278712426', 'Nurjanah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9253, 4, '6289602813645', 'FERI CS', '278712376', 'Ukar ilham', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9254, 4, '6285298832967', 'MAYANG CS', '278712329', 'Temmy Assa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9255, 4, '6281236382160', 'MAYANG CS', '278712268', 'Irvan Masyagie', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9256, 4, '6258858598969', 'FERI CS', '278712250', 'Fgjjhcbnn', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9257, 4, '6265883685988', 'MAYANG CS', '278711808', 'Cjebfhjevvhhj', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9258, 4, '6281337765686', 'MAYANG CS', '278711623', 'I Nyoman Werna', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9259, 4, '6281393126789', 'MAYANG CS', '278711555', 'Agus Istiyono', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9260, 4, '6282124658337', 'FERI CS', '278711487', 'Niswar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9261, 4, '6281242648810', 'FERI CS', '278711471', 'Zakaria Sampe', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9262, 4, '628112000213', 'FERI CS', '278711439', 'Ardi st', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9263, 4, '6285210881676', 'FERI CS', '278711212', 'Tumini', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9264, 4, '6285269274581', 'MAYANG CS', '278710765', 'Opu Makkawaru', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9265, 4, '6281374633292', 'FERI CS', '278710684', 'nofrizal hendra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9266, 4, '6282339714906', 'MAYANG CS', '278710631', 'Gani Hendra', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9267, 4, '6281258455321', 'MAYANG CS', '278710619', 'Sutiawan Darmawanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9268, 4, '6281258808899', 'MAYANG CS', '278710550', 'Robi Darwis', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9269, 4, '6281367338046', 'FERI CS', '278710343', 'charles frehdy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9270, 4, '6282284699867', 'MAYANG CS', '278710138', 'Javva Velicia', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9271, 4, '6281239115518', 'FERI CS', '278709985', 'Pak meri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9272, 4, '6282377794179', 'FERI CS', '278709825', 'Aras', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9273, 4, '6281211103993', 'MAYANG CS', '278709723', 'Yayan Sofiyan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9274, 4, '6285658346122', 'MAYANG CS', '278709620', 'Daris', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9275, 4, '6281271698732', 'MAYANG CS', '278707690', 'Syahril', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9276, 4, '6281916872007', 'FERI CS', '278707432', 'Maula Ditya', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9277, 4, '6282217962447', 'FERI CS', '278707115', 'Sopi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9278, 4, '6285338179331', 'MAYANG CS', '278706492', 'Baiq Sri Handayani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9279, 4, '6282339522476', 'FERI CS', '278706317', 'Hasni.pandie', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9280, 4, '6282190357779', 'MAYANG CS', '278705873', 'Nabil', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9281, 4, '6282197758146', 'FERI CS', '278705398', 'Juniar Novita', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9282, 4, '6282317709164', 'MAYANG CS', '278705020', 'Hasriani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9283, 4, '6281280241678', 'FERI CS', '278704417', 'Feriyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9284, 4, '6281352126579', 'FERI CS', '278703992', 'RIYADY', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9285, 4, '628161122407', 'MAYANG CS', '278703417', 'Yaman Edie Bair H08', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9286, 4, '6282115106916', 'MAYANG CS', '278703357', 'Bambang Lucy sobandi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9287, 4, '6282138218303', 'MAYANG CS', '278702974', 'Makkatang Hs', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9288, 4, '6281261696514', 'FERI CS', '278702852', 'Sutrisno', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9289, 4, '6285375781445', 'FERI CS', '278702773', 'Sudirman Tambusai', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9290, 4, '628138039333', 'FERI CS', '278702323', 'Syafril Latif', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9291, 4, '6281239227194', 'MAYANG CS', '278702029', 'Lyandi Takandjandji', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9292, 4, '6282386791038', 'FERI CS', '278701724', 'Sobur Jaya', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9293, 4, '6281363499510', 'MAYANG CS', '278701343', 'Yandri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9294, 4, '6288994316855', 'MAYANG CS', '278701319', 'Lasio', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9295, 4, '6281997950164', 'MAYANG CS', '278701129', 'Umar Dani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9296, 4, '6285398518888', 'FERI CS', '278701037', 'Charles Ngantung', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9297, 4, '62811551456', 'FERI CS', '278700886', 'Drs.H.Pandi.SH.MH', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9298, 4, '6281804932316', 'FERI CS', '278700857', 'Imat rohimat', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9299, 4, '6281222736078', 'MAYANG CS', '278700618', 'Kama Kama', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9300, 4, '6281256543343', 'FERI CS', '278700366', 'Ahmad junaidi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9301, 4, '6285609046900', 'MAYANG CS', '278700167', 'Ripul Padri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9302, 4, '6285857502067', 'FERI CS', '278699504', 'I Made suarga', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9303, 4, '6281355151041', 'MAYANG CS', '278699174', 'GORDIANUS PRIBADI LOMBONGADIL', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9304, 4, '6281314880627', 'MAYANG CS', '278699130', 'Dewi Latief', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9305, 4, '6282132819999', 'FERI CS', '278699030', 'Gerrie', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9306, 4, '6281270464625', 'FERI CS', '278699027', 'Lukman Simatupang / Lidya kusuma', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9307, 4, '6281319191971', 'MAYANG CS', '278698539', 'Bace pm', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9308, 4, '6281217546839', 'FERI CS', '278698121', 'Zainal Arifin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9309, 4, '6281245738989', 'MAYANG CS', '278697624', 'Maskurin Rantung', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9310, 4, '628179730809', 'MAYANG CS', '278697193', 'JMK Nengah Sudarma', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9311, 4, '6285320859045', 'FERI CS', '278696294', 'Udi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9312, 4, '6282118967521', 'MAYANG CS', '278695940', 'Suwito', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9313, 4, '6285804945334', 'FERI CS', '278695927', 'Selefthin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9314, 4, '6285288072074', 'FERI CS', '278695756', 'Parete', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9315, 4, '6281340597820', 'FERI CS', '278695391', 'Amerlina worung', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9316, 4, '6282365438890', 'MAYANG CS', '278695333', 'Mispan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9317, 4, '6289699824844', 'MAYANG CS', '278694937', 'Tino Azfian Nasution', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9318, 4, '6282190509099', 'MAYANG CS', '278694100', 'Matjuri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9319, 4, '628889991985', 'FERI CS', '278693640', 'Sari', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9320, 4, '6281374514509', 'FERI CS', '278693468', 'Joko Susilo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9321, 4, '6281337890324', 'MAYANG CS', '278693286', 'asti', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9322, 4, '6281341546779', 'MAYANG CS', '278692286', 'Mustapa Badoali', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9323, 4, '6281270081500', 'FERI CS', '278689609', 'Karnedy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9324, 4, '6281289405060', 'FERI CS', '278689113', 'Dedi harnadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9325, 4, '6285227284931', 'MAYANG CS', '278689001', 'Elly Dwiwati', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9326, 4, '6283851011551', 'MAYANG CS', '278688129', 'Nur jn', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9327, 4, '6282117063034', 'MAYANG CS', '278687593', 'Dani Daniansyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9328, 4, '6282252844350', 'FERI CS', '278687502', 'EDI SUSANTO', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9329, 4, '628176056858', 'FERI CS', '278687222', 'Putu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9330, 4, '6282170760782', 'MAYANG CS', '278687221', 'Syafrijal', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9331, 4, '6285292718851', 'FERI CS', '278687124', 'Eko Dwisukmanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9332, 4, '6282134808290', 'MAYANG CS', '278687034', 'Syifa Sandora', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9333, 4, '6282360390588', 'FERI CS', '278686667', 'Nasry Zaman', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9334, 4, '6285394182856', 'MAYANG CS', '278685879', 'Rubianto Mokodongan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9335, 4, '6281343922939', 'FERI CS', '278685806', 'Kasmirus Kebelen', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9336, 4, '6285240800450', 'FERI CS', '278685717', 'Parmin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9337, 4, '6287788467624', 'MAYANG CS', '278685584', 'MSULTONI', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9338, 4, '6285708152389', 'MAYANG CS', '278685337', 'Nur khozin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9339, 4, '6281368483409', 'FERI CS', '278685272', 'Mislena latief', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9340, 4, '6281916458450', 'MAYANG CS', '278685183', 'Wayan Sumartini', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9341, 4, '6282313669437', 'FERI CS', '278685094', 'IinRamadhani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9342, 4, '6281387440659', 'FERI CS', '278684998', 'Ane', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9343, 4, '6285270711195', 'MAYANG CS', '278684890', 'Jon M. Marpaung', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9344, 4, '6282349963341', 'FERI CS', '278684858', 'Jhon Enggel R', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9345, 4, '6285339098733', 'MAYANG CS', '278684717', 'Azhar(Toko miju gongob)', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9346, 4, '6281380606868', 'MAYANG CS', '278684653', 'Johan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9347, 4, '6283857847960', 'FERI CS', '278684566', 'Parna', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9348, 4, '62895392170201', 'FERI CS', '278684295', 'Moon.Olii', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9349, 4, '6285932601658', 'FERI CS', '278683745', 'Ikbal M Yusuf', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9350, 4, '6283193422980', 'FERI CS', '278683518', 'Julianaa Tumanggor', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9351, 4, '6281254872000', 'MAYANG CS', '278683474', 'Kristianto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9352, 4, '62895387623532', 'FERI CS', '278683154', 'TrioSubekti81', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9353, 4, '6282298042520', 'MAYANG CS', '278682679', 'Markus Hengkeng', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9354, 4, '6282337028661', 'FERI CS', '278682372', 'Graeny manderos', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9355, 4, '6282339425113', 'MAYANG CS', '278682043', 'Faruq Tawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9356, 4, '6285896225550', 'FERI CS', '278681499', 'Yusman edi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9357, 4, '6282183048546', 'FERI CS', '278681480', 'Dani ndut', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9358, 4, '6281219944218', 'MAYANG CS', '278681478', 'Putro Hadi Pamungkas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9359, 4, '6281346487654', 'FERI CS', '278680789', 'Steven Manoppo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9360, 4, '6282283841706', 'FERI CS', '278680420', 'Perdata', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9361, 4, '6285752923063', 'MAYANG CS', '278680371', 'Hairi Ansyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9362, 4, '6287773588307', 'MAYANG CS', '278680283', 'Wagiyo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9363, 4, '6282178785859', 'FERI CS', '278680229', 'setya wahyudi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9364, 4, '6282195088344', 'FERI CS', '278679726', 'Plona mokodompis', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9365, 4, '6281310925294', 'MAYANG CS', '278679581', 'Azma Faisal', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9366, 4, '6282151856541', 'MAYANG CS', '278679551', 'Mas Salim', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9367, 4, '6281522540228', 'MAYANG CS', '278679176', '81522540228', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9368, 4, '6285802847159', 'MAYANG CS', '278679001', 'Sriyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9369, 4, '6285397793020', 'FERI CS', '278678940', 'Aras', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9370, 4, '628123802134', 'MAYANG CS', '278678907', 'I GUSTI KETUT ARYA PUTRA', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9371, 4, '6285355891511', 'FERI CS', '278678823', 'wagimin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9372, 4, '6281371335652', 'MAYANG CS', '278678001', 'Safrizal z', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9373, 4, '6285332288011', 'FERI CS', '278677757', 'Yubert Bky', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9374, 4, '6285248187071', 'MAYANG CS', '278677744', 'Hanarul Affandi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9375, 4, '6281268710157', 'FERI CS', '278677724', 'Rishan Bahar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9376, 4, '6287765131932', 'FERI CS', '278677718', 'Wijayakusuma', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9377, 4, '62818185208', 'MAYANG CS', '278677641', 'Siane', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9378, 4, '6281366361766', 'FERI CS', '278677215', 'Astuti', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9379, 4, '6282314773456', 'FERI CS', '278677079', 'Joko Susilo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9380, 4, '6281369407100', 'MAYANG CS', '278676980', 'Chepi khairil', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9381, 4, '62895368022333', 'FERI CS', '278676799', 'SUKITO', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9382, 4, '6282214231993', 'MAYANG CS', '278676119', 'Mustopal', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9383, 4, '6285814051238', 'MAYANG CS', '278675870', 'Suyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9384, 4, '6282183703555', 'MAYANG CS', '278675819', 'Jon', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9385, 4, '6281380017452', 'MAYANG CS', '278675691', 'Boy hamza', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9386, 4, '6282192776787', 'FERI CS', '278674837', 'Yessie', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9387, 4, '6281345394181', 'MAYANG CS', '278673878', 'Harun Arrasyid', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9388, 4, '6282121826154', 'FERI CS', '278673699', 'Ibu jumyati', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9389, 4, '6282188326941', 'FERI CS', '278673623', 'Adam Iradat', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9390, 4, '6281290903600', 'MAYANG CS', '278673589', 'Yusuf Supriadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9391, 4, '6282288773656', 'MAYANG CS', '278672509', 'laoliniastato Laoli', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9392, 4, '62811911869', 'FERI CS', '278672354', 'Agung', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9393, 4, '6282238567989', 'FERI CS', '278672153', 'Dermawan Panemba', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9394, 4, '6281349378787', 'MAYANG CS', '278671209', 'Husniansyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9395, 4, '6281703792929', 'FERI CS', '278671003', 'Kadek wisana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9396, 4, '6289524992523', 'MAYANG CS', '278670739', 'Cahya bedariawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9397, 4, '62881374930009', 'FERI CS', '278670486', 'Erlandy Tumewu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9398, 4, '6285220650084', 'MAYANG CS', '278670436', 'rudian ahmad', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9399, 4, '6281276093192', 'FERI CS', '278670256', 'Ranto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9400, 4, '6282157088563', 'FERI CS', '278670189', 'Asyifa Tri BLOKIR', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9401, 4, '6281233485333', 'MAYANG CS', '278670141', 'Slamet mulyo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9402, 4, '6281342622109', 'FERI CS', '278669813', 'Paryanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9403, 4, '6281349402240', 'FERI CS', '278669781', 'joko lelono', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9404, 4, '6282142432686', 'MAYANG CS', '278669715', 'Dwi Prapti Ningsih', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9405, 4, '6281244473288', 'MAYANG CS', '278668922', 'Ay', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9406, 4, '6282386998852', 'FERI CS', '278668359', 'Surdial', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9407, 4, '6282340865953', 'MAYANG CS', '278667717', 'Wayan sudarsana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9408, 4, '6282290172455', 'FERI CS', '278667640', 'Martho Rimba', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9409, 4, '6281293953692', 'FERI CS', '278667160', 'Charles Nababan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9410, 4, '6281224512386', 'FERI CS', '278666970', 'Herniawan Wawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9411, 4, '6285362807634', 'FERI CS', '278666889', 'Zega Erlina', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9412, 4, '628123189660', 'MAYANG CS', '278666711', 'Ratna', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9413, 4, '6282342495971', 'MAYANG CS', '278666583', 'Ni Wayan Sumartini', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9414, 4, '6281341120561', 'FERI CS', '278666535', 'Ambo Aha Jumain', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9415, 4, '6285270143950', 'FERI CS', '278666491', 'Suyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9416, 4, '6282112640123', 'MAYANG CS', '278666432', 'Rende Batti', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9417, 4, '6287864847567', 'FERI CS', '278666207', 'Erlan suhadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9418, 4, '6282121973926', 'MAYANG CS', '278665803', 'Umi cani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9419, 4, '62124849428', 'MAYANG CS', '278665800', 'Ani Suryani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9420, 4, '6282323235599', 'FERI CS', '278665751', 'Natalia Natalia', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9421, 4, '6281296326225', 'MAYANG CS', '278664767', 'Santy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9422, 4, '6281346232034', 'MAYANG CS', '278664080', 'Stenly Rumagit', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9423, 4, '6285286873217', 'FERI CS', '278663721', 'Omega kristina siahaan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9424, 4, '62817350759', 'MAYANG CS', '278663485', 'RATNA K ROY', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9425, 4, '6282272085088', 'FERI CS', '278663096', 'Folala Telaumbanua', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9426, 4, '6281244070809', 'FERI CS', '278663061', 'Tommy Poluan BLOKIR', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9427, 4, '6282153511153', 'MAYANG CS', '278662951', 'Nur Izzaturrochmah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9428, 4, '6285232074710', 'MAYANG CS', '278662361', 'Hirma', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9429, 4, '6282196843716', 'MAYANG CS', '278661991', 'Decky Deston', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9430, 4, '6285784689188', 'FERI CS', '278661784', 'Siti Komariyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9431, 4, '6289631098975', 'FERI CS', '278661775', 'Stefanny watulingas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9432, 4, '6281391610385', 'MAYANG CS', '278661605', 'Warso/ talita', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9433, 4, '6281365650128', 'MAYANG CS', '278661587', 'Rahmat', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9434, 4, '6281319432400', 'MAYANG CS', '278661536', 'Taha Iril', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9435, 4, '6282278392878', 'MAYANG CS', '278661006', 'Rosita widiastuti', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9436, 4, '6287845816180', 'FERI CS', '278660816', 'Abdurrahman', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9437, 4, '6281239599522', 'FERI CS', '278660770', 'Made Wirama', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9438, 4, '6285891557290', 'MAYANG CS', '278660711', 'Nana Irhamna', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9439, 4, '6285299099659', 'FERI CS', '278660597', 'Saripuddin udhin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9440, 4, '6281377114669', 'MAYANG CS', '278660128', 'Supriyanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9441, 4, '6282187725400', 'FERI CS', '278660055', 'Andarias Upa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9442, 4, '6282341993675', 'MAYANG CS', '278660015', 'Mansyur Husain', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9443, 4, '6282247610760', 'MAYANG CS', '278659885', 'Sumerta Wayan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9444, 4, '6281649319932', 'FERI CS', '278659799', 'Armanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9445, 4, '6282331252122', 'FERI CS', '278659584', 'Hj. Rostina', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9446, 4, '6281289766365', 'MAYANG CS', '278659402', 'Arvin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9447, 4, '6282392356250', 'FERI CS', '278659288', 'Rullyansyah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9448, 4, '6282312749365', 'MAYANG CS', '278659156', 'M. MAKSUM', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9449, 4, '6282273456463', 'FERI CS', '278658996', 'Suhirianto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9450, 4, '6281916601631', 'MAYANG CS', '278658910', 'Komang sulasih', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9451, 4, '6281253093447', 'FERI CS', '278658887', 'Mah Yudi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9452, 4, '6287777119154', 'MAYANG CS', '278658826', 'Matani Jaya Bengkel', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9453, 4, '6285265818181', 'MAYANG CS', '278658815', 'Rio Mahesa Cendikiawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9454, 4, '6289653954745', 'FERI CS', '278658714', 'Aris', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9455, 4, '628121686589', 'FERI CS', '278658681', 'Tri Bowo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9456, 4, '6285133046862', 'MAYANG CS', '278658589', 'Pasya', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9457, 4, '6282290227603', 'FERI CS', '278658534', 'Mushawwir Mushawwir', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9458, 4, '6281250585126', 'MAYANG CS', '278658326', 'Yuliane', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9459, 4, '6281222145115', 'FERI CS', '278657825', 'Herry Jhon Sagai', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9460, 4, '6289506957447', 'MAYANG CS', '278657667', 'Wauran Rivo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9461, 4, '6285655712952', 'FERI CS', '278657507', 'Deden', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9462, 4, '6281253150939', 'FERI CS', '278657372', 'Zidan,..', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9463, 4, '628115420969', 'FERI CS', '278657297', 'Dina', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9464, 4, '6282352788495', 'FERI CS', '278656842', 'Rahman syah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9465, 4, '62818382833', 'MAYANG CS', '278656837', 'Andreas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9466, 4, '6281223521618', 'MAYANG CS', '278656794', 'Sabar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9467, 4, '6281914303492', 'FERI CS', '278656702', 'Suwarli', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9468, 4, '6285249490588', 'FERI CS', '278656223', 'Sumarno', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9469, 4, '6285341113988', 'MAYANG CS', '278656169', 'Aip.A Arfah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9470, 4, '6283812854337', 'MAYANG CS', '278655776', 'Olga Aer Tutu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9471, 4, '6285352657553', 'MAYANG CS', '278655688', 'Seni sualang', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9472, 4, '6282147918366', 'FERI CS', '278655396', 'Kesumajaya Kesumajaya', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9473, 4, '6285282298626', 'FERI CS', '278655242', 'Ismael', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9474, 4, '6285240222421', 'FERI CS', '278655191', 'Evans Biasa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9475, 4, '628161396577', 'MAYANG CS', '278655131', 'Gloria', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9476, 4, '6281242586162', 'FERI CS', '278655006', 'Nur Afni', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9477, 4, '6285263878851', 'MAYANG CS', '278654993', 'Erlida Mita', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9478, 4, '628119111579', 'MAYANG CS', '278654974', 'Sandy', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9479, 4, '6285850354555', 'MAYANG CS', '278654752', 'Lani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9480, 4, '6288289975780', 'FERI CS', '278654671', 'Sasmita Icas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9481, 4, '6285921871890', 'FERI CS', '278654436', 'Muhamad gobel', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9482, 4, '6281356076277', 'MAYANG CS', '278654257', 'khairul hamid', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9483, 4, '6285365387237', 'MAYANG CS', '278654123', 'Danri', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9484, 4, '6282271628209', 'MAYANG CS', '278654072', 'Seila Nadhila', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9485, 4, '6282189809948', 'FERI CS', '278653621', 'Silvia lowing', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9486, 4, '6281420552348', 'MAYANG CS', '278653431', 'Adel Sarayar', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9487, 4, '6282122583845', 'FERI CS', '278653424', 'Fauji', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9488, 4, '628126991747', 'FERI CS', '278652773', 'Hakim', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9489, 4, '6285256433739', 'MAYANG CS', '278652689', 'Irwan mokoagow', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9490, 4, '6285343635644', 'FERI CS', '278652212', 'H.Harudfin', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9491, 4, '6281216113619', 'FERI CS', '278652170', 'M ichsan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9492, 4, '6281362374444', 'MAYANG CS', '278652152', 'Anta G', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9493, 4, '6285246770145', 'MAYANG CS', '278651999', 'Robi awondatu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9494, 4, '6281244818020', 'FERI CS', '278651992', 'Elisabeth Renden', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9495, 4, '628114328004', 'FERI CS', '278651833', 'Alter Londo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9496, 4, '6281375921621', 'MAYANG CS', '278651799', 'Tetny.silalahi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9497, 4, '6281343052780', 'MAYANG CS', '278651495', 'Maikel', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9498, 4, '628161643001', 'FERI CS', '278651306', 'Fahry Fahry', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9499, 4, '6281320572787', 'FERI CS', '278651245', 'Apip Saepuloh', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9500, 4, '6281355025566', 'MAYANG CS', '278651135', 'Kaka', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9501, 4, '6281349130416', 'FERI CS', '278650853', 'Lista', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9502, 4, '6281347480704', 'MAYANG CS', '278650801', 'Andrias Pongoh', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9503, 4, '6281349435212', 'MAYANG CS', '278650797', 'Kusnun Kusnun', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9504, 4, '6285824850589', 'MAYANG CS', '278650324', 'Yosep Gunawan', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9505, 4, '62836086436', 'MAYANG CS', '278650178', 'Meylan Mokorowu', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9506, 4, '6285242027917', 'FERI CS', '278650097', 'Chali Supa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9507, 4, '628124407692', 'FERI CS', '278649821', 'Sandjaja Achmad', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9508, 4, '6282145273556', 'MAYANG CS', '278649755', 'A A NGURAH MEGANAFA', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9509, 4, '6282189962552', 'FERI CS', '278649605', 'SUKIMAN', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9510, 4, '6281337275735', 'FERI CS', '278649176', 'Igusti Ngurah', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9511, 4, '6285380550392', 'FERI CS', '278648633', 'Diaz Widiarto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9512, 4, '6282187950367', 'FERI CS', '278648434', 'jantiana loho', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9513, 4, '6282346404167', 'MAYANG CS', '278648415', 'Aning Latodjo', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9514, 4, '628152001599', 'MAYANG CS', '278648395', 'Papi Danie', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9515, 4, '628114117554', 'FERI CS', '278648374', 'LAUDDIN MARSUNI', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9516, 4, '6282321214672', 'MAYANG CS', '278648369', 'Fitri / mamah ragil', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9517, 4, '6281356088688', 'MAYANG CS', '278647844', 'Edvaard Makapuas', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9518, 4, '6281337381264', 'FERI CS', '278647508', 'Benny Kopong', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9519, 4, '6282224493872', 'FERI CS', '278647464', 'Sri Sundari 50,35', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9520, 4, '628137964095', 'MAYANG CS', '278647183', 'Norita Ervi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9521, 4, '6285173015434', 'FERI CS', '278647116', 'Yusuf', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9522, 4, '6281237550633', 'MAYANG CS', '278646998', 'Putra Arjana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9523, 4, '6282175977563', 'MAYANG CS', '278646739', 'Ahmad Pauzi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9524, 4, '6285361750710', 'FERI CS', '278646666', 'Kamiswanto', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9525, 4, '6282167719542', 'FERI CS', '278646556', 'sarman', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9526, 4, '62881012515231', 'FERI CS', '278646473', 'Rizal Ujee', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9527, 4, '6289601290961', 'FERI CS', '278646358', 'Budi kristian', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9528, 4, '628159190028', 'MAYANG CS', '278646173', 'Takhani', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9529, 4, '6285349309829', 'MAYANG CS', '278645979', 'Mulyadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9530, 4, '628786298163', 'MAYANG CS', '278645909', 'Nyoman Dharsana', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9531, 4, '6282123426613', 'FERI CS', '278645806', 'Pak Simusa', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9532, 4, '6281932300628', 'MAYANG CS', '278645548', 'H. Dudung A. Hadi', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9533, 4, '6281368030067', 'MAYANG CS', '278645502', 'Syamsulhilal', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9534, 4, '6285364665586', 'MAYANG CS', '278645376', 'Febriliana-Sujiman', '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(9535, 4, '6287877872763', 'FERI CS', '278645341', 'Uti', '2026-08-14 02:07:41', '2026-08-14 02:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `order_online_import_batches`
--

CREATE TABLE `order_online_import_batches` (
  `id` bigint UNSIGNED NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `processed_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `success_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `failed_rows` int UNSIGNED NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_online_import_batches`
--

INSERT INTO `order_online_import_batches` (`id`, `original_filename`, `stored_path`, `sender`, `status`, `total_rows`, `processed_rows`, `success_rows`, `failed_rows`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 'test status.csv', '/var/www/awannaweb/storage/app/private/order-online/RGm7x2g2mH5M1ZNCz7xVaz5WbQTkmLXpealXZxNx.csv', 'eresgeh', 'completed', 11, 11, 11, 0, NULL, '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(2, 'orderonline_orders_11-08-2026_bv2CcenGFl8x2DjgPqi.csv', '/var/www/awannaweb/storage/app/private/order-online/4zuk0FZ0AoxISegYBxV9jevCUZmXJbA6GTMHejbP.csv', 'MIRACLE STORE', 'completed', 20, 20, 20, 0, NULL, '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(3, 'orderonline_orders_11-08-2026_bv2CcenGFl8x2DjgPqi.csv', '/var/www/awannaweb/storage/app/private/order-online/9tVtmjsnRpyijh0w2ACkuui0vZlUKke2I1SJNoKt.csv', 'ERESGE', 'completed', 20, 20, 0, 20, NULL, '2026-08-11 05:05:11', '2026-08-11 05:05:11');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(2, 'supplier.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(3, 'supplier.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(4, 'supplier.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(5, 'supplier.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(6, 'produk.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(7, 'produk.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(8, 'produk.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(9, 'produk.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(10, 'whitelist.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(11, 'whitelist.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(12, 'whitelist.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(13, 'whitelist.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(14, 'spending.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(15, 'spending.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(16, 'spending.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(17, 'spending.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(18, 'spending.approve', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(19, 'user.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(20, 'user.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(21, 'user.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(22, 'user.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(23, 'role.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(24, 'role.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(25, 'role.edit', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(26, 'role.delete', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(27, 'laporan.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(28, 'laporan.export', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(29, 'topup.view', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(30, 'topup.create', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(31, 'topup.approve', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15'),
(32, 'topup.pay', 'web', '2026-08-10 22:33:15', '2026-08-10 22:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `inventory_id` bigint UNSIGNED DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `category`, `description`, `inventory_id`, `purchase_price`, `selling_price`, `unit`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'KMP', 'Kacamata Multifokus Photocromic', 'Kacamata', 'Kacamata multifokus dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya.', 1, 20000.00, 119000.00, 'Pcs', 'active', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(2, 'KSP', 'Kacamata Sporty Photocromic', 'Kacamata', 'Kacamata sporty dengan lensa photochromic yang menyesuaikan kegelapan sesuai cahaya.', 1, 20000.00, 119000.00, 'Pcs', 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17', NULL),
(3, 'KBJ', 'Kacamata Baca & Jalan', 'Kacamata', 'Kacamata baca dan jalan dengan lensa multifokus untuk kenyamanan pengguna.', 1, 24666.39, 119000.00, 'Pcs', 'active', '2026-08-10 22:33:17', '2026-08-11 04:36:16', NULL),
(4, 'KCHP', 'Kabel Casan Hp 3IN1', 'Aksesoris', 'Kabel casan HP 3 in 1 yang kompatibel dengan berbagai merek smartphone.', 1, 5000.00, 25000.00, 'Pcs', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(5, 'SH', 'Shendara Herbal', 'Herbal', 'Lulur Kaki Herbal Shendara dengan bahan alami untuk perawatan kulit kaki yang lembut dan sehat.', 1, 3000.00, 110000.00, 'Sachet', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(6, 'KNGH', 'Kreain Nature Gel Herbal', 'Herbal', 'Gel herbal Kreain Nature dengan bahan alami untuk kesehatan dan perawatan tubuh.', 1, 8000.00, 45000.00, 'Pcs', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(7, 'KDF', 'Kacamata Double Fokus', 'Kacamata', 'Kacamata double fokus pendamping Kacamata Baca & Jalan (KBJ), dikirim bersama untuk kombinasi yang lengkap.', 1, 25000.00, 119000.00, 'Pcs', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(8, 'BOX', 'Box Kacamata', 'Aksesoris', 'Box kemasan kacamata yang otomatis berkurang saat kacamata terkirim (1 box per 2 pcs).', 1, 2000.00, 5000.00, 'Pcs', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(9, 'LAP', 'Lap Pembersih', 'Aksesoris', 'Lap pembersih kacamata yang otomatis berkurang saat kacamata terkirim (1 lap per 2 pcs).', 1, 1500.00, 5000.00, 'Pcs', 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18', NULL),
(10, 'PRD-02', 'KACAMATA BACA & JALAN ANTI RADIASI', 'KACAMATA', NULL, 5, 2900.00, 119000.00, 'pcs', 'active', '2026-08-11 04:52:18', '2026-08-11 04:52:18', NULL),
(11, 'KABEJE', 'Kacamata Baca & Jalan', 'Aksesori', NULL, 6, 12000.00, 120000.00, '212 pcs', 'active', '2026-08-11 04:56:07', '2026-08-11 12:08:20', '2026-08-11 12:08:20'),
(12, 'Polarized', 'Kacamata Polarized', 'Kacamata', NULL, 5, 15800.00, 119000.00, '183', 'active', '2026-08-11 05:34:47', '2026-08-11 05:34:47', NULL),
(13, 'GSG', 'GoSugar', 'Herbal', 'HPP masih dummy', 1, 10000.00, 129000.00, 'pcs', 'active', '2026-08-12 01:43:57', '2026-08-12 01:43:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `power` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `code`, `name`, `jenis`, `stock`, `power`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'KMP+1', 'Plus +1,00', 'ukuran', 103, 1.00, 'active', '2026-08-10 22:33:16', '2026-08-11 05:00:10'),
(2, 1, 'KMP+1.25', 'Plus +1,25', 'ukuran', 111, 1.25, 'active', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(3, 1, 'KMP+1.5', 'Plus +1,50', 'ukuran', 111, 1.50, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(4, 1, 'KMP+1.75', 'Plus +1,75', 'ukuran', 111, 1.75, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(5, 1, 'KMP+2', 'Plus +2,00', 'ukuran', 107, 2.00, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:04'),
(6, 1, 'KMP+2.25', 'Plus +2,25', 'ukuran', 107, 2.25, 'active', '2026-08-10 22:33:17', '2026-08-11 04:58:35'),
(7, 1, 'KMP+2.5', 'Plus +2,50', 'ukuran', 111, 2.50, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(8, 1, 'KMP+2.75', 'Plus +2,75', 'ukuran', 109, 2.75, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:04'),
(9, 1, 'KMP+3', 'Plus +3,00', 'ukuran', 111, 3.00, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(10, 2, 'KSP+1', 'Plus +1,00', 'ukuran', 109, 1.00, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:03'),
(11, 2, 'KSP+1.25', 'Plus +1,25', 'ukuran', 109, 1.25, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:03'),
(12, 2, 'KSP+1.5', 'Plus +1,50', 'ukuran', 111, 1.50, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(13, 2, 'KSP+1.75', 'Plus +1,75', 'ukuran', 109, 1.75, 'active', '2026-08-10 22:33:17', '2026-08-11 04:38:19'),
(14, 2, 'KSP+2', 'Plus +2,00', 'ukuran', 107, 2.00, 'active', '2026-08-10 22:33:17', '2026-08-11 05:00:10'),
(15, 2, 'KSP+2.25', 'Plus +2,25', 'ukuran', 111, 2.25, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(16, 2, 'KSP+2.5', 'Plus +2,50', 'ukuran', 109, 2.50, 'active', '2026-08-10 22:33:17', '2026-08-11 05:00:10'),
(17, 2, 'KSP+2.75', 'Plus +2,75', 'ukuran', 109, 2.75, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:03'),
(18, 2, 'KSP+3', 'Plus +3,00', 'ukuran', 109, 3.00, 'active', '2026-08-10 22:33:17', '2026-08-11 04:58:36'),
(19, 3, 'KBJ+1', 'Plus +1,00', 'ukuran', 310, 1.00, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:04'),
(20, 3, 'KBJ+1.25', 'Plus +1,25', 'ukuran', 110, 1.25, 'active', '2026-08-10 22:33:17', '2026-08-11 05:00:10'),
(21, 3, 'KBJ+1.5', 'Plus +1,50', 'ukuran', 111, 1.50, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(22, 3, 'KBJ+1.75', 'Plus +1,75', 'ukuran', 111, 1.75, 'active', '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(23, 3, 'KBJ+2', 'Plus +2,00', 'ukuran', 108, 2.00, 'active', '2026-08-10 22:33:17', '2026-08-11 05:07:44'),
(24, 3, 'KBJ+2.25', 'Plus +2,25', 'ukuran', 110, 2.25, 'active', '2026-08-10 22:33:17', '2026-08-11 05:06:03'),
(25, 3, 'KBJ+2.5', 'Plus +2,50', 'ukuran', 111, 2.50, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(26, 3, 'KBJ+2.75', 'Plus +2,75', 'ukuran', 111, 2.75, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(27, 3, 'KBJ+3', 'Plus +3,00', 'ukuran', 111, 3.00, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(28, 4, 'KCHP', 'Kabel Casan Hp 3IN1', NULL, 1000, 0.00, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(29, 5, 'SH', 'Shendara Herbal', NULL, 491, 0.00, 'active', '2026-08-10 22:33:18', '2026-08-11 04:38:18'),
(30, 6, 'KNGH', 'Kreain Nature Gel Herbal', NULL, 500, 0.00, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(31, 7, 'KDF+1', 'Plus +1,00', 'ukuran', 110, 1.00, 'active', '2026-08-10 22:33:18', '2026-08-11 05:06:04'),
(32, 7, 'KDF+1.25', 'Plus +1,25', 'ukuran', 110, 1.25, 'active', '2026-08-10 22:33:18', '2026-08-11 05:00:10'),
(33, 7, 'KDF+1.5', 'Plus +1,50', 'ukuran', 111, 1.50, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(34, 7, 'KDF+1.75', 'Plus +1,75', 'ukuran', 111, 1.75, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(35, 7, 'KDF+2', 'Plus +2,00', 'ukuran', 108, 2.00, 'active', '2026-08-10 22:33:18', '2026-08-11 05:07:44'),
(36, 7, 'KDF+2.25', 'Plus +2,25', 'ukuran', 110, 2.25, 'active', '2026-08-10 22:33:18', '2026-08-11 05:06:03'),
(37, 7, 'KDF+2.5', 'Plus +2,50', 'ukuran', 111, 2.50, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(38, 7, 'KDF+2.75', 'Plus +2,75', 'ukuran', 111, 2.75, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(39, 7, 'KDF+3', 'Plus +3,00', 'ukuran', 111, 3.00, 'active', '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(40, 8, 'BOX', 'Box Kacamata', NULL, 977, 0.00, 'active', '2026-08-10 22:33:18', '2026-08-11 05:07:44'),
(41, 9, 'LAP', 'Lap Pembersih', NULL, 977, 0.00, 'active', '2026-08-10 22:33:18', '2026-08-11 05:07:44'),
(42, 10, 'KBAT+1.00', 'PLUS+1,00', 'UKURAN', 40, 1.00, 'active', '2026-08-11 04:54:19', '2026-08-11 04:55:18');

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_items`
--

CREATE TABLE `product_variant_items` (
  `id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `komponen_id` bigint UNSIGNED NOT NULL,
  `qty` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `date`, `supplier_id`, `product_variant_id`, `quantity`, `unit_price`, `shipping_cost`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-08-11', NULL, 19, 200, 23000.00, 0.00, NULL, 3, '2026-08-11 04:36:16', '2026-08-11 04:36:16'),
(2, '2026-08-11', 7, 42, 20, 2900.00, 0.00, NULL, 3, '2026-08-11 04:55:17', '2026-08-11 04:55:17');

-- --------------------------------------------------------

--
-- Table structure for table `regional_cs_stats`
--

CREATE TABLE `regional_cs_stats` (
  `id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `cs_panggilan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cs_user_id` bigint UNSIGNED DEFAULT NULL,
  `lead` int NOT NULL DEFAULT '0',
  `paid` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regional_cs_stats`
--

INSERT INTO `regional_cs_stats` (`id`, `tanggal`, `user_id`, `cs_panggilan`, `cs_user_id`, `lead`, `paid`, `created_at`, `updated_at`) VALUES
(1, '2026-08-01', 6, 'OPUS CS', 10, 3, 2, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(2, '2026-08-01', 6, 'MUKLAS CS', 15, 14, 11, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(3, '2026-08-01', 6, 'PUTRI CS', 14, 8, 6, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(4, '2026-08-02', 6, 'OPUS CS', 10, 6, 5, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(5, '2026-08-03', 6, 'OPUS CS', 10, 42, 17, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(6, '2026-08-03', 6, 'PUTRI CS', 14, 12, 1, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(7, '2026-08-03', 6, 'MUKLAS CS', 15, 24, 18, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(8, '2026-08-04', 6, 'OPUS CS', 10, 95, 39, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(9, '2026-08-04', 6, 'feri CS', 12, 12, 7, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(10, '2026-08-05', 6, 'MUKLAS CS', 15, 30, 9, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(11, '2026-08-05', 6, 'OPUS CS', 10, 37, 9, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(12, '2026-08-06', 6, 'OPUS CS', 10, 26, 4, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(13, '2026-08-06', 6, 'MUKLAS CS', 15, 21, 7, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(14, '2026-08-07', 6, 'MUKLAS CS', 15, 1, 1, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(15, '2026-08-07', 6, 'OPUS CS', 10, 15, 5, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(16, '2026-08-08', 6, 'OPUS CS', 10, 12, 4, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(17, '2026-08-08', 6, 'MUKLAS CS', 15, 2, 1, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(18, '2026-08-09', 6, 'OPUS CS', 10, 32, 8, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(19, '2026-08-10', 6, 'OPUS CS', 10, 39, 10, '2026-08-10 23:39:37', '2026-08-12 01:53:05'),
(20, '2026-08-01', 7, 'MUKLAS CS', 15, 56, 42, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(21, '2026-08-11', 6, 'OPUS CS', 10, 49, 12, '2026-08-11 04:17:56', '2026-08-12 01:53:05'),
(22, '2026-08-11', 6, 'PUTRI CS', 14, 7, 3, '2026-08-11 04:17:56', '2026-08-12 01:53:05'),
(23, '2026-08-11', 6, 'MUKLAS CS', 15, 9, 3, '2026-08-11 04:17:56', '2026-08-12 01:53:05'),
(24, '2026-08-01', 4, 'FERI CS', 12, 266, 223, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(25, '2026-08-01', 4, 'MAYANG CS', 13, 114, 100, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(26, '2026-08-02', 4, 'MAYANG CS', 13, 199, 169, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(27, '2026-08-02', 4, 'FERI CS', 12, 194, 161, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(28, '2026-08-03', 4, 'FERI CS', 12, 167, 138, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(29, '2026-08-03', 4, 'MAYANG CS', 13, 165, 143, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(30, '2026-08-04', 4, 'MAYANG CS', 13, 213, 173, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(31, '2026-08-04', 4, 'FERI CS', 12, 223, 183, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(32, '2026-08-05', 4, 'FERI CS', 12, 191, 156, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(33, '2026-08-05', 4, 'MAYANG CS', 13, 194, 156, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(34, '2026-08-06', 4, 'FERI CS', 12, 179, 144, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(35, '2026-08-06', 4, 'MAYANG CS', 13, 179, 146, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(36, '2026-08-07', 4, 'FERI CS', 12, 179, 146, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(37, '2026-08-07', 4, 'MAYANG CS', 13, 172, 137, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(38, '2026-08-08', 4, 'FERI CS', 12, 204, 163, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(39, '2026-08-08', 4, 'MAYANG CS', 13, 203, 166, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(40, '2026-08-09', 4, 'MAYANG CS', 13, 204, 166, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(41, '2026-08-09', 4, 'FERI CS', 12, 197, 151, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(42, '2026-08-10', 4, 'MAYANG CS', 13, 322, 265, '2026-08-11 11:53:32', '2026-08-11 14:40:52'),
(43, '2026-08-10', 4, 'FERI CS', 12, 11, 8, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(44, '2026-08-02', 7, 'MUKLAS CS', 15, 58, 45, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(45, '2026-08-02', 7, 'OPUS CS', 10, 2, 0, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(46, '2026-08-03', 7, 'MUKLAS CS', 15, 50, 41, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(47, '2026-08-04', 7, 'MUKLAS CS', 15, 37, 27, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(48, '2026-08-05', 7, 'MUKLAS CS', 15, 51, 42, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(49, '2026-08-06', 7, 'MUKLAS CS', 15, 38, 30, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(50, '2026-08-07', 7, 'MUKLAS CS', 15, 59, 43, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(51, '2026-08-01', 5, 'PUTRI CS', 14, 98, 88, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(52, '2026-08-01', 5, 'ASEP PACE CS', 11, 96, 85, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(53, '2026-08-02', 5, 'ASEP PACE CS', 11, 103, 89, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(54, '2026-08-02', 5, 'PUTRI CS', 14, 103, 88, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(55, '2026-08-03', 5, 'ASEP PACE CS', 11, 86, 80, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(56, '2026-08-03', 5, 'PUTRI CS', 14, 86, 72, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(57, '2026-08-04', 5, 'PUTRI CS', 14, 98, 76, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(58, '2026-08-04', 5, 'ASEP PACE CS', 11, 99, 81, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(59, '2026-08-05', 5, 'PUTRI CS', 14, 89, 68, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(60, '2026-08-05', 5, 'ASEP PACE CS', 11, 90, 74, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(61, '2026-08-06', 5, 'ASEP PACE CS', 11, 123, 102, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(62, '2026-08-06', 5, 'PUTRI CS', 14, 122, 93, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(63, '2026-08-07', 5, 'PUTRI CS', 14, 110, 80, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(64, '2026-08-07', 5, 'ASEP PACE CS', 11, 116, 87, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(65, '2026-08-08', 5, 'ASEP PACE CS', 11, 134, 101, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(66, '2026-08-08', 5, 'PUTRI CS', 14, 135, 111, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(67, '2026-08-09', 5, 'ASEP PACE CS', 11, 141, 107, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(68, '2026-08-09', 5, 'PUTRI CS', 14, 135, 111, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(69, '2026-08-10', 5, 'ASEP PACE CS', 11, 88, 64, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(70, '2026-08-10', 5, 'PUTRI CS', 14, 98, 79, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(71, '2026-08-11', 5, 'PUTRI CS', 14, 148, 118, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(72, '2026-08-11', 5, 'ASEP PACE CS', 11, 152, 121, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(73, '2026-08-12', 6, 'MUKLAS CS', 15, 31, 20, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(74, '2026-08-12', 6, 'OPUS CS', 10, 37, 17, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(75, '2026-08-12', 5, 'PUTRI CS', 14, 167, 132, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(76, '2026-08-12', 5, 'ASEP PACE CS', 11, 160, 129, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(77, '2026-08-11', 4, 'MAYANG CS', 13, 326, 271, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(78, '2026-08-12', 4, 'FERI CS', 12, 239, 206, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(79, '2026-08-12', 4, 'MAYANG CS', 13, 96, 73, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(80, '2026-08-13', 4, 'FERI CS', 12, 188, 146, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(81, '2026-08-13', 4, 'MAYANG CS', 13, 189, 139, '2026-08-14 02:07:41', '2026-08-14 02:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `regional_reports`
--

CREATE TABLE `regional_reports` (
  `id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lead` int NOT NULL DEFAULT '0',
  `paid` int NOT NULL DEFAULT '0',
  `paid_ratio` decimal(5,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regional_reports`
--

INSERT INTO `regional_reports` (`id`, `tanggal`, `user_id`, `province`, `lead`, `paid`, `paid_ratio`, `catatan`, `created_at`, `updated_at`) VALUES
(1, '2026-08-01', 6, 'BALI', 2, 1, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(2, '2026-08-01', 6, 'BANTEN', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(3, '2026-08-01', 6, 'BENGKULU', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(4, '2026-08-01', 6, 'DKI JAKARTA', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(5, '2026-08-01', 6, 'JAMBI', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(6, '2026-08-01', 6, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(7, '2026-08-01', 6, 'JAWA TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(8, '2026-08-01', 6, 'JAWA TIMUR', 4, 2, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(9, '2026-08-01', 6, 'KALIMANTAN TIMUR', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(10, '2026-08-01', 6, 'NUSA TENGGARA BARAT (NTB)', 3, 3, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(11, '2026-08-01', 6, 'SULAWESI SELATAN', 4, 3, 75.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(12, '2026-08-01', 6, 'SUMATRA BARAT', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(13, '2026-08-01', 6, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(14, '2026-08-02', 6, 'BANTEN', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(15, '2026-08-02', 6, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(16, '2026-08-02', 6, 'JAWA TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(17, '2026-08-02', 6, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(18, '2026-08-02', 6, 'SUMATRA UTARA', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(19, '2026-08-03', 6, 'BALI', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(20, '2026-08-03', 6, 'BANTEN', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(21, '2026-08-03', 6, 'JAMBI', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(22, '2026-08-03', 6, 'JAWA TIMUR', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(23, '2026-08-03', 6, 'KALIMANTAN SELATAN', 2, 1, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(24, '2026-08-03', 6, 'KALIMANTAN TIMUR', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(25, '2026-08-03', 6, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(26, '2026-08-03', 6, 'MALUKU', 18, 6, 33.33, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(27, '2026-08-03', 6, 'MALUKU UTARA', 7, 2, 28.57, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(28, '2026-08-03', 6, 'NUSA TENGGARA TIMUR (NTT)', 11, 5, 45.45, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(29, '2026-08-03', 6, 'PAPUA', 23, 12, 52.17, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(30, '2026-08-03', 6, 'PAPUA BARAT', 6, 3, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(31, '2026-08-03', 6, 'SULAWESI SELATAN', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(32, '2026-08-03', 6, 'SULAWESI TENGGARA', 2, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(33, '2026-08-03', 6, 'SULAWESI UTARA', 4, 3, 75.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(34, '2026-08-04', 6, 'BALI', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(35, '2026-08-04', 6, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(36, '2026-08-04', 6, 'BANTEN', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(37, '2026-08-04', 6, 'BENGKULU', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(38, '2026-08-04', 6, 'DKI JAKARTA', 4, 3, 75.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(39, '2026-08-04', 6, 'GORONTALO', 4, 2, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(40, '2026-08-04', 6, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(41, '2026-08-04', 6, 'JAWA TIMUR', 2, 1, 50.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(42, '2026-08-04', 6, 'KALIMANTAN TIMUR', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(43, '2026-08-04', 6, 'KALIMANTAN UTARA', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(44, '2026-08-04', 6, 'MALUKU', 8, 3, 37.50, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(45, '2026-08-04', 6, 'MALUKU UTARA', 8, 1, 12.50, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(46, '2026-08-04', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(47, '2026-08-04', 6, 'NUSA TENGGARA TIMUR (NTT)', 15, 6, 40.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(48, '2026-08-04', 6, 'PAPUA', 16, 4, 25.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(49, '2026-08-04', 6, 'PAPUA BARAT', 6, 1, 16.67, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(50, '2026-08-04', 6, 'SULAWESI BARAT', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(51, '2026-08-04', 6, 'SULAWESI SELATAN', 3, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(52, '2026-08-04', 6, 'SULAWESI TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(53, '2026-08-04', 6, 'SULAWESI TENGGARA', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(54, '2026-08-04', 6, 'SULAWESI UTARA', 24, 13, 54.17, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(55, '2026-08-04', 6, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(56, '2026-08-04', 6, 'SUMATRA UTARA', 3, 2, 66.67, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(57, '2026-08-05', 6, 'DKI JAKARTA', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(58, '2026-08-05', 6, 'GORONTALO', 3, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(59, '2026-08-05', 6, 'JAWA TENGAH', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(60, '2026-08-05', 6, 'JAWA TIMUR', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(61, '2026-08-05', 6, 'MALUKU', 5, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(62, '2026-08-05', 6, 'MALUKU UTARA', 9, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(63, '2026-08-05', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(64, '2026-08-05', 6, 'NUSA TENGGARA BARAT (NTB)', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(65, '2026-08-05', 6, 'NUSA TENGGARA TIMUR (NTT)', 5, 3, 60.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(66, '2026-08-05', 6, 'PAPUA', 16, 2, 12.50, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(67, '2026-08-05', 6, 'PAPUA BARAT', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(68, '2026-08-05', 6, 'RIAU', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(69, '2026-08-05', 6, 'SULAWESI BARAT', 2, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(70, '2026-08-05', 6, 'SULAWESI SELATAN', 4, 3, 75.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(71, '2026-08-05', 6, 'SULAWESI TENGAH', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(72, '2026-08-05', 6, 'SULAWESI UTARA', 12, 4, 33.33, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(73, '2026-08-05', 6, 'SUMATRA UTARA', 2, 2, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(74, '2026-08-06', 6, 'BANTEN', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(75, '2026-08-06', 6, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(76, '2026-08-06', 6, 'DKI JAKARTA', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(77, '2026-08-06', 6, 'GORONTALO', 1, 0, 0.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(78, '2026-08-06', 6, 'JAMBI', 1, 1, 100.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(79, '2026-08-06', 6, 'MALUKU', 5, 1, 20.00, NULL, '2026-08-10 23:39:36', '2026-08-10 23:39:36'),
(80, '2026-08-06', 6, 'MALUKU UTARA', 4, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(81, '2026-08-06', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(82, '2026-08-06', 6, 'NUSA TENGGARA TIMUR (NTT)', 4, 2, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(83, '2026-08-06', 6, 'PAPUA', 14, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(84, '2026-08-06', 6, 'PAPUA BARAT', 3, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(85, '2026-08-06', 6, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(86, '2026-08-06', 6, 'SULAWESI TENGAH', 2, 1, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(87, '2026-08-06', 6, 'SULAWESI UTARA', 7, 3, 42.86, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(88, '2026-08-06', 6, 'SUMATRA UTARA', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(89, '2026-08-07', 6, 'KALIMANTAN TIMUR', 3, 3, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(90, '2026-08-07', 6, 'KEPULAUAN RIAU', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(91, '2026-08-07', 6, 'MALUKU UTARA', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(92, '2026-08-07', 6, 'PAPUA', 4, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(93, '2026-08-07', 6, 'RIAU', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(94, '2026-08-07', 6, 'SULAWESI SELATAN', 2, 1, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(95, '2026-08-07', 6, 'SULAWESI TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(96, '2026-08-07', 6, 'SULAWESI TENGGARA', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(97, '2026-08-07', 6, 'SULAWESI UTARA', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(98, '2026-08-07', 6, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(99, '2026-08-08', 6, 'BALI', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(100, '2026-08-08', 6, 'JAWA TIMUR', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(101, '2026-08-08', 6, 'KALIMANTAN TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(102, '2026-08-08', 6, 'KALIMANTAN TIMUR', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(103, '2026-08-08', 6, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(104, '2026-08-08', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(105, '2026-08-08', 6, 'RIAU', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(106, '2026-08-08', 6, 'SULAWESI TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(107, '2026-08-08', 6, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(108, '2026-08-08', 6, 'SUMATRA SELATAN', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(109, '2026-08-08', 6, 'SUMATRA UTARA', 3, 1, 33.33, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(110, '2026-08-09', 6, 'BALI', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(111, '2026-08-09', 6, 'BENGKULU', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(112, '2026-08-09', 6, 'KALIMANTAN BARAT', 2, 1, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(113, '2026-08-09', 6, 'KALIMANTAN SELATAN', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(114, '2026-08-09', 6, 'KALIMANTAN TENGAH', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(115, '2026-08-09', 6, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(116, '2026-08-09', 6, 'LAMPUNG', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(117, '2026-08-09', 6, 'MALUKU UTARA', 5, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(118, '2026-08-09', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(119, '2026-08-09', 6, 'NUSA TENGGARA BARAT (NTB)', 2, 1, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(120, '2026-08-09', 6, 'NUSA TENGGARA TIMUR (NTT)', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(121, '2026-08-09', 6, 'SULAWESI BARAT', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(122, '2026-08-09', 6, 'SULAWESI SELATAN', 4, 1, 25.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(123, '2026-08-09', 6, 'SULAWESI TENGAH', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(124, '2026-08-09', 6, 'SULAWESI TENGGARA', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(125, '2026-08-09', 6, 'SULAWESI UTARA', 5, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(126, '2026-08-09', 6, 'SUMATRA UTARA', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(127, '2026-08-10', 6, 'BALI', 2, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(128, '2026-08-10', 6, 'DKI JAKARTA', 3, 2, 66.67, NULL, '2026-08-10 23:39:37', '2026-08-12 01:53:04'),
(129, '2026-08-10', 6, 'KALIMANTAN TIMUR', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(130, '2026-08-10', 6, 'MALUKU UTARA', 9, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-12 01:53:04'),
(131, '2026-08-10', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(132, '2026-08-10', 6, 'NUSA TENGGARA TIMUR (NTT)', 6, 1, 16.67, NULL, '2026-08-10 23:39:37', '2026-08-11 04:17:56'),
(133, '2026-08-10', 6, 'SULAWESI BARAT', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(134, '2026-08-10', 6, 'SULAWESI SELATAN', 1, 0, 0.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(135, '2026-08-10', 6, 'SULAWESI TENGAH', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(136, '2026-08-10', 6, 'SULAWESI TENGGARA', 2, 1, 50.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(137, '2026-08-10', 6, 'SULAWESI UTARA', 11, 4, 36.36, NULL, '2026-08-10 23:39:37', '2026-08-11 04:17:56'),
(138, '2026-08-10', 6, 'SUMATRA UTARA', 1, 1, 100.00, NULL, '2026-08-10 23:39:37', '2026-08-10 23:39:37'),
(139, '2026-08-01', 7, 'BALI', 2, 2, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(140, '2026-08-01', 7, 'BANTEN', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(141, '2026-08-01', 7, 'BENGKULU', 1, 0, 0.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(142, '2026-08-01', 7, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(143, '2026-08-01', 7, 'JAWA TENGAH', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(144, '2026-08-01', 7, 'JAWA TIMUR', 3, 2, 66.67, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(145, '2026-08-01', 7, 'KALIMANTAN BARAT', 2, 2, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(146, '2026-08-01', 7, 'KALIMANTAN SELATAN', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(147, '2026-08-01', 7, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(148, '2026-08-01', 7, 'KALIMANTAN TIMUR', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(149, '2026-08-01', 7, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(150, '2026-08-01', 7, 'LAMPUNG', 1, 0, 0.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(151, '2026-08-01', 7, 'NANGGROE ACEH DARUSSALAM (NAD)', 4, 4, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(152, '2026-08-01', 7, 'NUSA TENGGARA BARAT (NTB)', 1, 0, 0.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(153, '2026-08-01', 7, 'NUSA TENGGARA TIMUR (NTT)', 22, 18, 81.82, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(154, '2026-08-01', 7, 'RIAU', 2, 2, 100.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(155, '2026-08-01', 7, 'SULAWESI SELATAN', 3, 2, 66.67, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(156, '2026-08-01', 7, 'SULAWESI TENGAH', 2, 1, 50.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(157, '2026-08-01', 7, 'SULAWESI TENGGARA', 2, 1, 50.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(158, '2026-08-01', 7, 'SUMATRA SELATAN', 1, 0, 0.00, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(159, '2026-08-01', 7, 'SUMATRA UTARA', 3, 1, 33.33, NULL, '2026-08-11 04:06:43', '2026-08-11 04:06:43'),
(160, '2026-08-11', 6, 'JAMBI', 2, 1, 50.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(161, '2026-08-11', 6, 'JAWA BARAT', 2, 1, 50.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(162, '2026-08-11', 6, 'JAWA TIMUR', 2, 0, 0.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(163, '2026-08-11', 6, 'KALIMANTAN BARAT', 4, 1, 25.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(164, '2026-08-11', 6, 'KALIMANTAN SELATAN', 2, 0, 0.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(165, '2026-08-11', 6, 'KALIMANTAN TENGAH', 1, 0, 0.00, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(166, '2026-08-11', 6, 'KALIMANTAN TIMUR', 4, 1, 25.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(167, '2026-08-11', 6, 'KALIMANTAN UTARA', 1, 0, 0.00, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(168, '2026-08-11', 6, 'KEPULAUAN RIAU', 3, 1, 33.33, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(169, '2026-08-11', 6, 'LAMPUNG', 1, 1, 100.00, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(170, '2026-08-11', 6, 'MALUKU UTARA', 10, 1, 10.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(171, '2026-08-11', 6, 'NUSA TENGGARA TIMUR (NTT)', 9, 3, 33.33, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(172, '2026-08-11', 6, 'SULAWESI SELATAN', 3, 1, 33.33, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(173, '2026-08-11', 6, 'SULAWESI TENGAH', 1, 1, 100.00, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(174, '2026-08-11', 6, 'SULAWESI TENGGARA', 3, 1, 33.33, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(175, '2026-08-11', 6, 'SULAWESI UTARA', 12, 3, 25.00, NULL, '2026-08-11 04:17:56', '2026-08-12 01:53:04'),
(176, '2026-08-11', 6, 'SUMATRA SELATAN', 2, 0, 0.00, NULL, '2026-08-11 04:17:56', '2026-08-11 04:17:56'),
(177, '2026-08-01', 4, 'BALI', 21, 19, 90.48, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(178, '2026-08-01', 4, 'BANGKA BELITUNG', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(179, '2026-08-01', 4, 'BANTEN', 18, 16, 88.89, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(180, '2026-08-01', 4, 'BENGKULU', 6, 5, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(181, '2026-08-01', 4, 'DI YOGYAKARTA', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(182, '2026-08-01', 4, 'DKI JAKARTA', 33, 32, 96.97, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(183, '2026-08-01', 4, 'JAMBI', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(184, '2026-08-01', 4, 'JAWA BARAT', 52, 48, 92.31, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(185, '2026-08-01', 4, 'JAWA TENGAH', 26, 25, 96.15, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(186, '2026-08-01', 4, 'JAWA TIMUR', 23, 21, 91.30, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(187, '2026-08-01', 4, 'KALIMANTAN BARAT', 3, 3, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(188, '2026-08-01', 4, 'KALIMANTAN SELATAN', 7, 4, 57.14, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(189, '2026-08-01', 4, 'KALIMANTAN TENGAH', 5, 3, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(190, '2026-08-01', 4, 'KALIMANTAN TIMUR', 21, 17, 80.95, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(191, '2026-08-01', 4, 'KALIMANTAN UTARA', 3, 3, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(192, '2026-08-01', 4, 'KEPULAUAN RIAU', 11, 9, 81.82, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(193, '2026-08-01', 4, 'LAMPUNG', 13, 12, 92.31, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(194, '2026-08-01', 4, 'NUSA TENGGARA BARAT (NTB)', 14, 14, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(195, '2026-08-01', 4, 'NUSA TENGGARA TIMUR (NTT)', 10, 6, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(196, '2026-08-01', 4, 'RIAU', 17, 15, 88.24, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(197, '2026-08-01', 4, 'SULAWESI BARAT', 2, 1, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(198, '2026-08-01', 4, 'SULAWESI SELATAN', 33, 24, 72.73, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(199, '2026-08-01', 4, 'SULAWESI TENGAH', 6, 5, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(200, '2026-08-01', 4, 'SULAWESI TENGGARA', 6, 3, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(201, '2026-08-01', 4, 'SUMATRA BARAT', 9, 7, 77.78, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(202, '2026-08-01', 4, 'SUMATRA SELATAN', 16, 12, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(203, '2026-08-01', 4, 'SUMATRA UTARA', 11, 7, 63.64, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(204, '2026-08-02', 4, 'BALI', 18, 15, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(205, '2026-08-02', 4, 'BANGKA BELITUNG', 4, 3, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(206, '2026-08-02', 4, 'BANTEN', 19, 19, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(207, '2026-08-02', 4, 'BENGKULU', 5, 3, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(208, '2026-08-02', 4, 'DI YOGYAKARTA', 6, 6, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(209, '2026-08-02', 4, 'DKI JAKARTA', 21, 17, 80.95, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(210, '2026-08-02', 4, 'JAMBI', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(211, '2026-08-02', 4, 'JAWA BARAT', 41, 37, 90.24, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(212, '2026-08-02', 4, 'JAWA TENGAH', 25, 22, 88.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(213, '2026-08-02', 4, 'JAWA TIMUR', 25, 24, 96.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(214, '2026-08-02', 4, 'KALIMANTAN BARAT', 8, 7, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(215, '2026-08-02', 4, 'KALIMANTAN SELATAN', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(216, '2026-08-02', 4, 'KALIMANTAN TENGAH', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(217, '2026-08-02', 4, 'KALIMANTAN TIMUR', 26, 22, 84.62, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(218, '2026-08-02', 4, 'KEPULAUAN RIAU', 16, 14, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(219, '2026-08-02', 4, 'LAMPUNG', 7, 5, 71.43, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(220, '2026-08-02', 4, 'MALUKU', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(221, '2026-08-02', 4, 'NUSA TENGGARA BARAT (NTB)', 16, 13, 81.25, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(222, '2026-08-02', 4, 'RIAU', 21, 19, 90.48, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(223, '2026-08-02', 4, 'SULAWESI BARAT', 3, 1, 33.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(224, '2026-08-02', 4, 'SULAWESI SELATAN', 35, 27, 77.14, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(225, '2026-08-02', 4, 'SULAWESI TENGAH', 12, 8, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(226, '2026-08-02', 4, 'SULAWESI TENGGARA', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(227, '2026-08-02', 4, 'SUMATRA BARAT', 10, 7, 70.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(228, '2026-08-02', 4, 'SUMATRA SELATAN', 19, 13, 68.42, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(229, '2026-08-02', 4, 'SUMATRA UTARA', 27, 23, 85.19, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(230, '2026-08-03', 4, 'BALI', 16, 16, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(231, '2026-08-03', 4, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(232, '2026-08-03', 4, 'BANTEN', 15, 15, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(233, '2026-08-03', 4, 'BENGKULU', 8, 3, 37.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(234, '2026-08-03', 4, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(235, '2026-08-03', 4, 'DKI JAKARTA', 29, 29, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(236, '2026-08-03', 4, 'JAMBI', 7, 5, 71.43, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(237, '2026-08-03', 4, 'JAWA BARAT', 45, 41, 91.11, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(238, '2026-08-03', 4, 'JAWA TENGAH', 23, 21, 91.30, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(239, '2026-08-03', 4, 'JAWA TIMUR', 29, 25, 86.21, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(240, '2026-08-03', 4, 'KALIMANTAN BARAT', 9, 7, 77.78, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(241, '2026-08-03', 4, 'KALIMANTAN SELATAN', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(242, '2026-08-03', 4, 'KALIMANTAN TENGAH', 5, 5, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(243, '2026-08-03', 4, 'KALIMANTAN TIMUR', 18, 13, 72.22, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(244, '2026-08-03', 4, 'KALIMANTAN UTARA', 2, 2, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(245, '2026-08-03', 4, 'KEPULAUAN RIAU', 6, 4, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(246, '2026-08-03', 4, 'LAMPUNG', 10, 7, 70.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(247, '2026-08-03', 4, 'NUSA TENGGARA BARAT (NTB)', 16, 14, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(248, '2026-08-03', 4, 'NUSA TENGGARA TIMUR (NTT)', 1, 0, 0.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(249, '2026-08-03', 4, 'RIAU', 10, 8, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(250, '2026-08-03', 4, 'SULAWESI BARAT', 1, 0, 0.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(251, '2026-08-03', 4, 'SULAWESI SELATAN', 24, 21, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(252, '2026-08-03', 4, 'SULAWESI TENGAH', 12, 7, 58.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(253, '2026-08-03', 4, 'SULAWESI TENGGARA', 6, 3, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(254, '2026-08-03', 4, 'SUMATRA BARAT', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(255, '2026-08-03', 4, 'SUMATRA SELATAN', 14, 14, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(256, '2026-08-03', 4, 'SUMATRA UTARA', 18, 14, 77.78, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(257, '2026-08-04', 4, 'BALI', 18, 14, 77.78, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(258, '2026-08-04', 4, 'BANGKA BELITUNG', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(259, '2026-08-04', 4, 'BANTEN', 16, 15, 93.75, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(260, '2026-08-04', 4, 'BENGKULU', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(261, '2026-08-04', 4, 'DI YOGYAKARTA', 11, 10, 90.91, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(262, '2026-08-04', 4, 'DKI JAKARTA', 27, 24, 88.89, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(263, '2026-08-04', 4, 'JAMBI', 14, 11, 78.57, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(264, '2026-08-04', 4, 'JAWA BARAT', 61, 57, 93.44, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(265, '2026-08-04', 4, 'JAWA TENGAH', 30, 29, 96.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(266, '2026-08-04', 4, 'JAWA TIMUR', 23, 19, 82.61, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(267, '2026-08-04', 4, 'KALIMANTAN BARAT', 10, 10, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(268, '2026-08-04', 4, 'KALIMANTAN SELATAN', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(269, '2026-08-04', 4, 'KALIMANTAN TENGAH', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(270, '2026-08-04', 4, 'KALIMANTAN TIMUR', 15, 14, 93.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(271, '2026-08-04', 4, 'KEPULAUAN RIAU', 8, 6, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(272, '2026-08-04', 4, 'LAMPUNG', 11, 9, 81.82, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(273, '2026-08-04', 4, 'NUSA TENGGARA BARAT (NTB)', 15, 11, 73.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(274, '2026-08-04', 4, 'NUSA TENGGARA TIMUR (NTT)', 51, 32, 62.75, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(275, '2026-08-04', 4, 'RIAU', 23, 20, 86.96, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(276, '2026-08-04', 4, 'SULAWESI BARAT', 2, 1, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(277, '2026-08-04', 4, 'SULAWESI SELATAN', 26, 16, 61.54, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(278, '2026-08-04', 4, 'SULAWESI TENGAH', 9, 6, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(279, '2026-08-04', 4, 'SULAWESI TENGGARA', 5, 3, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(280, '2026-08-04', 4, 'SUMATRA BARAT', 7, 4, 57.14, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(281, '2026-08-04', 4, 'SUMATRA SELATAN', 18, 15, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(282, '2026-08-04', 4, 'SUMATRA UTARA', 16, 12, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(283, '2026-08-05', 4, 'BALI', 23, 20, 86.96, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(284, '2026-08-05', 4, 'BANGKA BELITUNG', 3, 2, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(285, '2026-08-05', 4, 'BANTEN', 20, 17, 85.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(286, '2026-08-05', 4, 'BENGKULU', 3, 2, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(287, '2026-08-05', 4, 'DI YOGYAKARTA', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(288, '2026-08-05', 4, 'DKI JAKARTA', 28, 23, 82.14, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(289, '2026-08-05', 4, 'JAMBI', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(290, '2026-08-05', 4, 'JAWA BARAT', 43, 41, 95.35, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(291, '2026-08-05', 4, 'JAWA TENGAH', 30, 28, 93.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(292, '2026-08-05', 4, 'JAWA TIMUR', 37, 32, 86.49, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(293, '2026-08-05', 4, 'KALIMANTAN BARAT', 15, 10, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(294, '2026-08-05', 4, 'KALIMANTAN SELATAN', 3, 3, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(295, '2026-08-05', 4, 'KALIMANTAN TENGAH', 2, 2, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(296, '2026-08-05', 4, 'KALIMANTAN TIMUR', 13, 9, 69.23, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(297, '2026-08-05', 4, 'KEPULAUAN RIAU', 5, 4, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(298, '2026-08-05', 4, 'LAMPUNG', 8, 7, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(299, '2026-08-05', 4, 'NUSA TENGGARA BARAT (NTB)', 15, 12, 80.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(300, '2026-08-05', 4, 'NUSA TENGGARA TIMUR (NTT)', 24, 16, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(301, '2026-08-05', 4, 'RIAU', 21, 14, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(302, '2026-08-05', 4, 'SULAWESI SELATAN', 32, 26, 81.25, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(303, '2026-08-05', 4, 'SULAWESI TENGAH', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(304, '2026-08-05', 4, 'SULAWESI TENGGARA', 7, 3, 42.86, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(305, '2026-08-05', 4, 'SUMATRA BARAT', 5, 2, 40.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(306, '2026-08-05', 4, 'SUMATRA SELATAN', 11, 7, 63.64, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(307, '2026-08-05', 4, 'SUMATRA UTARA', 21, 18, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(308, '2026-08-06', 4, 'BALI', 20, 19, 95.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(309, '2026-08-06', 4, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(310, '2026-08-06', 4, 'BANTEN', 11, 10, 90.91, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(311, '2026-08-06', 4, 'BENGKULU', 5, 3, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(312, '2026-08-06', 4, 'DI YOGYAKARTA', 6, 6, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(313, '2026-08-06', 4, 'DKI JAKARTA', 32, 29, 90.63, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(314, '2026-08-06', 4, 'JAMBI', 8, 6, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(315, '2026-08-06', 4, 'JAWA BARAT', 40, 37, 92.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(316, '2026-08-06', 4, 'JAWA TENGAH', 18, 16, 88.89, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(317, '2026-08-06', 4, 'JAWA TIMUR', 18, 17, 94.44, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(318, '2026-08-06', 4, 'KALIMANTAN BARAT', 17, 12, 70.59, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(319, '2026-08-06', 4, 'KALIMANTAN SELATAN', 8, 5, 62.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(320, '2026-08-06', 4, 'KALIMANTAN TENGAH', 5, 5, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(321, '2026-08-06', 4, 'KALIMANTAN TIMUR', 16, 14, 87.50, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(322, '2026-08-06', 4, 'KEPULAUAN RIAU', 10, 9, 90.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(323, '2026-08-06', 4, 'LAMPUNG', 11, 10, 90.91, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(324, '2026-08-06', 4, 'NUSA TENGGARA BARAT (NTB)', 17, 14, 82.35, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(325, '2026-08-06', 4, 'NUSA TENGGARA TIMUR (NTT)', 3, 1, 33.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(326, '2026-08-06', 4, 'RIAU', 23, 11, 47.83, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(327, '2026-08-06', 4, 'SULAWESI BARAT', 2, 1, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(328, '2026-08-06', 4, 'SULAWESI SELATAN', 30, 20, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(329, '2026-08-06', 4, 'SULAWESI TENGAH', 13, 9, 69.23, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(330, '2026-08-06', 4, 'SULAWESI TENGGARA', 9, 7, 77.78, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(331, '2026-08-06', 4, 'SUMATRA BARAT', 2, 1, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(332, '2026-08-06', 4, 'SUMATRA SELATAN', 16, 12, 75.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(333, '2026-08-06', 4, 'SUMATRA UTARA', 17, 15, 88.24, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(334, '2026-08-07', 4, 'BALI', 22, 18, 81.82, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(335, '2026-08-07', 4, 'BANGKA BELITUNG', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(336, '2026-08-07', 4, 'BANTEN', 14, 12, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(337, '2026-08-07', 4, 'BENGKULU', 2, 2, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(338, '2026-08-07', 4, 'DI YOGYAKARTA', 3, 3, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(339, '2026-08-07', 4, 'DKI JAKARTA', 21, 19, 90.48, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(340, '2026-08-07', 4, 'JAMBI', 11, 9, 81.82, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(341, '2026-08-07', 4, 'JAWA BARAT', 44, 37, 84.09, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(342, '2026-08-07', 4, 'JAWA TENGAH', 25, 22, 88.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(343, '2026-08-07', 4, 'JAWA TIMUR', 28, 24, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(344, '2026-08-07', 4, 'KALIMANTAN BARAT', 12, 11, 91.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(345, '2026-08-07', 4, 'KALIMANTAN SELATAN', 4, 4, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(346, '2026-08-07', 4, 'KALIMANTAN TENGAH', 11, 8, 72.73, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(347, '2026-08-07', 4, 'KALIMANTAN TIMUR', 18, 15, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(348, '2026-08-07', 4, 'KALIMANTAN UTARA', 4, 2, 50.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(349, '2026-08-07', 4, 'KEPULAUAN RIAU', 9, 8, 88.89, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(350, '2026-08-07', 4, 'LAMPUNG', 6, 6, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(351, '2026-08-07', 4, 'MALUKU', 1, 0, 0.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(352, '2026-08-07', 4, 'NUSA TENGGARA BARAT (NTB)', 13, 9, 69.23, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(353, '2026-08-07', 4, 'NUSA TENGGARA TIMUR (NTT)', 7, 6, 85.71, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(354, '2026-08-07', 4, 'RIAU', 24, 16, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(355, '2026-08-07', 4, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(356, '2026-08-07', 4, 'SULAWESI SELATAN', 21, 15, 71.43, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(357, '2026-08-07', 4, 'SULAWESI TENGAH', 7, 2, 28.57, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(358, '2026-08-07', 4, 'SULAWESI TENGGARA', 5, 2, 40.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(359, '2026-08-07', 4, 'SUMATRA BARAT', 7, 5, 71.43, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(360, '2026-08-07', 4, 'SUMATRA SELATAN', 13, 12, 92.31, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(361, '2026-08-07', 4, 'SUMATRA UTARA', 14, 11, 78.57, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(362, '2026-08-08', 4, 'BALI', 26, 20, 76.92, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(363, '2026-08-08', 4, 'BANGKA BELITUNG', 5, 5, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(364, '2026-08-08', 4, 'BANTEN', 20, 20, 100.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(365, '2026-08-08', 4, 'BENGKULU', 6, 4, 66.67, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(366, '2026-08-08', 4, 'DI YOGYAKARTA', 6, 5, 83.33, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(367, '2026-08-08', 4, 'DKI JAKARTA', 33, 30, 90.91, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(368, '2026-08-08', 4, 'JAMBI', 5, 3, 60.00, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(369, '2026-08-08', 4, 'JAWA BARAT', 51, 46, 90.20, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(370, '2026-08-08', 4, 'JAWA TENGAH', 31, 26, 83.87, NULL, '2026-08-11 11:53:31', '2026-08-11 11:53:31'),
(371, '2026-08-08', 4, 'JAWA TIMUR', 30, 21, 70.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(372, '2026-08-08', 4, 'KALIMANTAN BARAT', 12, 9, 75.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(373, '2026-08-08', 4, 'KALIMANTAN SELATAN', 9, 7, 77.78, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(374, '2026-08-08', 4, 'KALIMANTAN TENGAH', 8, 7, 87.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(375, '2026-08-08', 4, 'KALIMANTAN TIMUR', 19, 11, 57.89, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(376, '2026-08-08', 4, 'KALIMANTAN UTARA', 1, 0, 0.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(377, '2026-08-08', 4, 'KEPULAUAN RIAU', 3, 3, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(378, '2026-08-08', 4, 'LAMPUNG', 16, 14, 87.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(379, '2026-08-08', 4, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 1, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(380, '2026-08-08', 4, 'NUSA TENGGARA BARAT (NTB)', 16, 15, 93.75, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(381, '2026-08-08', 4, 'NUSA TENGGARA TIMUR (NTT)', 7, 4, 57.14, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(382, '2026-08-08', 4, 'RIAU', 16, 13, 81.25, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(383, '2026-08-08', 4, 'SULAWESI BARAT', 3, 1, 33.33, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(384, '2026-08-08', 4, 'SULAWESI SELATAN', 23, 14, 60.87, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(385, '2026-08-08', 4, 'SULAWESI TENGAH', 8, 5, 62.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(386, '2026-08-08', 4, 'SULAWESI TENGGARA', 6, 4, 66.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(387, '2026-08-08', 4, 'SUMATRA BARAT', 8, 6, 75.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(388, '2026-08-08', 4, 'SUMATRA SELATAN', 21, 21, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(389, '2026-08-08', 4, 'SUMATRA UTARA', 17, 14, 82.35, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(390, '2026-08-09', 4, 'BALI', 25, 21, 84.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(391, '2026-08-09', 4, 'BANGKA BELITUNG', 2, 2, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(392, '2026-08-09', 4, 'BANTEN', 25, 22, 88.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(393, '2026-08-09', 4, 'BENGKULU', 5, 4, 80.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(394, '2026-08-09', 4, 'DI YOGYAKARTA', 6, 6, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(395, '2026-08-09', 4, 'DKI JAKARTA', 26, 25, 96.15, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(396, '2026-08-09', 4, 'JAMBI', 9, 6, 66.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(397, '2026-08-09', 4, 'JAWA BARAT', 44, 35, 79.55, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(398, '2026-08-09', 4, 'JAWA TENGAH', 32, 30, 93.75, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(399, '2026-08-09', 4, 'JAWA TIMUR', 26, 20, 76.92, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(400, '2026-08-09', 4, 'KALIMANTAN BARAT', 8, 6, 75.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(401, '2026-08-09', 4, 'KALIMANTAN SELATAN', 3, 2, 66.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(402, '2026-08-09', 4, 'KALIMANTAN TENGAH', 6, 6, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(403, '2026-08-09', 4, 'KALIMANTAN TIMUR', 23, 18, 78.26, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(404, '2026-08-09', 4, 'KALIMANTAN UTARA', 7, 3, 42.86, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(405, '2026-08-09', 4, 'KEPULAUAN RIAU', 10, 7, 70.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(406, '2026-08-09', 4, 'LAMPUNG', 9, 8, 88.89, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(407, '2026-08-09', 4, 'MALUKU', 1, 0, 0.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(408, '2026-08-09', 4, 'NUSA TENGGARA BARAT (NTB)', 23, 18, 78.26, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(409, '2026-08-09', 4, 'NUSA TENGGARA TIMUR (NTT)', 4, 1, 25.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(410, '2026-08-09', 4, 'RIAU', 16, 11, 68.75, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(411, '2026-08-09', 4, 'SULAWESI BARAT', 1, 0, 0.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(412, '2026-08-09', 4, 'SULAWESI SELATAN', 27, 17, 62.96, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(413, '2026-08-09', 4, 'SULAWESI TENGAH', 12, 10, 83.33, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(414, '2026-08-09', 4, 'SULAWESI TENGGARA', 12, 6, 50.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(415, '2026-08-09', 4, 'SUMATRA BARAT', 13, 13, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(416, '2026-08-09', 4, 'SUMATRA SELATAN', 10, 7, 70.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(417, '2026-08-09', 4, 'SUMATRA UTARA', 16, 13, 81.25, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(418, '2026-08-10', 4, 'BALI', 27, 25, 92.59, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(419, '2026-08-10', 4, 'BANGKA BELITUNG', 3, 2, 66.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(420, '2026-08-10', 4, 'BANTEN', 12, 10, 83.33, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(421, '2026-08-10', 4, 'BENGKULU', 2, 1, 50.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(422, '2026-08-10', 4, 'DI YOGYAKARTA', 6, 5, 83.33, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(423, '2026-08-10', 4, 'DKI JAKARTA', 30, 26, 86.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(424, '2026-08-10', 4, 'JAMBI', 8, 5, 62.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(425, '2026-08-10', 4, 'JAWA BARAT', 41, 35, 85.37, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(426, '2026-08-10', 4, 'JAWA TENGAH', 20, 19, 95.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(427, '2026-08-10', 4, 'JAWA TIMUR', 33, 27, 81.82, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(428, '2026-08-10', 4, 'KALIMANTAN BARAT', 8, 7, 87.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(429, '2026-08-10', 4, 'KALIMANTAN SELATAN', 6, 3, 50.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(430, '2026-08-10', 4, 'KALIMANTAN TENGAH', 8, 8, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(431, '2026-08-10', 4, 'KALIMANTAN TIMUR', 10, 6, 60.00, NULL, '2026-08-11 11:53:32', '2026-08-11 14:40:52'),
(432, '2026-08-10', 4, 'KALIMANTAN UTARA', 1, 1, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(433, '2026-08-10', 4, 'KEPULAUAN RIAU', 11, 9, 81.82, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(434, '2026-08-10', 4, 'LAMPUNG', 5, 4, 80.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(435, '2026-08-10', 4, 'MALUKU UTARA', 1, 0, 0.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(436, '2026-08-10', 4, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 1, 50.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(437, '2026-08-10', 4, 'NUSA TENGGARA BARAT (NTB)', 7, 4, 57.14, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(438, '2026-08-10', 4, 'NUSA TENGGARA TIMUR (NTT)', 9, 7, 77.78, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(439, '2026-08-10', 4, 'RIAU', 8, 7, 87.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(440, '2026-08-10', 4, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(441, '2026-08-10', 4, 'SULAWESI SELATAN', 24, 18, 75.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(442, '2026-08-10', 4, 'SULAWESI TENGAH', 9, 6, 66.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(443, '2026-08-10', 4, 'SULAWESI TENGGARA', 2, 2, 100.00, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(444, '2026-08-10', 4, 'SUMATRA BARAT', 8, 7, 87.50, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(445, '2026-08-10', 4, 'SUMATRA SELATAN', 12, 11, 91.67, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(446, '2026-08-10', 4, 'SUMATRA UTARA', 19, 16, 84.21, NULL, '2026-08-11 11:53:32', '2026-08-11 11:53:32'),
(447, '2026-08-02', 7, 'BALI', 4, 4, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(448, '2026-08-02', 7, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(449, '2026-08-02', 7, 'BANTEN', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(450, '2026-08-02', 7, 'BENGKULU', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(451, '2026-08-02', 7, 'JAWA BARAT', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(452, '2026-08-02', 7, 'JAWA TENGAH', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(453, '2026-08-02', 7, 'JAWA TIMUR', 3, 3, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(454, '2026-08-02', 7, 'KALIMANTAN BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(455, '2026-08-02', 7, 'KALIMANTAN TIMUR', 2, 1, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 13:05:18'),
(456, '2026-08-02', 7, 'LAMPUNG', 0, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 13:05:05'),
(457, '2026-08-02', 7, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(458, '2026-08-02', 7, 'NUSA TENGGARA BARAT (NTB)', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(459, '2026-08-02', 7, 'NUSA TENGGARA TIMUR (NTT)', 30, 19, 63.33, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(460, '2026-08-02', 7, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(461, '2026-08-02', 7, 'SULAWESI SELATAN', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(462, '2026-08-02', 7, 'SULAWESI TENGGARA', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(463, '2026-08-02', 7, 'SULAWESI UTARA', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(464, '2026-08-02', 7, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(465, '2026-08-02', 7, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(466, '2026-08-02', 7, 'SUMATRA UTARA', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(467, '2026-08-03', 7, 'BALI', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(468, '2026-08-03', 7, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(469, '2026-08-03', 7, 'JAWA TIMUR', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(470, '2026-08-03', 7, 'KALIMANTAN BARAT', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(471, '2026-08-03', 7, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(472, '2026-08-03', 7, 'NANGGROE ACEH DARUSSALAM (NAD)', 6, 3, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(473, '2026-08-03', 7, 'NUSA TENGGARA TIMUR (NTT)', 20, 17, 85.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(474, '2026-08-03', 7, 'RIAU', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(475, '2026-08-03', 7, 'SULAWESI SELATAN', 7, 7, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26');
INSERT INTO `regional_reports` (`id`, `tanggal`, `user_id`, `province`, `lead`, `paid`, `paid_ratio`, `catatan`, `created_at`, `updated_at`) VALUES
(476, '2026-08-03', 7, 'SULAWESI TENGAH', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(477, '2026-08-03', 7, 'SULAWESI TENGGARA', 2, 1, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(478, '2026-08-03', 7, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(479, '2026-08-03', 7, 'SUMATRA UTARA', 4, 2, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(480, '2026-08-04', 7, 'BALI', 4, 3, 75.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(481, '2026-08-04', 7, 'BANTEN', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(482, '2026-08-04', 7, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(483, '2026-08-04', 7, 'DKI JAKARTA', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(484, '2026-08-04', 7, 'JAWA BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(485, '2026-08-04', 7, 'JAWA TENGAH', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(486, '2026-08-04', 7, 'KALIMANTAN TIMUR', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(487, '2026-08-04', 7, 'NUSA TENGGARA BARAT (NTB)', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(488, '2026-08-04', 7, 'NUSA TENGGARA TIMUR (NTT)', 13, 9, 69.23, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(489, '2026-08-04', 7, 'SULAWESI SELATAN', 4, 2, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(490, '2026-08-04', 7, 'SULAWESI TENGAH', 3, 2, 66.67, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(491, '2026-08-04', 7, 'SULAWESI TENGGARA', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(492, '2026-08-04', 7, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(493, '2026-08-04', 7, 'SUMATRA UTARA', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(494, '2026-08-05', 7, 'BALI', 4, 4, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(495, '2026-08-05', 7, 'BENGKULU', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(496, '2026-08-05', 7, 'DI YOGYAKARTA', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(497, '2026-08-05', 7, 'JAMBI', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(498, '2026-08-05', 7, 'JAWA BARAT', 3, 2, 66.67, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(499, '2026-08-05', 7, 'JAWA TENGAH', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(500, '2026-08-05', 7, 'KALIMANTAN BARAT', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(501, '2026-08-05', 7, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(502, '2026-08-05', 7, 'KALIMANTAN TIMUR', 3, 3, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(503, '2026-08-05', 7, 'NUSA TENGGARA BARAT (NTB)', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(504, '2026-08-05', 7, 'NUSA TENGGARA TIMUR (NTT)', 14, 12, 85.71, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(505, '2026-08-05', 7, 'RIAU', 2, 1, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(506, '2026-08-05', 7, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(507, '2026-08-05', 7, 'SULAWESI SELATAN', 4, 3, 75.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(508, '2026-08-05', 7, 'SULAWESI TENGAH', 3, 1, 33.33, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(509, '2026-08-05', 7, 'SULAWESI TENGGARA', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(510, '2026-08-05', 7, 'SUMATRA BARAT', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(511, '2026-08-05', 7, 'SUMATRA UTARA', 3, 2, 66.67, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(512, '2026-08-06', 7, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(513, '2026-08-06', 7, 'BANTEN', 3, 3, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(514, '2026-08-06', 7, 'BENGKULU', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(515, '2026-08-06', 7, 'JAWA BARAT', 3, 3, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(516, '2026-08-06', 7, 'JAWA TENGAH', 4, 3, 75.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(517, '2026-08-06', 7, 'JAWA TIMUR', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(518, '2026-08-06', 7, 'KALIMANTAN BARAT', 2, 1, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(519, '2026-08-06', 7, 'KEPULAUAN RIAU', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(520, '2026-08-06', 7, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 0, 0.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(521, '2026-08-06', 7, 'NUSA TENGGARA BARAT (NTB)', 2, 2, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(522, '2026-08-06', 7, 'NUSA TENGGARA TIMUR (NTT)', 10, 8, 80.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(523, '2026-08-06', 7, 'RIAU', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(524, '2026-08-06', 7, 'SULAWESI SELATAN', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(525, '2026-08-06', 7, 'SULAWESI TENGAH', 2, 1, 50.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(526, '2026-08-06', 7, 'SULAWESI TENGGARA', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(527, '2026-08-06', 7, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(528, '2026-08-06', 7, 'SUMATRA UTARA', 3, 3, 100.00, NULL, '2026-08-11 12:58:26', '2026-08-11 12:58:26'),
(529, '2026-08-07', 7, 'BALI', 1, 1, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(530, '2026-08-07', 7, 'BANTEN', 2, 1, 50.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(531, '2026-08-07', 7, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(532, '2026-08-07', 7, 'JAMBI', 1, 0, 0.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(533, '2026-08-07', 7, 'JAWA BARAT', 2, 1, 50.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(534, '2026-08-07', 7, 'JAWA TENGAH', 2, 2, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(535, '2026-08-07', 7, 'KALIMANTAN BARAT', 1, 1, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(536, '2026-08-07', 7, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(537, '2026-08-07', 7, 'KEPULAUAN RIAU', 3, 3, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(538, '2026-08-07', 7, 'NANGGROE ACEH DARUSSALAM (NAD)', 2, 2, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(539, '2026-08-07', 7, 'NUSA TENGGARA BARAT (NTB)', 1, 1, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(540, '2026-08-07', 7, 'NUSA TENGGARA TIMUR (NTT)', 28, 20, 71.43, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(541, '2026-08-07', 7, 'RIAU', 2, 2, 100.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(542, '2026-08-07', 7, 'SULAWESI SELATAN', 3, 2, 66.67, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(543, '2026-08-07', 7, 'SULAWESI TENGAH', 3, 1, 33.33, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(544, '2026-08-07', 7, 'SULAWESI TENGGARA', 2, 1, 50.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(545, '2026-08-07', 7, 'SUMATRA UTARA', 4, 3, 75.00, NULL, '2026-08-11 13:16:53', '2026-08-11 13:16:53'),
(546, '2026-08-01', 5, 'BALI', 5, 5, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(547, '2026-08-01', 5, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(548, '2026-08-01', 5, 'BANTEN', 4, 3, 75.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(549, '2026-08-01', 5, 'BENGKULU', 3, 2, 66.67, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(550, '2026-08-01', 5, 'DKI JAKARTA', 5, 4, 80.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(551, '2026-08-01', 5, 'JAMBI', 7, 7, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(552, '2026-08-01', 5, 'JAWA BARAT', 11, 9, 81.82, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(553, '2026-08-01', 5, 'JAWA TENGAH', 7, 7, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(554, '2026-08-01', 5, 'JAWA TIMUR', 5, 3, 60.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(555, '2026-08-01', 5, 'KALIMANTAN BARAT', 9, 7, 77.78, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(556, '2026-08-01', 5, 'KALIMANTAN SELATAN', 5, 5, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(557, '2026-08-01', 5, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(558, '2026-08-01', 5, 'KALIMANTAN TIMUR', 12, 10, 83.33, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(559, '2026-08-01', 5, 'KEPULAUAN RIAU', 3, 3, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(560, '2026-08-01', 5, 'LAMPUNG', 3, 3, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(561, '2026-08-01', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 12, 12, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(562, '2026-08-01', 5, 'NUSA TENGGARA BARAT (NTB)', 8, 8, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(563, '2026-08-01', 5, 'RIAU', 5, 5, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(564, '2026-08-01', 5, 'SULAWESI BARAT', 3, 3, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(565, '2026-08-01', 5, 'SULAWESI SELATAN', 14, 13, 92.86, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(566, '2026-08-01', 5, 'SULAWESI TENGAH', 12, 11, 91.67, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(567, '2026-08-01', 5, 'SULAWESI TENGGARA', 14, 12, 85.71, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(568, '2026-08-01', 5, 'SULAWESI UTARA', 6, 4, 66.67, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(569, '2026-08-01', 5, 'SUMATRA BARAT', 3, 3, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(570, '2026-08-01', 5, 'SUMATRA SELATAN', 5, 5, 100.00, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(571, '2026-08-01', 5, 'SUMATRA UTARA', 31, 27, 87.10, NULL, '2026-08-11 13:31:20', '2026-08-11 13:31:20'),
(572, '2026-08-02', 5, 'BALI', 12, 10, 83.33, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(573, '2026-08-02', 5, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(574, '2026-08-02', 5, 'BANTEN', 3, 3, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(575, '2026-08-02', 5, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(576, '2026-08-02', 5, 'DKI JAKARTA', 4, 3, 75.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(577, '2026-08-02', 5, 'JAMBI', 3, 3, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(578, '2026-08-02', 5, 'JAWA BARAT', 10, 10, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(579, '2026-08-02', 5, 'JAWA TENGAH', 6, 5, 83.33, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(580, '2026-08-02', 5, 'JAWA TIMUR', 2, 2, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(581, '2026-08-02', 5, 'KALIMANTAN BARAT', 12, 12, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(582, '2026-08-02', 5, 'KALIMANTAN TENGAH', 10, 9, 90.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(583, '2026-08-02', 5, 'KALIMANTAN TIMUR', 16, 14, 87.50, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(584, '2026-08-02', 5, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(585, '2026-08-02', 5, 'LAMPUNG', 3, 2, 66.67, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(586, '2026-08-02', 5, 'MALUKU UTARA', 16, 8, 50.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(587, '2026-08-02', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 11, 10, 90.91, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(588, '2026-08-02', 5, 'NUSA TENGGARA BARAT (NTB)', 6, 5, 83.33, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(589, '2026-08-02', 5, 'RIAU', 4, 4, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(590, '2026-08-02', 5, 'SULAWESI BARAT', 5, 5, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(591, '2026-08-02', 5, 'SULAWESI SELATAN', 16, 16, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(592, '2026-08-02', 5, 'SULAWESI TENGAH', 17, 14, 82.35, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(593, '2026-08-02', 5, 'SULAWESI TENGGARA', 8, 3, 37.50, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(594, '2026-08-02', 5, 'SULAWESI UTARA', 7, 7, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(595, '2026-08-02', 5, 'SUMATRA BARAT', 3, 2, 66.67, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(596, '2026-08-02', 5, 'SUMATRA SELATAN', 6, 4, 66.67, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(597, '2026-08-02', 5, 'SUMATRA UTARA', 23, 23, 100.00, NULL, '2026-08-11 13:46:49', '2026-08-11 13:46:49'),
(598, '2026-08-03', 5, 'BALI', 12, 12, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(599, '2026-08-03', 5, 'BANGKA BELITUNG', 2, 2, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(600, '2026-08-03', 5, 'BANTEN', 3, 3, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(601, '2026-08-03', 5, 'BENGKULU', 3, 2, 66.67, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(602, '2026-08-03', 5, 'DI YOGYAKARTA', 3, 3, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(603, '2026-08-03', 5, 'DKI JAKARTA', 4, 4, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(604, '2026-08-03', 5, 'JAMBI', 1, 1, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(605, '2026-08-03', 5, 'JAWA BARAT', 8, 8, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(606, '2026-08-03', 5, 'JAWA TENGAH', 4, 4, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(607, '2026-08-03', 5, 'JAWA TIMUR', 1, 1, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(608, '2026-08-03', 5, 'KALIMANTAN BARAT', 12, 11, 91.67, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(609, '2026-08-03', 5, 'KALIMANTAN TENGAH', 6, 6, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(610, '2026-08-03', 5, 'KALIMANTAN TIMUR', 7, 5, 71.43, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(611, '2026-08-03', 5, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(612, '2026-08-03', 5, 'LAMPUNG', 2, 2, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(613, '2026-08-03', 5, 'MALUKU UTARA', 2, 1, 50.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(614, '2026-08-03', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 5, 5, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(615, '2026-08-03', 5, 'NUSA TENGGARA BARAT (NTB)', 8, 7, 87.50, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(616, '2026-08-03', 5, 'NUSA TENGGARA TIMUR (NTT)', 2, 1, 50.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(617, '2026-08-03', 5, 'RIAU', 5, 5, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(618, '2026-08-03', 5, 'SULAWESI BARAT', 3, 3, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(619, '2026-08-03', 5, 'SULAWESI SELATAN', 15, 14, 93.33, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(620, '2026-08-03', 5, 'SULAWESI TENGAH', 10, 10, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(621, '2026-08-03', 5, 'SULAWESI TENGGARA', 5, 3, 60.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(622, '2026-08-03', 5, 'SULAWESI UTARA', 25, 18, 72.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(623, '2026-08-03', 5, 'SUMATRA BARAT', 2, 2, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(624, '2026-08-03', 5, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(625, '2026-08-03', 5, 'SUMATRA UTARA', 20, 17, 85.00, NULL, '2026-08-11 13:53:55', '2026-08-11 13:53:55'),
(626, '2026-08-04', 5, 'BALI', 4, 3, 75.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(627, '2026-08-04', 5, 'BANTEN', 7, 6, 85.71, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(628, '2026-08-04', 5, 'BENGKULU', 3, 2, 66.67, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(629, '2026-08-04', 5, 'DI YOGYAKARTA', 2, 2, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(630, '2026-08-04', 5, 'DKI JAKARTA', 2, 2, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(631, '2026-08-04', 5, 'JAMBI', 5, 3, 60.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(632, '2026-08-04', 5, 'JAWA BARAT', 7, 6, 85.71, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(633, '2026-08-04', 5, 'JAWA TENGAH', 4, 3, 75.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(634, '2026-08-04', 5, 'JAWA TIMUR', 3, 3, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(635, '2026-08-04', 5, 'KALIMANTAN BARAT', 9, 9, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(636, '2026-08-04', 5, 'KALIMANTAN SELATAN', 7, 5, 71.43, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(637, '2026-08-04', 5, 'KALIMANTAN TENGAH', 6, 4, 66.67, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(638, '2026-08-04', 5, 'KALIMANTAN TIMUR', 6, 4, 66.67, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(639, '2026-08-04', 5, 'KEPULAUAN RIAU', 2, 2, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(640, '2026-08-04', 5, 'MALUKU UTARA', 1, 1, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(641, '2026-08-04', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 8, 8, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(642, '2026-08-04', 5, 'NUSA TENGGARA BARAT (NTB)', 3, 3, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(643, '2026-08-04', 5, 'NUSA TENGGARA TIMUR (NTT)', 8, 5, 62.50, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(644, '2026-08-04', 5, 'RIAU', 9, 9, 100.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(645, '2026-08-04', 5, 'SULAWESI BARAT', 5, 4, 80.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(646, '2026-08-04', 5, 'SULAWESI SELATAN', 17, 13, 76.47, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(647, '2026-08-04', 5, 'SULAWESI TENGAH', 12, 9, 75.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(648, '2026-08-04', 5, 'SULAWESI TENGGARA', 12, 11, 91.67, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(649, '2026-08-04', 5, 'SULAWESI UTARA', 24, 14, 58.33, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(650, '2026-08-04', 5, 'SUMATRA BARAT', 5, 4, 80.00, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(651, '2026-08-04', 5, 'SUMATRA SELATAN', 3, 1, 33.33, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(652, '2026-08-04', 5, 'SUMATRA UTARA', 23, 21, 91.30, NULL, '2026-08-11 14:04:36', '2026-08-11 14:04:36'),
(653, '2026-08-05', 5, 'BALI', 2, 2, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(654, '2026-08-05', 5, 'BANTEN', 2, 1, 50.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(655, '2026-08-05', 5, 'DKI JAKARTA', 3, 3, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(656, '2026-08-05', 5, 'JAMBI', 2, 2, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(657, '2026-08-05', 5, 'JAWA BARAT', 6, 6, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(658, '2026-08-05', 5, 'JAWA TENGAH', 2, 2, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(659, '2026-08-05', 5, 'JAWA TIMUR', 8, 7, 87.50, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(660, '2026-08-05', 5, 'KALIMANTAN BARAT', 7, 5, 71.43, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(661, '2026-08-05', 5, 'KALIMANTAN TENGAH', 8, 7, 87.50, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(662, '2026-08-05', 5, 'KALIMANTAN TIMUR', 5, 4, 80.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(663, '2026-08-05', 5, 'KALIMANTAN UTARA', 2, 1, 50.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(664, '2026-08-05', 5, 'KEPULAUAN RIAU', 1, 1, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(665, '2026-08-05', 5, 'MALUKU UTARA', 11, 6, 54.55, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(666, '2026-08-05', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 10, 9, 90.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(667, '2026-08-05', 5, 'NUSA TENGGARA BARAT (NTB)', 3, 3, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(668, '2026-08-05', 5, 'NUSA TENGGARA TIMUR (NTT)', 4, 2, 50.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(669, '2026-08-05', 5, 'RIAU', 2, 2, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(670, '2026-08-05', 5, 'SULAWESI BARAT', 3, 3, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(671, '2026-08-05', 5, 'SULAWESI SELATAN', 6, 6, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(672, '2026-08-05', 5, 'SULAWESI TENGAH', 10, 9, 90.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(673, '2026-08-05', 5, 'SULAWESI TENGGARA', 8, 7, 87.50, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(674, '2026-08-05', 5, 'SULAWESI UTARA', 49, 31, 63.27, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(675, '2026-08-05', 5, 'SUMATRA BARAT', 3, 3, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(676, '2026-08-05', 5, 'SUMATRA SELATAN', 1, 1, 100.00, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(677, '2026-08-05', 5, 'SUMATRA UTARA', 21, 19, 90.48, NULL, '2026-08-11 14:14:25', '2026-08-11 14:14:25'),
(678, '2026-08-06', 5, 'BALI', 9, 7, 77.78, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(679, '2026-08-06', 5, 'BANTEN', 2, 1, 50.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(680, '2026-08-06', 5, 'BENGKULU', 1, 0, 0.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(681, '2026-08-06', 5, 'DI YOGYAKARTA', 2, 2, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(682, '2026-08-06', 5, 'DKI JAKARTA', 7, 6, 85.71, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(683, '2026-08-06', 5, 'JAMBI', 1, 1, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(684, '2026-08-06', 5, 'JAWA BARAT', 10, 10, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(685, '2026-08-06', 5, 'JAWA TENGAH', 6, 5, 83.33, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(686, '2026-08-06', 5, 'JAWA TIMUR', 6, 5, 83.33, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(687, '2026-08-06', 5, 'KALIMANTAN BARAT', 12, 12, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(688, '2026-08-06', 5, 'KALIMANTAN SELATAN', 7, 7, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(689, '2026-08-06', 5, 'KALIMANTAN TENGAH', 9, 8, 88.89, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(690, '2026-08-06', 5, 'KALIMANTAN TIMUR', 17, 14, 82.35, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(691, '2026-08-06', 5, 'KALIMANTAN UTARA', 4, 2, 50.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(692, '2026-08-06', 5, 'KEPULAUAN RIAU', 4, 4, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(693, '2026-08-06', 5, 'MALUKU UTARA', 3, 1, 33.33, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(694, '2026-08-06', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 8, 7, 87.50, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(695, '2026-08-06', 5, 'NUSA TENGGARA BARAT (NTB)', 12, 10, 83.33, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(696, '2026-08-06', 5, 'PAPUA BARAT', 1, 0, 0.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(697, '2026-08-06', 5, 'RIAU', 5, 3, 60.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(698, '2026-08-06', 5, 'SULAWESI BARAT', 5, 4, 80.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(699, '2026-08-06', 5, 'SULAWESI SELATAN', 11, 11, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(700, '2026-08-06', 5, 'SULAWESI TENGAH', 11, 6, 54.55, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(701, '2026-08-06', 5, 'SULAWESI TENGGARA', 10, 9, 90.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(702, '2026-08-06', 5, 'SULAWESI UTARA', 51, 32, 62.75, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(703, '2026-08-06', 5, 'SUMATRA BARAT', 3, 3, 100.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(704, '2026-08-06', 5, 'SUMATRA SELATAN', 3, 2, 66.67, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(705, '2026-08-06', 5, 'SUMATRA UTARA', 25, 23, 92.00, NULL, '2026-08-11 14:21:24', '2026-08-11 14:21:24'),
(706, '2026-08-07', 5, 'BALI', 4, 4, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(707, '2026-08-07', 5, 'BANTEN', 3, 3, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(708, '2026-08-07', 5, 'BENGKULU', 2, 2, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(709, '2026-08-07', 5, 'DI YOGYAKARTA', 3, 3, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(710, '2026-08-07', 5, 'DKI JAKARTA', 4, 3, 75.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(711, '2026-08-07', 5, 'JAMBI', 4, 2, 50.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(712, '2026-08-07', 5, 'JAWA BARAT', 8, 6, 75.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(713, '2026-08-07', 5, 'JAWA TENGAH', 4, 4, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(714, '2026-08-07', 5, 'JAWA TIMUR', 6, 6, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(715, '2026-08-07', 5, 'KALIMANTAN BARAT', 7, 6, 85.71, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(716, '2026-08-07', 5, 'KALIMANTAN SELATAN', 1, 1, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(717, '2026-08-07', 5, 'KALIMANTAN TENGAH', 10, 10, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(718, '2026-08-07', 5, 'KALIMANTAN TIMUR', 16, 12, 75.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(719, '2026-08-07', 5, 'KALIMANTAN UTARA', 2, 0, 0.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(720, '2026-08-07', 5, 'KEPULAUAN RIAU', 6, 5, 83.33, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(721, '2026-08-07', 5, 'LAMPUNG', 5, 4, 80.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(722, '2026-08-07', 5, 'MALUKU UTARA', 1, 0, 0.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(723, '2026-08-07', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 8, 6, 75.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(724, '2026-08-07', 5, 'NUSA TENGGARA BARAT (NTB)', 4, 3, 75.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(725, '2026-08-07', 5, 'NUSA TENGGARA TIMUR (NTT)', 1, 1, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(726, '2026-08-07', 5, 'RIAU', 2, 2, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(727, '2026-08-07', 5, 'SULAWESI BARAT', 1, 1, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(728, '2026-08-07', 5, 'SULAWESI SELATAN', 11, 6, 54.55, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(729, '2026-08-07', 5, 'SULAWESI TENGAH', 10, 7, 70.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(730, '2026-08-07', 5, 'SULAWESI TENGGARA', 9, 4, 44.44, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(731, '2026-08-07', 5, 'SULAWESI UTARA', 61, 35, 57.38, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(732, '2026-08-07', 5, 'SUMATRA SELATAN', 3, 3, 100.00, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(733, '2026-08-07', 5, 'SUMATRA UTARA', 30, 28, 93.33, NULL, '2026-08-11 14:30:52', '2026-08-11 14:30:52'),
(734, '2026-08-08', 5, 'BALI', 1, 1, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(735, '2026-08-08', 5, 'BANTEN', 2, 2, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(736, '2026-08-08', 5, 'DKI JAKARTA', 6, 6, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(737, '2026-08-08', 5, 'GORONTALO', 1, 0, 0.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(738, '2026-08-08', 5, 'JAMBI', 4, 4, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(739, '2026-08-08', 5, 'JAWA BARAT', 8, 6, 75.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(740, '2026-08-08', 5, 'JAWA TENGAH', 7, 7, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(741, '2026-08-08', 5, 'JAWA TIMUR', 3, 3, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(742, '2026-08-08', 5, 'KALIMANTAN BARAT', 13, 11, 84.62, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(743, '2026-08-08', 5, 'KALIMANTAN SELATAN', 3, 3, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(744, '2026-08-08', 5, 'KALIMANTAN TENGAH', 8, 3, 37.50, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(745, '2026-08-08', 5, 'KALIMANTAN TIMUR', 17, 17, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(746, '2026-08-08', 5, 'KALIMANTAN UTARA', 3, 3, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(747, '2026-08-08', 5, 'KEPULAUAN RIAU', 3, 3, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(748, '2026-08-08', 5, 'LAMPUNG', 5, 5, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(749, '2026-08-08', 5, 'MALUKU UTARA', 8, 0, 0.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(750, '2026-08-08', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 7, 6, 85.71, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(751, '2026-08-08', 5, 'NUSA TENGGARA BARAT (NTB)', 5, 5, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(752, '2026-08-08', 5, 'NUSA TENGGARA TIMUR (NTT)', 2, 2, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(753, '2026-08-08', 5, 'PAPUA', 1, 0, 0.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(754, '2026-08-08', 5, 'RIAU', 1, 1, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(755, '2026-08-08', 5, 'SULAWESI BARAT', 2, 2, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(756, '2026-08-08', 5, 'SULAWESI SELATAN', 13, 12, 92.31, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(757, '2026-08-08', 5, 'SULAWESI TENGAH', 19, 16, 84.21, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(758, '2026-08-08', 5, 'SULAWESI TENGGARA', 15, 10, 66.67, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(759, '2026-08-08', 5, 'SULAWESI UTARA', 83, 56, 67.47, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(760, '2026-08-08', 5, 'SUMATRA BARAT', 2, 2, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(761, '2026-08-08', 5, 'SUMATRA SELATAN', 3, 3, 100.00, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(762, '2026-08-08', 5, 'SUMATRA UTARA', 24, 23, 95.83, NULL, '2026-08-11 14:46:24', '2026-08-11 14:46:24'),
(763, '2026-08-09', 5, 'BALI', 5, 5, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(764, '2026-08-09', 5, 'BANGKA BELITUNG', 1, 1, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(765, '2026-08-09', 5, 'DI YOGYAKARTA', 2, 2, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(766, '2026-08-09', 5, 'DKI JAKARTA', 10, 10, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(767, '2026-08-09', 5, 'GORONTALO', 2, 0, 0.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(768, '2026-08-09', 5, 'JAMBI', 4, 4, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(769, '2026-08-09', 5, 'JAWA BARAT', 5, 5, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(770, '2026-08-09', 5, 'JAWA TENGAH', 8, 8, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(771, '2026-08-09', 5, 'JAWA TIMUR', 5, 3, 60.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(772, '2026-08-09', 5, 'KALIMANTAN BARAT', 15, 13, 86.67, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(773, '2026-08-09', 5, 'KALIMANTAN SELATAN', 7, 6, 85.71, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(774, '2026-08-09', 5, 'KALIMANTAN TENGAH', 8, 7, 87.50, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(775, '2026-08-09', 5, 'KALIMANTAN TIMUR', 15, 12, 80.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(776, '2026-08-09', 5, 'KALIMANTAN UTARA', 2, 1, 50.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(777, '2026-08-09', 5, 'KEPULAUAN RIAU', 2, 2, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(778, '2026-08-09', 5, 'LAMPUNG', 1, 1, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(779, '2026-08-09', 5, 'MALUKU UTARA', 22, 8, 36.36, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(780, '2026-08-09', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 7, 7, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(781, '2026-08-09', 5, 'NUSA TENGGARA BARAT (NTB)', 7, 7, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(782, '2026-08-09', 5, 'NUSA TENGGARA TIMUR (NTT)', 3, 1, 33.33, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(783, '2026-08-09', 5, 'RIAU', 6, 6, 100.00, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(784, '2026-08-09', 5, 'SULAWESI BARAT', 7, 6, 85.71, NULL, '2026-08-11 14:54:27', '2026-08-11 14:54:27'),
(785, '2026-08-09', 5, 'SULAWESI SELATAN', 9, 8, 88.89, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(786, '2026-08-09', 5, 'SULAWESI TENGAH', 16, 10, 62.50, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(787, '2026-08-09', 5, 'SULAWESI TENGGARA', 8, 8, 100.00, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(788, '2026-08-09', 5, 'SULAWESI UTARA', 66, 46, 69.70, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(789, '2026-08-09', 5, 'SUMATRA BARAT', 1, 1, 100.00, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(790, '2026-08-09', 5, 'SUMATRA SELATAN', 3, 3, 100.00, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(791, '2026-08-09', 5, 'SUMATRA UTARA', 29, 27, 93.10, NULL, '2026-08-11 14:54:28', '2026-08-11 14:54:28'),
(792, '2026-08-10', 5, 'BALI', 2, 2, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(793, '2026-08-10', 5, 'BANTEN', 4, 4, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(794, '2026-08-10', 5, 'BENGKULU', 2, 2, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(795, '2026-08-10', 5, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(796, '2026-08-10', 5, 'DKI JAKARTA', 6, 6, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(797, '2026-08-10', 5, 'GORONTALO', 1, 0, 0.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(798, '2026-08-10', 5, 'JAMBI', 3, 3, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(799, '2026-08-10', 5, 'JAWA BARAT', 6, 5, 83.33, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(800, '2026-08-10', 5, 'JAWA TENGAH', 2, 1, 50.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(801, '2026-08-10', 5, 'JAWA TIMUR', 2, 1, 50.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(802, '2026-08-10', 5, 'KALIMANTAN BARAT', 9, 8, 88.89, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(803, '2026-08-10', 5, 'KALIMANTAN SELATAN', 2, 1, 50.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(804, '2026-08-10', 5, 'KALIMANTAN TENGAH', 3, 2, 66.67, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(805, '2026-08-10', 5, 'KALIMANTAN TIMUR', 12, 8, 66.67, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(806, '2026-08-10', 5, 'KALIMANTAN UTARA', 3, 3, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(807, '2026-08-10', 5, 'KEPULAUAN RIAU', 2, 2, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(808, '2026-08-10', 5, 'LAMPUNG', 3, 3, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(809, '2026-08-10', 5, 'MALUKU UTARA', 4, 0, 0.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(810, '2026-08-10', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 3, 2, 66.67, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(811, '2026-08-10', 5, 'NUSA TENGGARA BARAT (NTB)', 5, 5, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(812, '2026-08-10', 5, 'NUSA TENGGARA TIMUR (NTT)', 5, 5, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(813, '2026-08-10', 5, 'RIAU', 4, 4, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(814, '2026-08-10', 5, 'SULAWESI SELATAN', 13, 11, 84.62, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(815, '2026-08-10', 5, 'SULAWESI TENGAH', 11, 9, 81.82, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(816, '2026-08-10', 5, 'SULAWESI TENGGARA', 4, 3, 75.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(817, '2026-08-10', 5, 'SULAWESI UTARA', 47, 28, 59.57, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(818, '2026-08-10', 5, 'SUMATRA BARAT', 4, 4, 100.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(819, '2026-08-10', 5, 'SUMATRA SELATAN', 3, 2, 66.67, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(820, '2026-08-10', 5, 'SUMATRA UTARA', 20, 18, 90.00, NULL, '2026-08-11 15:02:06', '2026-08-11 15:02:06'),
(821, '2026-08-11', 6, 'BALI', 1, 1, 100.00, NULL, '2026-08-12 01:53:04', '2026-08-12 01:53:04'),
(822, '2026-08-11', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 1, 100.00, NULL, '2026-08-12 01:53:04', '2026-08-12 01:53:04'),
(823, '2026-08-11', 6, 'SUMATRA UTARA', 1, 0, 0.00, NULL, '2026-08-12 01:53:04', '2026-08-12 01:53:04'),
(824, '2026-08-11', 5, 'BALI', 8, 7, 87.50, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(825, '2026-08-11', 5, 'BANGKA BELITUNG', 2, 2, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(826, '2026-08-11', 5, 'BANTEN', 6, 5, 83.33, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(827, '2026-08-11', 5, 'BENGKULU', 2, 2, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(828, '2026-08-11', 5, 'DI YOGYAKARTA', 2, 2, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(829, '2026-08-11', 5, 'DKI JAKARTA', 7, 6, 85.71, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(830, '2026-08-11', 5, 'JAMBI', 4, 4, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(831, '2026-08-11', 5, 'JAWA BARAT', 11, 8, 72.73, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(832, '2026-08-11', 5, 'JAWA TENGAH', 8, 6, 75.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(833, '2026-08-11', 5, 'JAWA TIMUR', 2, 2, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(834, '2026-08-11', 5, 'KALIMANTAN BARAT', 20, 17, 85.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(835, '2026-08-11', 5, 'KALIMANTAN SELATAN', 2, 2, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(836, '2026-08-11', 5, 'KALIMANTAN TENGAH', 10, 9, 90.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(837, '2026-08-11', 5, 'KALIMANTAN TIMUR', 11, 10, 90.91, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(838, '2026-08-11', 5, 'KALIMANTAN UTARA', 5, 3, 60.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(839, '2026-08-11', 5, 'KEPULAUAN RIAU', 4, 4, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(840, '2026-08-11', 5, 'LAMPUNG', 1, 0, 0.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(841, '2026-08-11', 5, 'MALUKU UTARA', 15, 5, 33.33, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(842, '2026-08-11', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 5, 4, 80.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(843, '2026-08-11', 5, 'NUSA TENGGARA BARAT (NTB)', 9, 9, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(844, '2026-08-11', 5, 'NUSA TENGGARA TIMUR (NTT)', 13, 9, 69.23, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(845, '2026-08-11', 5, 'RIAU', 9, 9, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(846, '2026-08-11', 5, 'SULAWESI BARAT', 5, 5, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(847, '2026-08-11', 5, 'SULAWESI SELATAN', 23, 22, 95.65, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(848, '2026-08-11', 5, 'SULAWESI TENGAH', 15, 10, 66.67, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(849, '2026-08-11', 5, 'SULAWESI TENGGARA', 8, 6, 75.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(850, '2026-08-11', 5, 'SULAWESI UTARA', 58, 38, 65.52, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(851, '2026-08-11', 5, 'SUMATRA BARAT', 6, 6, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(852, '2026-08-11', 5, 'SUMATRA SELATAN', 5, 5, 100.00, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(853, '2026-08-11', 5, 'SUMATRA UTARA', 24, 22, 91.67, NULL, '2026-08-12 06:21:03', '2026-08-12 06:21:03'),
(854, '2026-08-12', 6, 'BALI', 5, 2, 40.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(855, '2026-08-12', 6, 'BANGKA BELITUNG', 1, 0, 0.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(856, '2026-08-12', 6, 'DKI JAKARTA', 3, 3, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(857, '2026-08-12', 6, 'JAWA BARAT', 2, 1, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(858, '2026-08-12', 6, 'KALIMANTAN BARAT', 1, 0, 0.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(859, '2026-08-12', 6, 'KALIMANTAN SELATAN', 1, 1, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(860, '2026-08-12', 6, 'KALIMANTAN TENGAH', 1, 1, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(861, '2026-08-12', 6, 'KALIMANTAN TIMUR', 4, 2, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(862, '2026-08-12', 6, 'KALIMANTAN UTARA', 1, 1, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(863, '2026-08-12', 6, 'KEPULAUAN RIAU', 2, 1, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(864, '2026-08-12', 6, 'LAMPUNG', 2, 2, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(865, '2026-08-12', 6, 'MALUKU UTARA', 3, 2, 66.67, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(866, '2026-08-12', 6, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 1, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(867, '2026-08-12', 6, 'NUSA TENGGARA BARAT (NTB)', 1, 1, 100.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(868, '2026-08-12', 6, 'NUSA TENGGARA TIMUR (NTT)', 11, 4, 36.36, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(869, '2026-08-12', 6, 'RIAU', 2, 1, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(870, '2026-08-12', 6, 'SULAWESI SELATAN', 7, 4, 57.14, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(871, '2026-08-12', 6, 'SULAWESI TENGGARA', 2, 1, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(872, '2026-08-12', 6, 'SULAWESI UTARA', 8, 5, 62.50, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(873, '2026-08-12', 6, 'SUMATRA BARAT', 3, 0, 0.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(874, '2026-08-12', 6, 'SUMATRA SELATAN', 2, 1, 50.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(875, '2026-08-12', 6, 'SUMATRA UTARA', 5, 3, 60.00, NULL, '2026-08-13 01:07:35', '2026-08-13 01:07:35'),
(876, '2026-08-12', 5, 'BALI', 11, 9, 81.82, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(877, '2026-08-12', 5, 'BANGKA BELITUNG', 3, 3, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(878, '2026-08-12', 5, 'BANTEN', 5, 5, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(879, '2026-08-12', 5, 'DI YOGYAKARTA', 1, 1, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(880, '2026-08-12', 5, 'DKI JAKARTA', 12, 11, 91.67, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(881, '2026-08-12', 5, 'GORONTALO', 1, 0, 0.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(882, '2026-08-12', 5, 'JAMBI', 7, 6, 85.71, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(883, '2026-08-12', 5, 'JAWA BARAT', 8, 5, 62.50, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(884, '2026-08-12', 5, 'JAWA TENGAH', 11, 11, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(885, '2026-08-12', 5, 'JAWA TIMUR', 11, 10, 90.91, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(886, '2026-08-12', 5, 'KALIMANTAN BARAT', 16, 12, 75.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(887, '2026-08-12', 5, 'KALIMANTAN SELATAN', 4, 3, 75.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(888, '2026-08-12', 5, 'KALIMANTAN TENGAH', 12, 10, 83.33, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(889, '2026-08-12', 5, 'KALIMANTAN TIMUR', 16, 15, 93.75, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(890, '2026-08-12', 5, 'KALIMANTAN UTARA', 8, 4, 50.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(891, '2026-08-12', 5, 'KEPULAUAN RIAU', 9, 8, 88.89, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(892, '2026-08-12', 5, 'LAMPUNG', 8, 8, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(893, '2026-08-12', 5, 'MALUKU UTARA', 8, 1, 12.50, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(894, '2026-08-12', 5, 'NANGGROE ACEH DARUSSALAM (NAD)', 11, 8, 72.73, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(895, '2026-08-12', 5, 'NUSA TENGGARA BARAT (NTB)', 8, 8, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(896, '2026-08-12', 5, 'NUSA TENGGARA TIMUR (NTT)', 8, 7, 87.50, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(897, '2026-08-12', 5, 'RIAU', 4, 4, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(898, '2026-08-12', 5, 'SULAWESI SELATAN', 19, 16, 84.21, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(899, '2026-08-12', 5, 'SULAWESI TENGAH', 16, 12, 75.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(900, '2026-08-12', 5, 'SULAWESI TENGGARA', 20, 15, 75.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(901, '2026-08-12', 5, 'SULAWESI UTARA', 50, 30, 60.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(902, '2026-08-12', 5, 'SUMATRA BARAT', 7, 7, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(903, '2026-08-12', 5, 'SUMATRA SELATAN', 6, 6, 100.00, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(904, '2026-08-12', 5, 'SUMATRA UTARA', 27, 26, 96.30, NULL, '2026-08-13 14:59:31', '2026-08-13 14:59:31'),
(905, '2026-08-11', 4, 'BALI', 27, 24, 88.89, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(906, '2026-08-11', 4, 'BANGKA BELITUNG', 2, 2, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(907, '2026-08-11', 4, 'BANTEN', 14, 13, 92.86, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(908, '2026-08-11', 4, 'BENGKULU', 3, 3, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(909, '2026-08-11', 4, 'DI YOGYAKARTA', 6, 6, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(910, '2026-08-11', 4, 'DKI JAKARTA', 27, 23, 85.19, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(911, '2026-08-11', 4, 'JAMBI', 7, 6, 85.71, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(912, '2026-08-11', 4, 'JAWA BARAT', 38, 36, 94.74, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(913, '2026-08-11', 4, 'JAWA TENGAH', 16, 16, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(914, '2026-08-11', 4, 'JAWA TIMUR', 22, 19, 86.36, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(915, '2026-08-11', 4, 'KALIMANTAN BARAT', 9, 8, 88.89, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(916, '2026-08-11', 4, 'KALIMANTAN SELATAN', 7, 6, 85.71, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(917, '2026-08-11', 4, 'KALIMANTAN TENGAH', 6, 2, 33.33, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(918, '2026-08-11', 4, 'KALIMANTAN TIMUR', 15, 10, 66.67, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(919, '2026-08-11', 4, 'KEPULAUAN RIAU', 8, 8, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(920, '2026-08-11', 4, 'LAMPUNG', 10, 8, 80.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(921, '2026-08-11', 4, 'NUSA TENGGARA BARAT (NTB)', 14, 12, 85.71, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(922, '2026-08-11', 4, 'NUSA TENGGARA TIMUR (NTT)', 3, 2, 66.67, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(923, '2026-08-11', 4, 'RIAU', 19, 15, 78.95, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(924, '2026-08-11', 4, 'SULAWESI BARAT', 2, 1, 50.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(925, '2026-08-11', 4, 'SULAWESI SELATAN', 23, 15, 65.22, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(926, '2026-08-11', 4, 'SULAWESI TENGAH', 1, 1, 100.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(927, '2026-08-11', 4, 'SULAWESI TENGGARA', 7, 5, 71.43, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(928, '2026-08-11', 4, 'SUMATRA BARAT', 18, 13, 72.22, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(929, '2026-08-11', 4, 'SUMATRA SELATAN', 12, 10, 83.33, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(930, '2026-08-11', 4, 'SUMATRA UTARA', 10, 7, 70.00, NULL, '2026-08-14 00:47:04', '2026-08-14 00:47:04'),
(931, '2026-08-12', 4, 'BALI', 29, 26, 89.66, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(932, '2026-08-12', 4, 'BANGKA BELITUNG', 1, 0, 0.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(933, '2026-08-12', 4, 'BANTEN', 16, 15, 93.75, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(934, '2026-08-12', 4, 'BENGKULU', 5, 3, 60.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(935, '2026-08-12', 4, 'DI YOGYAKARTA', 6, 6, 100.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(936, '2026-08-12', 4, 'DKI JAKARTA', 18, 17, 94.44, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(937, '2026-08-12', 4, 'JAMBI', 8, 6, 75.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(938, '2026-08-12', 4, 'JAWA BARAT', 40, 39, 97.50, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(939, '2026-08-12', 4, 'JAWA TENGAH', 28, 26, 92.86, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(940, '2026-08-12', 4, 'JAWA TIMUR', 21, 20, 95.24, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(941, '2026-08-12', 4, 'KALIMANTAN BARAT', 9, 9, 100.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(942, '2026-08-12', 4, 'KALIMANTAN SELATAN', 6, 6, 100.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(943, '2026-08-12', 4, 'KALIMANTAN TENGAH', 12, 11, 91.67, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(944, '2026-08-12', 4, 'KALIMANTAN TIMUR', 15, 11, 73.33, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(945, '2026-08-12', 4, 'KALIMANTAN UTARA', 1, 0, 0.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(946, '2026-08-12', 4, 'KEPULAUAN RIAU', 6, 6, 100.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(947, '2026-08-12', 4, 'LAMPUNG', 11, 7, 63.64, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25');
INSERT INTO `regional_reports` (`id`, `tanggal`, `user_id`, `province`, `lead`, `paid`, `paid_ratio`, `catatan`, `created_at`, `updated_at`) VALUES
(948, '2026-08-12', 4, 'NUSA TENGGARA BARAT (NTB)', 10, 8, 80.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(949, '2026-08-12', 4, 'NUSA TENGGARA TIMUR (NTT)', 4, 0, 0.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(950, '2026-08-12', 4, 'RIAU', 13, 10, 76.92, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(951, '2026-08-12', 4, 'SULAWESI SELATAN', 17, 14, 82.35, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(952, '2026-08-12', 4, 'SULAWESI TENGAH', 10, 5, 50.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(953, '2026-08-12', 4, 'SULAWESI TENGGARA', 8, 4, 50.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(954, '2026-08-12', 4, 'SULAWESI UTARA', 1, 1, 100.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(955, '2026-08-12', 4, 'SUMATRA BARAT', 8, 4, 50.00, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(956, '2026-08-12', 4, 'SUMATRA SELATAN', 14, 12, 85.71, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(957, '2026-08-12', 4, 'SUMATRA UTARA', 18, 13, 72.22, NULL, '2026-08-14 02:07:25', '2026-08-14 02:07:25'),
(958, '2026-08-13', 4, 'BALI', 29, 26, 89.66, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(959, '2026-08-13', 4, 'BANGKA BELITUNG', 3, 3, 100.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(960, '2026-08-13', 4, 'BANTEN', 15, 14, 93.33, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(961, '2026-08-13', 4, 'BENGKULU', 4, 4, 100.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(962, '2026-08-13', 4, 'DI YOGYAKARTA', 3, 3, 100.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(963, '2026-08-13', 4, 'DKI JAKARTA', 19, 10, 52.63, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(964, '2026-08-13', 4, 'JAMBI', 11, 7, 63.64, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(965, '2026-08-13', 4, 'JAWA BARAT', 45, 41, 91.11, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(966, '2026-08-13', 4, 'JAWA TENGAH', 16, 16, 100.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(967, '2026-08-13', 4, 'JAWA TIMUR', 14, 9, 64.29, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(968, '2026-08-13', 4, 'KALIMANTAN BARAT', 11, 6, 54.55, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(969, '2026-08-13', 4, 'KALIMANTAN SELATAN', 5, 4, 80.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(970, '2026-08-13', 4, 'KALIMANTAN TENGAH', 8, 6, 75.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(971, '2026-08-13', 4, 'KALIMANTAN TIMUR', 14, 11, 78.57, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(972, '2026-08-13', 4, 'KEPULAUAN RIAU', 6, 5, 83.33, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(973, '2026-08-13', 4, 'LAMPUNG', 14, 13, 92.86, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(974, '2026-08-13', 4, 'MALUKU UTARA', 1, 0, 0.00, NULL, '2026-08-14 02:07:40', '2026-08-14 02:07:40'),
(975, '2026-08-13', 4, 'NANGGROE ACEH DARUSSALAM (NAD)', 1, 1, 100.00, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(976, '2026-08-13', 4, 'NUSA TENGGARA BARAT (NTB)', 14, 11, 78.57, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(977, '2026-08-13', 4, 'NUSA TENGGARA TIMUR (NTT)', 3, 2, 66.67, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(978, '2026-08-13', 4, 'PAPUA', 1, 0, 0.00, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(979, '2026-08-13', 4, 'RIAU', 16, 11, 68.75, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(980, '2026-08-13', 4, 'SULAWESI SELATAN', 26, 15, 57.69, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(981, '2026-08-13', 4, 'SULAWESI TENGAH', 6, 4, 66.67, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(982, '2026-08-13', 4, 'SULAWESI TENGGARA', 4, 3, 75.00, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(983, '2026-08-13', 4, 'SULAWESI UTARA', 53, 32, 60.38, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(984, '2026-08-13', 4, 'SUMATRA BARAT', 6, 4, 66.67, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(985, '2026-08-13', 4, 'SUMATRA SELATAN', 12, 11, 91.67, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41'),
(986, '2026-08-13', 4, 'SUMATRA UTARA', 17, 13, 76.47, NULL, '2026-08-14 02:07:41', '2026-08-14 02:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'owner', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(2, 'super_admin', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(3, 'admin', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(4, 'advertiser', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(5, 'mentor', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(6, 'keuangan', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(7, 'cs', 'web', '2026-08-10 22:33:16', '2026-08-10 22:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(13, 2),
(14, 2),
(15, 2),
(16, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(23, 2),
(24, 2),
(25, 2),
(26, 2),
(27, 2),
(28, 2),
(29, 2),
(30, 2),
(31, 2),
(32, 2),
(1, 3),
(2, 3),
(3, 3),
(4, 3),
(6, 3),
(7, 3),
(8, 3),
(10, 3),
(11, 3),
(12, 3),
(14, 3),
(18, 3),
(19, 3),
(20, 3),
(21, 3),
(27, 3),
(28, 3),
(1, 4),
(2, 4),
(6, 4),
(10, 4),
(11, 4),
(12, 4),
(13, 4),
(14, 4),
(15, 4),
(16, 4),
(17, 4),
(1, 5),
(2, 5),
(6, 5),
(10, 5),
(14, 5),
(27, 5),
(1, 6),
(2, 6),
(6, 6),
(14, 6),
(18, 6),
(27, 6),
(28, 6),
(1, 7),
(6, 7),
(14, 7),
(15, 7),
(16, 7);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0J4GjegKepcOpbMWwgv4HkLbqvVBBl2sXhPEt2yO', NULL, '45.205.1.240', 'Mozilla/5.0 (compatible; FlowIQLabsBot/1.0; +https://flowiq-labs.com/scanning-info)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM2tsVmN0MkV4YzVpaFMzQnRrN3Z2dHlvTVVjNjhDWUdFUTZsTXBIZyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoxOToiaHR0cDovLzEwMy41OC4xMDEuNyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjE5OiJodHRwOi8vMTAzLjU4LjEwMS43IjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1786699918),
('85xhAr498nfLLB8qHDNxdiVmxqd5LPtfn2tycqyK', 4, '160.19.18.97', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibHJtQlo0M3VFTU5Td2VjZ055akhPTFRuV3JjbzZCMFROSXpVRXl2MiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly8xMDMuNTguMTAxLjcvcmVnaW9uYWwvY2hlY2siO3M6NToicm91dGUiO3M6MTQ6InJlZ2lvbmFsLmNoZWNrIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDt9', 1786701289),
('BxCshTXVH9ylrGTBtEF05k9kXpV76gtfw5ldC4MQ', NULL, '45.194.67.26', 'Mozilla/5.0 (compatible; FlowIQLabsBot/1.0; +https://flowiq-labs.com/scanning-info)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiV0ZvNDhSSzhQWlo1bkI4Y05mMExJZTYzRWt5S01rdkFDVG1TbjcyWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMDMuNTguMTAxLjcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1786700705),
('i9KRwr0LxI9umcQgmhQxkVsus8N5Mb3LrMZmghxZ', NULL, '45.194.67.26', 'Mozilla/5.0 (compatible; FlowIQLabsBot/1.0; +https://flowiq-labs.com/scanning-info)', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiODgweUhmRU9WNDI1NE8xQk9POThIQWRoRzJLekVSVDJyczloZmdWbyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoxOToiaHR0cDovLzEwMy41OC4xMDEuNyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjE5OiJodHRwOi8vMTAzLjU4LjEwMS43IjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1786700696),
('y7OKxJPj2cMsgDicDzKMv5W7Jyiv54kLyuDDR9n3', NULL, '45.205.1.240', 'Mozilla/5.0 (compatible; FlowIQLabsBot/1.0; +https://flowiq-labs.com/scanning-info)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieGdMN1ZXM2dDTTNzQTZBZzRTMTBzRDRiTnYxbTVHcFJJRU5mdENHSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMDMuNTguMTAxLjcvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1786699922);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint UNSIGNED NOT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_address` text COLLATE utf8mb4_unicode_ci,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `shipping_fee` decimal(14,2) NOT NULL DEFAULT '0.00',
  `parcel_value` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_cod` tinyint(1) NOT NULL DEFAULT '0',
  `cod_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `source_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_status_histories`
--

CREATE TABLE `shipment_status_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `shipment_id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_orders`
--

CREATE TABLE `shipping_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `order_online_import_batch_id` bigint UNSIGNED NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `awb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_normalized` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdistrict` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('real','tembakan','belum_diproses','cancel','duplikat') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handled_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handled_by_user_id` bigint UNSIGNED DEFAULT NULL,
  `courier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `stock_note` text COLLATE utf8mb4_unicode_ci,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `weight` decimal(10,3) NOT NULL DEFAULT '0.000',
  `product_price` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_cod` tinyint(1) NOT NULL DEFAULT '0',
  `cod_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `aggregator_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `raw_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shipping_orders`
--

INSERT INTO `shipping_orders` (`id`, `order_online_import_batch_id`, `order_id`, `awb`, `customer_name`, `phone`, `phone_normalized`, `address`, `province`, `city`, `subdistrict`, `postal_code`, `payment_method`, `status`, `handled_by`, `handled_by_user_id`, `courier`, `courier_note`, `product_name`, `meta_account`, `product_code`, `product_id`, `product_variant_id`, `stock_note`, `quantity`, `weight`, `product_price`, `is_cod`, `cod_amount`, `aggregator_status`, `last_synced_at`, `delivered_at`, `raw_payload`, `created_at`, `updated_at`) VALUES
(1, 1, '278247928', '', 'Deny', '6285298540122', '6285298540122', 'Jln ir tendean lorong polindes', 'SULAWESI TENGAH', 'Kab. Poso', 'Poso Kota Utara', '94616', 'cod', 'belum_diproses', 'FERI CS', 12, NULL, NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+1.25', 1, 2, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"94616\", \"bump\": \"-\", \"city\": \"Kab. Poso\", \"cogs\": \"\", \"name\": \"Deny\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6285298540122\", \"coupon\": \"\", \"status\": \"pending\", \"weight\": \"1000\", \"address\": \"Jln ir tendean lorong polindes\", \"courier\": \"J&T - EZ\", \"paid_at\": \"\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278247928\", \"province\": \"Sulawesi Tengah\", \"quantity\": \"1\", \"utm_term\": \"1.2024772644212E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 43-44 Tahun Plus +1.25\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 20:09\", \"handled_by\": \"FERI CS\", \"ip_address\": \"2001:448a:7140:519e:4103:e35b:f9df:9c66\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Poso Kota Utara\", \"utm_content\": \"1.2024772644213E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"1.2024772644214E+017\", \"gross_revenue\": \"214000\", \"processing_at\": \"\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"85000\", \"payment_method\": \"cod\", \"payment_status\": \"unpaid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"85000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(2, 1, '278247802', '', 'Ishak', '6281347412602', '6281347412602', 'Jln p, surianata perum, hj, Sadri blok, b,1, nmr 34, RT,13, kelurahan bukit pinang, kec, Samarinda ulu, kota Samarinda, prop, Kaltim', 'KALIMANTAN TIMUR', 'Kota Samarinda', 'Samarinda Ulu', '75124', 'cod', 'real', 'FERI CS', 12, 'flix-spx', NULL, 'A.4 Shendara Herbal 9 pcs', '22769', 'SH', 5, 29, NULL, 9, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"75124\", \"bump\": \"-\", \"city\": \"Kota Samarinda\", \"cogs\": \"\", \"name\": \"Ishak\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6281347412602\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jln p, surianata perum, hj, Sadri blok, b,1, nmr 34, RT,13, kelurahan bukit pinang, kec, Samarinda ulu, kota Samarinda, prop, Kaltim\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 20:09\", \"product\": \"A.4 Shendara Herbal - 22769\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278247802\", \"province\": \"Kalimantan Timur\", \"quantity\": \"1\", \"utm_term\": \"1.20253445943781E+017\", \"variation\": \"PROMO: PAKET 1 DAPAT 9 PCS - Rp 119.000\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 20:07\", \"handled_by\": \"FERI CS\", \"ip_address\": \"182.8.179.243\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Samarinda Ulu\", \"utm_content\": \"1.20253445943801E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"SH\", \"utm_campaign\": \"1.20253445943791E+017\", \"gross_revenue\": \"176000\", \"processing_at\": \"07-08-2026 - 20:09\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"57000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"57000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(3, 1, '278247343', '', 'Ida Nurlatifah', '6285238191235', '6285238191235', 'jl. raya Sesetan no 266, rumah dinas no 17, kantor Balai Besar Veteriner Denpasar,', 'BALI', 'Kota Denpasar', 'Denpasar Selatan', '80225', 'cod', 'cancel', 'FERI CS', 12, NULL, NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+1.25', 2, 11, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"80225\", \"bump\": \"-\", \"city\": \"Kota Denpasar\", \"cogs\": \"\", \"name\": \"Ida Nurlatifah\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6285238191235\", \"coupon\": \"\", \"status\": \"cancelled\", \"weight\": \"1000\", \"address\": \"jl. raya Sesetan no 266, rumah dinas no 17, kantor Balai Besar Veteriner Denpasar,\", \"courier\": \"J&T - EZ\", \"paid_at\": \"\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278247343\", \"province\": \"Bali\", \"quantity\": \"1\", \"utm_term\": \"1.2024809775164E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 43-44 Tahun Plus +1.25, Warna: Orange Minimalis\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 20:01\", \"handled_by\": \"FERI CS\", \"ip_address\": \"114.122.139.78\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Denpasar Selatan\", \"utm_content\": \"1.2024809775167E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"1.2024809775159E+017\", \"gross_revenue\": \"149000\", \"processing_at\": \"\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"30000\", \"payment_method\": \"cod\", \"payment_status\": \"unpaid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"30000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(4, 1, '278247279', '999515139182', 'Aan Gorden', '6282316011417', '6282316011417', 'Jl. Cokroaminoto No.118, Ubung, Kec. Denpasar Utara, Kota Denpasar, Bali 80116', 'BALI', 'Kab. Badung', 'Kuta', '80361', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+1.75', 2, 13, NULL, 2, 1000.000, 119000.00, 1, 119000.00, 'delivered', NULL, '2026-08-09 05:58:56', '{\"zip\": \"80361\", \"bump\": \"-\", \"city\": \"Kab. Badung\", \"cogs\": \"\", \"name\": \"Aan Gorden\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6282316011417\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl. Cokroaminoto No.118, Ubung, Kec. Denpasar Utara, Kota Denpasar, Bali 80116\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 20:08\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278247279\", \"province\": \"Bali\", \"quantity\": \"1\", \"utm_term\": \"1.202479838542E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 48-49 Tahun Plus +1.75, Warna: Merah Klasik\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 20:00\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.154.151.145\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Kuta\", \"utm_content\": \"1.2024798385418E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"1.202479838541E+017\", \"gross_revenue\": \"149000\", \"processing_at\": \"07-08-2026 - 20:08\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"30000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"30000\"}', '2026-08-11 04:37:48', '2026-08-11 04:41:21'),
(5, 1, '278246350', '', 'Hendrik', '6281339995858', '6281339995858', 'Embong malang 25a, kondominium Regency 1706', 'JAWA TIMUR', 'Kota Surabaya', 'Tegalsari', '60264', 'cod', 'tembakan', 'FERI CS', 12, 'spx', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13724', 'KSP+2', 2, 14, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"60264\", \"bump\": \"-\", \"city\": \"Kota Surabaya\", \"cogs\": \"\", \"name\": \"Hendrik\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6281339995858\", \"coupon\": \"\", \"status\": \"pending\", \"weight\": \"1000\", \"address\": \"Embong malang 25a, kondominium Regency 1706\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 19:51\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13724\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278246350\", \"province\": \"Jawa Timur\", \"quantity\": \"1\", \"utm_term\": \"1.2025001034524E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 50-52 Tahun Plus +2.00, Warna: Grey Elegant\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 19:47\", \"handled_by\": \"FERI CS\", \"ip_address\": \"103.245.19.82\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Tegalsari\", \"utm_content\": \"1.2025001034527E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"1.2025001034512E+017\", \"gross_revenue\": \"140000\", \"processing_at\": \"\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"21000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"21000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(6, 1, '278244830', '', 'Indah', '6285210637202', '6285210637202', 'Taman Palem lestari blok C 1 no 80 cengkareng jakarta barat', 'DKI JAKARTA', 'Kota Jakarta Barat', 'Cengkareng', '11730', 'bank_transfer', 'belum_diproses', 'MAYANG CS', 13, NULL, NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13724', 'KSP+1', 2, 10, NULL, 2, 1000.000, 119000.00, 0, 0.00, NULL, NULL, NULL, '{\"zip\": \"11730\", \"bump\": \"-\", \"city\": \"Kota Jakarta Barat\", \"cogs\": \"\", \"name\": \"Indah\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6285210637202\", \"coupon\": \"\", \"status\": \"pending\", \"weight\": \"1000\", \"address\": \"Taman Palem lestari blok C 1 no 80 cengkareng jakarta barat\", \"courier\": \"J&T - EZ\", \"paid_at\": \"\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13724\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278244830\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"1.2024959357843E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 65 Tahun Ke atas +3.50, Warna: Orange Minimalis\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 19:27\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2001:448a:20a0:f5d8:4484:ad96:b9f4:399a\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Cengkareng\", \"utm_content\": \"1.2024959357842E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"1.2024959357853E+017\", \"gross_revenue\": \"133000\", \"processing_at\": \"\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"bank_transfer\", \"payment_status\": \"unpaid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(7, 1, '278243258', '', 'Edi fermana', '628118384611', '628118384611', 'Jl. Arimbi no.193 RT7 Rw 1, halim perdanakusuma,', 'DKI JAKARTA', 'Kota Jakarta Timur', 'Makasar', '13620', 'bank_transfer', 'real', 'MAYANG CS', 13, 'flix-tf', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13724', 'KSP+3', 2, 18, NULL, 2, 1000.000, 119000.00, 0, 0.00, NULL, NULL, NULL, '{\"zip\": \"13620\", \"bump\": \"-\", \"city\": \"Kota Jakarta Timur\", \"cogs\": \"\", \"name\": \"Edi fermana\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"628118384611\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl. Arimbi no.193 RT7 Rw 1, halim perdanakusuma,\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 20:05\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13724\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278243258\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"1.2024959357843E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 60-64 Tahun Plus +3.00, Warna: Grey Elegant\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 19:07\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2404:c0:202f:ec0d:8d29:3db8:862a:f151\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"ig\", \"net_revenue\": \"119000\", \"subdistrict\": \"Makasar\", \"utm_content\": \"1.2024959357842E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"1.2024959357853E+017\", \"gross_revenue\": \"133000\", \"processing_at\": \"07-08-2026 - 20:05\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"bank_transfer\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(8, 1, '278239312', '', 'As ac / Ansori', '6289514531976', '6289514531976', 'Jalan KH Agus salim, , gg klinik puri intan, No , 33, RT 1 / 5  Bekasi jaya, Bekasi timur, kota Bekasi Jawa Barat', 'JAWA BARAT', 'Kab. Bekasi', 'Babelan', '17610', 'bank_transfer', 'real', 'MAYANG CS', 13, 'flix-tf', NULL, 'A.3 Kacamata Multifokus Photocromic 4 pcs', '13724', 'KMP+2.25', 1, 6, NULL, 4, 1000.000, 179000.00, 0, 0.00, NULL, NULL, NULL, '{\"zip\": \"17610\", \"bump\": \"-\", \"city\": \"Kab. Bekasi\", \"cogs\": \"\", \"name\": \"As ac / Ansori\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6289514531976\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jalan KH Agus salim, , gg klinik puri intan, No , 33, RT 1 / 5  Bekasi jaya, Bekasi timur, kota Bekasi Jawa Barat\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 18:33\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13724\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278239312\", \"province\": \"Jawa Barat\", \"quantity\": \"1\", \"utm_term\": \"1.2024955341582E+017\", \"variation\": \"Promo: PROMO Beli 2 Dapat 4 - Rp 179.000, Ukuran: Usia 53-54 Tahun Plus +2.25\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 18:13\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"182.2.187.139\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"179000\", \"subdistrict\": \"Babelan\", \"utm_content\": \"1.2024955341583E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"1.2024955341577E+017\", \"gross_revenue\": \"194000\", \"processing_at\": \"07-08-2026 - 18:33\", \"product_price\": \"179000\", \"reseller_name\": \"\", \"shipping_cost\": \"15000\", \"payment_method\": \"bank_transfer\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"15000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(9, 1, '278238428', '', 'M.Bramnas Hede', '6287877946776', '6287877946776', 'SMK TRIGUNA 1956, Jl. H. Muchtar Raya No.56 E, RT.5/RW.11, Petukangan Utara, Kec. Pesanggrahan, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12260', 'DKI JAKARTA', 'Kota Jakarta Selatan', 'Pesanggrahan', '12330', 'bank_transfer', 'real', 'FERI CS', 12, 'flix-tf', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '22767', 'KMP+1', 1, 1, NULL, 2, 1000.000, 129000.00, 0, 0.00, NULL, NULL, NULL, '{\"zip\": \"12330\", \"bump\": \"-\", \"city\": \"Kota Jakarta Selatan\", \"cogs\": \"\", \"name\": \"M.Bramnas Hede\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6287877946776\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"SMK TRIGUNA 1956, Jl. H. Muchtar Raya No.56 E, RT.5/RW.11, Petukangan Utara, Kec. Pesanggrahan, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12260\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 18:10\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 22767\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278238428\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"1.2025059326419E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 40-42 Tahun Plus +1.00\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 18:01\", \"handled_by\": \"FERI CS\", \"ip_address\": \"119.2.43.115\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"ig\", \"net_revenue\": \"129000\", \"subdistrict\": \"Pesanggrahan\", \"utm_content\": \"1.2025059326418E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"1.2025059326417E+017\", \"gross_revenue\": \"143000\", \"processing_at\": \"07-08-2026 - 18:10\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"bank_transfer\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:37:48', '2026-08-11 04:37:48'),
(10, 1, '278213204', '999515139183', 'Ivan Khacank', '6282299888266', '6282299888266', 'Jl. Kb. Sirih Timur. Dalam No.37-39, RT.15/RW.7 (Melly\'s Garden Cafe)', 'DKI JAKARTA', 'Kota Jakarta Pusat', 'Menteng', '10330', 'cod', 'real', 'FERI CS', 12, 'sicepat', NULL, 'A.4 Shendara Herbal 9 pcs', '22769', 'SH', 5, 29, NULL, 9, 1000.000, 119000.00, 1, 119000.00, 'delivered', NULL, '2026-08-09 05:58:56', '{\"zip\": \"10330\", \"bump\": \"-\", \"city\": \"Kota Jakarta Pusat\", \"cogs\": \"\", \"name\": \"Ivan Khacank\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6282299888266\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl. Kb. Sirih Timur. Dalam No.37-39, RT.15/RW.7 (Melly\'s Garden Cafe)\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 11:38\", \"product\": \"A.4 Shendara Herbal - 22769\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278213204\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"1.20253445943781E+017\", \"variation\": \"PROMO: PAKET 1 DAPAT 9 PCS - Rp 119.000\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 11:37\", \"handled_by\": \"FERI CS\", \"ip_address\": \"2404:c0:2150::ea4c:4d0\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Menteng\", \"utm_content\": \"1.20253445943801E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"SH\", \"utm_campaign\": \"1.20253445943791E+017\", \"gross_revenue\": \"133000\", \"processing_at\": \"07-08-2026 - 11:38\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:37:48', '2026-08-11 04:41:21'),
(11, 1, '278212626', '', 'Ansari', '6285266691436', '6285266691436', 'RT 11, Kel:teluk nilau,kampung melayu', 'JAMBI', 'Kab. Tanjung Jabung Barat', 'Pengabuan', '36553', 'cod', 'real', 'FERI CS', 12, 'undeliverable', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13723', 'KBJ+1.25', 3, 20, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"36553\", \"bump\": \"-\", \"city\": \"Kab. Tanjung Jabung Barat\", \"cogs\": \"\", \"name\": \"Ansari\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"6285266691436\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"RT 11, Kel:teluk nilau,kampung melayu\", \"courier\": \"J&T - EZ\", \"paid_at\": \"07-08-2026 - 11:31\", \"product\": \"A.1 Kacamata Baca & Jalan - 13723\", \"cod_cost\": \"\", \"discount\": \"\", \"order_id\": \"278212626\", \"province\": \"Jambi\", \"quantity\": \"1\", \"utm_term\": \"1.2024800545572E+017\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 43-44 Tahun Plus +1.25\", \"bump_price\": \"\", \"created_at\": \"07-08-2026 - 11:28\", \"handled_by\": \"FERI CS\", \"ip_address\": \"114.10.104.79\", \"order_type\": \"form\", \"other_cost\": \"\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Pengabuan\", \"utm_content\": \"1.202480054557E+017\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"1.2024800545562E+017\", \"gross_revenue\": \"169000\", \"processing_at\": \"07-08-2026 - 11:31\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"50000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"50000\"}', '2026-08-11 04:37:48', '2026-08-11 04:43:35'),
(12, 2, '278546563', '', 'Herdyanoor', '+6285206666622', '6285206666622', 'Jl.samudin rt.02 rw.01desa/kelurahan kuala pembuang 1 seruyan hilir kab.seruyan.kalimantan tengah indonesia kode pos.74211', 'KALIMANTAN TENGAH', 'Kab. Seruyan', 'Seruyan Hilir', '74214', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+2', 1, 5, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"74214\", \"bump\": \"-\", \"city\": \"Kab. Seruyan\", \"cogs\": \"0\", \"name\": \"Herdyanoor\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6285206666622\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl.samudin rt.02 rw.01desa/kelurahan kuala pembuang 1 seruyan hilir kab.seruyan.kalimantan tengah indonesia kode pos.74211\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:56\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278546563\", \"province\": \"Kalimantan Tengah\", \"quantity\": \"1\", \"utm_term\": \"120246955966020080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 50-52 Tahun Plus +2.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 18:53\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2404:c0:9469:6438::1\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Seruyan Hilir\", \"utm_content\": \"120246955966030080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120246955966040080\", \"gross_revenue\": \"198000\", \"processing_at\": \"11-08-2026 - 18:56\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"69000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"69000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(13, 2, '278545355', '', 'Rusedi', '+6287852942168', '6287852942168', 'Desa Pangkalan RT 11 RW 1 Kecamatan Losarang, Kabupaten Indramayu, Provinsi Jawa Barat, Kode Pos 45253.', 'JAWA BARAT', 'Kab. Indramayu', 'Losarang', '45253', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+2', 1, 5, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"45253\", \"bump\": \"-\", \"city\": \"Kab. Indramayu\", \"cogs\": \"0\", \"name\": \"Rusedi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6287852942168\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Desa Pangkalan RT 11 RW 1 Kecamatan Losarang, Kabupaten Indramayu, Provinsi Jawa Barat, Kode Pos 45253.\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:40\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278545355\", \"province\": \"Jawa Barat\", \"quantity\": \"1\", \"utm_term\": \"120246742095160080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 50-52 Tahun Plus +2.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 18:38\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.144.102.245\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Losarang\", \"utm_content\": \"120246742095180080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120246742095120080\", \"gross_revenue\": \"137000\", \"processing_at\": \"11-08-2026 - 18:40\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"8000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"8000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(14, 2, '278545349', '', 'Syahlan dongoran', '+6282162711234', '6282162711234', 'Jl lintas Sipiongot desa sungai datar ALAN BENGKEL SEPEDA MOTOR', 'SUMATRA UTARA', 'Kab. Padang Lawas Utara', 'Dolok', '22756', 'cod', 'real', 'MAYANG CS', 13, 'flix-idx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+1.25', 1, 2, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"22756\", \"bump\": \"-\", \"city\": \"Kab. Padang Lawas Utara\", \"cogs\": \"0\", \"name\": \"Syahlan dongoran\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6282162711234\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl lintas Sipiongot desa sungai datar ALAN BENGKEL SEPEDA MOTOR\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:42\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278545349\", \"province\": \"Sumatera Utara\", \"quantity\": \"1\", \"utm_term\": \"120247634395880080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 43-44 Tahun Plus +1.25\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 18:38\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"114.122.38.239\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Dolok\", \"utm_content\": \"120247634395890080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120247634395850080\", \"gross_revenue\": \"191000\", \"processing_at\": \"11-08-2026 - 18:42\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"62000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"62000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(15, 2, '278544111', '', 'Ninzar Akib', '+628124210443', '628124210443', 'Jl. Sao Sao Lrg. Patoro I No.20, Bende, Kec. Kadia, Kota Kendari, Sulawesi Tenggara 93118', 'SULAWESI TENGGARA', 'Kota Kendari', 'Kadia', '93118', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+1', 1, 1, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"93118\", \"bump\": \"-\", \"city\": \"Kota Kendari\", \"cogs\": \"0\", \"name\": \"Ninzar Akib\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+628124210443\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl. Sao Sao Lrg. Patoro I No.20, Bende, Kec. Kadia, Kota Kendari, Sulawesi Tenggara 93118\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:29\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278544111\", \"province\": \"Sulawesi Tenggara\", \"quantity\": \"1\", \"utm_term\": \"120247726442120080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 65 Tahun Ke atas +3.50\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 18:22\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"180.251.144.124\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Kadia\", \"utm_content\": \"120247726442130080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120247726442140080\", \"gross_revenue\": \"197000\", \"processing_at\": \"11-08-2026 - 18:29\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"68000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"68000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(16, 2, '278543828', '', 'Enung Nuryadi', '+6282120312095', '6282120312095', 'Jln Cigugur kaler Patokan rumah sebelah timur SDN Sunan gunung jati RT 04/RW 02 desa Cigugur kaler ...', 'JAWA BARAT', 'Kab. Subang', 'Pusakajaya', '41255', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+2.75', 1, 8, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"41255\", \"bump\": \"-\", \"city\": \"Kab. Subang\", \"cogs\": \"0\", \"name\": \"Enung Nuryadi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6282120312095\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jln Cigugur kaler Patokan rumah sebelah timur SDN Sunan gunung jati RT 04/RW 02 desa Cigugur kaler ...\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:23\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278543828\", \"province\": \"Jawa Barat\", \"quantity\": \"1\", \"utm_term\": \"120247924626590080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 58-59 Tahun Plus +2.75\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 18:17\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.115.20.62\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Pusakajaya\", \"utm_content\": \"120247924626580080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120247924626570080\", \"gross_revenue\": \"145000\", \"processing_at\": \"11-08-2026 - 18:23\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"16000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"16000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(17, 2, '278542356', '', 'Hery', '+6281280234030', '6281280234030', 'Kp.paniis lebak,Perumahan galuh pakuan residence blok A No 2', 'BANTEN', 'Kab. Pandeglang', 'Jiput', '42263', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13724', 'KBJ+1', 3, 19, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"42263\", \"bump\": \"-\", \"city\": \"Kab. Pandeglang\", \"cogs\": \"0\", \"name\": \"Hery\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281280234030\", \"coupon\": \"P10K\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Kp.paniis lebak,Perumahan galuh pakuan residence blok A No 2\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:03\", \"product\": \"A.1 Kacamata Baca & Jalan - 13724\", \"cod_cost\": \"0\", \"discount\": \"10000\", \"order_id\": \"278542356\", \"province\": \"Banten\", \"quantity\": \"1\", \"utm_term\": \"120249370723460299\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 40-42 Tahun Plus +1.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:57\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.208.204.145\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"109000\", \"subdistrict\": \"Jiput\", \"utm_content\": \"120249370723440299\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120249370723400299\", \"gross_revenue\": \"132000\", \"processing_at\": \"11-08-2026 - 18:04\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"23000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"23000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(18, 2, '278542100', '', 'Sarifuddin', '+6281240168093', '6281240168093', 'Pasar baruga Jalan Pasar, Baruga, Kendari City, South East Sulawesi 93563', 'SULAWESI TENGGARA', 'Kota Kendari', 'Baruga', '93116', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13724', 'KMP+1', 1, 1, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"93116\", \"bump\": \"-\", \"city\": \"Kota Kendari\", \"cogs\": \"0\", \"name\": \"Sarifuddin\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281240168093\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Pasar baruga Jalan Pasar, Baruga, Kendari City, South East Sulawesi 93563\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:05\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13724\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278542100\", \"province\": \"Sulawesi Tenggara\", \"quantity\": \"1\", \"utm_term\": \"120250098704590299\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 40-42 Tahun Plus +1.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:53\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"182.5.8.48\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Baruga\", \"utm_content\": \"120250098704600299\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120250098704750299\", \"gross_revenue\": \"197000\", \"processing_at\": \"11-08-2026 - 18:05\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"68000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"68000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(19, 2, '278542031', '', 'Widiasa', '+6281338626191', '6281338626191', 'Jl buana kubu gg asam1a no1 Desa Tegal harum', 'BALI', 'Kota Denpasar', 'Denpasar Barat', '80113', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+1.25', 2, 11, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"80113\", \"bump\": \"-\", \"city\": \"Kota Denpasar\", \"cogs\": \"0\", \"name\": \"Widiasa\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281338626191\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl buana kubu gg asam1a no1 Desa Tegal harum\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:57\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278542031\", \"province\": \"Bali\", \"quantity\": \"1\", \"utm_term\": \"\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 43-44 Tahun Plus +1.25, Warna: Grey Elegant\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:52\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"180.254.225.162\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"\", \"utm_source\": \"\", \"net_revenue\": \"119000\", \"subdistrict\": \"Denpasar Barat\", \"utm_content\": \"\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"\", \"gross_revenue\": \"149000\", \"processing_at\": \"11-08-2026 - 17:57\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"30000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"30000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(20, 2, '278541668', '', 'Muhammad Fiqhi Fahmi', '+628126750179', '628126750179', 'Jl. Patin 6 no. 2292 komp. Pusri Sako Kelurahan Sako', 'SUMATRA SELATAN', 'Kota Palembang', 'Sako', '30163', 'cod', 'real', 'MAYANG CS', 13, 'flix-idx', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+2.25', 2, 15, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"30163\", \"bump\": \"-\", \"city\": \"Kota Palembang\", \"cogs\": \"0\", \"name\": \"Muhammad Fiqhi Fahmi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+628126750179\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl. Patin 6 no. 2292 komp. Pusri Sako Kelurahan Sako\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:57\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278541668\", \"province\": \"Sumatera Selatan\", \"quantity\": \"1\", \"utm_term\": \"120248005455670390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 53-54 Tahun Plus +2.25, Warna: Grey Elegant\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:47\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.119.66.74\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Sako\", \"utm_content\": \"120248005455650390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"120248005455590390\", \"gross_revenue\": \"148000\", \"processing_at\": \"11-08-2026 - 17:57\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"29000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"29000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(21, 2, '278541034', '', 'Sapruddin', '+6282323057173', '6282323057173', 'Dusun padak rt/RW 003/027 Desa labuhan sumbawa', 'NUSA TENGGARA BARAT (NTB)', 'Kab. Sumbawa', 'Labuhan Badas', '84316', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+2.5', 2, 16, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"84316\", \"bump\": \"-\", \"city\": \"Kab. Sumbawa\", \"cogs\": \"0\", \"name\": \"Sapruddin\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6282323057173\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Dusun padak rt/RW 003/027 Desa labuhan sumbawa\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:43\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278541034\", \"province\": \"Nusa Tenggara Barat (NTB)\", \"quantity\": \"1\", \"utm_term\": \"120248566863810390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 55-57 Tahun Plus +2.50, Warna: Grey Elegant\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:38\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2404:c0:3821:168b:1:0:71f5:e8a1\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Labuhan Badas\", \"utm_content\": \"120248566863830390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"120248566863820390\", \"gross_revenue\": \"171000\", \"processing_at\": \"11-08-2026 - 17:43\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"52000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"52000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(22, 2, '278541008', '', 'Halik', '+6281212330515', '6281212330515', 'Jl Makam no8 rt07/01 Desa Cipulir', 'DKI JAKARTA', 'Kota Jakarta Selatan', 'Kebayoran Lama', '12230', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+2.75', 2, 17, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"12230\", \"bump\": \"-\", \"city\": \"Kota Jakarta Selatan\", \"cogs\": \"0\", \"name\": \"Halik\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281212330515\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl Makam no8 rt07/01 Desa Cipulir\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:46\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278541008\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"120248565779210390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 58-59 Tahun Plus +2.75, Warna: Merah Klasik\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:38\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"216.243.116.176\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Kebayoran Lama\", \"utm_content\": \"120248565779390390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"120248565779430390\", \"gross_revenue\": \"133000\", \"processing_at\": \"11-08-2026 - 17:46\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(23, 2, '278540242', '', 'Suwandi', '+62811994909', '62811994909', 'Gading griya lestari blok C 1 no 8 tr04 rw 12', 'DKI JAKARTA', 'Kota Jakarta Utara', 'Cilincing', '14120', 'bank_transfer', 'real', 'MAYANG CS', 13, 'flix-tf', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13723', 'KBJ+2', 3, 23, NULL, 2, 1000.000, 119000.00, 0, 0.00, NULL, NULL, NULL, '{\"zip\": \"14120\", \"bump\": \"-\", \"city\": \"Kota Jakarta Utara\", \"cogs\": \"0\", \"name\": \"Suwandi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+62811994909\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Gading griya lestari blok C 1 no 8 tr04 rw 12\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:39\", \"product\": \"A.1 Kacamata Baca & Jalan - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278540242\", \"province\": \"DKI Jakarta\", \"quantity\": \"1\", \"utm_term\": \"120248758366290390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 50-52 Tahun Plus +2.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:27\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"140.213.190.73\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"ig\", \"net_revenue\": \"119000\", \"subdistrict\": \"Cilincing\", \"utm_content\": \"120248758366300390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120248758366310390\", \"gross_revenue\": \"133000\", \"processing_at\": \"11-08-2026 - 17:39\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"14000\", \"payment_method\": \"bank_transfer\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"14000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(24, 2, '278540231', '', 'Muhammad Edi', '+62895635388522', '62895635388522', 'Bumi Cikarang Asri Ciantra RT.03/012 nmr:25.jln.Ciantra Raya seberang Indomaret', 'JAWA BARAT', 'Kota Bekasi', 'Bekasi Timur', '17111', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13723', 'KSP+1', 2, 10, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"17111\", \"bump\": \"-\", \"city\": \"Kota Bekasi\", \"cogs\": \"0\", \"name\": \"Muhammad Edi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+62895635388522\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Bumi Cikarang Asri Ciantra RT.03/012 nmr:25.jln.Ciantra Raya seberang Indomaret\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:19\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278540231\", \"province\": \"Jawa Barat\", \"quantity\": \"1\", \"utm_term\": \"120248566863810390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 40-42 Tahun Plus +1.00, Warna: Orange Minimalis\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:27\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.147.82.251\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Bekasi Timur\", \"utm_content\": \"120248566863830390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"120248566863820390\", \"gross_revenue\": \"134000\", \"processing_at\": \"11-08-2026 - 18:19\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"15000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"15000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18');
INSERT INTO `shipping_orders` (`id`, `order_online_import_batch_id`, `order_id`, `awb`, `customer_name`, `phone`, `phone_normalized`, `address`, `province`, `city`, `subdistrict`, `postal_code`, `payment_method`, `status`, `handled_by`, `handled_by_user_id`, `courier`, `courier_note`, `product_name`, `meta_account`, `product_code`, `product_id`, `product_variant_id`, `stock_note`, `quantity`, `weight`, `product_price`, `is_cod`, `cod_amount`, `aggregator_status`, `last_synced_at`, `delivered_at`, `raw_payload`, `created_at`, `updated_at`) VALUES
(25, 2, '278539865', '', 'wilda', '+62882020120982', '62882020120982', 'jalan lahalede no 80 kelurahan ujung lare, samping SMP negri 2 parepare,toko jaya abadi cell', 'SULAWESI SELATAN', 'Kota Parepare', 'Soreang', '91131', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13724', 'KBJ+1.25', 3, 20, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"91131\", \"bump\": \"-\", \"city\": \"Kota Parepare\", \"cogs\": \"0\", \"name\": \"wilda\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+62882020120982\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"jalan lahalede no 80 kelurahan ujung lare, samping SMP negri 2 parepare,toko jaya abadi cell\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:39\", \"product\": \"A.1 Kacamata Baca & Jalan - 13724\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278539865\", \"province\": \"Sulawesi Selatan\", \"quantity\": \"1\", \"utm_term\": \"120249334142290299\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 43-44 Tahun Plus +1.25\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:21\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2402:5680:99ec:49dd:dbdd:e3fb:e1aa:98db\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Soreang\", \"utm_content\": \"120249334142280299\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120249334142490299\", \"gross_revenue\": \"172000\", \"processing_at\": \"11-08-2026 - 17:39\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"53000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"53000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(26, 2, '278539400', '', 'Ahmad Mujahid', '+6283809711486', '6283809711486', 'DUSUN REJOSARI RT 5 RW 5 DESA WONOYOSO, KEC.PRINGAPUS, KAB. SEMARANG', 'JAWA TENGAH', 'Kota Semarang', 'Semarang Selatan', '50245', 'cod', 'real', 'MAYANG CS', 13, 'sicepat', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13723', 'KBJ+2.25', 3, 24, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"50245\", \"bump\": \"-\", \"city\": \"Kota Semarang\", \"cogs\": \"0\", \"name\": \"Ahmad Mujahid\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6283809711486\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"DUSUN REJOSARI RT 5 RW 5 DESA WONOYOSO, KEC.PRINGAPUS, KAB. SEMARANG\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 17:42\", \"product\": \"A.1 Kacamata Baca & Jalan - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278539400\", \"province\": \"Jawa Tengah\", \"quantity\": \"1\", \"utm_term\": \"120247696560720390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 53-54 Tahun Plus +2.25\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 17:13\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.120.169.244\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Semarang Selatan\", \"utm_content\": \"120247696560710390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120247696560640390\", \"gross_revenue\": \"139000\", \"processing_at\": \"11-08-2026 - 17:42\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"20000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"20000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(27, 2, '278537450', '', 'Ipit', '+6285820668298', '6285820668298', 'Desa sungai mawang rt04.rw.04 samping spbu sungai mawang simpang lape', 'KALIMANTAN BARAT', 'Kab. Sanggau', 'Kapuas (Sanggau Kapuas)', '78516', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+1', 1, 1, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"78516\", \"bump\": \"-\", \"city\": \"Kab. Sanggau\", \"cogs\": \"0\", \"name\": \"Ipit\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6285820668298\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Desa sungai mawang rt04.rw.04 samping spbu sungai mawang simpang lape\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:18\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278537450\", \"province\": \"Kalimantan Barat\", \"quantity\": \"1\", \"utm_term\": \"120246778370050080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 65 Tahun Ke atas +3.50\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 16:45\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"114.10.137.117\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Kapuas (Sanggau Kapuas)\", \"utm_content\": \"120246778370040080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120246778369990080\", \"gross_revenue\": \"203000\", \"processing_at\": \"11-08-2026 - 18:18\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"74000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"74000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(28, 2, '278537417', '', 'Irwan Mpapuarenda', '+6281251339976', '6281251339976', 'Jln KH  harun nafsi Gang Mesjid Block C no.7 Desa Rapak Dalam', 'KALIMANTAN TIMUR', 'Kota Samarinda', 'Loa Janan Ilir', '75131', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13724', 'KBJ+2', 3, 23, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"75131\", \"bump\": \"-\", \"city\": \"Kota Samarinda\", \"cogs\": \"0\", \"name\": \"Irwan Mpapuarenda\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281251339976\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jln KH  harun nafsi Gang Mesjid Block C no.7 Desa Rapak Dalam\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:36\", \"product\": \"A.1 Kacamata Baca & Jalan - 13724\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278537417\", \"province\": \"Kalimantan Timur\", \"quantity\": \"1\", \"utm_term\": \"120249460641980299\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 50-52 Tahun Plus +2.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 16:45\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2404:c0:1a23:50ab:a9eb:54ff:ec02:34c3\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"ig\", \"net_revenue\": \"119000\", \"subdistrict\": \"Loa Janan Ilir\", \"utm_content\": \"120249460641990299\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120249460641860299\", \"gross_revenue\": \"189000\", \"processing_at\": \"11-08-2026 - 18:36\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"70000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"70000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(29, 2, '278521401', '', 'Sumardi', '+6282300025622', '6282300025622', 'Jl bukit paku merlung rt 05 ds.tidar kuranji (sp 2)', 'JAMBI', 'Kab. Batang Hari', 'Maro Sebo Ilir', '36655', 'cod', 'real', 'MAYANG CS', 13, 'flix-idx', NULL, 'A.3 Kacamata Multifokus Photocromic 2 pcs', '13722', 'KMP+3', 1, 9, NULL, 2, 1000.000, 129000.00, 1, 129000.00, NULL, NULL, NULL, '{\"zip\": \"36655\", \"bump\": \"-\", \"city\": \"Kab. Batang Hari\", \"cogs\": \"0\", \"name\": \"Sumardi\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6282300025622\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jl bukit paku merlung rt 05 ds.tidar kuranji (sp 2)\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:21\", \"product\": \"A.3 Kacamata Multifokus Photocromic - 13722\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278521401\", \"province\": \"Jambi\", \"quantity\": \"1\", \"utm_term\": \"120247924626590080\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 129.000, Ukuran: Usia 60-64 Tahun Plus +3.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 13:00\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"2a09:bac1:6540:8::3c3:19\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"129000\", \"subdistrict\": \"Maro Sebo Ilir\", \"utm_content\": \"120247924626580080\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KMP\", \"utm_campaign\": \"120247924626570080\", \"gross_revenue\": \"174000\", \"processing_at\": \"11-08-2026 - 18:21\", \"product_price\": \"129000\", \"reseller_name\": \"\", \"shipping_cost\": \"45000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"45000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(30, 2, '278489447', '', 'Nur indra', '+6281354611243', '6281354611243', 'Komp. Jartaco 5 k no. 1 Kelurahan parang tambung', 'SULAWESI SELATAN', 'Kota Makassar', 'Tamalate', '90224', 'cod', 'real', 'MAYANG CS', 13, 'flix-spx', NULL, 'A.2 Kacamata Sporty Photocromic 2 pcs', '13724', 'KSP+2', 2, 14, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"90224\", \"bump\": \"-\", \"city\": \"Kota Makassar\", \"cogs\": \"0\", \"name\": \"Nur indra\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6281354611243\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Komp. Jartaco 5 k no. 1 Kelurahan parang tambung\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:00\", \"product\": \"A.2 Kacamata Sporty Photocromic - 13724\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278489447\", \"province\": \"Sulawesi Selatan\", \"quantity\": \"1\", \"utm_term\": \"120250213449770299\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 50-52 Tahun Plus +2.00, Warna: Orange Minimalis\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 05:08\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"140.213.1.216\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Tamalate\", \"utm_content\": \"120250213449760299\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KSP\", \"utm_campaign\": \"120250213449730299\", \"gross_revenue\": \"179000\", \"processing_at\": \"11-08-2026 - 18:00\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"60000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"60000\"}', '2026-08-11 04:57:18', '2026-08-11 04:57:18'),
(31, 2, '278485864', '', 'Margo Siswoyo', '+6285274593663', '6285274593663', 'Jln lingkar durian sebatang rt05 rw05 suka damai simpang SMPN 05 ujung batu KC ujung batu kb Rokan hulu prop Riau ro', 'RIAU', 'Kab. Rokan Hulu', 'Ujung Batu', '28553', 'cod', 'real', 'MAYANG CS', 13, 'spx', NULL, 'A.1 Kacamata Baca & Jalan 2 pcs', '13723', 'KBJ+2', 3, 23, NULL, 2, 1000.000, 119000.00, 1, 119000.00, NULL, NULL, NULL, '{\"zip\": \"28553\", \"bump\": \"-\", \"city\": \"Kab. Rokan Hulu\", \"cogs\": \"0\", \"name\": \"Margo Siswoyo\", \"tags\": \"\", \"email\": \"\", \"notes\": \"\", \"phone\": \"+6285274593663\", \"coupon\": \"\", \"status\": \"processing\", \"weight\": \"1000\", \"address\": \"Jln lingkar durian sebatang rt05 rw05 suka damai simpang SMPN 05 ujung batu KC ujung batu kb Rokan hulu prop Riau ro\", \"courier\": \"J&T - EZ\", \"paid_at\": \"11-08-2026 - 18:42\", \"product\": \"A.1 Kacamata Baca & Jalan - 13723\", \"cod_cost\": \"0\", \"discount\": \"0\", \"order_id\": \"278485864\", \"province\": \"Riau\", \"quantity\": \"1\", \"utm_term\": \"120248005455720390\", \"variation\": \"Promo: PROMO Beli 1 Dapat 2 - Rp 119.000, Ukuran: Usia 50-52 Tahun Plus +2.00\", \"bump_price\": \"0\", \"created_at\": \"11-08-2026 - 01:17\", \"handled_by\": \"MAYANG CS\", \"ip_address\": \"103.166.122.102\", \"order_type\": \"form\", \"other_cost\": \"0\", \"utm_medium\": \"paid\", \"utm_source\": \"fb\", \"net_revenue\": \"119000\", \"subdistrict\": \"Ujung Batu\", \"utm_content\": \"120248005455700390\", \"completed_at\": \"\", \"payment_info\": \"\", \"product_code\": \"KBJ\", \"utm_campaign\": \"120248005455620390\", \"gross_revenue\": \"170000\", \"processing_at\": \"11-08-2026 - 18:42\", \"product_price\": \"119000\", \"reseller_name\": \"\", \"shipping_cost\": \"51000\", \"payment_method\": \"cod\", \"payment_status\": \"paid\", \"receipt_number\": \"\", \"variation_code\": \"\", \"shipping_markup\": \"0\", \"shipping_method\": \"shipped\", \"dropshipper_name\": \"\", \"dropshipper_phone\": \"\", \"original_shipping_cost\": \"51000\"}', '2026-08-11 04:57:18', '2026-08-11 05:07:42');

-- --------------------------------------------------------

--
-- Table structure for table `spending_harians`
--

CREATE TABLE `spending_harians` (
  `id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `whitelist_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `spending` decimal(15,2) NOT NULL DEFAULT '0.00',
  `lead` int NOT NULL DEFAULT '0',
  `paid` int NOT NULL DEFAULT '0',
  `paid_ratio` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `cpa_lead` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cpa_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `spending_harians`
--

INSERT INTO `spending_harians` (`id`, `tanggal`, `user_id`, `whitelist_id`, `product_id`, `spending`, `lead`, `paid`, `paid_ratio`, `cpa_lead`, `cpa_paid`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '2026-08-01', 6, 1, 1, 253213.00, 2, 1, 50.0000, 126606.50, 253213.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(2, '2026-08-01', 6, 3, 1, 442650.00, 4, 4, 100.0000, 110662.50, 110662.50, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(3, '2026-08-01', 6, 1, 2, 635780.00, 14, 11, 79.0000, 45412.86, 57798.18, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(4, '2026-08-01', 6, 2, 2, 99504.00, 5, 3, 60.0000, 19900.80, 33168.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(5, '2026-08-02', 6, 1, 3, 182010.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(6, '2026-08-02', 6, 1, 1, 230131.00, 1, 1, 100.0000, 230131.00, 230131.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(7, '2026-08-02', 6, 3, 1, 385979.00, 4, 4, 100.0000, 96494.75, 96494.75, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(8, '2026-08-02', 6, 1, 2, 297048.00, 1, 0, 0.0000, 297048.00, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(9, '2026-08-02', 6, 2, 2, 53082.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(10, '2026-08-03', 6, 1, 1, 483056.00, 19, 4, 21.0000, 25424.00, 120764.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(11, '2026-08-03', 6, 2, 1, 85390.00, 2, 1, 50.0000, 42695.00, 85390.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(12, '2026-08-03', 6, 3, 1, 326979.00, 10, 3, 30.0000, 32697.90, 108993.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(13, '2026-08-03', 6, 1, 2, 905676.00, 47, 27, 57.0000, 19269.70, 33543.56, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(14, '2026-08-03', 6, 2, 2, 107524.00, 2, 1, 50.0000, 53762.00, 107524.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(15, '2026-08-04', 6, 1, 1, 499795.00, 7, 5, 71.0000, 71399.29, 99959.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(16, '2026-08-04', 6, 2, 1, 178416.00, 2, 1, 50.0000, 89208.00, 178416.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(17, '2026-08-04', 6, 3, 1, 1316376.00, 55, 16, 29.0000, 23934.11, 82273.50, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(18, '2026-08-04', 6, 1, 2, 779659.00, 25, 15, 60.0000, 31186.36, 51977.27, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(19, '2026-08-04', 6, 2, 2, 336335.00, 18, 9, 50.0000, 18685.28, 37370.56, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(20, '2026-08-05', 6, 1, 1, 361354.00, 6, 2, 33.0000, 60225.67, 180677.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(21, '2026-08-05', 6, 2, 1, 163627.00, 9, 1, 11.0000, 18180.78, 163627.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(22, '2026-08-05', 6, 3, 1, 949401.00, 29, 9, 31.0000, 32737.97, 105489.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(23, '2026-08-05', 6, 1, 2, 393831.00, 11, 4, 36.0000, 35802.82, 98457.75, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(24, '2026-08-05', 6, 2, 2, 268639.00, 12, 2, 17.0000, 22386.58, 134319.50, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(25, '2026-08-06', 6, 1, 1, 207543.00, 2, 0, 0.0000, 103771.50, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(26, '2026-08-06', 6, 2, 1, 78226.00, 1, 0, 0.0000, 78226.00, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(27, '2026-08-06', 6, 3, 1, 683307.00, 17, 5, 29.0000, 40194.53, 136661.40, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(28, '2026-08-06', 6, 1, 2, 514566.00, 18, 4, 22.0000, 28587.00, 128641.50, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(29, '2026-08-06', 6, 2, 2, 219821.00, 7, 2, 29.0000, 31403.00, 109910.50, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(30, '2026-08-06', 6, 5, 3, 434749.00, 4, 0, 0.0000, 108687.25, 0.00, NULL, '2026-08-11 00:56:42', '2026-08-11 00:56:42', NULL),
(31, '2026-08-07', 6, 1, 1, 129596.00, 1, 0, 0.0000, 129596.00, 0.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(32, '2026-08-07', 6, 2, 1, 70122.00, 1, 1, 100.0000, 70122.00, 70122.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(33, '2026-08-07', 6, 3, 1, 185398.00, 5, 1, 20.0000, 37079.60, 185398.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(34, '2026-08-07', 6, 1, 2, 305916.00, 3, 2, 67.0000, 101972.00, 152958.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(35, '2026-08-07', 6, 2, 2, 414819.00, 3, 1, 33.0000, 138273.00, 414819.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(36, '2026-08-07', 6, 5, 3, 412882.00, 3, 1, 33.0000, 137627.33, 412882.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(37, '2026-08-08', 6, 1, 2, 340201.00, 5, 2, 40.0000, 68040.20, 170100.50, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(38, '2026-08-08', 6, 2, 2, 104633.00, 1, 1, 100.0000, 104633.00, 104633.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(39, '2026-08-08', 6, 3, 1, 323423.00, 5, 2, 40.0000, 64684.60, 161711.50, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(40, '2026-08-08', 6, 5, 3, 517768.00, 3, 0, 0.0000, 172589.33, 0.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(41, '2026-08-09', 6, 1, 2, 502089.00, 8, 2, 25.0000, 62761.13, 251044.50, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(42, '2026-08-09', 6, 3, 1, 537693.00, 12, 5, 42.0000, 44807.75, 107538.60, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(43, '2026-08-09', 6, 5, 3, 653020.00, 12, 1, 8.0000, 54418.33, 653020.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(44, '2026-08-10', 6, 1, 1, 204037.00, 2, 0, 0.0000, 102018.50, 0.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(45, '2026-08-10', 6, 3, 1, 645389.00, 15, 5, 33.0000, 43025.93, 129077.80, NULL, '2026-08-11 01:00:30', '2026-08-12 01:51:58', NULL),
(46, '2026-08-10', 6, 1, 2, 870506.00, 15, 5, 33.0000, 58033.73, 174101.20, NULL, '2026-08-11 01:00:30', '2026-08-12 01:52:23', NULL),
(47, '2026-08-10', 6, 5, 3, 438336.00, 7, 0, 0.0000, 62619.43, 0.00, NULL, '2026-08-11 01:00:30', '2026-08-11 01:00:30', NULL),
(48, '2026-08-01', 4, 7, 11, 0.00, 13, 12, 92.0000, 0.00, 0.00, NULL, '2026-08-11 11:57:10', '2026-08-11 14:54:12', '2026-08-11 14:54:12'),
(49, '2026-08-01', 4, 16, 11, 0.00, 48, 43, 90.0000, 0.00, 0.00, NULL, '2026-08-11 11:57:10', '2026-08-11 14:54:12', '2026-08-11 14:54:12'),
(50, '2026-08-01', 4, 7, 3, 914630.00, 13, 12, 92.0000, 70356.15, 76219.17, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(51, '2026-08-01', 4, 16, 3, 1935178.00, 48, 43, 90.0000, 40316.21, 45004.14, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(52, '2026-08-01', 4, 7, 1, 2115243.00, 41, 38, 93.0000, 51591.29, 55664.29, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(53, '2026-08-01', 4, 15, 1, 3231352.00, 88, 70, 80.0000, 36719.91, 46162.17, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(54, '2026-08-01', 4, 16, 1, 1462552.00, 47, 35, 74.0000, 31118.13, 41787.20, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(55, '2026-08-01', 4, 17, 1, 420388.00, 10, 9, 90.0000, 42038.80, 46709.78, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(56, '2026-08-01', 4, 18, 1, 0.00, 6, 5, 83.0000, 0.00, 0.00, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(57, '2026-08-01', 4, 7, 2, 1911909.00, 45, 40, 89.0000, 42486.87, 47797.73, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(58, '2026-08-01', 4, 15, 2, 1547050.00, 19, 16, 84.0000, 81423.68, 96690.63, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(59, '2026-08-01', 4, 16, 2, 1500229.00, 50, 43, 86.0000, 30004.58, 34889.05, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(60, '2026-08-01', 4, 15, 12, 146614.00, 1, 1, 100.0000, 146614.00, 146614.00, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(61, '2026-08-01', 4, 16, 4, 324061.00, 9, 8, 89.0000, 36006.78, 40507.63, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(62, '2026-08-01', 4, 19, 5, 350146.00, 3, 3, 100.0000, 116715.33, 116715.33, NULL, '2026-08-11 11:57:10', '2026-08-12 02:33:47', '2026-08-12 02:33:47'),
(63, '2026-08-02', 4, 7, 3, 534759.00, 19, 15, 79.0000, 28145.21, 35650.60, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(64, '2026-08-02', 4, 16, 3, 1619555.00, 47, 42, 89.0000, 34458.62, 38560.83, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(65, '2026-08-02', 4, 7, 1, 948229.00, 26, 21, 81.0000, 36470.35, 45153.76, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(66, '2026-08-02', 4, 15, 1, 1784925.00, 90, 75, 83.0000, 19832.50, 23799.00, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(67, '2026-08-02', 4, 16, 1, 1737656.00, 56, 44, 79.0000, 31029.57, 39492.18, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(68, '2026-08-02', 4, 17, 1, 546734.00, 15, 12, 80.0000, 36448.93, 45561.17, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(69, '2026-08-02', 4, 18, 1, 413057.00, 6, 4, 67.0000, 68842.83, 103264.25, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(70, '2026-08-02', 4, 7, 2, 1867884.00, 45, 39, 87.0000, 41508.53, 47894.46, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(71, '2026-08-02', 4, 15, 2, 1034759.00, 31, 28, 90.0000, 33379.32, 36955.68, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(72, '2026-08-02', 4, 16, 2, 1015048.00, 28, 27, 96.0000, 36251.71, 37594.37, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(73, '2026-08-02', 4, 15, 12, 568849.00, 12, 9, 75.0000, 47404.08, 63205.44, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(74, '2026-08-02', 4, 16, 4, 59944.00, 5, 4, 80.0000, 11988.80, 14986.00, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(75, '2026-08-02', 4, 19, 5, 353714.00, 13, 10, 77.0000, 27208.77, 35371.40, NULL, '2026-08-11 12:15:00', '2026-08-12 02:57:21', '2026-08-12 02:57:21'),
(76, '2026-08-03', 4, 7, 3, 578857.00, 16, 14, 88.0000, 36178.56, 41346.93, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(77, '2026-08-03', 4, 16, 3, 1672496.00, 37, 31, 84.0000, 45202.59, 53951.48, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(78, '2026-08-03', 4, 7, 1, 1293579.00, 22, 17, 77.0000, 58799.05, 76092.88, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(79, '2026-08-03', 4, 15, 1, 2358272.00, 51, 42, 82.0000, 46240.63, 56149.33, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(80, '2026-08-03', 4, 16, 1, 2003393.00, 62, 50, 81.0000, 32312.79, 40067.86, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(81, '2026-08-03', 4, 17, 1, 611758.00, 14, 11, 79.0000, 43697.00, 55614.36, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(82, '2026-08-03', 4, 18, 1, 481311.00, 9, 8, 89.0000, 53479.00, 60163.88, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(83, '2026-08-03', 4, 7, 2, 1855979.00, 41, 38, 93.0000, 45267.78, 48841.55, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(84, '2026-08-03', 4, 15, 2, 1364810.00, 26, 24, 92.0000, 52492.69, 56867.08, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(85, '2026-08-03', 4, 16, 2, 1046221.00, 28, 25, 89.0000, 37365.04, 41848.84, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(86, '2026-08-03', 4, 15, 12, 430669.00, 6, 5, 83.0000, 71778.17, 86133.80, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(87, '2026-08-03', 4, 16, 4, 51774.00, 2, 1, 50.0000, 25887.00, 51774.00, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(88, '2026-08-03', 4, 19, 5, 683004.00, 18, 15, 83.0000, 37944.67, 45533.60, NULL, '2026-08-11 12:22:55', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(89, '2026-08-01', 7, 10, 3, 770305.00, 25, 17, 68.0000, 30812.20, 45312.06, NULL, '2026-08-11 12:51:11', '2026-08-11 13:02:54', NULL),
(90, '2026-08-01', 7, 10, 2, 933960.00, 31, 25, 81.0000, 30127.74, 37358.40, NULL, '2026-08-11 12:51:11', '2026-08-11 13:02:37', NULL),
(91, '2026-08-04', 4, 7, 3, 1083788.00, 28, 22, 79.0000, 38706.71, 49263.09, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(92, '2026-08-04', 4, 16, 3, 1537167.00, 38, 33, 87.0000, 40451.76, 46580.82, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(93, '2026-08-04', 4, 7, 1, 1537297.00, 32, 24, 75.0000, 48040.53, 64054.04, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(94, '2026-08-04', 4, 15, 1, 3493977.00, 63, 49, 78.0000, 55459.95, 71305.65, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(95, '2026-08-04', 4, 16, 1, 3080589.00, 69, 51, 74.0000, 44646.22, 60403.71, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(96, '2026-08-04', 4, 17, 1, 582591.00, 14, 10, 71.0000, 41613.64, 58259.10, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(97, '2026-08-04', 4, 18, 1, 431550.00, 7, 7, 100.0000, 61650.00, 61650.00, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(98, '2026-08-04', 4, 7, 2, 3488450.00, 110, 96, 87.0000, 31713.18, 36338.02, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(99, '2026-08-04', 4, 15, 2, 1413269.00, 13, 12, 92.0000, 108713.00, 117772.42, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(100, '2026-08-04', 4, 16, 2, 1189606.00, 29, 23, 79.0000, 41020.90, 51722.00, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(101, '2026-08-04', 4, 15, 12, 120631.00, 1, 1, 100.0000, 120631.00, 120631.00, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(102, '2026-08-04', 4, 16, 4, 123028.00, 7, 6, 86.0000, 17575.43, 20504.67, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(103, '2026-08-04', 4, 19, 5, 907906.00, 25, 22, 88.0000, 36316.24, 41268.45, NULL, '2026-08-11 12:53:45', '2026-08-12 03:00:30', '2026-08-12 03:00:30'),
(104, '2026-08-02', 7, 10, 3, 1072474.00, 29, 23, 79.0000, 36981.86, 46629.30, NULL, '2026-08-11 13:00:23', '2026-08-11 13:01:54', NULL),
(105, '2026-08-02', 7, 10, 2, 1166519.00, 29, 22, 76.0000, 40224.79, 53023.59, NULL, '2026-08-11 13:00:23', '2026-08-11 13:01:36', NULL),
(106, '2026-08-03', 7, 10, 3, 678653.00, 19, 16, 84.0000, 35718.58, 42415.81, NULL, '2026-08-11 13:06:34', '2026-08-11 13:06:34', NULL),
(107, '2026-08-03', 7, 10, 2, 767052.00, 31, 25, 81.0000, 24743.61, 30682.08, NULL, '2026-08-11 13:06:34', '2026-08-11 13:07:44', NULL),
(108, '2026-08-04', 7, 10, 3, 891859.00, 23, 16, 70.0000, 38776.48, 55741.19, NULL, '2026-08-11 13:08:58', '2026-08-11 13:10:11', NULL),
(109, '2026-08-04', 7, 10, 2, 600964.00, 14, 11, 79.0000, 42926.00, 54633.09, NULL, '2026-08-11 13:08:58', '2026-08-11 13:08:58', NULL),
(110, '2026-08-05', 7, 10, 3, 1126097.00, 32, 27, 84.0000, 35190.53, 41707.30, NULL, '2026-08-11 13:11:27', '2026-08-11 13:11:27', NULL),
(111, '2026-08-05', 7, 10, 2, 960623.00, 19, 15, 79.0000, 50559.11, 64041.53, NULL, '2026-08-11 13:11:27', '2026-08-11 13:11:55', NULL),
(112, '2026-08-06', 7, 10, 3, 1266594.00, 19, 15, 79.0000, 66662.84, 84439.60, NULL, '2026-08-11 13:13:14', '2026-08-11 13:13:14', NULL),
(113, '2026-08-06', 7, 10, 2, 723256.00, 17, 14, 82.0000, 42544.47, 51661.14, NULL, '2026-08-11 13:13:14', '2026-08-11 13:14:02', NULL),
(114, '2026-08-06', 7, 10, 1, 213845.00, 2, 1, 50.0000, 106922.50, 213845.00, NULL, '2026-08-11 13:13:14', '2026-08-11 13:13:32', NULL),
(115, '2026-08-07', 7, 10, 3, 1112870.00, 29, 21, 72.0000, 38374.83, 52993.81, NULL, '2026-08-11 13:18:47', '2026-08-11 13:19:13', NULL),
(116, '2026-08-07', 7, 10, 2, 1008359.00, 30, 22, 73.0000, 33611.97, 45834.50, NULL, '2026-08-11 13:18:47', '2026-08-11 13:18:47', NULL),
(117, '2026-08-01', 5, 8, 1, 1909424.00, 27, 24, 89.0000, 70719.41, 79559.33, NULL, '2026-08-11 13:20:52', '2026-08-11 13:20:52', NULL),
(118, '2026-08-01', 5, 9, 1, 625056.00, 23, 19, 83.0000, 27176.35, 32897.68, NULL, '2026-08-11 13:20:52', '2026-08-11 13:20:52', NULL),
(119, '2026-08-01', 5, 11, 1, 643123.00, 17, 15, 88.0000, 37830.76, 42874.87, NULL, '2026-08-11 13:20:52', '2026-08-11 13:20:52', NULL),
(120, '2026-08-01', 5, 12, 1, 356053.00, 5, 3, 60.0000, 71210.60, 118684.33, NULL, '2026-08-11 13:20:52', '2026-08-11 13:20:52', NULL),
(121, '2026-08-01', 5, 14, 1, 174477.00, 2, 2, 100.0000, 87238.50, 87238.50, NULL, '2026-08-11 13:20:52', '2026-08-11 13:20:52', NULL),
(122, '2026-08-11', 5, 9, 2, 139931.00, 2, 2, 100.0000, 69965.50, 69965.50, NULL, '2026-08-11 13:23:30', '2026-08-11 13:24:36', '2026-08-11 13:24:36'),
(123, '2026-08-11', 5, 11, 2, 1949314.00, 60, 56, 93.0000, 32488.57, 34809.18, NULL, '2026-08-11 13:23:30', '2026-08-11 13:24:50', '2026-08-11 13:24:50'),
(124, '2026-08-11', 5, 12, 2, 626488.00, 14, 10, 71.0000, 44749.14, 62648.80, NULL, '2026-08-11 13:23:30', '2026-08-11 13:24:55', '2026-08-11 13:24:55'),
(125, '2026-08-11', 5, 13, 2, 304254.00, 4, 4, 100.0000, 76063.50, 76063.50, NULL, '2026-08-11 13:23:30', '2026-08-11 13:25:00', '2026-08-11 13:25:00'),
(126, '2026-08-11', 5, 14, 2, 953060.00, 18, 17, 94.0000, 52947.78, 56062.35, NULL, '2026-08-11 13:23:30', '2026-08-11 13:25:05', '2026-08-11 13:25:05'),
(127, '2026-08-01', 5, 9, 2, 139931.00, 2, 2, 100.0000, 69965.50, 69965.50, NULL, '2026-08-11 13:26:44', '2026-08-11 13:26:44', NULL),
(128, '2026-08-01', 5, 11, 2, 1949314.00, 60, 56, 93.0000, 32488.57, 34809.18, NULL, '2026-08-11 13:26:44', '2026-08-11 13:26:44', NULL),
(129, '2026-08-01', 5, 12, 2, 626488.00, 14, 10, 71.0000, 44749.14, 62648.80, NULL, '2026-08-11 13:26:44', '2026-08-11 13:26:44', NULL),
(130, '2026-08-01', 5, 13, 2, 304254.00, 4, 4, 100.0000, 76063.50, 76063.50, NULL, '2026-08-11 13:26:44', '2026-08-11 13:26:44', NULL),
(131, '2026-08-01', 5, 14, 2, 953060.00, 18, 17, 94.0000, 52947.78, 56062.35, NULL, '2026-08-11 13:26:44', '2026-08-11 13:26:44', NULL),
(132, '2026-08-01', 5, 9, 12, 737972.00, 14, 13, 93.0000, 52712.29, 56767.08, NULL, '2026-08-11 13:27:53', '2026-08-11 13:27:53', NULL),
(133, '2026-08-01', 5, 12, 3, 170837.00, 1, 1, 100.0000, 170837.00, 170837.00, NULL, '2026-08-11 13:29:45', '2026-08-11 13:29:45', NULL),
(134, '2026-08-01', 5, 14, 3, 307264.00, 7, 7, 100.0000, 43894.86, 43894.86, NULL, '2026-08-11 13:29:45', '2026-08-11 13:29:45', NULL),
(135, '2026-08-02', 5, 8, 1, 1559271.00, 46, 37, 80.0000, 33897.20, 42142.46, NULL, '2026-08-11 13:38:34', '2026-08-11 13:38:34', NULL),
(136, '2026-08-02', 5, 9, 1, 528233.00, 10, 8, 80.0000, 52823.30, 66029.13, NULL, '2026-08-11 13:38:34', '2026-08-11 13:38:34', NULL),
(137, '2026-08-02', 5, 11, 1, 370247.00, 10, 9, 90.0000, 37024.70, 41138.56, NULL, '2026-08-11 13:38:34', '2026-08-11 13:38:34', NULL),
(138, '2026-08-02', 5, 12, 1, 149975.00, 1, 1, 100.0000, 149975.00, 149975.00, NULL, '2026-08-11 13:38:34', '2026-08-11 13:38:34', NULL),
(139, '2026-08-02', 5, 8, 2, 271160.00, 2, 2, 100.0000, 135580.00, 135580.00, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(140, '2026-08-02', 5, 9, 2, 591950.00, 8, 6, 75.0000, 73993.75, 98658.33, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(141, '2026-08-02', 5, 11, 2, 2025190.00, 63, 56, 89.0000, 32145.87, 36164.11, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(142, '2026-08-02', 5, 12, 2, 483559.00, 8, 7, 88.0000, 60444.88, 69079.86, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(143, '2026-08-02', 5, 13, 2, 319876.00, 6, 6, 100.0000, 53312.67, 53312.67, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(144, '2026-08-02', 5, 14, 2, 1059484.00, 25, 22, 88.0000, 42379.36, 48158.36, NULL, '2026-08-11 13:41:49', '2026-08-11 13:41:49', NULL),
(145, '2026-08-02', 5, 9, 12, 592967.00, 10, 9, 90.0000, 59296.70, 65885.22, NULL, '2026-08-11 13:43:17', '2026-08-11 13:43:17', NULL),
(146, '2026-08-03', 4, 15, 3, 91568.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 13:43:27', '2026-08-12 02:58:37', '2026-08-12 02:58:37'),
(147, '2026-08-02', 5, 12, 3, 302190.00, 2, 2, 100.0000, 151095.00, 151095.00, NULL, '2026-08-11 13:45:14', '2026-08-11 13:45:14', NULL),
(148, '2026-08-02', 5, 14, 3, 320272.00, 5, 4, 80.0000, 64054.40, 80068.00, NULL, '2026-08-11 13:45:14', '2026-08-11 13:45:14', NULL),
(149, '2026-08-02', 5, 13, 5, 480790.00, 10, 8, 80.0000, 48079.00, 60098.75, NULL, '2026-08-11 13:45:53', '2026-08-11 13:45:53', NULL),
(150, '2026-08-03', 5, 8, 1, 1106101.00, 31, 27, 87.0000, 35680.68, 40966.70, NULL, '2026-08-11 13:48:45', '2026-08-11 13:48:45', NULL),
(151, '2026-08-03', 5, 9, 1, 473606.00, 13, 12, 92.0000, 36431.23, 39467.17, NULL, '2026-08-11 13:48:45', '2026-08-11 13:48:45', NULL),
(152, '2026-08-03', 5, 11, 1, 214577.00, 2, 2, 100.0000, 107288.50, 107288.50, NULL, '2026-08-11 13:48:45', '2026-08-11 13:48:45', NULL),
(153, '2026-08-03', 5, 12, 1, 233657.00, 3, 3, 100.0000, 77885.67, 77885.67, NULL, '2026-08-11 13:48:45', '2026-08-11 13:48:45', NULL),
(154, '2026-08-03', 5, 14, 1, 265846.00, 5, 3, 60.0000, 53169.20, 88615.33, NULL, '2026-08-11 13:48:45', '2026-08-11 13:48:45', NULL),
(155, '2026-08-03', 5, 8, 2, 365618.00, 2, 2, 100.0000, 182809.00, 182809.00, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(156, '2026-08-03', 5, 9, 2, 603948.00, 11, 11, 100.0000, 54904.36, 54904.36, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(157, '2026-08-03', 5, 11, 2, 2113192.00, 45, 38, 84.0000, 46959.82, 55610.32, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(158, '2026-08-03', 5, 12, 2, 193223.00, 3, 2, 67.0000, 64407.67, 96611.50, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(159, '2026-08-03', 5, 13, 2, 242387.00, 10, 8, 80.0000, 24238.70, 30298.38, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(160, '2026-08-03', 5, 14, 2, 910507.00, 16, 16, 100.0000, 56906.69, 56906.69, NULL, '2026-08-11 13:50:12', '2026-08-11 13:50:12', NULL),
(161, '2026-08-03', 5, 9, 12, 641566.00, 15, 14, 93.0000, 42771.07, 45826.14, NULL, '2026-08-11 13:50:51', '2026-08-11 13:50:51', NULL),
(162, '2026-08-03', 5, 12, 3, 136922.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 13:52:30', '2026-08-11 13:52:30', NULL),
(163, '2026-08-03', 5, 14, 3, 455591.00, 9, 7, 78.0000, 50621.22, 65084.43, NULL, '2026-08-11 13:52:30', '2026-08-11 13:52:30', NULL),
(164, '2026-08-03', 5, 13, 5, 359165.00, 7, 7, 100.0000, 51309.29, 51309.29, NULL, '2026-08-11 13:53:15', '2026-08-11 13:53:15', NULL),
(165, '2026-08-05', 4, 7, 3, 1667253.00, 40, 32, 80.0000, 41681.33, 52101.66, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(166, '2026-08-05', 4, 16, 3, 1373124.00, 28, 21, 75.0000, 49040.14, 65386.86, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(167, '2026-08-05', 4, 7, 1, 1604839.00, 33, 31, 94.0000, 48631.48, 51769.00, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(168, '2026-08-05', 4, 15, 1, 3822429.00, 76, 61, 80.0000, 50295.12, 62662.77, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(169, '2026-08-05', 4, 16, 1, 2206367.00, 45, 35, 78.0000, 49030.38, 63039.06, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(170, '2026-08-05', 4, 17, 1, 455968.00, 6, 4, 67.0000, 75994.67, 113992.00, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(171, '2026-08-05', 4, 18, 1, 407851.00, 5, 4, 80.0000, 81570.20, 101962.75, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(172, '2026-08-05', 4, 7, 2, 2782177.00, 54, 44, 81.0000, 51521.80, 63231.30, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(173, '2026-08-05', 4, 15, 2, 1679590.00, 51, 43, 84.0000, 32933.14, 39060.23, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(174, '2026-08-05', 4, 16, 2, 1288914.00, 31, 24, 77.0000, 41577.87, 53704.75, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(175, '2026-08-05', 4, 15, 12, 223522.00, 3, 2, 67.0000, 74507.33, 111761.00, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(176, '2026-08-05', 4, 16, 4, 223887.00, 4, 4, 100.0000, 55971.75, 55971.75, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(177, '2026-08-05', 4, 19, 5, 750160.00, 9, 7, 78.0000, 83351.11, 107165.71, NULL, '2026-08-11 13:58:17', '2026-08-11 13:58:17', NULL),
(178, '2026-08-04', 5, 8, 1, 1461699.00, 26, 23, 88.0000, 56219.19, 63552.13, NULL, '2026-08-11 13:59:47', '2026-08-11 13:59:47', NULL),
(179, '2026-08-04', 5, 9, 1, 436522.00, 12, 9, 75.0000, 36376.83, 48502.44, NULL, '2026-08-11 13:59:47', '2026-08-11 13:59:47', NULL),
(180, '2026-08-04', 5, 11, 1, 334746.00, 8, 6, 75.0000, 41843.25, 55791.00, NULL, '2026-08-11 13:59:47', '2026-08-11 13:59:47', NULL),
(181, '2026-08-04', 5, 12, 1, 329952.00, 3, 2, 67.0000, 109984.00, 164976.00, NULL, '2026-08-11 13:59:47', '2026-08-11 13:59:47', NULL),
(182, '2026-08-04', 5, 14, 1, 186639.00, 2, 2, 100.0000, 93319.50, 93319.50, NULL, '2026-08-11 13:59:47', '2026-08-11 13:59:47', NULL),
(183, '2026-08-04', 5, 8, 2, 246047.00, 3, 3, 100.0000, 82015.67, 82015.67, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(184, '2026-08-04', 5, 9, 2, 307036.00, 8, 7, 88.0000, 38379.50, 43862.29, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(185, '2026-08-04', 5, 11, 2, 2461231.00, 64, 51, 80.0000, 38456.73, 48259.43, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(186, '2026-08-04', 5, 12, 2, 192354.00, 2, 2, 100.0000, 96177.00, 96177.00, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(187, '2026-08-04', 5, 13, 2, 363128.00, 12, 9, 75.0000, 30260.67, 40347.56, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(188, '2026-08-04', 5, 14, 2, 1040938.00, 15, 13, 87.0000, 69395.87, 80072.15, NULL, '2026-08-11 14:01:36', '2026-08-11 14:01:36', NULL),
(189, '2026-08-04', 5, 9, 12, 399313.00, 4, 3, 75.0000, 99828.25, 133104.33, NULL, '2026-08-11 14:02:11', '2026-08-11 14:02:11', NULL),
(190, '2026-08-04', 5, 11, 3, 86922.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 14:03:12', '2026-08-11 14:03:12', NULL),
(191, '2026-08-04', 5, 12, 3, 409286.00, 8, 5, 63.0000, 51160.75, 81857.20, NULL, '2026-08-11 14:03:12', '2026-08-11 14:03:12', NULL),
(192, '2026-08-04', 5, 14, 3, 886330.00, 28, 20, 71.0000, 31654.64, 44316.50, NULL, '2026-08-11 14:03:12', '2026-08-11 14:03:12', NULL),
(193, '2026-08-04', 5, 13, 5, 321538.00, 2, 2, 100.0000, 160769.00, 160769.00, NULL, '2026-08-11 14:04:20', '2026-08-11 14:04:20', NULL),
(194, '2026-08-05', 5, 8, 1, 1297339.00, 31, 26, 84.0000, 41849.65, 49897.65, NULL, '2026-08-11 14:07:10', '2026-08-11 14:09:39', NULL),
(195, '2026-08-05', 5, 9, 1, 596877.00, 14, 13, 93.0000, 42634.07, 45913.62, NULL, '2026-08-11 14:07:10', '2026-08-11 14:09:52', NULL),
(196, '2026-08-05', 5, 11, 1, 427704.00, 11, 10, 91.0000, 38882.18, 42770.40, NULL, '2026-08-11 14:07:10', '2026-08-11 14:10:05', NULL),
(197, '2026-08-05', 5, 12, 1, 194244.00, 2, 1, 50.0000, 97122.00, 194244.00, NULL, '2026-08-11 14:07:10', '2026-08-11 14:10:17', NULL),
(198, '2026-08-05', 5, 14, 1, 272040.00, 7, 4, 57.0000, 38862.86, 68010.00, NULL, '2026-08-11 14:07:10', '2026-08-11 14:11:00', NULL),
(199, '2026-08-05', 5, 8, 2, 293605.00, 10, 5, 50.0000, 29360.50, 58721.00, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(200, '2026-08-05', 5, 9, 2, 650649.00, 13, 10, 77.0000, 50049.92, 65064.90, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(201, '2026-08-05', 5, 11, 2, 1224238.00, 30, 28, 93.0000, 40807.93, 43722.79, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(202, '2026-08-05', 5, 12, 2, 546579.00, 9, 8, 89.0000, 60731.00, 68322.38, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(203, '2026-08-05', 5, 13, 2, 301550.00, 1, 1, 100.0000, 301550.00, 301550.00, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(204, '2026-08-05', 5, 14, 2, 1134774.00, 24, 15, 63.0000, 47282.25, 75651.60, NULL, '2026-08-11 14:09:10', '2026-08-11 14:09:10', NULL),
(205, '2026-08-05', 5, 9, 12, 566358.00, 11, 9, 82.0000, 51487.09, 62928.67, NULL, '2026-08-11 14:11:50', '2026-08-11 14:11:50', NULL),
(206, '2026-08-05', 5, 12, 3, 246749.00, 4, 3, 75.0000, 61687.25, 82249.67, NULL, '2026-08-11 14:12:55', '2026-08-11 14:12:55', NULL),
(207, '2026-08-05', 5, 14, 3, 507259.00, 8, 6, 75.0000, 63407.38, 84543.17, NULL, '2026-08-11 14:12:55', '2026-08-11 14:12:55', NULL),
(208, '2026-08-05', 5, 13, 5, 104182.00, 1, 1, 100.0000, 104182.00, 104182.00, NULL, '2026-08-11 14:13:20', '2026-08-11 14:13:20', NULL),
(209, '2026-08-05', 5, 13, 10, 137873.00, 3, 2, 67.0000, 45957.67, 68936.50, NULL, '2026-08-11 14:13:51', '2026-08-11 14:13:51', NULL),
(210, '2026-08-06', 5, 8, 1, 1650074.00, 37, 31, 84.0000, 44596.59, 53228.19, NULL, '2026-08-11 14:16:09', '2026-08-11 14:16:09', NULL),
(211, '2026-08-06', 5, 9, 1, 1114672.00, 21, 18, 86.0000, 53079.62, 61926.22, NULL, '2026-08-11 14:16:09', '2026-08-11 14:16:09', NULL),
(212, '2026-08-06', 5, 11, 1, 505206.00, 10, 9, 90.0000, 50520.60, 56134.00, NULL, '2026-08-11 14:16:09', '2026-08-11 14:16:09', NULL),
(213, '2026-08-06', 5, 12, 1, 402798.00, 10, 7, 70.0000, 40279.80, 57542.57, NULL, '2026-08-11 14:16:09', '2026-08-11 14:16:09', NULL),
(214, '2026-08-06', 5, 14, 1, 251792.00, 5, 4, 80.0000, 50358.40, 62948.00, NULL, '2026-08-11 14:16:09', '2026-08-11 14:16:09', NULL),
(215, '2026-08-06', 5, 8, 2, 607049.00, 19, 14, 74.0000, 31949.95, 43360.64, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(216, '2026-08-06', 5, 9, 2, 348908.00, 7, 7, 100.0000, 49844.00, 49844.00, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(217, '2026-08-06', 5, 11, 2, 1907500.00, 42, 36, 86.0000, 45416.67, 52986.11, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(218, '2026-08-06', 5, 12, 2, 595424.00, 11, 10, 91.0000, 54129.45, 59542.40, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(219, '2026-08-06', 5, 13, 2, 265455.00, 4, 4, 100.0000, 66363.75, 66363.75, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(220, '2026-08-06', 5, 14, 2, 1229262.00, 33, 24, 73.0000, 37250.36, 51219.25, NULL, '2026-08-11 14:17:54', '2026-08-11 14:17:54', NULL),
(221, '2026-08-06', 5, 9, 12, 731153.00, 18, 11, 61.0000, 40619.61, 66468.45, NULL, '2026-08-11 14:18:20', '2026-08-11 14:18:20', NULL),
(222, '2026-08-06', 5, 12, 3, 405612.00, 15, 11, 73.0000, 27040.80, 36873.82, NULL, '2026-08-11 14:19:02', '2026-08-11 14:19:02', NULL),
(223, '2026-08-06', 5, 14, 3, 367910.00, 2, 2, 100.0000, 183955.00, 183955.00, NULL, '2026-08-11 14:19:02', '2026-08-11 14:19:02', NULL),
(224, '2026-08-06', 5, 13, 10, 237366.00, 5, 4, 80.0000, 47473.20, 59341.50, NULL, '2026-08-11 14:19:49', '2026-08-11 14:19:49', NULL),
(225, '2026-08-06', 5, 13, 5, 228418.00, 6, 3, 50.0000, 38069.67, 76139.33, NULL, '2026-08-11 14:20:48', '2026-08-11 14:20:48', NULL),
(226, '2026-08-07', 5, 8, 2, 621611.00, 14, 10, 71.0000, 44400.79, 62161.10, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(227, '2026-08-07', 5, 9, 2, 669111.00, 15, 10, 67.0000, 44607.40, 66911.10, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(228, '2026-08-07', 5, 11, 2, 1837752.00, 47, 38, 81.0000, 39101.11, 48361.89, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(229, '2026-08-07', 5, 12, 2, 463260.00, 8, 5, 63.0000, 57907.50, 92652.00, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(230, '2026-08-07', 5, 13, 2, 322020.00, 8, 5, 63.0000, 40252.50, 64404.00, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(231, '2026-08-07', 5, 14, 2, 1648266.00, 41, 23, 56.0000, 40201.61, 71663.74, NULL, '2026-08-11 14:24:36', '2026-08-11 14:24:36', NULL),
(232, '2026-08-07', 5, 8, 1, 1371179.00, 35, 28, 80.0000, 39176.54, 48970.68, NULL, '2026-08-11 14:25:58', '2026-08-11 14:25:58', NULL),
(233, '2026-08-07', 5, 9, 1, 542403.00, 5, 5, 100.0000, 108480.60, 108480.60, NULL, '2026-08-11 14:25:58', '2026-08-11 14:25:58', NULL),
(234, '2026-08-07', 5, 11, 1, 558098.00, 10, 10, 100.0000, 55809.80, 55809.80, NULL, '2026-08-11 14:25:58', '2026-08-11 14:25:58', NULL),
(235, '2026-08-07', 5, 12, 1, 498414.00, 12, 10, 83.0000, 41534.50, 49841.40, NULL, '2026-08-11 14:25:58', '2026-08-11 14:25:58', NULL),
(236, '2026-08-07', 5, 14, 1, 341398.00, 6, 4, 67.0000, 56899.67, 85349.50, NULL, '2026-08-11 14:25:58', '2026-08-11 14:25:58', NULL),
(237, '2026-08-07', 5, 9, 12, 588847.00, 8, 7, 88.0000, 73605.88, 84121.00, NULL, '2026-08-11 14:27:31', '2026-08-11 14:27:31', NULL),
(238, '2026-08-07', 5, 12, 3, 249834.00, 2, 2, 100.0000, 124917.00, 124917.00, NULL, '2026-08-11 14:28:14', '2026-08-11 14:28:14', NULL),
(239, '2026-08-07', 5, 14, 3, 483313.00, 10, 5, 50.0000, 48331.30, 96662.60, NULL, '2026-08-11 14:28:14', '2026-08-11 14:28:14', NULL),
(240, '2026-08-07', 5, 13, 10, 172589.00, 2, 2, 100.0000, 86294.50, 86294.50, NULL, '2026-08-11 14:28:50', '2026-08-11 14:28:50', NULL),
(241, '2026-08-07', 5, 13, 5, 224225.00, 3, 3, 100.0000, 74741.67, 74741.67, NULL, '2026-08-11 14:30:21', '2026-08-11 14:30:21', NULL),
(242, '2026-08-06', 4, 7, 3, 1379635.00, 34, 27, 79.0000, 40577.50, 51097.59, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(243, '2026-08-06', 4, 16, 3, 2051968.00, 49, 41, 84.0000, 41876.90, 50048.00, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(244, '2026-08-06', 4, 7, 1, 1344806.00, 22, 22, 100.0000, 61127.55, 61127.55, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(245, '2026-08-06', 4, 15, 1, 3443595.00, 66, 56, 85.0000, 52175.68, 61492.77, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(246, '2026-08-06', 4, 16, 1, 2123820.00, 40, 28, 70.0000, 53095.50, 75850.71, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(247, '2026-08-06', 4, 17, 1, 551598.00, 18, 14, 78.0000, 30644.33, 39399.86, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(248, '2026-08-06', 4, 18, 1, 456541.00, 7, 7, 100.0000, 65220.14, 65220.14, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(249, '2026-08-06', 4, 7, 2, 2642526.00, 44, 35, 80.0000, 60057.41, 75500.74, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(250, '2026-08-06', 4, 15, 2, 1180545.00, 24, 14, 58.0000, 49189.38, 84324.64, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(251, '2026-08-06', 4, 16, 2, 1259357.00, 30, 26, 87.0000, 41978.57, 48436.81, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(252, '2026-08-06', 4, 15, 12, 56928.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(253, '2026-08-06', 4, 16, 4, 279695.00, 9, 8, 89.0000, 31077.22, 34961.88, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(254, '2026-08-06', 4, 19, 5, 504789.00, 15, 12, 80.0000, 33652.60, 42065.75, NULL, '2026-08-11 14:36:44', '2026-08-11 14:36:44', NULL),
(255, '2026-08-07', 4, 7, 3, 1659933.00, 44, 35, 80.0000, 37725.75, 47426.66, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(256, '2026-08-07', 4, 16, 3, 1753782.00, 45, 36, 80.0000, 38972.93, 48716.17, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(257, '2026-08-07', 4, 7, 1, 1632118.00, 29, 23, 79.0000, 56279.93, 70961.65, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(258, '2026-08-07', 4, 15, 1, 3170506.00, 56, 45, 80.0000, 56616.18, 70455.69, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(259, '2026-08-07', 4, 16, 1, 1897841.00, 32, 24, 75.0000, 59307.53, 79076.71, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(260, '2026-08-07', 4, 17, 1, 832516.00, 18, 14, 78.0000, 46250.89, 59465.43, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(261, '2026-08-07', 4, 18, 1, 410738.00, 8, 5, 63.0000, 51342.25, 82147.60, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(262, '2026-08-07', 4, 7, 2, 2028670.00, 46, 38, 83.0000, 44101.52, 53386.05, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(263, '2026-08-07', 4, 15, 2, 1016784.00, 23, 19, 83.0000, 44208.00, 53514.95, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(264, '2026-08-07', 4, 16, 2, 1061215.00, 30, 28, 93.0000, 35373.83, 37900.54, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(265, '2026-08-07', 4, 16, 4, 342675.00, 15, 12, 80.0000, 22845.00, 28556.25, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(266, '2026-08-07', 4, 19, 5, 579639.00, 5, 4, 80.0000, 115927.80, 144909.75, NULL, '2026-08-11 14:37:21', '2026-08-11 14:37:21', NULL),
(267, '2026-08-08', 4, 7, 3, 1733820.00, 39, 32, 82.0000, 44456.92, 54181.88, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(268, '2026-08-08', 4, 16, 3, 1490961.00, 29, 23, 79.0000, 51412.45, 64824.39, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(269, '2026-08-08', 4, 7, 1, 2337905.00, 52, 41, 79.0000, 44959.71, 57022.07, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(270, '2026-08-08', 4, 15, 1, 2460243.00, 52, 42, 81.0000, 47312.37, 58577.21, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(271, '2026-08-08', 4, 16, 1, 2501818.00, 42, 32, 76.0000, 59567.10, 78181.81, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(272, '2026-08-08', 4, 17, 1, 1010110.00, 23, 17, 74.0000, 43917.83, 59418.24, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(273, '2026-08-08', 4, 18, 1, 429646.00, 4, 2, 50.0000, 107411.50, 214823.00, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(274, '2026-08-08', 4, 7, 2, 3386922.00, 84, 74, 88.0000, 40320.50, 45769.22, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(275, '2026-08-08', 4, 15, 2, 1309658.00, 31, 25, 81.0000, 42247.03, 52386.32, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(276, '2026-08-08', 4, 16, 2, 1375765.00, 33, 26, 79.0000, 41689.85, 52914.04, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(277, '2026-08-08', 4, 16, 4, 300081.00, 10, 8, 80.0000, 30008.10, 37510.13, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(278, '2026-08-08', 4, 19, 5, 589410.00, 8, 7, 88.0000, 73676.25, 84201.43, NULL, '2026-08-11 14:37:54', '2026-08-11 14:37:54', NULL),
(279, '2026-08-09', 4, 7, 3, 1668925.00, 50, 43, 86.0000, 33378.50, 38812.21, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(280, '2026-08-09', 4, 16, 3, 1119208.00, 30, 24, 80.0000, 37306.93, 46633.67, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(281, '2026-08-09', 4, 7, 1, 2109849.00, 41, 28, 68.0000, 51459.73, 75351.75, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(282, '2026-08-09', 4, 15, 1, 2658520.00, 48, 42, 88.0000, 55385.83, 63298.10, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(283, '2026-08-09', 4, 16, 1, 2225898.00, 60, 41, 68.0000, 37098.30, 54290.20, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(284, '2026-08-09', 4, 17, 1, 651368.00, 12, 7, 58.0000, 54280.67, 93052.57, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(285, '2026-08-09', 4, 18, 1, 322891.00, 1, 1, 100.0000, 322891.00, 322891.00, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(286, '2026-08-09', 4, 7, 2, 3106947.00, 91, 82, 90.0000, 34142.27, 37889.60, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(287, '2026-08-09', 4, 15, 2, 920458.00, 18, 12, 67.0000, 51136.56, 76704.83, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(288, '2026-08-09', 4, 16, 2, 934536.00, 23, 17, 74.0000, 40632.00, 54972.71, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(289, '2026-08-09', 4, 16, 4, 257526.00, 12, 8, 67.0000, 21460.50, 32190.75, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(290, '2026-08-09', 4, 19, 5, 568205.00, 15, 12, 80.0000, 37880.33, 47350.42, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(291, '2026-08-10', 4, 7, 3, 1546955.00, 33, 27, 82.0000, 46877.42, 57294.63, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(292, '2026-08-10', 4, 16, 3, 1097632.00, 26, 23, 88.0000, 42216.62, 47723.13, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(293, '2026-08-10', 4, 7, 1, 874788.00, 17, 16, 94.0000, 51458.12, 54674.25, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(294, '2026-08-10', 4, 15, 1, 2856328.00, 75, 61, 81.0000, 38084.37, 46825.05, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(295, '2026-08-10', 4, 16, 1, 1850610.00, 30, 23, 77.0000, 61687.00, 80461.30, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(296, '2026-08-10', 4, 17, 1, 1001174.00, 14, 9, 64.0000, 71512.43, 111241.56, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(297, '2026-08-10', 4, 18, 1, 279697.00, 3, 2, 67.0000, 93232.33, 139848.50, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(298, '2026-08-10', 4, 7, 2, 2612488.00, 61, 54, 89.0000, 42827.67, 48379.41, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(299, '2026-08-10', 4, 15, 2, 1189451.00, 23, 16, 70.0000, 51715.26, 74340.69, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(300, '2026-08-10', 4, 16, 2, 931176.00, 33, 27, 82.0000, 28217.45, 34488.00, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(301, '2026-08-10', 4, 16, 4, 153234.00, 6, 5, 83.0000, 25539.00, 30646.80, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(302, '2026-08-10', 4, 19, 5, 511962.00, 12, 10, 83.0000, 42663.50, 51196.20, NULL, '2026-08-11 14:39:30', '2026-08-11 14:39:30', NULL),
(303, '2026-08-08', 5, 8, 1, 1849990.00, 44, 32, 73.0000, 42045.23, 57812.19, NULL, '2026-08-11 14:39:35', '2026-08-11 14:39:35', NULL),
(304, '2026-08-08', 5, 9, 1, 727219.00, 11, 11, 100.0000, 66110.82, 66110.82, NULL, '2026-08-11 14:39:35', '2026-08-11 14:39:35', NULL),
(305, '2026-08-08', 5, 11, 1, 356499.00, 5, 3, 60.0000, 71299.80, 118833.00, NULL, '2026-08-11 14:39:35', '2026-08-11 14:39:35', NULL),
(306, '2026-08-08', 5, 12, 1, 428057.00, 9, 6, 67.0000, 47561.89, 71342.83, NULL, '2026-08-11 14:39:35', '2026-08-11 14:39:35', NULL),
(307, '2026-08-08', 5, 14, 1, 308489.00, 4, 4, 100.0000, 77122.25, 77122.25, NULL, '2026-08-11 14:39:35', '2026-08-11 14:39:35', NULL),
(308, '2026-08-08', 5, 8, 2, 1512587.00, 50, 38, 76.0000, 30251.74, 39804.92, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(309, '2026-08-08', 5, 9, 2, 754896.00, 10, 7, 70.0000, 75489.60, 107842.29, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(310, '2026-08-08', 5, 11, 2, 2373519.00, 60, 50, 83.0000, 39558.65, 47470.38, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(311, '2026-08-08', 5, 12, 2, 558891.00, 11, 9, 82.0000, 50808.27, 62099.00, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(312, '2026-08-08', 5, 13, 2, 282507.00, 8, 6, 75.0000, 35313.38, 47084.50, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(313, '2026-08-08', 5, 14, 2, 1179318.00, 31, 25, 81.0000, 38042.52, 47172.72, NULL, '2026-08-11 14:42:56', '2026-08-11 14:42:56', NULL),
(314, '2026-08-08', 5, 9, 12, 499767.00, 8, 6, 75.0000, 62470.88, 83294.50, NULL, '2026-08-11 14:43:36', '2026-08-11 14:43:36', NULL),
(315, '2026-08-08', 5, 12, 3, 292385.00, 3, 2, 67.0000, 97461.67, 146192.50, NULL, '2026-08-11 14:44:42', '2026-08-11 14:44:42', NULL),
(316, '2026-08-08', 5, 14, 3, 465205.00, 9, 7, 78.0000, 51689.44, 66457.86, NULL, '2026-08-11 14:44:42', '2026-08-11 14:44:42', NULL),
(317, '2026-08-08', 5, 13, 10, 136325.00, 2, 2, 100.0000, 68162.50, 68162.50, NULL, '2026-08-11 14:45:10', '2026-08-11 14:45:10', NULL),
(318, '2026-08-08', 5, 13, 5, 344383.00, 4, 4, 100.0000, 86095.75, 86095.75, NULL, '2026-08-11 14:45:41', '2026-08-11 14:45:41', NULL),
(319, '2026-08-09', 5, 8, 1, 1967362.00, 52, 39, 75.0000, 37833.88, 50445.18, NULL, '2026-08-11 14:50:12', '2026-08-11 14:50:12', NULL),
(320, '2026-08-09', 5, 9, 1, 624528.00, 16, 14, 88.0000, 39033.00, 44609.14, NULL, '2026-08-11 14:50:12', '2026-08-11 14:50:12', NULL),
(321, '2026-08-09', 5, 11, 1, 395327.00, 6, 5, 83.0000, 65887.83, 79065.40, NULL, '2026-08-11 14:50:12', '2026-08-11 14:50:12', NULL),
(322, '2026-08-09', 5, 12, 1, 233252.00, 1, 1, 100.0000, 233252.00, 233252.00, NULL, '2026-08-11 14:50:12', '2026-08-11 14:50:12', NULL),
(323, '2026-08-09', 5, 14, 1, 232094.00, 1, 1, 100.0000, 232094.00, 232094.00, NULL, '2026-08-11 14:50:12', '2026-08-11 14:50:12', NULL),
(324, '2026-08-09', 5, 8, 2, 1744151.00, 46, 31, 67.0000, 37916.33, 56262.94, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(325, '2026-08-09', 5, 9, 2, 851417.00, 15, 11, 73.0000, 56761.13, 77401.55, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(326, '2026-08-09', 5, 11, 2, 2865042.00, 65, 56, 86.0000, 44077.57, 51161.46, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(327, '2026-08-09', 5, 12, 2, 400511.00, 3, 3, 100.0000, 133503.67, 133503.67, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(328, '2026-08-09', 5, 13, 2, 495995.00, 11, 11, 100.0000, 45090.45, 45090.45, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(329, '2026-08-09', 5, 14, 2, 1470394.00, 29, 19, 66.0000, 50703.24, 77389.16, NULL, '2026-08-11 14:51:45', '2026-08-11 14:51:45', NULL),
(330, '2026-08-09', 5, 9, 12, 562289.00, 11, 10, 91.0000, 51117.18, 56228.90, NULL, '2026-08-11 14:52:26', '2026-08-11 14:52:26', NULL),
(331, '2026-08-09', 5, 12, 3, 213482.00, 3, 3, 100.0000, 71160.67, 71160.67, NULL, '2026-08-11 14:52:59', '2026-08-11 14:52:59', NULL),
(332, '2026-08-09', 5, 14, 3, 563082.00, 8, 5, 63.0000, 70385.25, 112616.40, NULL, '2026-08-11 14:52:59', '2026-08-11 14:52:59', NULL),
(333, '2026-08-09', 5, 13, 10, 185402.00, 2, 2, 100.0000, 92701.00, 92701.00, NULL, '2026-08-11 14:53:26', '2026-08-11 14:53:26', NULL),
(334, '2026-08-09', 5, 13, 6, 180781.00, 7, 7, 100.0000, 25825.86, 25825.86, NULL, '2026-08-11 14:53:55', '2026-08-11 14:53:55', NULL),
(335, '2026-08-10', 5, 8, 1, 1191185.00, 32, 27, 84.0000, 37224.53, 44117.96, NULL, '2026-08-11 14:56:58', '2026-08-11 14:56:58', NULL),
(336, '2026-08-10', 5, 9, 1, 187260.00, 3, 3, 100.0000, 62420.00, 62420.00, NULL, '2026-08-11 14:56:58', '2026-08-11 14:56:58', NULL),
(337, '2026-08-10', 5, 11, 1, 295031.00, 4, 2, 50.0000, 73757.75, 147515.50, NULL, '2026-08-11 14:56:58', '2026-08-11 14:56:58', NULL),
(338, '2026-08-10', 5, 12, 1, 213949.00, 2, 2, 100.0000, 106974.50, 106974.50, NULL, '2026-08-11 14:56:58', '2026-08-11 14:56:58', NULL),
(339, '2026-08-10', 5, 14, 1, 57692.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 14:56:58', '2026-08-11 14:56:58', NULL),
(340, '2026-08-10', 5, 8, 2, 1322237.00, 24, 15, 63.0000, 55093.21, 88149.13, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(341, '2026-08-10', 5, 9, 2, 471090.00, 10, 6, 60.0000, 47109.00, 78515.00, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(342, '2026-08-10', 5, 11, 2, 2215793.00, 56, 44, 79.0000, 39567.73, 50358.93, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(343, '2026-08-10', 5, 12, 2, 275532.00, 4, 3, 75.0000, 68883.00, 91844.00, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(344, '2026-08-10', 5, 13, 2, 240043.00, 2, 1, 50.0000, 120021.50, 240043.00, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(345, '2026-08-10', 5, 14, 2, 783822.00, 21, 19, 90.0000, 37324.86, 41253.79, NULL, '2026-08-11 14:58:39', '2026-08-11 14:58:39', NULL),
(346, '2026-08-10', 5, 12, 3, 163748.00, 2, 0, 0.0000, 81874.00, 0.00, NULL, '2026-08-11 14:59:58', '2026-08-11 14:59:58', NULL),
(347, '2026-08-10', 5, 14, 3, 513163.00, 14, 11, 79.0000, 36654.50, 46651.18, NULL, '2026-08-11 14:59:58', '2026-08-11 14:59:58', NULL),
(348, '2026-08-10', 5, 9, 12, 467442.00, 11, 9, 82.0000, 42494.73, 51938.00, NULL, '2026-08-11 15:00:28', '2026-08-11 15:00:28', NULL),
(349, '2026-08-10', 5, 13, 10, 116589.00, 1, 1, 100.0000, 116589.00, 116589.00, NULL, '2026-08-11 15:01:00', '2026-08-11 15:01:00', NULL),
(350, '2026-08-10', 5, 13, 5, 104311.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-11 15:01:30', '2026-08-11 15:01:30', NULL),
(351, '2026-08-11', 6, 1, 1, 156820.00, 2, 0, 0.0000, 78410.00, 0.00, NULL, '2026-08-12 01:54:34', '2026-08-12 01:54:34', NULL),
(352, '2026-08-11', 6, 3, 1, 1124683.00, 22, 6, 27.0000, 51121.95, 187447.17, NULL, '2026-08-12 01:54:34', '2026-08-12 01:54:34', NULL),
(353, '2026-08-11', 6, 1, 2, 1210029.00, 24, 6, 25.0000, 50417.88, 201671.50, NULL, '2026-08-12 01:54:34', '2026-08-12 01:54:34', NULL),
(354, '2026-08-11', 6, 5, 3, 1291135.00, 17, 6, 35.0000, 75949.12, 215189.17, NULL, '2026-08-12 01:54:34', '2026-08-12 01:54:34', NULL),
(355, '2026-08-01', 4, 7, 3, 914630.00, 13, 12, 92.0000, 70356.15, 76219.17, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(356, '2026-08-01', 4, 16, 3, 1935178.00, 48, 43, 90.0000, 40316.21, 45004.14, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(357, '2026-08-01', 4, 7, 1, 2116808.00, 41, 38, 93.0000, 51629.46, 55705.47, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(358, '2026-08-01', 4, 15, 1, 3231352.00, 88, 70, 80.0000, 36719.91, 46162.17, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(359, '2026-08-01', 4, 16, 1, 2432869.00, 47, 35, 74.0000, 51763.17, 69510.54, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(360, '2026-08-01', 4, 17, 1, 420388.00, 10, 9, 90.0000, 42038.80, 46709.78, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(361, '2026-08-01', 4, 18, 1, 367635.00, 6, 5, 83.0000, 61272.50, 73527.00, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(362, '2026-08-01', 4, 7, 2, 2005934.00, 45, 40, 89.0000, 44576.31, 50148.35, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(363, '2026-08-01', 4, 15, 2, 1547050.00, 19, 16, 84.0000, 81423.68, 96690.63, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(364, '2026-08-01', 4, 16, 2, 1500229.00, 50, 43, 86.0000, 30004.58, 34889.05, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL);
INSERT INTO `spending_harians` (`id`, `tanggal`, `user_id`, `whitelist_id`, `product_id`, `spending`, `lead`, `paid`, `paid_ratio`, `cpa_lead`, `cpa_paid`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(365, '2026-08-01', 4, 15, 12, 146614.00, 1, 1, 100.0000, 146614.00, 146614.00, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(366, '2026-08-01', 4, 16, 4, 324190.00, 9, 8, 89.0000, 36021.11, 40523.75, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(367, '2026-08-01', 4, 19, 5, 350146.00, 3, 3, 100.0000, 116715.33, 116715.33, NULL, '2026-08-12 02:36:15', '2026-08-12 02:36:15', NULL),
(368, '2026-08-02', 4, 7, 3, 534759.00, 19, 15, 79.0000, 28145.21, 35650.60, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(369, '2026-08-02', 4, 16, 3, 1619555.00, 47, 42, 89.0000, 34458.62, 38560.83, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(370, '2026-08-02', 4, 7, 1, 1381755.00, 26, 21, 81.0000, 53144.42, 65797.86, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(371, '2026-08-02', 4, 15, 1, 2811011.00, 90, 75, 83.0000, 31233.46, 37480.15, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(372, '2026-08-02', 4, 16, 1, 2182640.00, 56, 44, 79.0000, 38975.71, 49605.45, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(373, '2026-08-02', 4, 17, 1, 546734.00, 15, 12, 80.0000, 36448.93, 45561.17, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(374, '2026-08-02', 4, 18, 1, 413057.00, 6, 4, 67.0000, 68842.83, 103264.25, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(375, '2026-08-02', 4, 7, 2, 1922791.00, 45, 39, 87.0000, 42728.69, 49302.33, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(376, '2026-08-02', 4, 15, 2, 1034759.00, 31, 28, 90.0000, 33379.32, 36955.68, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(377, '2026-08-02', 4, 16, 2, 1015048.00, 28, 27, 96.0000, 36251.71, 37594.37, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(378, '2026-08-02', 4, 15, 12, 568849.00, 12, 9, 75.0000, 47404.08, 63205.44, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(379, '2026-08-02', 4, 16, 4, 178832.00, 5, 4, 80.0000, 35766.40, 44708.00, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(380, '2026-08-02', 4, 19, 5, 353714.00, 13, 10, 77.0000, 27208.77, 35371.40, NULL, '2026-08-12 02:58:05', '2026-08-12 02:58:05', NULL),
(381, '2026-08-03', 4, 7, 3, 578857.00, 16, 14, 88.0000, 36178.56, 41346.93, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(382, '2026-08-03', 4, 16, 3, 1672496.00, 37, 31, 84.0000, 45202.59, 53951.48, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(383, '2026-08-03', 4, 7, 1, 1356465.00, 22, 17, 77.0000, 61657.50, 79792.06, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(384, '2026-08-03', 4, 15, 1, 2444208.00, 51, 42, 82.0000, 47925.65, 58195.43, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(385, '2026-08-03', 4, 16, 1, 2003393.00, 62, 50, 81.0000, 32312.79, 40067.86, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(386, '2026-08-03', 4, 17, 1, 611758.00, 14, 11, 79.0000, 43697.00, 55614.36, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(387, '2026-08-03', 4, 18, 1, 481311.00, 9, 8, 89.0000, 53479.00, 60163.88, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(388, '2026-08-03', 4, 7, 2, 1907752.00, 41, 38, 93.0000, 46530.54, 50204.00, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(389, '2026-08-03', 4, 15, 2, 1364810.00, 26, 24, 92.0000, 52492.69, 56867.08, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(390, '2026-08-03', 4, 16, 2, 1046221.00, 28, 25, 89.0000, 37365.04, 41848.84, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(391, '2026-08-03', 4, 15, 12, 430669.00, 6, 5, 83.0000, 71778.17, 86133.80, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(392, '2026-08-03', 4, 16, 4, 177292.00, 2, 1, 50.0000, 88646.00, 177292.00, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(393, '2026-08-03', 4, 19, 5, 683004.00, 18, 15, 83.0000, 37944.67, 45533.60, NULL, '2026-08-12 02:59:57', '2026-08-12 02:59:57', NULL),
(394, '2026-08-04', 4, 7, 3, 1083788.00, 28, 22, 79.0000, 38706.71, 49263.09, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(395, '2026-08-04', 4, 16, 3, 1537167.00, 38, 33, 87.0000, 40451.76, 46580.82, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(396, '2026-08-04', 4, 7, 1, 1661926.00, 32, 24, 75.0000, 51935.19, 69246.92, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(397, '2026-08-04', 4, 15, 1, 3493977.00, 63, 49, 78.0000, 55459.95, 71305.65, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(398, '2026-08-04', 4, 16, 1, 3080589.00, 69, 51, 74.0000, 44646.22, 60403.71, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(399, '2026-08-04', 4, 17, 1, 582591.00, 14, 10, 71.0000, 41613.64, 58259.10, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(400, '2026-08-04', 4, 18, 1, 431550.00, 7, 7, 100.0000, 61650.00, 61650.00, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(401, '2026-08-04', 4, 7, 2, 3609306.00, 110, 96, 87.0000, 32811.87, 37596.94, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(402, '2026-08-04', 4, 15, 2, 1413269.00, 13, 12, 92.0000, 108713.00, 117772.42, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(403, '2026-08-04', 4, 16, 2, 1189606.00, 29, 23, 79.0000, 41020.90, 51722.00, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(404, '2026-08-04', 4, 15, 12, 120631.00, 1, 1, 100.0000, 120631.00, 120631.00, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(405, '2026-08-04', 4, 16, 4, 219985.00, 7, 6, 86.0000, 31426.43, 36664.17, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(406, '2026-08-04', 4, 19, 5, 907906.00, 25, 22, 88.0000, 36316.24, 41268.45, NULL, '2026-08-12 03:01:12', '2026-08-12 03:01:12', NULL),
(407, '2026-08-11', 5, 8, 1, 1172173.00, 58, 42, 72.0000, 20209.88, 27908.88, NULL, '2026-08-12 06:14:15', '2026-08-12 06:14:15', NULL),
(408, '2026-08-11', 5, 9, 1, 377788.00, 15, 13, 87.0000, 25185.87, 29060.62, NULL, '2026-08-12 06:14:15', '2026-08-12 06:14:15', NULL),
(409, '2026-08-11', 5, 11, 1, 322990.00, 7, 6, 86.0000, 46141.43, 53831.67, NULL, '2026-08-12 06:14:15', '2026-08-12 06:14:35', NULL),
(410, '2026-08-11', 5, 12, 1, 109851.00, 1, 1, 100.0000, 109851.00, 109851.00, NULL, '2026-08-12 06:14:15', '2026-08-12 06:15:00', NULL),
(411, '2026-08-11', 5, 14, 1, 44880.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-12 06:14:15', '2026-08-12 06:15:10', NULL),
(412, '2026-08-11', 5, 8, 2, 1555846.00, 31, 22, 71.0000, 50188.58, 70720.27, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(413, '2026-08-11', 5, 9, 2, 467562.00, 11, 9, 82.0000, 42505.64, 51951.33, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(414, '2026-08-11', 5, 11, 2, 2850310.00, 89, 77, 87.0000, 32025.96, 37017.01, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(415, '2026-08-11', 5, 12, 2, 217856.00, 3, 3, 100.0000, 72618.67, 72618.67, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(416, '2026-08-11', 5, 13, 2, 186477.00, 1, 1, 100.0000, 186477.00, 186477.00, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(417, '2026-08-11', 5, 14, 2, 1301541.00, 45, 40, 89.0000, 28923.13, 32538.53, NULL, '2026-08-12 06:16:31', '2026-08-12 06:16:31', NULL),
(418, '2026-08-11', 5, 9, 12, 772996.00, 11, 7, 64.0000, 70272.36, 110428.00, NULL, '2026-08-12 06:17:31', '2026-08-12 06:17:31', NULL),
(419, '2026-08-11', 5, 12, 3, 191749.00, 2, 2, 100.0000, 95874.50, 95874.50, NULL, '2026-08-12 06:18:04', '2026-08-12 06:18:04', NULL),
(420, '2026-08-11', 5, 14, 3, 521406.00, 15, 8, 53.0000, 34760.40, 65175.75, NULL, '2026-08-12 06:18:04', '2026-08-12 06:18:04', NULL),
(421, '2026-08-11', 5, 13, 10, 218181.00, 3, 2, 67.0000, 72727.00, 109090.50, NULL, '2026-08-12 06:18:40', '2026-08-12 06:18:40', NULL),
(422, '2026-08-11', 5, 13, 5, 311519.00, 8, 6, 75.0000, 38939.88, 51919.83, NULL, '2026-08-12 06:19:45', '2026-08-12 06:19:45', NULL),
(423, '2026-08-12', 6, 1, 1, 151725.00, 1, 1, 100.0000, 151725.00, 151725.00, NULL, '2026-08-13 01:07:24', '2026-08-13 01:07:24', NULL),
(424, '2026-08-12', 6, 3, 1, 1405409.00, 35, 20, 57.0000, 40154.54, 70270.45, NULL, '2026-08-13 01:07:24', '2026-08-13 01:07:24', NULL),
(425, '2026-08-12', 6, 20, 1, 189095.00, 1, 0, 0.0000, 189095.00, 0.00, NULL, '2026-08-13 01:07:24', '2026-08-13 01:07:24', NULL),
(426, '2026-08-12', 6, 1, 2, 861582.00, 20, 10, 50.0000, 43079.10, 86158.20, NULL, '2026-08-13 01:07:24', '2026-08-13 01:07:24', NULL),
(427, '2026-08-12', 6, 5, 3, 936242.00, 11, 6, 55.0000, 85112.91, 156040.33, NULL, '2026-08-13 01:07:24', '2026-08-13 01:07:24', NULL),
(428, '2026-08-12', 5, 12, 3, 175288.00, 2, 2, 100.0000, 87644.00, 87644.00, NULL, '2026-08-13 14:54:32', '2026-08-13 14:54:32', NULL),
(429, '2026-08-12', 5, 14, 3, 574024.00, 15, 13, 87.0000, 38268.27, 44155.69, NULL, '2026-08-13 14:54:32', '2026-08-13 14:54:32', NULL),
(430, '2026-08-12', 5, 8, 2, 2083776.00, 43, 35, 81.0000, 48459.91, 59536.46, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(431, '2026-08-12', 5, 9, 2, 610629.00, 7, 6, 86.0000, 87232.71, 101771.50, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(432, '2026-08-12', 5, 11, 2, 3038056.00, 92, 72, 78.0000, 33022.35, 42195.22, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(433, '2026-08-12', 5, 12, 2, 723780.00, 16, 10, 63.0000, 45236.25, 72378.00, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(434, '2026-08-12', 5, 13, 2, 602486.00, 19, 15, 79.0000, 31709.79, 40165.73, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(435, '2026-08-12', 5, 14, 2, 1251167.00, 17, 14, 82.0000, 73598.06, 89369.07, NULL, '2026-08-13 14:56:07', '2026-08-13 14:56:07', NULL),
(436, '2026-08-12', 5, 8, 1, 2312675.00, 69, 54, 78.0000, 33517.03, 42827.31, NULL, '2026-08-13 14:57:35', '2026-08-13 14:57:35', NULL),
(437, '2026-08-12', 5, 9, 1, 487144.00, 14, 13, 93.0000, 34796.00, 37472.62, NULL, '2026-08-13 14:57:35', '2026-08-13 14:57:35', NULL),
(438, '2026-08-12', 5, 11, 1, 669686.00, 13, 10, 77.0000, 51514.31, 66968.60, NULL, '2026-08-13 14:57:35', '2026-08-13 14:57:35', NULL),
(439, '2026-08-12', 5, 12, 1, 205108.00, 1, 1, 100.0000, 205108.00, 205108.00, NULL, '2026-08-13 14:57:35', '2026-08-13 14:57:35', NULL),
(440, '2026-08-12', 5, 14, 1, 172910.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-13 14:57:35', '2026-08-13 14:57:35', NULL),
(441, '2026-08-12', 5, 9, 12, 861716.00, 12, 11, 92.0000, 71809.67, 78337.82, NULL, '2026-08-13 14:58:12', '2026-08-13 14:58:12', NULL),
(442, '2026-08-12', 5, 13, 5, 293239.00, 7, 5, 71.0000, 41891.29, 58647.80, NULL, '2026-08-13 14:58:47', '2026-08-13 14:58:47', NULL),
(443, '2026-08-11', 4, 7, 3, 2774239.00, 58, 50, 86.0000, 47831.71, 55484.78, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(444, '2026-08-11', 4, 16, 3, 1278958.00, 28, 25, 89.0000, 45677.07, 51158.32, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(445, '2026-08-11', 4, 7, 1, 893727.00, 13, 10, 77.0000, 68748.23, 89372.70, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(446, '2026-08-11', 4, 15, 1, 2974967.00, 64, 52, 81.0000, 46483.86, 57210.90, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(447, '2026-08-11', 4, 16, 1, 1401831.00, 26, 20, 77.0000, 53916.58, 70091.55, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(448, '2026-08-11', 4, 17, 1, 333511.00, 5, 4, 80.0000, 66702.20, 83377.75, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(449, '2026-08-11', 4, 18, 1, 209424.00, 3, 3, 100.0000, 69808.00, 69808.00, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(450, '2026-08-11', 4, 7, 2, 2571488.00, 52, 46, 88.0000, 49451.69, 55901.91, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(451, '2026-08-11', 4, 15, 2, 890683.00, 14, 11, 79.0000, 63620.21, 80971.18, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(452, '2026-08-11', 4, 16, 2, 1241442.00, 36, 32, 89.0000, 34484.50, 38795.06, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(453, '2026-08-11', 4, 16, 4, 909498.00, 21, 14, 67.0000, 43309.43, 64964.14, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(454, '2026-08-11', 4, 19, 5, 405083.00, 6, 4, 67.0000, 67513.83, 101270.75, NULL, '2026-08-14 00:48:03', '2026-08-14 00:48:03', NULL),
(455, '2026-08-12', 4, 7, 3, 2351899.00, 48, 41, 85.0000, 48997.90, 57363.39, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(456, '2026-08-12', 4, 16, 3, 1097486.00, 18, 16, 89.0000, 60971.44, 68592.88, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(457, '2026-08-12', 4, 7, 1, 1228524.00, 26, 20, 77.0000, 47250.92, 61426.20, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(458, '2026-08-12', 4, 15, 1, 3194731.00, 64, 48, 75.0000, 49917.67, 66556.90, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(459, '2026-08-12', 4, 16, 1, 1730431.00, 26, 23, 88.0000, 66555.04, 75236.13, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(460, '2026-08-12', 4, 17, 1, 662998.00, 18, 13, 72.0000, 36833.22, 50999.85, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(461, '2026-08-12', 4, 18, 1, 162698.00, 2, 2, 100.0000, 81349.00, 81349.00, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(462, '2026-08-12', 4, 7, 2, 2795960.00, 60, 52, 87.0000, 46599.33, 53768.46, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(463, '2026-08-12', 4, 15, 2, 888671.00, 14, 14, 100.0000, 63476.50, 63476.50, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(464, '2026-08-12', 4, 16, 2, 1203438.00, 17, 16, 94.0000, 70790.47, 75214.88, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(465, '2026-08-12', 4, 16, 4, 893024.00, 32, 25, 78.0000, 27907.00, 35720.96, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(466, '2026-08-12', 4, 19, 5, 361356.00, 10, 9, 90.0000, 36135.60, 40150.67, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(467, '2026-08-13', 4, 7, 3, 2010734.00, 43, 33, 77.0000, 46761.26, 60931.33, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(468, '2026-08-13', 4, 16, 3, 1654627.00, 42, 34, 81.0000, 39395.88, 48665.50, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(469, '2026-08-13', 4, 7, 1, 1725401.00, 30, 25, 83.0000, 57513.37, 69016.04, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(470, '2026-08-13', 4, 15, 1, 3181087.00, 63, 45, 71.0000, 50493.44, 70690.82, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(471, '2026-08-13', 4, 16, 1, 1591105.00, 33, 22, 67.0000, 48215.30, 72322.95, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(472, '2026-08-13', 4, 17, 1, 684875.00, 10, 9, 90.0000, 68487.50, 76097.22, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(473, '2026-08-13', 4, 18, 1, 116720.00, 0, 0, 0.0000, 0.00, 0.00, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(474, '2026-08-13', 4, 7, 2, 2073078.00, 46, 39, 85.0000, 45066.91, 53155.85, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(475, '2026-08-13', 4, 15, 2, 1151380.00, 26, 19, 73.0000, 44283.85, 60598.95, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(476, '2026-08-13', 4, 16, 2, 2283316.00, 58, 38, 66.0000, 39367.52, 60087.26, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(477, '2026-08-13', 4, 16, 4, 854894.00, 20, 16, 80.0000, 42744.70, 53430.88, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL),
(478, '2026-08-13', 4, 19, 5, 360640.00, 6, 5, 83.0000, 60106.67, 72128.00, NULL, '2026-08-14 02:09:12', '2026-08-14 02:09:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `inventory_id` bigint UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `type` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `reference` enum('purchase','shipment','adjustment','order_online') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'adjustment',
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_variant_id`, `inventory_id`, `date`, `type`, `quantity`, `unit_price`, `reference`, `reference_id`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 1, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(2, 2, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 2, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:16', '2026-08-10 22:33:16'),
(3, 3, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 3, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(4, 4, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 4, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(5, 5, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 5, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(6, 6, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 6, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(7, 7, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 7, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(8, 8, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 8, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(9, 9, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 9, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(10, 10, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 10, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(11, 11, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 11, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(12, 12, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 12, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(13, 13, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 13, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(14, 14, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 14, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(15, 15, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 15, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(16, 16, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 16, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(17, 17, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 17, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(18, 18, NULL, '2026-08-11', 'in', 111, 20000.00, 'adjustment', 18, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(19, 19, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 19, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(20, 20, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 20, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(21, 21, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 21, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(22, 22, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 22, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(23, 23, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 23, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(24, 24, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 24, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:17', '2026-08-10 22:33:17'),
(25, 25, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 25, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(26, 26, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 26, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(27, 27, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 27, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(28, 28, NULL, '2026-08-11', 'in', 1000, 5000.00, 'adjustment', 28, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(29, 29, NULL, '2026-08-11', 'in', 500, 3000.00, 'adjustment', 29, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(30, 30, NULL, '2026-08-11', 'in', 500, 8000.00, 'adjustment', 30, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(31, 31, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 31, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(32, 32, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 32, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(33, 33, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 33, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(34, 34, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 34, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(35, 35, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 35, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(36, 36, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 36, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(37, 37, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 37, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(38, 38, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 38, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(39, 39, NULL, '2026-08-11', 'in', 111, 25000.00, 'adjustment', 39, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(40, 40, NULL, '2026-08-11', 'in', 1000, 2000.00, 'adjustment', 40, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(41, 41, NULL, '2026-08-11', 'in', 1000, 1500.00, 'adjustment', 41, 'Stok awal (seeder)', NULL, '2026-08-10 22:33:18', '2026-08-10 22:33:18'),
(42, 19, NULL, '2026-08-11', 'in', 200, 23000.00, 'purchase', 1, 'Pembelian -', 3, '2026-08-11 04:36:16', '2026-08-11 04:36:16'),
(43, 29, NULL, '2026-08-11', 'out', 9, NULL, 'order_online', 10, 'Order online 278213204', 3, '2026-08-11 04:38:18', '2026-08-11 04:38:18'),
(44, 13, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 4, 'Order online 278247279', 3, '2026-08-11 04:38:19', '2026-08-11 04:38:19'),
(45, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 4, 'Order online 278247279', 3, '2026-08-11 04:38:19', '2026-08-11 04:38:19'),
(46, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 4, 'Order online 278247279', 3, '2026-08-11 04:38:19', '2026-08-11 04:38:19'),
(47, 1, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 9, 'Order online 278238428', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(48, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 9, 'Order online 278238428', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(49, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 9, 'Order online 278238428', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(50, 6, NULL, '2026-08-11', 'out', 4, NULL, 'order_online', 8, 'Order online 278239312', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(51, 40, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 8, 'Order online 278239312', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(52, 41, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 8, 'Order online 278239312', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(53, 18, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 7, 'Order online 278243258', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(54, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 7, 'Order online 278243258', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(55, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 7, 'Order online 278243258', 3, '2026-08-11 04:42:28', '2026-08-11 04:42:28'),
(60, 14, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 5, 'Order online 278246350', 3, '2026-08-11 04:42:47', '2026-08-11 04:42:47'),
(61, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 5, 'Order online 278246350', 3, '2026-08-11 04:42:47', '2026-08-11 04:42:47'),
(62, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 5, 'Order online 278246350', 3, '2026-08-11 04:42:47', '2026-08-11 04:42:47'),
(63, 42, NULL, '2026-08-11', 'in', 20, 2900.00, 'adjustment', 42, 'Stok awal varian', 3, '2026-08-11 04:54:19', '2026-08-11 04:54:19'),
(64, 42, NULL, '2026-08-11', 'in', 20, 2900.00, 'purchase', 2, 'Pembelian PUSAT GROSIR KM', 3, '2026-08-11 04:55:18', '2026-08-11 04:55:18'),
(65, 35, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 23, 'Order online 278540242', 3, '2026-08-11 04:58:58', '2026-08-11 04:58:58'),
(66, 23, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 23, 'Order online 278540242', 3, '2026-08-11 04:58:58', '2026-08-11 04:58:58'),
(67, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 23, 'Order online 278540242', 3, '2026-08-11 04:58:58', '2026-08-11 04:58:58'),
(68, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 23, 'Order online 278540242', 3, '2026-08-11 04:58:58', '2026-08-11 04:58:58'),
(69, 14, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 30, 'Order online 278489447', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(70, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 30, 'Order online 278489447', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(71, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 30, 'Order online 278489447', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(72, 35, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 28, 'Order online 278537417', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(73, 23, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 28, 'Order online 278537417', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(74, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 28, 'Order online 278537417', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(75, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 28, 'Order online 278537417', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(76, 1, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 27, 'Order online 278537450', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(77, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 27, 'Order online 278537450', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(78, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 27, 'Order online 278537450', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(79, 32, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 25, 'Order online 278539865', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(80, 20, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 25, 'Order online 278539865', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(81, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 25, 'Order online 278539865', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(82, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 25, 'Order online 278539865', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(83, 16, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 21, 'Order online 278541034', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(84, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 21, 'Order online 278541034', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(85, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 21, 'Order online 278541034', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(86, 1, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 18, 'Order online 278542100', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(87, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 18, 'Order online 278542100', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(88, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 18, 'Order online 278542100', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(89, 1, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 15, 'Order online 278544111', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(90, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 15, 'Order online 278544111', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(91, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 15, 'Order online 278544111', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(92, 5, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 12, 'Order online 278546563', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(93, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 12, 'Order online 278546563', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(94, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 12, 'Order online 278546563', 3, '2026-08-11 04:59:34', '2026-08-11 04:59:34'),
(95, 36, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 26, 'Order online 278539400', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(96, 24, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 26, 'Order online 278539400', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(97, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 26, 'Order online 278539400', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(98, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 26, 'Order online 278539400', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(99, 10, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 24, 'Order online 278540231', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(100, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 24, 'Order online 278540231', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(101, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 24, 'Order online 278540231', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(102, 17, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 22, 'Order online 278541008', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(103, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 22, 'Order online 278541008', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(104, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 22, 'Order online 278541008', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(105, 11, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 19, 'Order online 278542031', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(106, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 19, 'Order online 278542031', 3, '2026-08-11 05:06:03', '2026-08-11 05:06:03'),
(107, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 19, 'Order online 278542031', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(108, 31, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 17, 'Order online 278542356', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(109, 19, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 17, 'Order online 278542356', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(110, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 17, 'Order online 278542356', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(111, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 17, 'Order online 278542356', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(112, 8, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 16, 'Order online 278543828', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(113, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 16, 'Order online 278543828', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(114, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 16, 'Order online 278543828', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(115, 5, NULL, '2026-08-11', 'out', 2, NULL, 'order_online', 13, 'Order online 278545355', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(116, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 13, 'Order online 278545355', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(117, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 13, 'Order online 278545355', 3, '2026-08-11 05:06:04', '2026-08-11 05:06:04'),
(118, 35, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 31, 'Order online 278485864', 3, '2026-08-11 05:07:44', '2026-08-11 05:07:44'),
(119, 23, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 31, 'Order online 278485864', 3, '2026-08-11 05:07:44', '2026-08-11 05:07:44'),
(120, 40, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 31, 'Order online 278485864', 3, '2026-08-11 05:07:44', '2026-08-11 05:07:44'),
(121, 41, NULL, '2026-08-11', 'out', 1, NULL, 'order_online', 31, 'Order online 278485864', 3, '2026-08-11 05:07:44', '2026-08-11 05:07:44');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_telepon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `kota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `kode_supplier`, `nama_supplier`, `pic_nama`, `pic_telepon`, `email`, `alamat`, `kota`, `provinsi`, `status`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SUP-001', 'PT Maju Bersama Sejahtera', 'Budi Santoso', '0812-3456-7890', 'budi@majubersama.co.id', 'Jl. Industri Raya No. 12', 'Jakarta Utara', 'DKI Jakarta', 'aktif', 'Supplier utama skincare & beauty', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(2, 'SUP-002', 'CV Herbal Nusantara', 'Sari Dewi', '0856-7890-1234', 'sari@herbalnusantara.id', 'Jl. Raya Bogor KM 35', 'Bogor', 'Jawa Barat', 'aktif', 'Spesialis produk herbal & suplemen', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(3, 'SUP-003', 'PT Digital Kreatif Indo', 'Andi Wijaya', '0878-2345-6789', 'andi@digitalkreatif.com', 'Jl. Sudirman Blok A No. 5', 'Surabaya', 'Jawa Timur', 'aktif', 'Supplier produk digital & aksesoris', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(4, 'SUP-004', 'UD Sumber Makmur', 'Hendra Kusuma', '0895-6789-0123', 'hendra@sumbermakmur.net', 'Jl. Pahlawan No. 88', 'Bandung', 'Jawa Barat', 'aktif', 'Produk fashion & lifestyle', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(5, 'SUP-005', 'PT Teknologi Mandiri', 'Rini Pratiwi', '0821-9876-5432', 'rini@tekmandir.co.id', 'Kawasan SCBD Lot 18', 'Jakarta Selatan', 'DKI Jakarta', 'nonaktif', 'Sementara nonaktif, sedang negosiasi ulang kontrak', '2026-08-10 22:33:16', '2026-08-10 22:33:16', NULL),
(6, 'SUP-023', 'PT maju mundur', 'denden', '0812333', 'suploer@gmail', 'jl. jalan', 'jakut', 'jakarta', 'aktif', 'hhh', '2026-08-11 04:33:03', '2026-08-11 04:33:23', '2026-08-11 04:33:23'),
(7, 'SUP-01', 'PUSAT GROSIR KM', 'UJANG B', '089664216838', 'yyunadiy@gmail.com', 'jln. caracas rt 08/03 dusun pahing', 'Kabupaten Kuningan', 'Jawa Barat', 'aktif', NULL, '2026-08-11 04:50:20', '2026-08-11 04:50:20', NULL),
(8, 'SUP 21', 'PT Kapitalisme', 'Fulan bin Fulan', '08123456789', 'penjual@kapitalisme.com', 'Jl. Fatamorgana', 'Jakarta', 'Jakarta', 'aktif', NULL, '2026-08-11 04:52:03', '2026-08-11 04:52:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `top_up_proposals`
--

CREATE TABLE `top_up_proposals` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `status` enum('pending','approved','declined','menunggu_pembayaran','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `previous_topup_total` decimal(15,2) DEFAULT NULL,
  `today_lead` int DEFAULT NULL,
  `today_paid` int DEFAULT NULL,
  `today_spending` decimal(15,2) DEFAULT NULL,
  `total_nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `approver_id` bigint UNSIGNED DEFAULT NULL,
  `decline_note` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `va_paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `va_paid_by` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `top_up_proposal_items`
--

CREATE TABLE `top_up_proposal_items` (
  `id` bigint UNSIGNED NOT NULL,
  `proposal_id` bigint UNSIGNED NOT NULL,
  `whitelist_id` bigint UNSIGNED NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `va_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `sisa_saldo_dilaporkan` decimal(15,2) DEFAULT NULL COMMENT 'Sisa saldo whitelist yg dilaporkan advertiser saat konfirmasi VA dibayar',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `panggilan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nohp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `norek` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `advertiser_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_profile_complete` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `email_verified_at`, `nama`, `panggilan`, `role`, `nohp`, `alamat`, `bank`, `norek`, `avatar`, `advertiser_id`, `is_active`, `is_profile_complete`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'owner@awanna.id', '$2y$12$Ttiy6oYqPZdRGG2RXfGEW.2bRAexYb4dlCsiuH0VmyDmDsKD4/Omq', NULL, 'Pemilik Awanna', 'Owner', 'Owner', '0811-0000-0000', 'Kantor Awanna, Jakarta', 'BCA', '1234567890', NULL, NULL, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(2, 'superadmin@awanna.id', '$2y$12$d7aX7vPk0eYSd5Xmyr2SBOdWweDxhpdlJP8l9LE.qDrcHTkQJqttm', NULL, 'Super Administrator', 'SuperAdmin', 'Admin', '0811-0000-0001', 'Jl. Contoh No. 1, Jakarta', 'BRI', '0987654321', NULL, NULL, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(3, 'admin@awanna.id', '$2y$12$a/4GU.i6AF34lyLhDjjeR.V.FWgk3Jsc/k6iqwLUt5Y1abXtjKWui', NULL, 'Ahmad Fauzi', 'Fauzi', 'Admin', '0811-0000-0002', 'Jl. Sudirman No. 5, Jakarta', 'Mandiri', '1122334455', NULL, NULL, 1, 1, 'IJdHEWWFr8MwHiZI92vv8XKmBPvabaHV5xPiIzfcECd8CoEEHyXD9HR0jgrI', '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(4, 'rendi@awanna.id', '$2y$12$ijbd/FjcSiN3LOvMo6vOde4uzrD2ESo7OcJTAINlgC3j.HkrUOtgS', NULL, 'rendi', 'rendi', 'Advertiser', '0812-1111-0001', 'Jl. Gatot Subroto No. 12, Jakarta', 'BCA', '5566778899', NULL, NULL, 1, 1, 'zMQ5I6yKlZuH0Tbk9M7WQlHkfBT2ou7M2o9vLLUpXDFDmaSYivM2LhJ24Alt', '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(5, 'yanca@awanna.id', '$2y$12$3k5JjV/jrQfnUfEyegpZhe.1fPqPMTSmFr71iB80SRhfY7oq5My0.', NULL, 'yanca', 'yanca', 'Advertiser', '0812-1111-0002', 'Jl. Thamrin No. 8, Jakarta', 'DANA', '08121111002', NULL, NULL, 1, 1, 'u5eSEcGfxUfZxrFbEa4HRaQzJxleH7WR8JFaRg6F6ST3ct0Z36LO5rtMtTnO', '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(6, 'parhan@awanna.id', '$2y$12$qHsrSWGnyU6XDf/HAmOXMe0LsVXCUFQR/oUEPLenJ3opemC7kTyh.', NULL, 'parhan', 'parhan', 'Advertiser', '0812-1111-0003', 'Jl. Kuningan No. 3, Jakarta', 'BNI', '9988776655', NULL, NULL, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(7, 'rama@awanna.id', '$2y$12$hVpYHtCHNG2xTrCAQyr7HelSimeZkMxHzCzRbHcl5.22rZ3xX5VPi', NULL, 'rama', 'rama', 'Advertiser', '0812-1111-0003', 'Jl. Kuningan No. 3, Jakarta', 'BNI', '9988776655', NULL, NULL, 1, 1, 'Md9nOLrxyyr9A3HczAkZpkhsBWLgnhBsyTqMdUz89YlWHcM8VZlCJznsGjf7', '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(8, 'mentor@awanna.id', '$2y$12$sio02sJ0Kmx8ggcraX6.oebcwyDuguNaKZJ6.UBRj2TU.EiRzYV9G', NULL, 'Bowo Susanto', 'Pak Bowo', 'Mentor', '0813-2222-0001', 'Jl. Senayan No. 20, Jakarta', 'BSI', '7766554433', NULL, NULL, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(9, 'keuangan@awanna.id', '$2y$12$plmVOlxCZOslzcJXgmnXRuc1RB5jOcHmEvmbI/wPljS1or78BKyQq', NULL, 'Siska Rahayu', 'Siska', 'Keuangan', '0814-3333-0001', 'Jl. Casablanca No. 15, Jakarta', 'BCA', '3344556677', NULL, NULL, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-10 22:33:23'),
(10, 'toha@awanna.id', '$2y$12$VLCYn0qX4isSUxU6UmxnXOwJ61PC89wNVZLUpUtCOBHvYvU9N5lB.', NULL, 'Opus CS', 'Opus CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 6, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-11 02:13:30'),
(11, 'asepace@awanna.id', '$2y$12$PgExk9WBBMY7IFKDjyT99uo3Jj8S2rmRUKHbb.6tLl0ZCqQHQfV0W', NULL, 'asep pace CS', 'asep pace CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 5, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-11 02:13:30'),
(12, 'feri@awanna.id', '$2y$12$mO8pHsnSRk/p1dcoLBRGle6rYVdqUJ18JvNwvi4X.8HWt4K86oZMu', NULL, 'feri CS', 'feri CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 4, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-11 02:13:30'),
(13, 'mayang@awanna.id', '$2y$12$aTSa0kJ5DxR1/42u7ONukusXKEhwzdbyfPzfovD.cDIkNAD8Li59C', NULL, 'mayang CS', 'mayang CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 4, 1, 1, NULL, '2026-08-10 22:33:23', '2026-08-11 02:13:30'),
(14, 'putri@awanna.id', '$2y$12$zvXqEdISX/mhMH80sH9GIe8iZ6hTWN6T2M8UKHRCb0lJ7iM4ObusW', NULL, 'putri CS', 'putri CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 5, 1, 1, NULL, '2026-08-10 22:33:24', '2026-08-11 02:13:30'),
(15, 'muklas@awanna.id', '$2y$12$n4/26oKVrAJ.BPTHShnw5.GJEmkbw4c01ZSJfCSwR4FgiPHlocXVK', NULL, 'muklas CS', 'muklas CS', 'CS', '0815-4444-0001', 'Jl. Mangga Dua No. 7, Jakarta', 'OVO', '08154444001', NULL, 7, 1, 1, NULL, '2026-08-10 22:33:24', '2026-08-11 02:13:30'),
(16, 'newuser@awanna.id', '$2y$12$ifJ8YL7VHtxwLs6d1uv.ueq8qXT0.UEIY3byBYDbhAG54vvJOC6Lu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2026-08-10 22:33:24', '2026-08-10 22:33:24'),
(17, 'tohaadv@awanna.id', '$2y$12$nideYJ50MwEnKt16mkwsyuFdkbJDWHo7ebs9E2XI1bx7mLwy01VUO', NULL, 'OPUSStore', 'OPUS', 'Advertiser', '083168055553', 'Jl.Bojong 2 cilimus kuningan', 'DANA', '083168055553', NULL, NULL, 1, 1, 'yIM9qCpbcS9HYL3JRwD2JkzeFd8K29tJp5kSa1xBMc4NPM7hTXx6GQnqaimI', '2026-08-11 03:54:21', '2026-08-11 04:01:05');

-- --------------------------------------------------------

--
-- Table structure for table `whitelists`
--

CREATE TABLE `whitelists` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL DEFAULT (curdate()),
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `total_topup` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_spending` decimal(15,2) NOT NULL DEFAULT '0.00',
  `nominal_terakhir_topup` decimal(15,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whitelists`
--

INSERT INTO `whitelists` (`id`, `nama`, `kode`, `platform`, `user_id`, `tanggal`, `status`, `total_topup`, `total_spending`, `nominal_terakhir_topup`, `catatan`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'WL60 - prhn', '22760', 'facebook', 6, '2026-08-01', 'aktif', 0.00, 10476163.00, 0.00, NULL, '2026-08-10 23:36:57', '2026-08-13 01:07:25', NULL),
(2, 'WL59 - prhn', '22759', 'facebook', 6, '2026-08-01', 'aktif', 0.00, 2180138.00, 0.00, NULL, '2026-08-10 23:37:14', '2026-08-11 01:00:30', NULL),
(3, 'WL58 - prhn', '22758', 'facebook', 6, '2026-08-01', 'aktif', 0.00, 8326687.00, 0.00, NULL, '2026-08-10 23:37:32', '2026-08-13 01:07:25', NULL),
(4, 'WL42 - prhn', '23642', 'facebook', 6, '2026-08-01', 'nonaktif', 0.00, 0.00, 0.00, NULL, '2026-08-10 23:37:51', '2026-08-10 23:37:51', NULL),
(5, 'WL43 - prhn', '23643', 'facebook', 6, '2026-08-01', 'aktif', 0.00, 4684132.00, 0.00, NULL, '2026-08-10 23:38:10', '2026-08-13 01:07:25', NULL),
(6, 'WL44 - prhn', '23644', 'facebook', 6, '2026-08-01', 'nonaktif', 0.00, 0.00, 0.00, NULL, '2026-08-10 23:38:26', '2026-08-10 23:38:26', NULL),
(7, 'OO - 13723 - rsg', '13723', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 73620377.00, 0.00, NULL, '2026-08-11 02:07:50', '2026-08-14 02:09:12', NULL),
(8, 'OO - 23318  YB', 'WL - FB 23318', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 29472159.00, 0.00, NULL, '2026-08-11 03:56:39', '2026-08-13 14:57:35', NULL),
(9, 'OO - 23319 YB', 'WL - FB 23319', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 20610821.00, 0.00, NULL, '2026-08-11 03:57:09', '2026-08-13 14:58:12', NULL),
(10, 'OO - 23316 - R7', '23316', 'facebook', 7, '2026-08-11', 'aktif', 0.00, 13293430.00, 0.00, NULL, '2026-08-11 03:57:11', '2026-08-11 13:18:48', NULL),
(11, 'OO - 23320 YB', 'WL - FB 23320', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 32041293.00, 0.00, NULL, '2026-08-11 03:58:16', '2026-08-13 14:57:35', NULL),
(12, 'OO - 23444 YB', 'WL - FB 23444', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 11590849.00, 0.00, NULL, '2026-08-11 03:59:07', '2026-08-13 14:57:35', NULL),
(13, 'OO - 23445 YB', 'WL - FB 23445', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 8083054.00, 0.00, NULL, '2026-08-11 03:59:39', '2026-08-13 14:58:47', NULL),
(14, 'OO - 23446 YB', 'WL - FB 23446', 'facebook', 5, '2026-08-01', 'aktif', 0.00, 22235609.00, 0.00, NULL, '2026-08-11 04:00:19', '2026-08-13 14:57:35', NULL),
(15, 'OO - 13722 - rsg', '13722', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 56877275.00, 0.00, NULL, '2026-08-11 04:04:28', '2026-08-14 02:09:12', NULL),
(16, 'OO - 13724 - rsg', '13724', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 68356430.00, 0.00, NULL, '2026-08-11 04:04:51', '2026-08-14 02:09:12', NULL),
(17, 'OO - 22767 - rsg', '22767', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 8345589.00, 0.00, NULL, '2026-08-11 04:05:41', '2026-08-14 02:09:12', NULL),
(18, 'OO - 22768 - rsg', '22768', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 4489759.00, 0.00, NULL, '2026-08-11 04:06:03', '2026-08-14 02:09:12', NULL),
(19, 'OO - 22769 - rsg', '22769', 'facebook', 4, '2026-08-11', 'aktif', 0.00, 6926014.00, 0.00, NULL, '2026-08-11 04:06:24', '2026-08-14 02:09:12', NULL),
(20, 'WLEV5 - prhn', 'HKM5', 'facebook', 6, '2026-08-13', 'aktif', 0.00, 189095.00, 0.00, NULL, '2026-08-13 01:00:23', '2026-08-13 01:07:25', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aggregator_sync_batches`
--
ALTER TABLE `aggregator_sync_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aggregator_sync_batches_status_index` (`status`),
  ADD KEY `aggregator_sync_batches_created_at_index` (`created_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `courier_rules`
--
ALTER TABLE `courier_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `courier_rules_sort_order_index` (`sort_order`),
  ADD KEY `courier_rules_payment_method_province_index` (`payment_method`,`province`);

--
-- Indexes for table `cs_assignments`
--
ALTER TABLE `cs_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cs_assignments_cs_bulan` (`cs_user_id`,`bulan`),
  ADD KEY `cs_assignments_created_by_foreign` (`created_by`),
  ADD KEY `idx_cs_assignments_adv_bulan` (`advertiser_id`,`bulan`),
  ADD KEY `cs_assignments_bulan_index` (`bulan`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`),
  ADD KEY `notifications_from_user_id_foreign` (`from_user_id`);

--
-- Indexes for table `order_online_contacts`
--
ALTER TABLE `order_online_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_online_contacts_advertiser_id_index` (`advertiser_id`),
  ADD KEY `order_online_contacts_phone_normalized_index` (`phone_normalized`);

--
-- Indexes for table `order_online_import_batches`
--
ALTER TABLE `order_online_import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_online_import_batches_status_index` (`status`),
  ADD KEY `order_online_import_batches_created_at_index` (`created_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_code_unique` (`code`),
  ADD KEY `products_inventory_id_foreign` (`inventory_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variants_code_unique` (`code`),
  ADD KEY `product_variants_product_id_index` (`product_id`),
  ADD KEY `product_variants_product_id_power_index` (`product_id`,`power`);

--
-- Indexes for table `product_variant_items`
--
ALTER TABLE `product_variant_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variant_items_product_variant_id_komponen_id_unique` (`product_variant_id`,`komponen_id`),
  ADD KEY `product_variant_items_product_variant_id_index` (`product_variant_id`),
  ADD KEY `product_variant_items_komponen_id_index` (`komponen_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchases_date_index` (`date`),
  ADD KEY `purchases_product_variant_id_index` (`product_variant_id`),
  ADD KEY `purchases_supplier_id_index` (`supplier_id`);

--
-- Indexes for table `regional_cs_stats`
--
ALTER TABLE `regional_cs_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cs_stats_unique` (`tanggal`,`user_id`,`cs_panggilan`),
  ADD KEY `regional_cs_stats_user_id_foreign` (`user_id`),
  ADD KEY `regional_cs_stats_cs_user_id_foreign` (`cs_user_id`);

--
-- Indexes for table `regional_reports`
--
ALTER TABLE `regional_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `regional_unique` (`tanggal`,`user_id`,`province`),
  ADD KEY `regional_reports_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipments_source_tracking_number_unique` (`source`,`tracking_number`),
  ADD KEY `shipments_source_index` (`source`),
  ADD KEY `shipments_tracking_number_index` (`tracking_number`),
  ADD KEY `shipments_status_index` (`status`),
  ADD KEY `shipments_created_date_index` (`created_date`),
  ADD KEY `shipments_product_id_index` (`product_id`);

--
-- Indexes for table `shipment_status_histories`
--
ALTER TABLE `shipment_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipment_status_histories_shipment_id_index` (`shipment_id`),
  ADD KEY `shipment_status_histories_user_id_index` (`user_id`);

--
-- Indexes for table `shipping_orders`
--
ALTER TABLE `shipping_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shipping_orders_batch_order_unique` (`order_online_import_batch_id`,`order_id`),
  ADD KEY `shipping_orders_handled_by_user_id_foreign` (`handled_by_user_id`),
  ADD KEY `shipping_orders_product_id_foreign` (`product_id`),
  ADD KEY `shipping_orders_awb_index` (`awb`),
  ADD KEY `shipping_orders_phone_index` (`phone`),
  ADD KEY `shipping_orders_phone_normalized_index` (`phone_normalized`),
  ADD KEY `shipping_orders_status_index` (`status`),
  ADD KEY `shipping_orders_payment_method_index` (`payment_method`),
  ADD KEY `shipping_orders_province_index` (`province`),
  ADD KEY `shipping_orders_handled_by_index` (`handled_by`),
  ADD KEY `shipping_orders_product_variant_id_index` (`product_variant_id`),
  ADD KEY `shipping_orders_order_online_import_batch_id_index` (`order_online_import_batch_id`),
  ADD KEY `shipping_orders_last_synced_at_index` (`last_synced_at`),
  ADD KEY `shipping_orders_status_payment_method_index` (`status`,`payment_method`),
  ADD KEY `shipping_orders_payment_method_province_index` (`payment_method`,`province`),
  ADD KEY `shipping_orders_awb_phone_index` (`awb`,`phone`),
  ADD KEY `shipping_orders_courier_index` (`courier`),
  ADD KEY `shipping_orders_product_code_index` (`product_code`);

--
-- Indexes for table `spending_harians`
--
ALTER TABLE `spending_harians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spending_harians_user_id_foreign` (`user_id`),
  ADD KEY `spending_harians_whitelist_id_foreign` (`whitelist_id`),
  ADD KEY `spending_harians_product_id_foreign` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_movements_ref_unique` (`reference`,`reference_id`,`type`,`product_variant_id`),
  ADD KEY `stock_movements_inventory_id_foreign` (`inventory_id`),
  ADD KEY `stock_movements_product_variant_id_index` (`product_variant_id`),
  ADD KEY `stock_movements_date_index` (`date`),
  ADD KEY `stock_movements_type_index` (`type`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suppliers_kode_supplier_unique` (`kode_supplier`);

--
-- Indexes for table `top_up_proposals`
--
ALTER TABLE `top_up_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `top_up_proposals_user_id_foreign` (`user_id`),
  ADD KEY `top_up_proposals_approver_id_foreign` (`approver_id`),
  ADD KEY `top_up_proposals_va_paid_by_foreign` (`va_paid_by`);

--
-- Indexes for table `top_up_proposal_items`
--
ALTER TABLE `top_up_proposal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `top_up_proposal_items_proposal_id_foreign` (`proposal_id`),
  ADD KEY `top_up_proposal_items_whitelist_id_foreign` (`whitelist_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_advertiser_id_foreign` (`advertiser_id`);

--
-- Indexes for table `whitelists`
--
ALTER TABLE `whitelists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `whitelists_kode_unique` (`kode`),
  ADD KEY `whitelists_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aggregator_sync_batches`
--
ALTER TABLE `aggregator_sync_batches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courier_rules`
--
ALTER TABLE `courier_rules`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `cs_assignments`
--
ALTER TABLE `cs_assignments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_online_contacts`
--
ALTER TABLE `order_online_contacts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9536;

--
-- AUTO_INCREMENT for table `order_online_import_batches`
--
ALTER TABLE `order_online_import_batches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `product_variant_items`
--
ALTER TABLE `product_variant_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `regional_cs_stats`
--
ALTER TABLE `regional_cs_stats`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `regional_reports`
--
ALTER TABLE `regional_reports`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=987;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_status_histories`
--
ALTER TABLE `shipment_status_histories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_orders`
--
ALTER TABLE `shipping_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `spending_harians`
--
ALTER TABLE `spending_harians`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=479;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `top_up_proposals`
--
ALTER TABLE `top_up_proposals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `top_up_proposal_items`
--
ALTER TABLE `top_up_proposal_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `whitelists`
--
ALTER TABLE `whitelists`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cs_assignments`
--
ALTER TABLE `cs_assignments`
  ADD CONSTRAINT `cs_assignments_advertiser_id_foreign` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cs_assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cs_assignments_cs_user_id_foreign` FOREIGN KEY (`cs_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_online_contacts`
--
ALTER TABLE `order_online_contacts`
  ADD CONSTRAINT `order_online_contacts_advertiser_id_foreign` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant_items`
--
ALTER TABLE `product_variant_items`
  ADD CONSTRAINT `product_variant_items_komponen_id_foreign` FOREIGN KEY (`komponen_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_variant_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `regional_cs_stats`
--
ALTER TABLE `regional_cs_stats`
  ADD CONSTRAINT `regional_cs_stats_cs_user_id_foreign` FOREIGN KEY (`cs_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `regional_cs_stats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `regional_reports`
--
ALTER TABLE `regional_reports`
  ADD CONSTRAINT `regional_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shipment_status_histories`
--
ALTER TABLE `shipment_status_histories`
  ADD CONSTRAINT `shipment_status_histories_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_orders`
--
ALTER TABLE `shipping_orders`
  ADD CONSTRAINT `shipping_orders_handled_by_user_id_foreign` FOREIGN KEY (`handled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipping_orders_order_online_import_batch_id_foreign` FOREIGN KEY (`order_online_import_batch_id`) REFERENCES `order_online_import_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipping_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shipping_orders_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `spending_harians`
--
ALTER TABLE `spending_harians`
  ADD CONSTRAINT `spending_harians_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spending_harians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spending_harians_whitelist_id_foreign` FOREIGN KEY (`whitelist_id`) REFERENCES `whitelists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `top_up_proposals`
--
ALTER TABLE `top_up_proposals`
  ADD CONSTRAINT `top_up_proposals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `top_up_proposals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `top_up_proposals_va_paid_by_foreign` FOREIGN KEY (`va_paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `top_up_proposal_items`
--
ALTER TABLE `top_up_proposal_items`
  ADD CONSTRAINT `top_up_proposal_items_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `top_up_proposals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `top_up_proposal_items_whitelist_id_foreign` FOREIGN KEY (`whitelist_id`) REFERENCES `whitelists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_advertiser_id_foreign` FOREIGN KEY (`advertiser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `whitelists`
--
ALTER TABLE `whitelists`
  ADD CONSTRAINT `whitelists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
