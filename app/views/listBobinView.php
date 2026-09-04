<?php

$bobins = $data['bobins'] ?? [];

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        //TODO 1. Lấy query hiện tại
        $query = $_GET ?? [];

        //TODO 2. [QUAN TRỌNG] Đảm bảo tham số 'url' luôn đúng định tuyến hiện tại
        //TODO Nếu $_GET chưa có 'url', ta phải ép buộc thêm vào.
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listBobinView';
        }

        //TODO 3. Ghi đè các tham số mới (Dùng array_merge cho gọn và nhanh hơn foreach)
        $query = array_merge($query, $overrideParams);

        //TODO 4. Xử lý trường hợp muốn xóa tham số (nếu truyền vào null)
        foreach ($query as $key => $value) {
            if (is_null($value) || $value === '') {
                unset($query[$key]);
            }
        }
        // 5. Build lại URL
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
    <title>Danh sách Bobin</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin.css">
</head>

<body>
    <div class="container">
        <!-- CONTROL BAR -->
        <div class="control-bar">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="btn-icon-gray">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form">
                <input type="hidden" name="url" value="bobin/listBobinView">
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5
                    6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" placeholder="Nhập thông tin Bobin cần tra cứu..."
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                </div>

                <div class="date-group">
                    <input type="date" name="from_date" id="fromDate" value="<?= $_GET['from_date'] ?? '' ?>">
                    <span class="arrow">➝</span>
                    <input type="date" name="to_date" id="toDate" value="<?= $_GET['to_date'] ?? '' ?>">
                </div>

                <button type="submit" class="btn-filter" id="btnFilter">Lọc</button>

                <script src="/WEB_BOBIN/public/assets/js/Manage/submit.js?v=<?= time() ?>"></script>

            </form>
            <!-- Nút nhấn Export Excel -->
            <?php
            // Logic Export Excel URL
            $exportParams = $_GET;
            $exportParams['url'] = 'bobin/exportExcel';
            $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
            ?>
            <a href="<?= $exportUrl ?>" class="btn-primary">
                Xuất file Excel
            </a>
            <!-- Nút nhấn đăng kí -->
            <!-- <a href="/WEB_BOBIN/public/index.php?url=bobin/createBobinView" class="btn-primary">
                Đăng ký
            </a> -->
        </div>
        <!-- LIST -->
        <div class="list-card">
            <div class="header-row"
                style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <h2 style="margin:0;">Số lượng Bobin: <?= count($bobins) ?></h2>
                <?php $currentStatus = $_GET['status'] ?? 'all'; ?>
                <div class="status-filter-bar" style="display:flex;gap:0.5rem;align-items:center;">
                    <a href="<?= buildFilterUrl(['status' => null]) ?>"
                        class="status-btn <?= $currentStatus === 'all' ? 'active' : '' ?>">
                        📦 Tất cả
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Ready']) ?>"
                        class="status-btn ready <?= $currentStatus === 'Ready' ? 'active' : '' ?>">
                        ✅ Đã cuộn
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked']) ?>"
                        class="status-btn unchecked <?= $currentStatus === 'Busy_Unchecked' ? 'active' : '' ?>">
                        ⏳ Chưa QC
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked']) ?>"
                        class="status-btn checked <?= $currentStatus === 'Busy_Checked' ? 'active' : '' ?>">
                        🛡️ Đã QC
                    </a>
                </div>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Danh sách trống</div>
            <?php else: ?>
                <div class="bobin-list">

                    <?php foreach ($bobins as $item): ?>

                        <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        // Vừa loại bỏ gạch dưới, vừa chuyển về chữ thường để so sánh dễ hơn
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'ready' => 'status_ready',
                            'busy_unchecked' => 'status_busy_unchecked',
                            'busy_checked' => 'status_busy_checked',
                            default => 'status_unknown'
                        };
                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $employeeRaw = $item['employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $productionOrderCode = '';
                        $productCode = '';
                        $employeeName = '';
                        $employeeCode = '';
                        $materialLot = '';


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
                                //! SAU NÀY DÙNG  $productionOrderCode = $decoded['production_order_code'] ?? '';
                                $productCode = $decoded['product_code'] ?? '';
                            }
                        }
                        if (is_string($employeeRaw) && trim($employeeRaw) !== '') {
                            $decoded = json_decode($employeeRaw, true);

                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $employeeCode = $decoded['employee_code'] ?? '';
                                $employeeName = $decoded['employee_name'] ?? '';
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
                                        'Ready' => 'Đã cuộn',
                                        'Busy_Unchecked'  => 'Đang đợi QC kiểm tra',
                                        'Busy_Checked'  => 'Đã kiểm tra QC',
                                        default => $rawStatus, // Giữ nguyên nếu không khớp
                                    };
                                    echo htmlspecialchars($displayStatus); ?>
                                </div>

                            </div>

                            <!-- INFO GRID -->
                            <div class="info-grid">

                                <!-- SAU NÀY SỬ DỤNG <div class="field-item">
                            <label>Mã chỉ thị sản xuất</label>
                            <div class="val-sub">
                                <?= htmlspecialchars($productionOrderCode ?? '') ?></div>
                        </div> -->

                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub"><?= htmlspecialchars($productCode ?? '') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Mã nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($employeeCode ?? '') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($employeeName ?? '') ?>
                                    </div>

                                </div>

                                <div class="field-item">
                                    <label>Ca làm việc</label>
                                    <?php
                                    $shiftMap = [
                                        'Ca 1'  => 'Ca 1',
                                        'Ca 2'  => 'Ca 2',
                                        'Ca 3'  => 'Ca 3',
                                        'Hành chính' => 'Hành chính',
                                    ];
                                    $rawShift  = $item['shift'] ?? null;
                                    $shiftText = $shiftMap[$rawShift] ?? 'Hành chính';
                                    ?>
                                    <div class="val-sub"><?= htmlspecialchars($shiftText) ?></div>
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
                                        m
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
                                        <div class="qc-meta-item">
                                            <label class="val-text">Mã số nhân viên:</label>
                                            <span class="val-sub"><?= htmlspecialchars($vi['inspector_code'] ?? '-') ?></span>
                                        </div>

                                        <div class="qc-meta-item">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <span class="val-sub"><?= htmlspecialchars($vi['inspector_name'] ?? '-') ?></span>
                                        </div>

                                        <div class="qc-meta-item">
                                            <label class="val-text">Thời điểm kiểm tra:</label>
                                            <span class="val-sub"><?= htmlspecialchars($vi['inspection_time'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="qc-badges">
                                    <?= viBadge('Gel', $defects['gel'] ?? false) ?>
                                    <?= viBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                    <?= viBadge('Màu', $defects['color_issue'] ?? false) ?>
                                    <?= viBadge('In', $defects['print_quality'] ?? false) ?>
                                </div>

                                <div class="qc-note">📝 <?= htmlspecialchars($defects['note'] ?? 'Chưa cập nhật') ?></div>
                            </div>
                            <div class="card-footer-simple">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                        <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>