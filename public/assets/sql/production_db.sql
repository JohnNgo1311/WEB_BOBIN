-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th1 23, 2026 lúc 03:22 AM
-- Phiên bản máy phục vụ: 10.4.25-MariaDB
-- Phiên bản PHP: 8.1.10

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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_history`
--

CREATE TABLE `bobin_history` (
  `id` int(11) NOT NULL,
  `bobin_id` char(14) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `data_json` longtext DEFAULT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `performed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
--
-- Đang đổ dữ liệu cho bảng `bobin_history`
--
INSERT INTO `bobin_history` (`id`, `bobin_id`, `action`, `data_json`, `performed_by`, `performed_at`) VALUES
(1, '2', 'updated', '{\"employee_code\":\"02114273\",\"owner\":\"Thân Trọng Tuấn\",\"production_code\":null,\"product_code\":\"TU0425B-1MZ2\",\"material_lot\":\"FDSF\",\"print_lot\":\"FDS\",\"length_m\":\"3210\",\"shift\":\"Ca 1\",\"production_date\":\"2025-11-27\",\"finish_time\":null}', '02114273', '2025-11-27 15:13:38'),
(2, 'BB' 'BB251127151806', 'created', '{\"employee_code\":\"02114273\",\"owner\":\"Thân Trọng Tuấn\",\"production_code\":null,\"product_code\":\"TU1065B-1MZ2\",\"material_lot\":\"REASD\",\"print_lot\":\"FADCZ\",\"length_m\":\"1000\",\"shift\":\"Ca 1\",\"production_date\":\"2025-11-27\",\"finish_time\":null}', '02114273', '2025-11-27 15:18:06'),
(3, 'BB' 'BB251127151806', 'updated', '{\"employee_code\":\"02114811\",\"owner\":\"Nguyễn Chấn Huy\",\"production_code\":null,\"product_code\":\"TU0425B-1MZ2\",\"material_lot\":\"FDSF\",\"print_lot\":\"FADCZ\",\"length_m\":\"3200\",\"shift\":\"Ca 2\",\"production_date\":\"2025-11-27\",\"finish_time\":null}', '02114811', '2025-11-27 15:19:43'),
(4, 'BB' 'BB251127153722', 'created', '{\"employee_code\":\"02021131\",\"owner\":\"Nguyễn Công Anh\",\"production_code\":null,\"product_code\":\"TIUB01B-1MZ2\",\"material_lot\":\"REASD\",\"print_lot\":\"FDS\",\"length_m\":\"1000\",\"shift\":\"Ca 2\",\"production_date\":\"2025-11-27\",\"finish_time\":null}', '02021131', '2025-11-27 15:37:22'),
(5, 'BB' 'BB251127155747', 'created', '{\"employee_code\":\"02021131\",\"owner\":\"Nguyễn Công Anh\",\"production_code\":null,\"product_code\":\"TU0425BU1-1MZ2\",\"material_lot\":\"FDSF\",\"print_lot\":\"FADCZ\",\"length_m\":\"1000\",\"shift\":\"Ca 2\",\"production_date\":\"2025-11-27\",\"finish_time\":\"27\\/11\\/2025 15:57:46\"}', '02021131', '2025-11-27 15:57:47'),
(6, 'BB' 'BB260123091112', 'created', '{\"employee_code\":\"02114273\",\"owner\":\"Thân Trọng Tuấn\",\"production_code\":null,\"product_code\":\"TU0425B-1MZ2\",\"material_lot\":\"FDSF\",\"print_lot\":\"FDS\",\"length_m\":\"5000\",\"shift\":\"Ca 1\",\"production_date\":\"2026-01-22\",\"finish_time\":\"23\\/01\\/2026 09:11:11\"}', '02114273', '2026-01-23 09:11:12');

-----------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reusable_values`
--

CREATE TABLE `reusable_values` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_used` datetime NOT NULL,
  `usage_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reusable_values`
--

INSERT INTO `reusable_values` (`id`, `key_name`, `value_text`, `last_used`, `usage_count`) VALUES
(1, 'employee_code', '02114273', '2026-01-23 09:11:12', 5),
(2, 'owner', 'Thân Trọng Tuấn', '2026-01-23 09:11:12', 5),
(3, 'production_code', 'P20221220', '2025-11-19 07:18:12', 1),
(4, 'material_lot', 'FDSF', '2026-01-23 09:11:12', 6),
(5, 'print_lot', 'FDS', '2026-01-23 09:11:12', 5),
(6, 'material_lot', 'REASD', '2025-11-27 15:37:22', 2),
(7, 'print_lot', 'FADCZ', '2025-11-27 15:57:47', 3),
(8, 'employee_code', '02114811', '2025-11-27 15:19:43', 1),
(9, 'owner', 'Nguyễn Chấn Huy', '2025-11-27 15:19:43', 1),
(10, 'employee_code', '02021131', '2025-11-27 15:57:47', 2),
(11, 'owner', 'Nguyễn Công Anh', '2025-11-27 15:57:47', 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bobins`
--
ALTER TABLE `bobins`
  ADD UNIQUE KEY `bobin_code` (`bobin_code`),
  ADD UNIQUE KEY `id_2` (`id`),
  ADD KEY `id` (`id`);

--
-- Chỉ mục cho bảng `bobin_history`
--
ALTER TABLE `bobin_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bobin_id` (`bobin_id`);

--
-- Chỉ mục cho bảng `bobin_list`
--
ALTER TABLE `bobin_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Chỉ mục cho bảng `reusable_values`
--
ALTER TABLE `reusable_values`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bobin_history`
--
ALTER TABLE `bobin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `bobin_list`
--
ALTER TABLE `bobin_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=301;

--
-- AUTO_INCREMENT cho bảng `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT cho bảng `reusable_values`
--
ALTER TABLE `reusable_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
