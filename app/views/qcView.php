<?php
$bobins = $data['bobins'] ?? [];
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
    <title><?= __('page_qc') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobin_QC.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/scanQR.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/qrScannerHelper.js?v=<?= time() ?>"></script>
</head>

<body>
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-header">
        <h1 data-i18n="page_qc"><?= __('page_qc') ?></h1>
        <!-- <p class="page-subtitle">Kiểm soát ngoại quan trước khi chuyển sang công đoạn cuộn</p> -->
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar-modern">
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                <input type="hidden" name="url" value="bobin/listBobinView_QC">
                <div class="control-row">
                    <div class="search-box-modern">
                        <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                            stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="<?= __('ph_scan_bobin') ?>"
                            data-i18n-ph="ph_scan_bobin" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
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

        <!-- DANH SÁCH BOBIN CHỜ QC -->
        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chờ kiểm tra: <span
                        class="counter-badge"><?= (int)($totalWaiting ?? count($bobins)) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
                <div class="empty-state">Hiện tại không có Bobin nào đang chờ kiểm tra QC.</div>
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

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finishTime = $item['finish_time'] ?? '';
                        $formatted_time = !empty($finishTime) ? date('dmY$His', strtotime($finishTime)) : '';
                        $mainInfoText = "{$productCode}\${$matLot}\${$printLot}\${$formatted_time}";
                        ?>

                        <div class="bobin-item status_busy_unchecked"
                            data-key="<?= htmlspecialchars($item['bobin_key_code']) ?>"
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
                                <div class="status-badge">CHƯA KIỂM TRA QC</div>
                            </div>

                            <!-- 12 THÔNG SỐ SẢN XUẤT -->
                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub">
                                        <span class="bobin-highlight-product"><?= htmlspecialchars($productCode) ?></span>
                                    </div>
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
                                    <?php
                                    $isRackEmpty = empty($rackCode) || $rackCode === 'Chưa cập nhật' || $rackCode === '---';
                                    ?>
                                    <div class="val-sub">
                                        <span class="bobin-highlight-rack <?= $isRackEmpty ? 'rack-empty' : '' ?>">
                                            <?= $isRackEmpty ? '---' : ('📍 ' . htmlspecialchars($rackCode)) ?>
                                        </span>
                                    </div>
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
                                    <?php
                                    $rawBobinType = trim($item['bobin_type'] ?? '');
                                    $typeBadgeClass = match (true) {
                                        $rawBobinType === 'Sản xuất' => 'type-san-xuat',
                                        $rawBobinType === 'Bù'       => 'type-bu',
                                        default                     => 'type-dieu-chinh'
                                    };
                                    ?>
                                    <div class="val-sub">
                                        <span class="bobin-type-badge <?= $typeBadgeClass ?>" data-type="<?= htmlspecialchars($rawBobinType) ?>" title="<?= htmlspecialchars($rawBobinType) ?>">
                                            <?= htmlspecialchars(!empty($rawBobinType) ? $rawBobinType : 'Chưa cập nhật') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Lot vật liệu</label>
                                    <div class="val-sub"><?= htmlspecialchars($matLot) ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub">
                                        <span class="bobin-highlight-printlot <?= empty($printLot) || $printLot === 'Chưa cập nhật' ? 'lot-empty' : '' ?>">
                                            <?= htmlspecialchars($printLot) ?>
                                        </span>
                                    </div>
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

                            <!-- ĐÙN CHECK THAM KHẢO -->
                            <div class="ext-check-strip">
                                <span class="ext-title">🏭 Đùn tự kiểm tra:</span>
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

                            <!-- KHU VỰC THAO TÁC CỦA QC -->
                            <div class="qc-section">
                                <div class="qc-section-title">
                                    <span>🛡️ Kiểm tra ngoại quan QC</span>
                                    <span class="qc-time-now">🕒 Hiện tại: <?= date('d/m/Y H:i') ?></span>
                                </div>

                                <div class="qc-form-row">
                                    <!-- TỰ ĐỘNG ĐIỀN THEO TÀI KHOẢN ĐĂNG NHẬP -->
                                    <div class="qc-input-group">
                                        <label>Mã NV QC:</label>
                                        <input type="text" class="qc-input input-inspector-code input-readonly"
                                            value="<?= htmlspecialchars($currentEmpCode) ?>" readonly>
                                    </div>
                                    <div class="qc-input-group">
                                        <label>Họ tên NV QC:</label>
                                        <input type="text" class="qc-input input-inspector-name input-readonly"
                                            value="<?= htmlspecialchars($currentEmpName) ?>" readonly>
                                    </div>
                                </div>

                                <!-- 4 NÚT KIỂM SOÁT NGOẠI QUAN -->
                                <div class="qc-switches-label">Kết quả kiểm tra ngoại quan:</div>
                                <div class="qc-badges" style="margin-bottom: 8px;">
                                    <?php
                                    $qcItems = ['gel' => 'Gel', 'foreign_object' => 'Dị vật', 'color_issue' => 'Màu sắc', 'print_quality' => 'Chữ in'];
                                    foreach ($qcItems as $key => $lbl):
                                    ?>
                                        <div class="vi-item-switch">
                                            <span class="switch-title"><?= $lbl ?></span>
                                            <button type="button" class="toggle-switch active" data-defect="<?= $key ?>"
                                                data-value="false" onclick="this.classList.toggle('active'); 
                                                         const isOk = this.classList.contains('active');
                                                         this.dataset.value = isOk ? 'false' : 'true'; 
                                                         this.textContent = isOk ? 'OK' : 'NG';">
                                                OK
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="qc-note">
                                    <label>Ghi chú QC (Bắt buộc khi đổi loại hoặc báo hủy):</label>
                                    <input type="text" class="note-field" placeholder="<?= __('qc_note_explain_ph') ?>"
                                        data-i18n-ph="qc_note_explain_ph">
                                </div>
                            </div>

                            <div class="card-footer-simple">
                                <div class="update-time">🕒 Tạo lúc: <?= htmlspecialchars($item['updated_time'] ?? '') ?></div>
                                <div class="button-group">
                                    <button type="button" class="btn-cancel" data-i18n="btn_cancel_bobin"
                                        onclick="handleCancel(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        🗑️ <?= __('btn_cancel_bobin') ?>
                                    </button>
                                    <button type="button" class="btn-change-type"
                                        onclick="handleChangeType(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        ⚠️ Đổi thành "ĐIỀU CHỈNH"
                                    </button>
                                    <button type="button" class="btn-confirm"
                                        onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>', '<?= htmlspecialchars($item['bobin_key_code']) ?>')">
                                        💾 Xác nhận đã kiểm tra
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL ĐỔI LOẠI BOBIN ĐIỀU CHỈNH -->
    <div id="modalChangeType" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h3>Đổi loại Bobin sang "Điều chỉnh"</h3>
            <p>Vui lòng chọn loại điều chỉnh cụ thể:</p>
            <select id="selectNewBobinType" class="modal-select">
                <option value="Điều chỉnh (Do CP)">Điều chỉnh (Do CP)</option>
                <option value="Điều chỉnh (Ngoại quan: Gel)">Điều chỉnh (Ngoại quan: Gel)</option>
                <option value="Điều chỉnh (Ngoại quan: Dị vật)">Điều chỉnh (Ngoại quan: Dị vật)</option>
                <option value="Điều chỉnh (Ngoại quan: Trầy)">Điều chỉnh (Ngoại quan: Trầy)</option>
                <option value="Điều chỉnh (Ngoại quan: Biến dạng)">Điều chỉnh (Ngoại quan: Biến dạng)</option>
                <option value="Điều chỉnh (Ngoại quan: Xước)">Điều chỉnh (Ngoại quan: Xước)</option>
                <option value="Điều chỉnh (Ngoại quan: Chữ in)">Điều chỉnh (Ngoại quan: Chữ in)</option>
                <option value="Điều chỉnh (Ngoại quan: Vón cục)">Điều chỉnh (Ngoại quan: Vón cục)</option>
                <option value="Điều chỉnh (Ngoại quan: Màu)">Điều chỉnh (Ngoại quan: Màu)</option>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModalChangeType()">Hủy bỏ</button>
                <button type="button" class="btn-modal-submit" onclick="submitChangeType()">Xác nhận đổi loại</button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/QC/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/QC/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/QC/scanQR.js?v=<?= time() ?>"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=1"></script>
</body>

</html>