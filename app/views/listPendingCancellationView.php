<?php
$bobins = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$filteredPendingCount = $totalRecords;
$pagination = $data['pagination'] ?? [];
$userRole   = $_SESSION['user']['role'] ?? '';
$canEditPendingCancel = !empty($_SESSION['user']) && AuthHelper::hasPermission('pending_cancel_edit');
$currentUrl = $_GET['url'] ?? '';
$pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? $filteredPendingCount);

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
            $query['url'] = 'bobin/listPendingCancellationView';
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
    <title><?= __('page_pending_cancel') ?> | SMC</title>
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
        <h1 data-i18n="page_pending_cancel"><?= __('page_pending_cancel') ?></h1>
        <!-- <p class="page-subtitle">Rà soát nguyên nhân lỗi từ Đùn, QC, Cuộn và xác nhận hoàn tất hủy Bobin</p> -->
    </div>

    <div class="container">
        <!-- Khung camera QR -->
        <div id="qr-reader"></div>

        <!-- CONTROL BAR GỌN GÀNG -->
        <div class="control-bar-modern">
            <?php
            $canExtrusion = AuthHelper::hasPermission('extrusion_create');
            $pendingBackUrl = $canExtrusion ? '/WEB_BOBIN/public/index.php?url=bobin/index' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $pendingBackUrl ?>" class="btn-back-modern" title="Quay lại">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listPendingCancellationView">
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

                        <?php
                        $exportParams = $_GET;
                        unset($exportParams['page']);
                        $exportParams['url'] = 'bobin/exportPendingCancellationExcel';
                        $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
                        ?>
                        <a href="<?= $exportUrl ?>" class="btn-modern btn-export" id="btnExportPendingExcel"
                            title="Xuất toàn bộ kết quả lọc ra file Excel">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <span data-i18n="btn_export_excel"><?= __('btn_export_excel') ?? 'Xuất Excel' ?></span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- BỘ LỌC VÀ THỐNG KÊ ĐỒNG BỘ -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <h2>Kết quả lọc danh sách chờ hủy: <span><?= number_format($filteredPendingCount) ?></span> Bobin</h2>
                <a href="<?= buildFilterUrl(['bobin_size' => 'all', 'bobin_type' => 'all', 'rack' => 'all', 'keyword' => null, 'employee_code' => null, 'product_code' => null, 'qc_defect' => 'all', 'machine' => null, 'winding_machine' => null]) ?>"
                    class="btn-reset-filter">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

            <div class="filter-grid-row">
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

                <!-- LỌC THEO MÁY ĐÙN (TASK-051) -->
                <div class="filter-col">
                    <div class="filter-label"> Máy đùn:</div>
                    <select id="machineFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <option value="<?= buildFilterUrl(['machine' => 'all', 'page' => 1]) ?>"
                            <?= ($currentMachine === '' || $currentMachine === 'all') ? 'selected' : '' ?>>
                            Tất cả máy đùn
                        </option>
                        <?php foreach ($machines as $m):
                            $mName = is_object($m) ? ($m->machine_name ?? '') : ($m['machine_name'] ?? '');
                            if (empty($mName)) continue;
                        ?>
                            <option value="<?= buildFilterUrl(['machine' => $mName, 'page' => 1]) ?>"
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
                        <option value="<?= buildFilterUrl(['winding_machine' => 'all', 'page' => 1]) ?>"
                            <?= ($currentWindingMachine === '' || $currentWindingMachine === 'all') ? 'selected' : '' ?>>
                            Tất cả máy cuộn
                        </option>
                        <?php foreach ($windingMachines as $wm):
                            $wmName = is_object($wm) ? ($wm->machine_name ?? '') : ($wm['machine_name'] ?? '');
                            if (empty($wmName)) continue;
                        ?>
                            <option value="<?= buildFilterUrl(['winding_machine' => $wmName, 'page' => 1]) ?>"
                                <?= $currentWindingMachine === $wmName ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wmName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php require ROOT_PATH . '/app/views/components/advancedBobinFilterAndStats.php'; ?>
        </div>

        <!-- LIST CARD -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chờ hủy: <span class="counter-badge"
                        id="pendingCounterBadge"><?= $filteredPendingCount ?></span> Bobin</h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Hiện tại không có Bobin nào trong danh sách chờ hủy.</div>
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

                        // Xác định trạng thái trước đó (previous_status) và trạng thái đích khi Khôi phục (Restore)
                        $previousStatus = $item['previous_status'] ?? '';
                        if (in_array($previousStatus, ['Busy_Unchecked', 'Busy_Checked', 'Rolled'], true)) {
                            $targetRestoreStatus = $previousStatus;
                        } elseif (!empty($winding_employeeCode) && $winding_employeeCode !== 'Chưa cập nhật' && $winding_employeeCode !== 'Chưa kiểm') {
                            $targetRestoreStatus = 'Busy_Checked';
                        } else {
                            $targetRestoreStatus = 'Busy_Unchecked';
                        }

                        $targetStatusI18nKey = match ($targetRestoreStatus) {
                            'Busy_Unchecked' => 'status_busy_unchecked',
                            'Busy_Checked'   => 'status_busy_checked',
                            'Rolled'         => 'status_rolled',
                            default          => 'status_busy_unchecked'
                        };
                        $targetStatusLabel = __($targetStatusI18nKey);
                        ?>

                        <div class="bobin-item status_pending_cancellation">
                            <!-- CARD TOP: TIÊU ĐỀ CAM HỔ PHÁCH -->
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
                                <div class="status-badges-wrap">
                                    <span class="prev-status-badge"
                                        title="<?= htmlspecialchars(__('restore_target_label') . ' ' . $targetStatusLabel) ?>">
                                        <span data-i18n="restore_target_label"><?= __('restore_target_label') ?></span>
                                        <strong
                                            data-i18n="<?= $targetStatusI18nKey ?>"><?= htmlspecialchars($targetStatusLabel) ?></strong>
                                    </span>
                                    <div class="status-badge">
                                        ĐANG CHỜ HỦY
                                    </div>
                                </div>
                            </div>

                            <!-- INFO GRID: 12 THÔNG SỐ SẢN XUẤT -->
                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub">
                                        <span
                                            class="bobin-highlight-product <?= getProductBadgeClass($productCode) ?>"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
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
                                    <div class="val-sub">
                                        <span class="bobin-type-badge <?= getBobinTypeBadgeClass($item['bobin_type'] ?? '') ?>"
                                            data-type="<?= htmlspecialchars($item['bobin_type'] ?? '') ?>"
                                            title="<?= htmlspecialchars($item['bobin_type'] ?? '') ?>"
                                            style="<?= getBobinTypeBadgeStyle($item['bobin_type'] ?? '') ?>">
                                            <?= htmlspecialchars(!empty($item['bobin_type']) ? $item['bobin_type'] : 'Chưa cập nhật') ?>
                                        </span>
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
                                <!-- CỘT 1: ĐÙN CHECK -->
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

                                <!-- CỘT 2: QC CHECK -->
                                <div class="pipeline-col qc-col">
                                    <div class="pipeline-title">
                                        <span>🛡️ QC Check</span>
                                        <span
                                            class="pipeline-submeta"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa kiểm') ?></span>
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
                                        📝
                                        <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Không có ghi chú' : $defects['note']) ?>
                                    </div>
                                </div>

                                <!-- CỘT 3: THÔNG TIN CUỘN -->
                                <div class="pipeline-col winding-col">
                                    <div class="pipeline-title">
                                        <span>📍 Thông tin cuộn</span>
                                        <span class="pipeline-submeta"><?= htmlspecialchars($winding_machine) ?> |
                                            <?= htmlspecialchars($winding_employeeCode) ?></span>
                                    </div>
                                    <div class="winding-info-mini">
                                        <span>Họ tên NV: <strong><?= htmlspecialchars($winding_employeeName) ?></strong></span>
                                        <span>Thông khí: <strong><?= htmlspecialchars($flow_test_result) ?></strong></span>
                                    </div>
                                    <div class="winding-note-text">
                                        📝
                                        <?= htmlspecialchars(trim($winding_note) === '' ? 'Không có ghi chú' : $winding_note) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- FOOTER: KHÔI PHỤC BOBIN & HOÀN TẤT HỦY BOBIN -->
                            <div class="card-footer-simple" style="flex-wrap: wrap;">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 <span class="val-text"
                                            data-i18n="pending_request_time"><?= __('pending_request_time') ?></span>
                                        <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>
                                <div class="button-group">
                                    <?php if ($canEditPendingCancel): ?>
                                        <button type="button" class="btn-restore-bobin" data-i18n="btn_restore_bobin_action"
                                            onclick="handleRestore(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code'] ?? '') ?>', '<?= htmlspecialchars($targetStatusI18nKey) ?>')">
                                            <?= __('btn_restore_bobin_action') ?>
                                        </button>
                                        <button type="button" class="btn-cancel-final" data-i18n="btn_final_cancel_bobin"
                                            onclick="handleDelete(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code'] ?? '') ?>')">
                                            <?= __('btn_final_cancel_bobin') ?>
                                        </button>
                                    <?php elseif (!empty($_SESSION['user'])): ?>
                                        <span class="admin-only-note" data-i18n="pending_view_only_notice">
                                            <?= __('pending_view_only_notice') ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="/WEB_BOBIN/public/index.php?url=auth/login" class="btn-cancel-final"
                                            style="text-decoration: none; background: #64748b; font-size: 12px;">
                                            🔒 Đăng nhập để thao tác
                                        </a>
                                    <?php endif; ?>
                                </div>

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

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/delete.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/autoSearchBobin.js?v=<?= time() ?>"></script>
</body>

</html>