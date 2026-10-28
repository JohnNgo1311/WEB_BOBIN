<?php
$bobins = $data['bobins'] ?? [];
$userRole   = $_SESSION['user']['role'] ?? '';
$currentUrl = $_GET['url'] ?? '';
$pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? 0);
// 1. TỔNG HỢP SỐ LIỆU TỪ DATABASE
$defaultStatus = [
    'Extruded'             => 0,
    'Rolled'               => 0,
    'Busy_Unchecked'       => 0,
    'Busy_Checked'         => 0,
    'Pending_Cancellation' => 0,
    'Cancelled'            => 0
];
$statusCounts = array_merge($defaultStatus, $data['statusCounts'] ?? []);
$defaultQcStats = [
    'total_bobins'    => 0,
    'inspected_total' => 0,
    'uninspected'     => 0,
    'ok_all'          => 0,
    'ng_any'          => 0,
    'gel'             => 0,
    'foreign_object'  => 0,
    'color_issue'     => 0,
    'print_quality'   => 0,
    'clump'           => 0,
    'scratch'         => 0,
    'streak'          => 0,
    'deformation'     => 0,
];
$qcDefectStats = array_merge($defaultQcStats, $data['qcDefectStats'] ?? []);

$statusOptions = [
    'all'                            => '📦 Tất cả trạng thái',
    'Extruded'                       => '⚙️ ĐÃ ĐÙN',
    'Busy_Unchecked'                 => '⏳ CHƯA KIỂM TRA QC',
    'Busy_Checked'                   => '🛡️ ĐÃ KIỂM TRA QC (Tất cả)',
    'Busy_Checked_InPeriod'          => '🛡️ ĐÃ KT QC (Đùn trong khoảng)',
    'Busy_Checked_Before'            => '🛡️ ĐÃ KT QC (Đùn trước đó)',
    'Rolled'                         => '✅ ĐÃ CUỘN',
    'Pending_Cancellation'          => '⚠️ CHỜ HỦY (Tất cả)',
    'Pending_Cancellation_InPeriod' => '⚠️ CHỜ HỦY (Đùn trong khoảng)',
    'Pending_Cancellation_Before'   => '⚠️ CHỜ HỦY (Đùn trước đó)',
    'Cancelled'                      => '🗑️ ĐÃ HỦY (Tất cả)',
    'Cancelled_InPeriod'             => '🗑️ ĐÃ HỦY (Đùn trong khoảng)',
    'Cancelled_Before'               => '🗑️ ĐÃ HỦY (Đùn trước đó)'
];

$currentStatus = $_GET['status'] ?? 'all';
$currentSize   = $_GET['bobin_size'] ?? 'all';
$currentType   = $_GET['bobin_type'] ?? 'all';
$currentRack   = $_GET['rack'] ?? 'all';
$currentEmpCode  = trim((string)($_GET['employee_code'] ?? ''));
$currentProdCode = trim((string)($_GET['product_code'] ?? ''));
$currentQcDefect = trim((string)($_GET['qc_defect'] ?? 'all'));
if ($currentQcDefect === '') {
    $currentQcDefect = 'all';
}
$currentMachine = trim((string)($_GET['machine'] ?? $_GET['extrusion_machine'] ?? ''));
$currentWindingMachine = trim((string)($_GET['winding_machine'] ?? ''));

$racks           = $data['racks'] ?? [];
$machines        = $data['machines'] ?? (class_exists('GlobalData') ? (GlobalData::$listExtrusionMachineEntity ?? []) : []);
$windingMachines = $data['windingMachines'] ?? (class_exists('GlobalData') ? (GlobalData::$listWindingMachineEntity ?? []) : []);

$defaultToDate   = date('Y-m-d');
$defaultFromDate = date('Y-m-d', strtotime('-7 days'));
$toDateVal       = !empty($_GET['to_date']) ? $_GET['to_date'] : $defaultToDate;
$fromDateVal     = !empty($_GET['from_date']) ? $_GET['from_date'] : $defaultFromDate;

// ========================================================
// TÍNH TOÁN DUNG LƯỢNG VÀ BOBIN TRỐNG (THEO TIÊU CHÍ 7)
// Số Bobin thực tế - Số Bobin [ĐÃ ĐÙN] + Số Bobin [ĐÃ CUỘN]
// ========================================================
$capacityMap = (!empty($data['capacityMap'])) ? $data['capacityMap'] : [
    'PL4-7 (TU04.TU06)' => 420,
    'PL4-7 (TU08~)'     => 480,
    'PL7-3'             => 460
];

if ($currentSize === 'all') {
    $totalRealBobins = array_sum($capacityMap);
} else {
    $totalRealBobins = $capacityMap[$currentSize] ?? 0;
}

$extrudedBobins = (int)($statusCounts['Extruded'] ?? 0);
$rolledBobins   = (int)($statusCounts['Rolled'] ?? 0);
$emptyBobins    = max(0, $totalRealBobins - $extrudedBobins + $rolledBobins);
$totalRecords   = $data['totalRecords'] ?? count($bobins);
$pagination     = $data['pagination'] ?? [];

// 2. DỮ LIỆU BIỂU ĐỒ (7 TIÊU CHÍ THEO YÊU CẦU 10)
// [Trống, Đã đùn, Chưa QC, Đã QC, Đã cuộn, Chờ hủy, Đã hủy]
$chartLabels = ['Trống', 'Đã đùn', 'Chưa QC', 'Đã QC', 'Đã cuộn', 'Chờ hủy', 'Đã hủy'];
$chartSeries = [
    $emptyBobins,
    $extrudedBobins,
    (int)($statusCounts['Busy_Unchecked'] ?? 0),
    (int)($statusCounts['Busy_Checked'] ?? 0),
    $rolledBobins,
    (int)($statusCounts['Pending_Cancellation'] ?? 0),
    (int)($statusCounts['Cancelled'] ?? 0)
];

$sizeOptions = [
    'all'               => 'Mọi kích thước',
    'PL7-3'             => 'PL7-3',
    'PL4-7 (TU08~)'     => 'PL4-7 (TU08~)',
    'PL4-7 (TU04.TU06)' => 'PL4-7 (TU04.TU06)'
];

