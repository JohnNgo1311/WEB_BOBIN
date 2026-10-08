<?php
$employees   = $data['employees'] ?? [];
$stats       = $data['stats'] ?? ['total' => 0, 'extrusion' => 0, 'qc' => 0, 'winding' => 0, 'admin' => 0];
$filters     = $data['filters'] ?? ['keyword' => '', 'cost_center' => 'all'];
$costCenters = $data['costCenters'] ?? [];
$userRole    = $_SESSION['user']['role'] ?? '';
$currentUrl  = $_GET['url'] ?? 'employee/index';
$currentUserId = (int)($_SESSION['user']['id'] ?? 0);
$pCount      = $data['pendingCount'] ?? ($pendingCount ?? (GlobalData::$pendingBobinCount ?? 0));

$msgType = $_GET['msg_type'] ?? '';
$msg = $_GET['msg'] ?? '';
$totalFiltered = count($employees);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= __('page_employee_list') ?? 'Quản lý nhân viên' ?> | SMC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/employeeList.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
</head>

<body>
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <div class="emp-container">
        <?php if (!empty($msg)): ?>
            <div class="msg-banner <?= $msgType === 'success' ? 'success' : 'error' ?>" id="msgBanner">
                <span><?= $msgType === 'success' ? '✅ ' : '⚠️ ' ?><?= htmlspecialchars($msg) ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">✕</button>
            </div>
        <?php endif; ?>

        <div class="emp-page-header">
            <div>
                <h1 class="emp-title" data-i18n="emp_manage_title"><?= __('emp_manage_title') ?></h1>
                <p class="emp-subtitle" data-i18n="emp_manage_subtitle"><?= __('emp_manage_subtitle') ?></p>
            </div>
        </div>

        <div class="stats-and-chart-container">
            <div class="stat-grid">
                <div class="stat-card stat-total" onclick="filterByRole('all')">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$stats['total'] ?></div>
                        <div class="stat-label" data-i18n="emp_total_stat"><?= __('emp_total_stat') ?></div>
                    </div>
                </div>
                <div class="stat-card stat-ext" onclick="filterByRole('extrusion')">
                    <div class="stat-icon">⚙️</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$stats['extrusion'] ?></div>
                        <div class="stat-label" data-i18n="emp_ext_stat"><?= __('emp_ext_stat') ?></div>
                    </div>
                </div>
                <div class="stat-card stat-qc" onclick="filterByRole('qc')">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$stats['qc'] ?></div>
                        <div class="stat-label" data-i18n="emp_qc_stat"><?= __('emp_qc_stat') ?></div>
                    </div>
                </div>
                <div class="stat-card stat-wind" onclick="filterByRole('winding')">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$stats['winding'] ?></div>
                        <div class="stat-label" data-i18n="emp_wind_stat"><?= __('emp_wind_stat') ?></div>
                    </div>
                </div>
                <div class="stat-card stat-admin" onclick="filterByRole('admin')">
                    <div class="stat-icon">🛡️</div>
                    <div class="stat-info">
                        <div class="stat-number"><?= (int)$stats['admin'] ?></div>
                        <div class="stat-label" data-i18n="emp_admin_stat"><?= __('emp_admin_stat') ?></div>
                    </div>
                </div>
            </div>

            <div class="emp-chart-section">
                <div class="chart-donut-wrapper">
                    <svg viewBox="0 0 100 100" id="donutChartSvg"></svg>
                    <div class="donut-center-text">
                        <div class="total-val"><?= (int)$stats['total'] ?></div>
                        <div class="total-lbl" data-i18n="emp_total_stat"><?= __('emp_total_stat') ?></div>
                    </div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item" onclick="filterByRole('extrusion')" style="cursor:pointer;">
                        <div style="display:flex;align-items:center;">
                            <div class="legend-color" style="background:#3b82f6;"></div>
                            <span data-i18n="emp_role_extrusion"><?= __('emp_role_extrusion') ?></span>
                        </div>
                        <span><?= (int)$stats['extrusion'] ?></span>
                    </div>
                    <div class="legend-item" onclick="filterByRole('qc')" style="cursor:pointer;">
                        <div style="display:flex;align-items:center;">
                            <div class="legend-color" style="background:#10b981;"></div>
                            <span data-i18n="emp_role_qc"><?= __('emp_role_qc') ?></span>
                        </div>
                        <span><?= (int)$stats['qc'] ?></span>
                    </div>
                    <div class="legend-item" onclick="filterByRole('winding')" style="cursor:pointer;">
                        <div style="display:flex;align-items:center;">
                            <div class="legend-color" style="background:#f59e0b;"></div>
                            <span data-i18n="emp_role_winding"><?= __('emp_role_winding') ?></span>
                        </div>
                        <span><?= (int)$stats['winding'] ?></span>
                    </div>
                    <div class="legend-item" onclick="filterByRole('admin')" style="cursor:pointer;">
                        <div style="display:flex;align-items:center;">
                            <div class="legend-color" style="background:#8b5cf6;"></div>
                            <span data-i18n="emp_role_admin"><?= __('emp_role_admin') ?></span>
                        </div>
                        <span><?= (int)$stats['admin'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="emp-toolbar">
            <form method="GET" action="/WEB_BOBIN/public/index.php" class="filter-form" id="filterForm">
                <input type="hidden" name="url" value="employee/index">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="keyword" id="searchInput"
                        value="<?= htmlspecialchars($filters['keyword']) ?>"
                        placeholder="<?= __('emp_search_ph') ?>"
                        data-placeholder-i18n="emp_search_ph" autocomplete="off">
                </div>
                <div class="filter-group">
                    <label data-i18n="emp_cost_center"><?= __('emp_cost_center') ?>:</label>
                    <select name="cost_center" id="filterCostCenter" onchange="this.form.submit()">
                        <option value="all" <?= (($filters['cost_center'] ?? 'all') === 'all') ? 'selected' : '' ?> data-i18n="emp_all_cost_centers">
                            <?= __('emp_all_cost_centers') ?>
                        </option>
                        <?php foreach ($costCenters as $cc): ?>
                            <option value="<?= htmlspecialchars($cc) ?>" <?= (($filters['cost_center'] ?? '') === $cc) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter" data-i18n="filter">🔍 <?= __('filter') ?></button>
                <?php if ($filters['keyword'] !== '' || ($filters['cost_center'] ?? 'all') !== 'all'): ?>
                    <a href="/WEB_BOBIN/public/index.php?url=employee/index" class="btn-reset-filter"
                        data-i18n="reset_filter">✕ <?= __('reset_filter') ?></a>
                <?php endif; ?>
                <div class="emp-counter-badge" id="empCounter">
                    Hiển thị: <strong><?= $totalFiltered ?></strong> / <?= (int)$stats['total'] ?> NV
                </div>
            </form>

            <div class="btn-action-group">
                <button type="button" class="btn-action-primary" onclick="openAddModal()" data-i18n="emp_btn_add">➕ <?= __('emp_btn_add') ?></button>
                <button type="button" class="btn-action-success" onclick="openImportModal()" data-i18n="emp_btn_import">📥 <?= __('emp_btn_import') ?></button>
                <a href="/WEB_BOBIN/public/index.php?url=employee/exportExcel" class="btn-action-info" data-i18n="emp_btn_export">📤 <?= __('emp_btn_export') ?></a>
                <a href="/WEB_BOBIN/public/index.php?url=employee/downloadTemplate" class="btn-action-secondary" data-i18n="emp_btn_template">📋 <?= __('emp_btn_template') ?></a>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="emp-table" id="employeeTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;" data-i18n="stt"><?= __('stt') ?></th>
                        <th style="width: 140px;" data-i18n="emp_code"><?= __('emp_code') ?> / <?= __('emp_username') ?></th>
                        <th style="min-width: 180px;" data-i18n="emp_name"><?= __('emp_name') ?></th>
                        <th style="width: 130px; text-align: center;" data-i18n="emp_cost_center"><?= __('emp_cost_center') ?></th>
                        <th style="width: 130px; text-align: center;" data-i18n="emp_role"><?= __('emp_role') ?></th>
                        <th style="width: 150px; text-align: center;" data-i18n="emp_password"><?= __('emp_password') ?></th>
                        <th style="width: 180px; text-align: center;" data-i18n="actions"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <?php if (empty($employees)): ?>
                        <tr class="empty-row">
                            <td colspan="7" class="text-center empty-state" style="padding: 40px; color: #94a3b8;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                                <div style="font-weight: 600; font-size: 15px;" data-i18n="no_data_found"><?= __('no_data_found') ?? 'Không tìm thấy nhân viên nào phù hợp.' ?></div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $index => $emp):
                            $empId = (int)$emp['id'];
                            $isSelf = ($empId === $currentUserId);
                            $roleLower = strtolower($emp['role'] ?? '');
                            $roleClass = 'role-ext';
                            $roleText = __('emp_role_extrusion');
                            if ($roleLower === 'qc') {
                                $roleClass = 'role-qc';
                                $roleText = __('emp_role_qc');
                            } elseif ($roleLower === 'winding') {
                                $roleClass = 'role-wind';
                                $roleText = __('emp_role_winding');
                            } elseif ($roleLower === 'admin') {
                                $roleClass = 'role-admin';
                                $roleText = __('emp_role_admin');
                            }
                            $isFirstLogin = ((int)($emp['is_first_login'] ?? 0) === 1);
                        ?>
                            <tr class="emp-row"
                                data-code="<?= htmlspecialchars(strtolower($emp['employee_code'])) ?>"
                                data-name="<?= htmlspecialchars(mb_strtolower($emp['employee_name'], 'UTF-8')) ?>"
                                data-cost-center="<?= htmlspecialchars(strtolower($emp['cost_center'] ?? '')) ?>"
                                data-role="<?= htmlspecialchars($roleLower) ?>">
                                <td style="text-align: center; font-weight: 600; color: #64748b;" class="col-stt">
                                    <?= $index + 1 ?>
                                </td>
                                <td>
                                    <div class="code-pill-wrap">
                                        <strong class="code-mono"><?= htmlspecialchars($emp['employee_code']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="emp-user-card">
                                        <div class="emp-avatar-initial">
                                            <?= mb_substr(trim($emp['employee_name']), 0, 1, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <div class="emp-name-text">
                                                <?= htmlspecialchars($emp['employee_name']) ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge-self" data-i18n="emp_self_badge"><?= __('emp_self_badge') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="emp-sub-time">
                                                <span data-i18n="emp_updated_prefix"><?= __('emp_updated_prefix') ?></span>
                                                <?= !empty($emp['updated_time']) ? date('d/m/Y H:i', strtotime($emp['updated_time'])) : __('emp_new_badge') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($emp['cost_center'])): ?>
                                        <span class="badge-cost-center"><?= htmlspecialchars($emp['cost_center']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="role-badge <?= $roleClass ?>" data-i18n="emp_role_<?= $roleLower ?>"><?= $roleText ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($isFirstLogin): ?>
                                        <span class="pwd-badge pwd-default" data-i18n="emp_pwd_default">⚠️ <?= __('emp_pwd_default') ?></span>
                                    <?php else: ?>
                                        <span class="pwd-badge pwd-custom" data-i18n="emp_pwd_encrypted">🔒 <?= __('emp_pwd_encrypted') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="row-actions">
                                        <button type="button" class="btn-row-edit"
                                            onclick='openEditModal(<?= json_encode($emp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            data-i18n="emp_btn_edit">✏️ <?= __('emp_btn_edit') ?></button>
                                        <button type="button" class="btn-row-reset"
                                            onclick="openResetModal(<?= $empId ?>, '<?= htmlspecialchars(addslashes($emp['employee_name'])) ?>', '<?= htmlspecialchars(addslashes($emp['employee_code'])) ?>')">🔑
                                            <?= __('emp_btn_reset_pwd') ?></button>
                                        <?php if (!$isSelf): ?>
                                            <button type="button" class="btn-row-del"
                                                onclick="confirmDelete(<?= $empId ?>, '<?= htmlspecialchars(addslashes($emp['employee_name'])) ?>', '<?= htmlspecialchars(addslashes($emp['employee_code'])) ?>')">🗑️</button>
                                        <?php else: ?>
                                            <button type="button" class="btn-row-del" disabled
                                                style="opacity: 0.3; cursor: not-allowed;">🗑️</button>
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

    <!-- Modals -->
    <div id="modalAddEmp" class="modal-backdrop">
        <div class="modal-box modal-md">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_add_title">➕ <?= __('emp_modal_add_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddEmp')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/create" id="formAddEmp">
                <div class="modal-body">
                    <div class="auto-notice-box">
                        <div class="notice-icon">💡</div>
                        <div class="notice-content">
                            <strong data-i18n="emp_notice_title"><?= __('emp_notice_title') ?>:</strong><br>
                            - <span data-i18n="emp_notice_username"><?= __('emp_notice_username') ?></span>.<br>
                            - <span data-i18n="emp_notice_password"><?= __('emp_notice_password') ?></span>.
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_code"><?= __('emp_code') ?></span> <span class="required">*</span></label>
                            <input type="text" name="employee_code" id="add_employee_code" required autocomplete="off"
                                placeholder="<?= __('emp_code_ph') ?>" data-placeholder-i18n="emp_code_ph">
                        </div>
                        <div class="form-group">
                            <label><span data-i18n="emp_name"><?= __('emp_name') ?></span> <span class="required">*</span></label>
                            <input type="text" name="employee_name" id="add_employee_name" required autocomplete="off"
                                placeholder="<?= __('emp_name_ph') ?>" data-placeholder-i18n="emp_name_ph">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_cost_center"><?= __('emp_cost_center') ?></span></label>
                            <input type="text" name="cost_center" id="add_cost_center" autocomplete="off"
                                placeholder="<?= __('emp_cost_center_ph') ?>" data-placeholder-i18n="emp_cost_center_ph">
                            <small class="field-hint" data-i18n="emp_cost_center_hint" style="color:#64748b; font-size:11.5px; margin-top:4px; display:block;">
                                <?= __('emp_cost_center_hint') ?>
                            </small>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalAddEmp')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="save">💾 <?= __('save') ?? 'Lưu nhân viên' ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditEmp" class="modal-backdrop">
        <div class="modal-box modal-md">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_edit_title">✏️ <?= __('emp_modal_edit_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditEmp')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/update" id="formEditEmp">
                <input type="hidden" name="id" id="edit_id" value="">
                <div class="modal-body">
                    <div class="emp-profile-header">
                        <div class="profile-label" data-i18n="emp_profile_label"><?= __('emp_profile_label') ?></div>
                        <div class="profile-value" id="edit_code_badge"></div>
                    </div>
                    <div class="form-group">
                        <label><span data-i18n="emp_name"><?= __('emp_name') ?></span> <span class="required">*</span></label>
                        <input type="text" name="employee_name" id="edit_employee_name" required autocomplete="off">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><span data-i18n="emp_cost_center"><?= __('emp_cost_center') ?></span></label>
                            <input type="text" name="cost_center" id="edit_cost_center" autocomplete="off"
                                placeholder="<?= __('emp_cost_center_ph') ?>" data-placeholder-i18n="emp_cost_center_ph">
                            <small class="field-hint" data-i18n="emp_cost_center_hint" style="color:#64748b; font-size:11.5px; margin-top:4px; display:block;">
                                <?= __('emp_cost_center_hint') ?>
                            </small>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalEditEmp')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="save">💾 <?= __('save') ?? 'Cập nhật' ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalResetPwd" class="modal-backdrop">
        <div class="modal-box modal-sm">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_reset_title">🔑 <?= __('emp_modal_reset_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalResetPwd')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/resetPassword" id="formResetPwd">
                <input type="hidden" name="id" id="reset_id" value="">
                <div class="modal-body">
                    <div class="reset-target-card">
                        <div class="target-name" id="reset_emp_info"></div>
                    </div>
                    <div class="reset-desc-box" data-i18n="emp_reset_desc_body"><?= __('emp_reset_desc_body') ?></div>
                    <div style="margin-top: 12px;">
                        <input type="text" name="new_password" id="reset_new_password"
                            placeholder="<?= __('emp_reset_custom_ph') ?>"
                            data-placeholder-i18n="emp_reset_custom_ph"
                            style="width: 100%; height: 36px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalResetPwd')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit btn-warning" data-i18n="emp_reset_btn">⚡ <?= __('emp_reset_btn') ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalImportExcel" class="modal-backdrop">
        <div class="modal-box modal-md">
            <div class="modal-header">
                <h3 data-i18n="emp_modal_import_title">📥 <?= __('emp_modal_import_title') ?></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalImportExcel')">✕</button>
            </div>
            <form method="POST" action="/WEB_BOBIN/public/index.php?url=employee/importExcel"
                enctype="multipart/form-data" id="formImportExcel">
                <div class="modal-body">
                    <div class="import-hint-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px; font-size:12.5px; line-height:1.6; color:#475569;">
                        <strong data-i18n="emp_import_col_title"><?= __('emp_import_col_title') ?></strong><br>
                        - <span data-i18n="emp_import_col1"><?= __('emp_import_col1') ?></span><br>
                        - <span data-i18n="emp_import_col2"><?= __('emp_import_col2') ?></span><br>
                        - <span data-i18n="emp_import_col3"><?= __('emp_import_col3') ?></span><br>
                        - <span data-i18n="emp_import_col4"><?= __('emp_import_col4') ?></span><br>
                        - <span data-i18n="emp_import_col5"><?= __('emp_import_col5') ?></span>
                    </div>
                    <input type="file" name="excel_file" id="excel_file" accept=".csv, .txt" required
                        style="display: block; margin-bottom: 12px; width:100%;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox" name="update_existing" value="1">
                        <span data-i18n="emp_import_update"><?= __('emp_import_update') ?></span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('modalImportExcel')" data-i18n="cancel"><?= __('cancel') ?></button>
                    <button type="submit" class="btn-submit" data-i18n="emp_import_start">🚀 <?= __('emp_import_start') ?></button>
                </div>
            </form>
        </div>
    </div>

    <form id="formDeleteEmp" method="POST" action="/WEB_BOBIN/public/index.php?url=employee/delete" style="display:none;">
        <input type="hidden" name="id" id="delete_id" value="">
    </form>

    <script>
        // Auto-dismiss message banner after 4 seconds
        setTimeout(() => {
            const msgBanner = document.getElementById('msgBanner');
            if (msgBanner) {
                msgBanner.style.opacity = '0';
                setTimeout(() => msgBanner.remove(), 300);
            }
        }, 4000);

        // Filter by role helper (client-side interactive filter)
        function filterByRole(role) {
            let vis = 0, stt = 1;
            const empRows = document.querySelectorAll('#employeeTableBody .emp-row');
            const totalBase = <?= (int)$stats['total'] ?>;
            const empCounter = document.getElementById('empCounter');

            empRows.forEach(r => {
                const rRole = r.getAttribute('data-role') || '';
                if (role === 'all' || rRole === role) {
                    r.style.display = '';
                    vis++;
                    const sttCell = r.querySelector('.col-stt');
                    if (sttCell) sttCell.textContent = stt++;
                } else {
                    r.style.display = 'none';
                }
            });
            if (empCounter) {
                const prefix = window.t ? window.t('showing') : 'Hiển thị:';
                empCounter.innerHTML = `${prefix} <strong>${vis}</strong> / ${totalBase} NV`;
            }
        }

        // Modal helpers
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function openAddModal() {
            document.getElementById('formAddEmp').reset();
            openModal('modalAddEmp');
        }

        function openImportModal() {
            document.getElementById('formImportExcel').reset();
            openModal('modalImportExcel');
        }

        function openEditModal(emp) {
            document.getElementById('edit_id').value = emp.id;
            document.getElementById('edit_code_badge').textContent = emp.employee_code || '';
            document.getElementById('edit_employee_name').value = emp.employee_name || '';
            document.getElementById('edit_cost_center').value = emp.cost_center || '';
            document.getElementById('edit_role').value = (emp.role || 'extrusion').toLowerCase();
            openModal('modalEditEmp');
        }

        function openResetModal(id, name, code) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_emp_info').textContent = name + ' (' + code + ')';
            document.getElementById('reset_new_password').value = '';
            openModal('modalResetPwd');
        }

        function confirmDelete(id, name, code) {
            const msg = (window.t ? window.t('emp_delete_confirm_full') : "Bạn có chắc chắn muốn xóa nhân viên khỏi hệ thống không? Thao tác này không thể hoàn tác!") +
                "\n" + name + " (" + code + ")";
            if (confirm(msg)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('formDeleteEmp').submit();
            }
        }

        const addCode = document.getElementById('add_employee_code');
        if (addCode) {
            addCode.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/\s+/g, '');
            });
        }

        const addCostCenter = document.getElementById('add_cost_center');
        if (addCostCenter) {
            addCostCenter.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/\s+/g, '');
            });
        }

        const editCostCenter = document.getElementById('edit_cost_center');
        if (editCostCenter) {
            editCostCenter.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/\s+/g, '');
            });
        }

        const searchInput = document.getElementById('searchInput');
        const empRows = document.querySelectorAll('#employeeTableBody .emp-row');
        const empCounter = document.getElementById('empCounter');
        const totalBase = <?= (int)$stats['total'] ?>;

        if (searchInput && empRows.length > 0) {
            searchInput.addEventListener('input', function() {
                const q = this.value.trim().toLowerCase();
                let vis = 0, stt = 1;
                empRows.forEach(r => {
                    const c = r.getAttribute('data-code') || '';
                    const n = r.getAttribute('data-name') || '';
                    const cc = r.getAttribute('data-cost-center') || '';
                    if (q === '' || c.includes(q) || n.includes(q) || cc.includes(q)) {
                        r.style.display = '';
                        vis++;
                        const sttCell = r.querySelector('.col-stt');
                        if (sttCell) sttCell.textContent = stt++;
                    } else {
                        r.style.display = 'none';
                    }
                });
                if (empCounter) {
                    const prefix = window.t ? window.t('showing') : 'Hiển thị:';
                    empCounter.innerHTML = `${prefix} <strong>${vis}</strong> / ${totalBase} NV`;
                }
            });
        }

        // Donut Chart logic
        document.addEventListener('DOMContentLoaded', () => {
            const total = <?= (int)$stats['total'] ?>;
            if (total === 0) return;

            const data = [
                { v: <?= (int)$stats['extrusion'] ?>, c: '#3b82f6' },
                { v: <?= (int)$stats['qc'] ?>, c: '#10b981' },
                { v: <?= (int)$stats['winding'] ?>, c: '#f59e0b' },
                { v: <?= (int)$stats['admin'] ?>, c: '#8b5cf6' }
            ];

            const svg = document.getElementById('donutChartSvg');
            if (svg) {
                let cum = 0;
                const r = 40;
                const circum = 2 * Math.PI * r;
                data.forEach(item => {
                    if (item.v === 0) return;
                    const pct = item.v / total;
                    const dash = pct * circum;
                    const gap = circum - dash;

                    const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    circle.setAttribute("cx", "50");
                    circle.setAttribute("cy", "50");
                    circle.setAttribute("r", r);
                    circle.setAttribute("stroke", item.c);
                    circle.setAttribute("fill", "transparent");
                    circle.setAttribute("stroke-width", "14");
                    circle.setAttribute("stroke-dasharray", `${dash} ${gap}`);
                    circle.setAttribute("stroke-dashoffset", -(cum / total) * circum);
                    svg.appendChild(circle);

                    cum += item.v;
                });
            }
        });
    </script>
</body>

</html>