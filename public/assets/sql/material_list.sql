-- 1. Tạo bảng material_list
CREATE TABLE IF NOT EXISTS `material_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grinding_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Chèn dữ liệu
INSERT INTO `material_list` (`id`, `brand`, `code`, `grinding_time`) VALUES
(1, 'BASF', 'A', 0),
(2, 'BASF', 'B', 1),
(3, 'BASF', 'C', 2),
(4, 'DIC Covestro', 'D', 0),
(5, 'DIC Covestro', 'E', 1),
(6, 'DIC Covestro', 'F', 2),
(7, 'Huntsman', 'G', 0),
(8, 'Huntsman', 'H', 1),
(9, 'Huntsman', 'i', 2),
(10, 'Covestro', 'G', 0),
(11, 'Covestro', 'H', 1),
(12, 'Covestro', 'i', 2)