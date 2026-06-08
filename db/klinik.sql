-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 08, 2026 at 02:30 AM
-- Server version: 12.2.2-MariaDB-log
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `klinik`
--

-- --------------------------------------------------------

-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `full_name`, `email`, `password_hash`, `created_at`, `is_active`, `reset_token`, `reset_expires`) VALUES
(1, 'admin1', 'Admin Utama', 'admin@klinik.com', '$2b$12$NWJTwXXyvvnZouL48HRe1OZVIApov7s0Lx1FnTcct8HoM8n.Wkkjm', '2026-06-05 09:32:08', 1, NULL, NULL),
(2, 'admin2', 'Admin Baru', 'admin2@klinik.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-06-06 23:40:52', 1, NULL, NULL),
(3, 'admin3', 'Admin Cadangan', 'admin3@klinik.com', '$2b$10$/N.IfHEpHpjkB8dzgSNXxOStBTIoJxnvB1aJRZku4cb40XQB4VJXK', '2026-06-07 08:00:00', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `slot_id` int(10) UNSIGNED NOT NULL,
  `booking_time` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('booked','cancelled','completed') NOT NULL DEFAULT 'booked',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `slot_id`, `booking_time`, `status`, `notes`) VALUES
(1, 1, 2, '2026-06-05 09:32:08', 'completed', 'Ingin konsultasi demam'),
(2, 1, 4, '2026-06-07 18:45:11', 'completed', ''),
(3, 13, 5, '2026-06-07 18:48:14', 'completed', ''),
(4, 14, 6, '2026-06-07 18:55:19', 'booked', 'Gigi Berlubang'),
(5, 6, 7, '2026-06-08 08:23:45', 'cancelled', 'saya sehat sebenernya tapi pengen bookinh'),
(9, 6, 7, '2026-06-08 08:32:19', 'cancelled', 'Ngga sakit cuman pengen pesen aja, hehe'),
(10, 6, 7, '2026-06-08 08:32:55', 'booked', 'Ngga sakit cuman pengen pesen aja, hehe'),
(11, 6, 8, '2026-06-08 08:34:07', 'cancelled', ''),
(12, 3, 8, '2026-06-08 08:49:02', 'booked', '');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `specialization`, `license_number`, `is_active`, `deleted_at`) VALUES
(1, 'dr. Andi Pratama', 'Dokter Umum', 'LIC-2026-001', 1, NULL),
(2, 'dr. Eagan Rainmahasin', 'Dokter Mata', 'LIC-2026-002', 1, NULL),
(3, 'dr. Ratna Sari', 'Dokter Anak', 'LIC-2026-003', 1, NULL),
(4, 'dr. Budi Hartono', 'Dokter Gigi', 'LIC-2026-004', 1, NULL),
(5, 'dr. Suwandi Prasetyo', 'Dokter Penyakit Dalam', 'LIC-2026-005', 1, NULL),
(6, 'dr. Fajar Nugroho', 'Dokter Jantung', 'LIC-2026-006', 1, NULL),
(7, 'dr. Maya Putri', 'Dokter Umum', 'LIC-2026-007', 1, NULL),
(8, 'dr. Rizky Saputra', 'Dokter Mata', 'LIC-2026-008', 1, NULL),
(9, 'dr. Dinda Lestari', 'Dokter Anak', 'LIC-2026-009', 1, NULL),
(10, 'dr. Ahmad Firmansyah', 'Dokter Gigi', 'LIC-2026-010', 1, NULL),
(11, 'dr. Siti Rahmawati', 'Dokter Penyakit Dalam', 'LIC-2026-011', 1, NULL),
(12, 'dr. Reza Kurniawan', 'Dokter Saraf', 'LIC-2026-012', 1, NULL),
(13, 'dr. Nabila Ayu', 'Dokter THT', 'LIC-2026-013', 1, NULL),
(14, 'dr. Yoga Pratama', 'Dokter Kulit dan Kelamin', 'LIC-2026-014', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `updated_at`, `reset_token`, `reset_expires`) VALUES
(1, 'Budi Santoso', 'budi@email.com', '08123456789', '$2b$12$Zc5Eivw/hwtU/53wp2iOyeS.8NFskmuo13S.FIwP5IZrg1k1J9hLm', '2026-06-05 09:32:08', '2026-06-07 21:43:14', 'c13bfcf95cc2b0e168426ec4bc74bd5bcbb519818e9b7e06d7dc2e40ce94b361', '2026-06-07 15:43:14'),
(2, 'Hendra Lesmana', 'hendraalim@gmail.com', '082143454637', '$2y$10$BFNdxjfWyP9aqDbFkoyZ9ubsxcNMtaVdQjerT5.QGiNV8tbSSTsSC', '2026-06-07 00:13:05', '2026-06-07 00:13:05', NULL, NULL),
(3, 'Bambang', 'bambangalim@gmail.com', '083839717338', '$2y$10$1yb3MS7zbeqEIXTS8lcATuFCNml2IqZdORMfS4/5BfKihMZ8AwnFa', '2026-06-07 00:15:16', '2026-06-07 00:15:16', NULL, NULL),
(4, 'Pisang', 'pisangkuning@gmail.com', '0823323221', '$2y$10$hLV2UKOYg0LYRqLbdA04leZgArqEJSQYExEAD1NalzcHwjwKoUjA2', '2026-06-07 00:53:25', '2026-06-07 00:53:25', NULL, NULL),
(5, 'Siti Rahayu', 'siti.rahayu@gmail.com', '081234000001', '$2b$10$YCygDoZ8Cv8CMq6RE/uVaOi6Aldf4mTumBwyQvIm3zSkrMdcP0qQu', '2026-06-07 08:01:00', '2026-06-07 08:01:00', NULL, NULL),
(6, 'Dewi Lestari', 'dewi.lestari@gmail.com', '081234000002', '$2b$10$m.ku6J92HcM6JMHO7GLLSuxDcCLm6tTkP6LwqR5GiqGw2ofLyjT5m', '2026-06-07 08:02:00', '2026-06-07 08:02:00', NULL, NULL),
(7, 'Rina Oktavia', 'rina.oktavia@gmail.com', '081234000003', '$2b$10$/cXcYCGSiwtZkDQcPShEz.LzHwCT7tTSTL8wOZDgZlBs9t2To473W', '2026-06-07 08:03:00', '2026-06-07 08:03:00', NULL, NULL),
(8, 'Agus Salim', 'agus.salim@gmail.com', '081234000004', '$2b$10$SNsO2Nma6SqTJK7aFUGPdO94odhgmdJvnTr2c9o2pbSW9JRmJLXIu', '2026-06-07 08:04:00', '2026-06-07 08:04:00', NULL, NULL),
(9, 'Joko Widodo', 'joko.widodo99@gmail.com', '081234000005', '$2b$10$XsGLLjB65zas4BxBz/DAJOiz6.LgLasgFIny2sNEwn.3mkxrR1Xtm', '2026-06-07 08:05:00', '2026-06-07 08:05:00', NULL, NULL),
(10, 'Sri Wahyuningsih', 'sri.wahyu@gmail.com', '081234000006', '$2b$10$PcCZEfOHNr.Fk49jSV4F5.qKAicFNkHfapj14GxeX.JnHdTDmRBFO', '2026-06-07 08:06:00', '2026-06-07 08:06:00', NULL, NULL),
(11, 'Yusuf Hidayat', 'yusuf.hidayat@gmail.com', '081234000007', '$2b$10$GfUu2uDYEVILbEs/8Cq6E./.2EkIIWHJc5oUA3LNJTkTTX8S7lPfG', '2026-06-07 08:07:00', '2026-06-07 08:07:00', NULL, NULL),
(12, 'Indah Permata', 'indah.permata@gmail.com', '081234000008', '$2b$10$6Pd67vU9ShZMzZnnHGeHEeySQ9gjfedollK6vlqTL3I6z3RA.pYv.', '2026-06-07 08:08:00', '2026-06-07 08:08:00', NULL, NULL),
(13, 'Santoso Budi', 'santoso.budi@gmail.com', '081234000009', '$2b$10$f2mSpOjeWb6ID/gsZo6lxeJ4wKjzNehw54AT6dKHSKzvdV8PxQR/K', '2026-06-07 08:09:00', '2026-06-07 08:09:00', NULL, NULL),
(14, 'Melinda Cahyani', 'melinda.cahyani@gmail.com', '081234000010', '$2b$10$z0El2dof2uH7YJxQglSdoeZuupUnsFU69ULB4GtLlwvUeuN.EbLbu', '2026-06-07 08:10:00', '2026-06-07 08:10:00', NULL, NULL),
(15, 'Sakayanagi Jambu', 'sakayanagisukajambu@gmail.com', '0897687558488', '$2y$10$b8C7plLaTQsWkYlOKglW/ePGhgh6ZFYaYYibuVdOhWeugyeiOGU5.', '2026-06-08 08:48:39', '2026-06-08 08:48:39', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(10) UNSIGNED NOT NULL,
  `doctor_id` int(10) UNSIGNED NOT NULL,
  `slot_datetime` datetime NOT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `doctor_id`, `slot_datetime`, `duration_minutes`, `is_booked`, `created_at`) VALUES
