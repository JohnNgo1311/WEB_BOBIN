<?php
$bobins = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$cancelledCount = $totalRecords;
$pagination = $data['pagination'] ?? [];
$userRole   = $_SESSION['user']['role'] ?? '';
$currentUrl = $_GET['url'] ?? '';
$pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? 0);

$currentSize   = $_GET['bobin_size'] ?? 'all';
$currentType   = $_GET['bobin_type'] ?? 'all';
$currentRack   = $_GET['rack'] ?? 'all';
$currentEmpCode  = trim((string)($_GET['employee_code'] ?? ''));
$currentProdCode = trim((string)($_GET['product_code'] ?? ''));
$currentQcDefect = trim((string)($_GET['qc_defect'] ?? 'all'));
if ($currentQcDefect === '') {
    $currentQcDefect = 'all';
}
$fromDateVal = trim((string)($_GET['from_date'] ?? ''));
$toDateVal   = trim((string)($_GET['to_date'] ?? ''));
$racks       = $data['racks'] ?? [];

$sizeOptions = [
    'all'               => 'Tất cả kích thước',
    'PL7-3'             => 'PL7-3',
    'PL4-7 (TU04.TU06)' => 'PL4-7 (TU04.TU06)',
    'PL4-7 (TU08~)'     => 'PL4-7 (TU08~)'
];

$typeOptions = [
    'all'                              => 'Tất cả loại Bobin',
    'Sản xuất'                         => 'Sản xuất',
    'Bù'                               => 'Bù',
    'Điều chỉnh (Do CP)'               => 'Điều chỉnh (Do CP)',
    'Điều chỉnh (Ngoại quan: Gel)'     => 'Điều chỉnh (Ngoại quan: Gel)',
    'Điều chỉnh (Ngoại quan: Dị vật)' => 'Điều chỉnh (Ngoại quan: Dị vật)',
    'Điều chỉnh (Ngoại quan: Trầy)'    => 'Điều chỉnh (Ngoại quan: Trầy)',
    'Điều chỉnh (Ngoại quan: Biến dạng)' => 'Điều chỉnh (Ngoại quan: Biến dạng)',
    'Điều chỉnh (Ngoại quan: Xước)'    => 'Điều chỉnh (Ngoại quan: Xước)',
    'Điều chỉnh (Ngoại quan: Chữ in)'  => 'Điều chỉnh (Ngoại quan: Chữ in)',
    'Điều chỉnh (Ngoại quan: Vón cục)' => 'Điều chỉnh (Ngoại quan: Vón cục)',
    'Điều chỉnh (Ngoại quan: Màu)'     => 'Điều chỉnh (Ngoại quan: Màu)'
];

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        $query = $_GET ?? [];
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listCancelledBobinView';
        }
        $query = array_merge($query, $overrideParams);
        foreach ($query as $key => $value) {
            if (is_null($value) || $value === '' || $value === 'all') {
                unset($query[$key]);
            }
        }
        return '/WEB_BOBIN/public/index.php?' . http_build_query($query);
    }
}

