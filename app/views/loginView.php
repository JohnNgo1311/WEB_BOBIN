<!DOCTYPE html>
<html lang="<?= Language::getCurrent() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?> | SMC</title>
    <!-- Phông chữ hệ thống Offline không phụ thuộc Google Fonts khi chạy Local Intranet -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/login.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script>
        window.__CUSTOM_I18N__ = <?= json_encode(Language::getCustomDictionary(), JSON_UNESCAPED_UNICODE) ?: '{}' ?>;
    </script>
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
</head>

<body>

    <div class="login-split-wrapper">
        <!-- ================= CỘT TRÁI: SHOWCASE & CỔNG THEO DÕI NHANH ================= -->
        <div class="showcase-section">
            <div class="showcase-content">
                <div class="brand-badge" data-i18n="login_brand_badge">
                    <span class="pulse-dot"></span> <?= __('login_brand_badge') ?>
                </div>

                <h1 class="showcase-title" data-i18n="login_title"><?= __('login_title') ?></h1>
                <p class="showcase-desc" data-i18n="login_subtitle">
                    <?= __('login_subtitle') ?>
                </p>

                <!-- KHU VỰC TRUY CẬP THEO DÕI NHANH -->
                <div class="quick-portal-box">
                    <div class="portal-header">
                        <div class="portal-header-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span data-i18n="login_quick_track"><?= __('login_quick_track') ?></span>
                        </div>
                        <span class="public-badge" data-i18n="login_free_access"><?= __('login_free_access') ?></span>
                    </div>

                    <div class="portal-cards-grid">
                        <!-- Card 1: Danh sách chi tiết -->
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView"
                            class="portal-card card-detail">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                            </div>
                            <div class="portal-info">
                                <div class="portal-name" data-i18n="portal_detail_title"><?= __('portal_detail_title', 'Danh sách Bobin hiện tại') ?></div>
                                <div class="portal-sub" data-i18n="portal_detail_sub"><?= __('portal_detail_sub', 'Tra cứu số lượng, tình trạng và công đoạn của toàn bộ Bobin') ?></div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>

                        <!-- Card 2: Lịch sử -->
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
                            class="portal-card card-history">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="portal-info">
                                <div class="portal-name" data-i18n="portal_history_title"><?= __('portal_history_title', 'Lịch sử hoạt động Bobin') ?></div>
                                <div class="portal-sub" data-i18n="portal_history_sub"><?= __('portal_history_sub', 'Xem nhật ký luân chuyển, lọc theo ngày và xuất báo cáo Excel') ?></div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>

                        <!-- Card 3: Chờ hủy -->
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listPendingCancellationView"
                            class="portal-card card-pending">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                    </path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </div>
                            <div class="portal-info">
                                <div class="portal-name" data-i18n="portal_pending_title"><?= __('portal_pending_title', 'Danh sách Bobin chờ hủy') ?></div>
                                <div class="portal-sub" data-i18n="portal_pending_sub"><?= __('portal_pending_sub', 'Theo dõi các Bobin báo lỗi, chờ phê duyệt hủy từ phân xưởng') ?></div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>
                    </div>
                </div>

                <div class="showcase-footer">
                    <div class="stat-pill" data-i18n="login_stat_qr"><?= __('login_stat_qr') ?></div>
                    <div class="stat-pill" data-i18n="login_stat_chart"><?= __('login_stat_chart') ?></div>
                    <div class="stat-pill" data-i18n="login_stat_shift"><?= __('login_stat_shift') ?></div>
                </div>
            </div>
        </div>

        <!-- ================= CỘT PHẢI: FORM ĐĂNG NHẬP ================= -->
        <div class="login-panel">
            <!-- NÚT CHUYỂN ĐỔI NGÔN NGỮ LINH HOẠT -->
            <div class="login-lang-wrapper">
                <?php require ROOT_PATH . '/app/views/components/languageSwitcher.php'; ?>
            </div>

            <div class="login-box">
                <div class="login-header">
                    <img src="/WEB_BOBIN/public/assets/images/smcLogo.png" alt="SMC Logo" class="login-logo">
                    <h2 data-i18n="login_btn_submit"><?= __('login_title') ?></h2>
                </div>

                <?php if (!empty($_GET['error'])): ?>
                <div class="login-alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="/WEB_BOBIN/public/index.php?url=auth/validateLogin" class="login-form">
                    <div class="form-group">
                        <label for="username" data-i18n="login_username"><?= __('login_username') ?></label>
                        <div class="input-icon-wrapper">
                            <span class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="username" name="username" placeholder="<?= __('login_username_ph') ?>"
                                data-i18n-ph="login_username_ph" required autocomplete="username" autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row" style="display:flex; justify-content:space-between; align-items:center;">
                            <label for="password" data-i18n="login_password"><?= __('login_password') ?></label>
                            <a href="javascript:void(0)" class="forgot-pwd-link" id="btnForgotPwd" data-i18n="login_forgot_pwd">
                                <?= __('login_forgot_pwd') ?>
                            </a>
                        </div>
                        <div class="input-icon-wrapper">
                            <span class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input type="password" id="password" name="password" placeholder="<?= __('login_password_ph') ?>"
                                data-i18n-ph="login_password_ph" required autocomplete="current-password">

                            <button type="button" class="btn-toggle-pwd" id="togglePwd" title="<?= __('login_toggle_pwd') ?>">
                                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login-submit">
                        <span data-i18n="login_btn_submit"><?= __('login_btn_submit') ?></span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="login-copyright" data-i18n="login_copyright">
                    <?= __('login_copyright') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL QUY TRÌNH QUÊN MẬT KHẨU LOCAL ================= -->
    <div class="fp-modal-backdrop" id="fpModal">
        <div class="fp-modal-card">
            <div class="fp-modal-header">
                <div class="fp-header-left">
                    <div class="fp-header-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h3 class="fp-header-title" data-i18n="fp_modal_title"><?= __('fp_modal_title') ?></h3>
                </div>
                <button type="button" class="fp-btn-close" id="btnCloseFpModal">&times;</button>
            </div>

            <div class="fp-modal-body">
                <div class="fp-notice-banner" data-i18n="fp_modal_desc">
                    <?= __('fp_modal_desc') ?>
                </div>

                <div class="fp-steps-container">
                    <div class="fp-step-item">
                        <div class="fp-step-num">1</div>
                        <div class="fp-step-content">
                            <strong data-i18n="fp_step_1_title"><?= __('fp_step_1_title') ?></strong>
                            <p data-i18n="fp_step_1_desc"><?= __('fp_step_1_desc') ?></p>
                        </div>
                    </div>

                    <div class="fp-step-item">
                        <div class="fp-step-num">2</div>
                        <div class="fp-step-content">
                            <strong data-i18n="fp_step_2_title"><?= __('fp_step_2_title') ?></strong>
                            <p data-i18n="fp_step_2_desc"><?= __('fp_step_2_desc') ?></p>
                        </div>
                    </div>

                    <div class="fp-step-item">
                        <div class="fp-step-num">3</div>
                        <div class="fp-step-content">
                            <strong data-i18n="fp_step_3_title"><?= __('fp_step_3_title') ?></strong>
                            <p data-i18n="fp_step_3_desc"><?= __('fp_step_3_desc') ?></p>
                        </div>
                    </div>
                </div>

                <div class="fp-hotline-box">
                    <div class="fp-hotline-title" data-i18n="fp_contact_info">
                        📞 <?= __('fp_contact_info') ?>
                    </div>
                    <div class="fp-hotline-numbers" data-i18n="fp_contact_room">
                        <?= __('fp_contact_room') ?>
                    </div>
                </div>
            </div>

            <div class="fp-modal-footer">
                <button type="button" class="fp-btn-ack" id="btnAckFpModal" data-i18n="fp_btn_understood">
                    <?= __('fp_btn_understood') ?>
                </button>
            </div>
        </div>
    </div>

    <script>
    // Toggle mật khẩu
    document.getElementById('togglePwd')?.addEventListener('click', function() {
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
        } else {
            pwdInput.type = 'password';
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
        }
    });

    // Modal Quên mật khẩu
    const fpModal = document.getElementById('fpModal');
    const btnForgotPwd = document.getElementById('btnForgotPwd');
    const btnCloseFpModal = document.getElementById('btnCloseFpModal');
    const btnAckFpModal = document.getElementById('btnAckFpModal');

    function openFpModal() {
        fpModal?.classList.add('show');
    }
    function closeFpModal() {
        fpModal?.classList.remove('show');
    }

    btnForgotPwd?.addEventListener('click', openFpModal);
    btnCloseFpModal?.addEventListener('click', closeFpModal);
    btnAckFpModal?.addEventListener('click', closeFpModal);
    fpModal?.addEventListener('click', function(e) {
        if (e.target === fpModal) closeFpModal();
    });
    </script>
</body>

</html>