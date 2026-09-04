-- Tạo bảng month_list
CREATE TABLE IF NOT EXISTS `month_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `month` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chèn dữ liệu
INSERT INTO `month_list` (`id`, `code`, `month`) VALUES
(1, 'o', 1),
(2, 'P', 2),
(3, 'Q', 3),
(4, 'R', 4),
(5, 'S', 5),
(6, 'T', 6),
(7, 'U', 7),
(8, 'V', 8),
(9, 'W', 9),
(10, 'X', 10),
(11, 'y', 11),
(12, 'Z', 12)