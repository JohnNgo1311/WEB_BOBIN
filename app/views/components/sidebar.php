<?php
// File: app/views/components/sidebar.php
// Component Sidebar Dọc Cây Thư Mục (Folder Tree Navigation) Hiện Đại cho WEB_BOBIN (TASK-014)

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

// Xác định trang chủ hợp lệ của người dùng dựa theo quyền thực tế
$userHomeUrl = match (true) {
    $canExtrusionCreate  => '/WEB_BOBIN/public/index.php?url=bobin/index',
    $canQcCheck          => '/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC',
    $canWindingConfirm   => '/WEB_BOBIN/public/index.php?url=bobin/windingView',
    $canBobinList        => '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView',
    $canBobinHistory     => '/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView',
    $canPendingCancel    => '/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView',
    $canEmployeeManage   => '/WEB_BOBIN/public/index.php?url=employee/index',
    $canPermissionManage => '/WEB_BOBIN/public/index.php?url=employee/permissionsView',
    default              => '/WEB_BOBIN/public/index.php?url=auth/login'
};

// Xác định thư mục nhóm nào đang active dựa trên currentUrl
$isExtrusionActive  = in_array($currentUrl, ['bobin/index', 'bobin/extrusion', 'bobin/extrusionEditBobinView', '']);
$isQcActive         = in_array($currentUrl, ['bobin/listBobinView_QC', 'bobin/qcEditBobinView']);
$isWindingActive    = in_array($currentUrl, ['bobin/windingView', 'bobin/listBobinView_Winding', 'bobin/windingEditBobinView']);
$isMonitorActive    = in_array($currentUrl, ['bobin/listBobinDetailView', 'bobin/listBobinHistoryView', 'bobin/listPendingCancellationView']);
$isSystemActive     = (strpos($currentUrl, 'employee') === 0);

// Đếm số mục con hợp lệ của từng thư mục
$extCount  = ($canExtrusionCreate ? 1 : 0) + ($canExtrusionEdit ? 1 : 0);
$qcCount   = ($canQcCheck ? 1 : 0) + ($canQcEdit ? 1 : 0);
$wndCount  = ($canWindingConfirm ? 1 : 0) + ($canWindingEdit ? 1 : 0);
$monCount  = ($canBobinList ? 1 : 0) + ($canBobinHistory ? 1 : 0) + ($canPendingCancel ? 1 : 0);
$sysCount  = ($canEmployeeManage ? 1 : 0) + ($canPermissionManage ? 2 : 0);

// Nhúng từ điển tùy chỉnh nếu có vào window.__CUSTOM_I18N__ để i18n.js tự động đồng bộ
$customDictJson = json_encode(Language::getCustomDictionary(), JSON_UNESCAPED_UNICODE);
?>
<script>
    window.__CUSTOM_I18N__ = <?= $customDictJson ?: '{}' ?>;
    if (typeof window.loadAndMergeCustomTranslations === 'function') {
        window.loadAndMergeCustomTranslations(window.__CUSTOM_I18N__);
    }
</script>

