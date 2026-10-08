/**
 * ==============================================================================
 * toast.js - HỆ THỐNG TOAST THÔNG BÁO TOÀN CỤC (GLOBAL UNIFIED TOAST SYSTEM)
 * ==============================================================================
 * Hoàn toàn Local Intranet - 100% Offline, không phụ thuộc CDN hay thư viện ngoài.
 * Hỗ trợ các trạng thái: Thành công (Success), Lỗi (Error), Cảnh báo (Warning), Thông tin (Info).
 * 
 * Tính năng chính:
 * 1. window.Toast & window.showToast thống nhất toàn bộ trang web.
 * 2. Tự động tiêm CSS độc lập (#unified-toast-styles), tối ưu Responsive (Desktop / Mobile / Tablet).
 * 3. Hỗ trợ Flash Toast (sessionStorage: 'bobin_toast_flash') hiển thị sau khi reload trang.
 * 4. Tự động nhận diện thông báo từ URL query (?msg=...&msg_type=..., ?error=..., ?success=...).
 * 5. Ngăn tràn giao diện, hỗ trợ nút đóng tức thì (✕) và thanh tiến trình thời gian.
 * 6. Tích hợp đa ngôn ngữ (window.t).
 * ==============================================================================
 */

(function (window, document) {
    'use strict';

    // 1. TIÊM CSS TỰ ĐỘNG NẾU CHƯA CÓ TRÊN TRANG
    function injectToastStyles() {
        if (document.getElementById('unified-toast-styles')) return;

        const style = document.createElement('style');
        style.id = 'unified-toast-styles';
        style.textContent = `
            /* Khung chứa danh sách Toast cố định góc trên */
            #bobin-toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 999999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
                max-width: 440px;
                width: calc(100vw - 40px);
                box-sizing: border-box;
            }

            /* Thẻ Toast hiển thị */
            .bobin-toast-item {
                pointer-events: auto;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 12px 16px;
                border-radius: 10px;
                background: #1e293b;
                color: #ffffff;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.25);
                font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
                font-size: 13.5px;
                font-weight: 600;
                line-height: 1.45;
                box-sizing: border-box;
                opacity: 0;
                transform: translateY(-20px) scale(0.96);
                transition: opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1), transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
                border: 1px solid rgba(255, 255, 255, 0.12);
                word-break: break-word;
                position: relative;
                overflow: hidden;
            }

            .bobin-toast-item.show {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            .bobin-toast-item.hide {
                opacity: 0;
                transform: translateY(-16px) scale(0.92);
            }

            /* Icon trạng thái */
            .bobin-toast-icon {
                font-size: 18px;
                line-height: 1;
                flex-shrink: 0;
                margin-top: 1px;
            }

            /* Nội dung thông báo */
            .bobin-toast-content {
                flex: 1 1 auto;
                min-width: 0;
                color: #ffffff;
            }

            .bobin-toast-title {
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 2px;
                opacity: 0.9;
            }

            .bobin-toast-text {
                font-size: 13.5px;
                font-weight: 500;
                color: #f8fafc;
            }

            /* Nút đóng */
            .bobin-toast-close {
                background: none;
                border: none;
                color: rgba(255, 255, 255, 0.7);
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                padding: 2px 4px;
                margin-left: 4px;
                flex-shrink: 0;
                transition: color 0.15s, transform 0.15s;
                border-radius: 4px;
            }

            .bobin-toast-close:hover {
                color: #ffffff;
                transform: scale(1.15);
            }

            /* Màu sắc theo từng trạng thái */
            /* 1. THÀNH CÔNG (Success) */
            .bobin-toast-item.toast-success {
                background: linear-gradient(135deg, #065f46 0%, #047857 100%);
                border-left: 5px solid #10b981;
                box-shadow: 0 10px 25px -5px rgba(6, 95, 70, 0.4);
            }
            .bobin-toast-item.toast-success .bobin-toast-title {
                color: #6ee7b7;
            }

            /* 2. LỖI (Error) */
            .bobin-toast-item.toast-error {
                background: linear-gradient(135deg, #991b1b 0%, #b91c1c 100%);
                border-left: 5px solid #ef4444;
                box-shadow: 0 10px 25px -5px rgba(153, 27, 27, 0.4);
            }
            .bobin-toast-item.toast-error .bobin-toast-title {
                color: #fca5a5;
            }

            /* 3. CẢNH BÁO (Warning) */
            .bobin-toast-item.toast-warning {
                background: linear-gradient(135deg, #92400e 0%, #b45309 100%);
                border-left: 5px solid #f59e0b;
                box-shadow: 0 10px 25px -5px rgba(146, 64, 14, 0.4);
            }
            .bobin-toast-item.toast-warning .bobin-toast-title {
                color: #fde68a;
            }

            /* 4. THÔNG TIN (Info) */
            .bobin-toast-item.toast-info {
                background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
                border-left: 5px solid #60a5fa;
                box-shadow: 0 10px 25px -5px rgba(30, 64, 175, 0.4);
            }
            .bobin-toast-item.toast-info .bobin-toast-title {
                color: #bfdbfe;
            }

            /* Tương thích ngược với các class cũ .toast-message */
            .toast-message {
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                z-index: 999999 !important;
                padding: 12px 18px !important;
                border-radius: 8px !important;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3) !important;
                font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif) !important;
                font-size: 13.5px !important;
                font-weight: 600 !important;
                color: #ffffff !important;
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                opacity: 0 !important;
                transform: translateY(-20px) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                pointer-events: auto !important;
                background-color: #1e293b !important;
            }
            .toast-message.show {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
            .toast-message.toast-success { background-color: #16a34a !important; }
            .toast-message.toast-error   { background-color: #dc2626 !important; }
            .toast-message.toast-warning { background-color: #f59e0b !important; }
            .toast-message.toast-info    { background-color: #2563eb !important; }

            /* Responsive cho Mobile & Tablet */
            @media (max-width: 640px) {
                #bobin-toast-container {
                    top: 14px;
                    left: 14px;
                    right: 14px;
                    width: calc(100vw - 28px);
                    max-width: none;
                }
                .bobin-toast-item {
                    padding: 11px 14px;
                    font-size: 13px;
                }
                .toast-message {
                    top: 14px !important;
                    left: 14px !important;
                    right: 14px !important;
                    width: auto !important;
                    justify-content: center !important;
                }
            }
        `;

        (document.head || document.documentElement).appendChild(style);
    }

    // 2. TẠO HOẶC LẤY CONTAINER CHỨA TOAST
    function getToastContainer() {
        injectToastStyles();
        let container = document.getElementById('bobin-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'bobin-toast-container';
            (document.body || document.documentElement).appendChild(container);
        }
        return container;
    }

    // Chuẩn hóa loại toast (type normalization)
    function normalizeType(type, message) {
        if (type === true || type === 'success') return 'success';
        if (type === false || type === 'error' || type === 'danger') return 'error';
        if (type === 'warn' || type === 'warning') return 'warning';
        if (type === 'info') return 'info';

        // Tự động suy luận từ nội dung tin nhắn nếu type không truyền hoặc là 'info'
        if (typeof message === 'string') {
            const trimmed = message.trim();
            if (trimmed.startsWith('❌') || trimmed.toLowerCase().includes('lỗi') || trimmed.toLowerCase().includes('thất bại') || trimmed.toLowerCase().includes('error')) {
                return 'error';
            }
            if (trimmed.startsWith('⚠️') || trimmed.toLowerCase().includes('cảnh báo') || trimmed.toLowerCase().includes('vui lòng')) {
                return 'warning';
            }
            if (trimmed.startsWith('✅') || trimmed.toLowerCase().includes('thành công') || trimmed.toLowerCase().includes('success')) {
                return 'success';
            }
        }

        return 'info';
    }

    // Lấy Icon và Title theo loại
    function getTypeDetails(type) {
        const lang = (window.Language && window.Language.current) || (document.documentElement.lang) || 'vi';
        const hasT = typeof window.t === 'function';

        switch (type) {
            case 'success':
                return {
                    icon: '✅',
                    title: hasT ? window.t('success', 'Thành công') : 'Thành công'
                };
            case 'error':
                return {
                    icon: '❌',
                    title: hasT ? window.t('error', 'Lỗi') : 'Lỗi hệ thống'
                };
            case 'warning':
                return {
                    icon: '⚠️',
                    title: hasT ? window.t('warning', 'Cảnh báo') : 'Cảnh báo'
                };
            case 'info':
            default:
                return {
                    icon: 'ℹ️',
                    title: hasT ? window.t('info', 'Thông báo') : 'Thông báo'
                };
        }
    }

    // Làm sạch tin nhắn nếu đã có sẵn icon ở đầu chuỗi
    function sanitizeMessage(msg) {
        if (!msg) return '';
        let cleaned = String(msg).trim();
        // Loại bỏ icon trùng lặp ở đầu nếu có
        cleaned = cleaned.replace(/^([✅❌⚠️ℹ️⚡💬]|Lỗi:|Lỗi\s*:)\s*/u, '');
        return cleaned.trim() || String(msg);
    }

    // 3. ĐỐI TƯỢNG TOAST TOÀN CỤC CHÍNH
    const Toast = {
        /**
         * Hiển thị Toast thông báo
         * @param {string} message - Nội dung thông báo
         * @param {string|boolean} type - 'success' | 'error' | 'warning' | 'info' (hoặc true: success, false: error)
         * @param {number} duration - Thời gian hiển thị tính bằng mili-giây (mặc định 3500ms)
         */
        show(message, type = 'info', duration = 3500) {
            if (!message) return null;

            const normType = normalizeType(type, message);
            const { icon, title } = getTypeDetails(normType);
            const cleanText = sanitizeMessage(message);

            const container = getToastContainer();

            const toastItem = document.createElement('div');
            toastItem.className = `bobin-toast-item toast-${normType}`;
            toastItem.setAttribute('role', 'alert');
            toastItem.setAttribute('aria-live', 'assertive');

            toastItem.innerHTML = `
                <div class="bobin-toast-icon">${icon}</div>
                <div class="bobin-toast-content">
                    <div class="bobin-toast-title">${title}</div>
                    <div class="bobin-toast-text">${cleanText}</div>
                </div>
                <button type="button" class="bobin-toast-close" title="Đóng" aria-label="Close">✕</button>
            `;

            container.appendChild(toastItem);

            // Kích hoạt hiệu ứng xuất hiện (Smooth transition)
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toastItem.classList.add('show');
                });
            });

            // Hàm đóng toast an toàn
            let isClosed = false;
            const closeToast = () => {
                if (isClosed) return;
                isClosed = true;
                toastItem.classList.remove('show');
                toastItem.classList.add('hide');
                setTimeout(() => {
                    try {
                        toastItem.remove();
                    } catch (e) { }
                }, 300);
            };

            // Nút bấm đóng
            const closeBtn = toastItem.querySelector('.bobin-toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    closeToast();
                });
            }

            // Tự động đóng sau duration (Lỗi hiển thị lâu hơn 500ms để người dùng đọc kịp)
            const autoDuration = normType === 'error' ? Math.max(duration, 4000) : duration;
            if (autoDuration > 0) {
                setTimeout(closeToast, autoDuration);
            }

            return toastItem;
        },

        // Helper Shorthand methods
        success(message, duration = 3500) {
            return this.show(message, 'success', duration);
        },

        error(message, duration = 4500) {
            return this.show(message, 'error', duration);
        },

        warning(message, duration = 3500) {
            return this.show(message, 'warning', duration);
        },

        info(message, duration = 3500) {
            return this.show(message, 'info', duration);
        },

        /**
         * Lưu Toast vào sessionStorage để hiển thị ngay sau khi trang tải lại (reload / redirect)
         * @param {string} message - Nội dung thông báo
         * @param {string} type - 'success' | 'error' | 'warning' | 'info'
         */
        flash(message, type = 'success') {
            try {
                sessionStorage.setItem('bobin_toast_flash', JSON.stringify({
                    message: message,
                    type: type,
                    timestamp: Date.now()
                }));
            } catch (e) {
                console.warn('[Toast] Flash storage failed:', e);
            }
        },

        /**
         * Xóa tất cả toast đang hiển thị
         */
        clear() {
            const container = document.getElementById('bobin-toast-container');
            if (container) {
                container.innerHTML = '';
            }
            document.querySelectorAll('.toast-message').forEach(el => el.remove());
        },

        /**
         * Hàm initStyle để tương thích 100% với delete.js và copyText.js cũ
         */
        initStyle() {
            injectToastStyles();
        }
    };

    // 4. KIỂM TRA VÀ HIỂN THỊ FLASH TOAST & URL QUERY TOAST KHI TRANG LOAD
    function checkAutoToasts() {
        // A. Kiểm tra sessionStorage ('bobin_toast_flash')
        try {
            const flashRaw = sessionStorage.getItem('bobin_toast_flash');
            if (flashRaw) {
                sessionStorage.removeItem('bobin_toast_flash');
                const data = JSON.parse(flashRaw);
                if (data && data.message) {
                    // Tránh flash cũ quá 30 giây
                    if (!data.timestamp || (Date.now() - data.timestamp) < 30000) {
                        setTimeout(() => {
                            Toast.show(data.message, data.type || 'success');
                        }, 180);
                    }
                }
            }
        } catch (e) { }

        // B. Kiểm tra URL Params (?msg=...&msg_type=..., ?error=..., ?success=...)
        try {
            if (window.location.search) {
                const params = new URLSearchParams(window.location.search);
                const msg = params.get('msg');
                const msgType = params.get('msg_type');
                const error = params.get('error');
                const success = params.get('success');

                if (msg) {
                    setTimeout(() => {
                        Toast.show(decodeURIComponent(msg), msgType || 'success');
                    }, 220);
                    cleanUrlParam(['msg', 'msg_type']);
                } else if (error) {
                    setTimeout(() => {
                        Toast.show(decodeURIComponent(error), 'error');
                    }, 220);
                    cleanUrlParam(['error']);
                } else if (success) {
                    setTimeout(() => {
                        Toast.show(decodeURIComponent(success), 'success');
                    }, 220);
                    cleanUrlParam(['success']);
                }
            }
        } catch (e) { }
    }

    // Dọn dẹp URL sau khi đã hiện toast để không bị lặp lại khi người dùng F5
    function cleanUrlParam(paramKeys) {
        try {
            if (!window.history || !window.history.replaceState) return;
            const url = new URL(window.location.href);
            let hasChanged = false;
            paramKeys.forEach(k => {
                if (url.searchParams.has(k)) {
                    url.searchParams.delete(k);
                    hasChanged = true;
                }
            });
            if (hasChanged) {
                window.history.replaceState({}, document.title, url.toString());
            }
        } catch (e) { }
    }

    // 5. GẮN VÀO WINDOW TOÀN CỤC
    window.Toast = Toast;
    window.showToast = function (message, type, duration) {
        return Toast.show(message, type, duration);
    };

    // Khởi tạo ngay lập tức hoặc khi DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            injectToastStyles();
            checkAutoToasts();
        });
    } else {
        injectToastStyles();
        checkAutoToasts();
    }

})(window, document);

var Toast = window.Toast;
var showToast = window.showToast;
