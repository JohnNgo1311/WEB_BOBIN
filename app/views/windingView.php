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
    <title><?= __('page_winding') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_Winding.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- MENU BAR ĐỒNG BỘ THEO VAI TRÒ & TỰ SÁNG THEO URL -->
    <?php
    $userRole   = $_SESSION['user']['role'] ?? '';
    $currentUrl = $_GET['url'] ?? '';
    $pCount     = $pendingCount ?? (GlobalData::$pendingBobinCount ?? 0);
    ?>
    <div class="menu-bar">
        <div class="menu-left">
            <!-- Nhóm Đùn -->
            <?php if (in_array($userRole, ['extrusion', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/index"
                    class="<?= in_array($currentUrl, ['bobin/index', 'bobin/extrusion', '']) ? 'active-nav' : '' ?>"
                    data-i18n="nav_extrusion">
                    <?= __('nav_extrusion') ?>
                </a>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView"
                    class="<?= ($currentUrl === 'bobin/extrusionEditBobinView') ? 'active-nav' : '' ?>"
                    data-i18n="nav_extrusion_edit">
                    <?= __('nav_extrusion_edit') ?>
                </a>
            <?php endif; ?>

            <!-- Nhóm QC -->
            <?php if (in_array($userRole, ['qc', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC"
                    class="<?= ($currentUrl === 'bobin/listBobinView_QC') ? 'active-nav' : '' ?>"
                    data-i18n="nav_qc">
                    <?= __('nav_qc') ?>
                </a>
            <?php endif; ?>

            <!-- Nhóm Cuộn -->
            <?php if (in_array($userRole, ['winding', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView"
                    class="<?= in_array($currentUrl, ['bobin/windingView', 'bobin/listBobinView_Winding']) ? 'active-nav' : '' ?>"
                    data-i18n="nav_winding">
                    <?= __('nav_winding') ?>
                </a>
            <?php endif; ?>

            <!-- Các trang theo dõi công khai -->
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView"
                class="<?= ($currentUrl === 'bobin/listBobinDetailView') ? 'active-nav' : '' ?>"
                data-i18n="nav_bobin_list">
                <?= __('nav_bobin_list') ?>
            </a>

            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
                class="<?= ($currentUrl === 'bobin/listBobinHistoryView') ? 'active-nav' : '' ?>"
                data-i18n="nav_bobin_history">
                <?= __('nav_bobin_history') ?>
            </a>

            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView"
                class="menu-pending-link <?= ($currentUrl === 'bobin/listPendingCancellationView') ? 'active-nav' : '' ?>">
                <span data-i18n="nav_pending_cancel"><?= __('nav_pending_cancel') ?></span>
                <?php if ($pCount > 0): ?>
                    <span class="badge-pending-count"><?= $pCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Quản trị viên: Danh sách nhân viên -->
            <?php if ($userRole === 'admin'): ?>
                <a href="/WEB_BOBIN/public/index.php?url=employee/index"
                    class="<?= (strpos($currentUrl, 'employee') === 0) ? 'active-nav' : '' ?>"
                    data-i18n="nav_employee_list">
                    <?= __('nav_employee_list') ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="menu-right">
            <?php require ROOT_PATH . '/app/views/components/languageSwitcher.php'; ?>
            <?php if (isset($_SESSION['user'])): ?>
                <span style="color:#cbd5e1; font-size:13px; font-weight:600; margin-right:8px;">
                    👤 <?= htmlspecialchars($_SESSION['user']['employee_name']) ?> (<?= strtoupper($userRole) ?>)
                </span>
                <a href="/WEB_BOBIN/public/index.php?url=auth/changePassword" class="btn-change-pwd" title="Đổi mật khẩu tài khoản" data-i18n="nav_change_pwd"><?= __('nav_change_pwd') ?></a>
                <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn" data-i18n="nav_logout"><?= __('nav_logout') ?></a>
            <?php else: ?>
                <a href="/WEB_BOBIN/public/index.php?url=auth/login"
                    style="background:#2563eb; color:#fff; padding:6px 14px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600;" data-i18n="nav_login">
                    <?= __('nav_login') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-header">
        <h1 data-i18n="page_winding"><?= __('page_winding') ?></h1>
        <!-- <p class="page-subtitle">Xác nhận thông tin máy cuộn và hoàn tất chu kỳ sản xuất</p> -->
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR HIỆN ĐẠI ĐỒNG BỘ -->
        <div class="control-bar-modern">
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
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <path d="M3 14h7v7H3z"></path>
                        </svg>
                        <span>Quét QR</span>
                    </button>
                    <button class="btn-modern btn-filter" type="submit">
                        Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- DANH SÁCH BOBIN CHỜ CUỘN -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chờ cuộn: <span class="counter-badge"><?= (int)($pagination['totalRecords'] ?? count($bobins)) ?></span></h2>
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
                        $displayStatus = strtolower($rawStatus) === 'rolled' ? 'Đã hoàn thành cuộn' : 'Đang chờ cuộn';
                        ?>

                        <div class="bobin-item <?= $statusClass ?>"
                            data-key="<?= htmlspecialchars($item['bobin_key_code']) ?>"
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

                            <!-- DẢI THÔNG TIN KIỂM TRA ĐÙN & QC (THAM KHẢO) -->
                            <div class="inspection-review-container">
                                <!-- ĐÙN TỰ KIỂM TRA -->
                                <div class="inspection-strip">
                                    <span class="strip-label">🏭 Đùn tự kiểm tra:</span>
                                    <div class="qc-badges">
                                        <?php
                                        $extChecks = [
                                            'Đường kính' => $extCheck['diameter'] ?? true,
                                            'Gel'        => $extCheck['gel'] ?? true,
                                            'Dị vật'     => $extCheck['foreign_object'] ?? true,
                                            'Màu'        => $extCheck['color'] ?? true,
                                            'Chữ in'     => $extCheck['print'] ?? true
                                        ];
                                        foreach ($extChecks as $lbl => $isOk):
                                        ?>
                                            <div class="vi-item <?= $isOk ? 'badge-ok' : 'badge-ng' ?>">
                                                <span><?= $lbl ?></span><strong><?= $isOk ? 'OK' : 'NG' ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- QC ĐÁNH GIÁ -->
                                <div class="inspection-strip qc-strip">
                                    <div class="strip-title-wrapper">
                                        <span class="strip-label">🛡️ QC đánh giá:</span>
                                        <span class="inspector-badge">NV: <?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa rõ') ?></span>
                                    </div>
                                    <div class="qc-badges">
                                        <div class="vi-item <?= ($defects['gel'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Gel</span><strong><?= ($defects['gel'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div class="vi-item <?= ($defects['foreign_object'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Dị vật</span><strong><?= ($defects['foreign_object'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div class="vi-item <?= ($defects['color_issue'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Màu</span><strong><?= ($defects['color_issue'] ?? false) ? 'NG' : 'OK' ?></strong>
                                        </div>
                                        <div class="vi-item <?= ($defects['print_quality'] ?? false) ? 'badge-ng' : 'badge-ok' ?>">
                                            <span>Chữ in</span><strong><?= ($defects['print_quality'] ?? false) ? 'NG' : 'OK' ?></strong>
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
                                                placeholder="Nhập hoặc chọn máy cuộn..." autocomplete="off">
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

                                <!-- HÀNG 2: GHI CHÚ CUỘN -->
                                <div class="winding-note">
                                    <label>Ghi chú cuộn (Bắt buộc nếu yêu cầu hủy):</label>
                                    <input type="text" class="note-field winding-note-field"
                                        placeholder="Nhập ghi chú máy cuộn, tình trạng bất thường (nếu có)...">
                                </div>
                            </div>

                            <!-- CARD FOOTER & NÚT BẤM HOÀN TẤT -->
                            <div class="card-footer-simple">
                                <div class="update-time">🕒 Cập nhật lúc: <?= htmlspecialchars($item['updated_time'] ?? '') ?></div>
                                <div class="button-group">
                                    <button type="button" class="btn-cancel"
                                        onclick="handleWindingCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        🗑️ Hủy Bobin
                                    </button>
                                    <button type="button" class="btn-confirm"
                                        onclick="handleWindingConfirm(
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
    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
</body>

</html>