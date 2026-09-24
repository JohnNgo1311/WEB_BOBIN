<?php
$bobins = $data['bobins'] ?? [];

// 1. TỔNG HỢP SỐ LIỆU TỪ DATABASE
$defaultStatus = [
    'Rolled'               => 0,
    'Busy_Unchecked'       => 0,
    'Busy_Checked'         => 0,
    'Pending_Cancellation' => 0,
    'Cancelled'            => 0
];
$statusCounts = array_merge($defaultStatus, $data['statusCounts'] ?? []);

$statusOptions = [
    'all'                  => '📦 Tất cả trạng thái',
    'Busy_Unchecked'       => '✅ ĐÃ ĐÙN',
    'Rolled'               => '✅ ĐÃ CUỘN',
    'Busy_Checked'         => '🛡️ ĐÃ KIỂM TRA QC',
    'Cancelled'            => '🗑️ ĐÃ HỦY'
];

$currentStatus = $_GET['status'] ?? 'all';
$currentSize   = $_GET['bobin_size'] ?? 'all';
$currentType   = $_GET['bobin_type'] ?? 'all';
$currentRack   = $_GET['rack'] ?? 'all';
$racks         = $data['racks'] ?? [];

// ========================================================
// TÍNH TOÁN DUNG LƯỢNG VÀ BOBIN TRỐNG
// ========================================================
$capacityMap = (!empty($data['capacityMap'])) ? $data['capacityMap'] : [
    'PL4-7 (TU04.TU06)' => 420,
    'PL4-7 (TU08~)'     => 480,
    'PL7-3'             => 460
];

if ($currentSize === 'all') {
    $totalRealBobins = array_sum($capacityMap);
} else {
    $totalRealBobins = $capacityMap[$currentSize] ?? 0;
}

$extrudedCount = $statusCounts['Busy_Unchecked'];

$uniqueRolledKeys = [];
foreach ($bobins as $b) {
    if (($b['bobin_current_status'] ?? '') === 'Rolled') {
        $keyCode = $b['bobin_key_code'] ?? '';
        if (!empty($keyCode)) {
            $uniqueRolledKeys[$keyCode] = true;
        }
    }
}
$uniqueRolledCount = count($uniqueRolledKeys);
$emptyBobins = min($totalRealBobins, max(0, $totalRealBobins - ($extrudedCount - $uniqueRolledCount)));
$untestedQC = max(0, $statusCounts['Busy_Unchecked'] - $statusCounts['Busy_Checked']);
$totalRecords = count($bobins);

// 2. DỮ LIỆU BIỂU ĐỒ
$chartLabels = ['Trống', 'Đã đùn', 'Chưa kiểm tra QC', 'Đã kiểm tra QC', 'Đã hủy'];
$chartSeries = [
    $emptyBobins,
    $extrudedCount,
    $untestedQC,
    $statusCounts['Busy_Checked'],
    $statusCounts['Cancelled']
];

$sizeOptions = [
    'all'               => 'Mọi kích thước',
    'PL7-3'             => 'PL7-3',
    'PL4-7 (TU08~)'     => 'PL4-7 (TU08~)',
    'PL4-7 (TU04.TU06)' => 'PL4-7 (TU04.TU06)'
];

$typeOptions = [
    'all'                              => 'Mọi loại Bobin',
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

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        $query = $_GET ?? [];
        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listBobinHistoryView';
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
        $text  = $goodDefect ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}

