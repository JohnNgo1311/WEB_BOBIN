-- 1. Tạo bảng `day_list`
CREATE TABLE IF NOT EXISTS `day_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Chèn dữ liệu vào bảng
INSERT INTO `day_list` (`id`, `code`, `day`) VALUES
(1, 'A', 1),
(2, 'B', 2),
(3, 'C', 3),
(4, 'D', 4),
(5, 'E', 5),
(6, 'F', 6),
(7, 'G', 7),
(8, 'H', 8),
(9, 'i', 9),
(10, 'J', 10),
(11, 'K', 11),
(12, 'L', 12),
(13, 'M', 13),
(14, 'N', 14),
(15, 'o', 15),
(16, 'P', 16),
(17, 'Q', 17),
(18, 'R', 18),
(19, 'S', 19),
(20, 'T', 20),
(21, 'U', 21),
(22, 'V', 22),
(23, 'W', 23),
(24, 'X', 24),
(25, 'y', 25),
(26, 'Z', 26),
(27, 'd', 27),
(28, 'e', 28),
(29, 'f', 29),
(30, 'g', 30),
(31, 'h', 31)