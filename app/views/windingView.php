<?php
$bobins = $data['bobins'] ?? [];
$pagination = $data['pagination'] ?? [];
$pendingCount = $data['pendingCount'] ?? 0;
$userRole = $_SESSION['user']['role'] ?? '';

// Lấy thông tin tài khoản đăng nhập hiện tại
$currentEmpCode = $_SESSION['user']['employee_code'] ?? '';
$currentEmpName = $_SESSION['user']['employee_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Nhóm Cuộn - Xác nhận hoàn thành Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_Winding.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- MENU BAR PHÂN QUYỀN ĐỘNG -->
    <div class="menu-bar">
        <div class="menu-left">
            <?php if (in_array($userRole, ['extrusion', 'admin'])): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index">Nhóm đùn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView">Điều chỉnh đùn</a>
            <?php endif; ?>

            <?php if (in_array($userRole, ['qc', 'admin'])): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC">QC</a>
            <?php endif; ?>

            <?php if (in_array($userRole, ['winding', 'admin'])): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView" class="active-nav">Cuộn</a>
            <?php endif; ?>

            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView" class="menu-pending-link">
                Danh sách chờ hủy
                <?php if ($pendingCount > 0): ?>
                <span class="badge-pending-count"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="menu-right">
            <?php if (isset($_SESSION['user'])): ?>
            <span style="color:#cbd5e1; font-size:13px; font-weight:600; margin-right:8px;">
                👤 <?= htmlspecialchars($_SESSION['user']['employee_name']) ?> (<?= strtoupper($userRole) ?>)
            </span>
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
            <?php else: ?>
            <a href="/WEB_BOBIN/public/index.php?url=auth/login"
                style="background:#2563eb; color:#fff; padding:6px 14px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600;">Đăng
                nhập</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-header">
        <h1>Nhóm cuộn - Xác nhận hoàn thành Bobin</h1>
        <p class="page-subtitle">Kiểm tra thông khí, ghi nhận máy cuộn và xác nhận hoàn tất chu kỳ sản xuất</p>
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar-modern">
            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/windingView">
                    <div class="control-row">
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="keyword" id="searchKeyword"
                                placeholder="Nhập hoặc quét mã Bobin..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            📷 Quét QR
                        </button>
                        <button class="btn-modern btn-filter" type="submit">
                            Lọc
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DANH SÁCH BOBIN ĐÃ KIỂM TRA QC CHỜ CUỘN -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chờ cuộn: <span class="counter-badge"><?= count($bobins) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
            <div class="empty-state">Hiện tại không có Bobin nào đang chờ cuộn.</div>
            <?php else: ?>
            <div class="bobin-list">
                <?php foreach ($bobins as $item): ?>
                <?php
                        $products = json_decode($item['products'] ?? '{}', true) ?: [];
                        $productCode = $products['product_code'] ?? 'Chưa cập nhật';

                        $extrusionEmployee = json_decode($item['extrusion_employee'] ?? '{}', true) ?: [];
                        $extEmpCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extEmpName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';

                        $matLotData = json_decode($item['material_lot'] ?? '{}', true) ?: [];
                        $matLot = $matLotData['lot'] ?? 'Chưa cập nhật';

                        $rackData = json_decode($item['rack'] ?? '{}', true) ?: [];
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';

                        $extCheck = json_decode($item['extrusion_check'] ?? '{}', true) ?: [];
                        $vi = json_decode($item['visual_inspection'] ?? '{}', true) ?: [];
                        $defects = $vi['defects'] ?? [];

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finishTime = $item['finish_time'] ?? '';
                        $formatted_time = !empty($finishTime) ? date('dmY$His', strtotime($finishTime)) : '';
                        $mainInfoText = "{$productCode}\${$matLot}\${$printLot}\${$formatted_time}";

                        $rawStatus = $item['bobin_current_status'] ?? 'Busy_Checked';
                        $statusClass = strtolower($rawStatus) === 'rolled' ? 'status_rolled' : 'status_busy_checked';
                        $displayStatus = strtolower($rawStatus) === 'rolled' ? 'Đã hoàn thành cuộn' : 'Đã kiểm tra QC (Chờ cuộn)';
                        ?>

                <div class="bobin-item <?= $statusClass ?>" data-key="<?= htmlspecialchars($item['bobin_key_code']) ?>"
                    data-ident="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
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
                            <div class="val-sub font-bold-blue"><?= htmlspecialchars($productCode) ?></div>
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
                            <div class="val-sub"><span class="val-rack"><?= htmlspecialchars($rackCode) ?></span></div>
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
                            <div class="val-sub" data-type="<?= htmlspecialchars($item['bobin_type'] ?? '') ?>">
                                <?= htmlspecialchars($item['bobin_type'] ?? 'Chưa cập nhật') ?>
                            </div>
                        </div>
                        <div class="field-item">
                            <label>Lot vật liệu</label>
                            <div class="val-sub"><?= htmlspecialchars($matLot) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Lot in</label>
                            <div class="val-sub highlight-printlot-text"><?= htmlspecialchars($printLot) ?></div>
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

                    <!-- QUY TRÌNH 3 CỘT (ĐÙN CHECK | QC CHECK | CUỘN THỰC THI) -->
                    <div class="winding-inspection-grid">
                        <!-- CỘT 1: ĐÙN TỰ KIỂM TRA -->
                        <div class="pipeline-col">
                            <div class="pipeline-title">🏭 Đùn tự kiểm tra</div>
                            <div class="qc-badges-static">
                                <?php
                                        $extChecks = [
                                            'Đ.Kính' => $extCheck['diameter'] ?? true,
                                            'Gel'    => $extCheck['gel'] ?? true,
                                            'Dị vật' => $extCheck['foreign_object'] ?? true,
                                            'Màu'    => $extCheck['color'] ?? true,
                                            'Chữ in' => $extCheck['print'] ?? true
                                        ];
                                        foreach ($extChecks as $lbl => $isOk):
                                        ?>
                                <div class="vi-item <?= $isOk ? 'badge-ok' : 'badge-ng' ?>">
                                    <span><?= $lbl ?></span><strong><?= $isOk ? 'OK' : 'NG' ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- CỘT 2: KẾT QUẢ KIỂM TRA QC -->
                        <div class="pipeline-col">
                            <div class="pipeline-title">
                                <span>🛡️ QC Đánh giá</span>
                                <span class="pipeline-submeta">NV:
                                    <?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa rõ') ?></span>
                            </div>
                            <div class="qc-badges-static">
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
                            </div>
                            <?php if (!empty($defects['note']) && $defects['note'] !== 'Chưa cập nhật'): ?>
                            <div class="qc-note-text">📝 <?= htmlspecialchars($defects['note']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- CỘT 3: NHẬP LIỆU NHÓM CUỘN -->
                        <div class="pipeline-col">
                            <div class="pipeline-title">📍 Ghi nhận thông tin cuộn</div>
                            <div class="winding-inputs-row">
                                <div class="winding-field-group">
                                    <label>Máy cuộn: <span class="required">*</span></label>
                                    <div class="suggestion-wrapper">
                                        <input type="text" class="winding-input winding-machine"
                                            placeholder="Nhập máy..." autocomplete="off">
                                        <div class="suggestion-box"></div>
                                    </div>
                                </div>

                                <!-- TỰ ĐỘNG ĐIỀN THEO TÀI KHOẢN ĐĂNG NHẬP -->
                                <div class="winding-field-group">
                                    <label>Mã NV Cuộn:</label>
                                    <input type="text" class="winding-input winding-employee-code input-readonly"
                                        value="<?= htmlspecialchars($currentEmpCode) ?>" readonly>
                                </div>
                                <div class="winding-field-group">
                                    <label>Họ tên NV:</label>
                                    <input type="text" class="winding-input winding-employee-name input-readonly"
                                        value="<?= htmlspecialchars($currentEmpName) ?>" readonly>
                                </div>
                            </div>
                            <div class="winding-note-row">
                                <label>Ghi chú cuộn (Bắt buộc nếu yêu cầu hủy):</label>
                                <input type="text" class="winding-input winding-note"
                                    placeholder="Nhập ghi chú máy cuộn (nếu có)...">
                            </div>
                        </div>
                    </div>

                    <div class="card-footer-simple">
                        <div class="update-time">🕒 Cập nhật lúc: <?= htmlspecialchars($item['updated_time'] ?? '') ?>
                        </div>
                        <div class="button-group">
                            <button type="button" class="btn-cancel" onclick="handleWindingCancel(this)">🗑️ Hủy
                                Bobin</button>
                            <button type="button" class="btn-confirm" onclick="handleWindingConfirm(this)">💾 Xác nhận
                                hoàn thành</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PHÂN TRANG -->
            <?php if (isset($pagination['totalPages']) && $pagination['totalPages'] > 1): ?>
            <div class="pagination-wrapper">
                <?php if ($pagination['currentPage'] > 1): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView&page=<?= $pagination['currentPage'] - 1 ?>"
                    class="page-btn">‹ Trước</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView&page=<?= $p ?>"
                    class="page-btn <?= ($p === (int)$pagination['currentPage']) ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView&page=<?= $pagination['currentPage'] + 1 ?>"
                    class="page-btn">Sau ›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Winding/scanQR.js?v=<?= time() ?>"></script>
</body>

</html>