<?php
// File: app/views/components/sidebar.php
// Component Sidebar Dọc Bên Trái Hiện Đại cho Hệ Thống WEB_BOBIN

require_once ROOT_PATH . '/app/core/AuthHelper.php';
require_once ROOT_PATH . '/app/core/Language.php';

$user         = $_SESSION['user'] ?? [];
$userRole     = strtolower($user['role'] ?? '');
$userName     = $user['employee_name'] ?? 'Người dùng';
$empCode      = $user['employee_code'] ?? '';
$currentUrl   = $_GET['url'] ?? '';
$pCount       = $pendingCount ?? ($pCount ?? (GlobalData::$pendingBobinCount ?? 0));
$currentLang  = Language::getCurrent();

// Kiểm tra quyền hạn chi tiết thông qua AuthHelper
$canExtrusionCreate  = AuthHelper::hasPermission('extrusion_create', $user);
$canExtrusionEdit    = AuthHelper::hasPermission('extrusion_edit', $user);
$canQcCheck          = AuthHelper::hasPermission('qc_check', $user);
$canQcEdit           = AuthHelper::hasPermission('qc_edit', $user);
$canWindingConfirm   = AuthHelper::hasPermission('winding_confirm', $user);
$canWindingEdit      = AuthHelper::hasPermission('winding_edit', $user);
$canBobinList        = AuthHelper::hasPermission('bobin_list', $user);
$canBobinHistory     = AuthHelper::hasPermission('bobin_history', $user);
$canPendingCancel    = AuthHelper::hasPermission('pending_cancel', $user);
$canEmployeeManage   = AuthHelper::hasPermission('employee_manage', $user);
$canPermissionManage = AuthHelper::hasPermission('permission_manage', $user);

// Tạo link giữ lại query params khi đổi ngôn ngữ
$currentQuery = $_GET;
unset($currentQuery['lang']);
$baseLangQuery = '/WEB_BOBIN/public/index.php?' . http_build_query($currentQuery);
$langPrefix = !empty($currentQuery) ? '&lang=' : 'lang=';

// Lấy chữ cái đầu làm avatar
$firstLetter = mb_substr(trim($userName), 0, 1, 'UTF-8');
?>

<!-- Mobile Topbar -->
<div class="sb-mobile-topbar">
    <button type="button" class="sb-mobile-toggle-btn" id="sbMobileToggle" aria-label="Toggle Navigation">
        ☰
    </button>
    <div class="sb-mobile-title">
        <span>🏭</span>
        <span>WEB_BOBIN</span>
    </div>
    <div style="font-size:12px; color:#38bdf8; font-weight:700;">
        <?= strtoupper($userRole ?: 'GUEST') ?>
    </div>
</div>

<!-- Mobile Backdrop Overlay -->
<div class="sb-overlay" id="sbOverlay"></div>

