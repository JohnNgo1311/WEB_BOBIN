// Đảm bảo ConfirmDialog luôn sẵn sàng ngay cả khi không tải delete.js
if (typeof window.ConfirmDialog === "undefined") {
    window.ConfirmDialog = {
        show: function (title, contentHTML, onConfirm, onToggle) {
            if (!document.getElementById('confirm-dialog-style')) {
                const style = document.createElement('style');
                style.id = 'confirm-dialog-style';
                style.innerHTML = `
                    .confirm-dialog-overlay { 
                        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);
                        display: flex; align-items: center; justify-content: center; 
                        z-index: 9999; opacity: 0; transition: opacity 0.2s ease;
                    }
                    .confirm-dialog-box { 
                        background: #fff; padding: 20px; border-radius: 10px; 
                        width: 90%; max-width: 520px; 
                        box-shadow: 0 10px 25px rgba(0,0,0,0.18); 
                        font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
                        transform: scale(0.95); transition: transform 0.2s ease;
                    }
                    .confirm-dialog-title { 
                        margin: 0 0 12px 0; font-size: 16px; color: #1e293b; 
                        text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; font-weight: 700;
                    }
                    .confirm-dialog-content { 
                        margin: 12px 0; font-size: 13.5px; color: #475569; 
                        max-height: 65vh; overflow-y: auto; line-height: 1.5;
                    }
                    .confirm-dialog-actions { 
                        display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; 
                    }
                    .dialog-btn {
                        padding: 7px 16px; border: none; border-radius: 6px; font-weight: 600;
                        cursor: pointer; transition: all 0.15s; font-size: 13px;
                    }
                    .back-btn-dialog { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
                    .back-btn-dialog:hover { background: #e2e8f0; }
                    .btn-danger-dialog { background: #dc2626; color: white; font-weight: 700; }
                    .btn-danger-dialog:hover { background: #b91c1c; }
                    @media (max-width: 640px) {
                        .confirm-dialog-box {
                            width: calc(100vw - 32px);
                            padding: 16px;
                        }
                        .confirm-dialog-actions {
                            flex-direction: column-reverse;
                            gap: 8px;
                        }
                        .dialog-btn {
                            width: 100%;
                            min-height: 42px;
                            font-size: 14px;
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            const overlay = document.createElement('div');
            overlay.className = 'confirm-dialog-overlay';

            const box = document.createElement('div');
            box.className = 'confirm-dialog-box';

            const cancelLabel = typeof window.t === 'function' ? window.t('btn_cancel', 'Hủy bỏ') : 'Hủy bỏ';
            const confirmLabel = typeof window.t === 'function' ? window.t('btn_confirm', 'Xác nhận') : 'Xác nhận';

            box.innerHTML = `
                <h3 class="confirm-dialog-title">${title}</h3>
                <div class="confirm-dialog-content">${contentHTML}</div>
                <div class="confirm-dialog-actions">
                    <button type="button" class="dialog-btn back-btn-dialog cancel-btn">${cancelLabel}</button>
                    <button type="button" class="dialog-btn btn-danger-dialog confirm-btn">${confirmLabel}</button>
                </div>
            `;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                box.style.transform = 'scale(1)';
            });

            box.addEventListener('click', (e) => {
                const toggleBtn = e.target.closest('.error-toggle-btn');
                if (toggleBtn) {
                    e.preventDefault();
                    toggleBtn.classList.toggle('active');
                    if (typeof onToggle === 'function') {
                        onToggle(box);
                    }
                }
            });

            const closeDialog = () => {
                overlay.style.opacity = '0';
                box.style.transform = 'scale(0.95)';
                setTimeout(() => overlay.remove(), 200);
            };

            box.querySelector('.cancel-btn').addEventListener('click', closeDialog);
            box.querySelector('.confirm-btn').addEventListener('click', async () => {
                const confirmBtn = box.querySelector('.confirm-btn');
                if (confirmBtn) confirmBtn.disabled = true;
                let shouldClose = true;
                if (typeof onConfirm === 'function') {
                    try {
                        const result = await onConfirm(box);
                        if (result === false) {
                            shouldClose = false;
                        }
                    } catch (err) {
                        console.error('ConfirmDialog callback error:', err);
                    }
                }
                if (shouldClose) {
                    closeDialog();
                } else if (confirmBtn) {
                    confirmBtn.disabled = false;
                }
            });
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    // Tìm nút Logout
    const logoutBtn = document.querySelector('.logout-btn, .header-btn-logout');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const logoutUrl = this.getAttribute('href');
            const titleText = typeof window.t === 'function' ? window.t('logout_confirm_title', 'Xác nhận đăng xuất') : 'Xác nhận đăng xuất';
            const msgText = typeof window.t === 'function' ? window.t('logout_confirm_msg', '⚠️ Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?') : '⚠️ Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?';

            if (window.ConfirmDialog && typeof window.ConfirmDialog.show === 'function') {
                window.ConfirmDialog.show(
                    titleText,
                    `
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #f5c6cb; text-align: left;">
                    ${msgText}
                    </div>
                    `,
                    () => {
                        window.location.href = logoutUrl;
                    }
                );
            } else if (confirm(msgText)) {
                window.location.href = logoutUrl;
            }
        });
    }
});