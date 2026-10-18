<?php
// File: app/views/components/periodicLogoutModal.php
// Component Yêu cầu Đăng xuất Định kỳ tại các thời điểm cố định (TASK-048):
// - 6:05 AM (06:05)
// - 2:05 PM (14:05)
// - 4:40 PM (16:40)
// - 10:05 PM (22:05)
// Mục đích: Đảm bảo User 2 không dùng tài khoản của User 1 để thực hiện tác vụ.
// Áp dụng đối với toàn bộ User đã đăng nhập. Tự động đăng xuất sau 60 giây nếu không xác nhận.

if (defined('WEB_BOBIN_PERIODIC_LOGOUT_RENDERED')) {
    return;
}

if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    return;
}

define('WEB_BOBIN_PERIODIC_LOGOUT_RENDERED', true);

require_once ROOT_PATH . '/app/core/AuthHelper.php';
require_once ROOT_PATH . '/app/core/Language.php';

$plUser       = $_SESSION['user'];
$plUserId     = (int)($plUser['id'] ?? 0);
$plUserName   = $plUser['employee_name'] ?? 'Người dùng';
$plEmpCode    = $plUser['employee_code'] ?? ($plUser['username'] ?? '');
$plUserRole   = strtoupper($plUser['role'] ?? '');

$justLoggedIn = !empty($_SESSION['user']['just_logged_in']);
if ($justLoggedIn) {
    unset($_SESSION['user']['just_logged_in']);
}

