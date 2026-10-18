<?php
// File: app/views/components/advancedBobinFilterAndStats.php
// Component Bộ lọc nâng cao (Mã nhân viên Đùn/Cuộn/QC, Mã sản phẩm, Tình trạng ngoại quan QC)
// và Thống kê số lượng Bobin tương ứng (TASK-046)

$currentEmployeeCode = trim((string)($_GET['employee_code'] ?? ''));
$currentProductCode  = trim((string)($_GET['product_code'] ?? ''));
$currentQcDefect     = trim((string)($_GET['qc_defect'] ?? 'all'));
if ($currentQcDefect === '') {
    $currentQcDefect = 'all';
}

$advEmployees = $data['employees'] ?? (class_exists('GlobalData') ? (GlobalData::$listEmployeeEntity ?? []) : []);
$advProducts  = $data['products'] ?? (class_exists('GlobalData') ? (GlobalData::$listProductEntity ?? []) : []);
$advProductCountsMap = $data['productCountsMap'] ?? [];

$defaultQcDefectStats = [
    'inspected_total' => 0,
    'ok_all'          => 0,
    'ng_any'          => 0,
    'gel'             => 0,
    'foreign_object'  => 0,
    'color_issue'     => 0,
    'print_quality'   => 0,
    'clump'           => 0,
    'scratch'         => 0,
    'streak'          => 0,
    'deformation'     => 0,
];
$advQcStats = array_merge($defaultQcDefectStats, $data['qcDefectStats'] ?? []);

$advTotalMatchingBobins = isset($totalRecords) ? (int)$totalRecords : count($bobins ?? []);

// Chuẩn hóa danh sách gợi ý nhân viên (Đùn, Cuộn, QC)
$employeeSuggestions = [];
$matchedEmployeeInfo = null;
foreach ($advEmployees as $emp) {
    $eCode = trim((string)($emp['employee_code'] ?? ''));
    if ($eCode === '') continue;
    $eName = trim((string)($emp['employee_name'] ?? ''));
    $eRole = strtolower(trim((string)($emp['role'] ?? '')));
    $roleLabel = match ($eRole) {
        'extrusion' => 'Đùn',
        'qc'        => 'QC',
        'winding'   => 'Cuộn',
        'admin'     => 'Admin',
        default     => strtoupper($eRole ?: 'NV')
    };
    $employeeSuggestions[$eCode] = [
        'code'       => $eCode,
        'name'       => $eName,
        'role'       => $eRole,
        'role_label' => $roleLabel
    ];
    if ($currentEmployeeCode !== '' && strcasecmp($currentEmployeeCode, $eCode) === 0) {
        $matchedEmployeeInfo = $employeeSuggestions[$eCode];
    }
}

// Chuẩn hóa danh sách gợi ý mã sản phẩm (kèm số lượng Bobin theo bộ lọc hiện hành)
$productSuggestions = [];
$matchedProductInfo = null;
foreach ($advProducts as $prod) {
    $pCode = trim((string)($prod['product_code'] ?? ''));
    if ($pCode === '') continue;
    $poCode = trim((string)($prod['production_order_code'] ?? ''));
    $pDesc  = trim((string)($prod['description'] ?? ''));
    $pCountVal = (int)($advProductCountsMap[$pCode] ?? 0);
    if (!isset($productSuggestions[$pCode])) {
        $productSuggestions[$pCode] = [
            'code'        => $pCode,
            'order_code'  => $poCode,
            'description' => $pDesc,
            'count'       => $pCountVal
        ];
    }
    if ($currentProductCode !== '' && strcasecmp($currentProductCode, $pCode) === 0) {
        $matchedProductInfo = $productSuggestions[$pCode];
    }
}
// Bổ sung các mã sản phẩm đang có mặt trong dữ liệu thực tế nhưng chưa có trong bảng danh mục
foreach ($advProductCountsMap as $pCodeKey => $cntVal) {
    $pCodeKey = trim((string)$pCodeKey);
    if ($pCodeKey === '') continue;
    if (!isset($productSuggestions[$pCodeKey])) {
        $productSuggestions[$pCodeKey] = [
            'code'        => $pCodeKey,
            'order_code'  => '',
            'description' => '',
            'count'       => (int)$cntVal
        ];
    } else {
        $productSuggestions[$pCodeKey]['count'] = (int)$cntVal;
    }
    if ($currentProductCode !== '' && strcasecmp($currentProductCode, $pCodeKey) === 0) {
        $matchedProductInfo = $productSuggestions[$pCodeKey];
    }
}

// Số lượng Bobin của mã sản phẩm đang được chọn/lọc
$selectedProductBobinCount = 0;
if ($currentProductCode !== '' && $currentProductCode !== 'all') {
    $selectedProductBobinCount = $advTotalMatchingBobins;
}