<!-- Left Sidebar Navigation -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Header -->
    <div class="sb-brand">
        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView" class="sb-brand-main">
            <div class="sb-brand-logo">🏭</div>
            <div class="sb-brand-info">
                <span class="sb-brand-title">WEB_BOBIN</span>
                <span class="sb-brand-sub">SMC Factory Pro</span>
            </div>
        </a>
        <button type="button" class="sb-toggle-btn" id="sbDesktopToggle" title="<?= __('sidebar_toggle') ?>" aria-label="<?= __('sidebar_toggle') ?>">
            ◀
        </button>
    </div>

    <!-- User Profile Card -->
    <?php if (!empty($user)): ?>
    <div class="sb-user-card">
        <div class="sb-user-avatar role-<?= htmlspecialchars($userRole) ?>" title="<?= htmlspecialchars($userName) ?>">
            <?= htmlspecialchars($firstLetter) ?>
        </div>
        <div class="sb-user-info">
            <span class="sb-user-name" title="<?= htmlspecialchars($userName) ?>">
                <?= htmlspecialchars($userName) ?>
            </span>
            <span class="sb-user-role-badge">
                <?= htmlspecialchars($empCode) ?> • <?= strtoupper($userRole) ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation List (Scrollable) -->
    <nav class="sb-nav-scroll">
        <!-- 1. NHÓM ĐÙN (EXTRUSION) -->
        <?php if ($canExtrusionCreate || $canExtrusionEdit): ?>
        <div class="sb-group">
            <div class="sb-group-title" data-i18n="sidebar_nav_title_production"><?= __('sidebar_nav_title_production') ?></div>
            <?php if ($canExtrusionCreate): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/index"
               class="sb-link <?= in_array($currentUrl, ['bobin/index', 'bobin/extrusion', '']) ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">⚙️</span>
                <span class="sb-link-text" data-i18n="nav_extrusion"><?= __('nav_extrusion') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canExtrusionEdit): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView"
               class="sb-link <?= ($currentUrl === 'bobin/extrusionEditBobinView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">✏️</span>
                <span class="sb-link-text" data-i18n="nav_extrusion_edit"><?= __('nav_extrusion_edit') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 2. NHÓM QC (QUALITY CONTROL) -->
        <?php if ($canQcCheck || $canQcEdit): ?>
        <div class="sb-group">
            <div class="sb-group-title" data-i18n="sidebar_nav_title_qc"><?= __('sidebar_nav_title_qc') ?></div>
            <?php if ($canQcCheck): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC"
               class="sb-link <?= ($currentUrl === 'bobin/listBobinView_QC') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">🛡️</span>
                <span class="sb-link-text" data-i18n="nav_qc"><?= __('nav_qc') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canQcEdit): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/qcEditBobinView"
               class="sb-link <?= ($currentUrl === 'bobin/qcEditBobinView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">🔍</span>
                <span class="sb-link-text" data-i18n="nav_qc_edit"><?= __('nav_qc_edit') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 3. NHÓM CUỘN (WINDING) -->
        <?php if ($canWindingConfirm || $canWindingEdit): ?>
        <div class="sb-group">
            <div class="sb-group-title" data-i18n="sidebar_nav_title_winding"><?= __('sidebar_nav_title_winding') ?></div>
            <?php if ($canWindingConfirm): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView"
               class="sb-link <?= in_array($currentUrl, ['bobin/windingView', 'bobin/listBobinView_Winding']) ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">📍</span>
                <span class="sb-link-text" data-i18n="nav_winding"><?= __('nav_winding') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canWindingEdit): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/windingEditBobinView"
               class="sb-link <?= ($currentUrl === 'bobin/windingEditBobinView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">⚡</span>
                <span class="sb-link-text" data-i18n="nav_winding_edit"><?= __('nav_winding_edit') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 4. GIÁM SÁT & BÁO CÁO (MONITORING) -->
        <div class="sb-group">
            <div class="sb-group-title" data-i18n="sidebar_nav_title_monitor"><?= __('sidebar_nav_title_monitor') ?></div>
            <?php if ($canBobinList): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView"
               class="sb-link <?= ($currentUrl === 'bobin/listBobinDetailView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">📦</span>
                <span class="sb-link-text" data-i18n="nav_bobin_list"><?= __('nav_bobin_list') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canBobinHistory): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
               class="sb-link <?= ($currentUrl === 'bobin/listBobinHistoryView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">🕒</span>
                <span class="sb-link-text" data-i18n="nav_bobin_history"><?= __('nav_bobin_history') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canPendingCancel): ?>
            <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView"
               class="sb-link <?= ($currentUrl === 'bobin/listPendingCancellationView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">⏳</span>
                <span class="sb-link-text" data-i18n="nav_pending_cancel"><?= __('nav_pending_cancel') ?></span>
                <?php if ($pCount > 0): ?>
                <span class="sb-badge-pending"><?= $pCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- 5. QUẢN TRỊ HỆ THỐNG (SYSTEM) -->
        <?php if ($canEmployeeManage || $canPermissionManage): ?>
        <div class="sb-group">
            <div class="sb-group-title" data-i18n="sidebar_nav_title_system"><?= __('sidebar_nav_title_system') ?></div>
            <?php if ($canEmployeeManage): ?>
            <a href="/WEB_BOBIN/public/index.php?url=employee/index"
               class="sb-link <?= (strpos($currentUrl, 'employee/index') === 0 || $currentUrl === 'employee') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">👥</span>
                <span class="sb-link-text" data-i18n="nav_employee_list"><?= __('nav_employee_list') ?></span>
            </a>
            <?php endif; ?>

            <?php if ($canPermissionManage): ?>
            <a href="/WEB_BOBIN/public/index.php?url=employee/permissionsView"
               class="sb-link <?= ($currentUrl === 'employee/permissionsView') ? 'active-nav' : '' ?>">
                <span class="sb-link-icon">🔐</span>
                <span class="sb-link-text" data-i18n="nav_permissions"><?= __('nav_permissions') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sb-footer">
        <!-- Language Switcher Bar -->
        <div class="sb-lang-bar">
            <a href="<?= $baseLangQuery . $langPrefix ?>vi" class="sb-lang-btn <?= $currentLang === 'vi' ? 'active-lang' : '' ?>" title="Tiếng Việt">
                🇻🇳 <span>VI</span>
            </a>
            <a href="<?= $baseLangQuery . $langPrefix ?>en" class="sb-lang-btn <?= $currentLang === 'en' ? 'active-lang' : '' ?>" title="English">
                🇬🇧 <span>EN</span>
            </a>
            <a href="<?= $baseLangQuery . $langPrefix ?>ja" class="sb-lang-btn <?= $currentLang === 'ja' ? 'active-lang' : '' ?>" title="日本語">
                🇯🇵 <span>JA</span>
            </a>
        </div>

        <!-- Footer Action Buttons -->
        <?php if (!empty($user)): ?>
        <div class="sb-actions-row">
            <a href="/WEB_BOBIN/public/index.php?url=auth/changePassword" class="sb-action-btn" title="<?= __('nav_change_pwd') ?>">
                <span class="sb-action-icon">🔑</span>
                <span class="sb-action-text" data-i18n="nav_change_pwd"><?= __('nav_change_pwd') ?></span>
            </a>
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="sb-action-btn btn-logout" title="<?= __('nav_logout') ?>">
                <span class="sb-action-icon">🚪</span>
                <span class="sb-action-text" data-i18n="nav_logout"><?= __('nav_logout') ?></span>
            </a>
        </div>
        <?php else: ?>
        <a href="/WEB_BOBIN/public/index.php?url=auth/login" class="sb-action-btn" style="background:#2563eb; color:#fff;" title="<?= __('nav_login') ?>">
            <span class="sb-action-icon">👤</span>
            <span class="sb-action-text" data-i18n="nav_login"><?= __('nav_login') ?></span>
        </a>
        <?php endif; ?>
    </div>
</aside>

<script>
// Sidebar Toggle Controller
(function() {
    const body = document.body;
    const desktopToggle = document.getElementById('sbDesktopToggle');
    const mobileToggle = document.getElementById('sbMobileToggle');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sbOverlay');

    // Khôi phục trạng thái thu gọn từ localStorage
    if (localStorage.getItem('webbobin_sidebar_collapsed') === '1') {
        body.classList.add('sidebar-collapsed');
        if (desktopToggle) desktopToggle.textContent = '▶';
    }

    if (desktopToggle) {
        desktopToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');
            const isCollapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('webbobin_sidebar_collapsed', isCollapsed ? '1' : '0');
            desktopToggle.textContent = isCollapsed ? '▶' : '◀';
        });
    }

    // Mobile Toggle
    if (mobileToggle && sidebar && overlay) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('show');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
        });
    }
})();
</script>

