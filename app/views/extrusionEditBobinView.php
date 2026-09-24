<?php
$bobins = $data['bobins'] ?? [];

$typeOptions = [
    'Sản xuất'                         => 'Sản xuất',
    'Bù'                               => 'Bù',
    'Điều chỉnh (Do CP)'               => 'Điều chỉnh (Do CP)',
    'Điều chỉnh (Ngoại quan: Gel)'     => 'Điều chỉnh (Ngoại quan: Gel)',
    'Điều chỉnh (Ngoại quan: Dị vật)' => 'Điều chỉnh (Ngoại quan: Dị vật)',
    'Điều chỉnh (Ngoại quan: Trầy)'    => 'Điều chỉnh (Ngoại quan: Trầy)',
    'Điều chỉnh (Ngoại quan: Biến dạng)' => 'Điều chỉnh (Ngoại quan: Biến dạng)',
    'Điều chỉnh (Ngoại quan: Xước)'    => 'Điều chỉnh (Ngoại quan: Xước)',
    'Điều chỉnh (Ngoại quan: Chữ in)'  => 'Điều chỉnh (Ngoại quan: Chữ in)',
    'Điều chỉnh (Ngoại quan: Vón cục)' => 'Điều chỉnh (Ngoại quan: Vón cục)',
    'Điều chỉnh (Ngoại quan: Màu)'     => 'Điều chỉnh (Ngoại quan: Màu)'
];