// Danh sách 8 trường ngoại quan QC chuẩn của hệ thống
$qcDefectFieldLabels = [
    'gel'            => ['label' => 'Gel',       'i18n' => 'qc_field_gel'],
    'foreign_object' => ['label' => 'Dị vật',    'i18n' => 'qc_field_foreign_object'],
    'color_issue'    => ['label' => 'Màu sắc',   'i18n' => 'qc_field_color_issue'],
    'print_quality'  => ['label' => 'Chữ in',    'i18n' => 'qc_field_print_quality'],
    'clump'          => ['label' => 'Vón cục',   'i18n' => 'qc_field_clump'],
    'scratch'        => ['label' => 'Trầy',      'i18n' => 'qc_field_scratch'],
    'streak'         => ['label' => 'Xước',      'i18n' => 'qc_field_streak'],
    'deformation'    => ['label' => 'Biến dạng', 'i18n' => 'qc_field_deformation'],
];

$qcFilterSelectOptions = [
    'all'            => '🛡️ ' . __('filter_qc_all') . ' (' . number_format($advTotalMatchingBobins) . ')',
    'ok_all'         => '✅ ' . __('filter_qc_ok_all') . ' (' . number_format($advQcStats['ok_all']) . ')',
    'ng_any'         => '⚠️ ' . __('filter_qc_ng_any') . ' (' . number_format($advQcStats['ng_any']) . ')',
    'gel'            => '🔴 ' . __('filter_qc_ng_prefix') . ' Gel (' . number_format($advQcStats['gel']) . ')',
    'foreign_object' => '🔴 ' . __('filter_qc_ng_prefix') . ' Dị vật (' . number_format($advQcStats['foreign_object']) . ')',
    'color_issue'    => '🔴 ' . __('filter_qc_ng_prefix') . ' Màu sắc (' . number_format($advQcStats['color_issue']) . ')',
    'print_quality'  => '🔴 ' . __('filter_qc_ng_prefix') . ' Chữ in (' . number_format($advQcStats['print_quality']) . ')',
    'clump'          => '🔴 ' . __('filter_qc_ng_prefix') . ' Vón cục (' . number_format($advQcStats['clump']) . ')',
    'scratch'        => '🔴 ' . __('filter_qc_ng_prefix') . ' Trầy (' . number_format($advQcStats['scratch']) . ')',
    'streak'         => '🔴 ' . __('filter_qc_ng_prefix') . ' Xước (' . number_format($advQcStats['streak']) . ')',
    'deformation'    => '🔴 ' . __('filter_qc_ng_prefix') . ' Biến dạng (' . number_format($advQcStats['deformation']) . ')',
];
?>

