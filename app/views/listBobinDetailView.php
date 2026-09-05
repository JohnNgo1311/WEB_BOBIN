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
    <!-- Đổi version lên v=3 để ép trình duyệt nhận CSS mới -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinDetail.css?v=3">
    <link rel="icon" href="data:,">
    <!-- Nạp thư viện biểu đồ và QR Code -->
    <script src="/WEB_BOBIN/public/assets/js/apexcharts.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/chart-helper.js"></script>
    <script defer src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>

    <h1>Danh sách Bobin hiện tại</h1>

    <div class="container">
        <!-- CONTROL BAR -->
        <div id="qr-reader"></div>

        <!-- CONTROL BAR GIAO DIỆN MỚI -->
        <div class="control-bar-modern">

            <!-- Nút Back bên trái -->
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index" class="btn-back-modern">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>

            <div class="control-main">
                <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form-modern" id="filterForm">
                    <input type="hidden" name="url" value="bobin/listBobinDetailView">

                    <div class="control-row top-row">
                        <!-- Ô tìm kiếm -->
                        <div class="search-box-modern">
                            <svg class="icon-search" viewBox="0 0 24 24" width="18" height="18" stroke="#94a3b8"
                                stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" name="keyword" id="searchKeyword"
                                placeholder="Nhập hoặc quét mã Bobin..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <!-- Nút Quét QR -->
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

                        <!-- Nút Lọc (Gộp lên cùng hàng) -->
                        <button type="submit" class="btn-modern btn-filter" id="btnFilter">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Lọc
                        </button>

                        <!-- Nút Xuất Excel -->
                        <?php
                        $exportParams = $_GET;
                        unset($exportParams['page']); // Không phân trang khi xuất excel
                        $exportParams['url'] = 'bobin/exportDetailExcel';
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
                </form>
            </div>
        </div>

        <!-- KHUNG THỐNG KÊ GIAO DIỆN MỚI 2 CỘT -->
        <div class="analytics-wrapper">

            <!-- CỘT TRÁI: TIÊU ĐỀ + BIỂU ĐỒ -->
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
                    <h3>Thống kê trạng thái Bobin</h3>
                </div>

                <div class="chart-box">
                    <div id="statusPieChart"></div>
                </div>
            </div>

            <!-- CỘT PHẢI: THẺ TỔNG + DANH SÁCH KPI -->
            <div class="analytics-right">

                <!-- Thẻ Tổng Số Lượng -->
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
                        <span class="value"><?= number_format($totalBobins) ?></span>
                    </div>
                </div>

                <!-- Danh sách các thẻ KPI có thể click lọc -->
                <div class="kpi-list">
                    <a href="<?= buildFilterUrl(['status' => 'Rolled', 'page' => 1]) ?>" class="kpi-card rolled">
                        <div class="kpi-left">
                            <span class="kpi-icon">⏳</span>
                            <span class="kpi-name">Đã cuộn</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Rolled']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Unchecked', 'page' => 1]) ?>"
                        class="kpi-card unchecked">
                        <div class="kpi-left">
                            <span class="kpi-icon">⌛</span>
                            <span class="kpi-name">Chưa QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Unchecked']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Busy_Checked', 'page' => 1]) ?>" class="kpi-card checked">
                        <div class="kpi-left">
                            <span class="kpi-icon">✓</span>
                            <span class="kpi-name">Đã QC</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Busy_Checked']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Line', 'page' => 1]) ?>" class="kpi-card line">
                        <div class="kpi-left">
                            <span class="kpi-icon">⚙️</span>
                            <span class="kpi-name">Tồn line</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Line']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>

                    <a href="<?= buildFilterUrl(['status' => 'Pending_Cancellation', 'page' => 1]) ?>"
                        class="kpi-card pending">
                        <div class="kpi-left">
                            <span class="kpi-icon">✕</span>
                            <span class="kpi-name">Chờ hủy</span>
                        </div>
                        <div class="kpi-right">
                            <strong class="kpi-val"><?= number_format($statusCounts['Pending_Cancellation']) ?></strong>
                            <span class="kpi-arrow">›</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- VẼ CHART TỨC THÌ VỚI ANIMATION MƯỢT -->
        <script>
            (function() {
                const rawData = <?= json_encode($chartSeries, JSON_NUMERIC_CHECK) ?>;
                const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                const totalBobin = rawData.reduce((a, b) => a + b, 0) || 1;
                const maxVal = Math.max(...rawData, 0);

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
                    colors: ['#34d399', '#fb923c', '#38bdf8', '#a855f7', '#fb7185'],
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
                            const percent = ((val / totalBobin) * 100).toFixed(1);
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
                            right: 30,
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
                            horizontal: 16,
                            vertical: 8
                        }
                    }
                });
                statusChart.render();
            })();
        </script>

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
                <div class="empty-state">Không tìm thấy Bobin nào khớp với bộ lọc.</div>
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