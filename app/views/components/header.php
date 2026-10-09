<?php
// File: app/views/components/header.php
// Component Top Header Bar Hiện Đại & Thông Minh cho Hệ Thống WEB_BOBIN (TASK-015)
// Tích hợp:
// - Góc Trái: Breadcrumb điều hướng phân cấp (Trang chủ > Phân xưởng / Thư mục > Trang hiện tại)
// - Góc Phải: Chuyển đổi ngôn ngữ (VI / EN / JA), Thông tin người dùng, Đổi mật khẩu & Đăng xuất

require_once ROOT_PATH . '/app/core/Language.php';
require_once ROOT_PATH . '/app/core/AuthHelper.php';

$user         = $_SESSION['user'] ?? [];
$userRole     = strtolower($user['role'] ?? '');
$userName     = $user['employee_name'] ?? 'Người dùng';
$empCode      = $user['employee_code'] ?? '';
$currentUrl   = $_GET['url'] ?? 'bobin/index';
$currentLang  = Language::getCurrent();

// Kiểm tra quyền hạn chi tiết để xác định Trang chủ hợp lệ của người dùng
$canExtrusionCreate  = AuthHelper::hasPermission('extrusion_create', $user);
$canQcCheck          = AuthHelper::hasPermission('qc_check', $user);
$canWindingConfirm   = AuthHelper::hasPermission('winding_confirm', $user);
$canBobinList        = AuthHelper::hasPermission('bobin_list', $user);
$canBobinHistory     = AuthHelper::hasPermission('bobin_history', $user);
$canPendingCancel    = AuthHelper::hasPermission('pending_cancel', $user);
$canEmployeeManage   = AuthHelper::hasPermission('employee_manage', $user);
$canPermissionManage = AuthHelper::hasPermission('permission_manage', $user);

$homeUrl = match (true) {
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

// Tạo link giữ lại query params khi đổi ngôn ngữ
$currentQuery = $_GET;
unset($currentQuery['lang']);
$baseLangQuery = '/WEB_BOBIN/public/index.php?' . http_build_query($currentQuery);
$langPrefix = !empty($currentQuery) ? '&lang=' : 'lang=';

// Lấy chữ cái đầu làm avatar
$firstLetter = mb_substr(trim($userName), 0, 1, 'UTF-8');

// Cấu hình Ánh xạ Breadcrumb cho từng trang trong hệ thống
$routeMap = [
    // 1. Phân xưởng Đùn
    'bobin/index' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_extrusion_stage',
        'page_icon'   => '⚙️',
        'page_key'    => 'nav_extrusion',
        'page_title'  => 'page_extrusion'
    ],
    'bobin/extrusion' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_extrusion_stage',
        'page_icon'   => '⚙️',
        'page_key'    => 'nav_extrusion',
        'page_title'  => 'page_extrusion'
    ],
    'bobin/extrusionEditBobinView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_extrusion_stage',
        'page_icon'   => '✏️',
        'page_key'    => 'nav_extrusion_edit',
        'page_title'  => 'page_extrusion_edit'
    ],

    // 2. Kiểm tra QC
    'bobin/listBobinView_QC' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_qc_stage',
        'page_icon'   => '🛡️',
        'page_key'    => 'nav_qc',
        'page_title'  => 'page_qc'
    ],
    'bobin/qcEditBobinView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_qc_stage',
        'page_icon'   => '🔍',
        'page_key'    => 'nav_qc_edit',
        'page_title'  => 'page_qc_edit'
    ],

    // 3. Phân xưởng Cuộn
    'bobin/windingView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_winding_stage',
        'page_icon'   => '📍',
        'page_key'    => 'nav_winding',
        'page_title'  => 'page_winding'
    ],
    'bobin/listBobinView_Winding' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_winding_stage',
        'page_icon'   => '📍',
        'page_key'    => 'nav_winding',
        'page_title'  => 'page_winding'
    ],
    'bobin/windingEditBobinView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_winding_stage',
        'page_icon'   => '⚡',
        'page_key'    => 'nav_winding_edit',
        'page_title'  => 'page_winding_edit'
    ],

    // 4. Giám sát & Báo cáo
    'bobin/listBobinDetailView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_monitor',
        'page_icon'   => '📦',
        'page_key'    => 'nav_bobin_list',
        'page_title'  => 'page_bobin_detail'
    ],
    'bobin/listBobinHistoryView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_monitor',
        'page_icon'   => '🕒',
        'page_key'    => 'nav_bobin_history',
        'page_title'  => 'page_bobin_history'
    ],
    'bobin/listPendingCancellationView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_monitor',
        'page_icon'   => '⏳',
        'page_key'    => 'nav_pending_cancel',
        'page_title'  => 'page_pending_cancel'
    ],

    // 5. Quản trị hệ thống
    'employee/index' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_system',
        'page_icon'   => '👥',
        'page_key'    => 'nav_employee_list',
        'page_title'  => 'page_employee_list'
    ],
    'employee' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_system',
        'page_icon'   => '👥',
        'page_key'    => 'nav_employee_list',
        'page_title'  => 'page_employee_list'
    ],
    'employee/permissionsView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_system',
        'page_icon'   => '🔐',
        'page_key'    => 'nav_permissions',
        'page_title'  => 'perm_page_title'
    ],
    'employee/translationsView' => [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_system',
        'page_icon'   => '🌐',
        'page_key'    => 'nav_translations',
        'page_title'  => 'lang_page_title'
    ],
];