<div class="adv-filter-section" id="advBobinFilterSection">
    <!-- HÀNG BỘ LỌC NÂNG CAO: MÃ NHÂN VIÊN (ĐÙN/CUỘN/QC) | MÃ SẢN PHẨM | TÌNH TRẠNG NGOẠI QUAN QC -->
    <div class="adv-filter-row">
        <!-- 1. FILTER THEO MÃ SỐ NHÂN VIÊN (ĐÙN / CUỘN / QC) -->
        <div class="adv-filter-col">
            <div class="adv-filter-label">
                <span>👤 <span
                        data-i18n="filter_label_employee_code"><?= __('filter_label_employee_code') ?></span>:</span>
                <span class="adv-label-sub"
                    data-i18n="filter_emp_scope_badge"><?= __('filter_emp_scope_badge') ?></span>
            </div>
            <div class="adv-input-group <?= $currentEmployeeCode !== '' ? 'has-value' : '' ?>">
                <input type="text" id="advEmployeeCodeInput" class="adv-filter-input" list="advEmployeeDatalist"
                    autocomplete="off" placeholder="<?= htmlspecialchars(__('filter_ph_employee_code')) ?>"
                    data-i18n-ph="filter_ph_employee_code" value="<?= htmlspecialchars($currentEmployeeCode) ?>">
                <?php if ($currentEmployeeCode !== ''): ?>
                <a href="<?= buildFilterUrl(['employee_code' => null, 'page' => 1]) ?>" class="adv-clear-btn"
                    title="<?= htmlspecialchars(__('filter_clear_employee')) ?>">✕</a>
                <?php endif; ?>
                <button type="button" class="adv-apply-btn" id="btnApplyEmployeeFilter" data-i18n="btn_apply_filter">
                    <?= __('btn_apply_filter') ?>
                </button>
            </div>
            <datalist id="advEmployeeDatalist">
                <?php foreach ($employeeSuggestions as $empItem): ?>
                <option value="<?= htmlspecialchars($empItem['code']) ?>">
                    <?= htmlspecialchars($empItem['name'] . ' (' . $empItem['role_label'] . ')') ?>
                </option>
                <?php endforeach; ?>
            </datalist>
            <div class="adv-suggest-menu" id="advEmployeeSuggestMenu"></div>
        </div>

        <!-- 2. FILTER THEO MÃ SẢN PHẨM (PRODUCT_CODE) -->
        <div class="adv-filter-col">
            <div class="adv-filter-label">
                <span>📦 <span
                        data-i18n="filter_label_product_code"><?= __('filter_label_product_code') ?></span>:</span>
                <?php if ($currentProductCode !== '' && $currentProductCode !== 'all'): ?>
                <span class="adv-label-sub">
                    <?= htmlspecialchars($currentProductCode) ?>:
                    <strong><?= number_format($selectedProductBobinCount) ?></strong> Bobin
                </span>
                <?php else: ?>
                <span class="adv-label-sub">
                    <?= number_format(count($advProductCountsMap)) ?> mã SP •
                    <?= number_format(array_sum($advProductCountsMap)) ?> Bobin
                </span>
                <?php endif; ?>
            </div>
            <div
                class="adv-input-group <?= ($currentProductCode !== '' && $currentProductCode !== 'all') ? 'has-value' : '' ?>">
                <input type="text" id="advProductCodeInput" class="adv-filter-input" list="advProductDatalist"
                    autocomplete="off" placeholder="<?= htmlspecialchars(__('filter_ph_product_code')) ?>"
                    data-i18n-ph="filter_ph_product_code"
                    value="<?= htmlspecialchars($currentProductCode === 'all' ? '' : $currentProductCode) ?>">
                <?php if ($currentProductCode !== '' && $currentProductCode !== 'all'): ?>
                <a href="<?= buildFilterUrl(['product_code' => null, 'page' => 1]) ?>" class="adv-clear-btn"
                    title="<?= htmlspecialchars(__('filter_clear_product')) ?>">✕</a>
                <?php endif; ?>
                <button type="button" class="adv-apply-btn" id="btnApplyProductFilter" data-i18n="btn_apply_filter">
                    <?= __('btn_apply_filter') ?>
                </button>
            </div>
            <datalist id="advProductDatalist">
                <?php foreach ($productSuggestions as $prodItem): ?>
                <option value="<?= htmlspecialchars($prodItem['code']) ?>">
                    <?= htmlspecialchars(($prodItem['order_code'] ? "Lệnh: {$prodItem['order_code']} | " : '') . "SL: {$prodItem['count']} Bobin") ?>
                </option>
                <?php endforeach; ?>
            </datalist>
            <div class="adv-suggest-menu" id="advProductSuggestMenu"></div>
        </div>

        <!-- 3. FILTER THEO TÌNH TRẠNG NGOẠI QUAN NHÓM QC (8 TRƯỜNG NGOẠI QUAN) -->
        <div class="adv-filter-col">
            <div class="adv-filter-label">
                <span>🛡️ <span data-i18n="filter_label_qc_defect"><?= __('filter_label_qc_defect') ?></span>:</span>
                <span class="adv-label-sub"
                    data-i18n="filter_qc_8_fields_badge"><?= __('filter_qc_8_fields_badge') ?></span>
            </div>
            <select id="qcDefectFilter" class="filter-select" onchange="window.location.href = this.value;">
                <?php foreach ($qcFilterSelectOptions as $qKey => $qLabel): ?>
                <option value="<?= buildFilterUrl(['qc_defect' => $qKey, 'page' => 1]) ?>"
                    <?= $currentQcDefect === $qKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($qLabel) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

</div>

