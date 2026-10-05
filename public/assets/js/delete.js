/* ==========================================
   1. COMPONENT: TOAST MESSAGE (Bọc an toàn)
========================================== */
if (typeof window.Toast === "undefined" || !window.Toast.initStyle) {
    window.Toast = {
        initStyle: function () {
            if (!document.getElementById('toast-style-css')) {
                const style = document.createElement('style');
                style.id = 'toast-style-css';
                style.innerHTML = `
                    .toast-message {
                        position: fixed; top: 24px; right: 24px; 
                        padding: 12px 20px; border-radius: 8px; 
                        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.2);
                        font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif); font-size: 13.5px; font-weight: 700;
                        color: #fff; z-index: 99999; opacity: 0;
                        transform: translateY(-20px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        display: flex; align-items: center; gap: 10px;
                        pointer-events: none; max-width: calc(100vw - 48px);
                    }
                    .toast-message.show { opacity: 1; transform: translateY(0); }
                    .toast-success { background-color: #16a34a !important; }
                    .toast-error { background-color: #dc2626 !important; }
                    .toast-warning { background-color: #f59e0b !important; }
                    .toast-info { background-color: #2563eb !important; }
                    @media (max-width: 640px) {
                        .toast-message {
                            top: 16px; right: 16px; left: 16px;
                            justify-content: center; text-align: center; max-width: none;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        },
        show: function (message, type = 'success') {
            this.initStyle();
            document.querySelectorAll('.toast-message').forEach(el => el.remove());

            const toast = document.createElement('div');
            toast.className = `toast-message toast-${type}`;
            const icon = type === 'error' ? '❌' : (type === 'warning' ? '⚠️' : '✅');
            toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                requestAnimationFrame(() => toast.classList.add('show'));
            });

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }
    };
}
var Toast = window.Toast;

// Lắng nghe Flash message từ sessionStorage khi trang vừa tải/reload xong
document.addEventListener('DOMContentLoaded', function () {
    const flashToast = sessionStorage.getItem('bobin_toast_flash');
    if (flashToast) {
        try {
            const data = JSON.parse(flashToast);
            if (data && data.message) {
                setTimeout(() => {
                    Toast.show(data.message, data.type || 'success');
                }, 300);
            }
        } catch (e) {
            console.error('Lỗi phân tích flash toast:', e);
        }
        sessionStorage.removeItem('bobin_toast_flash');
    }
});

/* ==========================================
   2. COMPONENT: CONFIRM DIALOG (Bọc an toàn)
========================================== */
if (typeof ConfirmDialog === "undefined") {
    window.ConfirmDialog = {
        show: function (title, contentHTML, onConfirm) {
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
                        background: #fff; padding: 20px; border-radius: 8px; 
                        width: 90%; max-width: 480px; 
                        box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
                        font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
                        transform: scale(0.95); transition: transform 0.2s ease;
                    }
                    .confirm-dialog-title { 
                        margin: 0 0 12px 0; font-size: 16px; color: #1e293b; 
                        text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; font-weight: 700;
                    }
                    .confirm-dialog-content { 
                        margin: 12px 0; font-size: 13.5px; color: #475569; 
                        max-height: 60vh; overflow-y: auto; line-height: 1.5;
                    }
                    .confirm-dialog-actions { 
                        display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; 
                    }
                    .dialog-btn {
                        padding: 7px 16px; border: none; border-radius: 5px; font-weight: 600;
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

            box.innerHTML = `
                <h3 class="confirm-dialog-title">${title}</h3>
                <div class="confirm-dialog-content">${contentHTML}</div>
                <div class="confirm-dialog-actions">
                    <button type="button" class="dialog-btn back-btn-dialog cancel-btn">Hủy bỏ</button>
                    <button type="button" class="dialog-btn btn-danger-dialog confirm-btn">Xác nhận</button>
                </div>
            `;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                box.style.transform = 'scale(1)';
            });

            const closeDialog = () => {
                overlay.style.opacity = '0';
                box.style.transform = 'scale(0.95)';
                setTimeout(() => overlay.remove(), 200);
            };

            box.querySelector('.cancel-btn').addEventListener('click', closeDialog);
            box.querySelector('.confirm-btn').addEventListener('click', async () => {
                if (typeof onConfirm === 'function') {
                    try {
                        await onConfirm(box);
                    } catch (err) {
                        console.error('ConfirmDialog callback error:', err);
                    }
                }
                closeDialog();
            });
        }
    };
}

/* ==========================================
   3. CHỨC NĂNG: XÓA / HOÀN TẤT HỦY BOBIN
========================================== */
function handleDelete(btnElement, bobinCode, bobinKeyCode) {
    btnElement.blur();

    if (!bobinCode || !bobinKeyCode) {
        Toast.show("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
        return;
    }

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
    };

    let reviewHTML = `
        <div style="background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 12px; border-left: 4px solid #ef4444; font-size: 13px; text-align: left;">
            ⚠️ Hành động này sẽ chuyển trạng thái Bobin thành <strong>ĐÃ HỦY</strong> vĩnh viễn và giải phóng hoàn toàn chu kỳ. Bạn có chắc chắn?
        </div>
        <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="display: flex; justify-content: space-between; align-items: center;">
                <strong>Mã định danh Bobin:</strong> 
                <span style="color: #0f172a; font-family: var(--font-family-mono, monospace); font-size: 15px; font-weight: 800; background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">${bobinCode}</span>
            </li>
        </ul>
    `;

    ConfirmDialog.show(
        '⚠️ Xác nhận Hoàn tất hủy Bobin',
        reviewHTML,
        async () => {
            const requestUrl = (typeof API_BASE_URL !== 'undefined' ? API_BASE_URL : '/WEB_BOBIN/public/index.php?url=') + 'bobin/deleteBobin';
            const originalBtnText = btnElement.innerHTML;

            btnElement.innerHTML = '⏳ Đang xử lý...';
            btnElement.disabled = true;

            try {
                const response = await fetch(requestUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(bodyData)
                });

                const data = await response.json();

                if (data.success) {
                    const successMsg = data.message || "Đã hủy Bobin thành công!";
                    
                    // Lưu Flash Toast để tiếp tục hiển thị sau khi reload trang
                    sessionStorage.setItem('bobin_toast_flash', JSON.stringify({
                        message: successMsg,
                        type: 'success'
                    }));

                    Toast.show(successMsg, 'success');

                    // Hiệu ứng mờ dần card trên giao diện trước khi reload
                    const card = btnElement.closest('.bobin-item');
                    if (card) {
                        card.style.transition = 'all 0.4s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                    }

                    setTimeout(() => window.location.reload(), 800);
                } else {
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                console.error('Lỗi Xóa Bobin:', error);
                Toast.show("Lỗi kết nối: " + (error.message || "Không thể thực hiện hủy"), 'error');

                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        }
    );
}
