-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 24, 2026 lúc 07:36 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

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
CREATE DATABASE IF NOT EXISTS `production_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
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
  `updated_time` datetime DEFAULT NULL
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
  `updated_time` datetime DEFAULT NULL
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
  `employee_code` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp()
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `bobin_list_detail`
--
ALTER TABLE `bobin_list_detail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bobin_key_code` (`bobin_key_code`),
  ADD UNIQUE KEY `bobin_identification_code` (`bobin_identification_code`);

--
-- Chỉ mục cho bảng `bobin_list_general`
--
ALTER TABLE `bobin_list_general`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bobin_key_code` (`bobin_key_code`),
  ADD UNIQUE KEY `bobin_identification_code` (`bobin_identification_code`);

--
-- Chỉ mục cho bảng `day_list`
--
ALTER TABLE `day_list`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bobin_history`
--
ALTER TABLE `bobin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bobin_list_detail`
--
ALTER TABLE `bobin_list_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bobin_list_general`
--
ALTER TABLE `bobin_list_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `day_list`
--
ALTER TABLE `day_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `extrusion_machine_list`
--
ALTER TABLE `extrusion_machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `machine_list`
--
ALTER TABLE `machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `material_list`
--
ALTER TABLE `material_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `material_lot_list`
--
ALTER TABLE `material_lot_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `month_list`
--
ALTER TABLE `month_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rack_list`
--
ALTER TABLE `rack_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `winding_machine_list`
--
ALTER TABLE `winding_machine_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `year_list`
--
ALTER TABLE `year_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


--
-- Siêu dữ liệu
--
USE `phpmyadmin`;

--
-- Siêu dữ liệu cho bảng bobin_capacity
--

--
-- Siêu dữ liệu cho bảng bobin_history
--

--
-- Đang đổ dữ liệu cho bảng `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'production_db', 'bobin_history', '{\"sorted_col\":\"`bobin_history`.`updated_time` DESC\"}', '2026-03-07 12:14:36');

--
-- Siêu dữ liệu cho bảng bobin_list_detail
--

--
-- Đang đổ dữ liệu cho bảng `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'production_db', 'bobin_list_detail', '{\"sorted_col\":\"`bobin_list_detail`.`updated_time` DESC\"}', '2026-09-24 14:53:44');

--
-- Siêu dữ liệu cho bảng bobin_list_general
--

--
-- Siêu dữ liệu cho bảng day_list
--

--
-- Siêu dữ liệu cho bảng employee_list
--

--
-- Siêu dữ liệu cho bảng extrusion_machine_list
--

--
-- Siêu dữ liệu cho bảng machine_list
--

--
-- Siêu dữ liệu cho bảng material_list
--

--
-- Siêu dữ liệu cho bảng material_lot_list
--

--
-- Siêu dữ liệu cho bảng month_list
--

--
-- Siêu dữ liệu cho bảng product_list
--

--
-- Đang đổ dữ liệu cho bảng `pma__table_uiprefs`
--

INSERT INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'production_db', 'product_list', '{\"sorted_col\":\"`product_list`.`updated_time` ASC\"}', '2026-02-15 14:43:55');

--
-- Siêu dữ liệu cho bảng rack_list
--

--
-- Siêu dữ liệu cho bảng winding_machine_list
--

--
-- Siêu dữ liệu cho bảng year_list
--

--
-- Siêu dữ liệu cho cơ sở dữ liệu production_db
--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