<!-- Mobile Topbar -->
<div class="sb-mobile-topbar">
    <button type="button" class="sb-mobile-toggle-btn" id="sbMobileToggle" aria-label="Toggle Navigation">
        ☰
    </button>
    <a href="<?= $userHomeUrl ?>" class="sb-mobile-title" style="text-decoration:none; color:inherit;">
        <span>🏭</span>
        <span>WEB_BOBIN</span>
    </a>
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
        <a href="<?= $userHomeUrl ?>" class="sb-brand-main">
            <div class="sb-brand-logo">🏭</div>
            <div class="sb-brand-info">
                <span class="sb-brand-title">WEB_BOBIN</span>
                <span class="sb-brand-sub">SMC Extrusion (VN)</span>
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

    <!-- Navigation Tree (Scrollable Folder Tree View) -->
    <nav class="sb-nav-scroll sb-tree-root">

        <!-- 1. FOLDER: NHÓM ĐÙN (EXTRUSION) -->
        <?php if ($canExtrusionCreate || $canExtrusionEdit): ?>
            <div class="sb-tree-folder <?= $isExtrusionActive ? 'has-active-child' : '' ?>" data-folder-id="extrusion">
                <button type="button" class="sb-tree-folder-head" aria-expanded="<?= $isExtrusionActive ? 'true' : 'false' ?>">
                    <span class="sb-tree-chevron">▸</span>
                    <span class="sb-tree-folder-icon">📁</span>
                    <span class="sb-tree-folder-title" data-i18n="sidebar_nav_title_extrusion_stage"><?= __('sidebar_nav_title_extrusion_stage') ?></span>
                    <span class="sb-tree-badge"><?= $extCount ?></span>
                </button>
                <div class="sb-tree-children">
                    <?php if ($canExtrusionCreate): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/index"
                            class="sb-tree-leaf <?= in_array($currentUrl, ['bobin/index', 'bobin/extrusion', '']) ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">⚙️</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_extrusion"><?= __('nav_extrusion') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canExtrusionEdit): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/extrusionEditBobinView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/extrusionEditBobinView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">✏️</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_extrusion_edit"><?= __('nav_extrusion_edit') ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. FOLDER: NHÓM QC (QUALITY CONTROL) -->
        <?php if ($canQcCheck || $canQcEdit): ?>
            <div class="sb-tree-folder <?= $isQcActive ? 'has-active-child' : '' ?>" data-folder-id="qc">
                <button type="button" class="sb-tree-folder-head" aria-expanded="<?= $isQcActive ? 'true' : 'false' ?>">
                    <span class="sb-tree-chevron">▸</span>
                    <span class="sb-tree-folder-icon">📁</span>
                    <span class="sb-tree-folder-title" data-i18n="sidebar_nav_title_qc_stage"><?= __('sidebar_nav_title_qc_stage') ?></span>
                    <span class="sb-tree-badge"><?= $qcCount ?></span>
                </button>
                <div class="sb-tree-children">
                    <?php if ($canQcCheck): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/listBobinView_QC') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">🛡️</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_qc"><?= __('nav_qc') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canQcEdit): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/qcEditBobinView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/qcEditBobinView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">🔍</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_qc_edit"><?= __('nav_qc_edit') ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. FOLDER: NHÓM CUỘN (WINDING) -->
        <?php if ($canWindingConfirm || $canWindingEdit): ?>
            <div class="sb-tree-folder <?= $isWindingActive ? 'has-active-child' : '' ?>" data-folder-id="winding">
                <button type="button" class="sb-tree-folder-head" aria-expanded="<?= $isWindingActive ? 'true' : 'false' ?>">
                    <span class="sb-tree-chevron">▸</span>
                    <span class="sb-tree-folder-icon">📁</span>
                    <span class="sb-tree-folder-title" data-i18n="sidebar_nav_title_winding_stage"><?= __('sidebar_nav_title_winding_stage') ?></span>
                    <span class="sb-tree-badge"><?= $wndCount ?></span>
                </button>
                <div class="sb-tree-children">
                    <?php if ($canWindingConfirm): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/windingView"
                            class="sb-tree-leaf <?= in_array($currentUrl, ['bobin/windingView', 'bobin/listBobinView_Winding']) ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">📍</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_winding"><?= __('nav_winding') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canWindingEdit): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/windingEditBobinView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/windingEditBobinView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">⚡</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_winding_edit"><?= __('nav_winding_edit') ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. FOLDER: GIÁM SÁT & BÁO CÁO (MONITORING) -->
        <?php if ($canBobinList || $canBobinHistory || $canPendingCancel): ?>
            <div class="sb-tree-folder <?= $isMonitorActive ? 'has-active-child' : '' ?>" data-folder-id="monitor">
                <button type="button" class="sb-tree-folder-head" aria-expanded="<?= $isMonitorActive ? 'true' : 'false' ?>">
                    <span class="sb-tree-chevron">▸</span>
                    <span class="sb-tree-folder-icon">📁</span>
                    <span class="sb-tree-folder-title" data-i18n="sidebar_nav_title_monitor"><?= __('sidebar_nav_title_monitor') ?></span>
                    <span class="sb-tree-badge"><?= $monCount ?></span>
                </button>
                <div class="sb-tree-children">
                    <?php if ($canBobinList): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/listBobinDetailView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">📦</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_bobin_list"><?= __('nav_bobin_list') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canBobinHistory): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/listBobinHistoryView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">🕒</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_bobin_history"><?= __('nav_bobin_history') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canPendingCancel): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView"
                            class="sb-tree-leaf <?= ($currentUrl === 'bobin/listPendingCancellationView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">⏳</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_pending_cancel"><?= __('nav_pending_cancel') ?></span>
                            <?php if ($pCount > 0): ?>
                                <span class="sb-badge-pending"><?= $pCount ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 5. FOLDER: QUẢN TRỊ HỆ THỐNG (SYSTEM) -->
        <?php if ($canEmployeeManage || $canPermissionManage): ?>
            <div class="sb-tree-folder <?= $isSystemActive ? 'has-active-child' : '' ?>" data-folder-id="system">
                <button type="button" class="sb-tree-folder-head" aria-expanded="<?= $isSystemActive ? 'true' : 'false' ?>">
                    <span class="sb-tree-chevron">▸</span>
                    <span class="sb-tree-folder-icon">📁</span>
                    <span class="sb-tree-folder-title" data-i18n="sidebar_nav_title_system"><?= __('sidebar_nav_title_system') ?></span>
                    <span class="sb-tree-badge"><?= $sysCount ?></span>
                </button>
                <div class="sb-tree-children">
                    <?php if ($canEmployeeManage): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=employee/index"
                            class="sb-tree-leaf <?= (strpos($currentUrl, 'employee/index') === 0 || $currentUrl === 'employee') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">👥</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_employee_list"><?= __('nav_employee_list') ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($canPermissionManage): ?>
                        <a href="/WEB_BOBIN/public/index.php?url=employee/permissionsView"
                            class="sb-tree-leaf <?= ($currentUrl === 'employee/permissionsView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">🔐</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_permissions"><?= __('nav_permissions') ?></span>
                        </a>

                        <a href="/WEB_BOBIN/public/index.php?url=employee/translationsView"
                            class="sb-tree-leaf <?= ($currentUrl === 'employee/translationsView') ? 'active-nav' : '' ?>">
                            <span class="sb-tree-leaf-icon">🌐</span>
                            <span class="sb-tree-leaf-text" data-i18n="nav_translations"><?= __('nav_translations') ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </nav>

    <!-- Sidebar Minimal Footer (Controls moved to Top Header Bar in TASK-015) -->
    <div class="sb-footer-minimal">
        <div class="sb-system-info">
            <span class="sb-status-dot"></span>
            <span class="sb-system-text">SMC Extrusion (VN)</span>
        </div>
        <div class="sb-version-text">v2.1 • Local</div>
    </div>