if (!function_exists('decodeJsonObject')) {
    function decodeJsonObject($value): array
    {
        if (!is_string($value) || trim($value) === '') return [];
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Điều chỉnh thông tin Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/extrusionEditBobin.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <div class="page-header">
        <h1>Nhóm đùn - Điều chỉnh thông tin Bobin</h1>
        <!-- <p class="page-subtitle">Nhấn "Chỉnh sửa" trên từng Bobin để mở khóa nhập liệu thông số</p> -->
    </div>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR -->
        <div class="control-bar">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="back-btn" title="Quay lại Nhóm Đùn">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>

            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/extrusionEditBobinView">

                <div class="qr-search-group">
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path
                                d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="Nhập hoặc quét mã Bobin..."
                            value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>

                    <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">
                        📷 Quét QR
                    </button>
                    <button type="submit" class="btn-filter" id="btnFilter">Lọc</button>
                </div>
            </form>
        </div>

        <div class="list-card">
            <div class="header-row">
                <h2>Số lượng Bobin chưa kiểm tra QC: <span class="counter-badge"><?= count($bobins) ?></span></h2>
            </div>

            <?php if (empty($bobins)): ?>
            <div class="empty-state">Không có Bobin nào cần điều chỉnh.</div>
            <?php else: ?>
            <div class="bobin-list">
                <?php foreach ($bobins as $item): ?>
                <?php
                        $products = decodeJsonObject($item['products'] ?? '');
                        $productCode = $products['product_code'] ?? '';
                        $productionOrderCode = $products['production_order_code'] ?? '';

                        $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                        $extrusion_employeeCode = $extrusionEmployee['employee_code'] ?? '';
                        $extrusion_employeeName = $extrusionEmployee['employee_name'] ?? '';

                        $materialLotData = decodeJsonObject($item['material_lot'] ?? '');
                        $materialLot = $materialLotData['lot'] ?? '';

                        $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                        $rackData = decodeJsonObject($item['rack'] ?? '');
                        $rackCode = $rackData['code'] ?? '';

                        $chkDiameter      = $extCheck['diameter'] ?? true;
                        $chkGel           = $extCheck['gel'] ?? true;
                        $chkForeignObject = $extCheck['foreign_object'] ?? true;
                        $chkColor         = $extCheck['color'] ?? true;
                        $chkPrint         = $extCheck['print'] ?? true;

                        $rawKey = $item['bobin_key_code'] ?? '';
                        $displayKey = htmlspecialchars($rawKey);
                        if (is_string($rawKey) && strpos($rawKey, '_') !== false) {
                            $parts = explode('_', $rawKey);
                            if (count($parts) >= 7) {
                                $code = array_shift($parts);
                                $date = implode('/', array_slice($parts, 0, 3));
                                $time = implode(':', array_slice($parts, 3, 3));
                                $displayKey = htmlspecialchars("{$code} - {$date} - {$time}");
                            }
                        }

                        $finishTimeRaw = $item['finish_time'] ?? '';
                        $finishTimeVal = '';
                        if (!empty($finishTimeRaw)) {
                            $ts = strtotime($finishTimeRaw);
                            if ($ts) $finishTimeVal = date('Y-m-d\TH:i:s', $ts);
                        }
                        ?>
                <div class="bobin-item status_busy_unchecked"
                    data-bobin-code="<?= htmlspecialchars($item['bobin_identification_code']) ?>">
                    <div class="card-top">
                        <div class="key-info">
                            <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                            <span class="key-code"><?= $displayKey ?></span>
                        </div>
                        <div class="status-badge">
                            ⏳ Chưa kiểm tra QC
                        </div>
                    </div>

                    <!-- CÁC Ô INPUT MẶC ĐỊNH LÀ DISABLED -->
                    <div class="info-grid">
                        <div class="field-item suggestion-wrapper highlight-input">
                            <label>Mã sản phẩm:</label>
                            <input id="product_code" class="input-field font-bold-blue" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($productCode) ?>" disabled>
                            <input type="hidden" id="production_order_code"
                                value="<?= htmlspecialchars($productionOrderCode) ?>">
                            <div id="product_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Mã nhân viên:</label>
                            <input id="extrusion_employee_code" class="input-field" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($extrusion_employeeCode) ?>" disabled>
                            <div id="employee_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item">
                            <label>Họ tên nhân viên:</label>
                            <input id="extrusion_employee_name" class="edit-input input-readonly" type="text"
                                value="<?= htmlspecialchars($extrusion_employeeName) ?>" readonly tabindex="-1">
                        </div>

                        <div class="field-item">
                            <label>Loại Bobin:</label>
                            <select id="bobin_type" name="bobin_type" disabled required>
                                <?php foreach ($typeOptions as $val => $lbl): ?>
                                <option value="<?= $val ?>"
                                    <?= ($item['bobin_type'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Lot vật liệu:</label>
                            <input id="material_lot" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($materialLot) ?>" disabled>
                            <div id="material_lot_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Số máy đùn:</label>
                            <input type="text" id="extrusion_machine" placeholder="Nhập số máy..." autocomplete="off"
                                disabled>
                            <div id="machine_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Vật liệu:</label>
                            <input type="text" id="material" placeholder="Nhập vật liệu..." autocomplete="off" disabled>
                            <div id="material_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item">
                            <label>Số lần nghiền:</label>
                            <input type="number" id="grinding_time" min="0" max="2" step="1" placeholder="0, 1 hoặc 2"
                                oninput="this.value = this.value.replace(/[^0-2]/g, '').slice(0, 1)" disabled>
                        </div>

                        <div class="field-item suggestion-wrapper">
                            <label>Lot in:</label>
                            <input id="print_lot" class="edit-input highlight-printlot" type="text" autocomplete="off"
                                value="<?= htmlspecialchars($item['print_lot'] ?? '') ?>" readonly tabindex="-1">
                            <div id="print_lot_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item highlight-box">
                            <label>Chiều dài (m):</label>
                            <input id="length_m" class="input-field font-bold-green" type="number"
                                value="<?= htmlspecialchars($item['length_m'] ?? 0) ?>" disabled>
                        </div>

                        <!-- VỊ TRÍ ĐẶT RACK -->
                        <div class="field-item suggestion-wrapper rack-box">
                            <label>Vị trí đặt (Rack):</label>
                            <input id="rack_code" class="input-field font-bold-cyan" type="text" autocomplete="off"
                                placeholder="Chọn mã Rack..." value="<?= htmlspecialchars($rackCode) ?>" disabled>
                            <div id="rack_suggestions" class="suggestion-box"></div>
                        </div>

                        <div class="field-item">
                            <label>Ca sản xuất:</label>
                            <select id="shift" name="shift" disabled required>
                                <option value="Ca 1" <?= ($item['shift'] ?? '') === 'Ca 1' ? 'selected' : '' ?>>Ca 1
                                </option>
                                <option value="Ca 2" <?= ($item['shift'] ?? '') === 'Ca 2' ? 'selected' : '' ?>>Ca 2
                                </option>
                                <option value="Ca 3" <?= ($item['shift'] ?? '') === 'Ca 3' ? 'selected' : '' ?>>Ca 3
                                </option>
                                <option value="Hành chính"
                                    <?= ($item['shift'] ?? '') === 'Hành chính' ? 'selected' : '' ?>>Hành chính</option>
                            </select>
                        </div>

                        <div class="field-item">
                            <label>Ngày đùn:</label>
                            <input id="extrusion_date" name="extrusion_date" class="input-field" type="date"
                                value="<?= htmlspecialchars($item['extrusion_date'] ?? '') ?>" disabled>
                        </div>

                        <div class="field-item">
                            <label>Thời điểm hoàn thành:</label>
                            <input id="finish_time" name="finish_time" class="input-field" type="datetime-local"
                                step="1" value="<?= htmlspecialchars($finishTimeVal) ?>"
                                onclick="try { this.showPicker(); } catch(e) {}"
                                onkeydown="return ['Tab', 'Escape'].includes(event.key)" disabled>
                        </div>
                    </div>

                    <!-- 5 TIÊU CHÍ ĐÙN CHECK -->
                    <div class="ext-check-strip">
                        <span class="ext-title">🏭 Đùn Check:</span>
                        <div class="ext-checks-group">
                            <?php
                                    $checks = [
                                        'diameter'       => ['label' => 'Đường kính', 'val' => $chkDiameter],
                                        'gel'            => ['label' => 'Gel',        'val' => $chkGel],
                                        'foreign_object' => ['label' => 'Dị vật',     'val' => $chkForeignObject],
                                        'color'          => ['label' => 'Màu sắc',    'val' => $chkColor],
                                        'print'          => ['label' => 'Chữ in',     'val' => $chkPrint]
                                    ];
                                    foreach ($checks as $key => $c):
                                        $isOk = (bool)$c['val'];
                                    ?>
                            <div class="ext-check-box">
                                <span class="check-box-label"><?= $c['label'] ?></span>
                                <button type="button" class="ext-toggle-btn <?= $isOk ? 'active' : '' ?>"
                                    data-field="ext_check_<?= $key ?>" data-value="<?= $isOk ? 'true' : 'false' ?>"
                                    onclick="toggleExtCheck(this)" disabled>
                                    <?= $isOk ? 'OK' : 'NG' ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- CÁC NÚT HÀNH ĐỘNG -->
                    <div class="card-footer-simple">
                        <?php if (!empty($item['updated_time'])): ?>
                        <div class="update-time">🕒 Cập nhật: <?= htmlspecialchars($item['updated_time']) ?></div>
                        <?php endif; ?>
                        <div class="button-group">
                            <!-- Nút Sửa ban đầu -->
                            <button type="button" class="btn-edit" onclick="startEdit(this)">
                                ✏️ Chỉnh sửa
                            </button>

                            <!-- Nút Hủy và Lưu (chỉ hiện khi bấm Chỉnh sửa) -->
                            <button type="button" class="btn-cancel-edit" style="display: none;"
                                onclick="cancelEdit(this)">
                                ✕ Hủy sửa
                            </button>
                            <button type="button" class="btn-confirm" style="display: none;"
                                onclick="handleConfirm(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                💾 Cập nhật
                            </button>

                            <!-- Nút Hủy Bobin -->
                            <button type="button" class="btn-delete"
                                onclick="handleDelete(this, '<?= htmlspecialchars($item['bobin_identification_code']) ?>')">
                                🗑️ Hủy Bobin
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
    const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_submit.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_suggestion.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Extrusion/edit_scanQR.js?v=<?= time() ?>"></script>
</body>

</html>