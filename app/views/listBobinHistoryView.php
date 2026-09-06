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

$currentStatus = $_GET['status'] ?? 'all';
$currentSize   = $_GET['bobin_size'] ?? 'all';
$currentType   = $_GET['bobin_type'] ?? 'all';

// ========================================================
// LOGIC MỚI: TÍNH TOÁN DUNG LƯỢNG VÀ BOBIN TRỐNG
// ========================================================
$capacityMap = $data['capacityMap'] ?? [
    'PL4-7 (TU04.TU06)' => 420,
    'PL4-7 (TU08~)'     => 480,
    'PL7-3'             => 460
];

// Luôn lấy đúng định mức thực tế: Chọn 'all' ra 1360, chọn size cụ thể ra đúng dung lượng của size đó
$totalRealBobins = ($currentSize === 'all')
    ? array_sum($capacityMap)
    : ($capacityMap[$currentSize] ?? 0);

// Số lượng Bobin ĐÃ ĐÙN = Số lượng Bobin chưa QC
$extrudedCount = $statusCounts['Busy_Unchecked'];

// LỌC DUY NHẤT: Đếm số lượng Bobin ĐÃ CUỘN (Lọc trùng lặp theo bobin_key_code)
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

// Tính Bobin Trống: Tổng thực tế (1360) - (Đã Đùn - Đã Cuộn [Unique])
$emptyBobins = $totalRealBobins - ($extrudedCount - $uniqueRolledCount);
// Đảm bảo số lượng nằm trong giới hạn thực tế (Từ 0 đến Max Capacity)
$emptyBobins = min($totalRealBobins, max(0, $emptyBobins));

// Số lượng Chưa kiểm tra QC
$untestedQC = max(0, $statusCounts['Busy_Unchecked'] - $statusCounts['Busy_Checked']);

// Tổng số kết quả tìm kiếm (Do trang Lịch sử không phân trang DB, count($bobins) là tổng số thực)
$totalRecords = count($bobins);
// ========================================================

