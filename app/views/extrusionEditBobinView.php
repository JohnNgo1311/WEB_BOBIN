<?php
// File: app/views/extrusionEditBobinView.php
// Trang Quản Trị - Điều Chỉnh Thông Số Sản Xuất Công Đoạn Đùn (TASK-020 Enhanced)

$bobins       = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
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

// Thống kê nhanh KPI theo tập bản ghi hiện tại
$extCheckOkCount = 0;
$extCheckNgCount = 0;
foreach ($bobins as $b) {
    $chk = decodeJsonObject($b['extrusion_check'] ?? '');
    $isAllOk = ($chk['diameter'] ?? true) && ($chk['gel'] ?? true) && ($chk['foreign_object'] ?? true) && ($chk['color'] ?? true) && ($chk['print'] ?? true);
    if ($isAllOk) {
        $extCheckOkCount++;
    } else {
        $extCheckNgCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_extrusion_edit') ?> | SMC WEB_BOBIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS Offline Local -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusionEditBobin.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">

    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- Vertical Left Sidebar & Header -->
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <div class="ext-container">
        <!-- 1. HERO HEADER -->
        <header class="ext-hero">
            <div class="ext-hero-title-area">
                <h1>
                    <span>🏭</span>
                    <span data-i18n="page_extrusion_edit"><?= __('page_extrusion_edit') ?></span>
                </h1>
                <p class="ext-hero-subtitle" data-i18n="ext_edit_subtitle"><?= __('ext_edit_subtitle') ?></p>
            </div>
            <div class="ext-hero-badge">
                <span>⚙️</span>
                <span data-i18n="ext_badge_stage"><?= __('ext_badge_stage') ?></span>
            </div>
        </header>

        <!-- 2. KPI STATS ROW -->
        <section class="ext-stats-grid">
            <div class="ext-stat-card stat-total">
                <div class="ext-stat-icon-wrap">📊</div>
                <div class="ext-stat-info">
                    <span class="ext-stat-val"><?= number_format($totalRecords) ?></span>
                    <span class="ext-stat-label" data-i18n="ext_kpi_total"><?= __('ext_kpi_total') ?></span>
                </div>
            </div>

            <div class="ext-stat-card stat-ok">
                <div class="ext-stat-icon-wrap">✨</div>
                <div class="ext-stat-info">
                    <span class="ext-stat-val"><?= number_format($extCheckOkCount) ?></span>
                    <span class="ext-stat-label" data-i18n="ext_kpi_check_ok"><?= __('ext_kpi_check_ok') ?></span>
                </div>
            </div>

            <div class="ext-stat-card stat-ng">
                <div class="ext-stat-icon-wrap">⚠️</div>
                <div class="ext-stat-info">
                    <span class="ext-stat-val"><?= number_format($extCheckNgCount) ?></span>
                    <span class="ext-stat-label" data-i18n="ext_kpi_check_ng"><?= __('ext_kpi_check_ng') ?></span>
                </div>
            </div>

            <div class="ext-stat-card stat-status">
                <div class="ext-stat-icon-wrap">🏭</div>
                <div class="ext-stat-info">
                    <span class="ext-stat-val">BUSY_UNCHECKED</span>
                    <span class="ext-stat-label" data-i18n="ext_kpi_unchecked"><?= __('ext_kpi_unchecked') ?></span>
                </div>
            </div>
        </section>

        <!-- QR SCANNER CONTAINER -->
        <div id="qr-reader"></div>

        <!-- 3. CONTROL TOOLBAR -->
        <section class="ext-toolbar">
            <?php 
                $backUrl = AuthHelper::hasPermission('extrusion_create') ? '/WEB_BOBIN/public/index.php?url=bobin/index' : ($userHomeUrl ?? '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView');
            ?>
            <a href="<?= $backUrl ?>" class="ext-back-btn" title="Quay lại">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="ext-search-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/extrusionEditBobinView">

                <div class="ext-search-wrap">
                    <svg class="ext-search-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" id="searchKeyword" class="ext-search-input"
                        placeholder="<?= __('ph_scan_bobin') ?>"
                        data-i18n-ph="ph_scan_bobin"
                        data-i18n="[placeholder]ph_scan_bobin"
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>" autocomplete="off">
                </div>

                <button type="button" id="btnScanQR" class="ext-btn-action ext-btn-scan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <path d="M3 14h7v7H3z"></path>
                    </svg>
                    <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?></span>
                </button>

                <button type="submit" class="ext-btn-action ext-btn-filter" id="btnFilter" data-i18n="filter">
                    <span>🔍</span>
                    <span><?= __('filter') ?></span>
                </button>
            </form>
        </section>

        <!-- 4. BOBIN LIST CARDS -->
        <?php if (empty($bobins)): ?>
            <div class="ext-empty-box">
                <div class="ext-empty-icon">📦</div>
                <div class="ext-empty-title" data-i18n="ext_empty_list"><?= __('ext_empty_list') ?></div>
            </div>
        <?php else: ?>
            <div class="ext-list-wrapper bobin-list">
                <?php foreach ($bobins as $item): ?>
                    <?php
                    $products = decodeJsonObject($item['products'] ?? '');
                    $productCode = $products['product_code'] ?? '';
                    $productionOrderCode = $products['production_order_code'] ?? '';

                    $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                    $extEmpCode = $extrusionEmployee['employee_code'] ?? '';
                    $extEmpName = $extrusionEmployee['employee_name'] ?? '';

                    $materialLotData = decodeJsonObject($item['material_lot'] ?? '');
                    $materialLot = $materialLotData['lot'] ?? '';

                    $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                    $rackData = decodeJsonObject($item['rack'] ?? '');
                    $rackCode = $rackData['code'] ?? '';

                    $chkDiameter      = $extCheck['diameter'] ?? true;
                    $chkGel           = $extCheck['gel'] ?? true;
                    $chkForeignObject = $extCheck['foreign_object'] ?? true;
                    $chkColor         = $extCheck['color'] ?? true;
                    $chkPrint         = $extCheck['print'] ?? true;

                    $rawKey = $item['bobin_key_code'] ?? '';
                    $displayKey = htmlspecialchars($rawKey);
                    if (is_string($rawKey) && strpos($rawKey, '_') !== false) {
                        $parts = explode('_', $rawKey);
                        if (count($parts) >= 7) {
                            $code = array_shift($parts);
                            $date = implode('/', array_slice($parts, 0, 3));
                            $time = implode(':', array_slice($parts, 3, 3));
                            $displayKey = htmlspecialchars("{$code} - {$date} - {$time}");
                        }
                    }

                    $finishTimeRaw = $item['finish_time'] ?? '';
                    $finishTimeVal = '';
                    if (!empty($finishTimeRaw)) {
                        $ts = strtotime($finishTimeRaw);
                        if ($ts) $finishTimeVal = date('Y-m-d\TH:i:s', $ts);
                    }
                    ?>
                    <div class="ext-card bobin-item status_busy_unchecked"
                        data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
                        
                        <!-- Card Header -->
                        <div class="ext-card-header card-top">
                            <div class="ext-id-group key-info">
                                <span class="ext-id-badge id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                <span class="ext-key-code key-code"><?= $displayKey ?></span>
                            </div>
                            <div class="ext-status-badge status-badge">
                                <span>🏭</span>
                                <span data-i18n="status_busy_unchecked"><?= __('status_busy_unchecked') ?></span>
                            </div>
                        </div>

                        <!-- Card Body (3 Columns Section Architecture) -->
                        <div class="ext-card-body info-grid">
                            
                            <!-- Section A: Thông số sản xuất & Quy cách -->
                            <div class="ext-section-box">
                                <div class="ext-sec-title">
                                    <span>📦</span>
                                    <span data-i18n="ext_sec_specs"><?= __('ext_sec_specs') ?></span>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_product"><?= __('field_product') ?></label>
                                    <input id="product_code" class="ext-input-control input-field font-bold-blue" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($productCode) ?>" placeholder="<?= __('ph_search_product') ?>" data-i18n-ph="ph_search_product" data-i18n="[placeholder]ph_search_product" disabled>
                                    <input type="hidden" id="production_order_code" value="<?= htmlspecialchars($productionOrderCode) ?>">
                                    <div id="product_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_length"><?= __('field_length') ?></label>
                                    <input id="length_m" class="ext-input-control input-field font-bold-green" type="number" step="0.001"
                                        value="<?= htmlspecialchars($item['length_m'] ?? 0) ?>" disabled>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_bobin_type"><?= __('field_bobin_type') ?></label>
                                    <select id="bobin_type" name="bobin_type" class="ext-input-control input-field" disabled required>
                                        <?php foreach ($typeOptions as $val => $lbl): ?>
                                            <option value="<?= htmlspecialchars($val) ?>" <?= ($item['bobin_type'] ?? '') === $val ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($lbl) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_print_lot"><?= __('field_print_lot') ?></label>
                                    <input id="print_lot" class="ext-input-control edit-input highlight-printlot" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($item['print_lot'] ?? '') ?>" readonly tabindex="-1">
                                    <div id="print_lot_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_rack"><?= __('field_rack') ?></label>
                                    <input id="rack_code" class="ext-input-control input-field font-bold-cyan" type="text" autocomplete="off"
                                        placeholder="<?= __('ph_select_rack') ?>" data-i18n-ph="ph_select_rack" data-i18n="[placeholder]ph_select_rack"
                                        value="<?= htmlspecialchars($rackCode) ?>" disabled>
                                    <div id="rack_suggestions" class="suggestion-box"></div>
                                </div>
                            </div>

                            <!-- Section B: Thiết bị máy đùn & Vật liệu -->
                            <div class="ext-section-box">
                                <div class="ext-sec-title">
                                    <span>⚙️</span>
                                    <span data-i18n="ext_sec_materials"><?= __('ext_sec_materials') ?></span>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_machine"><?= __('field_machine') ?></label>
                                    <input type="text" id="extrusion_machine" class="ext-input-control input-field"
                                        placeholder="<?= __('ph_input_machine') ?>" data-i18n-ph="ph_input_machine" data-i18n="[placeholder]ph_input_machine"
                                        autocomplete="off" disabled>
                                    <div id="machine_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_material"><?= __('field_material') ?></label>
                                    <input type="text" id="material" class="ext-input-control input-field"
                                        placeholder="<?= __('ph_input_material') ?>" data-i18n-ph="ph_input_material" data-i18n="[placeholder]ph_input_material"
                                        autocomplete="off" disabled>
                                    <div id="material_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_grinding"><?= __('field_grinding') ?></label>
                                    <input type="number" id="grinding_time" class="ext-input-control input-field" min="0" max="2" step="1"
                                        placeholder="<?= __('ph_input_grind') ?>" data-i18n-ph="ph_input_grind" data-i18n="[placeholder]ph_input_grind"
                                        oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)" disabled>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_material_lot"><?= __('field_material_lot') ?></label>
                                    <input id="material_lot" class="ext-input-control input-field" type="text" autocomplete="off"
                                        placeholder="<?= __('ph_enter_material_lot') ?>" data-i18n-ph="ph_enter_material_lot" data-i18n="[placeholder]ph_enter_material_lot"
                                        value="<?= htmlspecialchars($materialLot) ?>" disabled>
                                    <div id="material_lot_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_shift"><?= __('field_shift') ?></label>
                                    <select id="shift" name="shift" class="ext-input-control input-field" disabled required>
                                        <option value="Ca 1" <?= ($item['shift'] ?? '') === 'Ca 1' ? 'selected' : '' ?>>Ca 1</option>
                                        <option value="Ca 2" <?= ($item['shift'] ?? '') === 'Ca 2' ? 'selected' : '' ?>>Ca 2</option>
                                        <option value="Ca 3" <?= ($item['shift'] ?? '') === 'Ca 3' ? 'selected' : '' ?>>Ca 3</option>
                                        <option value="Hành chính" <?= ($item['shift'] ?? '') === 'Hành chính' ? 'selected' : '' ?>>Hành chính</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Section C: Nhân sự & Thời gian hoàn thành -->
                            <div class="ext-section-box">
                                <div class="ext-sec-title">
                                    <span>👤</span>
                                    <span data-i18n="ext_sec_personnel"><?= __('ext_sec_personnel') ?></span>
                                </div>

                                <div class="ext-field field-item suggestion-wrapper">
                                    <label class="ext-label" data-i18n="field_employee_code"><?= __('field_employee_code') ?></label>
                                    <input id="extrusion_employee_code" class="ext-input-control input-field font-bold-blue" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($extEmpCode) ?>" disabled>
                                    <div id="employee_suggestions" class="suggestion-box"></div>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_employee_name"><?= __('field_employee_name') ?></label>
                                    <input id="extrusion_employee_name" class="ext-input-control edit-input input-readonly" type="text"
                                        value="<?= htmlspecialchars($extEmpName) ?>" readonly tabindex="-1">
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_ext_date"><?= __('field_ext_date') ?></label>
                                    <input id="extrusion_date" name="extrusion_date" class="ext-input-control input-field" type="date"
                                        value="<?= htmlspecialchars($item['extrusion_date'] ?? '') ?>" disabled>
                                </div>

                                <div class="ext-field field-item">
                                    <label class="ext-label" data-i18n="field_finish_time"><?= __('field_finish_time') ?></label>
                                    <input id="finish_time" name="finish_time" class="ext-input-control input-field" type="datetime-local"
                                        step="1" value="<?= htmlspecialchars($finishTimeVal) ?>"
                                        onclick="try { this.showPicker(); } catch(e) {}"
                                        onkeydown="return ['Tab', 'Escape'].includes(event.key)" disabled>
                                </div>
                            </div>

                        </div>

                        <!-- 5 Tiêu chuẩn Đùn Check -->
                        <div class="ext-check-strip">
                            <div class="ext-criteria-header ext-title">
                                <span>🏭</span>
                                <span data-i18n="ext_criteria_title"><?= __('ext_criteria_title') ?></span>
                            </div>

                            <div class="ext-checks-group">
                                <?php
                                $checks = [
                                    'diameter'       => ['label_key' => 'crit_diameter', 'label' => 'Đường kính', 'val' => $chkDiameter],
                                    'gel'            => ['label_key' => 'crit_gel',      'label' => 'Gel',        'val' => $chkGel],
                                    'foreign_object' => ['label_key' => 'crit_foreign',  'label' => 'Dị vật',     'val' => $chkForeignObject],
                                    'color'          => ['label_key' => 'crit_color',    'label' => 'Màu sắc',    'val' => $chkColor],
                                    'print'          => ['label_key' => 'crit_print',    'label' => 'Chữ in',     'val' => $chkPrint]
                                ];
                                foreach ($checks as $key => $c):
                                    $isOk = (bool)$c['val'];
                                ?>
                                    <div class="ext-check-box">
                                        <span class="ext-criteria-name check-box-label" data-i18n="<?= $c['label_key'] ?>"><?= __($c['label_key']) ?></span>
                                        <button type="button" class="ext-toggle-btn <?= $isOk ? 'active' : '' ?>"
                                            data-field="ext_check_<?= $key ?>" data-value="<?= $isOk ? 'true' : 'false' ?>"
                                            onclick="toggleExtCheck(this)" disabled>
                                            <?= $isOk ? 'OK' : 'NG' ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="ext-card-footer card-footer-simple">
                            <?php if (!empty($item['updated_time'])): ?>
                                <div class="ext-update-time update-time">
                                    <span>🕒</span>
                                    <span data-i18n="emp_updated_prefix"><?= __('emp_updated_prefix') ?></span>
                                    <span><?= htmlspecialchars($item['updated_time']) ?></span>
                                </div>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>

                            <div class="ext-btn-group button-group">
                                <!-- Nút Sửa ban đầu -->
                                <button type="button" class="ext-btn btn-primary-edit btn-edit" onclick="startEdit(this)">
                                    <span>✏️</span>
                                    <span data-i18n="ext_btn_edit"><?= __('ext_btn_edit') ?></span>
                                </button>

                                <!-- Nút Hủy và Lưu (chỉ hiện khi bấm Chỉnh sửa) -->
                                <button type="button" class="ext-btn btn-cancel btn-cancel-edit" style="display: none;" onclick="cancelEdit(this)">
                                    <span>✕</span>
                                    <span data-i18n="ext_btn_cancel"><?= __('ext_btn_cancel') ?></span>
                                </button>
                                <button type="button" class="ext-btn btn-save btn-confirm" style="display: none;"
                                    onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                    <span>💾</span>
                                    <span data-i18n="ext_btn_save"><?= __('ext_btn_save') ?></span>
                                </button>

                                <!-- Nút Hủy Bobin -->
                                <button type="button" class="ext-btn btn-delete-scrap btn-delete"
                                    onclick="handleDelete(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                    <span>🗑️</span>
                                    <span data-i18n="ext_btn_delete"><?= __('ext_btn_delete') ?></span>
                                </button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>