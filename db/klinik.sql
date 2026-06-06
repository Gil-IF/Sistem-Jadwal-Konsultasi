-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 06, 2026 at 06:26 PM
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

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `full_name`, `email`, `password_hash`, `created_at`, `is_active`) VALUES
(1, 'admin1', 'Admin Utama', 'admin@klinik.com', '$2b$12$NWJTwXXyvvnZouL48HRe1OZVIApov7s0Lx1FnTcct8HoM8n.Wkkjm', '2026-06-05 09:32:08', 1),
(2, 'admin2', 'Admin Baru', 'admin2@klinik.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-06-06 23:40:52', 1);

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
(1, 1, 2, '2026-06-05 09:32:08', 'completed', 'Ingin konsultasi demam');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `specialization`, `license_number`, `is_active`) VALUES
(1, 'dr. Andi Pratama', 'Dokter Umum', '1234567890', 1),
(2, 'dr. Eagan Rainmahasin', 'Dokter Mata', '313123', 1);

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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Budi Santoso', 'budi@email.com', '08123456789', '$2b$12$Zc5Eivw/hwtU/53wp2iOyeS.8NFskmuo13S.FIwP5IZrg1k1J9hLm', '2026-06-05 09:32:08', '2026-06-05 09:43:31'),
(2, 'Hendra Lesmana', 'hendraalim@gmail.com', '082143454637', '$2y$10$BFNdxjfWyP9aqDbFkoyZ9ubsxcNMtaVdQjerT5.QGiNV8tbSSTsSC', '2026-06-07 00:13:05', '2026-06-07 00:13:05'),
(3, 'Bambang', 'bambangalim@gmail.com', '083839717338', '$2y$10$1yb3MS7zbeqEIXTS8lcATuFCNml2IqZdORMfS4/5BfKihMZ8AwnFa', '2026-06-07 00:15:16', '2026-06-07 00:15:16'),
(4, 'Pisang', 'pisangkuning@gmail.com', '0823323221', '$2y$10$hLV2UKOYg0LYRqLbdA04leZgArqEJSQYExEAD1NalzcHwjwKoUjA2', '2026-06-07 00:53:25', '2026-06-07 00:53:25');

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
(2, 1, '2026-06-06 09:30:00', 30, 1, '2026-06-05 09:32:08');

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
  ADD UNIQUE KEY `uq_appt_slot` (`slot_id`),
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