// Tìm mục khớp với currentUrl
$matchedRoute = $routeMap[$currentUrl] ?? null;
if (!$matchedRoute) {
    foreach ($routeMap as $k => $info) {
        if (str_starts_with($currentUrl, $k)) {
            $matchedRoute = $info;
            break;
        }
    }
}

if (!$matchedRoute) {
    $matchedRoute = [
        'folder_icon' => '📁',
        'folder_key'  => 'sidebar_nav_title_extrusion_stage',
        'page_icon'   => '📄',
        'page_key'    => 'nav_extrusion',
        'page_title'  => 'nav_extrusion'
    ];
}
$customDictJson = json_encode(Language::getCustomDictionary(), JSON_UNESCAPED_UNICODE) ?: '{}';
?>
<script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
<script>
    window.__CUSTOM_I18N__ = <?= $customDictJson ?>;
    if (typeof window.loadAndMergeCustomTranslations === 'function') {
        window.loadAndMergeCustomTranslations(window.__CUSTOM_I18N__);
    }
</script>

<!-- TOP HEADER BAR (TASK-015 & TASK-016 RESPONSIVE) -->
<header class="app-top-header" id="appTopHeader">
    <!-- GÓC TRÁI: Hamburger Drawer Toggle (Mobile/Tablet) & Breadcrumb Điều Hướng -->
    <div class="header-left">
        <button type="button" class="header-mobile-toggle sb-mobile-toggle-btn" id="headerMobileToggle"
            onclick="window.toggleWebBobinMobileMenu ? window.toggleWebBobinMobileMenu(event) : void(0)"
            aria-label="Toggle Navigation" title="Menu">
            <span class="toggle-icon">☰</span>
        </button>

        <nav class="header-breadcrumb" aria-label="Breadcrumb">
            <ol class="header-breadcrumb-list">
                <!-- Root: Trang chủ -->
                <li class="header-breadcrumb-item">
                    <a href="<?= $homeUrl ?>" class="header-breadcrumb-link" title="<?= __('breadcrumb_home') ?>">
                        <span class="bc-icon">🏠</span>
                        <span data-i18n="breadcrumb_home"><?= __('breadcrumb_home') ?></span>
                    </a>
                </li>

                <!-- Separator 1 -->
                <li class="header-breadcrumb-sep" aria-hidden="true">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </li>

                <!-- Folder Category -->
                <li class="header-breadcrumb-item">
                    <span class="header-breadcrumb-folder">
                        <span class="bc-icon"><?= $matchedRoute['folder_icon'] ?></span>
                        <span
                            data-i18n="<?= $matchedRoute['folder_key'] ?>"><?= __($matchedRoute['folder_key']) ?></span>
                    </span>
                </li>

                <!-- Separator 2 -->
                <li class="header-breadcrumb-sep" aria-hidden="true">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </li>

                <!-- Current Active Page -->
                <li class="header-breadcrumb-item active" aria-current="page">
                    <span class="header-breadcrumb-current">
                        <span class="bc-icon"><?= $matchedRoute['page_icon'] ?></span>
                        <span data-i18n="<?= $matchedRoute['page_key'] ?>"><?= __($matchedRoute['page_key']) ?></span>
                    </span>
                </li>
            </ol>
        </nav>
    </div>

    <!-- GÓC PHẢI: Ngôn Ngữ, Profile Nhân Viên, Đổi Mật Khẩu, Đăng Xuất -->
    <div class="header-right">
        <!-- 1. Bộ chuyển đổi Ngôn ngữ (Language Switcher) -->
        <div class="header-lang-switch" role="group" aria-label="Language Selector">
            <a href="<?= $baseLangQuery . $langPrefix ?>vi"
                class="header-lang-btn <?= $currentLang === 'vi' ? 'active-lang' : '' ?>" title="Tiếng Việt">
                <span class="flag-icon">🇻🇳</span>
                <span class="lang-text">VI</span>
            </a>
            <a href="<?= $baseLangQuery . $langPrefix ?>en"
                class="header-lang-btn <?= $currentLang === 'en' ? 'active-lang' : '' ?>" title="English">
                <span class="flag-icon">🇬🇧</span>
                <span class="lang-text">EN</span>
            </a>
            <a href="<?= $baseLangQuery . $langPrefix ?>ja"
                class="header-lang-btn <?= $currentLang === 'ja' ? 'active-lang' : '' ?>" title="日本語">
                <span class="flag-icon">🇯🇵</span>
                <span class="lang-text">JA</span>
            </a>
        </div>

        <div class="header-divider" aria-hidden="true"></div>

        <?php if (!empty($user)): ?>
            <!-- 2. Thẻ Thông tin Người dùng (User Profile Badge) -->
            <div class="header-user-profile" title="<?= htmlspecialchars($userName) ?> (<?= strtoupper($userRole) ?>)">
                <div class="header-user-avatar role-<?= htmlspecialchars($userRole) ?>">
                    <?= htmlspecialchars($firstLetter) ?>
                </div>
                <div class="header-user-meta">
                    <span class="header-user-name" title="<?= htmlspecialchars($userName) ?>">
                        <?= htmlspecialchars($userName) ?>
                    </span>
                    <span class="header-user-role role-tag-<?= htmlspecialchars($userRole) ?>">
                        <?= htmlspecialchars($empCode) ?> •
                        <?php
                        $roleLabels = [
                            'extrusion' => 'Nhóm Đùn',
                            'winding' => 'Nhóm Cuộn',
                            'qc' => 'Nhóm QC',
                            'admin' => 'Admin',
                        ];
                        echo htmlspecialchars($roleLabels[$userRole] ?? strtoupper($userRole));
                        ?>

                    </span>
                </div>
            </div>

            <!-- 3. Nút Đổi mật khẩu -->
            <a href="/WEB_BOBIN/public/index.php?url=auth/changePassword" class="header-btn header-btn-pwd"
                title="<?= __('nav_change_pwd') ?>">
                <span class="btn-icon">🔑</span>
                <span class="btn-text" data-i18n="nav_change_pwd"><?= __('nav_change_pwd') ?></span>
            </a>

            <!-- 4. Nút Đăng xuất -->
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="header-btn header-btn-logout"
                title="<?= __('nav_logout') ?>">
                <span class="btn-icon">🚪</span>
                <span class="btn-text" data-i18n="nav_logout"><?= __('nav_logout') ?></span>
            </a>
        <?php else: ?>
            <!-- Người dùng Khách: Nút Đăng nhập -->
            <a href="/WEB_BOBIN/public/index.php?url=auth/login" class="header-btn header-btn-login"
                title="<?= __('nav_login') ?>">
                <span class="btn-icon">👤</span>
                <span class="btn-text" data-i18n="nav_login"><?= __('nav_login') ?></span>
            </a>
        <?php endif; ?>
    </div>
</header>