</aside>

<script>
    // Sidebar Desktop & Mobile & Folder Tree Controller (TASK-014, TASK-015, TASK-016)
    (function() {
        const body = document.body;
        const desktopToggle = document.getElementById('sbDesktopToggle');
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('sbOverlay');

        let lastToggleTime = 0;

        // Hàm toàn cục mở/đóng Sidebar trên Mobile & Tablet (Hoạt động hoàn hảo trên Android/Xiaomi/iOS)
        window.toggleWebBobinMobileMenu = function(e) {
            const now = Date.now();
            if (now - lastToggleTime < 250) return; // Chống duplicate trigger touch + click
            lastToggleTime = now;

            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }

            const sb = document.getElementById('appSidebar');
            const ov = document.getElementById('sbOverlay');
            if (sb && ov) {
                const isOpen = sb.classList.toggle('mobile-open');
                ov.classList.toggle('show', isOpen);
                body.classList.toggle('sb-drawer-active', isOpen);
            }
        };

        // Hàm toàn cục đóng Sidebar
        window.closeWebBobinMobileMenu = function(e) {
            if (e && typeof e.stopPropagation === 'function') {
                e.stopPropagation();
            }
            const sb = document.getElementById('appSidebar');
            const ov = document.getElementById('sbOverlay');
            if (sb) sb.classList.remove('mobile-open');
            if (ov) ov.classList.remove('show');
            body.classList.remove('sb-drawer-active');
        };

        // 1. Khôi phục trạng thái Sidebar thu gọn/mở rộng trên Desktop
        if (localStorage.getItem('webbobin_sidebar_collapsed') === '1' && window.innerWidth > 992) {
            body.classList.add('sidebar-collapsed');
            if (desktopToggle) desktopToggle.textContent = '▶';
        }

        if (desktopToggle) {
            desktopToggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 992) {
                    // Trên Mobile/Tablet: nút đóng drawer
                    window.closeWebBobinMobileMenu(e);
                    return;
                }
                body.classList.toggle('sidebar-collapsed');
                const isCollapsed = body.classList.contains('sidebar-collapsed');
                localStorage.setItem('webbobin_sidebar_collapsed', isCollapsed ? '1' : '0');
                desktopToggle.textContent = isCollapsed ? '▶' : '◀';
            });
        }

        // 2. Event Delegation ở cấp độ document: Luôn bắt được nút hamburger dù header.php tải sau sidebar.php
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('#headerMobileToggle, #sbMobileToggle, .sb-mobile-toggle-btn');
            if (toggleBtn) {
                window.toggleWebBobinMobileMenu(e);
                return;
            }

            const ov = document.getElementById('sbOverlay');
            if (e.target === ov) {
                window.closeWebBobinMobileMenu(e);
                return;
            }

            // Tự động đóng drawer khi click vào liên kết trang trên màn hình di động
            const leaf = e.target.closest('.sb-tree-leaf');
            if (leaf && window.innerWidth <= 992) {
                window.closeWebBobinMobileMenu(e);
            }
        });

        // Hỗ trợ đóng drawer khi nhấn phím ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeWebBobinMobileMenu();
            }
        });

        // 3. Folder Tree Expand / Collapse Controller với LocalStorage Persistence & Smooth Scrolling
        const folders = document.querySelectorAll('.sb-tree-folder');
        folders.forEach(folder => {
            const folderId = folder.getAttribute('data-folder-id');
            const head = folder.querySelector('.sb-tree-folder-head');
            const folderIcon = folder.querySelector('.sb-tree-folder-icon');
            const hasActive = folder.classList.contains('has-active-child');

            // Khôi phục trạng thái từ localStorage, ưu tiên mở nếu có trang active bên trong
            const savedState = localStorage.getItem('webbobin_tree_folder_' + folderId);
            let isOpen = false;

            if (hasActive) {
                isOpen = true; // Luôn mở thư mục chứa trang hiện tại
            } else if (savedState !== null) {
                isOpen = (savedState === '1');
            } else {
                isOpen = false; // Mặc định đóng các thư mục khác
            }

            if (isOpen) {
                folder.classList.add('is-open');
                if (head) head.setAttribute('aria-expanded', 'true');
                if (folderIcon) folderIcon.textContent = '📂';
            } else {
                folder.classList.remove('is-open');
                if (head) head.setAttribute('aria-expanded', 'false');
                if (folderIcon) folderIcon.textContent = '📁';
            }

            // Sự kiện click toggle thư mục
            if (head) {
                head.addEventListener('click', function(e) {
                    // Nếu sidebar đang thu gọn trên desktop, click vào thư mục sẽ bung sidebar ra trước
                    if (body.classList.contains('sidebar-collapsed')) {
                        body.classList.remove('sidebar-collapsed');
                        localStorage.setItem('webbobin_sidebar_collapsed', '0');
                        if (desktopToggle) desktopToggle.textContent = '◀';
                    }

                    const nowOpen = folder.classList.toggle('is-open');
                    head.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
                    if (folderIcon) {
                        folderIcon.textContent = nowOpen ? '📂' : '📁';
                    }
                    localStorage.setItem('webbobin_tree_folder_' + folderId, nowOpen ? '1' : '0');

                    // Tự động cuộn nhẹ nhàng để thấy trọn vẹn thư mục vừa mở mà không bị che khuất
                    if (nowOpen) {
                        setTimeout(() => {
                            folder.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
                        }, 50);
                    }
                });
            }
        });
    })();
</script>