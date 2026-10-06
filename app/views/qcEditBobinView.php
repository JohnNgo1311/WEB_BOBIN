<?php
$bobins = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$pagination = $data['pagination'] ?? [];
$userRole   = $_SESSION['user']['role'] ?? '';
$currentUrl = $_GET['url'] ?? '';
$pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? 0);
$typeOptions = [
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
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_qc_edit') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusionEditBobin.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>

    <div class="page-header">
        <div>
            <h1>🛡️ <span data-i18n="page_qc_edit"><?= __('page_qc_edit') ?></span></h1>
            <p class="page-header-subtitle">Điều chỉnh kết quả kiểm tra ngoại quan QC</p>
        </div>
        <div class="page-header-badge">
            <span>🔍 QC CHECK</span>
        </div>
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC" class="back-btn" title="Quay lại QC">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/qcEditBobinView">

                <div class="qr-search-group">
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path
                                d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="Nhập hoặc quét mã Bobin..."
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
                        <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?></span>
                    </button>
                    <button type="submit" class="btn-filter" id="btnFilter"
                        data-i18n="filter"><?= __('filter') ?></button>
                </div>
            </form>
        </div>

        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin có thể điều chỉnh QC: <span
                        class="counter-badge"><?= number_format($totalRecords) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Không có Bobin nào cần điều chỉnh QC.</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
                        $products = decodeJsonObject($item['products'] ?? '');
                        $productCode = $products['product_code'] ?? 'Chưa cập nhật';

                        $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                        $extEmpCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extEmpName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';

                        $matLotData = decodeJsonObject($item['material_lot'] ?? '');
                        $matLot = $matLotData['lot'] ?? 'Chưa cập nhật';

                        $rackData = decodeJsonObject($item['rack'] ?? '');
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';

                        $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                        $vi = decodeJsonObject($item['visual_inspection'] ?? '');

                        $chkGel           = !($vi['defect_gel'] ?? false);
                        $chkForeignObject = !($vi['defect_foreign_object'] ?? false);
                        $chkColor         = !($vi['defect_color_issue'] ?? false);
                        $chkPrint         = !($vi['defect_print_quality'] ?? false);

                        $inspectorCode = $vi['inspector_code'] ?? '';
                        $inspectorName = $vi['inspector_name'] ?? '';
                        $defectNote    = $vi['defect_note'] ?? '';

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finishTime = $item['finish_time'] ?? '';
                        ?>
                        <div class="bobin-item status_busy_checked"
                            data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
                            <div class="card-top">
                                <div class="key-info">
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                    <span class="key-code"><?= htmlspecialchars($item['bobin_key_code']) ?></span>
                                </div>
                                <div class="status-badge">
                                    ĐÃ KIỂM TRA QC
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm:</label>
                                    <div class="val-sub font-bold-blue"><?= htmlspecialchars($productCode) ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Lot in:</label>
                                    <div class="val-sub highlight-printlot-text"><?= htmlspecialchars($printLot) ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Chiều dài (m):</label>
                                    <div class="val-highlight"><?= number_format($item['length_m'] ?? 0) ?> m</div>
                                </div>

                                <div class="field-item">
                                    <label>Vị trí Rack:</label>
                                    <div class="val-sub"><span class="val-rack"><?= htmlspecialchars($rackCode) ?></span></div>
                                </div>

                                <div class="field-item">
                                    <label>Loại Bobin:</label>
                                    <select class="input-field select-bobin-type" disabled>
                                        <?php foreach ($typeOptions as $val => $text): ?>
                                            <option value="<?= htmlspecialchars($val) ?>"
                                                <?= ($item['bobin_type'] ?? '') === $val ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($text) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-item suggestion-wrapper">
                                    <label>Mã nhân viên QC:</label>
                                    <input class="input-field input-inspector-code" type="text" autocomplete="off"
                                        value="<?= htmlspecialchars($inspectorCode) ?>" disabled>
                                </div>

                                <div class="field-item">
                                    <label>Họ tên nhân viên QC:</label>
                                    <input class="input-field input-readonly input-inspector-name" type="text"
                                        value="<?= htmlspecialchars($inspectorName) ?>" readonly tabindex="-1">
                                </div>

                                <div class="field-item" style="grid-column: span 2;">
                                    <label>Ghi chú QC:</label>
                                    <input class="input-field qc-note-input" type="text"
                                        value="<?= htmlspecialchars($defectNote) ?>" disabled>
                                </div>
                            </div>

                            <!-- 4 TIÊU CHÍ NGOẠI QUAN QC -->
                            <div class="ext-check-strip">
                                <span class="ext-title">🛡️ Tiêu chí ngoại quan QC:</span>
                                <div class="ext-checks-group">
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
                                        <div class="ext-check-box">
                                            <span class="check-box-label"><?= $c['label'] ?></span>
                                            <button type="button" class="qc-toggle-btn ext-toggle-btn <?= $isOk ? 'active' : '' ?>"
                                                data-field="qc_check_<?= $key ?>" data-value="<?= $isOk ? 'true' : 'false' ?>"
                                                onclick="toggleQCCheck(this)" disabled>
                                                <?= $isOk ? 'OK' : 'NG' ?>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- CÁC NÚT HÀNH ĐỘNG -->
                            <div class="card-footer-simple">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 Cập nhật: <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>

                                <div class="button-group">
                                    <button type="button" class="btn-edit" onclick="startQCEdit(this)">
                                        ✏️ Chỉnh sửa QC
                                    </button>
                                    <button type="button" class="btn-cancel-edit" style="display: none;"
                                        onclick="cancelQCEdit(this)">
                                        ✕ Hủy sửa
                                    </button>
                                    <button type="button" class="btn-confirm" style="display: none;"
                                        onclick="handleQCConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        💾 Cập nhật
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
    <script src="/WEB_BOBIN/public/assets/js/QC/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>