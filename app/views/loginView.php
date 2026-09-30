<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Bobin | SMC</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/login.css?v=5">
    <link rel="icon" href="data:,">
</head>

<body>

    <div class="login-split-wrapper">
        <!-- ================= CỘT TRÁI: SHOWCASE & CỔNG THEO DÕI NHANH ================= -->
        <div class="showcase-section">
            <div class="showcase-content">
                <div class="brand-badge">
                    <span class="pulse-dot"></span> PLASTIC EXTRUSION BUILDING
                </div>

                <h1 class="showcase-title">HỆ THỐNG QUẢN LÝ BOBIN</h1>
                <p class="showcase-desc">
                    Nền tảng kiểm soát thông tin thời gian thực giữa các công đoạn <strong>Nhóm Đùn</strong>,
                    <strong>Nhóm QC</strong>, <strong>Nhóm Cuộn</strong>.
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
                            <span>Theo dõi nhanh hệ thống</span>
                        </div>
                        <span class="public-badge">Truy cập tự do</span>
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
                                <div class="portal-name">Danh sách chi tiết Bobin</div>
                                <div class="portal-sub">Biểu đồ tổng quan, số lượng trạng thái, tra cứu vị trí RACK & QC
                                </div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>

                        <!-- Card 2: Lịch sử hoạt động -->
                        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinHistoryView"
                            class="portal-card card-history">
                            <div class="portal-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v5h5"></path>
                                    <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"></path>
                                    <path d="M12 7v5l4 2"></path>
                                </svg>
                            </div>
                            <div class="portal-info">
                                <div class="portal-name">Lịch sử hoạt động</div>
                                <div class="portal-sub">Tra cứu nhật ký luân chuyển Bobin theo khoảng ngày & quét mã QR
                                </div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>

                        <!-- Card 3: Danh sách chờ hủy -->
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
                                <div class="portal-name">Danh sách Bobin chờ hủy</div>
                                <div class="portal-sub">Theo dõi các Bobin báo lỗi, chờ phê duyệt hủy từ phân xưởng
                                </div>
                            </div>
                            <div class="portal-arrow">→</div>
                        </a>
                    </div>
                </div>

                <div class="showcase-footer">
                    <div class="stat-pill">⚡ Quét QR tốc độ cao</div>
                    <div class="stat-pill">📊 Báo cáo biểu đồ thời gian thực</div>
                    <div class="stat-pill">🏭 Đồng bộ liên tục theo ca</div>
                </div>
            </div>
        </div>

        <!-- ================= CỘT PHẢI: FORM ĐĂNG NHẬP ================= -->
        <div class="login-panel">
            <div class="login-box">
                <div class="login-header">
                    <img src="/WEB_BOBIN/public/assets/images/smcLogo.png" alt="SMC Logo" class="login-logo">
                    <h2>ĐĂNG NHẬP HỆ THỐNG</h2>
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
                        <label for="username">Tên đăng nhập / Mã nhân viên</label>
                        <div class="input-icon-wrapper">
                            <span class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="username" name="username"
                                placeholder="Ví dụ: dun01, qc01 hoặc admin..." required autocomplete="username"
                                autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label for="password">Mật khẩu bảo mật</label>
                        </div>
                        <div class="input-icon-wrapper">
                            <span class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </span>
                            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required
                                autocomplete="current-password">

                            <button type="button" class="btn-toggle-pwd" id="togglePwd" title="Ẩn/Hiện mật khẩu">
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
                        <span>Đăng nhập vào hệ thống</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="login-copyright">
                    © <?= date('Y') ?> SMC Factory. Đã kích hoạt bảo vệ phiên làm việc.
                </div>
            </div>
        </div>
    </div>

    <script>
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
    </script>
</body>

</html>