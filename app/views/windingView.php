<?php

$bobins = $data['bobins'] ?? [];
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
        $text = $goodDefect ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Danh sách Bobin - Nhóm Cuộn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_Winding.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
</head>

<body>
    <div class="menu-bar">
        <div class="menu-left">
            <a href="#">Nhóm đùn</a>
            <a href="#">QC</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView">Cuộn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView">Danh sách chờ hủy</a>
        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>

    <h1>Nhóm cuộn - Xác nhận hoàn thành Bobin</h1>

    <div class="container">

        <!-- CONTROL BAR -->
        <div class="control-bar">
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form">
                <input type="hidden" name="url" value="bobin/windingView">
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24">
                        <path
                            d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" placeholder="Nhập thông tin Bobin cần tra cứu..."
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                </div>
                <button class="btn-filter">Lọc</button>
            </form>
        </div>

        <!-- LIST -->
        <div class="list-card">
            <div class="header-row"
                style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <h2 style="margin:0;">Tổng số lượng Bobin:
                    <?= number_format($pagination['totalRecords'] ?? count($bobins)) ?> Bobin</h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Danh sách trống</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled' => 'status_rolled',
                            'busy_checked' => 'status_busy_checked',
                            default => 'status_unknown'
                        };
                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $winding_employeeRaw = $item['winding_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $productCode = 'Chưa cập nhật';
                        $extrusion_employeeName = 'Chưa cập nhật';
                        $extrusion_employeeCode = 'Chưa cập nhật';
                        $materialLot = 'Chưa cập nhật';
                        $winding_machine = $item['winding_machine'] ?? '';
                        $winding_employeeCode = '';
                        $winding_employeeName = '';
                        $winding_note = $item['winding_note'] ?? '';

                        $vi = [];
                        $defects = [];

                        if (is_string($viRaw) && trim($viRaw) !== '') {
                            $decoded = json_decode($viRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $vi = $decoded;
                                $defects = $vi['defects'] ?? [];
                            }
                        }

                        if (is_string($productsRaw) && trim($productsRaw) !== '') {
                            $decoded = json_decode($productsRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $productCode = $decoded['product_code'] ?? '';
                            }
                        }
                        if (is_string($extrusion_employeeRaw) && trim($extrusion_employeeRaw) !== '') {
                            $decoded = json_decode($extrusion_employeeRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $extrusion_employeeCode = $decoded['employee_code'] ?? '';
                                $extrusion_employeeName = $decoded['employee_name'] ?? '';
                            }
                        }
                        if (is_string($winding_employeeRaw) && trim($winding_employeeRaw) !== '') {
                            $decoded = json_decode($winding_employeeRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $winding_employeeCode = $decoded['employee_code'] ?? '';
                                $winding_employeeName = $decoded['employee_name'] ?? '';
                            }
                        }
                        if (is_string($materialLotRaw) && trim($materialLotRaw) !== '') {
                            $decoded = json_decode($materialLotRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $materialLot = $decoded['lot'] ?? '';
                            }
                        }
                        ?>
                        <div class="bobin-item <?= $statusClass ?>">
                            <!-- HEADER -->
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
                                    <span class="key-code"><?= $displayKey ?></span>
                                    <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                                </div>
                                <div class="status-badge">
                                    <?php
                                    $displayStatus = match ($rawStatus) {
                                        'Rolled' => 'Đã cuộn',
                                        'Busy_Checked'  => 'Đã kiểm tra QC',
                                        default => $rawStatus,
                                    };
                                    echo htmlspecialchars($displayStatus); ?>
                                </div>
                            </div>

                            <!-- INFO GRID -->
                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub"><?= htmlspecialchars($productCode ?? '') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Mã nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode ?? 'Chưa cập nhật') ?>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName ?? 'Chưa cập nhật') ?>
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
                                    <div class="val-sub"><?= htmlspecialchars($materialLot ?? '') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['print_lot']) ?></div>
                                </div>
                                <div class="field-item highlight-box">
                                    <label>Chiều dài (m)</label>
                                    <div class="val-highlight">
                                        <?= number_format($item['length_m'], 0, decimal_separator: ".", thousands_separator: ",") ?>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Ngày đùn</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['extrusion_date']) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Thời điểm hoàn thành cuộn</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['finish_time']) ?></div>
                                </div>
                            </div>

                            <div class="divider"></div>
                            <!-- QC -->
                            <div class="qc-section">
                                <div class="qc-title">🛡️ QC Check</div>
                                <div class="qc-header">
                                    <div class="qc-info-row">
                                        <div class="qc-input">
                                            <label class="val-text">Mã nhân viên QC:</label>
                                            <input type="text" class="qc-input input-inspector-code"
                                                value="<?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?>"
                                                readonly>
                                        </div>
                                        <div class="qc-input">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <input type="text" class="qc-input input-inspector-name"
                                                value="<?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa cập nhật') ?>"
                                                readonly>
                                        </div>
                                        <div class="qc-meta-item">
                                            <label class="val-text">Thời điểm kiểm tra:</label>
                                            <span class="val-sub"><?= htmlspecialchars($vi['inspection_time'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="qc-badges">
                                    <?php
                                    $badges = [
                                        'Gel' => 'gel',
                                        'Dị vật' => 'foreign_object',
                                        'Màu' => 'color_issue',
                                        'Mực In' => 'print_quality'
                                    ];
                                    foreach ($badges as $label => $key):
                                        $goodDefect = $defects[$key] ?? false;
                                    ?>
                                        <div class="vi-item-switch" data-key="<?= htmlspecialchars($key) ?>">
                                            <span><?= htmlspecialchars($label) ?></span>
                                            <button type="button" class="toggle-switch <?= $goodDefect ? 'active' : '' ?>" disabled>
                                                <span class="switch-label"><?= $goodDefect ? 'OK' : 'NG' ?></span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="qc-note">
                                    <label>📝 Ghi chú:</label>
                                    <textarea class="qc-note-field"
                                        readonly><?= htmlspecialchars($defects['note'] ?? 'Chưa cập nhật') ?></textarea>
                                </div>
                            </div>

                            <!-- Winding -->
                            <div class="divider"></div>
                            <div class="winding-section">
                                <div class="winding-title">📍 Thông tin cuộn</div>
                                <div class="winding-header">
                                    <div class="winding-info-row">
                                        <div class="winding-input">
                                            <label class="val-text">Máy cuộn:</label>
                                            <input type="text" class="winding-input winding-machine-name"
                                                value="<?= ($rawStatus === 'Rolled') ? htmlspecialchars($winding_machine ?? '') : '' ?>"
                                                placeholder="Nhập mã số máy cuộn">
                                            <div class="suggestion-box"></div>
                                        </div>
                                        <div class="winding-input">
                                            <label class="val-text">Mã nhân viên:</label>
                                            <input type="text" class="winding-input winding-employee-code"
                                                value="<?= ($rawStatus === 'Rolled') ? htmlspecialchars($winding_employeeCode ?? '') : '' ?>"
                                                placeholder="Nhập mã số nhân viên">
                                            <div class="suggestion-box"></div>
                                        </div>
                                        <div class="winding-input">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <input type="text" class="winding-input winding-employee-name"
                                                value="<?= ($rawStatus === 'Rolled') ? htmlspecialchars($winding_employeeName ?? '') : '' ?>"
                                                placeholder="Nhập họ tên nhân viên" readonly>
                                        </div>
                                    </div>
                                    <div class="winding-note">
                                        <label>📝 Ghi chú:</label>
                                        <input class="winding-note-field"
                                            value="<?= ($rawStatus === 'Rolled') ? htmlspecialchars($winding_note ?? '') : '' ?>"
                                            placeholder="Nhập ghi chú">
                                    </div>

                                    <div class="card-footer-simple">
                                        <?php if (!empty($item['updated_time'])): ?>
                                            <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                                <?= htmlspecialchars($item['updated_time']) ?></div>
                                        <?php endif; ?>

                                        <?php
                                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                                        $formatted_time = !empty($item['finish_time']) ? date('dmY$His', strtotime($item['finish_time'])) : 'Chưa cập nhật';

                                        $fields = [$productCode, $materialLot, $printLot, $formatted_time];
                                        $isMissingData = false;

                                        foreach ($fields as $field) {
                                            $checkValue = trim((string)$field);
                                            if ($checkValue === '' || $checkValue === 'Chưa cập nhật') {
                                                $isMissingData = true;
                                                break;
                                            }
                                        }

                                        $mainInfoText = $isMissingData ? 'Chưa cập nhật đầy đủ thông tin cần thiết' : ($productCode . '$' . $materialLot . '$' . $printLot . '$' . $formatted_time);
                                        ?>

                                        <div class="button-group">
                                            <button type="button" class="btn-cancel"
                                                onclick="handleCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                                Hủy Bobin
                                            </button>

                                            <button type="button" class="btn-confirm"
                                                onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>', '<?= htmlspecialchars($mainInfoText) ?>')">
                                                Xác nhận hoàn thành
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- THANH PHÂN TRANG -->
                <?php if (isset($pagination['totalPages']) && $pagination['totalPages'] > 1): ?>
                    <div class="pagination-wrapper"
                        style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px; padding:12px;">
                        <?php if ($pagination['currentPage'] > 1): ?>
                            <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="page-btn"
                                style="padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; text-decoration:none; color:#334155; background:#fff; font-size:14px;">
                                ‹ Trước
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $pagination['currentPage'] - 2);
                        $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                        for ($p = $start; $p <= $end; $p++):
                            $isActive = ($p === (int)$pagination['currentPage']);
                        ?>
                            <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"
                                style="padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; <?= $isActive ? 'background:#0284c7; color:#fff; border-color:#0369a1; font-weight:600;' : 'border:1px solid #cbd5e1; color:#334155; background:#fff;' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                            <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="page-btn"
                                style="padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; text-decoration:none; color:#334155; background:#fff; font-size:14px;">
                                Sau ›
                            </a>
                        <?php endif; ?>

                        <span style="font-size:13px; color:#64748b; margin-left:10px;">
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
    <script src="/WEB_BOBIN/public/assets/js/Winding/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/suggestion.js?v=<?= time() ?>"></script>
</body>

</html>