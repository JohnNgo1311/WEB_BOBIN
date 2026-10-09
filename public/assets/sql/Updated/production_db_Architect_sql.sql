-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12
-- Kiến trúc Cơ sở dữ liệu Chuẩn: `production_db` (Synchronized Architecture)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `production_db`
--
CREATE DATABASE IF NOT EXISTS `production_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `production_db`;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_capacity`
--

DROP TABLE IF EXISTS `bobin_capacity`;
CREATE TABLE `bobin_capacity` (
  `size_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_history`
--

DROP TABLE IF EXISTS `bobin_history`;
CREATE TABLE `bobin_history` (
  `id` int(11) NOT NULL,
  `bobin_key_code` varchar(50) NOT NULL,
  `bobin_identification_code` varchar(50) NOT NULL,
  `bobin_size` enum('PL7-3','PL4-7 (TU04.TU06)','PL4-7 (TU08~)') NOT NULL,
  `bobin_type` enum('Sản xuất','Bù','Điều chỉnh (Do CP)','Điều chỉnh (Ngoại quan: Gel)','Điều chỉnh (Ngoại quan: Dị vật)','Điều chỉnh (Ngoại quan: Trầy)','Điều chỉnh (Ngoại quan: Biến dạng)','Điều chỉnh (Ngoại quan: Xước)','Điều chỉnh (Ngoại quan: Chữ in)','Điều chỉnh (Ngoại quan: Vón cục)','Điều chỉnh (Ngoại quan: Màu)') NOT NULL,
  `extrusion_employee` text DEFAULT NULL,
  `extrusion_check` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extrusion_check`)),
  `rack` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rack`)),
  `products` text DEFAULT NULL,
  `material_lot` text DEFAULT NULL,
  `print_lot` varchar(50) DEFAULT NULL,
  `length_m` decimal(10,3) DEFAULT NULL,
  `shift` enum('Ca 1','Ca 2','Ca 3','Hành chính') DEFAULT 'Ca 1',
  `extrusion_date` date DEFAULT NULL,
  `finish_time` datetime DEFAULT NULL,
  `bobin_current_status` enum('Rolled','Busy_Unchecked','Busy_Checked','Pending_Cancellation','Cancelled') DEFAULT 'Rolled',
  `visual_inspection` text DEFAULT NULL,
  `winding_machine` varchar(50) DEFAULT NULL,
  `winding_employee` text DEFAULT NULL,
  `winding_note` varchar(100) DEFAULT NULL,
  `flow_test_result` enum('Thành công','Thất bại') DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `update_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`update_history`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_list_detail`
--

DROP TABLE IF EXISTS `bobin_list_detail`;
CREATE TABLE `bobin_list_detail` (
  `id` int(11) NOT NULL,
  `bobin_key_code` varchar(50) NOT NULL,
  `bobin_identification_code` varchar(50) NOT NULL,
  `bobin_size` enum('PL7-3','PL4-7 (TU04.TU06)','PL4-7 (TU08~)') NOT NULL,
  `bobin_type` enum('Sản xuất','Bù','Điều chỉnh (Do CP)','Điều chỉnh (Ngoại quan: Gel)','Điều chỉnh (Ngoại quan: Dị vật)','Điều chỉnh (Ngoại quan: Trầy)','Điều chỉnh (Ngoại quan: Biến dạng)','Điều chỉnh (Ngoại quan: Xước)','Điều chỉnh (Ngoại quan: Chữ in)','Điều chỉnh (Ngoại quan: Vón cục)','Điều chỉnh (Ngoại quan: Màu)') NOT NULL,
  `extrusion_employee` text DEFAULT NULL,
  `extrusion_check` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extrusion_check`)),
  `rack` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rack`)),
  `products` text DEFAULT NULL,
  `material_lot` text DEFAULT NULL,
  `print_lot` varchar(50) DEFAULT NULL,
  `length_m` decimal(10,3) DEFAULT NULL,
  `shift` enum('Ca 1','Ca 2','Ca 3','Hành chính') DEFAULT 'Ca 1',
  `extrusion_date` date DEFAULT NULL,
  `finish_time` datetime DEFAULT NULL,
  `bobin_current_status` enum('Rolled','Busy_Unchecked','Busy_Checked','Pending_Cancellation','Cancelled') DEFAULT 'Rolled',
  `visual_inspection` text DEFAULT NULL,
  `winding_machine` varchar(50) DEFAULT NULL,
  `winding_employee` text DEFAULT NULL,
  `flow_test_result` enum('Thành công','Thất bại') DEFAULT NULL,
  `winding_note` varchar(100) DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `update_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`update_history`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_list_general`
--

