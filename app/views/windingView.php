<?php
$bobins = $data['bobins'] ?? [];
$pagination = $data['pagination'] ?? [];
$pendingCount = $data['pendingCount'] ?? 0;
$userRole = $_SESSION['user']['role'] ?? '';

// Lấy thông tin tài khoản đăng nhập hiện tại
$currentEmpCode = $_SESSION['user']['employee_code'] ?? '';
$currentEmpName = $_SESSION['user']['employee_name'] ?? '';

$baseUrl = defined('BASE_URL') ? BASE_URL : '/WEB_BOBIN/public';

$machines = $data['machines'] ?? (class_exists('GlobalData') ? (GlobalData::$listExtrusionMachineEntity ?? []) : []);
$windingMachines = $data['windingMachines'] ?? (class_exists('GlobalData') ? (GlobalData::$listWindingMachineEntity ?? []) : []);
$productList = $data['products'] ?? (class_exists('GlobalData') ? (GlobalData::$listProductEntity ?? []) : []);

// Chuẩn bị danh sách mã sản phẩm duy nhất
$productCodes = [];
foreach ($productList as $p) {
    $pCode = is_object($p) ? ($p->product_code ?? '') : ($p['product_code'] ?? '');
    $pCode = trim((string)$pCode);
    if (!empty($pCode) && !in_array($pCode, $productCodes, true)) {
        $productCodes[] = $pCode;
    }
}
sort($productCodes, SORT_NATURAL | SORT_FLAG_CASE);

