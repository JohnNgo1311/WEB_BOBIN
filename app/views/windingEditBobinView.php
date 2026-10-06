<?php
$bobins = $data['bobins'] ?? [];
$totalRecords = $data['totalRecords'] ?? count($bobins);
$pagination = $data['pagination'] ?? [];
$userRole   = $_SESSION['user']['role'] ?? '';
$currentUrl = $_GET['url'] ?? '';
$pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? 0);

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
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_winding_edit') ?> | SMC</title>
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
            <h1>📍 <span data-i18n="page_winding_edit"><?= __('page_winding_edit') ?></span></h1>
            <p class="page-header-subtitle">Điều chỉnh thông tin ca kíp, máy cuộn và kiểm tra thông khí công đoạn Cuộn</p>
        </div>
        <div class="page-header-badge">
            <span>⚡ WINDING CHECK</span>
        </div>
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView" class="back-btn" title="Quay lại Cuộn">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/windingEditBobinView">

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
                    <button type="submit" class="btn-filter" id="btnFilter" data-i18n="filter"><?= __('filter') ?></button>
                </div>
            </form>
        </div>

        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin có thể điều chỉnh Cuộn: <span class="counter-badge"><?= number_format($totalRecords) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
            <div class="empty-state">Không có Bobin nào cần điều chỉnh Cuộn.</div>
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

                        $windingEmp = decodeJsonObject($item['winding_employee'] ?? '');
                        $windingEmpCode = $windingEmp['employee_code'] ?? '';
                        $windingEmpName = $windingEmp['employee_name'] ?? '';

                        $machine = $item['winding_machine'] ?? '';
                        $flowResult = $item['flow_test_result'] ?? 'Thành công';
                        $windingNote = $item['winding_note'] ?? '';

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        ?>
                <div class="bobin-item status_rolled"
                    data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
                    <div class="card-top">
                        <div class="key-info">
                            <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                            <span class="key-code"><?= htmlspecialchars($item['bobin_key_code']) ?></span>
                        </div>
                        <div class="status-badge">
                            ĐÃ CUỘN
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

                        <div class="field-item suggestion-wrapper">
                            <label>Máy cuộn:</label>
                            <input class="input-field input-winding-machine font-bold-cyan" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($machine) ?>" disabled>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Mã NV Cuộn:</label>
                            <input class="input-field input-winding-employee-code" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($windingEmpCode) ?>" disabled>
                        </div>

                        <div class="field-item">
                            <label>Họ tên NV Cuộn:</label>
                            <input class="input-field input-readonly input-winding-employee-name" type="text"
                                value="<?= htmlspecialchars($windingEmpName) ?>" readonly tabindex="-1">
                        </div>

                        <div class="field-item">
                            <label>Kiểm tra thông khí:</label>
                            <select class="input-field select-flow-result" disabled>
                                <option value="Thành công" <?= $flowResult === 'Thành công' ? 'selected' : '' ?>>Thành công</option>
                                <option value="Thất bại" <?= $flowResult === 'Thất bại' ? 'selected' : '' ?>>Thất bại</option>
                            </select>
                        </div>

                        <div class="field-item" style="grid-column: span 2;">
                            <label>Ghi chú Cuộn:</label>
                            <input class="input-field input-winding-note" type="text"
                                value="<?= htmlspecialchars($windingNote) ?>" disabled>
                        </div>
                    </div>

                    <!-- CÁC NÚT HÀNH ĐỘNG -->
                    <div class="card-footer-simple">
                        <?php if (!empty($item['updated_time'])): ?>
                        <div class="update-time">🕒 Cập nhật: <?= htmlspecialchars($item['updated_time']) ?></div>
                        <?php endif; ?>

                        <div class="button-group">
                            <button type="button" class="btn-edit" onclick="startWindingEdit(this)">
                                ✏️ Chỉnh sửa Cuộn
                            </button>
                            <button type="button" class="btn-cancel-edit" style="display: none;" onclick="cancelWindingEdit(this)">
                                ✕ Hủy sửa
                            </button>
                            <button type="button" class="btn-confirm" style="display: none;"
                                onclick="handleWindingConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
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
                    Trang <?= $pagination['currentPage'] ?> / <?= $pagination['totalPages'] ?> (Tổng <?= number_format($totalRecords) ?> Bobin)
                </span>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <script>
    const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>
