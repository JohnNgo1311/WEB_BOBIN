<?php
$bobins = $data['bobins'] ?? [];
$pagination = $data['pagination'] ?? [
    'currentPage'  => 1,
    'totalPages'   => 1,
    'totalRecords' => count($bobins),
    'limit'        => 50
];

// 1. Khởi tạo mảng mặc định an toàn tuyệt đối tránh lỗi Undefined Array Key
$defaultStatus = [
    'Rolled'               => 0,
    'Busy_Unchecked'       => 0,
    'Busy_Checked'         => 0,
    'Line'                 => 0,
    'Pending_Cancellation' => 0,
];
$statusCounts = array_merge($defaultStatus, $data['statusCounts'] ?? []);
$currentPage  = (int)($pagination['currentPage'] ?? 1);
$totalPages   = (int)($pagination['totalPages'] ?? 1);
$totalRecords = (int)($pagination['totalRecords'] ?? count($bobins));
$totalBobins  = ($statusCounts['Rolled'] ?? 0)
    + ($statusCounts['Busy_Unchecked'] ?? 0)
    + ($statusCounts['Busy_Checked'] ?? 0)
    + ($statusCounts['Pending_Cancellation'] ?? 0);

// 2. Chuẩn bị dữ liệu cho Chart
$chartLabels = ['Đã cuộn', 'Chưa QC', 'Đã QC', 'Tồn line', 'Chờ hủy'];
$chartSeries = [
    $statusCounts['Rolled'],
    $statusCounts['Busy_Unchecked'],
    $statusCounts['Busy_Checked'],
    $statusCounts['Line'],
    $statusCounts['Pending_Cancellation']
];

if (!function_exists('buildFilterUrl')) {
    function buildFilterUrl(array $overrideParams = []): string
    {
        $query = $_GET ?? [];

        if (!isset($query['url'])) {
            $query['url'] = 'bobin/listBobinDetailView';
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

$currentStatus = $_GET['status'] ?? 'all';
$currentSize   = $_GET['bobin_size'] ?? 'all';
$currentType   = $_GET['bobin_type'] ?? 'all';

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
    'Điều chỉnh (Ngoại quan: Dị vật)' => 'Điều chỉnh (Ngoại quan: Dị vật)'
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Danh sách Bobin</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinDetail.css?v=1">
    <link rel="icon" href="data:,">
    <!-- Nạp thư viện biểu đồ và QR Code -->
    <script src="/WEB_BOBIN/public/assets/js/apexcharts.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/chart-helper.js"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>

    <!-- Style dành riêng cho UI nâng cấp -->
    <style>
    /* Card tổng quan KPI */
    .analytics-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 24px 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08);
        border: 1px solid #e2e8f0;
    }

    .analytics-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 18px;
        margin-bottom: 20px;
    }

    .analytics-header h3 {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .total-counter {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        padding: 10px 22px;
        border-radius: 12px;
        text-align: right;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
    }

    .total-counter .label {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        color: #94a3b8;
        font-weight: 700;
        letter-spacing: 0.8px;
    }

    .total-counter .value {
        font-size: 26px;
        font-weight: 900;
        color: #38bdf8;
        line-height: 1.1;
    }

    .analytics-body {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 32px;
        align-items: center;
    }

    .kpi-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .kpi-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-radius: 12px;
        border-width: 2px;
        border-style: solid;
        font-size: 15px;
        font-weight: 700;
        transition: transform 0.15s ease;
    }

    .kpi-card:hover {
        transform: translateX(4px);
    }

    .kpi-card .kpi-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        margin-right: 12px;
        display: inline-block;
    }

    .kpi-card .kpi-val {
        font-size: 22px;
        font-weight: 900;
    }

    .kpi-card.rolled {
        background: #f0fdf4;
        border-color: #22c55e;
        box-shadow: 0 4px 14px rgba(34, 197, 94, 0.12);
    }

    .kpi-card.rolled .kpi-dot {
        background: #16a34a;
    }

    .kpi-card.rolled .kpi-val {
        color: #15803d;
    }

    .kpi-card.unchecked {
        background: #fffbeb;
        border-color: #f59e0b;
        box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);
    }

    .kpi-card.unchecked .kpi-dot {
        background: #d97706;
    }

    .kpi-card.unchecked .kpi-val {
        color: #b45309;
    }

    .kpi-card.checked {
        background: #f0f9ff;
        border-color: #0ea5e9;
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.12);
    }

    .kpi-card.checked .kpi-dot {
        background: #0284c7;
    }

    .kpi-card.checked .kpi-val {
        color: #0369a1;
    }

    .kpi-card.line {
        background: #faf5ff;
        border-color: #a855f7;
        box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12);
    }

    .kpi-card.line .kpi-dot {
        background: #9333ea;
    }

    .kpi-card.line .kpi-val {
        color: #7e22ce;
    }

    .kpi-card.pending {
        background: #fef2f2;
        border-color: #ef4444;
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);
    }

    .kpi-card.pending .kpi-dot {
        background: #dc2626;
    }

    .kpi-card.pending .kpi-val {
        color: #b91c1c;
    }

    /* Bảng điều khiển bộ lọc (Filter Dashboard) */
    .filter-dashboard {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
    }

    .filter-dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1.5px dashed #f1f5f9;
    }

    .filter-dashboard-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .filter-dashboard-header h2 span {
        color: #0284c7;
        font-size: 22px;
        font-weight: 800;
    }

    .filter-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .filter-row:last-child {
        margin-bottom: 0;
    }

    .filter-label {
        width: 140px;
        font-size: 14px;
        font-weight: 700;
        color: #475569;
        flex-shrink: 0;
        padding-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1;
    }

    .filter-pill,
    .status-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        font-size: 13.5px;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .filter-pill:hover,
    .status-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .filter-pill.active,
    .status-btn.active {
        background: #0284c7;
        color: #ffffff;
        border-color: #0369a1;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
    }
    </style>