$plConfig = [
    'userId'            => $plUserId,
    'justLoggedIn'      => $justLoggedIn,
    'countdownTotalSec' => AuthHelper::PERIODIC_LOGOUT_COUNTDOWN_SECONDS,
    'schedules'         => AuthHelper::PERIODIC_LOGOUT_SCHEDULES,
    'logoutUrl'         => BASE_URL . '/index.php?url=auth/logout',
];
?>
<style>
    /* ================= PERIODIC LOGOUT MODAL (TASK-048) ================= */
    .periodic-logout-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2147483640;
        background: rgba(15, 23, 42, 0.82);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        transition: opacity 0.22s ease;
    }

    .periodic-logout-backdrop.is-open {
        display: flex;
        opacity: 1;
    }

    .periodic-logout-dialog {
        width: 100%;
        max-width: 480px;
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(245, 158, 11, 0.35);
        overflow: hidden;
        transform: translateY(12px) scale(0.97);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0f172a;
    }

    .periodic-logout-backdrop.is-open .periodic-logout-dialog {
        transform: translateY(0) scale(1);
    }

    .periodic-logout-dialog.shake-attention {
        animation: plShake 0.38s ease;
    }

    @keyframes plShake {

        0%,
        100% {
            transform: translateX(0);
        }

        20%,
        60% {
            transform: translateX(-7px);
        }

        40%,
        80% {
            transform: translateX(7px);
        }
    }

    .pl-header {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
        color: #ffffff;
        padding: 20px 24px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        transition: background 0.3s ease;
    }

    .periodic-logout-dialog.is-urgent .pl-header {
        background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    }

    .pl-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
        animation: plPulseIcon 1.8s infinite ease-in-out;
    }

    @keyframes plPulseIcon {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.08);
        }
    }

    .pl-header-text {
        flex: 1;
        min-width: 0;
    }

    .pl-title {
        margin: 0;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.25;
    }

    .pl-subtitle {
        margin: 4px 0 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.92);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .pl-checkpoint-pill {
        background: rgba(255, 255, 255, 0.24);
        padding: 2px 9px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 12.5px;
        letter-spacing: 0.02em;
    }

    .pl-body {
        padding: 20px 24px 22px;
    }

    .pl-user-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 11px 14px;
        margin-bottom: 14px;
    }

    .pl-user-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .pl-user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #0284c7;
        color: #ffffff;
        font-weight: 800;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .pl-user-meta {
        min-width: 0;
    }

    .pl-user-name {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pl-user-code {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }

    .pl-schedule-tags {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .pl-schedule-tag {
        font-size: 11.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .pl-schedule-tag.active-slot {
        background: #fef3c7;
        color: #b45309;
        border-color: #f59e0b;
    }

    .pl-message {
        font-size: 14px;
        line-height: 1.55;
        color: #334155;
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        padding: 11px 14px;
        border-radius: 0 10px 10px 0;
        margin-bottom: 18px;
    }

    .periodic-logout-dialog.is-urgent .pl-message {
        background: #fef2f2;
        border-left-color: #ef4444;
        color: #7f1d1d;
    }

    /* Countdown Counter Box */
    .pl-counter-card {
        display: flex;
        align-items: center;
        gap: 18px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 18px;
        margin-bottom: 18px;
        transition: all 0.25s ease;
    }

    .periodic-logout-dialog.is-urgent .pl-counter-card {
        background: #fff1f2;
        border-color: #fecdd3;
    }

    .pl-ring-wrap {
        position: relative;
        width: 74px;
        height: 74px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pl-ring-svg {
        width: 74px;
        height: 74px;
        transform: rotate(-90deg);
    }

    .pl-ring-bg {
        fill: none;
        stroke: #e2e8f0;
        stroke-width: 6;
    }

    .pl-ring-fg {
        fill: none;
        stroke: #f59e0b;
        stroke-width: 6;
        stroke-linecap: round;
        stroke-dasharray: 194.78;
        stroke-dashoffset: 0;
        transition: stroke-dashoffset 0.25s linear, stroke 0.25s ease;
    }

    .periodic-logout-dialog.is-urgent .pl-ring-fg {
        stroke: #ef4444;
    }

    .pl-ring-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .pl-sec-num {
        font-size: 23px;
        font-weight: 900;
        color: #d97706;
        font-variant-numeric: tabular-nums;
    }

    .periodic-logout-dialog.is-urgent .pl-sec-num {
        color: #dc2626;
        animation: plBlinkUrgent 0.8s infinite;
    }

    @keyframes plBlinkUrgent {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .pl-sec-unit {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        margin-top: 2px;
    }

    .pl-counter-info {
        flex: 1;
        min-width: 0;
    }

    .pl-counter-label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    .pl-counter-highlight {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .pl-counter-highlight strong {
        color: #d97706;
        font-size: 16px;
        font-variant-numeric: tabular-nums;
    }

    .periodic-logout-dialog.is-urgent .pl-counter-highlight strong {
        color: #dc2626;
    }

    .pl-progress-track {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .pl-progress-bar {
        height: 100%;
        width: 100%;
        background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        border-radius: 999px;
        transition: width 0.25s linear, background 0.25s ease;
    }

    .periodic-logout-dialog.is-urgent .pl-progress-bar {
        background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
    }

    .pl-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .pl-btn-confirm {
        width: 100%;
        border: none;
        border-radius: 12px;
        padding: 14px 20px;
        font-size: 15px;
        font-weight: 800;
        color: #ffffff;
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 8px 20px -4px rgba(234, 88, 12, 0.45);
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    .pl-btn-confirm:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
        box-shadow: 0 12px 24px -4px rgba(234, 88, 12, 0.55);
    }

    .pl-btn-confirm:active {
        transform: translateY(0);
    }

    .pl-btn-confirm:disabled {
        opacity: 0.75;
        cursor: wait;
    }
</style>

<!-- DIALOG YÊU CẦU ĐĂNG XUẤT ĐỊNH KỲ (TASK-048) -->
<div class="periodic-logout-backdrop" id="periodicLogoutBackdrop" role="dialog" aria-modal="true"
    aria-labelledby="periodicLogoutTitle">
    <div class="periodic-logout-dialog" id="periodicLogoutDialog">
        <div class="pl-header">
            <div class="pl-header-icon" aria-hidden="true">⏰</div>
            <div class="pl-header-text">
                <h3 class="pl-title" id="periodicLogoutTitle" data-i18n="periodic_logout_title">
                    <?= __('periodic_logout_title', 'Yêu cầu đăng xuất định kỳ') ?>
                </h3>
                <div class="pl-subtitle">
                    <span
                        data-i18n="periodic_logout_checkpoint_label"><?= __('periodic_logout_checkpoint_label', 'Mốc kiểm soát cố định:') ?></span>
                    <span class="pl-checkpoint-pill" id="periodicLogoutCheckpointBadge">6:05 AM</span>
                </div>
            </div>
        </div>

        <div class="pl-body">
            <!-- Thông tin tài khoản đang đăng nhập -->
            <div class="pl-user-box">
                <div class="pl-user-left">
                    <div class="pl-user-avatar">
                        <?= htmlspecialchars(mb_substr(trim($plUserName), 0, 1, 'UTF-8') ?: 'U') ?>
                    </div>
                    <div class="pl-user-meta">
                        <div class="pl-user-name"><?= htmlspecialchars($plUserName) ?></div>
                        <div class="pl-user-code"><?= htmlspecialchars($plEmpCode) ?> •
                            <?= htmlspecialchars($plUserRole) ?></div>
                    </div>
                </div>
                <span
                    style="font-size:11.5px; font-weight:700; color:#dc2626; background:#fef2f2; border:1px solid #fecdd3; padding:3px 8px; border-radius:6px;"
                    data-i18n="periodic_logout_active_session">
                    <?= __('periodic_logout_active_session', 'Phiên đang mở') ?>
                </span>
            </div>

            <!-- Các khung giờ đăng xuất cố định trong ngày -->
            <div class="pl-schedule-tags" id="periodicLogoutScheduleTags">
                <span class="pl-schedule-tag" data-slot="06:05">🕕 6:05 AM</span>
                <span class="pl-schedule-tag" data-slot="14:05">🕑 2:05 PM</span>
                <span class="pl-schedule-tag" data-slot="16:40">🕓 4:40 PM</span>
                <span class="pl-schedule-tag" data-slot="22:05">🕙 10:05 PM</span>
            </div>

            <!-- Nội dung thông báo lý do -->
            <div class="pl-message" data-i18n="periodic_logout_desc">
                <?= __('periodic_logout_desc', 'Đã đến thời điểm đăng xuất định kỳ của hệ thống. Để đảm bảo an toàn và không dùng chung tài khoản giữa các nhân viên/ca làm việc, vui lòng xác nhận đăng xuất ngay.') ?>
            </div>

            <!-- Bộ đếm ngược 60 giây (1 phút) -->
            <div class="pl-counter-card">
                <div class="pl-ring-wrap">
                    <svg class="pl-ring-svg" viewBox="0 0 74 74">
                        <circle class="pl-ring-bg" cx="37" cy="37" r="31"></circle>
                        <circle class="pl-ring-fg" id="periodicLogoutRingCircle" cx="37" cy="37" r="31"></circle>
                    </svg>
                    <div class="pl-ring-center">
                        <span class="pl-sec-num" id="periodicLogoutSecNum">60</span>
                        <span class="pl-sec-unit"
                            data-i18n="periodic_logout_sec_short"><?= __('periodic_logout_sec_short', 'giây') ?></span>
                    </div>
                </div>

                <div class="pl-counter-info">
                    <div class="pl-counter-label" data-i18n="periodic_logout_auto_notice">
                        <?= __('periodic_logout_auto_notice', 'Nếu không xác nhận, hệ thống sẽ tự động đăng xuất sau:') ?>
                    </div>
                    <div class="pl-counter-highlight">
                        <strong id="periodicLogoutSecInline">60</strong>
                        <span
                            data-i18n="periodic_logout_seconds_unit"><?= __('periodic_logout_seconds_unit', 'giây (Tối đa 1 phút)') ?></span>
                    </div>
                    <div class="pl-progress-track">
                        <div class="pl-progress-bar" id="periodicLogoutProgressBar"></div>
                    </div>
                </div>
            </div>

            <!-- Nút xác nhận đăng xuất -->
            <div class="pl-actions">
                <button type="button" class="pl-btn-confirm" id="btnConfirmPeriodicLogout">
                    <span>🚪</span>
                    <span id="btnConfirmPeriodicLogoutText" data-i18n="periodic_logout_btn_confirm">
                        <?= __('periodic_logout_btn_confirm', 'Xác nhận đăng xuất ngay') ?>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        if (window.__WEB_BOBIN_PERIODIC_LOGOUT_INIT__) return;
        window.__WEB_BOBIN_PERIODIC_LOGOUT_INIT__ = true;

        const config = <?= json_encode($plConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const TOTAL_COUNTDOWN_SEC = Number(config.countdownTotalSec) || 60;
        const RING_CIRCUMFERENCE = 2 * Math.PI * 31; // r=31 => ~194.78
        const USER_ID = Number(config.userId) || 0;

        const STORAGE_ACTIVE_KEY = 'webbobin_periodic_logout_active_' + USER_ID;
        const STORAGE_SKIP_SLOT_KEY = 'webbobin_periodic_logout_skip_slot_' + USER_ID;

        const backdropEl = document.getElementById('periodicLogoutBackdrop');
        const dialogEl = document.getElementById('periodicLogoutDialog');
        const badgeEl = document.getElementById('periodicLogoutCheckpointBadge');
        const secNumEl = document.getElementById('periodicLogoutSecNum');
        const secInlineEl = document.getElementById('periodicLogoutSecInline');
        const ringEl = document.getElementById('periodicLogoutRingCircle');
        const barEl = document.getElementById('periodicLogoutProgressBar');
        const btnConfirm = document.getElementById('btnConfirmPeriodicLogout');
        const btnTextEl = document.getElementById('btnConfirmPeriodicLogoutText');

        const schedules = Array.isArray(config.schedules) ? config.schedules : [{
                hour: 6,
                minute: 5,
                label: '6:05 AM',
                time_24: '06:05'
            },
            {
                hour: 14,
                minute: 5,
                label: '2:05 PM',
                time_24: '14:05'
            },
            {
                hour: 16,
                minute: 40,
                label: '4:40 PM',
                time_24: '16:40'
            },
            {
                hour: 22,
                minute: 5,
                label: '10:05 PM',
                time_24: '22:05'
            }
        ];

        let isModalOpen = false;
        let isLoggingOut = false;
        let countdownEndPerfMs = 0;
        let activeCheckpointLabel = '6:05 AM';
        let activeSlot24 = '';
        let activeSlotKey = '';
        let countdownTimerId = null;
        let lastCheckedWallMs = Date.now();

        function pad2(n) {
            return n < 10 ? '0' + n : String(n);
        }

        function buildMinuteKey(d) {
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()) +
                '_' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
        }

        // Nếu User vừa mới đăng nhập xong (ví dụ đăng nhập lại lúc 14:05:20 sau khi vừa đăng xuất),
        // ghi nhận phút hiện tại vào skip_slot để không bật lại dialog trong đúng phút vừa đăng nhập đó.
        if (config.justLoggedIn) {
            try {
                sessionStorage.removeItem(STORAGE_ACTIVE_KEY);
                sessionStorage.setItem(STORAGE_SKIP_SLOT_KEY, buildMinuteKey(new Date()));
            } catch (e) {}
        }

        function highlightActiveSlot(slot24, label) {
            if (badgeEl && label) {
                badgeEl.textContent = label;
            }
            const tags = document.querySelectorAll('#periodicLogoutScheduleTags .pl-schedule-tag');
            tags.forEach(tag => {
                const slot = tag.getAttribute('data-slot');
                if ((slot24 && slot === slot24) || (label && tag.textContent.includes(label))) {
                    tag.classList.add('active-slot');
                } else {
                    tag.classList.remove('active-slot');
                }
            });
        }

        function updateCounterUI(secLeft) {
            const safeSec = Math.max(0, Math.min(TOTAL_COUNTDOWN_SEC, Math.ceil(secLeft)));
            if (secNumEl) secNumEl.textContent = String(safeSec);
            if (secInlineEl) secInlineEl.textContent = String(safeSec);

            const ratio = Math.max(0, Math.min(1, secLeft / TOTAL_COUNTDOWN_SEC));
            if (ringEl) {
                const offset = RING_CIRCUMFERENCE * (1 - ratio);
                ringEl.style.strokeDasharray = String(RING_CIRCUMFERENCE);
                ringEl.style.strokeDashoffset = String(offset);
            }
            if (barEl) {
                barEl.style.width = (ratio * 100).toFixed(1) + '%';
            }
            if (dialogEl) {
                if (safeSec <= 15) {
                    dialogEl.classList.add('is-urgent');
                } else {
                    dialogEl.classList.remove('is-urgent');
                }
            }
        }

        function executePeriodicLogout(reason) {
            if (isLoggingOut) return;
            isLoggingOut = true;

            if (countdownTimerId) {
                clearInterval(countdownTimerId);
                countdownTimerId = null;
            }

            try {
                sessionStorage.removeItem(STORAGE_ACTIVE_KEY);
            } catch (e) {}

            if (btnConfirm) {
                btnConfirm.disabled = true;
            }
            if (btnTextEl) {
                const loggingOutText = (typeof window.t === 'function') ?
                    window.t('periodic_logout_processing', 'Đang thực hiện đăng xuất...') :
                    'Đang thực hiện đăng xuất...';
                btnTextEl.textContent = loggingOutText;
            }

            const sep = config.logoutUrl.indexOf('?') !== -1 ? '&' : '?';
            const targetUrl = config.logoutUrl +
                sep + 'reason=' + encodeURIComponent(reason || 'periodic_confirm') +
                '&cp=' + encodeURIComponent(activeCheckpointLabel || '');

            window.location.replace(targetUrl);
        }

        function openPeriodicLogoutDialog(checkpointLabel, initialSeconds, slot24, slotKey) {
            if (isLoggingOut) return;

            activeCheckpointLabel = checkpointLabel || activeCheckpointLabel || '6:05 AM';
            activeSlot24 = slot24 || activeSlot24 || '';
            activeSlotKey = slotKey || activeSlotKey || buildMinuteKey(new Date());

            const startSec = (typeof initialSeconds === 'number' && initialSeconds > 0) ?
                Math.min(TOTAL_COUNTDOWN_SEC, initialSeconds) :
                TOTAL_COUNTDOWN_SEC;

            if (!isModalOpen) {
                isModalOpen = true;
                // Sử dụng performance.now() để đếm ngược 60 giây chính xác tuyệt đối, không bị nhảy cóc khi đổi giờ máy tính
                countdownEndPerfMs = performance.now() + (startSec * 1000);
                highlightActiveSlot(activeSlot24, activeCheckpointLabel);

                try {
                    sessionStorage.setItem(STORAGE_ACTIVE_KEY, JSON.stringify({
                        label: activeCheckpointLabel,
                        slot24: activeSlot24,
                        slotKey: activeSlotKey,
                        wallDeadlineMs: Date.now() + (startSec * 1000)
                    }));
                } catch (e) {}

                if (document.activeElement && typeof document.activeElement.blur === 'function') {
                    document.activeElement.blur();
                }

                if (backdropEl) {
                    backdropEl.classList.add('is-open');
                }
                if (btnConfirm) {
                    setTimeout(() => btnConfirm.focus(), 60);
                }
            }

            const tickCountdown = function() {
                const remainingMs = countdownEndPerfMs - performance.now();
                const remainingSec = remainingMs / 1000;
                updateCounterUI(remainingSec);

                if (remainingMs <= 0) {
                    executePeriodicLogout('periodic_auto');
                }
            };

            tickCountdown();
            if (!countdownTimerId) {
                countdownTimerId = setInterval(tickCountdown, 200);
            }
        }

        // Khôi phục Dialog nếu đang đếm ngược dở dang và người dùng F5 / chuyển trang
        function restoreActiveDialogIfAny() {
            try {
                const raw = sessionStorage.getItem(STORAGE_ACTIVE_KEY);
                if (!raw) return;
                const saved = JSON.parse(raw);
                if (!saved || !saved.wallDeadlineMs) {
                    sessionStorage.removeItem(STORAGE_ACTIVE_KEY);
                    return;
                }
                const remMs = Number(saved.wallDeadlineMs) - Date.now();
                // Chỉ khôi phục nếu thời gian còn lại nằm trong khoảng hợp lệ (1s .. 60s)
                if (remMs > 0 && remMs <= (TOTAL_COUNTDOWN_SEC * 1000 + 2000)) {
                    const remSec = Math.max(1, Math.min(TOTAL_COUNTDOWN_SEC, Math.ceil(remMs / 1000)));
                    openPeriodicLogoutDialog(saved.label, remSec, saved.slot24 || '', saved.slotKey || '');
                } else {
                    sessionStorage.removeItem(STORAGE_ACTIVE_KEY);
                }
            } catch (e) {}
        }

        /**
         * Kiểm tra xem thời gian hiện tại có rơi vào đúng 1 trong 4 mốc cố định (6:05 AM, 2:05 PM, 4:40 PM, 10:05 PM) không
         */
        function checkScheduleNow() {
            if (isLoggingOut || isModalOpen) return;

            const now = new Date();
            const nowMs = now.getTime();
            const currentMinKey = buildMinuteKey(now);

            // Nếu đã sang phút khác với phút vừa đăng nhập, xóa cờ skip để lần tới chạm mốc vẫn hiện bình thường
            try {
                const skipKey = sessionStorage.getItem(STORAGE_SKIP_SLOT_KEY);
                if (skipKey && skipKey !== currentMinKey) {
                    sessionStorage.removeItem(STORAGE_SKIP_SLOT_KEY);
                }
            } catch (e) {}

            const curHour = now.getHours();
            const curMin = now.getMinutes();

            // 1. Kiểm tra khớp trực tiếp phút hiện tại với 4 mốc cố định (06:05, 14:05, 16:40, 22:05)
            for (let i = 0; i < schedules.length; i++) {
                const s = schedules[i];
                if (curHour === Number(s.hour) && curMin === Number(s.minute)) {
                    let skipKey = null;
                    try {
                        skipKey = sessionStorage.getItem(STORAGE_SKIP_SLOT_KEY);
                    } catch (e) {}

                    if (skipKey === currentMinKey) {
                        lastCheckedWallMs = nowMs;
                        return;
                    }

                    openPeriodicLogoutDialog(s.label, TOTAL_COUNTDOWN_SEC, s.time_24, currentMinKey);
                    lastCheckedWallMs = nowMs;
                    return;
                }
            }

            // 2. Trường hợp tab trình duyệt bị ẩn ở chế độ nền (background throttling) vừa đi qua mốc cố định trong vòng 2 phút gần nhất
            const deltaMs = nowMs - lastCheckedWallMs;
            if (deltaMs > 1500 && deltaMs <= 120000) {
                for (let i = 0; i < schedules.length; i++) {
                    const s = schedules[i];
                    const cpDate = new Date(
                        now.getFullYear(),
                        now.getMonth(),
                        now.getDate(),
                        Number(s.hour),
                        Number(s.minute),
                        0,
                        0
                    );
                    const cpMs = cpDate.getTime();
                    if (lastCheckedWallMs < cpMs && nowMs >= cpMs) {
                        const slotKey = buildMinuteKey(cpDate);
                        let skipKey = null;
                        try {
                            skipKey = sessionStorage.getItem(STORAGE_SKIP_SLOT_KEY);
                        } catch (e) {}

                        if (skipKey !== slotKey) {
                            openPeriodicLogoutDialog(s.label, TOTAL_COUNTDOWN_SEC, s.time_24, slotKey);
                            lastCheckedWallMs = nowMs;
                            return;
                        }
                    }
                }
            }

            lastCheckedWallMs = nowMs;
        }

        // Sự kiện click nút "Xác nhận đăng xuất ngay"
        if (btnConfirm) {
            btnConfirm.addEventListener('click', function(e) {
                e.preventDefault();
                executePeriodicLogout('periodic_confirm');
            });
        }

        // Chặn đóng dialog khi click ra vùng nền ngoài hoặc nhấn phím Escape
        if (backdropEl) {
            backdropEl.addEventListener('click', function(e) {
                if (e.target === backdropEl && dialogEl) {
                    dialogEl.classList.remove('shake-attention');
                    void dialogEl.offsetWidth;
                    dialogEl.classList.add('shake-attention');
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (!isModalOpen) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                if (dialogEl) {
                    dialogEl.classList.remove('shake-attention');
                    void dialogEl.offsetWidth;
                    dialogEl.classList.add('shake-attention');
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                executePeriodicLogout('periodic_confirm');
            }
        }, true);

        // Khôi phục nếu đang đếm ngược hoặc kiểm tra ngay thời điểm hiện tại
        restoreActiveDialogIfAny();
        checkScheduleNow();

        // Kiểm tra định kỳ mỗi 1 giây và ngay khi tab quay lại trạng thái hiển thị
        setInterval(checkScheduleNow, 1000);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkScheduleNow();
            }
        });
        window.addEventListener('focus', checkScheduleNow);

        // Hàm hỗ trợ kiểm thử nhanh trên giao diện (nếu cần gọi từ Console)
        window.triggerPeriodicLogoutModal = function(customLabel) {
            openPeriodicLogoutDialog(customLabel || '2:05 PM', TOTAL_COUNTDOWN_SEC, '');
        };
    })();
</script>