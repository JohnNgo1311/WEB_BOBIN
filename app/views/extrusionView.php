<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Extrusion - Nhập liệu Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusion.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <!-- MENU BAR -->
    <div class="menu-bar">
        <div class="menu-left">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="active-nav">Nhóm đùn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView">Điều chỉnh thông tin Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView" class="menu-pending-link">
                Danh sách chờ hủy
                <span class="badge-pending-count" id="badgePendingCancellation"
                    style="<?= (GlobalData::$pendingBobinCount > 0) ? '' : 'display: none;' ?>">
                    <?= GlobalData::$pendingBobinCount ?>
                </span>
            </a>
        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>

    <!-- TIÊU ĐỀ CHÍNH -->
    <div class="page-header">
        <h1>Nhóm đùn - Nhập thông tin Bobin</h1>
    </div>

    <div id="qr-reader"></div>

    <form id="bobinForm">
        <!-- PHÂN KHU 1: ĐỊNH DANH & KẾ HOẠCH -->
        <div class="form-section-header">
            <span class="section-badge">1</span>
            <span>Định danh Bobin & Nhân sự</span>
        </div>

        <div class="form-group primary-highlight">
            <label>Mã định danh Bobin: <span class="required">*</span></label>
            <div class="qr-input-group">
                <div class="suggestions">
                    <input type="text" name="bobin_identification_code" id="bobin_identification_code"
                        placeholder="Nhập hoặc quét mã Bobin..." required autocomplete="off">
                    <div id="bobin_suggestions" class="suggestion-box"></div>
                </div>
                <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                    📷 Quét QR
                </button>
            </div>
        </div>
        <div class="form-group">
            <label>Kích thước Bobin:</label>
            <input type="text" name="bobin_size" id="bobin_size" class="input-readonly" readonly
                placeholder="Tự động theo mã Bobin">
        </div>

        <div class="form-group">
            <label>Mã số nhân viên: <span class="required">*</span></label>
            <div class="suggestions">
                <input type="text" name="extrusion_employee_code" id="extrusion_employee_code"
                    placeholder="Nhập mã nhân viên đùn..." autocomplete="off" required>
                <div id="employee_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Họ tên nhân viên:</label>
            <input type="text" name="extrusion_employee_name" id="extrusion_employee_name" class="input-readonly"
                readonly placeholder="Tự động cập nhật tên">
        </div>

        <!-- PHÂN KHU 2: THÔNG TIN SẢN XUẤT -->
        <div class="form-section-header">
            <span class="section-badge badge-teal">2</span>
            <span>Thông tin sản xuất Bobin</span>
        </div>

        <div class="form-group">
            <label>Mã sản phẩm: <span class="required">*</span></label>
            <div class="suggestions">
                <input type="text" name="product_code" id="product_code" placeholder="Gõ để tìm mã sản phẩm..."
                    autocomplete="off" required>
                <div id="product_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Mã chỉ thị sản xuất (Tạm thời):</label>
            <input type="text" name="production_order_code" id="production_order_code" class="input-readonly" readonly
                placeholder="Tự động theo sản phẩm">
        </div>
        <div class="form-group">
            <label>Loại Bobin: <span class="required">*</span></label>
            <select name="bobin_type" required>
                <option value="Sản xuất">Sản xuất</option>
                <option value="Bù">Bù</option>
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
        </div>

        <div class="form-group">
            <label>Ca sản xuất: <span class="required">*</span></label>
            <select name="shift" required>
                <option value="Ca 1">Ca 1</option>
                <option value="Ca 2">Ca 2</option>
                <option value="Ca 3">Ca 3</option>
                <option value="Hành chính">Hành chính</option>
            </select>
        </div>





        <div class="form-group">
            <label>Số máy: <span class="required">*</span></label>
            <div class="suggestions">
                <input type="text" id="machine" placeholder="Chọn số máy đùn..." required autocomplete="off">
                <div id="machine_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Vật liệu: <span class="required">*</span></label>
            <div class="suggestions">
                <input type="text" id="material" placeholder="Chọn loại vật liệu..." required autocomplete="off">
                <div id="material_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Số lần nghiền: <span class="required">*</span></label>
            <input type="number" id="grinding_time" list="list_grinding_time" placeholder="Nhập 0, 1 hoặc 2" required
                min="0" max="2" step="1" oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)">
        </div>

        <div class="form-group">
            <label>Lot in (Print Lot):</label>
            <input type="text" name="print_lot" id="print_lot" class="input-readonly highlight-printlot" readonly
                placeholder="Tự động ghép mã Lot in">
        </div>

        <div class="form-group">
            <label>Lot vật liệu: <span class="required">*</span></label>
            <div class="suggestions">
                <input type="text" name="material_lot" id="material_lot" placeholder="Nhập Lot vật liệu..." required
                    autocomplete="off">
                <div id="material_lot_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group length-highlight">
            <label>Chiều dài (m): <span class="required">*</span></label>
            <input type="number" step="1" name="length_m" placeholder="Nhập chiều dài bobin..." required min="0"
                max="5000" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)">
        </div>

        <div class="form-group rack-highlight">
            <label>Vị trí đặt (Rack):</label>
            <div class="suggestions">
                <input type="text" name="rack_code" id="rack_code" placeholder="Chọn vị trí Rack đặt Bobin"
                    autocomplete="off">
                <div id="rack_suggestions" class="suggestion-box"></div>
            </div>
        </div>

        <div class="form-group">
            <label>Ngày đùn:</label>
            <input type="date" name="extrusion_date"
                value="<?php echo (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d'); ?>">
        </div>

        <!-- THỜI GIAN HOÀN THÀNH CUỘN -->
        <div class="form-group full-width time-group-wrapper">
            <div class="time-header">
                <label>Thời gian hoàn thành cuộn:</label>
                <label class="manual-time-label">
                    <input type="checkbox" id="manual_time_toggle"> 🕒 Chọn thời gian thủ công
                </label>
            </div>
            <div class="time-input-container">
                <input type="text" name="finish_time" id="finish_time" class="input-realtime" readonly>
                <input type="datetime-local" id="manual_datetime_picker" step="1" style="display: none;">
            </div>
        </div>

        <!-- PHÂN KHU 3: NGOẠI QUAN ĐÙN -->
        <div class="form-section-header">
            <span class="section-badge badge-amber">3</span>
            <span>🛡️Đùn Check </span>
        </div>

        <div class="form-group full-width">
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

        <!-- NÚT BẤM HÀNH ĐỘNG -->
        <div class="btn-group">
            <button type="reset" class="btn btn-secondary">🔄 Làm mới</button>
            <button type="submit" class="btn btn-primary">💾 Lưu Bobin</button>
        </div>
    </form>

    <script>
    const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/scanQR.js?v=<?= time() ?>"></script>
</body>

</html>