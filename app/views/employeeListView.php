<?php
$employees = $data['employees'] ?? [];
$stats     = $data['stats'] ?? ['total' => 0, 'extrusion' => 0, 'qc' => 0, 'winding' => 0, 'admin' => 0];
$filters   = $data['filters'] ?? ['keyword' => '', 'role' => 'all', 'status' => 'all'];
$userRole  = $_SESSION['user']['role'] ?? '';
$currentUrl = $_GET['url'] ?? 'employee/index';
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$pendingCount = GlobalData::$pendingBobinCount ?? 0;

$successMsg = $_GET['success'] ?? '';
$errorMsg   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= __('page_employee_list') ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/employeeList.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
</head>
<body>

    <!-- 1. THANH ĐIỀU HƯỚNG CHUẨN ĐỒNG BỘ -->
    <div class="menu-bar">
        <div class="menu-left">
            <!-- Nhóm Đùn -->
            <?php if (in_array($userRole, ['extrusion', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/index"
                    class="<?= in_array($currentUrl, ['bobin/index', 'bobin/extrusion']) ? 'active-nav' : '' ?>"
                    data-i18n="nav_extrusion">
                    <?= __('nav_extrusion') ?>
                </a>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView"
                    class="<?= ($currentUrl === 'bobin/extrusionEditBobinView') ? 'active-nav' : '' ?>"
                    data-i18n="nav_extrusion_edit">
                    <?= __('nav_extrusion_edit') ?>
                </a>
            <?php endif; ?>

            <!-- Nhóm QC -->
            <?php if (in_array($userRole, ['qc', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC"
                    class="<?= ($currentUrl === 'bobin/listBobinView_QC') ? 'active-nav' : '' ?>"
                    data-i18n="nav_qc">
                    <?= __('nav_qc') ?>
                </a>
            <?php endif; ?>

            <!-- Nhóm Cuộn -->
            <?php if (in_array($userRole, ['winding', 'admin'])): ?>
                <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView"
                    class="<?= in_array($currentUrl, ['bobin/windingView', 'bobin/listBobinView_Winding']) ? 'active-nav' : '' ?>"
                    data-i18n="nav_winding">
                    <?= __('nav_winding') ?>
                </a>
            <?php endif; ?>

            <!-- Các trang theo dõi công khai -->
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView"
                class="<?= ($currentUrl === 'bobin/listBobinDetailView') ? 'active-nav' : '' ?>"
                data-i18n="nav_bobin_list">
                <?= __('nav_bobin_list') ?>
            </a>

            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
                class="<?= ($currentUrl === 'bobin/listBobinHistoryView') ? 'active-nav' : '' ?>"
                data-i18n="nav_bobin_history">
                <?= __('nav_bobin_history') ?>
            </a>

            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView"
                class="menu-pending-link <?= ($currentUrl === 'bobin/listPendingCancellationView') ? 'active-nav' : '' ?>"
                data-i18n="nav_pending_cancel">
                <?= __('nav_pending_cancel') ?>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge-pending-count"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Mục quản trị Admin: Danh sách nhân viên -->
            <?php if ($userRole === 'admin'): ?>
                <a href="/WEB_BOBIN/public/index.php?url=employee/index"
                    class="active-nav"
                    data-i18n="nav_employee_list">
                    <?= __('nav_employee_list') ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="menu-right">
            <?php require ROOT_PATH . '/app/views/components/languageSwitcher.php'; ?>
            <?php if (isset($_SESSION['user'])): ?>
                <span style="color:#cbd5e1; font-size:13px; font-weight:600; margin-right:8px;">
                    👤 <?= htmlspecialchars($_SESSION['user']['employee_name']) ?> (<?= strtoupper($userRole) ?>)
                </span>
                <a href="/WEB_BOBIN/public/index.php?url=auth/changePassword" class="btn-change-pwd" title="Đổi mật khẩu tài khoản" data-i18n="nav_change_pwd">
                    <?= __('nav_change_pwd') ?>
                </a>
                <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="logout-btn" data-i18n="nav_logout">
                    <?= __('nav_logout') ?>
                </a>
            <?php else: ?>
                <a href="/WEB_BOBIN/public/index.php?url=auth/login" class="logout-btn" style="background:#2563eb;" data-i18n="nav_login">
                    <?= __('nav_login') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. CONTAINER CHÍNH -->
    <div class="emp-container">

        <!-- THÔNG BÁO FLASH -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert-box alert-success">
                <span>✅ <?= htmlspecialchars($successMsg) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">✕</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert-box alert-error">
                <span>⚠️ <?= htmlspecialchars($errorMsg) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">✕</button>
            </div>
        <?php endif; ?>

        <!-- TIÊU ĐỀ TRANG -->
        <div class="emp-header">
            <h1 class="emp-title" data-i18n="emp_manage_title"><?= __('emp_manage_title') ?></h1>
            <p class="emp-subtitle" data-i18n="emp_manage_subtitle"><?= __('emp_manage_subtitle') ?></p>
        </div>

        <!-- THẺ THỐNG KÊ NHÂN SỰ -->
        <div class="stat-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <div class="stat-number"><?= (int)$stats['total'] ?></div>
                    <div class="stat-label" data-i18n="emp_total_stat"><?= __('emp_total_stat') ?></div>
                </div>
            </div>

            <div class="stat-card stat-ext">
                <div class="stat-icon">⚙️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= (int)$stats['extrusion'] ?></div>
                    <div class="stat-label" data-i18n="emp_ext_stat"><?= __('emp_ext_stat') ?></div>
                </div>
            </div>

            <div class="stat-card stat-qc">
                <div class="stat-icon">🔍</div>
                <div class="stat-info">
                    <div class="stat-number"><?= (int)$stats['qc'] ?></div>
                    <div class="stat-label" data-i18n="emp_qc_stat"><?= __('emp_qc_stat') ?></div>
                </div>
            </div>

            <div class="stat-card stat-wind">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <div class="stat-number"><?= (int)$stats['winding'] ?></div>
                    <div class="stat-label" data-i18n="emp_wind_stat"><?= __('emp_wind_stat') ?></div>
                </div>
            </div>

            <div class="stat-card stat-admin">
                <div class="stat-icon">🛡️</div>
                <div class="stat-info">
                    <div class="stat-number"><?= (int)$stats['admin'] ?></div>
                    <div class="stat-label" data-i18n="emp_admin_stat"><?= __('emp_admin_stat') ?></div>
                </div>
            </div>
        </div>

        <!-- THANH CÔNG CỤ: TÌM KIẾM, BỘ LỌC VÀ CÁC THAO TÁC -->
        <div class="emp-toolbar">
            <!-- Form Tìm kiếm & Lọc -->
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form">
                <input type="hidden" name="url" value="employee/index">

                <div class="search-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword']) ?>"
                        placeholder="Tìm theo Mã NV, Họ tên, Tên đăng nhập..."
                        data-placeholder-i18n="search">
                </div>

                <div class="filter-group">
                    <label data-i18n="emp_role"><?= __('emp_role') ?>:</label>
                    <select name="role" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['role'] === 'all') ? 'selected' : '' ?>>-- Tất cả vai trò --</option>
                        <option value="extrusion" <?= ($filters['role'] === 'extrusion') ? 'selected' : '' ?> data-i18n="emp_role_extrusion"><?= __('emp_role_extrusion') ?></option>
                        <option value="qc" <?= ($filters['role'] === 'qc') ? 'selected' : '' ?> data-i18n="emp_role_qc"><?= __('emp_role_qc') ?></option>
                        <option value="winding" <?= ($filters['role'] === 'winding') ? 'selected' : '' ?> data-i18n="emp_role_winding"><?= __('emp_role_winding') ?></option>
                        <option value="admin" <?= ($filters['role'] === 'admin') ? 'selected' : '' ?> data-i18n="emp_role_admin"><?= __('emp_role_admin') ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label data-i18n="emp_status"><?= __('emp_status') ?>:</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?= ($filters['status'] === 'all') ? 'selected' : '' ?>>-- Tất cả tình trạng --</option>
                        <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?> data-i18n="emp_status_active"><?= __('emp_status_active') ?></option>
                        <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : '' ?> data-i18n="emp_status_inactive"><?= __('emp_status_inactive') ?></option>
                    </select>
                </div>

                <button type="submit" class="btn-filter" data-i18n="filter">🔍 <?= __('filter') ?></button>
                <?php if ($filters['keyword'] !== '' || $filters['role'] !== 'all' || $filters['status'] !== 'all'): ?>
                    <a href="/WEB_BOBIN/public/index.php?url=employee/index" class="btn-reset-filter" title="Xóa bộ lọc" data-i18n="reset_filter">✕ <?= __('reset_filter') ?></a>
                <?php endif; ?>
            </form>

            <!-- Nhóm nút hành động nhanh -->
            <div class="btn-action-group">
                <button type="button" class="btn-action-primary" onclick="openAddModal()" data-i18n="emp_btn_add">
                    <?= __('emp_btn_add') ?>
                </button>

                <button type="button" class="btn-action-success" onclick="openImportModal()" data-i18n="emp_btn_import">
                    <?= __('emp_btn_import') ?>
                </button>

                <a href="/WEB_BOBIN/public/index.php?url=employee/exportExcel" class="btn-action-info" data-i18n="emp_btn_export">
                    <?= __('emp_btn_export') ?>
                </a>

                <a href="/WEB_BOBIN/public/index.php?url=employee/downloadTemplate" class="btn-action-secondary" data-i18n="emp_btn_template">
                    <?= __('emp_btn_template') ?>
                </a>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH NHÂN VIÊN -->
        <div class="table-wrapper">
            <table class="emp-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;" data-i18n="stt"><?= __('stt') ?></th>
                        <th style="width: 120px;" data-i18n="emp_code"><?= __('emp_code') ?></th>
                        <th style="min-width: 180px;" data-i18n="emp_name"><?= __('emp_name') ?></th>
                        <th style="min-width: 130px;" data-i18n="emp_username"><?= __('emp_username') ?></th>
                        <th style="width: 140px; text-align: center;" data-i18n="emp_role"><?= __('emp_role') ?></th>
                        <th style="width: 150px; text-align: center;" data-i18n="emp_status"><?= __('emp_status') ?></th>
                        <th style="width: 160px; text-align: center;" data-i18n="emp_password"><?= __('emp_password') ?></th>
                        <th style="width: 200px; text-align: center;" data-i18n="actions"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="8" class="text-center empty-state" style="padding: 40px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                <div style="font-weight: 600; font-size: 15px;">Không tìm thấy nhân viên nào phù hợp.</div>
                                <div style="font-size: 13px; margin-top: 4px;">Vui lòng kiểm tra lại từ khóa tìm kiếm hoặc điều chỉnh bộ lọc.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $index => $emp): 
                            $empId = (int)$emp['id'];
                            $isSelf = ($empId === $currentUserId);
                            $roleLower = strtolower($emp['role'] ?? '');
                            
                            // Role Badge mapping
                            $roleClass = 'role-ext';
                            $roleText  = __('emp_role_extrusion');
                            if ($roleLower === 'qc') {
                                $roleClass = 'role-qc';
                                $roleText  = __('emp_role_qc');
                            } elseif ($roleLower === 'winding') {
                                $roleClass = 'role-wind';
                                $roleText  = __('emp_role_winding');
                            } elseif ($roleLower === 'admin') {
                                $roleClass = 'role-admin';
                                $roleText  = __('emp_role_admin');
                            }

                            // Status Badge
                            $isActive = ((int)($emp['is_active'] ?? 1) === 1);
                            $isFirstLogin = ((int)($emp['is_first_login'] ?? 0) === 1);
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: #64748b;"><?= $index + 1 ?></td>
                                <td>
                                    <strong style="font-family: monospace; font-size: 14px; color: #1e293b;"><?= htmlspecialchars($emp['employee_code']) ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14px;">
                                        <?= htmlspecialchars($emp['employee_name']) ?>
                                        <?php if ($isSelf): ?>
                                            <span style="font-size: 11px; background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 600;">Bạn</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">
                                        Cập nhật: <?= !empty($emp['updated_time']) ? date('d/m/Y H:i', strtotime($emp['updated_time'])) : 'Mới tạo' ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-size: 13px; color: #475569;"><?= htmlspecialchars($emp['username']) ?></code>
                                </td>
                                <td style="text-align: center;">
                                    <span class="role-badge <?= $roleClass ?>"><?= $roleText ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($isActive): ?>
                                        <span class="status-badge status-active" data-i18n="emp_status_active">● <?= __('emp_status_active') ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive" data-i18n="emp_status_inactive">✖ <?= __('emp_status_inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($isFirstLogin): ?>
                                        <span class="pwd-badge pwd-default" title="Nhân viên chưa đổi mật khẩu riêng, đang dùng mật khẩu tạm 123" data-i18n="emp_pwd_default">
                                            ⚠️ <?= __('emp_pwd_default') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="pwd-badge pwd-custom" title="Nhân viên đã thiết lập mật khẩu an toàn riêng" data-i18n="emp_pwd_encrypted">
                                            🔒 <?= __('emp_pwd_encrypted') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="row-actions">
                                        <!-- Nút Sửa -->
                                        <button type="button" class="btn-row-edit" title="Chỉnh sửa thông tin"
                                            onclick='openEditModal(<?= json_encode($emp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            data-i18n="emp_btn_edit">
                                            ✏️ <?= __('emp_btn_edit') ?>
                                        </button>

                                        <!-- Nút Cấp lại mật khẩu -->
                                        <button type="button" class="btn-row-reset" title="Cấp lại / Đổi mật khẩu nhân viên"
                                            onclick="openResetModal(<?= $empId ?>, '<?= htmlspecialchars(addslashes($emp['employee_name'])) ?>', '<?= htmlspecialchars(addslashes($emp['employee_code'])) ?>')">
                                            🔑
                                        </button>

                                        <!-- Nút Xóa (trừ chính mình) -->
                                        <?php if (!$isSelf): ?>
                                            <button type="button" class="btn-row-del" title="Xóa nhân viên khỏi hệ thống"
                                                onclick="confirmDelete(<?= $empId ?>, '<?= htmlspecialchars(addslashes($emp['employee_name'])) ?>', '<?= htmlspecialchars(addslashes($emp['employee_code'])) ?>')">
                                                🗑️
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-row-del" disabled title="Không thể tự xóa tài khoản của chính mình" style="opacity: 0.3; cursor: not-allowed;">
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= 3. CÁC MODAL TƯƠNG TÁC ================= -->

    <!-- MODAL 1: THÊM NHÂN VIÊN MỚI -->
    <div id="modalAddEmp" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_add_title">➕ <?= __('emp_modal_add_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddEmp')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/create" id="formAddEmp">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_code"><?= __('emp_code') ?></span> <span class="required">*</span></label>
                            <input type="text" name="employee_code" id="add_employee_code" required
                                placeholder="VD: NV01, NV02..." autocomplete="off">
                            <small class="field-hint">Mã định danh duy nhất của nhân viên trong xưởng.</small>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_name"><?= __('emp_name') ?></span> <span class="required">*</span></label>
                            <input type="text" name="employee_name" id="add_employee_name" required
                                placeholder="VD: Nguyễn Văn A..." autocomplete="off">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_username"><?= __('emp_username') ?></span></label>
                            <input type="text" name="username" id="add_username"
                                placeholder="Nếu để trống sẽ tự lấy Mã nhân viên">
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_role"><?= __('emp_role') ?></span> <span class="required">*</span></label>
                            <select name="role" id="add_role" required>
                                <option value="extrusion" data-i18n="emp_role_extrusion"><?= __('emp_role_extrusion') ?></option>
                                <option value="qc" data-i18n="emp_role_qc"><?= __('emp_role_qc') ?></option>
                                <option value="winding" data-i18n="emp_role_winding"><?= __('emp_role_winding') ?></option>
                                <option value="admin" data-i18n="emp_role_admin"><?= __('emp_role_admin') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_password"><?= __('emp_password') ?> ban đầu</span></label>
                            <input type="text" name="password" id="add_password" value="123" placeholder="Mặc định: 123">
                            <small class="field-hint">Mặc định: <b>123</b>. Hệ thống sẽ tự kích hoạt yêu cầu đổi mật khẩu ở lần đăng nhập đầu tiên.</small>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_status"><?= __('emp_status') ?></span></label>
                            <select name="is_active" id="add_is_active">
                                <option value="1" data-i18n="emp_status_active">Đang làm việc</option>
                                <option value="0" data-i18n="emp_status_inactive">Đã nghỉ việc / Khóa</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalAddEmp')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="save">💾 Lưu nhân viên</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CHỈNH SỬA THÔNG TIN NHÂN VIÊN -->
    <div id="modalEditEmp" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_edit_title">✏️ <?= __('emp_modal_edit_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditEmp')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/update" id="formEditEmp">
                <input type="hidden" name="id" id="edit_id" value="">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_code"><?= __('emp_code') ?></span></label>
                            <input type="text" id="edit_employee_code" readonly
                                style="background:#f1f5f9; cursor:not-allowed; font-weight:700;">
                            <small class="field-hint">Mã nhân viên là mã định danh không thể thay đổi.</small>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_name"><?= __('emp_name') ?></span> <span class="required">*</span></label>
                            <input type="text" name="employee_name" id="edit_employee_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_username"><?= __('emp_username') ?></span> <span class="required">*</span></label>
                            <input type="text" name="username" id="edit_username" required>
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_role"><?= __('emp_role') ?></span> <span class="required">*</span></label>
                            <select name="role" id="edit_role" required>
                                <option value="extrusion" data-i18n="emp_role_extrusion"><?= __('emp_role_extrusion') ?></option>
                                <option value="qc" data-i18n="emp_role_qc"><?= __('emp_role_qc') ?></option>
                                <option value="winding" data-i18n="emp_role_winding"><?= __('emp_role_winding') ?></option>
                                <option value="admin" data-i18n="emp_role_admin"><?= __('emp_role_admin') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><span data-i18n="emp_status"><?= __('emp_status') ?></span></label>
                        <select name="is_active" id="edit_is_active">
                            <option value="1" data-i18n="emp_status_active">Đang làm việc</option>
                            <option value="0" data-i18n="emp_status_inactive">Đã nghỉ việc / Khóa</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalEditEmp')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="save">💾 Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: CẤP LẠI MẬT KHẨU (KHI QUÊN MẬT KHẨU) -->
    <div id="modalResetPwd" class="modal-backdrop">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <h3 data-i18n="emp_btn_reset_pwd">🔑 <?= __('emp_btn_reset_pwd') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalResetPwd')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/resetPassword" id="formResetPwd">
                <input type="hidden" name="id" id="reset_id" value="">
                <div class="modal-body">
                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; margin-bottom:16px;">
                        <div style="font-size:13px; color:#1e40af; font-weight:600;">Cấp lại mật khẩu cho nhân viên:</div>
                        <div style="font-size:16px; font-weight:700; color:#1e3a8a; margin-top:4px;" id="reset_emp_info">Nguyễn Văn A (NV01)</div>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu mới (Tùy chọn):</label>
                        <input type="text" name="new_password" id="reset_new_password" value="123" placeholder="Mặc định: 123" autocomplete="off">
                        <small class="field-hint">
                            Nếu để trống hoặc giữ <b>123</b>, hệ thống sẽ cấp mật khẩu tạm là <b>123</b>.<br>
                            Sau khi đăng nhập lại, nhân viên <b>bắt buộc phải đổi mật khẩu mới</b>.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalResetPwd')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit btn-warning" data-i18n="confirm">⚡ Xác nhận cấp lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: NHẬP DỮ LIỆU TỪ EXCEL / CSV -->
    <div id="modalImportExcel" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <h3 data-i18n="emp_btn_import">📥 <?= __('emp_btn_import') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalImportExcel')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/importExcel" enctype="multipart/form-data" id="formImportExcel">
                <div class="modal-body">
                    <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; padding:20px; text-align:center; margin-bottom:16px;">
                        <div style="font-size:36px; margin-bottom:8px;">📊</div>
                        <div style="font-weight:700; font-size:14.5px; color:#1e293b; margin-bottom:4px;">Chọn file Excel / CSV danh sách nhân viên</div>
                        <div style="font-size:12.5px; color:#64748b; margin-bottom:16px;">Định dạng chuẩn khuyến nghị: <b>.CSV (UTF-8)</b> xuất trực tiếp từ Microsoft Excel</div>
                        
                        <input type="file" name="excel_file" id="excel_file" accept=".csv, .txt" required
                            style="margin: 0 auto; display: block; font-size: 13px;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label style="display:flex; align-items:center; gap:8px; font-weight:600; font-size:13.5px; color:#334155; cursor:pointer;">
                            <input type="checkbox" name="update_existing" value="1" style="width:16px; height:16px;">
                            <span>Cập nhật thông tin nếu Mã nhân viên đã tồn tại trên hệ thống</span>
                        </label>
                    </div>

                    <div style="background:#fefce8; border:1px solid #fef08a; border-radius:8px; padding:12px; font-size:12.5px; color:#854d0e; line-height:1.5;">
                        <b>📌 Cấu trúc các cột trong file:</b><br>
                        - Cột 1: <b>Mã NV</b> (Bắt buộc, không trùng lặp)<br>
                        - Cột 2: <b>Họ và tên</b> (Bắt buộc)<br>
                        - Cột 3: <b>Vai trò</b> (<code>extrusion</code>, <code>qc</code>, <code>winding</code>, <code>admin</code>)<br>
                        - Cột 4: <b>Tên đăng nhập</b> (để trống sẽ tự lấy Mã NV)<br>
                        - Cột 5: <b>Trạng thái</b> (1: Đang làm việc, 0: Khóa/Nghỉ việc)<br>
                        <div style="margin-top: 8px;">
                            👉 <a href="/WEB_BOBIN/public/index.php?url=employee/downloadTemplate" style="color:#2563eb; font-weight:700; text-decoration:underline;">Tải file mẫu chuẩn tại đây (Mau_nhap_nhan_vien_SMC.csv)</a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalImportExcel')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="confirm">🚀 Bắt đầu nhập file</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FORM ẨN DÙNG CHO XÓA NHÂN VIÊN -->
    <form id="formDeleteEmp" method="POST" action="/WEB_BOBIN/public/index.php?url=employee/delete" style="display:none;">
        <input type="hidden" name="id" id="delete_id" value="">
    </form>

    <!-- 4. JAVASCRIPT ĐIỀU KHIỂN GIAO DIỆN -->
    <script>
        // Mở/Đóng Modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Đóng modal khi bấm ra ngoài hoặc nhấn ESC
        document.querySelectorAll('.modal-backdrop').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // Modal Thêm nhân viên
        function openAddModal() {
            document.getElementById('formAddEmp').reset();
            document.getElementById('add_password').value = '123';
            openModal('modalAddEmp');
            setTimeout(() => document.getElementById('add_employee_code').focus(), 150);
        }

        // Modal Sửa nhân viên
        function openEditModal(emp) {
            if (!emp) return;
            document.getElementById('edit_id').value = emp.id;
            document.getElementById('edit_employee_code').value = emp.employee_code || '';
            document.getElementById('edit_employee_name').value = emp.employee_name || '';
            document.getElementById('edit_username').value = emp.username || '';
            document.getElementById('edit_role').value = (emp.role || 'extrusion').toLowerCase();
            document.getElementById('edit_is_active').value = (emp.is_active !== undefined) ? emp.is_active : '1';
            
            openModal('modalEditEmp');
            setTimeout(() => document.getElementById('edit_employee_name').focus(), 150);
        }

        // Modal Cấp lại mật khẩu
        function openResetModal(id, name, code) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_emp_info').textContent = name + ' (' + code + ')';
            document.getElementById('reset_new_password').value = '123';
            openModal('modalResetPwd');
        }

        // Modal Nhập Excel
        function openImportModal() {
            document.getElementById('formImportExcel').reset();
            openModal('modalImportExcel');
        }

        // Xác nhận xóa nhân viên
        function confirmDelete(id, name, code) {
            const confirmMsg = `Bạn có chắc chắn muốn xóa nhân viên "${name}" (${code}) khỏi hệ thống không?\n\nLưu ý: Thao tác này không thể hoàn tác!`;
            if (confirm(confirmMsg)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formDeleteEmp').submit();
            }
        }

        // Tự động điền Username khi gõ Employee Code trong form thêm mới
        const addCodeInput = document.getElementById('add_employee_code');
        const addUserInput = document.getElementById('add_username');
        if (addCodeInput && addUserInput) {
            addCodeInput.addEventListener('input', function() {
                if (addUserInput.dataset.userEdited !== 'true') {
                    addUserInput.value = this.value.trim().toUpperCase();
                }
            });
            addUserInput.addEventListener('input', function() {
                this.dataset.userEdited = (this.value.trim() !== '') ? 'true' : 'false';
            });
        }
    </script>
</body>
</html>
