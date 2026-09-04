-- Tạo bảng year_list
CREATE TABLE IF NOT EXISTS `year_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chèn dữ liệu
INSERT INTO `year_list` (`id`, `code`, `year`) VALUES
(1, 'V', 2017),
(2, 'W', 2018),
(3, 'X', 2019),
(4, 'y', 2020),
(5, 'Z', 2021),
(6, 'A', 2022),
(7, 'B', 2023),
(8, 'C', 2024),
(9, 'D', 2025),
(10, 'E', 2026),
(11, 'F', 2027),
(12, 'G', 2028)