(1, 1, '2026-06-06 09:00:00', 30, 0, '2026-06-05 09:32:08'),
(2, 1, '2026-06-06 09:30:00', 30, 1, '2026-06-05 09:32:08'),
(3, 1, '2026-06-08 09:12:00', 30, 0, '2026-06-07 18:18:43'),
(4, 4, '2026-06-09 08:00:00', 15, 1, '2026-06-07 18:35:26'),
(5, 4, '2026-06-09 08:15:00', 15, 1, '2026-06-07 18:35:26'),
(6, 4, '2026-06-09 08:30:00', 15, 1, '2026-06-07 18:35:26'),
(7, 4, '2026-06-09 08:45:00', 15, 1, '2026-06-07 18:35:26'),
(8, 4, '2026-06-09 09:00:00', 15, 1, '2026-06-07 18:35:26'),
(9, 4, '2026-06-09 09:15:00', 15, 0, '2026-06-07 18:35:26'),
(10, 4, '2026-06-09 09:30:00', 15, 0, '2026-06-07 18:35:26'),
(11, 4, '2026-06-09 09:45:00', 15, 0, '2026-06-07 18:35:26'),
(12, 4, '2026-06-09 10:00:00', 15, 0, '2026-06-07 18:35:26'),
(13, 4, '2026-06-09 10:15:00', 15, 0, '2026-06-07 18:35:26'),
(14, 4, '2026-06-09 10:30:00', 15, 0, '2026-06-07 18:35:26'),
(15, 4, '2026-06-09 10:45:00', 15, 0, '2026-06-07 18:35:26'),
(16, 4, '2026-06-09 11:00:00', 15, 0, '2026-06-07 18:35:26'),
(17, 4, '2026-06-09 11:15:00', 15, 0, '2026-06-07 18:35:26'),
(18, 4, '2026-06-09 11:30:00', 15, 0, '2026-06-07 18:35:26'),
(19, 4, '2026-06-09 11:45:00', 15, 0, '2026-06-07 18:35:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admin_username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appt_patient` (`patient_id`),
  ADD KEY `idx_appt_slot_status` (`slot_id`,`status`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_patient_email` (`email`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slot_doctor_time` (`doctor_id`,`slot_datetime`),
  ADD KEY `idx_slot_doctor_date_booked` (`doctor_id`,`slot_datetime`,`is_booked`),
  ADD KEY `idx_slot_datetime` (`slot_datetime`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appt_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `fk_slot_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
