<?php
// File: app/views/employeePermissionsView.php
// Trang Quản lý Phân quyền Tài khoản Người dùng - SMC Pro

$employees        = $data['employees'] ?? [];
$selectedCode     = $data['selectedCode'] ?? '';
$selectedEmployee = $data['selectedEmployee'] ?? null;
$allPermissions   = $data['allPermissions'] ?? [];
$pendingCount     = $data['pendingCount'] ?? 0;
$userRole         = $data['userRole'] ?? ($_SESSION['user']['role'] ?? '');
$userName         = $data['userName'] ?? ($_SESSION['user']['employee_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('perm_page_title') ?> | SMC WEB_BOBIN</title>

    <!-- CSS Offline Local -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/employeePermissions.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">

    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
</head>

<body>
    <!-- Vertical Left Sidebar -->
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="perm-page-wrapper">
        <!-- Hero Header -->
        <header class="perm-hero">
            <div class="perm-hero-content">
                <h1>
                    <span>🔐</span>
                    <span data-i18n="perm_page_title"><?= __('perm_page_title') ?></span>
                </h1>
                <p data-i18n="perm_page_subtitle"><?= __('perm_page_subtitle') ?></p>
            </div>
            <div class="perm-hero-badge">
                <span>⚡</span>
                <span>Role-Based Access Control</span>
            </div>
        </header>

        <!-- Search & Quick Selection Box -->
        <section class="perm-search-box">
            <div class="perm-search-grid">
                <div class="perm-search-input-wrap">
                    <span class="perm-search-icon">🔍</span>
                    <input type="text"
                           id="searchEmpInput"
                           class="perm-search-input"
                           placeholder="<?= __('perm_search_placeholder') ?>"
                           data-i18n="[placeholder]perm_search_placeholder"
                           list="empDatalist"
                           value="<?= htmlspecialchars($selectedCode) ?>"
                           autocomplete="off">
                    <datalist id="empDatalist">
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= htmlspecialchars($emp['employee_code']) ?>">
                                <?= htmlspecialchars($emp['employee_name']) ?> (<?= strtoupper($emp['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <button type="button" id="btnSearchEmp" class="perm-btn-search">
                    <span data-i18n="perm_btn_search"><?= __('perm_btn_search') ?></span>
                </button>
            </div>

            <!-- Quick Selector -->
            <div class="perm-quick-bar">
                <span class="perm-quick-label" data-i18n="perm_quick_select"><?= __('perm_quick_select') ?></span>
                <select id="quickSelectEmp" class="perm-quick-select">
                    <option value="">-- Chọn nhân viên từ danh sách --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['employee_code']) ?>"
                                <?= ($selectedCode === $emp['employee_code']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['employee_code']) ?> - <?= htmlspecialchars($emp['employee_name']) ?> (<?= strtoupper($emp['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- Empty State Container (khi chưa chọn nhân viên) -->
        <section class="perm-empty-state" id="emptyState" style="<?= $selectedEmployee ? 'display:none;' : '' ?>">
            <div class="perm-empty-icon">👥</div>
            <div class="perm-empty-title" data-i18n="perm_select_employee_prompt"><?= __('perm_select_employee_prompt') ?></div>
            <p class="perm-empty-text">
                Nhập mã nhân viên vào ô tìm kiếm hoặc chọn một nhân viên từ danh mục thả xuống ở trên để bắt đầu cấu hình quyền hạn.
            </p>
        </section>

        <!-- Employee Detail & Permissions Matrix -->
        <section id="permDetailSection" style="<?= $selectedEmployee ? '' : 'display:none;' ?>">
            <!-- Profile Info Card -->
            <div class="perm-profile-card">
                <div class="perm-profile-left">
                    <div class="perm-avatar" id="profAvatar">A</div>
                    <div>
                        <div class="perm-info-name">
                            <span id="profName">Nguyễn Văn A</span>
                            <span class="badge-role" id="profRoleBadge">EXTRUSION</span>
                            <span class="badge-status-pill" id="profStatusBadge">Đang hoạt động</span>
                            <span class="badge-perm-type" id="profPermTypeBadge">Mặc định</span>
                        </div>
                        <div class="perm-info-meta">
                            <span class="perm-meta-item">
                                <strong>Mã NV:</strong> <span id="profCode">00000000</span>
                            </span>
                            <span class="perm-meta-item">
                                <strong>Tài khoản:</strong> <span id="profUsername">user</span>
                            </span>
                            <span class="perm-meta-item">
                                <strong>Cập nhật:</strong> <span id="profUpdateTime">---</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Tools -->
                <div class="perm-profile-actions">
                    <button type="button" class="perm-btn-tool" id="btnSelectAll" title="Chọn tất cả quyền">
                        <span>☑️</span>
                        <span data-i18n="perm_btn_select_all"><?= __('perm_btn_select_all') ?></span>
                    </button>
                    <button type="button" class="perm-btn-tool" id="btnDeselectAll" title="Bỏ chọn tất cả quyền">
                        <span>⬜</span>
                        <span data-i18n="perm_btn_deselect_all"><?= __('perm_btn_deselect_all') ?></span>
                    </button>
                    <button type="button" class="perm-btn-tool btn-reset-role" id="btnResetDefault" title="Khôi phục quyền mặc định">
                        <span>🔄</span>
                        <span data-i18n="perm_btn_reset_default"><?= __('perm_btn_reset_default') ?></span>
                    </button>
                </div>
            </div>

            <!-- Permissions Matrix Form -->
            <form id="permsForm" onsubmit="return false;">
                <input type="hidden" id="currentEmpCode" value="">

                <div class="perm-matrix-container">
                    <?php foreach ($allPermissions as $groupKey => $group): ?>
                        <div class="perm-group-card" data-group="<?= htmlspecialchars($groupKey) ?>">
                            <div class="perm-group-header">
                                <div class="perm-group-title">
                                    <span class="perm-group-icon"><?= $group['icon'] ?></span>
                                    <span data-i18n="<?= $group['title_key'] ?>"><?= __($group['title_key']) ?></span>
                                </div>
                                <button type="button" class="perm-btn-check-all-group" onclick="toggleGroupPerms('<?= htmlspecialchars($groupKey) ?>')">
                                    Chọn / Bỏ nhóm này
                                </button>
                            </div>

                            <div class="perm-items-grid">
                                <?php foreach ($group['items'] as $permKey => $item): ?>
                                    <label class="perm-item-box" id="box_<?= htmlspecialchars($permKey) ?>">
                                        <div class="perm-checkbox-custom">
                                            <input type="checkbox"
                                                   name="perms[]"
                                                   value="<?= htmlspecialchars($permKey) ?>"
                                                   data-group="<?= htmlspecialchars($groupKey) ?>"
                                                   id="chk_<?= htmlspecialchars($permKey) ?>"
                                                   onchange="onPermCheckboxChange(this)">
                                            <div class="perm-check-mark"></div>
                                        </div>
                                        <div class="perm-item-content">
                                            <div class="perm-item-name">
                                                <span data-i18n="<?= $item['name_key'] ?>"><?= __($item['name_key']) ?></span>
                                                <span class="perm-item-key"><?= htmlspecialchars($permKey) ?></span>
                                            </div>
                                            <div class="perm-item-desc" data-i18n="<?= $item['desc_key'] ?>">
                                                <?= __($item['desc_key']) ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sticky Bottom Action Bar -->
                <div class="perm-sticky-bar">
                    <div class="perm-sticky-summary">
                        <span>Đã cấp:</span>
                        <span class="perm-count-badge" id="selectedCountBadge">0 / <?= count(AuthHelper::getAllPermissionKeys()) ?></span>
                        <span style="font-size:13px; color:#94a3b8;">quyền hạn thao tác</span>
                    </div>

                    <div style="display:flex; gap:12px; align-items:center;">
                        <button type="button" class="perm-btn-save" id="btnSavePerms">
                            <span>💾</span>
                            <span data-i18n="perm_btn_save"><?= __('perm_btn_save') ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </main>

    <!-- Floating Toast Notification -->
    <div class="perm-toast" id="permToast">
        <span id="toastIcon">✅</span>
        <span id="toastMsg">Thao tác thành công</span>
    </div>

    <!-- JavaScript Xử Lý Nghiệp Vụ Phân Quyền -->
    <script>
    (function () {
        'use strict';

        let currentEmployee = null;
        const totalPermsCount = <?= count(AuthHelper::getAllPermissionKeys()) ?>;

        const searchInput    = document.getElementById('searchEmpInput');
        const btnSearch      = document.getElementById('btnSearchEmp');
        const quickSelect    = document.getElementById('quickSelectEmp');
        const emptyState     = document.getElementById('emptyState');
        const detailSection  = document.getElementById('permDetailSection');
        const btnSave        = document.getElementById('btnSavePerms');
        const btnSelectAll   = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');
        const btnResetDef    = document.getElementById('btnResetDefault');
        const countBadge     = document.getElementById('selectedCountBadge');

        // Hàm hiển thị Toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('permToast');
            const msgEl = document.getElementById('toastMsg');
            const iconEl = document.getElementById('toastIcon');

            msgEl.textContent = message;
            toast.className = 'perm-toast ' + type + ' show';
            iconEl.textContent = type === 'success' ? '✅' : '⚠️';

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3500);
        }

        // Lấy thông tin nhân viên qua AJAX
        function loadEmployee(code) {
            if (!code || !code.trim()) {
                showToast('Vui lòng nhập hoặc chọn mã nhân viên.', 'error');
                return;
            }

            code = code.trim();
            btnSearch.disabled = true;
            btnSearch.innerHTML = '<span>⏳</span> <span>Đang tìm...</span>';

            fetch('/WEB_BOBIN/public/index.php?url=employee/getEmployeePermissions&employee_code=' + encodeURIComponent(code))
                .then(res => res.json())
                .then(data => {
                    btnSearch.disabled = false;
                    btnSearch.innerHTML = '<span>🔍</span> <span><?= __("perm_btn_search") ?></span>';

                    if (!data.success) {
                        showToast(data.error || 'Không tìm thấy nhân viên.', 'error');
                        return;
                    }

                    renderEmployeeData(data.employee);
                })
                .catch(err => {
                    btnSearch.disabled = false;
                    btnSearch.innerHTML = '<span>🔍</span> <span><?= __("perm_btn_search") ?></span>';
                    showToast('Lỗi kết nối máy chủ: ' + err.message, 'error');
                });
        }

        // Render dữ liệu nhân viên và các checkbox quyền
        function renderEmployeeData(emp) {
            currentEmployee = emp;
            document.getElementById('currentEmpCode').value = emp.employee_code;

            // Profile info
            document.getElementById('profName').textContent = emp.employee_name;
            document.getElementById('profCode').textContent = emp.employee_code;
            document.getElementById('profUsername').textContent = emp.username;
            document.getElementById('profUpdateTime').textContent = emp.updated_time || '---';

            // Avatar & Role
            const avatar = document.getElementById('profAvatar');
            avatar.textContent = (emp.employee_name || 'U').charAt(0).toUpperCase();
            avatar.className = 'perm-avatar role-' + (emp.role || 'extrusion');

            const roleBadge = document.getElementById('profRoleBadge');
            roleBadge.textContent = (emp.role || 'EXTRUSION').toUpperCase();
            roleBadge.className = 'badge-role role-' + (emp.role || 'extrusion');

            const statusBadge = document.getElementById('profStatusBadge');
            if (emp.is_active === 1) {
                statusBadge.textContent = 'Đang hoạt động';
                statusBadge.className = 'badge-status-pill active';
            } else {
                statusBadge.textContent = 'Đã khóa';
                statusBadge.className = 'badge-status-pill inactive';
            }

            const permTypeBadge = document.getElementById('profPermTypeBadge');
            if (emp.has_custom_permissions) {
                permTypeBadge.textContent = 'Quyền tùy chỉnh';
                permTypeBadge.className = 'badge-perm-type custom';
            } else {
                permTypeBadge.textContent = 'Mặc định theo Role';
                permTypeBadge.className = 'badge-perm-type default';
            }

            // Đồng bộ Checkboxes
            const activePerms = emp.permissions || [];
            const allCheckboxes = document.querySelectorAll('input[name="perms[]"]');

            allCheckboxes.forEach(cb => {
                const isChecked = activePerms.includes(cb.value);
                cb.checked = isChecked;
                const parentBox = document.getElementById('box_' + cb.value);
                if (parentBox) {
                    parentBox.classList.toggle('is-checked', isChecked);
                }
            });

            updateCountSummary();

            // Hiển thị section chi tiết
            emptyState.style.display = 'none';
            detailSection.style.display = 'block';

            // Cập nhật URL trình duyệt (không reload trang)
            const newUrl = '/WEB_BOBIN/public/index.php?url=employee/permissionsView&employee_code=' + encodeURIComponent(emp.employee_code);
            window.history.replaceState({ path: newUrl }, '', newUrl);
        }

        // Cập nhật số lượng quyền được check
        function updateCountSummary() {
            const checkedCount = document.querySelectorAll('input[name="perms[]"]:checked').length;
            countBadge.textContent = checkedCount + ' / ' + totalPermsCount;
        }

        // Sự kiện checkbox thay đổi
        window.onPermCheckboxChange = function (cb) {
            const parentBox = document.getElementById('box_' + cb.value);
            if (parentBox) {
                parentBox.classList.toggle('is-checked', cb.checked);
            }
            updateCountSummary();
        };

        // Bật / tắt cả nhóm
        window.toggleGroupPerms = function (groupKey) {
            const groupBoxes = document.querySelectorAll(`input[name="perms[]"][data-group="${groupKey}"]`);
            if (groupBoxes.length === 0) return;

            // Kiểm tra xem nhóm đã check hết chưa
            let allChecked = true;
            groupBoxes.forEach(cb => {
                if (!cb.checked) allChecked = false;
            });

            const newCheckedState = !allChecked;
            groupBoxes.forEach(cb => {
                cb.checked = newCheckedState;
                const parentBox = document.getElementById('box_' + cb.value);
                if (parentBox) {
                    parentBox.classList.toggle('is-checked', newCheckedState);
                }
            });

            updateCountSummary();
        };

        // Chọn tất cả
        btnSelectAll.addEventListener('click', function () {
            document.querySelectorAll('input[name="perms[]"]').forEach(cb => {
                cb.checked = true;
                const parentBox = document.getElementById('box_' + cb.value);
                if (parentBox) parentBox.classList.add('is-checked');
            });
            updateCountSummary();
        });

        // Bỏ chọn tất cả
        btnDeselectAll.addEventListener('click', function () {
            document.querySelectorAll('input[name="perms[]"]').forEach(cb => {
                cb.checked = false;
                const parentBox = document.getElementById('box_' + cb.value);
                if (parentBox) parentBox.classList.remove('is-checked');
            });
            updateCountSummary();
        });

        // Khôi phục quyền mặc định theo Role
        btnResetDef.addEventListener('click', function () {
            if (!currentEmployee) return;

            if (!confirm(`Khôi phục quyền về mặc định theo vai trò [${currentEmployee.role.toUpperCase()}] cho nhân viên này?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('employee_code', currentEmployee.employee_code);
            formData.append('reset_default', '1');

            fetch('/WEB_BOBIN/public/index.php?url=employee/updatePermissions', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    loadEmployee(currentEmployee.employee_code);
                } else {
                    showToast(data.error || 'Khôi phục thất bại.', 'error');
                }
            })
            .catch(err => {
                showToast('Lỗi: ' + err.message, 'error');
            });
        });

        // Lưu phân quyền
        btnSave.addEventListener('click', function () {
            if (!currentEmployee) return;

            const checkedPerms = [];
            document.querySelectorAll('input[name="perms[]"]:checked').forEach(cb => {
                checkedPerms.push(cb.value);
            });

            btnSave.disabled = true;
            btnSave.innerHTML = '<span>⏳</span> <span>Đang lưu...</span>';

            const formData = new FormData();
            formData.append('employee_code', currentEmployee.employee_code);
            formData.append('permissions', JSON.stringify(checkedPerms));

            fetch('/WEB_BOBIN/public/index.php?url=employee/updatePermissions', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<span>💾</span> <span><?= __("perm_btn_save") ?></span>';

                if (data.success) {
                    showToast(data.message, 'success');
                    const permTypeBadge = document.getElementById('profPermTypeBadge');
                    permTypeBadge.textContent = 'Quyền tùy chỉnh';
                    permTypeBadge.className = 'badge-perm-type custom';
                } else {
                    showToast(data.error || 'Lưu thất bại.', 'error');
                }
            })
            .catch(err => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<span>💾</span> <span><?= __("perm_btn_save") ?></span>';
                showToast('Lỗi kết nối máy chủ: ' + err.message, 'error');
            });
        });

        // Tìm kiếm khi nhấn Enter
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadEmployee(searchInput.value);
            }
        });

        // Tìm kiếm khi nhấn nút Tra cứu
        btnSearch.addEventListener('click', function () {
            loadEmployee(searchInput.value);
        });

        // Tìm kiếm khi chọn từ dropdown chọn nhanh
        quickSelect.addEventListener('change', function () {
            if (this.value) {
                searchInput.value = this.value;
                loadEmployee(this.value);
            }
        });

        // Tự động load nếu có employee_code sẵn từ server
        <?php if (!empty($selectedCode)): ?>
            loadEmployee('<?= htmlspecialchars($selectedCode) ?>');
        <?php endif; ?>

    })();
    </script>
</body>
</html>

