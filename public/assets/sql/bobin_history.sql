CREATE TABLE `bobin_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bobin_key_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bobin_identification_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bobin_size` enum(
    'PL7-3',
    'PL4-7 (TU04)',
    'PL4-7'
  ) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bobin_type` enum(
    'Sản xuất',
    'Bù',
    'Điều chỉnh (Do CP)',
    'Điều chỉnh (Ngoại quan: Gel)',
    'Điều chỉnh (Ngoại quan: Dị vật)'
  ) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extrusion_employee` TEXT,
  `products` TEXT,
  `material_lot` TEXT,
  `print_lot` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `length_m` decimal(10, 3) DEFAULT NULL,
  `shift` enum(
    'Ca 1',
    'Ca 2',
    'Ca 3',
    'Hành chính'
  ) COLLATE utf8mb4_unicode_ci DEFAULT 'Ca 1',
  `extrusion_date` DATE DEFAULT NULL,
  `finish_time` datetime DEFAULT NULL,
  `bobin_current_status` enum(
    'Rolled',
    'Busy_Unchecked',
    'Busy_Checked',
    'Pending_Cancellation',
    'Cancelled'
  ) COLLATE utf8mb4_unicode_ci DEFAULT 'Rolled',
  `visual_inspection` TEXT,
  `winding_machine` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `winding_employee` TEXT,
  `winding_note` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flow_test_result` enum('Thành công', 'Thất bại') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci