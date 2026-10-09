<?php
// File: app/views/qcEditBobinView.php
// Trang Quản Trị - Điều Chỉnh Kết Quả Kiểm Tra Ngoại Quan QC (TASK-019 Enhanced)

$bobins       = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$pagination   = $data['pagination'] ?? [];
$userRole     = $_SESSION['user']['role'] ?? '';
$currentUrl   = $_GET['url'] ?? '';
$pCount       = $pendingCount ?? (class_exists('GlobalData') ? (GlobalData::$pendingBobinCount ?? 0) : 0);

$typeOptions = [
    'Sản xuất'                           => 'Sản xuất',
    'Bù'                                 => 'Bù',
    'Điều chỉnh (Do CP)'                 => 'Điều chỉnh (Do CP)',
    'Điều chỉnh (Ngoại quan: Gel)'       => 'Điều chỉnh (Ngoại quan: Gel)',
    'Điều chỉnh (Ngoại quan: Dị vật)'   => 'Điều chỉnh (Ngoại quan: Dị vật)',
    'Điều chỉnh (Ngoại quan: Trầy)'      => 'Điều chỉnh (Ngoại quan: Trầy)',
    'Điều chỉnh (Ngoại quan: Biến dạng)' => 'Điều chỉnh (Ngoại quan: Biến dạng)',
    'Điều chỉnh (Ngoại quan: Xước)'      => 'Điều chỉnh (Ngoại quan: Xước)',
    'Điều chỉnh (Ngoại quan: Chữ in)'    => 'Điều chỉnh (Ngoại quan: Chữ in)',
    'Điều chỉnh (Ngoại quan: Vón cục)'   => 'Điều chỉnh (Ngoại quan: Vón cục)',
    'Điều chỉnh (Ngoại quan: Màu)'       => 'Điều chỉnh (Ngoại quan: Màu)'
];