<script>
(function() {
    const employeeData =
        <?= json_encode(array_values($employeeSuggestions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productData =
        <?= json_encode(array_values($productSuggestions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function applyParamAndNavigate(paramName, paramValue) {
        const url = new URL(window.location.href);
        const trimmed = (paramValue || '').trim();
        if (!trimmed || trimmed === 'all') {
            url.searchParams.delete(paramName);
        } else {
            url.searchParams.set(paramName, trimmed);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    // Gắn sự kiện cho ô lọc Mã nhân viên (Đùn / Cuộn / QC)
    const empInput = document.getElementById('advEmployeeCodeInput');
    const empMenu = document.getElementById('advEmployeeSuggestMenu');
    const btnApplyEmp = document.getElementById('btnApplyEmployeeFilter');

    function renderEmployeeSuggest(query) {
        if (!empMenu) return;
        const q = (query || '').trim().toLowerCase();
        const matched = employeeData.filter(item => {
            if (!q) return true;
            return item.code.toLowerCase().includes(q) ||
                (item.name && item.name.toLowerCase().includes(q)) ||
                (item.role_label && item.role_label.toLowerCase().includes(q));
        }).slice(0, 30);

        if (matched.length === 0) {
            empMenu.classList.remove('open');
            empMenu.innerHTML = '';
            return;
        }

        empMenu.innerHTML = matched.map(item => `
                <div class="adv-suggest-item" data-code="${item.code.replace(/"/g, '&quot;')}">
                    <div class="adv-suggest-main">
                        <span class="adv-suggest-code">${item.code}</span>
                        <span class="adv-suggest-sub">${item.name || ''}</span>
                    </div>
                    <span class="adv-suggest-badge">${item.role_label || ''}</span>
                </div>
            `).join('');
        empMenu.classList.add('open');

        empMenu.querySelectorAll('.adv-suggest-item').forEach(el => {
            el.addEventListener('mousedown', function(e) {
                e.preventDefault();
                const code = this.getAttribute('data-code') || '';
                empInput.value = code;
                empMenu.classList.remove('open');
                applyParamAndNavigate('employee_code', code);
            });
        });
    }

    if (empInput) {
        empInput.addEventListener('focus', () => renderEmployeeSuggest(empInput.value));
        empInput.addEventListener('input', () => renderEmployeeSuggest(empInput.value));
        empInput.addEventListener('blur', () => {
            setTimeout(() => empMenu && empMenu.classList.remove('open'), 180);
        });
        empInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                empMenu && empMenu.classList.remove('open');
                applyParamAndNavigate('employee_code', empInput.value);
            }
        });
    }
    if (btnApplyEmp && empInput) {
        btnApplyEmp.addEventListener('click', () => {
            applyParamAndNavigate('employee_code', empInput.value);
        });
    }

    // Gắn sự kiện cho ô lọc Mã sản phẩm
    const prodInput = document.getElementById('advProductCodeInput');
    const prodMenu = document.getElementById('advProductSuggestMenu');
    const btnApplyProd = document.getElementById('btnApplyProductFilter');

    function renderProductSuggest(query) {
        if (!prodMenu) return;
        const q = (query || '').trim().toLowerCase();
        const matched = productData.filter(item => {
            if (!q) return true;
            return item.code.toLowerCase().includes(q) ||
                (item.order_code && item.order_code.toLowerCase().includes(q)) ||
                (item.description && item.description.toLowerCase().includes(q));
        }).slice(0, 35);

        if (matched.length === 0) {
            prodMenu.classList.remove('open');
            prodMenu.innerHTML = '';
            return;
        }

        prodMenu.innerHTML = matched.map(item => `
                <div class="adv-suggest-item" data-code="${item.code.replace(/"/g, '&quot;')}">
                    <div class="adv-suggest-main">
                        <span class="adv-suggest-code">${item.code}</span>
                        <span class="adv-suggest-sub">${item.order_code ? ('Lệnh SX: ' + item.order_code) : (item.description || 'Mã sản phẩm')}</span>
                    </div>
                    <span class="adv-suggest-badge ${item.count > 0 ? 'has-count' : ''}">${item.count} Bobin</span>
                </div>
            `).join('');
        prodMenu.classList.add('open');

        prodMenu.querySelectorAll('.adv-suggest-item').forEach(el => {
            el.addEventListener('mousedown', function(e) {
                e.preventDefault();
                const code = this.getAttribute('data-code') || '';
                prodInput.value = code;
                prodMenu.classList.remove('open');
                applyParamAndNavigate('product_code', code);
            });
        });
    }

    if (prodInput) {
        prodInput.addEventListener('focus', () => renderProductSuggest(prodInput.value));
        prodInput.addEventListener('input', () => renderProductSuggest(prodInput.value));
        prodInput.addEventListener('blur', () => {
            setTimeout(() => prodMenu && prodMenu.classList.remove('open'), 180);
        });
        prodInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                prodMenu && prodMenu.classList.remove('open');
                applyParamAndNavigate('product_code', prodInput.value);
            }
        });
    }
    if (btnApplyProd && prodInput) {
        btnApplyProd.addEventListener('click', () => {
            applyParamAndNavigate('product_code', prodInput.value);
        });
    }

    // Đồng bộ các trường lọc nâng cao khi người dùng nhấn nút "Lọc" ở thanh tìm kiếm chính (#filterForm)
    const mainFilterForm = document.getElementById('filterForm');
    if (mainFilterForm) {
        mainFilterForm.addEventListener('submit', () => {
            const syncHiddenInput = (name, val) => {
                let el = mainFilterForm.querySelector(`input[name="${name}"]`);
                const cleanVal = (val || '').trim();
                if (!cleanVal || cleanVal === 'all') {
                    if (el) el.remove();
                    return;
                }
                if (!el) {
                    el = document.createElement('input');
                    el.type = 'hidden';
                    el.name = name;
                    mainFilterForm.appendChild(el);
                }
                el.value = cleanVal;
            };
            if (empInput) syncHiddenInput('employee_code', empInput.value);
            if (prodInput) syncHiddenInput('product_code', prodInput.value);
        });
    }
})();
</script>