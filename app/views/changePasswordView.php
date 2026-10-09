<?php
$userRole     = $user['role'] ?? ($_SESSION['user']['role'] ?? '');
$employeeName = $user['employee_name'] ?? ($_SESSION['user']['employee_name'] ?? '');
$employeeCode = $user['employee_code'] ?? ($_SESSION['user']['employee_code'] ?? '');
$username     = $user['username'] ?? ($_SESSION['user']['username'] ?? '');
$isFirstLogin = !empty($isFirstLogin);

$homeUrl = match ($userRole) {
    'extrusion' => '/WEB_BOBIN/public/index.php?url=bobin/index',
    'qc'        => '/WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC',
    'winding'   => '/WEB_BOBIN/public/index.php?url=bobin/listBobinView_Winding',
    default     => '/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView',
};
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isFirstLogin ? __('cp_title_first') : __('cp_title_normal') ?> | SMC Bobin</title>
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/changePassword.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <script>
        window.__CUSTOM_I18N__ = <?= json_encode(Language::getCustomDictionary(), JSON_UNESCAPED_UNICODE) ?: '{}' ?>;
    </script>
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
</head>

<body>

    <!-- THANH ĐIỀU HƯỚNG GỌN -->
    <header class="cp-navbar">
        <a href="<?= $isFirstLogin ? '#' : $homeUrl ?>" class="cp-nav-brand">
            <img src="/WEB_BOBIN/public/assets/images/smcLogo.png" alt="SMC Logo">
            <span>SMC BOBIN MANAGEMENT</span>
        </a>

        <div class="cp-nav-user" style="display:flex; align-items:center; gap:10px;">
            <?php require ROOT_PATH . '/app/views/components/languageSwitcher.php'; ?>
            <span class="user-badge-nav">
                👤 <?= htmlspecialchars($employeeName) ?> (<?= strtoupper($userRole) ?>)
            </span>
            <a href="/WEB_BOBIN/public/index.php?url=auth/logout" class="btn-nav-logout"
                title="Đăng xuất khỏi hệ thống" data-i18n="nav_logout">
                <?= __('nav_logout') ?>
            </a>
        </div>
    </header>

    <!-- KHU VỰC CHÍNH -->
    <main class="cp-main-container">
        <div class="cp-card">

            <!-- BANNER BẮT BUỘC ĐỔI MẬT KHẨU LẦN ĐẦU -->
            <?php if ($isFirstLogin): ?>
            <div class="cp-alert-first-login">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                    </path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div>
                    <strong>Yêu cầu bảo mật: Đổi mật khẩu lần đầu!</strong><br>
                    Để đảm bảo an toàn thông tin cá nhân và tránh trùng lặp mật khẩu ban đầu của hệ thống, vui lòng đổi
                    mật khẩu mới trước khi tiếp tục.
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="cp-alert-error">
                <span>⚠️ <?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <div class="cp-header">
                <div class="cp-icon-wrap">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h1 class="cp-title"><?= $isFirstLogin ? 'THIẾT LẬP MẬT KHẨU MỚI' : 'ĐỔI MẬT KHẨU TÀI KHOẢN' ?></h1>
                <p class="cp-subtitle">
                    <?= $isFirstLogin ? 'Vui lòng nhập mật khẩu hiện tại và tạo mật khẩu mới an toàn' : 'Cập nhật mật khẩu định kỳ để bảo vệ tài khoản của bạn' ?>
                </p>

                <div class="cp-user-info-pill">
                    <span>Mã NV: <strong><?= htmlspecialchars($employeeCode) ?></strong></span>
                    <span>•</span>
                    <span>Tài khoản: <strong><?= htmlspecialchars($username) ?></strong></span>
                    <span class="role-badge"><?= strtoupper($userRole) ?></span>
                </div>
            </div>

            <form id="changePasswordForm" method="POST" action="/WEB_BOBIN/public/index.php?url=auth/postChangePassword"
                class="cp-form">

                <!-- 1. MẬT KHẨU HIỆN TẠI -->
                <div class="form-group">
                    <label for="current_password" data-i18n="cp_current_pwd">
                        <?= __('cp_current_pwd') ?> <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" id="current_password" name="current_password"
                            placeholder="<?= __('cp_current_pwd_ph') ?>" data-i18n-ph="cp_current_pwd_ph"
                            required autocomplete="current-password" autofocus>
                        <button type="button" class="btn-toggle-pwd" data-target="current_password"
                            title="<?= __('login_toggle_pwd') ?>" data-i18n="[title]login_toggle_pwd">
                            👁️
                        </button>
                    </div>
                </div>

                <!-- 2. MẬT KHẨU MỚI -->
                <div class="form-group">
                    <label for="new_password" data-i18n="cp_new_pwd">
                        <?= __('cp_new_pwd') ?> <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 2l-2 2m-2-2l2 2"></path>
                                <path d="M12 11a5 5 0 1 0-5-5"></path>
                                <circle cx="7" cy="7" r="2"></circle>
                            </svg>
                        </span>
                        <input type="password" id="new_password" name="new_password" placeholder="<?= __('cp_new_pwd_ph') ?>"
                            data-i18n-ph="cp_new_pwd_ph" required autocomplete="new-password" minlength="6">
                        <button type="button" class="btn-toggle-pwd" data-target="new_password"
                            title="<?= __('login_toggle_pwd') ?>" data-i18n="[title]login_toggle_pwd">
                            👁️
                        </button>
                    </div>

                    <!-- Thanh đánh giá độ mạnh mật khẩu -->
                    <div class="pwd-strength-container" id="strengthContainer" style="display: none;">
                        <div class="pwd-strength-bar">
                            <div class="pwd-strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="pwd-strength-text">
                            <span data-i18n="cp_strength_label"><?= __('cp_strength_label') ?></span>
                            <strong id="strengthLabel">-</strong>
                        </div>
                    </div>
                </div>

                <!-- 3. XÁC NHẬN MẬT KHẨU MỚI -->
                <div class="form-group">
                    <label for="confirm_password" data-i18n="cp_confirm_pwd">
                        <?= __('cp_confirm_pwd') ?> <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="field-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="<?= __('cp_confirm_pwd_ph') ?>" data-i18n-ph="cp_confirm_pwd_ph" required autocomplete="new-password" minlength="6">
                        <button type="button" class="btn-toggle-pwd" data-target="confirm_password"
                            title="<?= __('login_toggle_pwd') ?>" data-i18n="[title]login_toggle_pwd">
                            👁️
                        </button>
                    </div>
                </div>

                <!-- HƯỚNG DẪN BẢO MẬT -->
                <div class="pwd-guidelines">
                    <strong>💡 Lưu ý an toàn:</strong>
                    <ul>
                        <li>Độ dài tối thiểu từ <strong>6 ký tự trở lên</strong>.</li>
                        <li>Không đặt mật khẩu trùng với mật khẩu cũ hoặc mật khẩu mặc định (<code>123</code>,
                            <code>123456</code>).
                        </li>
                        <li>Nên kết hợp chữ cái và số để tăng tính bảo mật cho tài khoản của bạn.</li>
                    </ul>
                </div>

                <!-- NÚT HÀNH ĐỘNG -->
                <div class="cp-actions">
                    <?php if (!$isFirstLogin): ?>
                    <a href="<?= $homeUrl ?>" class="btn-cancel-pwd" data-i18n="cp_btn_back">
                        <?= __('cp_btn_back') ?>
                    </a>
                    <?php endif; ?>

                    <button type="submit" class="btn-submit-pwd" id="btnSubmitPassword">
                        <span data-i18n="cp_btn_submit"><?= __('cp_btn_submit') ?></span>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="cp-footer">
        © <?= date('Y') ?> SMC Factory. Đã kích hoạt cơ chế bảo vệ danh tính & kiểm soát mật khẩu cá nhân.
    </footer>

    <!-- SCRIPT XỬ LÝ ĐỔI MẬT KHẨU HIỆN ĐẠI -->
    <script>
    const isFirstLogin = <?= json_encode($isFirstLogin) ?>;
    const employeeCode = <?= json_encode($employeeCode) ?>;
    const username = <?= json_encode($username) ?>;
    var Toast = window.Toast;

    // 1. Nút Ẩn/Hiện mật khẩu
    document.querySelectorAll('.btn-toggle-pwd').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🔒';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });

    // 2. Đo độ mạnh mật khẩu mới
    const newPwdInput = document.getElementById('new_password');
    const strengthContainer = document.getElementById('strengthContainer');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');

    newPwdInput.addEventListener('input', function() {
        const val = this.value;
        if (!val) {
            strengthContainer.style.display = 'none';
            return;
        }
        strengthContainer.style.display = 'flex';

        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 8) score++;
        if (/[0-9]/.test(val) && /[a-zA-Z]/.test(val)) score++;
        if (/[^a-zA-Z0-9]/.test(val)) score++;

        strengthFill.className = 'pwd-strength-fill';
        if (score <= 1) {
            strengthFill.classList.add('strength-weak');
            strengthLabel.textContent = 'Yếu';
            strengthLabel.style.color = '#ef4444';
        } else if (score <= 2) {
            strengthFill.classList.add('strength-medium');
            strengthLabel.textContent = 'Trung bình';
            strengthLabel.style.color = '#f59e0b';
        } else {
            strengthFill.classList.add('strength-strong');
            strengthLabel.textContent = 'Mạnh';
            strengthLabel.style.color = '#16a34a';
        }
    });

    // 3. Xử lý gửi Form bằng AJAX
    const form = document.getElementById('changePasswordForm');
    const submitBtn = document.getElementById('btnSubmitPassword');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const curPwd = document.getElementById('current_password').value.trim();
        const newPwd = document.getElementById('new_password').value.trim();
        const cfmPwd = document.getElementById('confirm_password').value.trim();

        if (!curPwd || !newPwd || !cfmPwd) {
            Toast.show("Vui lòng điền đầy đủ các trường mật khẩu.", "warning");
            return;
        }

        if (newPwd.length < 6) {
            Toast.show("Mật khẩu mới phải có tối thiểu 6 ký tự.", "warning");
            document.getElementById('new_password').focus();
            return;
        }

        if (newPwd !== cfmPwd) {
            Toast.show("Mật khẩu xác nhận không khớp với mật khẩu mới.", "error");
            document.getElementById('confirm_password').focus();
            return;
        }

        if (newPwd === curPwd) {
            Toast.show("Mật khẩu mới không được trùng với mật khẩu hiện tại.", "warning");
            document.getElementById('new_password').focus();
            return;
        }

        const forbidden = ['123', '123456', 'password', 'admin', employeeCode.toLowerCase(), username
            .toLowerCase()
        ];
        if (forbidden.includes(newPwd.toLowerCase())) {
            Toast.show(
                "Mật khẩu mới quá đơn giản hoặc trùng mã NV/tài khoản. Vui lòng chọn mật khẩu an toàn hơn.",
                "error");
            document.getElementById('new_password').focus();
            return;
        }

        // Gửi dữ liệu lên máy chủ
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>⏳ Đang xử lý...</span>';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const succMsg = data.message || (window.t ? window.t('toast_saved_success') : "Đổi mật khẩu thành công!");
                Toast.flash(succMsg, "success");
                Toast.show(succMsg, "success");
                submitBtn.innerHTML = '<span>✔️ Đã đổi thành công!</span>';

                setTimeout(() => {
                    window.location.href = data.redirect || '/WEB_BOBIN/public/index.php';
                }, 1000);
            } else {
                Toast.show(data.message || "Không thể đổi mật khẩu.", "error");
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        } catch (err) {
            console.error("Lỗi đổi mật khẩu:", err);
            // Fallback submit form truyền thống nếu fetch bị lỗi
            form.submit();
        }
    });
    </script>
</body>

</html>