if (!function_exists('decodeJsonObject')) {
    function decodeJsonObject($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch sử Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinHistory.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/apexcharts.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/chart-helper.js"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>
    <h1>Lịch sử Bobin</h1>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR GỌN GÀNG -->
        <div class="control-bar-modern">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="btn-back-modern" title="Quay lại">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listBobinHistoryView">

                    <div class="control-row">
                        <!-- TÌM KIẾM KEYWORD -->
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="searchKeyword" name="keyword"
                                placeholder="Nhập hoặc quét mã Bobin..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <!-- CHỌN KHOẢNG NGÀY -->
                        <?php
                        $defaultToDate = date('Y-m-d');
                        $defaultFromDate = date('Y-m-d', strtotime('-7 days'));
                        $fromDateVal = !empty($_GET['from_date']) ? $_GET['from_date'] : $defaultFromDate;
                        $toDateVal = !empty($_GET['to_date']) ? $_GET['to_date'] : $defaultToDate;
                        ?>
                        <div class="date-picker-compact">
                            <input type="date" name="from_date" id="fromDate"
                                value="<?= htmlspecialchars($fromDateVal) ?>">
                            <span class="date-sep">➝</span>
                            <input type="date" name="to_date" id="toDate" value="<?= htmlspecialchars($toDateVal) ?>">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <path d="M3 14h7v7H3z"></path>
                            </svg>
                            Quét QR
                        </button>

                        <button type="submit" class="btn-modern btn-filter" id="btnFilter">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Lọc
                        </button>

                        <?php
                        $exportParams = $_GET;
                        $exportParams['url'] = 'bobin/exportHistoryExcel';
                        $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
                        ?>
                        <!-- MENU XUẤT EXCEL THÔNG MINH CHO LỊCH SỬ -->
                        <div class="export-dropdown-wrapper">
                            <button type="button" class="btn-modern btn-export" id="btnToggleExportMenu">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>Xuất Excel ▾</span>
                            </button>
                            <div class="export-dropdown-menu" id="exportMenuBox">
                                <a href="<?= $exportUrl ?>" class="export-menu-item">
                                    <span class="menu-item-icon">📄</span>
                                    <div class="menu-item-text">
                                        <strong>Xuất toàn bộ kết quả lọc</strong>
                                        <small>Tất cả <?= number_format($totalRecords) ?> lượt cập nhật</small>
                                    </div>
                                </a>
                                <button type="button" class="export-menu-item" id="btnExportSelectedItems">
                                    <span class="menu-item-icon">☑️</span>
                                    <div class="menu-item-text">
                                        <strong>Xuất các bản ghi đã chọn</strong>
                                        <small id="selectedCountText">Đã chọn: 0 bản ghi</small>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- DASHBOARD THỐNG KÊ (BIỂU ĐỒ 220PX + LƯỚI 2 CỘT KPI) -->
        <div class="analytics-wrapper">
            <div class="analytics-left">
                <div class="analytics-title-box">
                    <div class="badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="18" y="4" width="4" height="16" fill="#38bdf8" stroke="none"></rect>
                            <rect x="10" y="9" width="4" height="11" fill="#818cf8" stroke="none"></rect>
                            <rect x="2" y="14" width="4" height="6" fill="#34d399" stroke="none"></rect>
                        </svg>
                    </div>
                    <h3>Thống kê lịch sử cập nhật trạng thái Bobin</h3>
                </div>

                <div class="chart-box">
                    <div id="statusPieChart"></div>
                </div>
            </div>

            <div class="analytics-right">
                <div class="total-card">
                    <div class="total-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 12 12 17 22 12"></polyline>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </svg>
                    </div>
                    <div class="total-info">
                        <span class="label">Tổng số lượng Bobin thực tế</span>
                        <span class="value"><?= number_format($totalRealBobins) ?></span>
                    </div>
                </div>

                <!-- LƯỚI 2 CỘT KPI THU GỌN -->
                <div class="kpi-grid">
                    <div class="kpi-card empty-bobin" style="cursor: default;">
                        <div class="kpi-left">
                            <span class="kpi-icon">📦</span>
                            <span class="kpi-name">BOBIN TRỐNG</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($emptyBobins) ?></strong>
                        </div>
                    </div>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked']) ?>" class="kpi-card unchecked">
                        <div class="kpi-left">
                            <span class="kpi-icon">✅</span>
                            <span class="kpi-name">ĐÃ ĐÙN</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($extrudedCount) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>



                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked']) ?>" class="kpi-card unchecked">
                        <div class="kpi-left">
                            <span class="kpi-icon">⏳</span>
                            <span class="kpi-name">CHƯA KT QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($untestedQC) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked']) ?>" class="kpi-card checked">
                        <div class="kpi-left">
                            <span class="kpi-icon">🛡️</span>
                            <span class="kpi-name">ĐÃ KT QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Checked']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>
                    <a href="<?= buildFilterUrl(['status' => 'Rolled']) ?>" class="kpi-card rolled">
                        <div class="kpi-left">
                            <span class="kpi-icon">✅</span>
                            <span class="kpi-name">ĐÃ CUỘN</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Rolled']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>
                    <a href="<?= buildFilterUrl(['status' => 'Cancelled']) ?>" class="kpi-card cancelled">
                        <div class="kpi-left">
                            <span class="kpi-icon">🗑️</span>
                            <span class="kpi-name">ĐÃ HỦY</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Cancelled']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <script>
        (function() {
            const rawData = <?= json_encode($chartSeries, JSON_NUMERIC_CHECK) ?>;
            const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const totalRealBobins = <?= (int)$totalRealBobins ?>;
            const maxVal = Math.max(...rawData, 0);

            const statusChart = new BaseChart('#statusPieChart', {
                chart: {
                    type: 'bar',
                    height: 220,
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        speed: 300
                    }
                },
                series: [{
                    name: 'Số lượng Bobin',
                    data: rawData
                }],
                colors: ['#94a3b8', '#34d399', '#fb923c', '#38bdf8', '#fb7185'],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '62%',
                        distributed: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    offsetX: 8,
                    formatter: function(val) {
                        const percent = totalRealBobins > 0 ? ((val / totalRealBobins) * 100).toFixed(
                            1) : 0;
                        return `${val.toLocaleString('vi-VN')} (${percent}%)`;
                    },
                    style: {
                        fontSize: '12px',
                        fontWeight: 800,
                        colors: ["#475569"]
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3,
                    padding: {
                        top: -12,
                        right: 35,
                        bottom: -10,
                        left: 15
                    }
                },
                xaxis: {
                    categories: labels,
                    min: 0,
                    max: maxVal === 0 ? 10 : Math.ceil(maxVal * 1.25),
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '11px',
                            fontWeight: 600
                        },
                        formatter: (val) => Math.floor(val).toLocaleString('vi-VN')
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#1e293b',
                            fontSize: '12.5px',
                            fontWeight: 700
                        }
                    }
                },
                legend: {
                    show: false
                }
            });
            statusChart.render();
        })();
        </script>

        <!-- BỘ LỌC 4 DROPDOWN TRÊN 1 HÀNG NGANG (TIẾT KIỆM KHÔNG GIAN) -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <h2>Kết quả tìm kiếm: <span><?= number_format($totalRecords) ?></span> Lượt cập nhật</h2>
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAllCheckbox"> Chọn tất cả trang này
                    </label>
                </div>
                <a href="<?= buildFilterUrl(['status' => 'all', 'bobin_size' => 'all', 'bobin_type' => 'all', 'rack' => 'all', 'keyword' => null]) ?>"
                    class="btn-reset-filter">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

            <div class="filter-grid-row">
                <div class="filter-col">
                    <div class="filter-label">📌 Trạng thái:</div>
                    <select id="statusFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?= buildFilterUrl(['status' => $key]) ?>"
                            <?= $currentStatus === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-col">
                    <div class="filter-label">📏 Kích thước:</div>
                    <select id="sizeFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($sizeOptions as $key => $label): ?>
                        <option value="<?= buildFilterUrl(['bobin_size' => $key]) ?>"
                            <?= $currentSize === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-col">
                    <div class="filter-label">🏷️ Loại Bobin:</div>
                    <select id="typeFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <?php foreach ($typeOptions as $key => $label): ?>
                        <option value="<?= buildFilterUrl(['bobin_type' => $key]) ?>"
                            <?= $currentType === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-col">
                    <div class="filter-label">🏢 Vị trí Rack:</div>
                    <select id="rackFilter" class="filter-select" onchange="window.location.href = this.value;">
                        <option value="<?= buildFilterUrl(['rack' => 'all']) ?>"
                            <?= $currentRack === 'all' ? 'selected' : '' ?>>
                            Tất cả các Rack
                        </option>
                        <?php foreach ($racks as $r): ?>
                        <?php $rCode = $r['rack_code'] ?? $r['code'] ?? ''; ?>
                        <?php if (!empty($rCode)): ?>
                        <option value="<?= buildFilterUrl(['rack' => $rCode]) ?>"
                            <?= $currentRack === $rCode ? 'selected' : '' ?>>
                            📍 Rack: <?= htmlspecialchars($rCode) ?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH BOBIN THỰC TẾ -->
        <div class="list-card">
            <?php if (empty($bobins)): ?>
            <div class="empty-state">Không tìm thấy dữ liệu nào trong khoảng thời gian này.</div>
            <?php else: ?>
            <div class="bobin-list">
                <?php foreach ($bobins as $item): ?>
                <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled'               => 'status_rolled',
                            'busy_unchecked'       => 'status_busy_unchecked',
                            'busy_checked'         => 'status_busy_checked',
                            'pending_cancellation' => 'status_pending_cancellation',
                            'cancelled'            => 'status_cancelled',
                            default                => 'status_unknown'
                        };

                        $vi = decodeJsonObject($item['visual_inspection'] ?? '');
                        $defects = is_array($vi['defects'] ?? null) ? $vi['defects'] : [];
                        $products = decodeJsonObject($item['products'] ?? '');
                        $productCode = $products['product_code'] ?? 'Chưa cập nhật';
                        $extrusionEmployee = decodeJsonObject($item['extrusion_employee'] ?? '');
                        $extrusion_employeeCode = $extrusionEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $extrusion_employeeName = $extrusionEmployee['employee_name'] ?? 'Chưa cập nhật';
                        $materialLotData = decodeJsonObject($item['material_lot'] ?? '');
                        $materialLot = $materialLotData['lot'] ?? 'Chưa cập nhật';
                        $windingEmployee = decodeJsonObject($item['winding_employee'] ?? '');
                        $winding_employeeCode = $windingEmployee['employee_code'] ?? 'Chưa cập nhật';
                        $winding_employeeName = $windingEmployee['employee_name'] ?? 'Chưa cập nhật';
                        $extCheck = decodeJsonObject($item['extrusion_check'] ?? '');
                        $rackData = decodeJsonObject($item['rack'] ?? '');
                        $rackCode = $rackData['code'] ?? 'Chưa cập nhật';
                        $isRackEmpty = ($rackCode === 'Chưa cập nhật');

                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'] ?? '';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';

                        $formatted_time = !empty($finish_time) ? date('dmY$His', strtotime($finish_time)) : '';
                        $fields = [$productCode, $materialLot, $printLot, $formatted_time];
                        $isMissingData = false;
                        foreach ($fields as $field) {
                            $checkValue = trim((string)$field);
                            if ($checkValue === '' || $checkValue === 'Chưa cập nhật') {
                                $isMissingData = true;
                                break;
                            }
                        }

                        $mainInfoText = $isMissingData
                            ? 'Chưa cập nhật đầy đủ thông tin cần thiết'
                            : $productCode . '$' . $materialLot . '$' . $printLot . '$' . $formatted_time;
                        ?>
                <div class="bobin-item <?= $statusClass ?>">
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
                            <input type="checkbox" class="bobin-select-checkbox"
                                value="<?= htmlspecialchars($item['id'] ?? $item['bobin_key_code']) ?>"
                                title="Chọn bản ghi này để xuất Excel">

                            <span class="key-code"><?= $displayKey ?></span>
                            <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                        </div>

                        <!-- ĐƯA MAIN-INFO VÀ STATUS-BADGE VÀO LẠI BÊN TRONG CARD-TOP -->
                        <div class="main-info-wrapper">
                            <div class="main-info">
                                <?= htmlspecialchars($mainInfoText) ?>
                            </div>
                            <button type="button" class="btn-copy" data-copy="<?= htmlspecialchars($mainInfoText) ?>"
                                onclick="copyToClipboard(this)" title="Copy nội dung">
                                📋 Copy
                            </button>
                        </div>

                        <div class="status-badge">
                            <?php
                                    $displayStatus = match ($rawStatus) {
                                        'Rolled'               => 'Đã cuộn',
                                        'Busy_Unchecked'       => 'Đang đợi QC kiểm tra',
                                        'Busy_Checked'         => 'Đã kiểm tra QC',
                                        'Pending_Cancellation' => 'Chờ hủy',
                                        'Cancelled'            => 'Đã hủy',
                                        default                => $rawStatus,
                                    };
                                    echo htmlspecialchars($displayStatus);
                                    ?>
                        </div>
                    </div> <!-- ĐÓNG THẺ CARD-TOP TẠI ĐÂY -->

                    <!-- 12 THÔNG SỐ TRÌNH BÀY DẠNG GRID TỐI ƯU -->
                    <div class="info-grid">
                        <div class="field-item">
                            <label>Mã sản phẩm</label>
                            <div class="val-sub"><?= htmlspecialchars($productCode) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Mã nhân viên</label>
                            <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Họ tên nhân viên</label>
                            <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Vị trí RACK</label>
                            <div class="val-sub">
                                <span class="val-rack <?= $isRackEmpty ? 'val-empty' : '' ?>">
                                    <?= htmlspecialchars($rackCode) ?>
                                </span>
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
                            <div class="val-sub"><?= htmlspecialchars($materialLot) ?></div>
                        </div>
                        <div class="field-item">
                            <label>Lot in</label>
                            <div class="val-sub"><?= htmlspecialchars($item['print_lot'] ?? 'Chưa cập nhật') ?></div>
                        </div>
                        <div class="field-item highlight-box">
                            <label>Chiều dài (m)</label>
                            <div class="val-highlight">
                                <?= number_format($item['length_m'] ?? 0, 0, ".", ",") ?> m
                            </div>
                        </div>
                        <div class="field-item">
                            <label>Ngày đùn</label>
                            <div class="val-sub"><?= htmlspecialchars($item['extrusion_date'] ?? '') ?></div>
                        </div>
                        <div class="field-item">
                            <label>Thời điểm hoàn thành cuộn</label>
                            <div class="val-sub"><?= htmlspecialchars($item['finish_time'] ?? '') ?></div>
                        </div>
                    </div>

                    <!-- GỘP 3 KHỐI KIỂM SOÁT THÀNH 3 CỘT SONG SONG -->
                    <div class="inspection-pipeline-grid">
                        <!-- CỘT 1: ĐÙN CHECK -->
                        <div class="pipeline-col extrusion-col">
                            <div class="qc-title">🏭 Đùn Check</div>
                            <div class="qc-badges">
                                <?php
                                        $extItems = [
                                            'Đường kính' => $extCheck['diameter'] ?? true,
                                            'Gel'        => $extCheck['gel'] ?? true,
                                            'Dị vật'     => $extCheck['foreign_object'] ?? true,
                                            'Màu'        => $extCheck['color'] ?? true,
                                            'Chữ in'     => $extCheck['print'] ?? true,
                                        ];
                                        foreach ($extItems as $label => $isOk) {
                                            echo viBadge($label, $isOk);
                                        }
                                        ?>
                            </div>
                        </div>

                        <!-- CỘT 2: QC CHECK -->
                        <div class="pipeline-col qc-col">
                            <div class="qc-title">🛡️ QC Check</div>
                            <div class="qc-header">
                                <div class="qc-info-row">
                                    <div class="qc-meta-item">
                                        <label class="val-text">Mã NV:</label>
                                        <span
                                            class="val-sub"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?></span>
                                    </div>
                                    <div class="qc-meta-item">
                                        <label class="val-text">Họ tên:</label>
                                        <span
                                            class="val-sub"><?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa cập nhật') ?></span>
                                    </div>
                                    <div class="qc-meta-item">
                                        <label class="val-text">Thời gian:</label>
                                        <span
                                            class="val-sub"><?= htmlspecialchars($vi['inspection_time'] ?? 'Chưa cập nhật') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="qc-badges">
                                <?= viBadge('Gel', $defects['gel'] ?? false) ?>
                                <?= viBadge('Dị vật', $defects['foreign_object'] ?? false) ?>
                                <?= viBadge('Màu', $defects['color_issue'] ?? false) ?>
                                <?= viBadge('In', $defects['print_quality'] ?? false) ?>
                            </div>
                            <div class="qc-note">📝
                                <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Chưa cập nhật' : $defects['note']) ?>
                            </div>
                        </div>

                        <!-- CỘT 3: THÔNG TIN CUỘN -->
                        <div class="pipeline-col winding-col">
                            <div class="winding-title">📍 Thông tin cuộn</div>
                            <div class="winding-header">
                                <div class="winding-info-row">
                                    <div class="winding-input">
                                        <label class="val-text">Máy cuộn:</label>
                                        <span
                                            class="val-sub"><?= htmlspecialchars($item['winding_machine'] ?? 'Chưa cập nhật') ?></span>
                                    </div>
                                    <div class="winding-input">
                                        <label class="val-text">Mã NV:</label>
                                        <span class="val-sub"><?= htmlspecialchars($winding_employeeCode) ?></span>
                                    </div>
                                    <div class="winding-input">
                                        <label class="val-text">Họ tên:</label>
                                        <span class="val-sub"><?= htmlspecialchars($winding_employeeName) ?></span>
                                    </div>
                                    <div class="winding-input">
                                        <label class="val-text">Thông khí:</label>
                                        <span class="val-sub"><?= htmlspecialchars($flow_test_result) ?></span>
                                    </div>
                                </div>
                                <div class="winding-note">📝
                                    <?= htmlspecialchars(trim($item['winding_note'] ?? '') === '' ? 'Chưa cập nhật' : $item['winding_note']) ?>
                                </div>
                            </div>
                        </div>
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

    <?php if (!empty($data['error'])): ?>
    <div id="toastMessage" class="toast-message toast-error show">
        ⚠️ <?= htmlspecialchars($data['error']) ?>
    </div>
    <script>
    setTimeout(() => {
        const toast = document.getElementById('toastMessage');
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }
    }, 3000);
    </script>
    <?php endif; ?>

    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=1"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=1"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const interactiveElements = document.querySelectorAll(
            ".filter-dashboard select, .filter-dashboard a, .kpi-list a");
        interactiveElements.forEach(el => {
            const eventType = el.tagName === "SELECT" ? "change" : "click";
            el.addEventListener(eventType, function() {
                sessionStorage.setItem("bobin_history_scroll_pos", window.scrollY);
            });
        });

        const scrollPos = sessionStorage.getItem("bobin_history_scroll_pos");
        if (scrollPos !== null) {
            window.scrollTo({
                top: parseInt(scrollPos, 10),
                behavior: "instant"
            });
            sessionStorage.removeItem("bobin_history_scroll_pos");
        }
    });
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnToggle = document.getElementById('btnToggleExportMenu');
        const menuBox = document.getElementById('exportMenuBox');
        const selectAllCb = document.getElementById('selectAllCheckbox');
        const itemCheckboxes = document.querySelectorAll('.bobin-select-checkbox');
        const countText = document.getElementById('selectedCountText');
        const btnExportSelected = document.getElementById('btnExportSelectedItems');

        // Bật/tắt menu dropdown xuất Excel
        if (btnToggle && menuBox) {
            btnToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                menuBox.classList.toggle('show');
            });
            document.addEventListener('click', function() {
                menuBox.classList.remove('show');
            });
        }

        // Cập nhật số lượng bản ghi đã tích chọn
        function updateSelectedCount() {
            const selected = Array.from(itemCheckboxes).filter(cb => cb.checked);
            if (countText) {
                countText.textContent = `Đã chọn: ${selected.length} bản ghi`;
            }
            if (selectAllCb) {
                selectAllCb.checked = (selected.length === itemCheckboxes.length && itemCheckboxes.length > 0);
            }
        }

        // Chọn tất cả
        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => cb.checked = selectAllCb.checked);
                updateSelectedCount();
            });
        }

        itemCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSelectedCount);
        });

        // Xử lý xuất các mục đã chọn
        if (btnExportSelected) {
            btnExportSelected.addEventListener('click', function() {
                const selectedIds = Array.from(itemCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedIds.length === 0) {
                    alert("⚠️ Vui lòng tích chọn ít nhất 1 bản ghi lịch sử trước khi xuất!");
                    return;
                }

                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('url', 'bobin/exportHistoryExcel');
                currentUrl.searchParams.set('selected_ids', selectedIds.join(','));
                window.location.href = currentUrl.toString();
            });
        }
    });
    </script>
</body>

</html>