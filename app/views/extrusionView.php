<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Extrusion - Nhập liệu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusion.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <div class="menu-bar">
        <div class="menu-left">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index">Nhóm đùn</a>
            <a href="#">QC</a>
            <a href="#">Cuộn</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView">Điều chỉnh thông tin
                Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView">Danh sách Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView">Lịch sử Bobin</a>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView">Danh sách chờ hủy</a>

        </div>
        <div class="menu-right">
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </div>
    </div>
    </div>

    <h1>Nhóm đùn - Nhập thông tin Bobin</h1>
    <!-- Khung hiển thị camera (mặc định ẩn) -->
    <div id="qr-reader"></div>
    <form id="bobinForm">

        <label>Mã định danh Bobin:</label>

        <!-- Bọc ô input và nút quét trong một flex container -->
        <div class="qr-input-group">
            <div class="suggestions">
                <input type="text" name="bobin_identification_code" id="bobin_identification_code"
                    placeholder="Nhập hoặc quét mã định danh..." required autocomplete="off">
                <div id="bobin_suggestions" class="suggestion-box"></div>
            </div>

            <!-- Nút bật camera -->
            <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                Quét QR
            </button>
        </div>



        <label>Kích thước Bobin:</label>
        <input type="text" name="bobin_size" id="bobin_size" readonly>

        <label>Loại Bobin:</label>
        <select name="bobin_type" required>
            <option value="Sản xuất">Sản xuất</option>
            <option value="Bù">Bù</option>
            <option value="Điều chỉnh (Do CP)">Điều chỉnh (Do CP)</option>
            <option value="Điều chỉnh (Ngoại quan: Gel)">Điều chỉnh (Ngoại quan: Gel)</option>
            <option value="Điều chỉnh (Ngoại quan: Dị vật)">Điều chỉnh (Ngoại quan: Dị vật)</option>
        </select>

        <label>Mã số nhân viên:</label>
        <div class="suggestions">
            <input type="text" name="extrusion_employee_code" id="extrusion_employee_code"
                placeholder="Nhập mã số nhân viên..." autocomplete="off">
            <div id="employee_suggestions" class="suggestion-box"></div>
        </div>

        <label>Họ tên nhân viên:</label>
        <input type="text" name="extrusion_employee_name" id="extrusion_employee_name" readonly>

        <label>Mã sản phẩm:</label>
        <div class="suggestions">
            <input type="text" name="product_code" id="product_code" placeholder="Nhập mã sản phẩm..."
                autocomplete="off">
            <div id="product_suggestions" class="suggestion-box"></div>
        </div>

        <label>Mã chỉ thị sản xuất (Tạm thời):</label>
        <input type="text" name="production_order_code" id="production_order_code" readonly>

        <label>Số máy:</label>
        <div class="suggestions">
            <input type="text" id="machine" placeholder="Nhập số máy..." required autocomplete="off">
            <div id="machine_suggestions" class="suggestion-box"></div>
        </div>

        <label>Vật liệu:</label>
        <div class="suggestions">
            <input type="text" id="material" placeholder="Nhập loại vật liệu sử dụng..." required autocomplete="off">
            <div id="material_suggestions" class="suggestion-box"></div>
        </div>

        <label>Số lần nghiền:</label>
        <input type="number" id="grinding_time" list="list_grinding_time" placeholder="Nhập 0, 1 hoặc 2" required
            min="0" max="2" step="1" oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)">

        <label>Lot in:</label>
        <input type="text" name="print_lot" id="print_lot" readonly>

        <label>Lot vật liệu:</label>
        <div class="suggestions">
            <input type="text" name="material_lot" id="material_lot" placeholder="Nhập Lot vật liệu..." required
                autocomplete="off">
            <div id="material_lot_suggestions" class="suggestion-box"></div>
        </div>

        <label>Chiều dài (m):</label>
        <input type="number" step="1" name="length_m" placeholder="Nhập chiều dài bobin..." required min="0" max="5000"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4)">

        <label>Ca sản xuất:</label>
        <select name="shift" required>
            <!-- <option value="">-- Chọn --</option> -->
            <option value="Ca 1">Ca 1</option>
            <option value="Ca 2">Ca 2</option>
            <option value="Ca 3">Ca 3</option>
            <option value="Hành chính">Hành chính</option>
        </select>

        <label>Ngày đùn:</label>

        <input type="date" name="extrusion_date"
            value="<?php echo (new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d'); ?>">

        <label>Thời gian hoàn thành cuộn:</label>
        <input type="text" name="finish_time" id="finish_time" readonly>

        <div class="btn-group">
            <button type="reset" class="btn btn-secondary">Làm mới</button>
            <button type="submit" class="btn btn-primary">Lưu Bobin</button>
        </div>

    </form>

    <script>
        // Định nghĩa đường dẫn gốc từ PHP
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/logout.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/scanQR.js?v=<?= time() ?>"></script>

</html>