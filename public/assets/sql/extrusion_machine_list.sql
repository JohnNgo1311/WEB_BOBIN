-- 1. Tạo bảng extrusion_machine_list
CREATE TABLE IF NOT EXISTS `extrusion_machine_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_number` int(11) NOT NULL,
  `machine_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `machine_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Chèn dữ liệu
INSERT INTO `extrusion_machine_list` (`id`, `machine_number`, `machine_code`, `machine_name`) VALUES
(1, 1, '1', 'PL13'),
(2, 2, '2', 'PL12'),
(3, 3, '3', 'PL01'),
(4, 4, '4', 'PL02'),
(5, 5, '5', 'PL03'),
(6, 6, '6', 'PL04'),
(7, 7, '7', 'PL05'),
(8, 8, '8', 'PL06'),
(9, 9, '9', 'PL07'),
(10, 10, 'A', 'PL08'),
(11, 11, 'B', 'PL09'),
(12, 12, 'C', 'PL10'),
(13, 13, 'D', 'PL11'),
(14, 14, 'E', 'PL14'),
(15, 15, 'F', 'PL15'),
(16, 16, 'G', 'PL16'),
(17, 17, 'H', 'PL17'),
(18, 18, 'i', 'PL18'),
(19, 19, 'J', 'PL19'),
(20, 20, 'K', 'PL20'),
(21, 21, 'L', 'PL21'),
(22, 22, 'M', 'PL22'),
(23, 23, 'N', 'PL23'),
(24, 24, 'o', 'PL24'),
(25, 25, 'P', 'PL25'),
(26, 26, 'Q', 'PL26')