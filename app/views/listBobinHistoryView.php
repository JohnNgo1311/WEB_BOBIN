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
            $query['url'] = 'bobin/listBobinHistoryView';
        }

        //TODO 3. Ghi đè các tham số mới (Dùng array_merge cho gọn và nhanh hơn foreach)
        $query = array_merge($query, $overrideParams);

        //TODO 4. Xử lý trường hợp muốn xóa tham số (nếu truyền vào null)
        foreach ($query as $key => $value) {
            if (is_null($value) || $value === '') {
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
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinHistory.css">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>
<h1>Lịch sử Bobin</h1>

<body>
    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar">

            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="back-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/listBobinHistoryView">

                <div class="qr-search-group">
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5
                        6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                        </svg>
                        <input type="text" id="searchKeyword" name="keyword" placeholder="Nhập hoặc quét mã Bobin..."
                            value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>

                    <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                        Quét QR
                    </button>
                </div>

                <?php
                // Lấy ngày hiện tại và 7 ngày trước định dạng Y-m-d
                $defaultToDate = date('Y-m-d');
                $defaultFromDate = date('Y-m-d', strtotime('-7 days'));
                // Kiểm tra nếu biến GET không rỗng thì dùng biến GET, nếu rỗng thì dùng giá trị mặc định
                $fromDateVal = !empty($_GET['from_date']) ? $_GET['from_date'] : $defaultFromDate;
                $toDateVal = !empty($_GET['to_date']) ? $_GET['to_date'] : $defaultToDate;
                ?>
                <div class="date-group">
                    <input type="date" name="from_date" id="fromDate" value="<?= htmlspecialchars($fromDateVal) ?>">
                    <span class="arrow">➝</span>
                    <input type="date" name="to_date" id="toDate" value="<?= htmlspecialchars($toDateVal) ?>">
                </div>

                <button type="submit" class="btn-filter" id="btnFilter">Lọc</button>
            </form>


            <!-- Nút nhấn Export Excel -->
            <?php
            // Logic Export Excel URL
            $exportParams = $_GET;
            $exportParams['url'] = 'bobin/exportHistoryExcel';
            $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
            ?>
            <a href="<?= $exportUrl ?>" class="btn-excel">
                Xuất file Excel
            </a>
        </div>
        <!-- LIST -->
        <div class="list-card">
            <div class="header-row">
                <h2 style="margin:0;">Số lượng Bobin: <?= count($bobins) ?></h2>
                <?php $currentStatus = $_GET['status'] ?? 'all'; ?>
                <div class="status-filter-bar">
                    <a href="<?= buildFilterUrl(['status' => 'all']) ?>"
                        class="status-btn all <?= $currentStatus === 'all' ? 'active' : '' ?>">
                        📦 Tất cả
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Rolled']) ?>"
                        class="status-btn rolled <?= $currentStatus === 'Rolled' ? 'active' : '' ?>">
                        ✅ Đã cuộn
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked']) ?>"
                        class="status-btn unchecked <?= $currentStatus === 'Busy_Unchecked' ? 'active' : '' ?>">
                        ⏳ Chưa QC
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked']) ?>"
                        class="status-btn checked <?= $currentStatus === 'Busy_Checked' ? 'active' : '' ?>">
                        🛡️ Đã QC

                        <a href="<?= buildFilterUrl(['status' => 'Line']) ?>"
                            class="status-btn line <?= $currentStatus === 'Line' ? 'active' : '' ?>">
                            📍 Tồn line
                        </a>
                    </a>
                    <a href="<?= buildFilterUrl(['status' => 'Pending_Cancellation']) ?>"
                        class="status-btn pending_cancellation <?= $currentStatus === 'Pending_Cancellation' ? 'active' : '' ?>">
                        ⌛ Chờ hủy
                    </a>
                    <a href="<?= buildFilterUrl(['status' => 'Cancelled']) ?>"
                        class="status-btn cancelled <?= $currentStatus === 'Cancelled' ? 'active' : '' ?>">
                        🗑️ Đã hủy
                    </a>
                </div>
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
                            'busy_checked' => 'status_busy_checked',
                            'pending_cancellation' => 'status_pending_cancellation',
                            'cancelled' => 'status_cancelled',
                            default => 'status_unknown'
                        };
                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $winding_employeeRaw = $item['winding_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'];
                        $productionOrderCode = 'Chưa cập nhật';
                        $productCode = 'Chưa cập nhật';
                        $extrusion_employeeName = 'Chưa cập nhật';
                        $extrusion_employeeCode = 'Chưa cập nhật';
                        $winding_employeeName = 'Chưa cập nhật';
                        $winding_employeeCode = 'Chưa cập nhật';
                        $materialLot = 'Chưa cập nhật';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';


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
                                $productionOrderCode = $decoded['production_order_code'] ?? 'Chưa cập nhật';
                                $productCode = $decoded['product_code'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($extrusion_employeeRaw) && trim($extrusion_employeeRaw) !== '') {
                            $decoded = json_decode($extrusion_employeeRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $extrusion_employeeCode = $decoded['employee_code'] ?? 'Chưa cập nhật';
                                $extrusion_employeeName = $decoded['employee_name'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($materialLotRaw) && trim($materialLotRaw) !== '') {
                            $decoded = json_decode($materialLotRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $materialLot = $decoded['lot'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($winding_employeeRaw) && trim($winding_employeeRaw) !== '') {
                            $decoded = json_decode($winding_employeeRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $winding_employeeCode = $decoded['employee_code'] ?? 'Chưa cập nhật';
                                $winding_employeeName = $decoded['employee_name'] ?? 'Chưa cập nhật';
                            }
                        }
                        $formatted_time = date('dmY$His', strtotime($finish_time));
                        $fields = [$productCode, $materialLot, $printLot, $formatted_time];
                        $isMissingData = false;
                        foreach ($fields as $field) {
                            // Ép kiểu về chuỗi và loại bỏ khoảng trắng thừa 
                            $checkValue = trim((string)$field);

                            // Kiểm tra nếu rỗng hoặc bằng 'Chưa cập nhật'
                            if ($checkValue === '' || $checkValue === 'Chưa cập nhật') {
                                $isMissingData = true;
                                break; // Dừng kiểm tra ngay khi phát hiện có 1 trường không hợp lệ
                            }
                        }
                        // Gán giá trị cho $mainInfoText dựa trên kết quả kiểm tra
                        $isMissingData ?
                            $mainInfoText = 'Chưa cập nhật đầy đủ thông tin cần thiết'

                            : $mainInfoText = $productCode . '$' . $materialLot . '$' . $printLot . '$' . $formatted_time;

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
                                            $displayKey = htmlspecialchars($code . ' - ' . $date . ' - ' . $time);
                                        } elseif (count($parts) >= 2) {
                                            $code = array_shift($parts);
                                            $rest = implode('/', $parts);
                                            $displayKey = htmlspecialchars($code . ' - ' . $rest);
                                        }
                                    }
                                    ?>
                                    <span class="key-code"><?= $displayKey ?></span>
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                </div>
                                <div class="main-info-wrapper">
                                    <div class="main-info">
                                        <?= htmlspecialchars($mainInfoText) ?>
                                    </div>
                                    <button type="button" class="btn-copy" data-copy="<?= htmlspecialchars($mainInfoText) ?>"
                                        onclick="copyToClipboard(this)" title="Copy nội dung">
                                        📋 Copy
                                    </button>
                                </div>
                                <div class="status-badge">
                                    <?php

                                    $displayStatus = match ($rawStatus) {
                                        'Rolled' => 'Đã cuộn',
                                        'Busy_Unchecked'  => 'Đang đợi QC kiểm tra',
                                        'Busy_Checked'  => 'Đã kiểm tra QC',
                                        'Pending_Cancellation' => 'Chờ hủy',
                                        'Cancelled' => 'Đã hủy',
                                        default => $rawStatus, // Giữ nguyên nếu không khớp
                                    };
                                    echo htmlspecialchars($displayStatus); ?>
                                </div>

                            </div>

                            <!-- INFO GRID -->
                            <div class="info-grid">

                                <!-- SAU NÀY SỬ DỤNG <div class="field-item">
                            <label>Mã chỉ thị sản xuất</label>
                            <div class="val-sub">
                                <?= htmlspecialchars($productionOrderCode ?? 'Chưa cập nhật') ?></div>
                        </div> -->

                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub"><?= htmlspecialchars($productCode ?? 'Chưa cập nhật') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Mã nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode ?? 'Chưa cập nhật') ?>
                                    </div>
                                </div>

                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName ?? 'Chưa cập nhật') ?>
                                    </div>

                                </div>

                                <div class="field-item">
                                    <label>Ca làm việc</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['shift'] ?? 'Chưa cập nhật') ?></div>
                                </div>


                                <div class="field-item">
                                    <label>Kích thước Bobin</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['bobin_size'] ?? 'Chưa cập nhật') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Loại Bobin</label>
                                    <div class="val-sub" data-type="<?= htmlspecialchars($item['bobin_type'] ?? '') ?>">
                                        <?= htmlspecialchars($item['bobin_type'] ?? 'Chưa cập nhật') ?>
                                    </div>
                                </div>

                                <div class="field-item">
                                    <label>Lot vật liệu</label>
                                    <div class="val-sub"><?= htmlspecialchars($materialLot ?? 'Chưa cập nhật') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['print_lot'] ?? 'Chưa cập nhật') ?></div>
                                </div>

                                <div class="field-item highlight-box">
                                    <label>Chiều dài (m)</label>
                                    <div class="val-highlight">
                                        <?= number_format($item['length_m'], 0, decimal_separator: ".", thousands_separator: ",") ?>
                                        m
                                    </div>
                                </div>

                                <div class="field-item">
                                    <label>Ngày đùn</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['extrusion_date']) ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Thời điểm hoàn thành cuộn</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['finish_time']) ?></div>
                                </div>

                            </div>

                            <div class="divider"></div>

                            <!-- QC -->
                            <div class="qc-section">
                                <div class="qc-title">🛡️ QC Check</div>

                                <div class="qc-header">
                                    <div class="qc-info-row">
                                        <div class="qc-meta-item">
                                            <label class="val-text">Mã số nhân viên:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?></span>
                                        </div>

                                        <div class="qc-meta-item">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa cập nhật') ?></span>
                                        </div>

                                        <div class="qc-meta-item">
                                            <label class="val-text">Thời điểm kiểm tra:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($vi['inspection_time'] ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="qc-badges">
                                    <?= viBadge('Gel', $defects['gel'] ?? false) ?>
                                    <?= viBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                    <?= viBadge('Màu', $defects['color_issue'] ?? false) ?>
                                    <?= viBadge('In', $defects['print_quality'] ?? false) ?>
                                </div>

                                <div class="qc-note">📝

                                    <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Chưa cập nhật' : $defects['note']) ?>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <div class="winding-section">
                                <div class="winding-title">📍 Thông tin cuộn</div>
                                <div class="winding-header">
                                    <div class="winding-info-row">
                                        <div class="winding-input">
                                            <label class="val-text">Máy cuộn:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($item['winding_machine'] ?? 'Chưa cập nhật') ?></span>
                                        </div>

                                        <div class="winding-input">
                                            <label class="val-text">Mã nhân viên:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($winding_employeeCode ?? 'Chưa cập nhật') ?></span>
                                        </div>

                                        <div class="winding-input">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($winding_employeeName ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                        <div class="winding-input">
                                            <label class="val-text">Kết quả thông khí:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($flow_test_result ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                    </div>
                                    <div class="winding-note">📝
                                        <?= htmlspecialchars(trim($item['winding_note'] ?? '') === '' ? 'Chưa cập nhật' : $item['winding_note']) ?>
                                    </div>
                                </div>

                                <div class="card-footer-simple">
                                    <?php if (!empty($item['updated_time'])): ?>
                                        <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                            <?= htmlspecialchars($item['updated_time']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=<?= time() ?>"></script>
</body>

</html>