// 2. CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ (CHART)
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
    'Điều chỉnh (Ngoại quan: Dị vật)' => 'Điều chỉnh (Ngoại quan: Dị vật)'
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
        $text = $goodDefect ? 'OK' : 'NG';
        return "<div class='vi-item $class'><span>$label</span><strong>$text</strong></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch sử Bobin</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinHistory.css?v=5">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/apexcharts.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/chart-helper.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <style>
        .kpi-card.empty-bobin {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .kpi-card.empty-bobin .kpi-icon {
            background: #cbd5e1;
            color: #475569;
        }

        .kpi-card.empty-bobin .kpi-val {
            color: #334155;
        }
    </style>
</head>

<body>
    <h1>Lịch sử Bobin</h1>

    <div class="container">
        <div id="qr-reader"></div>

        <!-- CONTROL BAR GIAO DIỆN MỚI -->
        <div class="control-bar-modern">
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="btn-back-modern">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listBobinHistoryView">

                    <!-- HÀNG TRÊN: Tìm kiếm | Quét QR | Xuất Excel -->
                    <div class="control-row top-row">
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="18" height="18" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="searchKeyword" name="keyword"
                                placeholder="Nhập hoặc quét mã Bobin..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <button type="button" id="btnScanQR" class="btn-modern btn-scan">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <path d="M3 14h7v7H3z"></path>
                            </svg>
                            Quét QR
                        </button>

                        <?php
                        $exportParams = $_GET;
                        $exportParams['url'] = 'bobin/exportHistoryExcel';
                        $exportUrl = '/WEB_BOBIN/public/index.php?' . http_build_query($exportParams);
                        ?>
                        <a href="<?= $exportUrl ?>" class="btn-modern btn-export">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <path d="M8 13h2"></path>
                                <path d="M8 17h2"></path>
                                <path d="M14 13h2"></path>
                                <path d="M14 17h2"></path>
                            </svg>
                            Xuất file Excel
                        </a>
                    </div>

                    <!-- HÀNG DƯỚI: Chọn ngày | Nút Lọc -->
                    <div class="control-row bottom-row">
                        <?php
                        $defaultToDate = date('Y-m-d');
                        $defaultFromDate = date('Y-m-d', strtotime('-7 days'));
                        $fromDateVal = !empty($_GET['from_date']) ? $_GET['from_date'] : $defaultFromDate;
                        $toDateVal = !empty($_GET['to_date']) ? $_GET['to_date'] : $defaultToDate;
                        ?>
                        <div class="date-picker-modern">
                            <input type="date" name="from_date" id="fromDate"
                                value="<?= htmlspecialchars($fromDateVal) ?>">
                        </div>
                        <span class="date-arrow">➝</span>
                        <div class="date-picker-modern">
                            <input type="date" name="to_date" id="toDate" value="<?= htmlspecialchars($toDateVal) ?>">
                        </div>

                        <button type="submit" class="btn-modern btn-filter" id="btnFilter">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Lọc
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- KHUNG THỐNG KÊ GIAO DIỆN MỚI 2 CỘT -->
        <div class="analytics-wrapper">
            <div class="analytics-left">
                <div class="analytics-title-box">
                    <div class="badge-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
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

            <!-- CỘT PHẢI: THẺ TỔNG + DANH SÁCH KPI -->
            <div class="analytics-right">
                <div class="total-card">
                    <div class="total-icon-wrap">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 12 12 17 22 12"></polyline>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </svg>
                    </div>
                    <div class="total-info">
                        <span class="label">Tổng số lượng Bobin</span>
                        <span class="value"><?= number_format($totalRealBobins) ?></span>
                    </div>
                </div>

                <div class="kpi-list">
                    <!-- Thẻ Bobin Trống (Chỉ hiển thị, không click) -->
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

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked']) ?>" class="kpi-card unchecked">
                        <div class="kpi-left">
                            <span class="kpi-icon">⏳</span>
                            <span class="kpi-name">CHƯA KIỂM TRA QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($untestedQC) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked']) ?>" class="kpi-card checked">
                        <div class="kpi-left">
                            <span class="kpi-icon">🛡️</span>
                            <span class="kpi-name">ĐÃ KIỂM TRA QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Checked']) ?></strong>
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
                        background: '#ffffff',
                        type: 'bar',
                        height: 380,
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
                    colors: ['#94a3b8', '#34d399', '#fb923c', '#38bdf8', '#fb7185'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '60%',
                            distributed: true,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        offsetX: 10,
                        offsetY: 0,
                        formatter: function(val) {
                            const percent = totalRealBobins > 0 ? ((val / totalRealBobins) * 100).toFixed(
                                1) : 0;
                            return `${val.toLocaleString('vi-VN')} (${percent}%)`;
                        },
                        style: {
                            fontSize: '13px',
                            fontWeight: 800,
                            colors: ["#676767"]
                        },
                        background: {
                            enabled: false
                        },
                        dropShadow: {
                            enabled: false
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
                        padding: {
                            top: 0,
                            right: 40,
                            bottom: 0,
                            left: 25
                        }
                    },
                    xaxis: {
                        categories: labels,
                        min: 0,
                        max: maxVal === 0 ? 10 : Math.ceil(maxVal * 1.25),
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '12px',
                                fontWeight: 600
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
                            align: 'left',
                            minWidth: 90,
                            maxWidth: 130,
                            offsetX: -15,
                            style: {
                                colors: '#1e293b',
                                fontSize: '14px',
                                fontWeight: 700
                            }
                        }
                    },
                    tooltip: {
                        theme: 'dark',
                        style: {
                            fontSize: '15px'
                        },
                        y: {
                            formatter: (val) => val.toLocaleString('vi-VN')
                        }
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        horizontalAlign: 'center',
                        fontSize: '13.5px',
                        fontFamily: 'system-ui, -apple-system, sans-serif',
                        fontWeight: 600,
                        labels: {
                            colors: '#475569'
                        },
                        markers: {
                            radius: 12,
                            width: 14,
                            height: 14,
                            offsetX: -4
                        },
                        itemMargin: {
                            horizontal: 10,
                            vertical: 8
                        }
                    }
                });
                statusChart.render();
            })();
        </script>

        <!-- BỘ LỌC CHI TIẾT (Trạng thái, Kích thước, Loại) -->
        <div class="filter-dashboard">
            <div class="filter-dashboard-header">
                <h2>Kết quả tìm kiếm: <span><?= number_format($totalRecords) ?></span> Lượt cập nhật</h2>
                <a href="<?= buildFilterUrl(['status' => 'all', 'bobin_size' => 'all', 'bobin_type' => 'all', 'keyword' => null]) ?>"
                    style="padding: 8px 16px; font-size:13px; font-weight:600; color:#475569; text-decoration:none; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; transition:0.2s;">
                    🔄 Đặt lại bộ lọc
                </a>
            </div>

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
                    <a href="<?= buildFilterUrl(['status' => 'Cancelled', 'page' => 1]) ?>"
                        class="status-btn <?= $currentStatus === 'Cancelled' ? 'active' : '' ?>">❌ Đã hủy</a>

                </div>
            </div>
            <!-- 2. Hàng lọc Kích thước -->
            <div class="filter-row">
                <div class="filter-label">📏 Kích thước:</div>
                <div class="filter-actions">
                    <?php foreach ($sizeOptions as $key => $label): ?>
                        <a href="<?= buildFilterUrl(['bobin_size' => $key]) ?>"
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
                        <a href="<?= buildFilterUrl(['bobin_type' => $key]) ?>"
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
                <div class="empty-state">Không tìm thấy dữ liệu nào trong khoảng thời gian này.</div>
            <?php else: ?>
                <div class="bobin-list">
                    <?php foreach ($bobins as $item): ?>
                        <?php
                        $rawStatus = $item['bobin_current_status'] ?? 'Unknown';
                        $status = strtolower($rawStatus);
                        $statusClass = match ($status) {
                            'rolled' => 'status_rolled',
                            'busy_unchecked' => 'status_busy_unchecked',
                            'busy_checked' => 'status_busy_checked',
                            'pending_cancellation' => 'status_pending_cancellation',
                            'cancelled' => 'status_cancelled',
                            default => 'status_unknown'
                        };

                        $viRaw = $item['visual_inspection'] ?? '';
                        $productsRaw = $item['products'] ?? '';
                        $extrusion_employeeRaw = $item['extrusion_employee'] ?? '';
                        $winding_employeeRaw = $item['winding_employee'] ?? '';
                        $materialLotRaw = $item['material_lot'] ?? '';
                        $printLot = $item['print_lot'] ?? 'Chưa cập nhật';
                        $finish_time = $item['finish_time'];
                        $productCode = 'Chưa cập nhật';
                        $extrusion_employeeName = 'Chưa cập nhật';
                        $extrusion_employeeCode = 'Chưa cập nhật';
                        $winding_employeeName = 'Chưa cập nhật';
                        $winding_employeeCode = 'Chưa cập nhật';
                        $materialLot = 'Chưa cập nhật';
                        $flow_test_result = $item['flow_test_result'] ?? 'Chưa cập nhật';

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
                                $productCode = $decoded['product_code'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($extrusion_employeeRaw) && trim($extrusion_employeeRaw) !== '') {
                            $decoded = json_decode($extrusion_employeeRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $extrusion_employeeCode = $decoded['employee_code'] ?? 'Chưa cập nhật';
                                $extrusion_employeeName = $decoded['employee_name'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($materialLotRaw) && trim($materialLotRaw) !== '') {
                            $decoded = json_decode($materialLotRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $materialLot = $decoded['lot'] ?? 'Chưa cập nhật';
                            }
                        }
                        if (is_string($winding_employeeRaw) && trim($winding_employeeRaw) !== '') {
                            $decoded = json_decode($winding_employeeRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $winding_employeeCode = $decoded['employee_code'] ?? 'Chưa cập nhật';
                                $winding_employeeName = $decoded['employee_name'] ?? 'Chưa cập nhật';
                            }
                        }

                        $formatted_time = date('dmY$His', strtotime($finish_time));
                        $fields = [$productCode, $materialLot, $printLot, $formatted_time];
                        $isMissingData = false;
                        foreach ($fields as $field) {
                            if (trim((string)$field) === '' || trim((string)$field) === 'Chưa cập nhật') {
                                $isMissingData = true;
                                break;
                            }
                        }
                        $mainInfoText = $isMissingData ? 'Chưa cập nhật đầy đủ thông tin cần thiết' : $productCode . '$' . $materialLot . '$' . $printLot . '$' . $formatted_time;
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
                                        'Rolled' => 'Đã cuộn',
                                        'Busy_Unchecked'  => 'Đang đợi QC kiểm tra',
                                        'Busy_Checked'  => 'Đã kiểm tra QC',
                                        'Pending_Cancellation' => 'Chờ hủy',
                                        'Cancelled' => 'Đã hủy',
                                        default => $rawStatus,
                                    };
                                    echo htmlspecialchars($displayStatus);
                                    ?>
                                </div>
                            </div>

                            <div class="info-grid">
                                <div class="field-item">
                                    <label>Mã sản phẩm</label>
                                    <div class="val-sub"><?= htmlspecialchars($productCode ?? 'Chưa cập nhật') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Mã nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeCode ?? 'Chưa cập nhật') ?>
                                    </div>
                                </div>
                                <div class="field-item">
                                    <label>Họ tên nhân viên</label>
                                    <div class="val-sub"><?= htmlspecialchars($extrusion_employeeName ?? 'Chưa cập nhật') ?>
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
                                    <div class="val-sub"><?= htmlspecialchars($materialLot ?? 'Chưa cập nhật') ?></div>
                                </div>
                                <div class="field-item">
                                    <label>Lot in</label>
                                    <div class="val-sub"><?= htmlspecialchars($item['print_lot'] ?? 'Chưa cập nhật') ?></div>
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
                                <div class="qc-note">📝
                                    <?= htmlspecialchars(trim($defects['note'] ?? '') === '' ? 'Chưa cập nhật' : $defects['note']) ?>
                                </div>
                            </div>

                            <div class="divider"></div>

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
                                            <span
                                                class="val-sub"><?= htmlspecialchars($winding_employeeCode ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                        <div class="winding-input">
                                            <label class="val-text">Họ tên nhân viên:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($winding_employeeName ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                        <div class="winding-input">
                                            <label class="val-text">Kết quả thông khí:</label>
                                            <span
                                                class="val-sub"><?= htmlspecialchars($flow_test_result ?? 'Chưa cập nhật') ?></span>
                                        </div>
                                    </div>
                                    <div class="winding-note">📝
                                        <?= htmlspecialchars(trim($item['winding_note'] ?? '') === '' ? 'Chưa cập nhật' : $item['winding_note']) ?>
                                    </div>
                                </div>
                                <div class="card-footer-simple">
                                    <?php if (!empty($item['updated_time'])): ?>
                                        <div class="update-time">🕒 <span class="val-text">Thời điểm cập nhật trạng thái:</span>
                                            <?= htmlspecialchars($item['updated_time']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/WEB_BOBIN/public/assets/js/copyText.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Manage/scanQR.js?v=<?= time() ?>"></script>

    <!-- Giữ vị trí cuộn khi chuyển trạng thái bộ lọc -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const filterLinks = document.querySelectorAll(".kpi-list a, .filter-dashboard a");
            filterLinks.forEach(link => {
                link.addEventListener("click", function() {
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
    <!-- HIỂN THỊ TOAST BÁO LỖI NẾU CÓ -->
    <?php if (!empty($data['error'])): ?>
        <style>
            .toast-message {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #2ecc71;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                font-weight: bold;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.5s ease;
            }

            .toast-message.show {
                opacity: 1;
                transform: translateY(0);
            }

            .toast-message.toast-error {
                background-color: #e74c3c;
            }
        </style>

        <div id="toastMessage" class="toast-message toast-error show">
            ⚠️ <?= htmlspecialchars($data['error']) ?>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toastMessage');
                if (toast) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 500); // Gỡ khỏi DOM sau khi mờ dần
                }
            }, 3000); // Tự động ẩn sau 3 giây
        </script>
    <?php endif; ?>
</body>

</html>