<?php
// Lấy thông tin nhân viên đã đăng nhập
$currentEmpCode = $_SESSION['user']['employee_code'] ?? '';
$currentEmpName = $_SESSION['user']['employee_name'] ?? '';
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title><?= __('page_extrusion') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusion.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <!-- TIÊU ĐỀ TRANG -->
    <div class="page-header">
        <h1 data-i18n="page_extrusion"><?= __('page_extrusion') ?></h1>
        <!-- <p class="page-subtitle">Khởi tạo chu kỳ sản xuất Bobin mới và ghi nhận thông số kỹ thuật đùn</p> -->
    </div>

    <div class="container">
        <!-- KHUNG CAMERA QUÉT QR -->
        <div id="qr-reader"></div>

        <form id="bobinForm" method="POST" action="/WEB_BOBIN/public/index.php?url=bobin/createBobin">
            <!-- ================= KHỐI 1: ĐỊNH DANH BOBIN & NHÂN SỰ ================= -->
            <div class="form-section-card">
                <div class="section-header">
                    <span class="section-icon">🏷️</span>
                    <div class="section-title-wrap">
                        <span class="section-title">Định danh Bobin & Người phụ trách</span>
                        <span class="section-desc">Quét mã QR hoặc nhập mã Bobin và thông tin nhân viên phụ trách</span>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group primary-highlight">
                        <label>Mã định danh Bobin: <span class="required">*</span></label>
                        <div class="qr-input-group">
                            <div class="suggestions">
                                <input type="text" name="bobin_identification_code" id="bobin_identification_code"
                                    placeholder="<?= __('ph_scan_bobin') ?>" data-i18n-ph="ph_scan_bobin" required autocomplete="off">
                                <div id="bobin_suggestions" class="suggestion-box"></div>
                            </div>
                            <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <path d="M3 14h7v7H3z"></path>
                                </svg>
                                <span data-i18n="btn_scan_qr">Quét QR</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kích thước Bobin: <span class="required">*</span></label>
                        <input type="text" name="bobin_size" id="bobin_size" class="input-readonly" readonly required
                            placeholder="<?= __('ph_auto_size') ?>" data-i18n-ph="ph_auto_size">
                    </div>

                    <div class="form-group">
                        <label>Mã số nhân viên: <span class="required">*</span></label>
                        <input type="text" name="extrusion_employee_code" id="extrusion_employee_code"
                            class="input-readonly" readonly value="<?= htmlspecialchars($currentEmpCode) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Họ tên nhân viên: <span class="required">*</span></label>
                        <input type="text" name="extrusion_employee_name" id="extrusion_employee_name"
                            class="input-readonly" readonly value="<?= htmlspecialchars($currentEmpName) ?>" required>
                    </div>
                </div>
            </div>

            <!-- ================= KHỐI 2: KẾ HOẠCH SẢN XUẤT & MẺ LIỆU ================= -->
            <div class="form-section-card">
                <div class="section-header">
                    <span class="section-icon icon-blue">📋</span>
                    <div class="section-title-wrap">
                        <span class="section-title">THÔNG TIN SẢN XUẤT</span>
                        <span class="section-desc">Mã sản phẩm, Chiều dài, Lot in</span>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Mã sản phẩm: <span class="required">*</span></label>
                        <div class="suggestions">
                            <input type="text" name="product_code" id="product_code"
                                placeholder="<?= __('ph_search_product') ?>" data-i18n-ph="ph_search_product" autocomplete="off" required>
                            <div id="product_suggestions" class="suggestion-box"></div>
                        </div>
                    </div>

                    <!-- Mã chỉ thị SX (PO) ẩn khỏi UI nhưng vẫn nằm trong luồng dữ liệu -->
                    <input type="hidden" name="production_order_code" id="production_order_code" value="">

                    <div class="form-group length-highlight">
                        <label>Chiều dài: <span class="required">*</span> <span class="label-hint">(mét)</span></label>
                        <input type="number" step="1" name="length_m" id="length_m" placeholder="<?= __('ph_length_hint') ?>" data-i18n-ph="ph_length_hint" required
                            min="1" max="5000" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)">
                    </div>

                    <div class="form-group">
                        <label>Loại Bobin: <span class="required">*</span></label>
                        <select name="bobin_type" id="bobin_type" required>
                            <option value="Sản xuất">Sản xuất</option>
                            <option value="Bù">Bù</option>
                            <option value="Điều chỉnh (Do CP)">Điều chỉnh (Do CP)</option>
                            <option value="Điều chỉnh (Ngoại quan: Gel)">Điều chỉnh (Ngoại quan: Gel)</option>
                            <option value="Điều chỉnh (Ngoại quan: Dị vật)">Điều chỉnh (Ngoại quan: Dị vật)</option>
                            <option value="Điều chỉnh (Ngoại quan: Trầy)">Điều chỉnh (Ngoại quan: Trầy)</option>
                            <option value="Điều chỉnh (Ngoại quan: Biến dạng)">Điều chỉnh (Ngoại quan: Biến dạng)
                            </option>
                            <option value="Điều chỉnh (Ngoại quan: Xước)">Điều chỉnh (Ngoại quan: Xước)</option>
                            <option value="Điều chỉnh (Ngoại quan: Chữ in)">Điều chỉnh (Ngoại quan: Chữ in)</option>
                            <option value="Điều chỉnh (Ngoại quan: Vón cục)">Điều chỉnh (Ngoại quan: Vón cục)</option>
                            <option value="Điều chỉnh (Ngoại quan: Màu)">Điều chỉnh (Ngoại quan: Màu)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ca sản xuất: <span class="required">*</span></label>
                        <select name="shift" id="shift" required>
                            <option value="Ca 1">Ca 1</option>
                            <option value="Ca 2">Ca 2</option>
                            <option value="Ca 3">Ca 3</option>
                            <option value="Hành chính">Hành chính</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Số máy đùn: <span class="required">*</span></label>
                        <div class="suggestions">
                            <input type="text" name="machine" id="machine" placeholder="<?= __('ph_select_machine') ?>" data-i18n-ph="ph_select_machine" required
                                autocomplete="off">
                            <div id="machine_suggestions" class="suggestion-box"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vật liệu: <span class="required">*</span></label>
                        <div class="suggestions">
                            <input type="text" name="material" id="material" placeholder="<?= __('ph_select_material') ?>" data-i18n-ph="ph_select_material"
                                required autocomplete="off">
                            <div id="material_suggestions" class="suggestion-box"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Số lần nghiền: <span class="required">*</span> <span class="label-hint">(0, 1 hoặc
                                2)</span></label>
                        <input type="number" name="grinding_time" id="grinding_time" list="list_grinding_time"
                            placeholder="<?= __('ph_grind_hint') ?>" data-i18n-ph="ph_grind_hint" required min="0" max="2" step="1"
                            oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)">
                    </div>

                    <div class="form-group">
                        <label>Lot vật liệu: <span class="required">*</span></label>
                        <div class="suggestions">
                            <input type="text" name="material_lot" id="material_lot" placeholder="<?= __('ph_enter_material_lot') ?>" data-i18n-ph="ph_enter_material_lot"
                                required autocomplete="off">
                            <div id="material_lot_suggestions" class="suggestion-box"></div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Lot in (Print Lot): <span class="required">*</span> <span class="label-hint">(Tự động
                                ghép mã)</span></label>
                        <input type="text" name="print_lot" id="print_lot" class="input-readonly highlight-printlot"
                            readonly required placeholder="<?= __('ph_auto_printlot') ?>" data-i18n-ph="ph_auto_printlot">
                    </div>
                </div>
            </div>

            <!-- ================= KHỐI 3: THÔNG SỐ CUỘN & VỊ TRÍ RACK ================= -->
            <div class="form-section-card">
                <div class="section-header">
                    <span class="section-icon icon-teal">📦</span>
                    <div class="section-title-wrap">
                        <span class="section-title">Thời điểm đùn và vị trí Rack</span>
                        <span class="section-desc">Thời điểm hoàn thành Bobin và vị trí đặt Rack</span>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group rack-highlight">
                        <label>Vị trí Rack: <span class="required">*</span></label>
                        <div class="suggestions">
                            <input type="text" name="rack_code" id="rack_code" placeholder="<?= __('ph_select_rack') ?>" data-i18n-ph="ph_select_rack"
                                autocomplete="off" required>
                            <div id="rack_suggestions" class="suggestion-box"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ngày đùn: <span class="required">*</span></label>
                        <input type="date" name="extrusion_date" id="extrusion_date" required
                            value="<?php echo (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d'); ?>">
                    </div>

                    <!-- THỜI GIAN HOÀN THÀNH CUỘN -->
                    <div class="form-group full-width time-group-wrapper">
                        <div class="time-header">
                            <label>Thời gian hoàn thành: <span class="required">*</span></label>
                            <label class="manual-time-label">
                                <input type="checkbox" id="manual_time_toggle"> 🕒 Chọn thời gian thủ công
                            </label>
                        </div>
                        <div class="time-input-container">
                            <input type="text" name="finish_time" id="finish_time" class="input-realtime" readonly
                                required>
                            <input type="datetime-local" id="manual_datetime_picker" step="1" style="display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= KHỐI 4: TỰ KIỂM TRA NGOẠI QUAN ĐÙN ================= -->
            <div class="form-section-card">
                <div class="section-header">
                    <span class="section-icon icon-amber">🛡️</span>
                    <div class="section-title-wrap">
                        <span class="section-title">Nhóm đùn Check lần 1</span>
                        <span class="section-desc">Xác nhận chất lượng ban đầu trước công đoạn
                            QC</span>
                    </div>
                </div>

                <div class="ext-checks-group">
                    <?php
                    $checks = [
                        'diameter'       => 'Đường kính',
                        'gel'            => 'Gel',
                        'foreign_object' => 'Dị vật',
                        'color'          => 'Màu sắc',
                        'print'          => 'Chữ in'
                    ];
                    foreach ($checks as $key => $label):
                    ?>
                        <div class="ext-check-box">
                            <span class="check-box-label"><?= $label ?></span>
                            <button type="button" class="ext-toggle-btn active" data-field="ext_check_<?= $key ?>"
                                data-value="true" onclick="this.classList.toggle('active'); 
                                         const isActive = this.classList.contains('active');
                                         this.dataset.value = isActive ? 'true' : 'false';
                                         this.textContent = isActive ? 'OK' : 'NG';">
                                OK
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ================= NÚT BẤM HÀNH ĐỘNG ================= -->
            <div class="btn-group">
                <button type="reset" class="btn btn-secondary" data-i18n="btn_refresh">🔄 <?= __('btn_refresh', 'Làm mới') ?></button>
                <button type="submit" class="btn btn-primary" data-i18n="btn_save_bobin">💾 <?= __('btn_save_bobin', 'Lưu Bobin') ?></button>
            </div>
        </form>
    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/scanQR.js?v=<?= time() ?>"></script>
</body>

</html>