if (!function_exists('extCheckBadge')) {
    function extCheckBadge($label, $isOk)
    {
        $class = $isOk ? 'badge-ok' : 'badge-ng';
        $text  = $isOk ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}

if (!function_exists('qcDefectBadge')) {
    function qcDefectBadge($label, $hasDefect)
    {
        $isOk = !$hasDefect;
        $class = $isOk ? 'badge-ok' : 'badge-ng';
        $text  = $isOk ? 'OK' : 'NG';
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
    <title><?= __('page_cancelled_list') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listPendingCancellation.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/advancedBobinFilter.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/scanQR.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" href="/WEB_BOBIN/public/assets/images/favicon.png?v=2">
    <link rel="shortcut icon" href="/WEB_BOBIN/public/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/WEB_BOBIN/public/assets/images/favicon.png?v=2">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/qrScannerHelper.js?v=<?= time() ?>"></script>
</head>

<body>

    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-header">
        <h1 data-i18n="page_cancelled_list"><?= __('page_cancelled_list') ?></h1>
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar-modern">
            <?php
            $canExtrusion = AuthHelper::hasPermission('extrusion_create');
            $cancelledBackUrl = $canExtrusion ? '/WEB_BOBIN/public/index.php?url=bobin/index' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $cancelledBackUrl ?>" class="btn-back-modern" title="Quay lại">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listCancelledBobinView">
                    <?php if ($currentSize !== 'all'): ?>
                        <input type="hidden" name="bobin_size" value="<?= htmlspecialchars($currentSize) ?>">
                    <?php endif; ?>
                    <?php if ($currentType !== 'all'): ?>
                        <input type="hidden" name="bobin_type" value="<?= htmlspecialchars($currentType) ?>">
                    <?php endif; ?>
                    <?php if ($currentRack !== 'all'): ?>
                        <input type="hidden" name="rack" value="<?= htmlspecialchars($currentRack) ?>">
                    <?php endif; ?>
                    <?php if ($currentEmpCode !== ''): ?>
                        <input type="hidden" name="employee_code" value="<?= htmlspecialchars($currentEmpCode) ?>">
                    <?php endif; ?>
                    <?php if ($currentProdCode !== ''): ?>
                        <input type="hidden" name="product_code" value="<?= htmlspecialchars($currentProdCode) ?>">
                    <?php endif; ?>
                    <?php if ($currentQcDefect !== 'all'): ?>
                        <input type="hidden" name="qc_defect" value="<?= htmlspecialchars($currentQcDefect) ?>">
                    <?php endif; ?>

                    <div class="control-row" style="flex-wrap: wrap;">
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="keyword" id="searchKeyword"
                                placeholder="<?= __('ph_scan_bobin') ?>" data-i18n-ph="ph_scan_bobin"
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <div style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 7px; padding: 3px 8px;">
                            <input type="date" name="from_date" id="fromDate"
                                value="<?= htmlspecialchars($fromDateVal) ?>"
                                style="border: none; background: transparent; font-size: 12.5px; font-weight: 600; color: #1e293b; outline: none;">
                            <span style="color: #64748b; font-size: 12px;">➝</span>
                            <input type="date" name="to_date" id="toDate"
                                value="<?= htmlspecialchars($toDateVal) ?>"
                                style="border: none; background: transparent; font-size: 12.5px; font-weight: 600; color: #1e293b; outline: none;">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <path d="M3 14h7v7H3z"></path>
                            </svg>
                            <span>Quét QR</span>
                        </button>

                        <button class="btn-modern btn-filter" type="submit" id="btnFilter">
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

        <!-- BỘ LỌC VÀ THỐNG KÊ ĐỒNG BỘ -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <h2>Kết quả lọc danh sách đã hủy: <span><?= number_format($cancelledCount) ?></span> Bobin</h2>
                <a href="<?= buildFilterUrl(['bobin_size' => 'all', 'bobin_type' => 'all', 'rack' => 'all', 'keyword' => null, 'from_date' => null, 'to_date' => null, 'employee_code' => null, 'product_code' => null, 'qc_defect' => 'all']) ?>"
                    class="btn-reset-filter">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

            <div class="filter-grid-row cols-3">
                <div class="filter-col">
                    <div class="filter-label">📏 Kích thước:</div>
                    <select id="sizeFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($sizeOptions as $key => $label): ?>
                            <option value="<?= buildFilterUrl(['bobin_size' => $key]) ?>"
                                <?= $currentSize === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-col">
                    <div class="filter-label">🏷️ Loại Bobin:</div>
                    <select id="typeFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($typeOptions as $key => $label): ?>
                            <option value="<?= buildFilterUrl(['bobin_type' => $key]) ?>"
                                <?= $currentType === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-col">
                    <div class="filter-label">🏢 Vị trí Rack:</div>
                    <select id="rackFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <option value="<?= buildFilterUrl(['rack' => 'all']) ?>"
                            <?= $currentRack === 'all' ? 'selected' : '' ?>>
                            Tất cả các Rack
                        </option>
                        <optgroup label="🏭 Khu vực Rack theo Xưởng / Tầng">
                            <option value="<?= buildFilterUrl(['rack' => 'B021']) ?>"
                                <?= $currentRack === 'B021' ? 'selected' : '' ?>>
                                🏢 B021 => Xưởng 2, tầng 1
                            </option>
                            <option value="<?= buildFilterUrl(['rack' => 'B022']) ?>"
                                <?= $currentRack === 'B022' ? 'selected' : '' ?>>
                                🏢 B022 => Xưởng 2, tầng 2
                            </option>
                            <option value="<?= buildFilterUrl(['rack' => 'B031']) ?>"
                                <?= $currentRack === 'B031' ? 'selected' : '' ?>>
                                🏢 B031 => Xưởng 3, tầng 1
                            </option>
                            <option value="<?= buildFilterUrl(['rack' => 'B032']) ?>"
                                <?= $currentRack === 'B032' ? 'selected' : '' ?>>
                                🏢 B032 => Xưởng 3, tầng 2
                            </option>
                        </optgroup>
                        <?php if (!empty($racks)): ?>
                            <optgroup label="📍 Chi tiết từng Rack cụ thể">
                                <?php foreach ($racks as $r): ?>
                                    <?php $rCode = $r['rack_code'] ?? $r['code'] ?? ''; ?>
                                    <?php if (!empty($rCode)): ?>
                                        <option value="<?= buildFilterUrl(['rack' => $rCode]) ?>"
                                            <?= $currentRack === $rCode ? 'selected' : '' ?>>
                                            📍 <?= htmlspecialchars($rCode) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <?php require ROOT_PATH . '/app/views/components/advancedBobinFilterAndStats.php'; ?>
        </div>

        <!-- LIST CARD -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin đã hủy: <span class="counter-badge" id="cancelledCounterBadge" style="background: #dc2626;"><?= $cancelledCount ?></span> Bobin</h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Không tìm thấy Bobin đã hủy nào khớp với bộ lọc.</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
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
                        $winding_employeeCode = $windingEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $winding_employeeName = $windingEmployee['employee_name'] ?? 'Chưa cập nhật';

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'] ?? '';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';
                        $winding_machine = $item['winding_machine'] ?? 'Chưa cập nhật';
                        $winding_note = $item['winding_note'] ?? 'Chưa cập nhật';
                        $cancelNote = trim((string)($item['note_cancel'] ?? ($item['reason_for_cancellation'] ?? '')));

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

                        <div class="bobin-item status_cancelled" style="border-left: 5px solid #dc2626;">
                            <div class="card-top" style="background: linear-gradient(90deg, #fef2f2 0%, #fff1f2 100%); border-bottom: 1px solid #fecaca;">
                                <div class="key-info">
                                    <span class="key-code" style="color: #991b1b;"><?= $displayKey ?></span>
                                    <span class="id-code" style="background: #fee2e2; color: #b91c1c;">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                </div>
                                <div class="main-info-wrapper">
                                    <div class="main-info"><?= htmlspecialchars($mainInfoText) ?></div>
                                    <button type="button" class="btn-copy" data-copy="<?= htmlspecialchars($mainInfoText) ?>"
                                        onclick="copyToClipboard(this)" title="Copy nội dung">
                                        📋 Copy
                                    </button>
                                </div>
                                <div class="status-badges-wrap">
                                    <div class="status-badge" style="background: #dc2626; color: #ffffff;">
                                        🗑️ ĐÃ HỦY
                                    </div>
                                </div>
                            </div>

                            <!-- INFO GRID: 12 THÔNG SỐ SẢN XUẤT -->
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

                            <!-- QUY TRÌNH 3 CỘT: ĐÙN CHECK | QC CHECK | THÔNG TIN CUỘN -->
                            <div class="pending-inspection-grid">
                                <div class="pipeline-col extrusion-col">
                                    <div class="pipeline-title">🏭 Đùn Check (Tự kiểm tra)</div>
                                    <div class="qc-badges-static">
                                        <?= extCheckBadge('Đ.Kính', $extCheck['diameter'] ?? true) ?>
                                        <?= extCheckBadge('Gel', $extCheck['gel'] ?? true) ?>
                                        <?= extCheckBadge('Dị vật', $extCheck['foreign_object'] ?? true) ?>
                                        <?= extCheckBadge('Màu', $extCheck['color'] ?? true) ?>
                                        <?= extCheckBadge('In', $extCheck['print'] ?? true) ?>
                                        <?= extCheckBadge('Vón cục', $extCheck['clump'] ?? $extCheck['lump'] ?? true) ?>
                                        <?= extCheckBadge('Trầy', $extCheck['scratch'] ?? true) ?>
                                        <?= extCheckBadge('Xước', $extCheck['streak'] ?? $extCheck['scuff'] ?? true) ?>
                                        <?= extCheckBadge('Biến dạng', $extCheck['deformation'] ?? $extCheck['deformed'] ?? true) ?>
                                    </div>
                                </div>

                                <div class="pipeline-col qc-col">
                                    <div class="pipeline-title">
                                        <span>🛡️ QC Check</span>
                                        <span class="pipeline-submeta"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa kiểm') ?></span>
                                    </div>
                                    <div class="qc-badges-static">
                                        <?= qcDefectBadge('Gel', $defects['gel'] ?? false) ?>
                                        <?= qcDefectBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                        <?= qcDefectBadge('Màu', $defects['color_issue'] ?? false) ?>
                                        <?= qcDefectBadge('In', $defects['print_quality'] ?? false) ?>
                                        <?= qcDefectBadge('Vón cục', ($defects['clump'] ?? false) || ($defects['lump'] ?? false) || ($vi['defect_clump'] ?? false)) ?>
                                        <?= qcDefectBadge('Trầy', ($defects['scratch'] ?? false) || ($vi['defect_scratch'] ?? false)) ?>
                                        <?= qcDefectBadge('Xước', ($defects['streak'] ?? false) || ($defects['scuff'] ?? false) || ($vi['defect_streak'] ?? false)) ?>
                                        <?= qcDefectBadge('Biến dạng', ($defects['deformation'] ?? false) || ($defects['deformed'] ?? false) || ($vi['defect_deformation'] ?? false)) ?>
                                    </div>
                                    <div class="qc-note-text">
                                        📝 <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Không có ghi chú' : $defects['note']) ?>
                                    </div>
                                </div>

                                <div class="pipeline-col winding-col">
                                    <div class="pipeline-title">
                                        <span>📍 Thông tin cuộn</span>
                                        <span class="pipeline-submeta"><?= htmlspecialchars($winding_machine) ?> | <?= htmlspecialchars($winding_employeeCode) ?></span>
                                    </div>
                                    <div class="winding-info-mini">
                                        <span>Họ tên NV: <strong><?= htmlspecialchars($winding_employeeName) ?></strong></span>
                                        <span>Thông khí: <strong><?= htmlspecialchars($flow_test_result) ?></strong></span>
                                    </div>
                                    <div class="winding-note-text">
                                        📝 <?= htmlspecialchars(trim($winding_note) === '' ? 'Không có ghi chú' : $winding_note) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- FOOTER: THỜI GIAN HỦY & LỊCH SỬ AUDIT -->
                            <div class="card-footer-simple" style="flex-wrap: wrap;">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 Thời điểm cập nhật hủy: <strong><?= htmlspecialchars($item['updated_time']) ?></strong></div>
                                <?php endif; ?>
                                <?php if ($cancelNote !== ''): ?>
                                    <div style="font-size: 12.5px; color: #b91c1c; font-weight: 700;">
                                        ⚠️ Lý do / Ghi chú hủy: <?= htmlspecialchars($cancelNote) ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                $rawAudit = $item['update_history'] ?? '';
                                $auditList = decodeJsonObject($rawAudit);
                                if (!empty($auditList) && is_array($auditList)):
                                ?>
                                    <div class="audit-history-box" style="margin-top: 8px; padding: 6px 10px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 12px; width: 100%;">
                                        <div style="font-weight: 700; color: #475569; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                            <span>📝</span>
                                            <span data-i18n="audit_trail_title"><?= __('audit_trail_title') ?></span>
                                            <span class="badge" style="background: #e2e8f0; color: #334155; padding: 1px 6px; border-radius: 10px; font-size: 11px;"><?= count($auditList) ?></span>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <?php foreach ($auditList as $audit): ?>
                                                <?php
                                                $stageKey = strtolower(trim($audit['stage'] ?? ''));
                                                $stageName = match ($stageKey) {
                                                    'extrusion' => __('audit_stage_extrusion'),
                                                    'qc'        => __('audit_stage_qc'),
                                                    'winding'   => __('audit_stage_winding'),
                                                    'admin', 'system' => __('audit_stage_admin'),
                                                    default     => htmlspecialchars($audit['stage'] ?? '')
                                                };
                                                $empDisplay = htmlspecialchars(($audit['employee_code'] ?? '') . ' - ' . ($audit['employee_name'] ?? ''));
                                                $timeDisplay = htmlspecialchars($audit['updated_at'] ?? '');
                                                $rawNote = trim($audit['note'] ?? '');
                                                $noteI18nAttr = '';
                                                $noteColor = '#64748b';
                                                if ($rawNote === 'Đưa vào danh sách chờ hủy' || ($audit['action'] ?? '') === 'pending_cancel') {
                                                    $noteDisplay = __('audit_note_pending_cancel');
                                                    $noteI18nAttr = 'data-i18n="audit_note_pending_cancel"';
                                                    $noteColor = '#d97706';
                                                } elseif ($rawNote === 'Xác nhận hủy' || ($audit['action'] ?? '') === 'confirm_cancel') {
                                                    $noteDisplay = __('audit_note_confirm_cancel');
                                                    $noteI18nAttr = 'data-i18n="audit_note_confirm_cancel"';
                                                    $noteColor = '#dc2626';
                                                } elseif ($rawNote === 'Khôi phục Bobin từ danh sách chờ hủy' || ($audit['action'] ?? '') === 'restore') {
                                                    $noteDisplay = __('audit_note_restore');
                                                    $noteI18nAttr = 'data-i18n="audit_note_restore"';
                                                    $noteColor = '#059669';
                                                } else {
                                                    $noteDisplay = htmlspecialchars($rawNote);
                                                }
                                                ?>
                                                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #fff; padding: 3px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                    <div>
                                                        <strong style="color: #2563eb;">[<?= $stageName ?>]</strong>
                                                        <span style="color: #1e293b; font-weight: 600;"><?= $empDisplay ?></span>
                                                        <?php if ($noteDisplay !== ''): ?>
                                                            <span style="color: <?= $noteColor ?>; font-weight: 600; font-style: italic;">(<span <?= $noteI18nAttr ?>><?= $noteDisplay ?></span>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span style="color: #64748b; font-family: monospace; font-size: 11.5px;">🕒 <?= $timeDisplay ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                        for ($p = $start; $p <= $end; $p++): $isActive = ($p === (int)$pagination['currentPage']);
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
                            <?= number_format($totalRecords) ?> Bobin)
                        </span>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/autoSearchBobin.js?v=<?= time() ?>"></script>
</body>

</html>