</head>

<body>

    <h1>Danh sách Bobin hiện tại</h1>

    <div class="container">
        <!-- CONTROL BAR -->
        <div id="qr-reader"></div>

        <div class="control-bar">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="back-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="bobin/listBobinDetailView">
                <div class="qr-search-group">
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24">
                            <path
                                d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19z" />
                        </svg>
                        <input type="text" name="keyword" id="searchKeyword" placeholder="Nhập hoặc quét mã Bobin..."
                            value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>
                    <button type="button" id="btnScanQR" class="btn-scan-qr btn-scan-default">Quét QR</button>
                    <button type="submit" class="btn-filter" id="btnFilter">Lọc</button>
                </div>
            </form>

            <!-- Xuất Excel -->
            <?php
            $exportParams = $_GET;
            unset($exportParams['page']); // Không phân trang khi xuất excel
            $exportParams['url'] = 'bobin/exportDetailExcel';
            $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
            ?>
            <a href="<?= $exportUrl ?>" class="btn-excel">Xuất file Excel</a>
        </div>

        <!-- KHUNG BIỂU ĐỒ VÀ THẺ THỐNG KÊ TỔNG QUAN -->
        <div class="analytics-card">
            <div class="analytics-header">
                <div class="title-wrap">
                    <span class="badge-icon">📊</span>
                    <div>
                        <h3>Thống kê trạng thái Bobin</h3>

                    </div>
                </div>
                <div class="total-counter">
                    <span class="label">Tổng số lượng Bobin</span>
                    <span class="value"><?= number_format($totalBobins) ?></span>
                </div>
            </div>

            <div class="analytics-body">
                <!-- Vùng biểu đồ ApexCharts ngang -->
                <div class="chart-area">
                    <div id="statusPieChart"></div>
                </div>

                <!-- Bảng thẻ tóm tắt KPI -->
                <div class="kpi-grid">
                    <div class="kpi-card rolled">
                        <span class="kpi-dot"></span>
                        <span class="kpi-name">Đã cuộn</span>
                        <strong class="kpi-val"><?= number_format($statusCounts['Rolled']) ?></strong>
                    </div>
                    <div class="kpi-card unchecked">
                        <span class="kpi-dot"></span>
                        <span class="kpi-name">Chưa QC</span>
                        <strong class="kpi-val"><?= number_format($statusCounts['Busy_Unchecked']) ?></strong>
                    </div>
                    <div class="kpi-card checked">
                        <span class="kpi-dot"></span>
                        <span class="kpi-name">Đã QC</span>
                        <strong class="kpi-val"><?= number_format($statusCounts['Busy_Checked']) ?></strong>
                    </div>
                    <div class="kpi-card line">
                        <span class="kpi-dot"></span>
                        <span class="kpi-name">Tồn line</span>
                        <strong class="kpi-val"><?= number_format($statusCounts['Line']) ?></strong>
                    </div>
                    <div class="kpi-card pending">
                        <span class="kpi-dot"></span>
                        <span class="kpi-name">Chờ hủy</span>
                        <strong class="kpi-val"><?= number_format($statusCounts['Pending_Cancellation']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- VẼ CHART TỨC THÌ VỚI ANIMATION MƯỢT -->
        <script>
        (function() {
            const rawData = <?= json_encode($chartSeries, JSON_NUMERIC_CHECK) ?>;
            const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            const totalBobin = rawData.reduce((a, b) => a + b, 0) || 1;

            const statusChart = new BaseChart('#statusPieChart', {
                chart: {
                    type: 'bar',
                    height: 310,
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 380,
                        animateGradually: {
                            enabled: true,
                            delay: 60
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 300
                        }
                    }
                },
                series: [{
                    name: 'Số lượng Bobin',
                    data: rawData
                }],
                colors: ['#10b981', '#f59e0b', '#0ea5e9', '#a855f7', '#ef4444'],
                plotOptions: {
                    bar: {
                        vertical: true,
                        horizontal: false,
                        barHeight: '60%', // Tăng độ dày của thanh ngang lên 75% để biểu đồ to ra
                        barwidth: '60%',
                        distributed: true,
                        dataLabels: {
                            position: 'top' // Đặt nhãn dữ liệu ở trên cùng của thanh

                        }
                    }
                },
                dataLabels: {
                    enabled: true,

                    formatter: function(val) {
                        const percent = ((val / totalBobin) * 100).toFixed(1);
                        // Đã loại bỏ chữ "cuộn"
                        return `${val.toLocaleString('vi-VN')} (${percent}%)`;
                    },
                    style: {
                        fontSize: '14px',
                        fontWeight: 600,
                        colors: ["#0f172a"]
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: false
                        }
                    },
                    // Giảm khoảng đệm lề phải vì nhãn chữ đã ngắn lại
                    padding: {
                        top: 0,
                        right: 80,
                        bottom: 0,
                        left: 10
                    }
                },
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '15px',
                            fontWeight: 800
                        },
                        formatter: (val) => Math.floor(val).toLocaleString('vi-VN')
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#1e293b',
                            fontSize: '15px',
                            fontWeight: 800
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    style: {
                        fontSize: '14px'
                    },
                    // Đã loại bỏ chữ "cuộn" khi hover
                    y: {
                        formatter: (val) => val.toLocaleString('vi-VN')
                    }
                },
                legend: {
                    show: false
                }
            });

            statusChart.render();
        })();
        </script>

        <!-- BẢNG ĐIỀU KHIỂN BỘ LỌC (FILTER DASHBOARD) -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <h2>Kết quả tìm kiếm: <span><?= number_format($totalRecords) ?></span> Bobin</h2>
                <a href="<?= buildFilterUrl(['status' => 'all', 'bobin_size' => 'all', 'bobin_type' => 'all', 'page' => 1, 'keyword' => null]) ?>"
                    style="padding: 8px 16px; font-size:13px; font-weight:600; color:#475569; text-decoration:none; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; transition:0.2s;">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

            <!-- 1. Hàng lọc Trạng thái -->
            <div class="filter-row">
                <div class="filter-label">📌 Trạng thái:</div>
                <div class="filter-actions">
                    <a href="<?= buildFilterUrl(['status' => 'all', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'all' ? 'active' : '' ?>">📦 Tất cả trạng thái</a>
                    <a href="<?= buildFilterUrl(['status' => 'Rolled', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Rolled' ? 'active' : '' ?>">✅ Đã cuộn</a>
                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Busy_Unchecked' ? 'active' : '' ?>">⏳ Chưa QC</a>
                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Busy_Checked' ? 'active' : '' ?>">🛡️ Đã QC</a>
                    <a href="<?= buildFilterUrl(['status' => 'Line', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Line' ? 'active' : '' ?>">📍 Tồn line</a>
                    <a href="<?= buildFilterUrl(['status' => 'Pending_Cancellation', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Pending_Cancellation' ? 'active' : '' ?>">⌛ Chờ
                        hủy</a>
                </div>
            </div>

            <!-- 2. Hàng lọc Kích thước -->
            <div class="filter-row">
                <div class="filter-label">📏 Kích thước:</div>
                <div class="filter-actions">
                    <?php foreach ($sizeOptions as $key => $label): ?>
                    <a href="<?= buildFilterUrl(['bobin_size' => $key, 'page' => 1]) ?>"
                        class="filter-pill <?= $currentSize === $key ? 'active' : '' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Hàng lọc Loại Bobin -->
            <div class="filter-row">
                <div class="filter-label">🏷️ Loại Bobin:</div>
                <div class="filter-actions">
                    <?php foreach ($typeOptions as $key => $label): ?>
                    <a href="<?= buildFilterUrl(['bobin_type' => $key, 'page' => 1]) ?>"
                        class="filter-pill <?= $currentType === $key ? 'active' : '' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- DANH SÁCH BOBIN THỰC TẾ -->
        <div class="list-card">
            <?php if (empty($bobins)): ?>
            <div class="empty-state" style="text-align:center; padding: 40px; color:#64748b;">Không tìm thấy Bobin nào
                khớp với bộ lọc.</div>
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

                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $winding_employeeRaw = $item['winding_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'] ?? '';
                        $productCode = 'Chưa cập nhật';
                        $extrusion_employeeName = 'Chưa cập nhật';
                        $extrusion_employeeCode = 'Chưa cập nhật';
                        $winding_employeeName = 'Chưa cập nhật';
                        $winding_employeeCode = 'Chưa cập nhật';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';
                        $materialLot = 'Chưa cập nhật';

                        $vi = decodeJsonObject($viRaw);
                        $defects = is_array($vi['defects'] ?? null) ? $vi['defects'] : [];
                        $products = decodeJsonObject($productsRaw);
                        $productCode = $products['product_code'] ?? $productCode;
                        $extrusionEmployee = decodeJsonObject($extrusion_employeeRaw);
                        $extrusion_employeeCode = $extrusionEmployee['employee_code'] ?? $extrusion_employeeCode;
                        $extrusion_employeeName = $extrusionEmployee['employee_name'] ?? $extrusion_employeeName;
                        $materialLotData = decodeJsonObject($materialLotRaw);
                        $materialLot = $materialLotData['lot'] ?? $materialLot;
                        $windingEmployee = decodeJsonObject($winding_employeeRaw);
                        $winding_employeeCode = $windingEmployee['employee_code'] ?? $winding_employeeCode;
                        $winding_employeeName = $windingEmployee['employee_name'] ?? $winding_employeeName;

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
                            <span class="key-code"><?= $displayKey ?></span>
                            <span class="id-code">#<?= htmlspecialchars($item['bobin_identification_code']) ?></span>
                        </div>
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
                                        'Pending_Cancellation' => 'Đang chờ hủy',
                                        'Cancelled'            => 'Đã hủy',
                                        default                => $rawStatus,
                                    };
                                    echo htmlspecialchars($displayStatus);
                                    ?>
                        </div>
                    </div>

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

                    <div class="divider"></div>

                    <!-- QC -->
                    <div class="qc-section">
                        <div class="qc-title">🛡️ QC Check</div>
                        <div class="qc-header">
                            <div class="qc-info-row">
                                <div class="qc-meta-item">
                                    <label class="val-text">Mã số nhân viên:</label>
                                    <span
                                        class="val-sub"><?= htmlspecialchars($vi['inspector_code'] ?? 'Chưa cập nhật') ?></span>
                                </div>
                                <div class="qc-meta-item">
                                    <label class="val-text">Họ tên nhân viên:</label>
                                    <span
                                        class="val-sub"><?= htmlspecialchars($vi['inspector_name'] ?? 'Chưa cập nhật') ?></span>
                                </div>
                                <div class="qc-meta-item">
                                    <label class="val-text">Thời điểm kiểm tra:</label>
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
                        <div class="qc-note">📝 <?= htmlspecialchars($defects['note'] ?? 'Chưa cập nhật') ?></div>
                    </div>

                    <div class="divider"></div>

                    <!-- Winding -->
                    <div class="winding-section">
                        <div class="winding-title">📍 Thông tin cuộn</div>
                        <div class="winding-header">
                            <div class="winding-info-row">
                                <div class="winding-input">
                                    <label class="val-text">Máy cuộn:</label>
                                    <span
                                        class="val-sub"><?= htmlspecialchars($item['winding_machine'] ?? 'Chưa cập nhật') ?></span>
                                </div>
                                <div class="winding-input">
                                    <label class="val-text">Mã nhân viên:</label>
                                    <span class="val-sub"><?= htmlspecialchars($winding_employeeCode) ?></span>
                                </div>
                                <div class="winding-input">
                                    <label class="val-text">Họ tên nhân viên:</label>
                                    <span class="val-sub"><?= htmlspecialchars($winding_employeeName) ?></span>
                                </div>
                                <div class="winding-input">
                                    <label class="val-text">Kết quả thông khí:</label>
                                    <span class="val-sub"><?= htmlspecialchars($flow_test_result) ?></span>
                                </div>
                            </div>
                            <div class="winding-note">📝
                                <?= htmlspecialchars(trim($item['winding_note'] ?? '') === '' ? 'Chưa cập nhật' : $item['winding_note']) ?>
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

            <!-- THANH PHÂN TRANG -->
            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="pagination-wrapper"
                style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px; padding:12px;">
                <?php if ($pagination['currentPage'] > 1): ?>
                <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] - 1]) ?>" class="page-btn"
                    style="padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; text-decoration:none; color:#334155; background:#fff; font-size:14px;">
                    ‹ Trước
                </a>
                <?php endif; ?>

                <?php
                        $start = max(1, $pagination['currentPage'] - 2);
                        $end = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                        for ($p = $start; $p <= $end; $p++):
                            $isActive = ($p === (int)$pagination['currentPage']);
                        ?>
                <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"
                    style="padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; <?= $isActive ? 'background:#0284c7; color:#fff; border-color:#0369a1; font-weight:600;' : 'border:1px solid #cbd5e1; color:#334155; background:#fff;' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="<?= buildFilterUrl(['page' => $pagination['currentPage'] + 1]) ?>" class="page-btn"
                    style="padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; text-decoration:none; color:#334155; background:#fff; font-size:14px;">
                    Sau ›
                </a>
                <?php endif; ?>

                <span style="font-size:13px; color:#64748b; margin-left:10px;">
                    Trang <?= $pagination['currentPage'] ?> / <?= $pagination['totalPages'] ?> (Tổng
                    <?= number_format($pagination['totalRecords']) ?> Bobin)
                </span>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts dùng chung -->
    <script defer src="/WEB_BOBIN/public/assets/js/copyText.js?v=1"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=1"></script>

    <!-- Giữ vị trí cuộn khi chuyển trạng thái / phân trang -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterLinks = document.querySelectorAll(".filter-dashboard a, .pagination-wrapper a");
        filterLinks.forEach(link => {
            link.addEventListener("click", function() {
                sessionStorage.setItem("bobin_scroll_pos", window.scrollY);
            });
        });

        const scrollPos = sessionStorage.getItem("bobin_scroll_pos");
        if (scrollPos !== null) {
            window.scrollTo({
                top: parseInt(scrollPos, 10),
                behavior: "instant"
            });
            sessionStorage.removeItem("bobin_scroll_pos");
        }
    });
    </script>
</body>

</html>