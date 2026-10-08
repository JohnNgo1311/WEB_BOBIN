<?php
// File: app/views/windingEditBobinView.php
// Trang Quản Trị - Điều Chỉnh Thông Tin Công Đoạn Cuộn (TASK-019 Enhanced)

$bobins       = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$pagination   = $data['pagination'] ?? [];
$userRole     = $_SESSION['user']['role'] ?? '';
$currentUrl   = $_GET['url'] ?? '';
$pCount       = $pendingCount ?? (class_exists('GlobalData') ? (GlobalData::$pendingBobinCount ?? 0) : 0);

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
            $query['url'] = 'bobin/windingEditBobinView';
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
$flowOkCount = 0;
$flowNgCount = 0;
foreach ($bobins as $b) {
    if (($b['flow_test_result'] ?? '') === 'Thất bại') {
        $flowNgCount++;
    } else {
        $flowOkCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_winding_edit') ?> | SMC WEB_BOBIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS Offline Local -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/windingEditBobin.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">

    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- Vertical Left Sidebar & Header -->
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <div class="wnd-container">
        <!-- 1. HERO HEADER -->
        <header class="wnd-hero">
            <div class="wnd-hero-title-area">
                <h1>
                    <span>📍</span>
                    <span data-i18n="page_winding_edit"><?= __('page_winding_edit') ?></span>
                </h1>
                <p class="wnd-hero-subtitle" data-i18n="winding_edit_subtitle"><?= __('winding_edit_subtitle') ?></p>
            </div>
            <div class="wnd-hero-badge">
                <span>⚡</span>
                <span>WINDING PROCESS EDIT</span>
            </div>
        </header>

        <!-- 2. KPI STATS ROW -->
        <section class="wnd-stats-grid">
            <div class="wnd-stat-card stat-total">
                <div class="wnd-stat-icon-wrap">📦</div>
                <div class="wnd-stat-info">
                    <span class="wnd-stat-val"><?= number_format($totalRecords) ?></span>
                    <span class="wnd-stat-label" data-i18n="winding_kpi_total"><?= __('winding_kpi_total') ?></span>
                </div>
            </div>

            <div class="wnd-stat-card stat-flow-ok">
                <div class="wnd-stat-icon-wrap">💨</div>
                <div class="wnd-stat-info">
                    <span class="wnd-stat-val"><?= number_format($flowOkCount) ?></span>
                    <span class="wnd-stat-label" data-i18n="winding_kpi_flow_ok"><?= __('winding_kpi_flow_ok') ?></span>
                </div>
            </div>

            <div class="wnd-stat-card stat-flow-ng">
                <div class="wnd-stat-icon-wrap">⚠️</div>
                <div class="wnd-stat-info">
                    <span class="wnd-stat-val"><?= number_format($flowNgCount) ?></span>
                    <span class="wnd-stat-label" data-i18n="winding_kpi_flow_ng"><?= __('winding_kpi_flow_ng') ?></span>
                </div>
            </div>

            <div class="wnd-stat-card stat-recent">
                <div class="wnd-stat-icon-wrap">🕒</div>
                <div class="wnd-stat-info">
                    <span class="wnd-stat-val">ROLLED</span>
                    <span class="wnd-stat-label" data-i18n="winding_kpi_recent"><?= __('winding_kpi_recent') ?></span>
                </div>
            </div>
        </section>

        <!-- QR SCANNER CONTAINER -->
        <div id="qr-reader" style="display:none; max-width: 500px; margin: 0 auto 16px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.15);"></div>

        <!-- 3. CONTROL TOOLBAR -->
        <section class="wnd-toolbar">
            <?php 
                $backUrl = AuthHelper::hasPermission('winding_confirm') ? '/WEB_BOBIN/public/index.php?url=bobin/windingView' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $backUrl ?>" class="wnd-back-btn" title="Quay lại">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="wnd-search-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/windingEditBobinView">

                <div class="wnd-search-wrap">
                    <svg class="wnd-search-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" id="searchKeyword" class="wnd-search-input"
                        placeholder="<?= __('ph_scan_bobin') ?>"
                        data-i18n-ph="ph_scan_bobin"
                        data-i18n="[placeholder]ph_scan_bobin"
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" autocomplete="off">
                </div>

                <button type="button" id="btnScanQR" class="wnd-btn-action wnd-btn-scan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <path d="M3 14h7v7H3z"></path>
                    </svg>
                    <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?></span>
                </button>

                <button type="submit" class="wnd-btn-action wnd-btn-filter" id="btnFilter" data-i18n="filter">
                    <span>🔍</span>
                    <span><?= __('filter') ?></span>
                </button>
            </form>
        </section>

        <!-- 4. BOBIN LIST CARDS -->
        <?php if (empty($bobins)): ?>
            <div class="wnd-empty-box">
                <div class="wnd-empty-icon">📍</div>
                <div class="wnd-empty-title" data-i18n="winding_empty_list"><?= __('winding_empty_list') ?></div>
            </div>
        <?php else: ?>
            <div class="wnd-list-wrapper bobin-list">
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

                    $windingEmp = decodeJsonObject($item['winding_employee'] ?? '');
                    $windingEmpCode = $windingEmp['employee_code'] ?? '';
                    $windingEmpName = $windingEmp['employee_name'] ?? '';

                    $machine = $item['winding_machine'] ?? '';
                    $flowResult = $item['flow_test_result'] ?? 'Thành công';
                    $windingNote = $item['winding_note'] ?? '';

                    $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                    ?>
                    <div class="wnd-card bobin-item status_rolled"
                        data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
                        
                        <!-- Card Header -->
                        <div class="wnd-card-header card-top">
                            <div class="wnd-id-group key-info">
                                <span class="wnd-id-badge id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                <span class="wnd-key-code key-code"><?= htmlspecialchars($item['bobin_key_code']) ?></span>
                            </div>
                            <div class="wnd-status-badge status-badge">
                                <span>📍</span>
                                <span data-i18n="status_rolled"><?= __('status_rolled') ?></span>
                            </div>
                        </div>

                        <!-- Card Body (2 Columns) -->
                        <div class="wnd-card-body info-grid">
                            <!-- Left: Bobin Specs Panel -->
                            <div class="wnd-specs-panel">
                                <div class="wnd-field field-item">
                                    <label class="wnd-label" data-i18n="field_product"><?= __('field_product') ?></label>
                                    <div class="wnd-val val-sub">
                                        <span class="wnd-tag-product font-bold-blue"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
                                </div>

                                <div class="wnd-field field-item">
                                    <label class="wnd-label" data-i18n="field_print_lot"><?= __('field_print_lot') ?></label>
                                    <div class="wnd-val val-sub">
                                        <span class="wnd-tag-printlot highlight-printlot-text"><?= htmlspecialchars($printLot) ?></span>
                                    </div>
                                </div>

                                <div class="wnd-field field-item">
                                    <label class="wnd-label" data-i18n="field_length"><?= __('field_length') ?></label>
                                    <div class="wnd-val val-highlight">
                                        <span class="wnd-tag-length"><?= number_format($item['length_m'] ?? 0) ?> m</span>
                                    </div>
                                </div>

                                <div class="wnd-field field-item">
                                    <label class="wnd-label" data-i18n="field_rack"><?= __('field_rack') ?></label>
                                    <div class="wnd-val val-sub">
                                        <span class="wnd-tag-rack val-rack"><?= htmlspecialchars($rackCode) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Winding Parameters Panel -->
                            <div class="wnd-params-panel">
                                <div class="wnd-params-header">
                                    <span>⚙️</span>
                                    <span data-i18n="sidebar_nav_title_winding_stage"><?= __('sidebar_nav_title_winding_stage') ?></span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="wnd-field field-item suggestion-wrapper">
                                        <label class="wnd-label" data-i18n="field_winding_machine"><?= __('field_winding_machine') ?></label>
                                        <input class="wnd-input-control input-field input-winding-machine font-bold-cyan wnd-machine-badge" type="text" autocomplete="off"
                                            value="<?= htmlspecialchars($machine) ?>" disabled>
                                    </div>

                                    <div class="wnd-field field-item">
                                        <label class="wnd-label" data-i18n="field_flow_test"><?= __('field_flow_test') ?></label>
                                        <select class="wnd-input-control input-field select-flow-result wnd-flow-select" disabled>
                                            <option value="Thành công" <?= $flowResult === 'Thành công' ? 'selected' : '' ?>>💨 Thành công (Pass)</option>
                                            <option value="Thất bại" <?= $flowResult === 'Thất bại' ? 'selected' : '' ?>>⚠️ Thất bại (Fail)</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="wnd-field field-item suggestion-wrapper">
                                        <label class="wnd-label" data-i18n="field_winding_emp_code"><?= __('field_winding_emp_code') ?></label>
                                        <input class="wnd-input-control input-field input-winding-employee-code" type="text" autocomplete="off"
                                            value="<?= htmlspecialchars($windingEmpCode) ?>" disabled>
                                    </div>

                                    <div class="wnd-field field-item">
                                        <label class="wnd-label" data-i18n="field_winding_emp_name"><?= __('field_winding_emp_name') ?></label>
                                        <input class="wnd-input-control input-field input-readonly input-winding-employee-name" type="text"
                                            value="<?= htmlspecialchars($windingEmpName) ?>" readonly tabindex="-1">
                                    </div>
                                </div>

                                <div class="wnd-field field-item">
                                    <label class="wnd-label" data-i18n="field_winding_note"><?= __('field_winding_note') ?></label>
                                    <input class="wnd-input-control input-field input-winding-note" type="text"
                                        placeholder="<?= __('winding_note_ph') ?>"
                                        data-i18n-ph="winding_note_ph"
                                        data-i18n="[placeholder]winding_note_ph"
                                        value="<?= htmlspecialchars($windingNote) ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="wnd-card-footer card-footer-simple">
                            <?php if (!empty($item['updated_time'])): ?>
                                <div class="wnd-update-time update-time">
                                    <span>🕒</span>
                                    <span><?= __('emp_updated_prefix') ?> <?= htmlspecialchars($item['updated_time']) ?></span>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>

                            <div class="wnd-btn-group button-group">
                                <button type="button" class="wnd-btn-edit btn-edit" onclick="startWindingEdit(this)">
                                    <span>✏️</span>
                                    <span data-i18n="winding_btn_edit"><?= __('winding_btn_edit') ?></span>
                                </button>
                                <button type="button" class="wnd-btn-cancel btn-cancel-edit" style="display: none;"
                                    onclick="cancelWindingEdit(this)">
                                    <span>✕</span>
                                    <span data-i18n="winding_btn_cancel"><?= __('winding_btn_cancel') ?></span>
                                </button>
                                <button type="button" class="wnd-btn-save btn-confirm" style="display: none;"
                                    onclick="handleWindingConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                    <span>💾</span>
                                    <span data-i18n="winding_btn_save"><?= __('winding_btn_save') ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 5. PHÂN TRANG (PAGINATION) -->
            <?php if (isset($pagination['totalPages']) && $pagination['totalPages'] > 1): ?>
                <nav class="wnd-pagination pagination-wrapper">
                    <?php if ($pagination['currentPage'] > 1): ?>
                        <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="wnd-page-btn page-btn">
                            ‹ Trước
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $pagination['currentPage'] - 2);
                    $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    for ($p = $start; $p <= $end; $p++):
                        $isActive = ($p === (int)$pagination['currentPage']);
                    ?>
                        <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="wnd-page-btn page-btn <?= $isActive ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                        <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="wnd-page-btn page-btn">
                            Sau ›
                        </a>
                    <?php endif; ?>

                    <span class="wnd-page-info page-info">
                        Trang <?= $pagination['currentPage'] ?> / <?= $pagination['totalPages'] ?> (Tổng <?= number_format($totalRecords) ?> Bobin)
                    </span>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- JS Variables & Script Modules -->
    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>
