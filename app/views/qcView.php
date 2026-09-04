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
            $query['url'] = 'bobin/listBobinView_QC';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_QC.css?v=<?= time() ?>">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>
<link rel="icon" href="data:,">

</head>


<body>
    <div class="menu-bar">
        <div class="menu-left">
            <a href="#">Nhóm đùn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC">Nhóm QC</a>
            <a href="#">Nhóm Cuộn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView">Danh sách chờ hủy</a>
        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    </div>
    </div>

    <h1>Nhóm QC - Kiểm tra chất lượng Bobin</h1>

    <!-- Khung hiển thị camera (mặc định ẩn) -->
    <div id="qr-reader"></div>
    <div class="container">
        <!-- CONTROL BAR -->
        <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
            <input type="hidden" name="url" value="bobin/listBobinView_QC">

            <div class="qr-search-group">
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5
                        6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                    </svg>
                    <input type="text" name="keyword" id="searchKeyword" placeholder="Nhập hoặc quét mã Bobin..."
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                </div>

                <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                    Quét QR
                </button>

                <button class="btn-filter" type="submit">Lọc</button>
            </div>
        </form>


        <!-- LIST -->
        <div class="list-card">
            <div class="header-row"
                style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <h2 style="margin:0;">Số lượng Bobin: <?= count($bobins) ?></h2>
                <?php $currentStatus = $_GET['status'] ?? 'all'; ?>
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
                            'busy_unchecked' => 'status_busy_unchecked',
                            default => 'status_unknown'
                        };
                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $productionOrderCode = '';
                        $productCode = '';
                        $extrusion_employeeName = '';
                        $extrusion_employeeCode = '';
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
                                $productionOrderCode = $decoded['production_order_code'] ?? '';
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
                                        'Busy_Unchecked'  => 'Chưa kiểm tra QC',
                                        default => $rawStatus, // Giữ nguyên nếu không khớp
                                    };
                                    echo htmlspecialchars($displayStatus); ?>
                                </div>

                            </div>
                            <!-- INFO GRID -->
                            <div class="info-grid">

                                <!--SAU NÀY SỬ DỤNG <div class="field-item">
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
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode ?? '') ?></div>
                                </div>

                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName ?? '') ?>
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

                                        <div class="qc-input">
                                            <label class="val-text">Mã số nhân viên:</label>
                                            <input type="text" class="qc-input input-inspector-code"
                                                placeholder="Nhập mã số nhân viên">
                                            <div class="suggestion-box"></div>
                                        </div>

                                        <div class="qc-input">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <input type="text" class="qc-input input-inspector-name" readonly>
                                        </div>

                                        <div class="qc-meta-item">
                                            <label class="val-text">Thời điểm kiểm tra:</label>
                                            <span class="val-sub">
                                                <?php echo (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'); ?>
                                            </span>
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

                                            <button type="button" class="toggle-switch <?= $goodDefect ? 'active' : '' ?>"
                                                data-value="<?= $goodDefect ? 'true' : 'false' ?>" onclick="this.classList.toggle('active'); 
                 this.dataset.value = this.classList.contains('active') ? 'true' : 'false'; 
                 this.querySelector('.switch-label').textContent = this.classList.contains('active') ? 'OK' : 'NG';">

                                                <span class="switch-label"><?= $goodDefect ? 'OK' : 'NG' ?></span>

                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="qc-note">
                                    <label>📝 Ghi chú:</label>
                                    <input type="text" class="note-field" placeholder="Nhập ghi chú QC...">
                                </div>
                            </div>
                            <div class="card-footer-simple">
                                <?php if (!empty($item['updated_time'])): ?>
                                    <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                        <?= htmlspecialchars($item['updated_time']) ?></div>
                                <?php endif; ?>
                                <div class="button-group">
                                    <button type="button" class="btn-cancel"
                                        onclick="handleCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        Hủy Bobin
                                    </button>
                                    <button type="button" class="btn-confirm"
                                        onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        Xác nhận đã kiểm tra
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Định nghĩa đường dẫn gốc từ PHP
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/QC/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/QC/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/QC/scanQR.js?v=<?= time() ?>"></script>
</body>

</html>