if (!function_exists('decodeJsonObject')) {
    function decodeJsonObject($value): array
    {
        if (!is_string($value) || trim($value) === '') return [];
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        $query = $_GET ?? [];
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/qcEditBobinView';
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

// Thống kê nhanh KPI theo tập bản ghi hiện tại
$qcPassCount = 0;
$qcDefectCount = 0;
foreach ($bobins as $b) {
    $viData = decodeJsonObject($b['visual_inspection'] ?? '');
    $hasDefect = ($viData['defect_gel'] ?? false) || ($viData['defect_foreign_object'] ?? false) || ($viData['defect_color_issue'] ?? false) || ($viData['defect_print_quality'] ?? false);
    if ($hasDefect) {
        $qcDefectCount++;
    } else {
        $qcPassCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_qc_edit') ?> | SMC WEB_BOBIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS Offline Local -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/qcEditBobin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/scanQR.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">

    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/qrScannerHelper.js?v=<?= time() ?>"></script>
</head>

<body>
    <!-- Vertical Left Sidebar & Header -->
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <div class="qc-container">
        <!-- 1. HERO HEADER -->
        <header class="qc-hero">
            <div class="qc-hero-title-area">
                <h1>
                    <span>🛡️</span>
                    <span data-i18n="page_qc_edit"><?= __('page_qc_edit') ?></span>
                </h1>
                <p class="qc-hero-subtitle" data-i18n="qc_edit_subtitle"><?= __('qc_edit_subtitle') ?></p>
            </div>
            <div class="qc-hero-badge">
                <span>🔍</span>
                <span>QC INSPECTION EDIT</span>
            </div>
        </header>

        <!-- 2. KPI STATS ROW -->
        <section class="qc-stats-grid">
            <div class="qc-stat-card stat-total">
                <div class="qc-stat-icon-wrap">📊</div>
                <div class="qc-stat-info">
                    <span class="qc-stat-val"><?= number_format($totalRecords) ?></span>
                    <span class="qc-stat-label" data-i18n="qc_kpi_total"><?= __('qc_kpi_total') ?></span>
                </div>
            </div>

            <div class="qc-stat-card stat-pass">
                <div class="qc-stat-icon-wrap">✨</div>
                <div class="qc-stat-info">
                    <span class="qc-stat-val"><?= number_format($qcPassCount) ?></span>
                    <span class="qc-stat-label" data-i18n="qc_kpi_pass"><?= __('qc_kpi_pass') ?></span>
                </div>
            </div>

            <div class="qc-stat-card stat-defect">
                <div class="qc-stat-icon-wrap">⚠️</div>
                <div class="qc-stat-info">
                    <span class="qc-stat-val"><?= number_format($qcDefectCount) ?></span>
                    <span class="qc-stat-label" data-i18n="qc_kpi_defect"><?= __('qc_kpi_defect') ?></span>
                </div>
            </div>

            <div class="qc-stat-card stat-checked">
                <div class="qc-stat-icon-wrap">🛡️</div>
                <div class="qc-stat-info">
                    <span class="qc-stat-val">BUSY_CHECKED</span>
                    <span class="qc-stat-label" data-i18n="qc_kpi_checked"><?= __('qc_kpi_checked') ?></span>
                </div>
            </div>
        </section>

        <!-- QR SCANNER CONTAINER -->
        <div id="qr-reader"></div>

        <!-- 3. CONTROL TOOLBAR -->
        <section class="qc-toolbar">
            <?php
            $backUrl = AuthHelper::hasPermission('qc_check') ? '/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $backUrl ?>" class="qc-back-btn" title="Quay lại">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="qc-search-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/qcEditBobinView">

                <div class="qc-search-wrap">
                    <svg class="qc-search-icon" viewBox="0 0 24 24">
                        <path
                            d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" id="searchKeyword" class="qc-search-input"
                        placeholder="<?= __('ph_scan_bobin') ?>" data-i18n-ph="ph_scan_bobin"
                        data-i18n="[placeholder]ph_scan_bobin" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                        autocomplete="off">
                </div>

                <button type="button" id="btnScanQR" class="qc-btn-action qc-btn-scan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <path d="M3 14h7v7H3z"></path>
                    </svg>
                    <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?></span>
                </button>

                <button type="submit" class="qc-btn-action qc-btn-filter" id="btnFilter" data-i18n="filter">
                    <span>🔍</span>
                    <span><?= __('filter') ?></span>
                </button>
            </form>
        </section>

        <!-- 4. BOBIN LIST CARDS -->
        <?php if (empty($bobins)): ?>
            <div class="qc-empty-box">
                <div class="qc-empty-icon">📦</div>
                <div class="qc-empty-title" data-i18n="qc_empty_list"><?= __('qc_empty_list') ?></div>
            </div>
        <?php else: ?>
            <div class="qc-list-wrapper bobin-list">
                <?php foreach ($bobins as $item): ?>
                    <?php
                    $products = decodeJsonObject($item['products'] ?? '');
                    $productCode = $products['product_code'] ?? 'Chưa cập nhật';

                    $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                    $extEmpCode = $extrusionEmployee['employee_code'] ?? '---';
                    $extEmpName = $extrusionEmployee['employee_name'] ?? '---';

                    $matLotData = decodeJsonObject($item['material_lot'] ?? '');
                    $matLot = $matLotData['lot'] ?? '---';

                    $rackData = decodeJsonObject($item['rack'] ?? '');
                    $rackCode = $rackData['code'] ?? '---';

                    $vi = decodeJsonObject($item['visual_inspection'] ?? '');

                    $chkGel           = !($vi['defect_gel'] ?? false);
                    $chkForeignObject = !($vi['defect_foreign_object'] ?? false);
                    $chkColor         = !($vi['defect_color_issue'] ?? false);
                    $chkPrint         = !($vi['defect_print_quality'] ?? false);

                    $inspectorCode = $vi['inspector_code'] ?? '';
                    $inspectorName = $vi['inspector_name'] ?? '';
                    $defectNote    = $vi['defect_note'] ?? '';

                    $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                    ?>
                    <div class="qc-card bobin-item status_busy_checked"
                        data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">

                        <!-- Card Header -->
                        <div class="qc-card-header card-top">
                            <div class="qc-id-group key-info">
                                <span
                                    class="qc-id-badge id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                <span class="qc-key-code key-code"><?= htmlspecialchars($item['bobin_key_code']) ?></span>
                            </div>
                            <div class="qc-status-badge status-badge">
                                <span>🛡️</span>
                                <span data-i18n="status_busy_checked"><?= __('status_busy_checked') ?></span>
                            </div>
                        </div>

                        <!-- Card Body (2 Columns) -->
                        <div class="qc-card-body info-grid">
                            <!-- Left: Bobin Specs Panel -->
                            <div class="qc-specs-panel">
                                <div class="qc-field field-item">
                                    <label class="qc-label" data-i18n="field_product"><?= __('field_product') ?></label>
                                    <div class="qc-val val-sub">
                                        <span class="qc-tag-product font-bold-blue"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
                                </div>

                                <div class="qc-field field-item">
                                    <label class="qc-label" data-i18n="field_print_lot"><?= __('field_print_lot') ?></label>
                                    <div class="qc-val val-sub">
                                        <span
                                            class="qc-tag-printlot highlight-printlot-text"><?= htmlspecialchars($printLot) ?></span>
                                    </div>
                                </div>

                                <div class="qc-field field-item">
                                    <label class="qc-label" data-i18n="field_length"><?= __('field_length') ?></label>
                                    <div class="qc-val val-highlight">
                                        <span class="qc-tag-length"><?= number_format($item['length_m'] ?? 0) ?> m</span>
                                    </div>
                                </div>

                                <div class="qc-field field-item">
                                    <label class="qc-label" data-i18n="field_rack"><?= __('field_rack') ?></label>
                                    <div class="qc-val val-sub">
                                        <span class="qc-tag-rack val-rack"><?= htmlspecialchars($rackCode) ?></span>
                                    </div>
                                </div>

                                <div class="qc-field span-2 field-item">
                                    <label class="qc-label" data-i18n="field_bobin_type"><?= __('field_bobin_type') ?></label>
                                    <select class="qc-input-control input-field select-bobin-type" disabled>
                                        <?php foreach ($typeOptions as $val => $text): ?>
                                            <option value="<?= htmlspecialchars($val) ?>"
                                                <?= ($item['bobin_type'] ?? '') === $val ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($text) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Right: QC Inspection Panel -->
                            <div class="qc-inspect-panel">
                                <div class="qc-criteria-header ext-title">
                                    <span>🛡️</span>
                                    <span data-i18n="qc_criteria_title"><?= __('qc_criteria_title') ?></span>
                                </div>

                                <!-- 4 Criteria Toggle Buttons -->
                                <div class="qc-criteria-grid ext-checks-group">
                                    <?php
                                    $qcChecks = [
                                        'gel'            => ['label' => 'Gel',     'val' => $chkGel],
                                        'foreign_object' => ['label' => 'Dị vật',  'val' => $chkForeignObject],
                                        'color'          => ['label' => 'Màu sắc', 'val' => $chkColor],
                                        'print'          => ['label' => 'Chữ in',  'val' => $chkPrint]
                                    ];
                                    foreach ($qcChecks as $key => $c):
                                        $isOk = (bool)$c['val'];
                                    ?>
                                        <div class="qc-criteria-item ext-check-box">
                                            <span class="qc-criteria-name check-box-label"><?= $c['label'] ?></span>
                                            <button type="button" class="qc-toggle-btn ext-toggle-btn <?= $isOk ? 'active' : '' ?>"
                                                data-field="qc_check_<?= $key ?>" data-value="<?= $isOk ? 'true' : 'false' ?>"
                                                onclick="toggleQCCheck(this)" disabled>
                                                <?= $isOk ? 'OK' : 'NG' ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- QC Inspector & Notes -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="qc-field field-item suggestion-wrapper">
                                        <label class="qc-label"
                                            data-i18n="field_inspector_code"><?= __('field_inspector_code') ?></label>
                                        <input class="qc-input-control input-field input-inspector-code" type="text"
                                            autocomplete="off" value="<?= htmlspecialchars($inspectorCode) ?>" disabled>
                                    </div>

                                    <div class="qc-field field-item">
                                        <label class="qc-label"
                                            data-i18n="field_inspector_name"><?= __('field_inspector_name') ?></label>
                                        <input class="qc-input-control input-field input-readonly input-inspector-name"
                                            type="text" value="<?= htmlspecialchars($inspectorName) ?>" readonly tabindex="-1">
                                    </div>
                                </div>

                                <div class="qc-field field-item">
                                    <label class="qc-label" data-i18n="field_defect_note"><?= __('field_defect_note') ?></label>
                                    <input class="qc-input-control input-field qc-note-input" type="text"
                                        placeholder="<?= __('qc_note_ph') ?>" data-i18n-ph="qc_note_ph"
                                        data-i18n="[placeholder]qc_note_ph" value="<?= htmlspecialchars($defectNote) ?>"
                                        disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="qc-card-footer card-footer-simple">
                            <?php if (!empty($item['updated_time'])): ?>
                                <div class="qc-update-time update-time">
                                    <span>🕒</span>
                                    <span><?= __('emp_updated_prefix') ?> <?= htmlspecialchars($item['updated_time']) ?></span>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>

                            <div class="qc-btn-group button-group">
                                <button type="button" class="qc-btn-edit btn-edit" onclick="startQCEdit(this)">
                                    <span>✏️</span>
                                    <span data-i18n="qc_btn_edit"><?= __('qc_btn_edit') ?></span>
                                </button>
                                <button type="button" class="qc-btn-cancel btn-cancel-edit" style="display: none;"
                                    onclick="cancelQCEdit(this)">
                                    <span>✕</span>
                                    <span data-i18n="qc_btn_cancel"><?= __('qc_btn_cancel') ?></span>
                                </button>
                                <button type="button" class="qc-btn-save btn-confirm" style="display: none;"
                                    onclick="handleQCConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                    <span>💾</span>
                                    <span data-i18n="qc_btn_save"><?= __('qc_btn_save') ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 5. PHÂN TRANG (PAGINATION) -->
            <?php if (isset($pagination['totalPages']) && $pagination['totalPages'] > 1): ?>
                <nav class="qc-pagination pagination-wrapper">
                    <?php if ($pagination['currentPage'] > 1): ?>
                        <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="qc-page-btn page-btn">
                            ‹ Trước
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $pagination['currentPage'] - 2);
                    $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    for ($p = $start; $p <= $end; $p++):
                        $isActive = ($p === (int)$pagination['currentPage']);
                    ?>
                        <a href="<?= buildFilterUrl(['page' => $p]) ?>"
                            class="qc-page-btn page-btn <?= $isActive ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                        <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="qc-page-btn page-btn">
                            Sau ›
                        </a>
                    <?php endif; ?>

                    <span class="qc-page-info page-info">
                        Trang <?= $pagination['currentPage'] ?> / <?= $pagination['totalPages'] ?> (Tổng
                        <?= number_format($totalRecords) ?> Bobin)
                    </span>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- JS Variables & Script Modules -->
    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/QC/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>