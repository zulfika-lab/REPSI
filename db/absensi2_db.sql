-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 02:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id` int(11) NOT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `alasan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `izin`
--

INSERT INTO `izin` (`id`, `nama`, `instansi`, `tanggal`, `jenis`, `keterangan`, `bukti`, `created_at`, `status`, `alasan`) VALUES
(1, 'Andro Lay', '', '2025-12-01', 'Sakit', '', 'uploads/1764779350_Notulen Rapat 8 Juli 2025.pdf', '2025-12-03 16:29:10', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `izin_status`
--

CREATE TABLE `izin_status` (
  `id` int(11) NOT NULL,
  `nama` varchar(200) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `alasan` text DEFAULT NULL,
  `alpha` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekapan_cache`
--

CREATE TABLE `rekapan_cache` (
  `id` int(11) NOT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `is_late` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_absen`
--

CREATE TABLE `rekap_absen` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `status` enum('hadir','terlambat','izin_approved','izin_rejected') DEFAULT NULL,
  `waktu` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rekap_absen`
--

INSERT INTO `rekap_absen` (`id`, `nama`, `tanggal`, `status`, `waktu`, `created_at`) VALUES
(1, 'Arthur Manoppo', '2025-12-05', 'izin_approved', NULL, '2025-12-04 22:42:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `izin_status`
--
ALTER TABLE `izin_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rekapan_cache`
--
ALTER TABLE `rekapan_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rekap_absen`
--
ALTER TABLE `rekap_absen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_absen` (`nama`,`tanggal`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `izin_status`
--
ALTER TABLE `izin_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rekapan_cache`
--
ALTER TABLE `rekapan_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_absen`
--
ALTER TABLE `rekap_absen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
