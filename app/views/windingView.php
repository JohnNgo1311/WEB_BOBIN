<?php
$bobins = $data['bobins'] ?? [];
$pendingCount = (int)($data['pendingCount'] ?? 0);
$pagination = $data['pagination'] ?? [
    'currentPage'  => 1,
    'totalPages'   => 1,
    'totalRecords' => count($bobins),
    'limit'        => 50
];

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        $query = $_GET ?? [];
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/windingView';
        }
        $query = array_merge($query, $overrideParams);
        foreach ($query as $key => $value) {
            if (is_null($value) || $value === '') {
                unset($query[$key]);
            }
        }
        return '/WEB_BOBIN/public/index.php?' . http_build_query($query);
    }
}

if (!function_exists('viBadge')) {
    function viBadge($label, $goodDefect)
    {
        $class = $goodDefect ? 'badge-ok' : 'badge-ng';
        $text  = $goodDefect ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}

if (!function_exists('decodeJsonObject')) {
    function decodeJsonObject($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Nhóm Cuộn - Xác nhận hoàn thành Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_Winding.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- MENU BAR ĐỒNG BỘ TRANG QC -->
    <div class="menu-bar">
        <div class="menu-left">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView" class="active-nav">Nhóm cuộn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView" class="menu-pending-link">
                Danh sách chờ hủy
                <?php if ($pendingCount > 0): ?>
                <span class="badge-pending-count"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>

    <!-- TIÊU ĐỀ TRANG & SUBTITLE -->
    <div class="page-header">
        <h1>Nhóm cuộn - Xác nhận hoàn thành Bobin</h1>
        <!-- <p class="page-subtitle">Kiểm tra thông khí, ghi nhận thông tin máy cuộn và xác nhận hoàn tất chu kỳ sản xuất
        </p> -->
    </div>

    <div class="container">
        <!-- Khung camera QR -->
        <div id="qr-reader"></div>

        <!-- CONTROL BAR TÌM KIẾM -->
        <div class="control-bar-modern">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="btn-back-modern" title="Quay lại">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/windingView">

                    <div class="control-row">
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="keyword" id="searchKeyword"
                                placeholder="Nhập hoặc quét mã Bobin..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <path d="M3 14h7v7H3z"></path>
                            </svg>
                            Quét QR
                        </button>

                        <button class="btn-modern btn-filter" type="submit">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Lọc
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DANH SÁCH CARD BOBIN -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin tại line Cuộn: <span
                        class="counter-badge"><?= number_format($pagination['totalRecords'] ?? count($bobins)) ?></span>
                    Bobin</h2>
            </div>

            <?php if (empty($bobins)): ?>
            <div class="empty-state">Hiện tại không có Bobin nào cần xử lý cuộn.</div>
            <?php else: ?>
            <div class="bobin-list">
                <?php foreach ($bobins as $item): ?>
                <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled'       => 'status_rolled',
                            'busy_checked' => 'status_busy_checked',
                            default        => 'status_unknown'
                        };

                        $displayStatus = match ($rawStatus) {
                            'Rolled'       => 'Đã cuộn',
                            'Busy_Checked' => 'Đã kiểm tra QC (Chờ cuộn)',
                            default        => $rawStatus
                        };

                        $products = decodeJsonObject($item['products'] ?? '');
                        $productCode = $products['product_code'] ?? 'Chưa cập nhật';
                        $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                        $extrusion_employeeCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extrusion_employeeName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';
                        $materialLotData = decodeJsonObject($item['material_lot'] ?? '');
                        $materialLot = $materialLotData['lot'] ?? 'Chưa cập nhật';
                        $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                        $rackData = decodeJsonObject($item['rack'] ?? '');
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';
                        $isRackEmpty = ($rackCode === 'Chưa cập nhật');

                        $vi = decodeJsonObject($item['visual_inspection'] ?? '');
                        $defects = is_array($vi['defects'] ?? null) ? $vi['defects'] : [];

                        $windingEmployee = decodeJsonObject($item['winding_employee'] ?? '');
                        $winding_employeeCode = ($rawStatus === 'Rolled') ? ($windingEmployee['employee_code'] ?? '') : '';
                        $winding_employeeName = ($rawStatus === 'Rolled') ? ($windingEmployee['employee_name'] ?? '') : '';
                        $winding_machine = ($rawStatus === 'Rolled') ? ($item['winding_machine'] ?? '') : '';
                        $winding_note = ($rawStatus === 'Rolled') ? ($item['winding_note'] ?? '') : '';

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'] ?? '';
                        $formatted_time = !empty($finish_time) ? date('dmY$His', strtotime($finish_time)) : '';

                        $isMissingData = ($productCode === 'Chưa cập nhật' || $materialLot === 'Chưa cập nhật');
                        $mainInfoText = $isMissingData
                            ? 'Chưa cập nhật đầy đủ thông tin cần thiết'
                            : "{$productCode}\${$materialLot}\${$printLot}\${$formatted_time}";

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

                <div class="bobin-item <?= $statusClass ?>">
                    <!-- CARD TOP -->
                    <div class="card-top">
                        <div class="key-info">
                            <span class="key-code"><?= $displayKey ?></span>
                            <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                        </div>
                        <div class="main-info-wrapper">
                            <div class="main-info"><?= htmlspecialchars($mainInfoText) ?></div>
                            <button type="button" class="btn-copy" data-copy="<?= htmlspecialchars($mainInfoText) ?>"
                                onclick="copyToClipboard(this)" title="Copy nội dung">
                                📋 Copy
                            </button>
                        </div>
                        <div class="status-badge">
                            <?= htmlspecialchars($displayStatus) ?>
                        </div>
                    </div>

                    <!-- INFO GRID (12 THÔNG SỐ SẢN XUẤT) -->
                    <div class="info-grid">
                        <div class="field-item">
                            <label>Mã sản phẩm</label>
                            <div class="val-sub font-bold-blue"><?= htmlspecialchars($productCode) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Mã nhân viên đùn</label>
                            <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Họ tên nhân viên đùn</label>
                            <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Vị trí RACK</label>
                            <div class="val-sub">
                                <span class="val-rack <?= $isRackEmpty ? 'val-empty' : '' ?>">
                                    <?= htmlspecialchars($rackCode) ?>
                                </span>
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
                            <div class="val-sub"><?= htmlspecialchars($materialLot) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Lot in</label>
                            <div class="val-sub highlight-printlot-text"><?= htmlspecialchars($printLot) ?></div>
                        </div>
                        <div class="field-item highlight-box">
                            <label>Chiều dài (m)</label>
                            <div class="val-highlight">
                                <?= number_format($item['length_m'] ?? 0, 0, ".", ",") ?> m
                            </div>
                        </div>
                        <div class="field-item">
                            <label>Ngày đùn</label>
                            <div class="val-sub"><?= htmlspecialchars($item['extrusion_date'] ?? '') ?></div>
                        </div>
                        <div class="field-item">
                            <label>Thời điểm hoàn thành cuộn</label>
                            <div class="val-sub"><?= htmlspecialchars($item['finish_time'] ?? '') ?></div>
                        </div>
                    </div>

                    <!-- QUY TRÌNH 3 CỘT: ĐÙN CHECK | QC CHECK | THAO TÁC CUỘN -->
                    <div class="winding-inspection-grid">
                        <!-- CỘT 1: ĐÙN CHECK -->
                        <div class="pipeline-col extrusion-col">
                            <div class="pipeline-title">🏭 Đùn Check (Tự kiểm tra)</div>
                            <div class="qc-badges-static">
                                <?= viBadge('Đ.Kính', $extCheck['diameter'] ?? true) ?>
                                <?= viBadge('Gel', $extCheck['gel'] ?? true) ?>
                                <?= viBadge('Dị vật', $extCheck['foreign_object'] ?? true) ?>
                                <?= viBadge('Màu', $extCheck['color'] ?? true) ?>
                                <?= viBadge('In', $extCheck['print'] ?? true) ?>
                            </div>
                        </div>

                        <!-- CỘT 2: QC CHECK (ĐÃ KIỂM DUYỆT) -->
                        <div class="pipeline-col qc-col">
                            <div class="pipeline-title">
                                <span>🛡️ QC Check (Đã duyệt)</span>
                                <span
                                    class="pipeline-submeta"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?></span>
                            </div>
                            <div class="qc-badges-static">
                                <?= viBadge('Gel', $defects['gel'] ?? false) ?>
                                <?= viBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                <?= viBadge('Màu', $defects['color_issue'] ?? false) ?>
                                <?= viBadge('In', $defects['print_quality'] ?? false) ?>
                            </div>
                            <div class="qc-note-text">
                                📝
                                <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Không có ghi chú' : $defects['note']) ?>
                            </div>
                        </div>

                        <!-- CỘT 3: THAO TÁC NHÓM CUỘN -->
                        <div class="pipeline-col winding-action-col">
                            <div class="pipeline-title">📍 Thao tác Cuộn</div>

                            <div class="winding-inputs-row">
                                <div class="winding-field-group">
                                    <label>Máy cuộn: <span class="required">*</span></label>
                                    <div class="suggestion-wrapper">
                                        <input type="text" class="winding-input winding-machine-name"
                                            value="<?= htmlspecialchars($winding_machine) ?>"
                                            placeholder="Chọn máy cuộn..." autocomplete="off">
                                        <div class="suggestion-box"></div>
                                    </div>
                                </div>

                                <div class="winding-field-group">
                                    <label>Mã NV cuộn: <span class="required">*</span></label>
                                    <div class="suggestion-wrapper">
                                        <input type="text" class="winding-input winding-employee-code"
                                            value="<?= htmlspecialchars($winding_employeeCode) ?>"
                                            placeholder="Nhập mã NV..." autocomplete="off">
                                        <div class="suggestion-box"></div>
                                    </div>
                                </div>

                                <div class="winding-field-group">
                                    <label>Họ tên NV cuộn:</label>
                                    <input type="text" class="winding-input winding-employee-name"
                                        value="<?= htmlspecialchars($winding_employeeName) ?>"
                                        placeholder="Tự động cập nhật" readonly>
                                </div>
                            </div>

                            <div class="winding-note-row">
                                <label>Ghi chú cuộn:</label>
                                <input type="text" class="winding-input winding-note-field"
                                    value="<?= htmlspecialchars($winding_note) ?>"
                                    placeholder="Nhập ghi chú cuộn (nếu có)...">
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER: NÚT BẤM HÀNH ĐỘNG -->
                    <div class="card-footer-simple">
                        <?php if (!empty($item['updated_time'])): ?>
                        <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                            <?= htmlspecialchars($item['updated_time']) ?></div>
                        <?php endif; ?>
                        <div class="button-group">
                            <button type="button" class="btn-cancel"
                                onclick="handleCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                🗑️ Hủy Bobin
                            </button>

                            <button type="button" class="btn-confirm"
                                onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>', '<?= htmlspecialchars($mainInfoText) ?>')">
                                💾 <?= ($rawStatus === 'Rolled') ? 'Cập nhật cuộn' : 'Xác nhận hoàn thành' ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PHÂN TRANG -->
            <?php if (isset($pagination['totalPages']) && $pagination['totalPages'] > 1): ?>
            <div class="pagination-wrapper">
                <?php if ($pagination['currentPage'] > 1): ?>
                <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="page-btn">
                    ‹ Trước
                </a>
                <?php endif; ?>

                <?php
                        $start = max(1, $pagination['currentPage'] - 2);
                        $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                        for ($p = $start; $p <= $end; $p++):
                            $isActive = ($p === (int)$pagination['currentPage']);
                        ?>
                <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="page-btn <?= $isActive ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="page-btn">
                    Sau ›
                </a>
                <?php endif; ?>

                <span class="page-info">
                    Trang <?= $pagination['currentPage'] ?> / <?= $pagination['totalPages'] ?> (Tổng
                    <?= number_format($pagination['totalRecords']) ?> Bobin)
                </span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/copyText.js?v=1"></script>
    <script src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=1"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/suggestion.js?v=<?= time() ?>"></script>
</body>

</html>