$filters = $data['filters'] ?? [
    'keyword'         => trim((string)($_GET['keyword'] ?? '')),
    'machine'         => trim((string)($_GET['machine'] ?? '')),
    'winding_machine' => trim((string)($_GET['winding_machine'] ?? '')),
    'product_code'    => trim((string)($_GET['product_code'] ?? '')),
    'bobin_type'      => trim((string)($_GET['bobin_type'] ?? '')),
];
$hasActiveFilter = (!empty($filters['keyword']) || !empty($filters['machine']) || !empty($filters['winding_machine']) || !empty($filters['product_code']) || (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all'));

$bobinTypeOptions = [
    'all'                                => 'Tất cả loại Bobin',
    'Sản xuất'                           => 'Sản xuất',
    'Bù'                                 => 'Bù',
    'Điều chỉnh (Do CP)'                 => 'Điều chỉnh (Do CP)',
    'Điều chỉnh (Ngoại quan: Gel)'       => 'Điều chỉnh (Gel)',
    'Điều chỉnh (Ngoại quan: Dị vật)'   => 'Điều chỉnh (Dị vật)',
    'Điều chỉnh (Ngoại quan: Trầy)'      => 'Điều chỉnh (Trầy)',
    'Điều chỉnh (Ngoại quan: Biến dạng)' => 'Điều chỉnh (Biến dạng)',
    'Điều chỉnh (Ngoại quan: Xước)'      => 'Điều chỉnh (Xước)',
    'Điều chỉnh (Ngoại quan: Chữ in)'    => 'Điều chỉnh (Chữ in)',
    'Điều chỉnh (Ngoại quan: Vón cục)'   => 'Điều chỉnh (Vón cục)',
    'Điều chỉnh (Ngoại quan: Màu)'       => 'Điều chỉnh (Màu)',
];

if (!function_exists('buildWindingFilterUrl')) {
    function buildWindingFilterUrl(array $newParams = []): string
    {
        $query = $_GET;
        $query['url'] = 'bobin/windingView';
        foreach ($newParams as $k => $v) {
            if ($v === '' || $v === null || $v === 'all') {
                unset($query[$k]);
            } else {
                $query[$k] = $v;
            }
        }
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/WEB_BOBIN/public';
        return $baseUrl . '/index.php?' . http_build_query($query);
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
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_winding') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_Winding.css?v=<?= time() ?>">
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
        <h1 data-i18n="page_winding"><?= __('page_winding') ?></h1>
        <!-- <p class="page-subtitle">Xác nhận thông tin máy cuộn và hoàn tất chu kỳ sản xuất</p> -->
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- THANH CÔNG CỤ TÌM KIẾM & BỘ LỌC ĐỒNG BỘ HIỆN ĐẠI -->
        <div class="control-bar-modern">
            <form method="GET" action="<?= $baseUrl ?>/index.php" class="filter-form-modern" id="filterForm">
                <input type="hidden" name="url" value="bobin/windingView">
                <div class="control-row">
                    <!-- TÌM KIẾM TỪ KHÓA -->
                    <div class="search-box-modern">
                        <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                            stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="<?= __('ph_scan_bobin') ?>"
                            data-i18n-ph="ph_scan_bobin" value="<?= htmlspecialchars($filters['keyword']) ?>"
                            autocomplete="off">
                    </div>

                    <!-- LỌC THEO MÁY ĐÙN -->
                    <div class="filter-group">
                        <select name="machine" id="filterMachine" class="filter-select-modern"
                            onchange="this.form.submit()">
                            <option value="" data-i18n="opt_all_machines">
                                <?= __('opt_all_machines') ?? 'Tất cả máy đùn' ?></option>
                            <?php foreach ($machines as $m):
                                $mName = is_object($m) ? ($m->machine_name ?? '') : ($m['machine_name'] ?? '');
                                if (empty($mName)) continue;
                            ?>
                                <option value="<?= htmlspecialchars($mName) ?>"
                                    <?= ($filters['machine'] === $mName) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- LỌC THEO LOẠI BOBIN -->
                    <div class="filter-group">
                        <select name="bobin_type" id="filterBobinType" class="filter-select-modern"
                            onchange="this.form.submit()">
                            <option value="" data-i18n="opt_all_bobin_types">
                                <?= __('opt_all_bobin_types') ?? 'Tất cả loại Bobin' ?></option>
                            <?php foreach ($bobinTypeOptions as $tKey => $tLabel):
                                if ($tKey === 'all' || $tKey === '') continue;
                            ?>
                                <option value="<?= htmlspecialchars($tKey) ?>"
                                    <?= ($filters['bobin_type'] === $tKey) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <!-- LỌC THEO MÃ SẢN PHẨM (SUGGESTION TEXTFIELD) -->
                    <div class="filter-group">
                        <input type="text" name="product_code" id="filterProductCode"
                            class="filter-input-modern filter-product-suggestion"
                            placeholder="<?= __('ph_product_code') ?? 'Mã sản phẩm...' ?>"
                            data-i18n-ph="ph_product_code" value="<?= htmlspecialchars($filters['product_code']) ?>"
                            autocomplete="off">
                        <div class="suggestion-box" id="winding_product_suggestions"></div>
                    </div>



                    <!-- NHÓM NÚT THAO TÁC -->
                    <div class="filter-actions-group">
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
                        <button class="btn-modern btn-filter" type="submit" id="btnFilter">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            <span data-i18n="filter"><?= __('filter') ?? 'Lọc' ?></span>
                        </button>
                        <?php if ($hasActiveFilter): ?>
                            <a href="<?= $baseUrl ?>/index.php?url=bobin/windingView" class="btn-modern btn-reset-filter"
                                data-i18n="[title]reset_filter" title="<?= __('reset_filter') ?? 'Xóa lọc' ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span data-i18n="reset_filter"><?= __('reset_filter') ?? 'Xóa lọc' ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- DANH SÁCH BOBIN CHỜ CUỘN -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chờ cuộn: <span
                        class="counter-badge"><?= (int)($pagination['totalRecords'] ?? count($bobins)) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Hiện tại không có Bobin nào đang chờ cuộn.</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
                        $itemProducts = json_decode($item['products'] ?? '{}', true) ?: [];
                        $productCode = $itemProducts['product_code'] ?? 'Chưa cập nhật';

                        $extrusionEmployee = json_decode($item['extrusion_employee'] ?? '{}', true) ?: [];
                        $extEmpCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extEmpName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';

                        $matLotData = json_decode($item['material_lot'] ?? '{}', true) ?: [];
                        $matLot = $matLotData['lot'] ?? 'Chưa cập nhật';

                        $rackData = json_decode($item['rack'] ?? '{}', true) ?: [];
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';

                        $extCheck = json_decode($item['extrusion_check'] ?? '{}', true) ?: [];
                        $vi = json_decode($item['visual_inspection'] ?? '{}', true) ?: [];
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

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finishTime = $item['finish_time'] ?? '';
                        $formatted_time = !empty($finishTime) ? date('dmY$His', strtotime($finishTime)) : '';
                        $mainInfoText = "{$productCode}\${$matLot}\${$printLot}\${$formatted_time}";

                        $rawStatus = $item['bobin_current_status'] ?? 'Busy_Checked';
                        $statusClass = strtolower($rawStatus) === 'rolled' ? 'status_rolled' : 'status_busy_checked';
                        $displayStatus = strtolower($rawStatus) === 'rolled' ? 'ĐÃ CUỘN' : 'ĐÃ KIỂM TRA QC';
                        ?>

                        <div class="bobin-item <?= $statusClass ?>" data-key="<?= htmlspecialchars($item['bobin_key_code']) ?>"
                            data-ident="<?= htmlspecialchars($item['bobin_identification_code']) ?>">

                            <!-- CARD TOP -->
                            <div class="card-top">
                                <div class="key-info">
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                    <span class="key-code"><?= htmlspecialchars($item['bobin_key_code']) ?></span>
                                </div>
                                <div class="main-info-wrapper">
                                    <span class="main-info"><?= htmlspecialchars($mainInfoText) ?></span>
                                    <button type="button" class="btn-copy" data-copy="<?= htmlspecialchars($mainInfoText) ?>"
                                        onclick="copyToClipboard(this)">📋 Copy</button>
                                </div>
                                <div class="status-badge"><?= $displayStatus ?></div>
                            </div>

                            <!-- 12 THÔNG SỐ SẢN XUẤT -->
                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub">
                                        <span
                                            class="bobin-highlight-product <?= getProductBadgeClass($productCode) ?>"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Mã NV Đùn</label>
                                    <div class="val-sub"><?= htmlspecialchars($extEmpCode) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Họ tên NV Đùn</label>
                                    <div class="val-sub"><?= htmlspecialchars($extEmpName) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Vị trí RACK</label>
                                    <?php
                                    $isRackEmpty = empty($rackCode) || $rackCode === 'Chưa cập nhật' || $rackCode === '---';
                                    ?>
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
                                    <label>Ca sản xuất</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['shift'] ?? 'Chưa cập nhật') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Kích thước</label>
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
                                    <div class="val-sub"><?= htmlspecialchars($matLot) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub">
                                        <span
                                            class="bobin-highlight-printlot <?= empty($printLot) || $printLot === 'Chưa cập nhật' ? 'lot-empty' : '' ?>">
                                            <?= htmlspecialchars($printLot) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="field-item highlight-box">
                                    <label>Chiều dài (m)</label>
                                    <div class="val-highlight"><?= number_format($item['length_m'] ?? 0) ?> m</div>
                                </div>
                                <div class="field-item">
                                    <label>Ngày đùn</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['extrusion_date'] ?? '') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Thời điểm hoàn thành</label>
                                    <div class="val-sub"><?= htmlspecialchars($finishTime) ?></div>
                                </div>
                            </div>

                            <!-- DẢI THÔNG TIN KIỂM TRA ĐÙN & QC (THAM KHẢO) -->
                            <div class="inspection-review-container">
                                <!-- ĐÙN TỰ KIỂM TRA -->
                                <div class="inspection-strip">
                                    <span class="strip-label">🏭 Đùn tự kiểm tra:</span>
                                    <div class="qc-badges">
                                        <?php
                                        $extChecks = [
                                            'crit_diameter'    => ['label' => 'Đường kính', 'ok' => (bool)($extCheck['diameter'] ?? true)],
                                            'crit_gel'         => ['label' => 'Gel',        'ok' => (bool)($extCheck['gel'] ?? true)],
                                            'crit_foreign'     => ['label' => 'Dị vật',     'ok' => (bool)($extCheck['foreign_object'] ?? true)],
                                            'crit_color'       => ['label' => 'Màu sắc',    'ok' => (bool)($extCheck['color'] ?? true)],
                                            'crit_print'       => ['label' => 'Chữ in',     'ok' => (bool)($extCheck['print'] ?? true)],
                                            'crit_clump'       => ['label' => 'Vón cục',    'ok' => (bool)($extCheck['clump'] ?? $extCheck['lump'] ?? true)],
                                            'crit_scratch'     => ['label' => 'Trầy',      'ok' => (bool)($extCheck['scratch'] ?? true)],
                                            'crit_streak'      => ['label' => 'Xước',      'ok' => (bool)($extCheck['streak'] ?? $extCheck['scuff'] ?? true)],
                                            'crit_deformation' => ['label' => 'Biến dạng', 'ok' => (bool)($extCheck['deformation'] ?? $extCheck['deformed'] ?? true)]
                                        ];
                                        foreach ($extChecks as $i18nKey => $ec):
                                        ?>
                                            <div class="vi-item <?= $ec['ok'] ? 'badge-ok' : 'badge-ng' ?>">
                                                <span
                                                    data-i18n="<?= $i18nKey ?>"><?= __($i18nKey) ?></span><strong><?= $ec['ok'] ? 'OK' : 'NG' ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- QC ĐÁNH GIÁ -->
                                <div class="inspection-strip qc-strip">
                                    <div class="strip-title-wrapper">
                                        <span class="strip-label">🛡️ QC đánh giá:</span>
                                        <span class="inspector-badge">NV:
                                            <?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa rõ') ?></span>
                                    </div>
                                    <div class="qc-badges">
                                        <div class="vi-item <?= ($defects['gel'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Gel</span><strong><?= ($defects['gel'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= ($defects['foreign_object'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Dị
                                                vật</span><strong><?= ($defects['foreign_object'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= ($defects['color_issue'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Màu</span><strong><?= ($defects['color_issue'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= ($defects['print_quality'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Chữ
                                                in</span><strong><?= ($defects['print_quality'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= (($defects['clump'] ?? false) || ($defects['lump'] ?? false) || ($vi['defect_clump'] ?? false)) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Vón
                                                cục</span><strong><?= (($defects['clump'] ?? false) || ($defects['lump'] ?? false) || ($vi['defect_clump'] ?? false)) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= (($defects['scratch'] ?? false) || ($vi['defect_scratch'] ?? false)) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Trầy</span><strong><?= (($defects['scratch'] ?? false) || ($vi['defect_scratch'] ?? false)) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= (($defects['streak'] ?? false) || ($defects['scuff'] ?? false) || ($vi['defect_streak'] ?? false)) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Xước</span><strong><?= (($defects['streak'] ?? false) || ($defects['scuff'] ?? false) || ($vi['defect_streak'] ?? false)) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div
                                            class="vi-item <?= (($defects['deformation'] ?? false) || ($defects['deformed'] ?? false) || ($vi['defect_deformation'] ?? false)) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Biến
                                                dạng</span><strong><?= (($defects['deformation'] ?? false) || ($defects['deformed'] ?? false) || ($vi['defect_deformation'] ?? false)) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                    </div>
                                    <?php if (!empty($defects['note']) && $defects['note'] !== 'Chưa cập nhật'): ?>
                                        <div class="qc-note-pill">
                                            <span>Ghi chú QC:</span> <?= htmlspecialchars($defects['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- KHU VỰC THAO TÁC CỦA NHÓM CUỘN (TƯƠNG ĐƯƠNG QC-SECTION) -->
                            <div class="winding-section">
                                <div class="winding-section-title">
                                    <span>📍 Ghi nhận thông tin công đoạn Cuộn</span>
                                    <span class="winding-time-now">🕒 Hiện tại: <?= date('d/m/Y H:i') ?></span>
                                </div>

                                <!-- HÀNG 1: MÁY CUỘN & NHÂN VIÊN -->
                                <div class="winding-form-row">
                                    <div class="winding-input-group machine-group">
                                        <label>Máy cuộn: <span class="required">*</span></label>
                                        <div class="suggestion-wrapper">
                                            <input type="text" class="winding-input winding-machine-name"
                                                placeholder="<?= __('ph_winding_machine_input') ?>"
                                                data-i18n-ph="ph_winding_machine_input" autocomplete="off">
                                            <div class="suggestion-box"></div>
                                        </div>
                                    </div>

                                    <!-- TỰ ĐỘNG ĐIỀN THEO TÀI KHOẢN ĐĂNG NHẬP -->
                                    <div class="winding-input-group">
                                        <label>Mã NV Cuộn:</label>
                                        <input type="text" class="winding-input winding-employee-code input-readonly"
                                            value="<?= htmlspecialchars($currentEmpCode) ?>" readonly>
                                    </div>
                                    <div class="winding-input-group">
                                        <label>Họ tên NV Cuộn:</label>
                                        <input type="text" class="winding-input winding-employee-name input-readonly"
                                            value="<?= htmlspecialchars($currentEmpName) ?>" readonly>
                                    </div>
                                </div>

                                <!-- HÀNG 2: HẠNG MỤC LỖI KHI HỦY & GHI CHÚ CUỘN -->
                                <div class="winding-bottom-row">
                                    <div class="winding-cancel-quick-group">
                                        <label
                                            data-i18n="winding_cancel_reason_card_lbl"><?= __('winding_cancel_reason_card_lbl') ?></label>
                                        <div class="winding-quick-reason-btns">
                                            <button type="button" class="card-error-toggle-btn" data-error="Ngoại quan"
                                                onclick="toggleCardCancelReason(this)">
                                                <span class="reason-icon">🔍</span>
                                                <span class="reason-text"
                                                    data-i18n="winding_reason_appearance"><?= __('winding_reason_appearance') ?></span>
                                                <span class="reason-check">NG</span>
                                            </button>
                                            <button type="button" class="card-error-toggle-btn" data-error="Thông khí"
                                                onclick="toggleCardCancelReason(this)">
                                                <span class="reason-icon">💨</span>
                                                <span class="reason-text"
                                                    data-i18n="winding_reason_airflow"><?= __('winding_reason_airflow') ?></span>
                                                <span class="reason-check">NG</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="winding-note">
                                        <label data-i18n="winding_note_lbl"><?= __('winding_note_lbl') ?></label>
                                        <input type="text" class="note-field winding-note-field"
                                            placeholder="<?= __('ph_winding_note_explain') ?>"
                                            data-i18n-ph="ph_winding_note_explain">
                                    </div>
                                </div>
                            </div>

                            <!-- CARD FOOTER & NÚT BẤM HOÀN TẤT -->
                            <div class="card-footer-simple">
                                <div class="update-time">🕒 Cập nhật lúc: <?= htmlspecialchars($item['updated_time'] ?? '') ?>
                                </div>
                                <div class="button-group">
                                    <button type="button" class="btn-cancel" data-i18n="btn_cancel_bobin"
                                        onclick="handleWindingCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        🗑️ <?= __('btn_cancel_bobin') ?>
                                    </button>
                                    <button type="button" class="btn-confirm" onclick="handleWindingConfirm(
                                            this,
                                            '<?= htmlspecialchars($item['bobin_identification_code']) ?>',
                                            '<?= htmlspecialchars($item['bobin_key_code']) ?>',
                                            '<?= htmlspecialchars($mainInfoText) ?>'
                                        )">
                                        💾 Xác nhận hoàn thành
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
                            <a href="<?= buildWindingFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="page-btn">‹
                                Trước</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                            <a href="<?= buildWindingFilterUrl(['page' => $p]) ?>"
                                class="page-btn <?= ($p === (int)$pagination['currentPage']) ? 'active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                            <a href="<?= buildWindingFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="page-btn">Sau
                                ›</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
        window.__FILTER_PRODUCT_CODES__ = <?= json_encode(array_values($productCodes), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/scanQR.js?v=<?= time() ?>"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pInput = document.getElementById('filterProductCode');
            const pBox = document.getElementById('winding_product_suggestions');
            const filterForm = document.getElementById('filterForm');
            if (!pInput || !pBox || !filterForm) return;

            const productCodes = window.__FILTER_PRODUCT_CODES__ || [];

            function renderSuggestions(val) {
                const query = (val || '').toLowerCase().trim();
                pBox.innerHTML = '';
                const matches = query === '' ?
                    productCodes.slice(0, 30) :
                    productCodes.filter(c => c.toLowerCase().includes(query)).slice(0, 30);

                if (matches.length === 0) {
                    pBox.style.display = 'none';
                    return;
                }

                const fragment = document.createDocumentFragment();
                matches.forEach(code => {
                    const item = document.createElement('div');
                    item.className = 'suggestion-item';
                    item.textContent = code;
                    item.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        pInput.value = code;
                        pBox.style.display = 'none';
                        filterForm.submit();
                    });
                    fragment.appendChild(item);
                });
                pBox.appendChild(fragment);
                pBox.style.display = 'block';
            }

            pInput.addEventListener('input', function() {
                renderSuggestions(this.value);
            });

            pInput.addEventListener('focus', function() {
                renderSuggestions(this.value);
            });

            document.addEventListener('click', function(e) {
                if (!pInput.contains(e.target) && !pBox.contains(e.target)) {
                    pBox.style.display = 'none';
                }
            });

            pInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    pBox.style.display = 'none';
                } else if (e.key === 'Enter') {
                    pBox.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>