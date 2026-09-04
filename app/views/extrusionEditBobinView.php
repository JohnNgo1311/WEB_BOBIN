<?php

$bobins = $data['bobins'] ?? [];

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        //TODO 1. Lấy query hiện tại
        $query = $_GET ?? [];

        //TODO 2. [QUAN TRỌNG] Đảm bảo tham số 'url' luôn đúng định tuyến hiện tại
        //TODO Nếu $_GET chưa có 'url', ta phải ép buộc thêm vào.
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listBobinView_QC';
        }

        //TODO 3. Ghi đè các tham số mới (Dùng array_merge cho gọn và nhanh hơn foreach)
        $query = [...$query, ...$overrideParams];

        //TODO 4. Xử lý trường hợp muốn xóa tham số (nếu truyền vào null)
        foreach ($query as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            }
        }
        // 5. Build lại URL
        return '/WEB_BOBIN/public/index.php?' . http_build_query($query);
    }
}

if (!function_exists('viBadge')) {
    function viBadge($label, $goodDefect)
    {
        $class = $goodDefect ? 'badge-ok' : 'badge-ng';
        $text = $goodDefect ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Danh sách Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusionEditBobin.css?v=<?= time() ?>">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>

</head>
<link rel="icon" href="data:,">

</head>


<body>
    <div class="menu-bar">
        <div class="menu-left">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index">Nhóm đùn</a>
            <a href="#">QC</a>
            <a href="#">Cuộn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView">Điều chỉnh thông tin Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView">Danh sách chờ hủy</a>

        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    </div>
    </div>

    <h1>Nhóm QC - Kiểm tra chất lượng Bobin</h1>

    <div class="container">
        <!-- CONTROL BAR -->
        <div id="qr-reader"></div>

        <div class="control-bar">
            <!-- Nút nhấn Filter -->
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/extrusionEditBobinView">

                <div class="qr-search-group">
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5
                        6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="Nhập hoặc quét mã Bobin..."
                            value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>

                    <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                        Quét QR
                    </button>

                    <button type="submit" class="btn-filter" id="btnFilter">Lọc</button>
                </div>
            </form>


        </div>

        <!-- LIST -->
        <div class="list-card">
            <div class="header-row"
                style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <h2 style="margin:0;">Số lượng Bobin: <?= count($bobins) ?></h2>
                <?php $currentStatus = $_GET['status'] ?? 'all'; ?>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Danh sách trống</div>
            <?php else: ?>

                <div class="bobin-list">

                    <?php foreach ($bobins as $item): ?>

                        <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        // Vừa loại bỏ gạch dưới, vừa chuyển về chữ thường để so sánh dễ hơn
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled' => 'status_rolled',
                            'busy_unchecked' => 'status_busy_unchecked',
                            default => 'status_unknown'
                        };
                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $productionOrderCode = '';
                        $productCode = '';
                        $extrusion_employeeName = '';
                        $extrusion_employeeCode = '';
                        $materialLot = '';


                        $vi = [];
                        $defects = [];

                        if (is_string($viRaw) && trim($viRaw) !== '') {
                            $decoded = json_decode($viRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $vi = $decoded;
                                $defects = $vi['defects'] ?? [];
                            }
                        }

                        if (is_string($productsRaw) && trim($productsRaw) !== '') {
                            $decoded = json_decode($productsRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $productionOrderCode = $decoded['production_order_code'] ?? '';
                                $productCode = $decoded['product_code'] ?? '';
                            }
                        }
                        if (is_string($extrusion_employeeRaw) && trim($extrusion_employeeRaw) !== '') {
                            $decoded = json_decode($extrusion_employeeRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $extrusion_employeeCode = $decoded['employee_code'] ?? '';
                                $extrusion_employeeName = $decoded['employee_name'] ?? '';
                            }
                        }
                        if (is_string($materialLotRaw) && trim($materialLotRaw) !== '') {
                            $decoded = json_decode($materialLotRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $materialLot = $decoded['lot'] ?? '';
                            }
                        }
                        ?>
                        <div class="bobin-item <?= $statusClass ?>">
                            <!-- HEADER -->
                            <div class="card-top">
                                <div class="key-info">
                                    <?php
                                    $rawKey = $item['bobin_key_code'] ?? '';
                                    $displayKey = htmlspecialchars($rawKey);
                                    if (is_string($rawKey) && strpos($rawKey, '_') !== false) {
                                        $parts = explode('_', $rawKey);
                                        if (count($parts) >= 7) {
                                            $code = array_shift($parts);
                                            $date = implode('/', array_slice($parts, 0, 3));
                                            $time = implode(':', array_slice($parts, 3, 3));
                                            $displayKey = htmlspecialchars("{$code} - {$date} - {$time}");
                                        } elseif (count($parts) >= 2) {
                                            $code = array_shift($parts);
                                            $rest = implode('/', $parts);
                                            $displayKey = htmlspecialchars("{$code} - {$rest}");
                                        }
                                    }
                                    ?>
                                    <span class="key-code"><?= $displayKey ?></span>
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                </div>
                                <div class="status-badge">
                                    <?php

                                    $displayStatus = match ($rawStatus) {
                                        'Rolled' => 'Đã cuộn',
                                        'Busy_Unchecked'  => 'Chưa kiểm tra QC',
                                        default => $rawStatus, // Giữ nguyên nếu không khớp
                                    };
                                    echo htmlspecialchars($displayStatus); ?>
                                </div>

                            </div>
                            <!-- INFO GRID -->
                            <div class="info-grid">
                                <!-- Product Code -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Mã sản phẩm</label>
                                    <input id="product_code" class="input-field" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($productCode ?? '') ?>">
                                    <div id="product_suggestions" class="suggestion-box"></div>
                                </div>
                                <!-- Production Order -->
                                <!-- SAU NÀY SỬ DỤNG <div class="field-item suggestion-wrapper">
                            <label>Mã chỉ thị sản xuất</label>
                            <input id="production_order_code" class="edit-input" type="text"
                                value="<?= htmlspecialchars($productionOrderCode ?? '') ?>" readonly>
                        </div> -->

                                <!-- Employee Code -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Mã nhân viên</label>
                                    <input id="extrusion_employee_code" class="input-field" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($extrusion_employeeCode ?? '') ?>">
                                    <div id="employee_suggestions" class="suggestion-box"></div>
                                </div>

                                <!-- Employee Name -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Họ tên nhân viên</label>
                                    <input id="extrusion_employee_name" class="edit-input" type="text"
                                        value="<?= htmlspecialchars($extrusion_employeeName ?? '') ?>" readonly>
                                </div>

                                <div class="field-item suggestion-wrapper">
                                    <label>Loại Bobin:</label>
                                    <select id="bobin_type" name="bobin_type" required>
                                        <option value="Sản xuất"
                                            <?= ($item['bobin_type'] ?? '') === 'Sản xuất' ? 'selected' : '' ?>>Sản xuất
                                        </option>
                                        <option value="Bù" <?= ($item['bobin_type'] ?? '') === 'Bù' ? 'selected' : '' ?>>Bù
                                        </option>
                                        <option value="Điều chỉnh (Do CP)"
                                            <?= ($item['bobin_type'] ?? '') === 'Điều chỉnh (Do CP)' ? 'selected' : '' ?>>Điều
                                            chỉnh (Do CP)
                                        </option>
                                        <option value="Điều chỉnh (Ngoại quan: Gel)"
                                            <?= ($item['bobin_type'] ?? '') === 'Điều chỉnh (Ngoại quan: Gel)' ? 'selected' : '' ?>>
                                            Điều chỉnh (Ngoại quan: Gel)
                                        </option>
                                        <option value="Điều chỉnh (Ngoại quan: Dị vật)"
                                            <?= ($item['bobin_type'] ?? '') === 'Điều chỉnh (Ngoại quan: Dị vật)' ? 'selected' : '' ?>>
                                            Điều chỉnh (Ngoại quan: Dị vật)
                                        </option>
                                    </select>
                                </div>
                                <!-- Material Lot -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Lot vật liệu</label>
                                    <input id="material_lot" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($materialLot ?? '') ?>">
                                    <div id="material_lot_suggestions" class="suggestion-box"></div>
                                </div>
                                <div class="field-item suggestion-wrapper">
                                    <label>Số máy đùn:</label>
                                    <div class="suggestions">
                                        <input type="text" id="extrusion_machine" placeholder="Nhập số máy..." required
                                            autocomplete="off">
                                        <div id="machine_suggestions" class="suggestion-box"></div>
                                    </div>
                                </div>

                                <div class="field-item suggestion-wrapper">
                                    <label>Vật liệu:</label>
                                    <div class="suggestions">
                                        <input type="text" id="material" placeholder="Nhập loại vật liệu sử dụng..." required
                                            autocomplete="off">
                                        <div id="material_suggestions" class="suggestion-box"></div>
                                    </div>
                                </div>
                                <div class="field-item suggestion-wrapper">
                                    <label>Số lần nghiền:</label>
                                    <input type="number" id="grinding_time" list="list_grinding_time"
                                        placeholder="Nhập 0, 1 hoặc 2" required min="0" max="2" step="1"
                                        oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)">
                                </div>

                                <!-- Print Lot -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Lot in</label>
                                    <input id="print_lot" class="edit-input" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($item['print_lot'] ?? '') ?>" readonly>
                                    <div id="print_lot_suggestions" class="suggestion-box"></div>
                                </div>


                                <!-- Length -->
                                <div class="field-item highlight-box">
                                    <label>Chiều dài (m)</label>
                                    <input id="length_m" class="input-field" type="number"
                                        value="<?= htmlspecialchars($item['length_m'] ?? 0) ?>">
                                </div>

                                <div class="field-item suggestion-wrapper">
                                    <label>Ca sản xuất:</label>
                                    <select id="shift" name="shift" required>
                                        <option value="Ca 1" <?= ($item['shift'] ?? '') === 'Ca 1' ? 'selected' : '' ?>>Ca 1
                                        </option>
                                        <option value="Ca 2" <?= ($item['shift'] ?? '') === 'Ca 2' ? 'selected' : '' ?>>Ca 2
                                        </option>
                                        <option value="Ca 3" <?= ($item['shift'] ?? '') === 'Ca 3' ? 'selected' : '' ?>>Ca 3
                                        </option>
                                        <option value="Hành chính"
                                            <?= ($item['shift'] ?? '') === 'Hành chính' ? 'selected' : '' ?>>Hành chính
                                        </option>
                                    </select>
                                </div>
                                <!-- Finish Time -->
                                <div class="field-item suggestion-wrapper">
                                    <label>Thời điểm hoàn thành cuộn</label>
                                    <input id="finish_time" name="finish_time" class="input-field" type="text" value="">
                                </div>

                            </div>
                            <div class="card-footer-simple">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                        <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>
                                <div class="button-group">
                                    <button type="button" class="btn-delete"
                                        onclick="handleDelete(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                        Hủy Bobin
                                    </button>
                                    <button type="button" class="btn-confirm"
                                        onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                        Xác nhận đã kiểm tra
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Định nghĩa đường dẫn gốc từ PHP
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>

</body>

</html>