$typeOptions = [
    'all'                              => 'Mọi loại Bobin',
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
        global $fromDateVal, $toDateVal;
        $query = $_GET ?? [];
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listBobinHistoryView';
        }

        $effectiveToDate = $overrideParams['to_date'] ?? ($query['to_date'] ?? $toDateVal);
        $effectiveFromDate = $overrideParams['from_date'] ?? ($query['from_date'] ?? $fromDateVal);

        if (!isset($query['from_date']) && !empty($fromDateVal)) {
            $query['from_date'] = $fromDateVal;
        }
        if (!isset($query['to_date']) && !empty($toDateVal)) {
            $query['to_date'] = $toDateVal;
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

if (!function_exists('getProductBadgeClass')) {
    function getProductBadgeClass(?string $code): string
    {
        $code = trim((string)$code);
        if ($code === '' || $code === 'Chưa cập nhật' || $code === '---') {
            return 'product-empty';
        }
        $idx = abs(crc32($code)) % 10;
        return 'prod-theme-' . $idx;
    }
}

if (!function_exists('getBobinTypeBadgeClass')) {
    function getBobinTypeBadgeClass(?string $type): string
    {
        $type = trim((string)$type);
        if ($type === '' || $type === 'Chưa cập nhật' || $type === '---') {
            return 'type-empty';
        }
        if ($type === 'Sản xuất') {
            return 'type-san-xuat';
        }
        if ($type === 'Bù') {
            return 'type-bu';
        }
        if ($type === 'Điều chỉnh (Do CP)') {
            return 'type-dieu-chinh-cp';
        }
        if (str_contains($type, 'Gel')) {
            return 'type-dieu-chinh-gel';
        }
        if (str_contains($type, 'Dị vật')) {
            return 'type-dieu-chinh-di-vat';
        }
        if (str_contains($type, 'Trầy')) {
            return 'type-dieu-chinh-tray';
        }
        if (str_contains($type, 'Biến dạng')) {
            return 'type-dieu-chinh-bien-dang';
        }
        if (str_contains($type, 'Xước')) {
            return 'type-dieu-chinh-xuoc';
        }
        if (str_contains($type, 'Chữ in')) {
            return 'type-dieu-chinh-chu-in';
        }
        if (str_contains($type, 'Vón cục')) {
            return 'type-dieu-chinh-von-cuc';
        }
        if (str_contains($type, 'Màu')) {
            return 'type-dieu-chinh-mau';
        }
        return 'type-dieu-chinh';
    }
}

if (!function_exists('getBobinTypeBadgeStyle')) {
    function getBobinTypeBadgeStyle(?string $type): string
    {
        $type = trim((string)$type);
        if ($type === '' || $type === 'Chưa cập nhật' || $type === '---') {
            return 'color: #64748b; background-color: #f1f5f9; border: 1.5px dashed #cbd5e1; font-weight: 600; font-style: italic;';
        }
        if ($type === 'Sản xuất') {
            return 'color: #14532d; background-color: #dcfce7; border: 1.5px solid #22c55e; font-weight: 700;';
        }
        if ($type === 'Bù') {
            return 'color: #1e40af; background-color: #dbeafe; border: 1.5px solid #3b82f6; font-weight: 700;';
        }
        if ($type === 'Điều chỉnh (Do CP)') {
            return 'color: #c2410c; background-color: #ffedd5; border: 1.5px solid #f97316; font-weight: 700;';
        }
        if (str_contains($type, 'Gel')) {
            return 'color: #6b21a8; background-color: #f3e8ff; border: 1.5px solid #a855f7; font-weight: 700;';
        }
        if (str_contains($type, 'Dị vật')) {
            return 'color: #9f1239; background-color: #ffe4e6; border: 1.5px solid #f43f5e; font-weight: 700;';
        }
        if (str_contains($type, 'Trầy')) {
            return 'color: #92400e; background-color: #fef3c7; border: 1.5px solid #f59e0b; font-weight: 700;';
        }
        if (str_contains($type, 'Biến dạng')) {
            return 'color: #115e59; background-color: #ccfbf1; border: 1.5px solid #14b8a6; font-weight: 700;';
        }
        if (str_contains($type, 'Xước')) {
            return 'color: #86198f; background-color: #fae8ff; border: 1.5px solid #d946ef; font-weight: 700;';
        }
        if (str_contains($type, 'Chữ in')) {
            return 'color: #3730a3; background-color: #e0e7ff; border: 1.5px solid #6366f1; font-weight: 700;';
        }
        if (str_contains($type, 'Vón cục')) {
            return 'color: #5b21b6; background-color: #ede9fe; border: 1.5px solid #8b5cf6; font-weight: 700;';
        }
        if (str_contains($type, 'Màu')) {
            return 'color: #991b1b; background-color: #fee2e2; border: 1.5px solid #ef4444; font-weight: 700;';
        }
        return 'color: #9a3412; background-color: #ffedd5; border: 1.5px solid #ea580c; font-weight: 700;';
    }
}

// Hàm badge kiểm tra công đoạn đùn: true = Đạt (OK), false = Lỗi (NG)
if (!function_exists('extCheckBadge')) {
    function extCheckBadge($label, $isOk)
    {
        $class = $isOk ? 'badge-ok' : 'badge-ng';
        $text  = $isOk ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}

// Hàm badge kiểm tra QC: $hasDefect = true có lỗi (NG); false không lỗi (OK)
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
    <title><?= __('page_bobin_history') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinHistory.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/advancedBobinFilter.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/scanQR.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" href="/WEB_BOBIN/public/assets/images/favicon.png?v=2">
    <link rel="shortcut icon" href="/WEB_BOBIN/public/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/WEB_BOBIN/public/assets/images/favicon.png?v=2">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/apexcharts.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/chart-helper.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/qrScannerHelper.js?v=<?= time() ?>"></script>
</head>

<body>

    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>
    <h1 data-i18n="page_bobin_history"><?= __('page_bobin_history') ?></h1>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR GỌN GÀNG -->
        <div class="control-bar-modern">
            <?php
            $canExtrusion = AuthHelper::hasPermission('extrusion_create');
            $histBackUrl = $canExtrusion ? '/WEB_BOBIN/public/index.php?url=bobin/index' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $histBackUrl ?>" class="btn-back-modern" title="Quay lại">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listBobinHistoryView">
                    <?php if ($currentStatus !== 'all'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
                    <?php endif; ?>
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
                    <?php if ($currentMachine !== '' && $currentMachine !== 'all'): ?>
                        <input type="hidden" name="machine" value="<?= htmlspecialchars($currentMachine) ?>">
                    <?php endif; ?>
                    <?php if ($currentWindingMachine !== '' && $currentWindingMachine !== 'all'): ?>
                        <input type="hidden" name="winding_machine" value="<?= htmlspecialchars($currentWindingMachine) ?>">
                    <?php endif; ?>

                    <div class="control-row">
                        <!-- TÌM KIẾM KEYWORD -->
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="searchKeyword" name="keyword"
                                placeholder="<?= __('ph_scan_bobin') ?>" data-i18n-ph="ph_scan_bobin"
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <!-- CHỌN KHOẢNG NGÀY -->
                        <div class="date-picker-compact">
                            <input type="date" name="from_date" id="fromDate"
                                value="<?= htmlspecialchars($fromDateVal) ?>">
                            <span class="date-sep">➝</span>
                            <input type="date" name="to_date" id="toDate" value="<?= htmlspecialchars($toDateVal) ?>">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <path d="M3 14h7v7H3z"></path>
                            </svg>
                            <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?? 'Quét QR' ?></span>
                        </button>

                        <button type="submit" class="btn-modern btn-filter" id="btnFilter">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            <span data-i18n="filter"><?= __('filter') ?? 'Lọc' ?></span>
                        </button>

                        <?php
                        $exportParams = $_GET;
                        $exportParams['url'] = 'bobin/exportHistoryExcel';
                        if (empty($exportParams['from_date'])) $exportParams['from_date'] = $fromDateVal;
                        if (empty($exportParams['to_date'])) $exportParams['to_date'] = $toDateVal;
                        $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
                        ?>
                        <!-- MENU XUẤT EXCEL THÔNG MINH CHO LỊCH SỬ -->
                        <div class="export-dropdown-wrapper">
                            <button type="button" class="btn-modern btn-export" id="btnToggleExportMenu">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span
                                    data-i18n="btn_export_excel_dropdown"><?= __('btn_export_excel_dropdown') ?? 'Xuất Excel ▾' ?></span>
                            </button>
                            <div class="export-dropdown-menu" id="exportMenuBox">
                                <a href="<?= $exportUrl ?>" class="export-menu-item">
                                    <span class="menu-item-icon">📄</span>
                                    <div class="menu-item-text">
                                        <strong
                                            data-i18n="export_all_results"><?= __('export_all_results') ?? 'Xuất toàn bộ kết quả lọc' ?></strong>
                                        <small>Tất cả <?= number_format($totalRecords) ?> lượt cập nhật</small>
                                    </div>
                                </a>
                                <button type="button" class="export-menu-item" id="btnExportSelectedItems">
                                    <span class="menu-item-icon">☑️</span>
                                    <div class="menu-item-text">
                                        <strong
                                            data-i18n="export_selected_bobins"><?= __('export_selected_bobins') ?? 'Xuất các bản ghi đã chọn' ?></strong>
                                        <small id="selectedCountText">Đã chọn: 0 bản ghi</small>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- DASHBOARD THỐNG KÊ (BIỂU ĐỒ 220PX + LƯỚI 2 CỘT KPI) -->
        <div class="analytics-wrapper">
            <div class="analytics-left">
                <div class="analytics-charts-grid">
                    <!-- BIỂU ĐỒ 1: THỐNG KÊ LỊCH SỬ CẬP NHẬT TRẠNG THÁI BOBIN -->
                    <div class="chart-panel">
                        <div class="analytics-title-box">
                            <div class="badge-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="18" y="4" width="4" height="16" fill="#38bdf8" stroke="none"></rect>
                                    <rect x="10" y="9" width="4" height="11" fill="#818cf8" stroke="none"></rect>
                                    <rect x="2" y="14" width="4" height="6" fill="#34d399" stroke="none"></rect>
                                </svg>
                            </div>
                            <h3 data-i18n="chart_history_status_stats">Thống kê lịch sử cập nhật trạng thái Bobin</h3>
                        </div>

                        <div class="chart-box">
                            <div id="statusPieChart"></div>
                        </div>
                    </div>

                    <!-- BIỂU ĐỒ 2: THỐNG KÊ NGOẠI QUAN BOBIN (TASK-052) -->
                    <div class="chart-panel">
                        <div class="analytics-title-box qc-chart-title-box">
                            <div class="title-left">
                                <div class="badge-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed"
                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 11l3 3L22 4"></path>
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                                    </svg>
                                </div>
                                <h3 data-i18n="chart_qc_defect_stats"><?= __('chart_qc_defect_stats') ?></h3>
                            </div>
                            <div class="qc-stat-badges">
                                <span class="qc-badge-pill qc-pill-ok" title="Đạt toàn bộ tiêu chuẩn ngoại quan">
                                    <span class="dot">●</span> <span data-i18n="qc_defect_ok"><?= __('qc_defect_ok') ?></span>:
                                    <strong><?= number_format($qcDefectStats['ok_all'] ?? 0) ?></strong>
                                </span>
                                <span class="qc-badge-pill qc-pill-ng" title="Có ít nhất một lỗi ngoại quan">
                                    <span class="dot">●</span> <span data-i18n="qc_defect_ng"><?= __('qc_defect_ng') ?></span>:
                                    <strong><?= number_format($qcDefectStats['ng_any'] ?? 0) ?></strong>
                                </span>
                            </div>
                        </div>

                        <div class="chart-box">
                            <div id="qcDefectChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="analytics-right">
                <div class="total-card">
                    <div class="total-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 12 12 17 22 12"></polyline>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </svg>
                    </div>
                    <div class="total-info">
                        <span class="label">Tổng số lượng Bobin thực tế</span>
                        <span class="value"><?= number_format($totalRealBobins) ?></span>
                    </div>
                </div>

                <!-- LƯỚI 2 CỘT KPI THU GỌN (CHỈ HIỂN THỊ SỐ, KHÔNG PHẢI NÚT NHẤN) -->
                <div class="kpi-grid">
                    <div class="kpi-card empty-bobin">
                        <div class="kpi-left">
                            <span class="kpi-icon">📦</span>
                            <span class="kpi-name">BOBIN TRỐNG</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($emptyBobins) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card extruded">
                        <div class="kpi-left">
                            <span class="kpi-icon">⚙️</span>
                            <span class="kpi-name">ĐÃ ĐÙN</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Extruded'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card unchecked">
                        <div class="kpi-left">
                            <span class="kpi-icon">⏳</span>
                            <span class="kpi-name">CHƯA KT QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Unchecked'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card checked">
                        <div class="kpi-left">
                            <span class="kpi-icon">🛡️</span>
                            <div class="kpi-title-wrap">
                                <span class="kpi-name">ĐÃ KT QC</span>
                                <div class="kpi-sub-breakdown">
                                    <span class="sub-pill in-period"
                                        title="Bobin được đùn trong khoảng thời gian tra cứu">Trong:
                                        <strong><?= number_format($statusCounts['Busy_Checked_InPeriod'] ?? 0) ?></strong></span>
                                    <span class="sub-pill before"
                                        title="Bobin được đùn trước khoảng thời gian tra cứu">Trước:
                                        <strong><?= number_format($statusCounts['Busy_Checked_Before'] ?? 0) ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Checked'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card rolled">
                        <div class="kpi-left">
                            <span class="kpi-icon">✅</span>
                            <span class="kpi-name">ĐÃ CUỘN</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Rolled'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card pending">
                        <div class="kpi-left">
                            <span class="kpi-icon">⚠️</span>
                            <div class="kpi-title-wrap">
                                <span class="kpi-name">CHỜ HỦY</span>
                                <div class="kpi-sub-breakdown">
                                    <span class="sub-pill in-period"
                                        title="Bobin được đùn trong khoảng thời gian tra cứu">Trong:
                                        <strong><?= number_format($statusCounts['Pending_Cancellation_InPeriod'] ?? 0) ?></strong></span>
                                    <span class="sub-pill before"
                                        title="Bobin được đùn trước khoảng thời gian tra cứu">Trước:
                                        <strong><?= number_format($statusCounts['Pending_Cancellation_Before'] ?? 0) ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-right">
                            <strong
                                class="kpi-val"><?= number_format($statusCounts['Pending_Cancellation'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="kpi-card cancelled">
                        <div class="kpi-left">
                            <span class="kpi-icon">🗑️</span>
                            <div class="kpi-title-wrap">
                                <span class="kpi-name">ĐÃ HỦY</span>
                                <div class="kpi-sub-breakdown">
                                    <span class="sub-pill in-period"
                                        title="Bobin được đùn trong khoảng thời gian tra cứu">Trong:
                                        <strong><?= number_format($statusCounts['Cancelled_InPeriod'] ?? 0) ?></strong></span>
                                    <span class="sub-pill before"
                                        title="Bobin được đùn trước khoảng thời gian tra cứu">Trước:
                                        <strong><?= number_format($statusCounts['Cancelled_Before'] ?? 0) ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Cancelled'] ?? 0) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- BẢNG BẢO TOÀN SỐ LIỆU ĐÃ ĐÙN -->
                <div class="conservation-banner">
                    <span class="banner-icon">⚖️</span>
                    <div class="banner-text">
                        <strong>Bảo toàn số lượng ĐÃ ĐÙN
                            (<?= number_format($statusCounts['Extruded'] ?? 0) ?>):</strong>
                        <span>Đã QC (<?= number_format($statusCounts['Conserved_Busy_Checked'] ?? 0) ?>) + Chưa QC
                            (<?= number_format($statusCounts['Conserved_Busy_Unchecked'] ?? 0) ?>) + Đã cuộn
                            (<?= number_format($statusCounts['Conserved_Rolled'] ?? 0) ?>) + Chờ hủy
                            (<?= number_format($statusCounts['Conserved_Pending_Cancellation'] ?? 0) ?>) + Đã hủy
                            (<?= number_format($statusCounts['Conserved_Cancelled'] ?? 0) ?>) =
                            <strong><?= number_format(($statusCounts['Conserved_Busy_Checked'] ?? 0) + ($statusCounts['Conserved_Busy_Unchecked'] ?? 0) + ($statusCounts['Conserved_Rolled'] ?? 0) + ($statusCounts['Conserved_Pending_Cancellation'] ?? 0) + ($statusCounts['Conserved_Cancelled'] ?? 0)) ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                const rawData = <?= json_encode($chartSeries, JSON_NUMERIC_CHECK) ?>;
                const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                const totalRealBobins = <?= (int)$totalRealBobins ?>;
                const maxVal = Math.max(...rawData, 0);

                const extrudedBobins = <?= (int)$extrudedBobins ?>;

                const translatedLabels = labels.map(function(lbl) {
                    if (typeof window.t !== 'function') return lbl;
                    if (lbl === 'Trống') return window.t('chart_empty', 'Trống');
                    if (lbl === 'Đã đùn') return window.t('status_extruded', 'Đã đùn');
                    if (lbl === 'Chưa QC') return window.t('status_unchecked', 'Chưa QC');
                    if (lbl === 'Đã QC') return window.t('status_checked', 'Đã QC');
                    if (lbl === 'Đã cuộn') return window.t('status_ready', 'Đã cuộn');
                    if (lbl === 'Chờ hủy') return window.t('status_pending_cancel', 'Chờ hủy');
                    if (lbl === 'Đã hủy') return window.t('status_cancelled', 'Đã hủy');
                    return lbl;
                });

                const statusChart = new BaseChart('#statusPieChart', {
                    chart: {
                        type: 'bar',
                        height: 240,
                        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            speed: 300
                        }
                    },
                    series: [{
                        name: (typeof window.t === 'function' ? window.t('chart_bobin_count',
                            'Số lượng Bobin') : 'Số lượng Bobin'),
                        data: rawData
                    }],
                    colors: ['#94a3b8', '#0d9488', '#f97316', '#0284c7', '#22c55e', '#f59e0b', '#ef4444'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '62%',
                            distributed: true,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        offsetX: 8,
                        formatter: function(val, opt) {
                            const idx = opt.dataPointIndex;
                            let percent = 0;
                            if (idx === 0 || idx === 1) {
                                // Trống và Đã đùn: tính theo Tổng số lượng Bobin thực tế
                                percent = totalRealBobins > 0 ? ((val / totalRealBobins) * 100).toFixed(1) :
                                    0;
                            } else {
                                // Chưa QC, Đã QC, Đã cuộn, Chờ hủy, Đã hủy: tính theo số lượng ĐÃ ĐÙN
                                percent = extrudedBobins > 0 ? ((val / extrudedBobins) * 100).toFixed(1) :
                                    0;
                            }
                            return `${val.toLocaleString('vi-VN')} (${percent}%)`;
                        },
                        style: {
                            fontSize: '12px',
                            fontWeight: 800,
                            colors: ["#475569"]
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 3,
                        padding: {
                            top: -12,
                            right: 45,
                            bottom: -10,
                            left: 15
                        }
                    },
                    xaxis: {
                        categories: translatedLabels,
                        min: 0,
                        max: maxVal === 0 ? 10 : Math.ceil(maxVal * 1.3),
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontWeight: 600
                            },
                            formatter: (val) => Math.floor(val).toLocaleString('vi-VN')
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#1e293b',
                                fontSize: '12.5px',
                                fontWeight: 700
                            }
                        }
                    },
                    legend: {
                        show: false
                    }
                });
                statusChart.render();

                // BIỂU ĐỒ 2: THỐNG KÊ NGOẠI QUAN BOBIN (TASK-052)
                const qcDefectData = [
                    <?= (int)($qcDefectStats['gel'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['foreign_object'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['color_issue'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['print_quality'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['clump'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['scratch'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['streak'] ?? 0) ?>,
                    <?= (int)($qcDefectStats['deformation'] ?? 0) ?>
                ];
                const qcLabelsRaw = ['Gel', 'Dị vật', 'Lỗi màu', 'Chữ in', 'Vón cục', 'Trầy', 'Xước', 'Biến dạng'];
                const qcTranslatedLabels = qcLabelsRaw.map(function(lbl) {
                    if (typeof window.t !== 'function') return lbl;
                    if (lbl === 'Gel') return window.t('qc_defect_gel', 'Gel');
                    if (lbl === 'Dị vật') return window.t('qc_defect_foreign', 'Dị vật');
                    if (lbl === 'Lỗi màu') return window.t('qc_defect_color', 'Lỗi màu');
                    if (lbl === 'Chữ in') return window.t('qc_defect_print', 'Chữ in');
                    if (lbl === 'Vón cục') return window.t('qc_defect_clump', 'Vón cục');
                    if (lbl === 'Trầy') return window.t('qc_defect_scratch', 'Trầy');
                    if (lbl === 'Xước') return window.t('qc_defect_streak', 'Xước');
                    if (lbl === 'Biến dạng') return window.t('qc_defect_deform', 'Biến dạng');
                    return lbl;
                });
                const qcTotalNg = <?= (int)($qcDefectStats['ng_any'] ?? 0) ?>;
                const qcMaxVal = Math.max(...qcDefectData, 0);

                const qcChart = new BaseChart('#qcDefectChart', {
                    chart: {
                        type: 'bar',
                        height: 240,
                        fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            speed: 300
                        }
                    },
                    series: [{
                        name: (typeof window.t === 'function' ? window.t('chart_defect_count',
                            'Số lỗi') : 'Số lỗi'),
                        data: qcDefectData
                    }],
                    colors: ['#a855f7', '#f43f5e', '#f97316', '#6366f1', '#8b5cf6', '#f59e0b', '#ec4899',
                        '#14b8a6'
                    ],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '62%',
                            distributed: true,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        offsetX: 8,
                        formatter: function(val) {
                            if (val === 0) return '0';
                            const percent = qcTotalNg > 0 ? ((val / qcTotalNg) * 100).toFixed(1) : 0;
                            return `${val.toLocaleString('vi-VN')} (${percent}%)`;
                        },
                        style: {
                            fontSize: '12px',
                            fontWeight: 800,
                            colors: ["#475569"]
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 3,
                        padding: {
                            top: -12,
                            right: 35,
                            bottom: -10,
                            left: 15
                        }
                    },
                    xaxis: {
                        categories: qcTranslatedLabels,
                        min: 0,
                        max: qcMaxVal === 0 ? 5 : Math.ceil(qcMaxVal * 1.3),
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '11px',
                                fontWeight: 600
                            },
                            formatter: (val) => Math.floor(val).toLocaleString('vi-VN')
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#1e293b',
                                fontSize: '12px',
                                fontWeight: 700
                            }
                        }
                    },
                    legend: {
                        show: false
                    }
                });
                qcChart.render();
            })();
        </script>

        <!-- BỘ LỌC 4 DROPDOWN TRÊN 1 HÀNG NGANG (TIẾT KIỆM KHÔNG GIAN) -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <h2>Kết quả tìm kiếm: <span><?= number_format($totalRecords) ?></span> Lượt cập nhật</h2>
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAllCheckbox"> Chọn tất cả trang này
                    </label>
                </div>
                <a href="<?= buildFilterUrl(['status' => 'all', 'bobin_size' => 'all', 'bobin_type' => 'all', 'rack' => 'all', 'keyword' => null, 'employee_code' => null, 'product_code' => null, 'qc_defect' => 'all', 'machine' => null, 'winding_machine' => null]) ?>"
                    class="btn-reset-filter">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

            <div class="filter-grid-row">
                <div class="filter-col">
                    <div class="filter-label">📌 Trạng thái:</div>
                    <select id="statusFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($statusOptions as $key => $label): ?>
                            <option value="<?= buildFilterUrl(['status' => $key]) ?>"
                                <?= $currentStatus === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

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
                        <?php foreach ($racks as $r): ?>
                            <?php $rCode = $r['rack_code'] ?? $r['code'] ?? ''; ?>
                            <?php if (!empty($rCode)): ?>
                                <option value="<?= buildFilterUrl(['rack' => $rCode]) ?>"
                                    <?= $currentRack === $rCode ? 'selected' : '' ?>>
                                    📍 Rack: <?= htmlspecialchars($rCode) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- LỌC THEO MÁY ĐÙN (TASK-051) -->
                <div class="filter-col">
                    <div class="filter-label"> Máy đùn:</div>
                    <select id="machineFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <option value="<?= buildFilterUrl(['machine' => 'all']) ?>"
                            <?= ($currentMachine === '' || $currentMachine === 'all') ? 'selected' : '' ?>>
                            Tất cả máy đùn
                        </option>
                        <?php foreach ($machines as $m):
                            $mName = is_object($m) ? ($m->machine_name ?? '') : ($m['machine_name'] ?? '');
                            if (empty($mName)) continue;
                        ?>
                            <option value="<?= buildFilterUrl(['machine' => $mName]) ?>"
                                <?= $currentMachine === $mName ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- LỌC THEO MÁY CUỘN (TASK-051) -->
                <div class="filter-col">
                    <div class="filter-label"> Máy cuộn:</div>
                    <select id="windingMachineFilter" class="filter-select"
                        onchange="window.location.href = this.value;">
                        <option value="<?= buildFilterUrl(['winding_machine' => 'all']) ?>"
                            <?= ($currentWindingMachine === '' || $currentWindingMachine === 'all') ? 'selected' : '' ?>>
                            Tất cả máy cuộn
                        </option>
                        <?php foreach ($windingMachines as $wm):
                            $wmName = is_object($wm) ? ($wm->machine_name ?? '') : ($wm['machine_name'] ?? '');
                            if (empty($wmName)) continue;
                        ?>
                            <option value="<?= buildFilterUrl(['winding_machine' => $wmName]) ?>"
                                <?= $currentWindingMachine === $wmName ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wmName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php require ROOT_PATH . '/app/views/components/advancedBobinFilterAndStats.php'; ?>
        </div>

        <!-- DANH SÁCH BOBIN THỰC TẾ -->
        <div class="list-card">
            <?php if (empty($bobins)): ?>
                <div class="empty-state">Không tìm thấy dữ liệu nào trong khoảng thời gian này.</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled'               => 'status_rolled',
                            'busy_unchecked'       => 'status_busy_unchecked',
                            'busy_checked'         => 'status_busy_checked',
                            'pending_cancellation' => 'status_pending_cancellation',
                            'cancelled'            => 'status_cancelled',
                            default                => 'status_unknown'
                        };

                        $vi = decodeJsonObject($item['visual_inspection'] ?? '');
                        $defects = is_array($vi['defects'] ?? null) ? $vi['defects'] : [
                            'gel'            => $vi['defect_gel'] ?? false,
                            'foreign_object' => $vi['defect_foreign'] ?? ($vi['defect_foreign_object'] ?? false),
                            'color_issue'    => $vi['defect_color'] ?? ($vi['defect_color_issue'] ?? false),
                            'print_quality'  => $vi['defect_print'] ?? ($vi['defect_print_quality'] ?? false),
                            'clump'          => $vi['defect_clump'] ?? false,
                            'scratch'        => $vi['defect_scratch'] ?? false,
                            'streak'         => $vi['defect_streak'] ?? false,
                            'deformation'    => $vi['defect_deformation'] ?? false,
                            'note'           => $vi['defect_note'] ?? ''
                        ];
                        if (empty($vi['inspection_time']) && !empty($vi['inspected_at'])) {
                            $vi['inspection_time'] = $vi['inspected_at'];
                        }
                        $products = decodeJsonObject($item['products'] ?? '');
                        $productCode = $products['product_code'] ?? 'Chưa cập nhật';
                        $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                        $extrusion_employeeCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extrusion_employeeName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';
                        $materialLotData = decodeJsonObject($item['material_lot'] ?? '');
                        $materialLot = $materialLotData['lot'] ?? 'Chưa cập nhật';
                        $windingEmployee = decodeJsonObject($item['winding_employee'] ?? '');
                        $winding_employeeCode = $windingEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $winding_employeeName = $windingEmployee['employee_name'] ?? 'Chưa cập nhật';
                        $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                        $rackData = decodeJsonObject($item['rack'] ?? '');
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';
                        $isRackEmpty = ($rackCode === 'Chưa cập nhật');

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'] ?? '';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';

                        $formatted_time = !empty($finish_time) ? date('dmY$His', strtotime($finish_time)) : '';
                        $fields = [$productCode, $materialLot, $printLot, $formatted_time];
                        $isMissingData = false;
                        foreach ($fields as $field) {
                            $checkValue = trim((string)$field);
                            if ($checkValue === '' || $checkValue === 'Chưa cập nhật') {
                                $isMissingData = true;
                                break;
                            }
                        }

                        $mainInfoText = $isMissingData
                            ? 'Chưa cập nhật đầy đủ thông tin cần thiết'
                            : $productCode . '$' . $materialLot . '$' . $printLot . '$' . $formatted_time;
                        ?>
                        <div class="bobin-item <?= $statusClass ?>">
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
                                    <input type="checkbox" class="bobin-select-checkbox"
                                        value="<?= htmlspecialchars($item['id'] ?? $item['bobin_key_code']) ?>"
                                        title="Chọn bản ghi này để xuất Excel">

                                    <span class="key-code"><?= $displayKey ?></span>
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                </div>

                                <!-- ĐƯA MAIN-INFO VÀ STATUS-BADGE VÀO LẠI BÊN TRONG CARD-TOP -->
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
                                        'Rolled'               => 'ĐÃ CUỘN',
                                        'Busy_Unchecked'       => 'CHƯA KIỂM TRA QC',
                                        'Busy_Checked'         => 'ĐÃ KIỂM TRA QC',
                                        'Pending_Cancellation' => 'ĐANG CHỜ HỦY',
                                        'Cancelled'            => 'ĐÃ HỦY',
                                        default                => $rawStatus,
                                    };
                                    echo htmlspecialchars($displayStatus);
                                    ?>
                                </div>
                            </div> <!-- ĐÓNG THẺ CARD-TOP TẠI ĐÂY -->

                            <!-- 12 THÔNG SỐ TRÌNH BÀY DẠNG GRID TỐI ƯU -->
                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub">
                                        <span
                                            class="bobin-highlight-product <?= getProductBadgeClass($productCode) ?>"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Mã nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Vị trí RACK</label>
                                    <div class="val-sub">
                                        <span class="bobin-highlight-rack <?= $isRackEmpty ? 'rack-empty' : '' ?>">
                                            <?= $isRackEmpty ? '---' : ('📍 ' . htmlspecialchars($rackCode)) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label data-i18n="field_machine"><?= __('field_machine') ?? 'Máy đùn' ?></label>
                                    <div class="val-sub">
                                        <?= htmlspecialchars(!empty($item['extrusion_machine']) ? $item['extrusion_machine'] : 'Chưa cập nhật') ?>
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
                                    <?php
                                    $rawBobinType = trim($item['bobin_type'] ?? '');
                                    $typeBadgeClass = getBobinTypeBadgeClass($rawBobinType);
                                    ?>
                                    <div class="val-sub">
                                        <span class="bobin-type-badge <?= $typeBadgeClass ?>"
                                            data-type="<?= htmlspecialchars($rawBobinType) ?>"
                                            title="<?= htmlspecialchars($rawBobinType) ?>"
                                            style="<?= getBobinTypeBadgeStyle($rawBobinType) ?>">
                                            <?= htmlspecialchars(!empty($rawBobinType) ? $rawBobinType : 'Chưa cập nhật') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Lot vật liệu</label>
                                    <div class="val-sub"><?= htmlspecialchars($materialLot) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub">
                                        <span
                                            class="bobin-highlight-printlot <?= empty($item['print_lot']) || $item['print_lot'] === 'Chưa cập nhật' ? 'lot-empty' : '' ?>">
                                            <?= htmlspecialchars($item['print_lot'] ?? 'Chưa cập nhật') ?>
                                        </span>
                                    </div>
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

                            <!-- GỘP 3 KHỐI KIỂM SOÁT THÀNH 3 CỘT SONG SONG -->
                            <div class="inspection-pipeline-grid">
                                <!-- CỘT 1: ĐÙN CHECK -->
                                <div class="pipeline-col extrusion-col">
                                    <div class="qc-title">🏭 Đùn Check</div>
                                    <?php
                                    $hasExtCheck = !empty($extCheck) && (
                                        isset($extCheck['diameter']) ||
                                        isset($extCheck['gel']) ||
                                        isset($extCheck['foreign_object']) ||
                                        isset($extCheck['color']) ||
                                        isset($extCheck['print']) ||
                                        isset($extCheck['clump']) ||
                                        isset($extCheck['scratch']) ||
                                        isset($extCheck['streak']) ||
                                        isset($extCheck['deformation'])
                                    );
                                    ?>
                                    <?php if (!$hasExtCheck): ?>
                                        <div class="empty-pipeline-notice notice-ext" data-i18n="pipeline_no_ext_data">
                                            <?= __('pipeline_no_ext_data') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="qc-badges">
                                            <?php
                                            $extItems = [
                                                'crit_diameter'    => ['label' => 'Đường kính', 'ok' => (bool)($extCheck['diameter'] ?? true)],
                                                'crit_gel'         => ['label' => 'Gel',        'ok' => (bool)($extCheck['gel'] ?? true)],
                                                'crit_foreign'     => ['label' => 'Dị vật',     'ok' => (bool)($extCheck['foreign_object'] ?? true)],
                                                'crit_color'       => ['label' => 'Màu sắc',    'ok' => (bool)($extCheck['color'] ?? true)],
                                                'crit_print'       => ['label' => 'Chữ in',     'ok' => (bool)($extCheck['print'] ?? true)],
                                                'crit_clump'       => ['label' => 'Vón cục',    'ok' => (bool)($extCheck['clump'] ?? $extCheck['lump'] ?? true)],
                                                'crit_scratch'     => ['label' => 'Trầy',      'ok' => (bool)($extCheck['scratch'] ?? true)],
                                                'crit_streak'      => ['label' => 'Xước',      'ok' => (bool)($extCheck['streak'] ?? $extCheck['scuff'] ?? true)],
                                                'crit_deformation' => ['label' => 'Biến dạng', 'ok' => (bool)($extCheck['deformation'] ?? $extCheck['deformed'] ?? true)],
                                            ];
                                            foreach ($extItems as $i18nKey => $ec) {
                                                $cls = $ec['ok'] ? 'badge-ok' : 'badge-ng';
                                                $txt = $ec['ok'] ? 'OK' : 'NG';
                                                echo "<div class='vi-item $cls'><span data-i18n='{$i18nKey}'>" . __($i18nKey) . "</span><strong>$txt</strong></div>";
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pipeline-col-image-bar">
                                        <?= BobinImageHelper::renderViewButton('extrusion', $item['extrusion_chart_image'] ?? null, (string)($item['bobin_identification_code'] ?? '')) ?>
                                    </div>
                                </div>

                                <!-- CỘT 2: QC CHECK -->
                                <div class="pipeline-col qc-col">
                                    <div class="qc-title">🛡️ QC Check</div>
                                    <?php $hasQcInspected = !empty($vi['inspector_code']) && $vi['inspector_code'] !== 'Chưa cập nhật'; ?>
                                    <?php if (!$hasQcInspected): ?>
                                        <div class="empty-pipeline-notice notice-qc" data-i18n="pipeline_no_qc_data">
                                            <?= __('pipeline_no_qc_data') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="qc-header">
                                            <div class="qc-info-row">
                                                <div class="qc-meta-item">
                                                    <label class="val-text">Mã NV:</label>
                                                    <span
                                                        class="val-sub"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?></span>
                                                </div>
                                                <div class="qc-meta-item">
                                                    <label class="val-text">Họ tên:</label>
                                                    <span
                                                        class="val-sub"><?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa cập nhật') ?></span>
                                                </div>
                                                <div class="qc-meta-item">
                                                    <label class="val-text">Thời gian:</label>
                                                    <span
                                                        class="val-sub"><?= htmlspecialchars($vi['inspection_time'] ?? 'Chưa cập nhật') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="qc-badges">
                                            <?= qcDefectBadge('Gel', $defects['gel'] ?? false) ?>
                                            <?= qcDefectBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                            <?= qcDefectBadge('Màu', $defects['color_issue'] ?? false) ?>
                                            <?= qcDefectBadge('In', $defects['print_quality'] ?? false) ?>
                                            <?= qcDefectBadge('Vón cục', ($defects['clump'] ?? false) || ($defects['lump'] ?? false) || ($vi['defect_clump'] ?? false)) ?>
                                            <?= qcDefectBadge('Trầy', ($defects['scratch'] ?? false) || ($vi['defect_scratch'] ?? false)) ?>
                                            <?= qcDefectBadge('Xước', ($defects['streak'] ?? false) || ($defects['scuff'] ?? false) || ($vi['defect_streak'] ?? false)) ?>
                                            <?= qcDefectBadge('Biến dạng', ($defects['deformation'] ?? false) || ($defects['deformed'] ?? false) || ($vi['defect_deformation'] ?? false)) ?>
                                        </div>
                                        <?php if (!empty($defects['note']) && $defects['note'] !== 'Chưa cập nhật'): ?>
                                            <div class="qc-note">📝 <?= htmlspecialchars($defects['note']) ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="pipeline-col-image-bar">
                                        <?= BobinImageHelper::renderViewButton('qc', $item['qc_visual_image'] ?? null, (string)($item['bobin_identification_code'] ?? '')) ?>
                                    </div>
                                </div>

                                <!-- CỘT 3: THÔNG TIN CUỘN -->
                                <div class="pipeline-col winding-col">
                                    <div class="winding-title">📍 Thông tin cuộn</div>
                                    <?php $hasWinding = (!empty($item['winding_machine']) && $item['winding_machine'] !== 'Chưa cập nhật') || (!empty($winding_employeeCode) && $winding_employeeCode !== 'Chưa cập nhật') || ($rawStatus === 'Rolled'); ?>
                                    <?php if (!$hasWinding): ?>
                                        <div class="empty-pipeline-notice notice-winding" data-i18n="pipeline_no_winding_data">
                                            <?= __('pipeline_no_winding_data') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="winding-header">
                                            <div class="winding-info-row">
                                                <div class="winding-input">
                                                    <label class="val-text">Máy cuộn:</label>
                                                    <span
                                                        class="val-sub"><?= htmlspecialchars($item['winding_machine'] ?? 'Chưa cập nhật') ?></span>
                                                </div>
                                                <div class="winding-input">
                                                    <label class="val-text">Mã NV:</label>
                                                    <span class="val-sub"><?= htmlspecialchars($winding_employeeCode) ?></span>
                                                </div>
                                                <div class="winding-input">
                                                    <label class="val-text">Họ tên:</label>
                                                    <span class="val-sub"><?= htmlspecialchars($winding_employeeName) ?></span>
                                                </div>
                                                <div class="winding-input">
                                                    <label class="val-text">Thông khí:</label>
                                                    <span class="val-sub"><?= htmlspecialchars($flow_test_result) ?></span>
                                                </div>
                                            </div>
                                            <?php if (!empty($item['winding_note']) && $item['winding_note'] !== 'Chưa cập nhật'): ?>
                                                <div class="winding-note">📝 <?= htmlspecialchars($item['winding_note']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pipeline-col-image-bar">
                                        <?= BobinImageHelper::renderViewButton('winding', $item['winding_chart_image'] ?? null, (string)($item['bobin_identification_code'] ?? '')) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer-simple">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                        <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>

                                <?php
                                $rawAudit = $item['update_history'] ?? '';
                                $auditList = decodeJsonObject($rawAudit);
                                if (!empty($auditList) && is_array($auditList)):
                                ?>
                                    <div class="audit-history-box"
                                        style="margin-top: 8px; padding: 6px 10px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 12px; width: 100%;">
                                        <div
                                            style="font-weight: 700; color: #475569; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                            <span>📝</span>
                                            <span data-i18n="audit_trail_title"><?= __('audit_trail_title') ?></span>
                                            <span class="badge"
                                                style="background: #e2e8f0; color: #334155; padding: 1px 6px; border-radius: 10px; font-size: 11px;"><?= count($auditList) ?></span>
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
                                                <div
                                                    style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #fff; padding: 3px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                    <div>
                                                        <strong style="color: #2563eb;">[<?= $stageName ?>]</strong>
                                                        <span style="color: #1e293b; font-weight: 600;"><?= $empDisplay ?></span>
                                                        <?php if ($noteDisplay !== ''): ?>
                                                            <span
                                                                style="color: <?= $noteColor ?>; font-weight: 600; font-style: italic;">(<span
                                                                    <?= $noteI18nAttr ?>><?= $noteDisplay ?></span>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span style="color: #64748b; font-family: monospace; font-size: 11.5px;">🕒
                                                        <?= $timeDisplay ?></span>
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

    <?php if (!empty($data['error'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const errMsg = <?= json_encode($data['error'], JSON_UNESCAPED_UNICODE) ?>;
                if (window.Toast && window.Toast.error) {
                    window.Toast.error(errMsg);
                }
            });
        </script>
    <?php endif; ?>

    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/autoSearchBobin.js?v=<?= time() ?>"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const interactiveElements = document.querySelectorAll(
                ".filter-dashboard select, .filter-dashboard a, .kpi-list a, .pagination-wrapper a");
            interactiveElements.forEach(el => {
                const eventType = el.tagName === "SELECT" ? "change" : "click";
                el.addEventListener(eventType, function() {
                    sessionStorage.setItem("bobin_history_scroll_pos", window.scrollY);
                });
            });

            const scrollPos = sessionStorage.getItem("bobin_history_scroll_pos");
            if (scrollPos !== null) {
                window.scrollTo({
                    top: parseInt(scrollPos, 10),
                    behavior: "instant"
                });
                sessionStorage.removeItem("bobin_history_scroll_pos");
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const btnToggle = document.getElementById('btnToggleExportMenu');
            const menuBox = document.getElementById('exportMenuBox');
            const selectAllCb = document.getElementById('selectAllCheckbox');
            const itemCheckboxes = document.querySelectorAll('.bobin-select-checkbox');
            const countText = document.getElementById('selectedCountText');
            const btnExportSelected = document.getElementById('btnExportSelectedItems');

            // Bật/tắt menu dropdown xuất Excel
            if (btnToggle && menuBox) {
                btnToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menuBox.classList.toggle('show');
                });
                document.addEventListener('click', function() {
                    menuBox.classList.remove('show');
                });
            }

            // Cập nhật số lượng bản ghi đã tích chọn
            function updateSelectedCount() {
                const selected = Array.from(itemCheckboxes).filter(cb => cb.checked);
                if (countText) {
                    countText.textContent = `Đã chọn: ${selected.length} bản ghi`;
                }
                if (selectAllCb) {
                    selectAllCb.checked = (selected.length === itemCheckboxes.length && itemCheckboxes.length > 0);
                }
            }

            // Chọn tất cả
            if (selectAllCb) {
                selectAllCb.addEventListener('change', function() {
                    itemCheckboxes.forEach(cb => cb.checked = selectAllCb.checked);
                    updateSelectedCount();
                });
            }

            itemCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            // Xử lý xuất các mục đã chọn
            if (btnExportSelected) {
                btnExportSelected.addEventListener('click', function() {
                    const selectedIds = Array.from(itemCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);

                    if (selectedIds.length === 0) {
                        const warnMsg = window.t ? window.t('warn_select_history_first') :
                            "Vui lòng tích chọn ít nhất 1 bản ghi lịch sử trước khi xuất!";
                        if (window.Toast && window.Toast.show) {
                            window.Toast.show(warnMsg, "warning");
                        } else if (typeof Toast !== 'undefined' && Toast.show) {
                            Toast.show(warnMsg, "warning");
                        } else {
                            alert(warnMsg);
                        }
                        return;
                    }

                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('url', 'bobin/exportHistoryExcel');
                    currentUrl.searchParams.set('selected_ids', selectedIds.join(','));
                    window.location.href = currentUrl.toString();
                });
            }
        });
    </script>
</body>

</html>