DROP TABLE IF EXISTS `bobin_list_general`;
CREATE TABLE `bobin_list_general` (
  `id` int(11) NOT NULL,
  `bobin_key_code` varchar(50) NOT NULL,
  `bobin_identification_code` varchar(50) NOT NULL,
  `bobin_size` enum('PL7-3','PL4-7 (TU04.TU06)','PL4-7 (TU08~)') NOT NULL,
  `bobin_type` enum('Sản xuất','Bù','Điều chỉnh (Do CP)','Điều chỉnh (Ngoại quan: Gel)','Điều chỉnh (Ngoại quan: Dị vật)','Điều chỉnh (Ngoại quan: Trầy)','Điều chỉnh (Ngoại quan: Biến dạng)','Điều chỉnh (Ngoại quan: Xước)','Điều chỉnh (Ngoại quan: Chữ in)','Điều chỉnh (Ngoại quan: Vón cục)','Điều chỉnh (Ngoại quan: Màu)') NOT NULL,
  `bobin_current_status` enum('Rolled','Busy_Unchecked','Busy_Checked','Pending_Cancellation','Cancelled') DEFAULT 'Rolled',
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `day_list`
--

DROP TABLE IF EXISTS `day_list`;
CREATE TABLE `day_list` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `day` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employee_list`
--

DROP TABLE IF EXISTS `employee_list`;
CREATE TABLE `employee_list` (
  `id` int(11) NOT NULL,
  `employee_code` varchar(50) NOT NULL COMMENT 'Mã nhân viên',
  `employee_name` varchar(100) NOT NULL COMMENT 'Họ và tên nhân viên',
  `cost_center` varchar(50) DEFAULT NULL COMMENT 'Mã bộ phận',
  `role` enum('extrusion','qc','winding','admin') NOT NULL DEFAULT 'extrusion' COMMENT 'Vai trò phân quyền',
  `username` varchar(50) NOT NULL COMMENT 'Tên đăng nhập',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu mã hóa password_hash',
  `is_first_login` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Chưa đổi mật khẩu lần đầu, 0: Đã đổi mật khẩu',
  `permissions` longtext DEFAULT NULL COMMENT 'Mảng JSON lưu danh sách mã quyền thao tác tùy biến (nếu NULL thì theo Role mặc định)',
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `extrusion_machine_list`
--

DROP TABLE IF EXISTS `extrusion_machine_list`;
CREATE TABLE `extrusion_machine_list` (
  `id` int(11) NOT NULL,
  `machine_number` int(11) NOT NULL,
  `machine_code` varchar(10) NOT NULL,
  `machine_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `machine_list`
--

DROP TABLE IF EXISTS `machine_list`;
CREATE TABLE `machine_list` (
  `id` int(11) NOT NULL,
  `machine_number` int(11) NOT NULL,
  `machine_code` varchar(10) NOT NULL,
  `machine_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material_list`
--

DROP TABLE IF EXISTS `material_list`;
CREATE TABLE `material_list` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `grinding_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material_lot_list`
--

DROP TABLE IF EXISTS `material_lot_list`;
CREATE TABLE `material_lot_list` (
  `id` int(11) NOT NULL,
  `lot` varchar(50) NOT NULL,
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `month_list`
--

DROP TABLE IF EXISTS `month_list`;
CREATE TABLE `month_list` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `month` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_list`
--

DROP TABLE IF EXISTS `product_list`;
CREATE TABLE `product_list` (
  `id` int(11) NOT NULL,
  `production_order_code` varchar(50) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rack_list`
--

DROP TABLE IF EXISTS `rack_list`;
CREATE TABLE `rack_list` (
  `id` int(11) NOT NULL,
  `rack_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `winding_machine_list`
--

DROP TABLE IF EXISTS `winding_machine_list`;
CREATE TABLE `winding_machine_list` (
  `id` int(11) NOT NULL,
  `machine_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `year_list`
--

DROP TABLE IF EXISTS `year_list`;
CREATE TABLE `year_list` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `year` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bobin_capacity`
--
ALTER TABLE `bobin_capacity`
  ADD PRIMARY KEY (`size_name`);

--
-- Chỉ mục cho bảng `bobin_history`
--
ALTER TABLE `bobin_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_key_code` (`bobin_key_code`),
  ADD KEY `idx_hist_ident_code` (`bobin_identification_code`),
  ADD KEY `idx_hist_status` (`bobin_current_status`),
  ADD KEY `idx_hist_updated_time` (`updated_time`),
  ADD KEY `idx_hist_finish_time` (`finish_time`);

--
-- Chỉ mục cho bảng `bobin_list_detail`
--
ALTER TABLE `bobin_list_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bobin_key_code` (`bobin_key_code`),
  ADD UNIQUE KEY `bobin_identification_code` (`bobin_identification_code`),
  ADD KEY `idx_detail_status` (`bobin_current_status`);

--
-- Chỉ mục cho bảng `bobin_list_general`
--
ALTER TABLE `bobin_list_general`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bobin_key_code` (`bobin_key_code`),
  ADD UNIQUE KEY `bobin_identification_code` (`bobin_identification_code`),
  ADD KEY `idx_general_status` (`bobin_current_status`);

--
-- Chỉ mục cho bảng `day_list`
--
ALTER TABLE `day_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `employee_list`
--
ALTER TABLE `employee_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_employee_code` (`employee_code`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_cost_center` (`cost_center`);

--
-- Chỉ mục cho bảng `extrusion_machine_list`
--
ALTER TABLE `extrusion_machine_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `machine_list`
--
ALTER TABLE `machine_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `material_list`
--
ALTER TABLE `material_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `material_lot_list`
--
ALTER TABLE `material_lot_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `month_list`
--
ALTER TABLE `month_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `product_list`
--
ALTER TABLE `product_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_order_code` (`production_order_code`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Chỉ mục cho bảng `rack_list`
--
ALTER TABLE `rack_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rack_code` (`rack_code`);

--
-- Chỉ mục cho bảng `winding_machine_list`
--
ALTER TABLE `winding_machine_list`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `year_list`
--
ALTER TABLE `year_list`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng
--

ALTER TABLE `bobin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bobin_list_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `bobin_list_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `day_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `employee_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `extrusion_machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `material_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `material_lot_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `month_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `product_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rack_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `winding_machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `year_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
