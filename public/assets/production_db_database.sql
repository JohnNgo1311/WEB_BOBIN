-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 06, 2026 lúc 04:14 PM
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_capacity`
--

CREATE TABLE `bobin_capacity` (
  `size_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_history`
--

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
  `update_history` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_list_detail`
--

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
  `update_history` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bobin_list_general`
--

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

CREATE TABLE `day_list` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `day` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employee_list`
--

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

INSERT INTO `employee_list` (`id`, `employee_code`, `employee_name`, `cost_center`, `role`, `username`, `password`, `is_first_login`, `permissions`, `updated_time`) VALUES
(1, '01510036', 'Nguyễn Thành Thân', 'A00536', 'admin', '01510036', '123', 1, null, '2026-10-03 22:00:00'),
(2, '01910698', 'Nguyễn Thị Hiền', 'A00536', 'admin', '01910698', '123', 1, null, '2026-10-03 22:00:00'),
(3, '01911022', 'Kiều Minh Thiện', 'A00791', 'admin', '01911022', '123', 1, null, '2026-10-03 22:00:00'),
(4, '02114273', 'Thân Trọng Tuấn', 'A00564', 'admin', '02114273', '123', 1, null, '2026-10-03 22:00:00'),
(5, '02114811', 'Nguyễn Chấn Huy', 'A00565', 'admin', '02114811', '123', 1, null, '2026-10-03 22:00:00'),
(6, '01920507', 'Hồ Thị Mỹ', 'A00340', 'admin', '01920507', '123', 1, null, '2026-10-03 22:00:00'),
(7, '02411884', 'Phan Đình Lâm', 'A00340', 'admin', '02411884', '123', 1, null, '2026-10-03 22:00:00'),
(8, '02516134', 'Ngô Xuân Nghĩa', 'A00340', 'admin', '02516134', '123', 1, null, '2026-10-03 22:00:00'),
(9, '02519007', 'Ninh Quang Trường', 'A00340', 'admin', '02519007', '123', 1, null, '2026-10-03 22:00:00'),
(10, '02619486', 'Nguyễn Đức Huy', 'A00340', 'admin', '02619486', '123', 1, null, '2026-10-03 22:00:00'),
(11, '02317843', 'Đinh Hoàng Phúc', 'A00853', 'admin', '02317843', '123', 1, null, '2026-10-03 22:00:00'),
(12, '01920598', 'Trịnh Minh Thiện', 'A00791', 'admin', '01920598', '123', 1, null, '2026-10-03 22:00:00'),
(13, '01920871', 'Xa Minh Hiếu', 'A00791', 'admin', '01920871', '123', 1, null, '2026-10-03 22:00:00'),
(14, '02216809', 'Nguyễn Bá Nhân Hậu', 'A00855', 'admin', '02216809', '123', 1, null, '2026-10-03 22:00:00'),
(15, '01920172', 'Võ Thị Mỹ', 'A00792', 'qc', '01920172', '123', 1, null, '2026-10-03 22:00:00'),
(16, '01920525', 'Lương Thị Vân', 'A00792', 'qc', '01920525', '123', 1, null, '2026-10-03 22:00:00'),
(17, '02125543', 'Đinh Thị Huyền Trang', 'A00792', 'qc', '02125543', '123', 1, null, '2026-10-03 22:00:00'),
(18, '02649247', 'Trương Cẩm Mừng', 'A00792', 'qc', '02649247', '123', 1, null, '2026-10-03 22:00:00'),
(19, '02649256', 'Nguyễn Thị Thu', 'A00792', 'qc', '02649256', '123', 1, null, '2026-10-03 22:00:00'),
(20, '01920589', 'Nguyễn Ngọc Thiện', 'A00330', 'extrusion', '01920589', '123', 1, null, '2026-10-03 22:00:00'),
(21, '01920668', 'Hà Viết Thế Thiện', 'A00852', 'extrusion', '01920668', '123', 1, null, '2026-10-03 22:00:00'),
(22, '01920862', 'Nguyễn Khắc Kiên', 'A00330', 'extrusion', '01920862', '123', 1, null, '2026-10-03 22:00:00'),
(23, '01921551', 'Trương Quang Nghĩa', 'A00330', 'extrusion', '01921551', '123', 1, null, '2026-10-03 22:00:00'),
(24, '01921560', 'Trần Quang Hiệu', 'A00330', 'extrusion', '01921560', '123', 1, null, '2026-10-03 22:00:00'),
(25, '02020390', 'Võ Thiên Tuế', 'A00330', 'extrusion', '02020390', '123', 1, null, '2026-10-03 22:00:00'),
(26, '02020488', 'Lê Thanh Trúc', 'A00330', 'extrusion', '02020488', '123', 1, null, '2026-10-03 22:00:00'),
(27, '02020497', 'Vũ Nhân Tài', 'A00330', 'extrusion', '02020497', '123', 1, null, '2026-10-03 22:00:00'),
(28, '02021645', 'Trịnh Xuân Tỉnh', 'A00330', 'extrusion', '02021645', '123', 1, null, '2026-10-03 22:00:00'),
(29, '02020965', 'Hà Xuân Tiến', 'A00330', 'extrusion', '02020965', '123', 1, null, '2026-10-03 22:00:00'),
(30, '02021131', 'Nguyễn Công Anh', 'A00330', 'extrusion', '02021131', '123', 1, null, '2026-10-03 22:00:00'),
(31, '02021159', 'Trần Vương', 'A00330', 'extrusion', '02021159', '123', 1, null, '2026-10-03 22:00:00'),
(32, '02021742', 'Nguyễn Ngọc Dũng', 'A00330', 'extrusion', '02021742', '123', 1, null, '2026-10-03 22:00:00'),
(33, '02022149', 'Nguyễn Xuân Mạnh', 'A00330', 'extrusion', '02022149', '123', 1, null, '2026-10-03 22:00:00'),
(34, '02022158', 'Bùi Lê Đức Minh', 'A00330', 'extrusion', '02022158', '123', 1, null, '2026-10-03 22:00:00'),
(35, '02220255', 'Nguyễn Đức Sơn', 'A00330', 'extrusion', '02220255', '123', 1, null, '2026-10-03 22:00:00'),
(36, '02221908', 'Nguyễn Minh Hoàng', 'A00330', 'extrusion', '02221908', '123', 1, null, '2026-10-03 22:00:00'),
(37, '02222785', 'Nguyễn Anh Duy', 'A00330', 'extrusion', '02222785', '123', 1, null, '2026-10-03 22:00:00'),
(38, '02223535', 'Nguyễn Quốc', 'A00330', 'extrusion', '02223535', '123', 1, null, '2026-10-03 22:00:00'),
(39, '02224288', 'Võ Văn Dương', 'A00330', 'extrusion', '02224288', '123', 1, null, '2026-10-03 22:00:00'),
(40, '02224358', 'Hoàng Trung Hiếu', 'A00852', 'extrusion', '02224358', '123', 1, null, '2026-10-03 22:00:00'),
(41, '02224367', 'Ngô Minh Hiếu', 'A00330', 'extrusion', '02224367', '123', 1, null, '2026-10-03 22:00:00'),
(42, '02226596', 'Đoàn Văn Dương', 'A00852', 'extrusion', '02226596', '123', 1, null, '2026-10-03 22:00:00'),
(43, '02227708', 'Phạm Việt Khải', 'A00330', 'extrusion', '02227708', '123', 1, null, '2026-10-03 22:00:00'),
(44, '02228947', 'Phạm Lê Minh Tân', 'A00852', 'extrusion', '02228947', '123', 1, null, '2026-10-03 22:00:00'),
(45, '02229894', 'Nguyễn Hữu Tài', 'A00330', 'extrusion', '02229894', '123', 1, null, '2026-10-03 22:00:00'),
(46, '02241656', 'Lương Trọng Ân', 'A00330', 'extrusion', '02241656', '123', 1, null, '2026-10-03 22:00:00'),
(47, '02243991', 'Bùi Quang Chưởng', 'A00330', 'extrusion', '02243991', '123', 1, null, '2026-10-03 22:00:00'),
(48, '02325295', 'Lưu Văn Đủ', 'A00330', 'extrusion', '02325295', '123', 1, null, '2026-10-03 22:00:00'),
(49, '02325301', 'Cao Thế Mỹ', 'A00330', 'extrusion', '02325301', '123', 1, null, '2026-10-03 22:00:00'),
(50, '02325310', 'Nguyễn Công Hậu', 'A00330', 'extrusion', '02325310', '123', 1, null, '2026-10-03 22:00:00'),
(51, '02325365', 'Trần Minh Nhật', 'A00330', 'extrusion', '02325365', '123', 1, null, '2026-10-03 22:00:00'),
(52, '02325374', 'Trương Văn Tình', 'A00330', 'extrusion', '02325374', '123', 1, null, '2026-10-03 22:00:00'),
(53, '02645241', 'Nguyễn Đình Hoàng', 'A00330', 'extrusion', '02645241', '123', 1, null, '2026-10-03 22:00:00'),
(54, '02645250', 'Trần Xuân Bắc', 'A00330', 'extrusion', '02645250', '123', 1, null, '2026-10-03 22:00:00'),
(55, '02645269', 'Lê Thanh Toàn', 'A00330', 'extrusion', '02645269', '123', 1, null, '2026-10-03 22:00:00'),
(56, '02645278', 'MOHA MAD NAZID', 'A00330', 'extrusion', '02645278', '123', 1, null, '2026-10-03 22:00:00'),
(57, '02648105', 'Nguyễn Nhật Luân', 'A00330', 'extrusion', '02648105', '123', 1, null, '2026-10-03 22:00:00'),
(58, '02648114', 'Vòng Nhục Dưởng', 'A00330', 'extrusion', '02648114', '123', 1, null, '2026-10-03 22:00:00'),
(59, '02648123', 'Nguyễn Phạm Ngọc Hiền', 'A00330', 'extrusion', '02648123', '123', 1, null, '2026-10-03 22:00:00'),
#N/A
(61, '02020752', 'Nguyễn Chí Cường', 'A00442', 'extrusion', '02020752', '123', 1, null, '2026-10-03 22:00:00'),
(62, '02020947', 'Nguyễn Hoàng Duy', 'A00442', 'extrusion', '02020947', '123', 1, null, '2026-10-03 22:00:00'),
(63, '02121404', 'Lê Hoàng Minh Tâm', 'A00442', 'extrusion', '02121404', '123', 1, null, '2026-10-03 22:00:00'),
(64, '02222350', 'Hoàng Văn Nguyên', 'A00442', 'extrusion', '02222350', '123', 1, null, '2026-10-03 22:00:00'),
(65, '02223526', 'Nguyễn Thiên Phú', 'A00442', 'extrusion', '02223526', '123', 1, null, '2026-10-03 22:00:00'),
(66, '02223605', 'Võ Minh Bắc', 'A00442', 'extrusion', '02223605', '123', 1, null, '2026-10-03 22:00:00'),
(67, '02243654', 'Lưu Văn Giang', 'A00442', 'extrusion', '02243654', '123', 1, null, '2026-10-03 22:00:00'),
(68, '02622116', 'Trịnh Nhựt Khánh', 'A00442', 'extrusion', '02622116', '123', 1, null, '2026-10-03 22:00:00'),
(69, '02644057', 'Phạm Văn Công', 'A00442', 'extrusion', '02644057', '123', 1, null, '2026-10-03 22:00:00'),
(70, '02644075', 'Huỳnh Đại Nhân', 'A00442', 'extrusion', '02644075', '123', 1, null, '2026-10-03 22:00:00'),
(71, '01920163', 'Lê Thị Phương Dung', 'A00430', 'winding', '01920163', '123', 1, null, '2026-10-03 22:00:00'),
(72, '01920260', 'Nguyễn Thị Lý', 'A00430', 'winding', '01920260', '123', 1, null, '2026-10-03 22:00:00'),
(73, '01920552', 'Lê Thị Phượng Hằng', 'A00430', 'winding', '01920552', '123', 1, null, '2026-10-03 22:00:00'),
(75, '01921603', 'Nguyễn Trần Anh Thư', 'A00430', 'winding', '01921603', '123', 1, null, '2026-10-03 22:00:00'),
(76, '01921700', 'Trần Thị Minh Phương', 'A00430', 'winding', '01921700', '123', 1, null, '2026-10-03 22:00:00'),
(77, '01921694', 'Tạ Tuyền Phong', 'A00430', 'winding', '01921694', '123', 1, null, '2026-10-03 22:00:00'),
(78, '01921764', 'Nguyễn Trung Nhân', 'A00430', 'winding', '01921764', '123', 1, null, '2026-10-03 22:00:00'),
(79, '02021496', 'Nguyễn Việt Thắng', 'A00430', 'winding', '02021496', '123', 1, null, '2026-10-03 22:00:00'),
(80, '02021502', 'Phan Thị Huệ', 'A00430', 'winding', '02021502', '123', 1, null, '2026-10-03 22:00:00'),
(81, '02021511', 'Võ Thị Vân Anh', 'A00430', 'winding', '02021511', '123', 1, null, '2026-10-03 22:00:00'),
(82, '02021788', 'Nguyễn Thị Cẩm Tú', 'A00430', 'winding', '02021788', '123', 1, null, '2026-10-03 22:00:00'),
(83, '02021797', 'Hoàng Thị Yên', 'A00430', 'winding', '02021797', '123', 1, null, '2026-10-03 22:00:00'),
(84, '02021803', 'Nguyễn Thị Yến Văn', 'A00854', 'winding', '02021803', '123', 1, null, '2026-10-03 22:00:00'),
(85, '02121398', 'Lê Đại Dương', 'A00430', 'winding', '02121398', '123', 1, null, '2026-10-03 22:00:00'),
(86, '02121714', 'Lê Hải', 'A00430', 'winding', '02121714', '123', 1, null, '2026-10-03 22:00:00'),
(87, '02125552', 'Trần Thị Thanh Kiều', 'A00430', 'winding', '02125552', '123', 1, null, '2026-10-03 22:00:00'),
(88, '02125996', 'Lê Thị Thanh Vân', 'A00430', 'winding', '02125996', '123', 1, null, '2026-10-03 22:00:00'),
(89, '02126667', 'Đào Hoàng Vũ', 'A00430', 'winding', '02126667', '123', 1, null, '2026-10-03 22:00:00'),
(90, '02220945', 'Vũ Thị Thu Hồng', 'A00430', 'winding', '02220945', '123', 1, null, '2026-10-03 22:00:00'),
(91, '02223580', 'Ngô Ánh Ngọc', 'A00430', 'winding', '02223580', '123', 1, null, '2026-10-03 22:00:00'),
(92, '02224428', 'Lê Thị Lụa', 'A00430', 'winding', '02224428', '123', 1, null, '2026-10-03 22:00:00'),
(93, '02224455', 'Ân Thành Trí', 'A00430', 'winding', '02224455', '123', 1, null, '2026-10-03 22:00:00'),
(94, '02225065', 'Trần Thị Thảo', 'A00430', 'winding', '02225065', '123', 1, null, '2026-10-03 22:00:00'),
(95, '02225658', 'Nguyễn Nga Hoàng Dung', 'A00430', 'winding', '02225658', '123', 1, null, '2026-10-03 22:00:00'),
(96, '02226602', 'Nguyễn Thị Tuyết', 'A00430', 'winding', '02226602', '123', 1, null, '2026-10-03 22:00:00'),
(97, '02228141', 'Nguyễn Thị Ngọc Quỳnh', 'A00430', 'winding', '02228141', '123', 1, null, '2026-10-03 22:00:00'),
(98, '02229511', 'Phạm Thanh Lý', 'A00430', 'winding', '02229511', '123', 1, null, '2026-10-03 22:00:00'),
(99, '02242761', 'Lê Đức Minh', 'A00430', 'winding', '02242761', '123', 1, null, '2026-10-03 22:00:00'),
(100, '02324728', 'Bùi Thị Cẩm Nhi', 'A00430', 'winding', '02324728', '123', 1, null, '2026-10-03 22:00:00'),
(101, '02324755', 'Lê Hải Vũ', 'A00430', 'winding', '02324755', '123', 1, null, '2026-10-03 22:00:00'),
(102, '02243663', 'Vũ Văn Đông', 'A00430', 'winding', '02243663', '123', 1, null, '2026-10-03 22:00:00'),
(103, '02325277', 'Võ Hoàng Nhật Vi', 'A00430', 'winding', '02325277', '123', 1, null, '2026-10-03 22:00:00'),
(104, '02521747', 'Nguyễn Hoài Hậu', 'A00430', 'winding', '02521747', '123', 1, null, '2026-10-03 22:00:00'),
(105, '02521756', 'Lê Thị Kim Hồng', 'A00430', 'winding', '02521756', '123', 1, null, '2026-10-03 22:00:00'),
(106, '02525044', 'Kim Thị Thương', 'A00430', 'winding', '02525044', '123', 1, null, '2026-10-03 22:00:00'),
(107, '02525628', 'Lý Thị Thanh Hằng', 'A00430', 'winding', '02525628', '123', 1, null, '2026-10-03 22:00:00'),
(108, '02525637', 'Nguyễn Thị Huyền Trang', 'A00430', 'winding', '02525637', '123', 1, null, '2026-10-03 22:00:00'),
(109, '02525646', 'Vũ Thị Thủy', 'A00430', 'winding', '02525646', '123', 1, null, '2026-10-03 22:00:00'),
(110, '02525655', 'Hà Thị Mỹ Hoa', 'A00430', 'winding', '02525655', '123', 1, null, '2026-10-03 22:00:00'),
(111, '02542485', 'Trần Hoàng Nam', 'A00430', 'winding', '02542485', '123', 1, null, '2026-10-03 22:00:00'),
(112, '02542494', 'Võ Phát Lợi', 'A00430', 'winding', '02542494', '123', 1, null, '2026-10-03 22:00:00'),
(113, '02644552', 'Hàn Thị Trang', 'A00430', 'winding', '02644552', '123', 1, null, '2026-10-03 22:00:00'),
(114, '02646082', 'Phan Khánh Linh', 'A00430', 'winding', '02646082', '123', 1, null, '2026-10-03 22:00:00'),
(115, '02644109', 'Đào Thiên Bình', 'A00442', 'winding', '02644109', '123', 1, null, '2026-10-03 22:00:00'),
(116, '02644118', 'Trần Khánh Linh', 'A00430', 'winding', '02644118', '123', 1, null, '2026-10-03 22:00:00'),
(117, '02644127', 'Vũ Thị Dung', 'A00430', 'winding', '02644127', '123', 1, null, '2026-10-03 22:00:00'),
(118, '02644136', 'Dương Thị Thanh Trúc', 'A00854', 'winding', '02644136', '123', 1, null, '2026-10-03 22:00:00'),
(119, '02646365', 'Đinh Thị Đài Trang', 'A00430', 'winding', '02646365', '123', 1, null, '2026-10-03 22:00:00'),
(120, '02646383', 'Trần Thị Mỹ Duyên', 'A00430', 'winding', '02646383', '123', 1, null, '2026-10-03 22:00:00'),
(121, '02646392', 'Trần Thị Tuyết', 'A00430', 'winding', '02646392', '123', 1, null, '2026-10-03 22:00:00'),
(122, '02646408', 'Lưu Thị Phượng', 'A00430', 'winding', '02646408', '123', 1, null, '2026-10-03 22:00:00'),
(123, '02646417', 'Đỗ Thị Yến Nhi', 'A00430', 'winding', '02646417', '123', 1, null, '2026-10-03 22:00:00'),
(125, '02648132', 'Trần Vũ Đỉnh', 'A00430', 'winding', '02648132', '123', 1, null, '2026-10-03 22:00:00'),
(126, '02648141', 'Nguyễn Việt Dũng', 'A00430', 'winding', '02648141', '123', 1, null, '2026-10-03 22:00:00'),
(127, '02648150', 'Phạm Ngọc Lợi', 'A00430', 'winding', '02648150', '123', 1, null, '2026-10-03 22:00:00'),
(128, '02648169', 'Trần Thị Hoài Thu', 'A00430', 'winding', '02648169', '123', 1, null, '2026-10-03 22:00:00'),
(129, '02648187', 'Thạch Thị Lệ', 'A00430', 'winding', '02648187', '123', 1, null, '2026-10-03 22:00:00'),
(130, '02648196', 'Lê Kim Ngân', 'A00430', 'winding', '02648196', '123', 1, null, '2026-10-03 22:00:00'),
(131, '02648202', 'Lê Sơn Phượng', 'A00430', 'winding', '02648202', '123', 1, null, '2026-10-03 22:00:00'),
(132, '02648211', 'Phạm Thị Quỳnh Như', 'A00430', 'winding', '02648211', '123', 1, null, '2026-10-03 22:00:00'),
(133, '02649210', 'Nguyễn Trung Hậu', 'A00430', 'winding', '02649210', '123', 1, null, '2026-10-03 22:00:00'),
(134, '02649229', 'Võ Thị Hương', 'A00430', 'winding', '02649229', '123', 1, null, '2026-10-03 22:00:00'),
(135, '02649238', 'Huỳnh Thị Kim Hậu', 'A00430', 'winding', '02649238', '123', 1, null, '2026-10-03 22:00:00');
-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `extrusion_machine_list`
--

CREATE TABLE `extrusion_machine_list` (
  `id` int(11) NOT NULL,
  `machine_number` int(11) NOT NULL,
  `machine_code` varchar(10) NOT NULL,
  `machine_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material_list`
--

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

CREATE TABLE `material_lot_list` (
  `id` int(11) NOT NULL,
  `lot` varchar(50) NOT NULL,
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `month_list`
--

CREATE TABLE `month_list` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `month` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_list`
--

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

CREATE TABLE `rack_list` (
  `id` int(11) NOT NULL,
  `rack_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `winding_machine_list`
--

CREATE TABLE `winding_machine_list` (
  `id` int(11) NOT NULL,
  `machine_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `year_list`
--

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
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_active` (`is_active`);

--
-- Chỉ mục cho bảng `extrusion_machine_list`
--
ALTER TABLE `extrusion_machine_list`
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
-- AUTO_INCREMENT cho bảng `employee_list`
--
ALTER TABLE `employee_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `extrusion_machine_list`
--
ALTER TABLE `extrusion_machine_list`
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
