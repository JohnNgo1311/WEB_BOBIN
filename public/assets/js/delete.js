// ==========================================
// 1. COMPONENT: TOAST MESSAGE
// ==========================================
const Toast = {
    initStyle: function () {
        if (!document.getElementById('toast-style-css')) {
            const style = document.createElement('style');
            style.id = 'toast-style-css';
            style.innerHTML = `
                .toast-message {
                    position: fixed; top: 20px; right: -350px; 
                    min-width: 250px; padding: 14px 20px; 
                    border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                    font-family: system-ui, sans-serif; font-size: 14px; font-weight: 500;
                    color: #fff; z-index: 10000;
                    transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    display: flex; align-items: center; gap: 10px;
                }
                .toast-message.show { right: 20px; }
                .toast-success { background-color: #10b981; border-left: 5px solid #059669; }
                .toast-error { background-color: #ef4444; border-left: 5px solid #b91c1c; }
            `;
            document.head.appendChild(style);
        }
    },
    show: function (message, type = 'success') {
        this.initStyle();
        console.log("Hiển thị toast:", message);

        const toast = document.createElement('div');
        toast.className = `toast-message ${type === 'error' ? 'toast-error' : 'toast-success'}`;

        // Thêm icon đơn giản bằng emoji cho sinh động
        const icon = type === 'error' ? '❌' : '✅';
        toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;

        document.body.appendChild(toast);

        // Kích hoạt animation slide-in
        requestAnimationFrame(() => {
            setTimeout(() => toast.classList.add('show'), 10);
        });

        // Tự động ẩn và xóa element
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400); // Đợi animation chạy xong mới xóa DOM
        }, 2500); // Tăng thời gian hiển thị lên 2.5s để dễ đọc hơn
    }
};

// ==========================================
// 2. COMPONENT: CONFIRM DIALOG
// ==========================================
const ConfirmDialog = {
    show: function (title, contentHTML, onConfirm, onToggle) {
        if (!document.getElementById('confirm-dialog-style')) {
            const style = document.createElement('style');
            style.id = 'confirm-dialog-style';
            style.innerHTML = `
                .confirm-dialog-overlay { 
                    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                    background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(3px);
                    display: flex; align-items: center; justify-content: center; 
                    z-index: 9999; opacity: 0; transition: opacity 0.2s ease;
                }
                .confirm-dialog-box { 
                    background: #fff; padding: 24px; border-radius: 12px; 
                    width: 90%; max-width: 450px; 
                    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); 
                    font-family: system-ui, sans-serif;
                    transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }
                .confirm-dialog-title { 
                    margin: 0 0 16px 0; font-size: 18px; color: #1e293b; 
                    text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; 
                }
                .confirm-dialog-content { 
                    margin: 15px 0; font-size: 14px; color: #475569; 
                    max-height: 60vh; overflow-y: auto; line-height: 1.5;
                }
                .confirm-dialog-actions { 
                    display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; 
                }
                .dialog-btn {
                    padding: 8px 16px; border: none; border-radius: 6px; font-weight: 500;
                    cursor: pointer; transition: all 0.2s; font-size: 14px;
                }
                .back-btn { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
                .back-btn:hover { background: #e2e8f0; }
                .btn-danger { background: #ef4444; color: white; }
                .btn-danger:hover { background: #dc2626; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2); }
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
                <button type="button" class="dialog-btn back-btn cancel-btn">Hủy bỏ</button>
                <button type="button" class="dialog-btn btn-danger confirm-btn">Hoàn tất hủy Bobin</button>
            </div>
        `;

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        // Animation xuất hiện
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            box.style.transform = 'scale(1)';
        });

        // Hàm đóng popup
        const closeDialog = () => {
            overlay.style.opacity = '0';
            box.style.transform = 'scale(0.95)';
            setTimeout(() => overlay.remove(), 200);
        };

        // Bắt sự kiện click
        box.addEventListener('click', (e) => {
            if (e.target.classList.contains('error-toggle-btn')) {
                e.target.classList.toggle('active');
                if (typeof onToggle === 'function') onToggle(box);
            }
        });

        // Click nút Hủy hoặc click ra ngoài overlay để đóng
        box.querySelector('.cancel-btn').addEventListener('click', closeDialog);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeDialog();
        });

        // Click Xác nhận
        box.querySelector('.confirm-btn').addEventListener('click', () => {
            if (typeof onConfirm === 'function') onConfirm(box);
            closeDialog();
        });
    }
};

// ==========================================
// 3. CHỨC NĂNG: XÓA BOBIN
// ==========================================
function handleDelete(btnElement, bobinCode, bobinKeyCode) {
    // 1. Xóa trạng thái focus (viền xám/nền xám) của trình duyệt ngay khi vừa click
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
        <ul style="list-style: none; padding: 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="display: flex; justify-content: space-between; align-items: center;">
                <strong>Mã Bobin:</strong> 
                <span style="color: #0f172a; font-family: monospace; font-size: 15px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">${bobinCode}</span>
            </li>
        </ul>`;

    ConfirmDialog.show(
        '⚠️ Hoàn tất hủy Bobin',
        `
        <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; font-weight: 500; margin-bottom: 16px; border-left: 4px solid #ef4444; font-size: 13px; text-align: left;">
            Hành động này sẽ hủy Bobin vĩnh viễn và không thể hoàn tác. Bạn có chắc chắn muốn tiếp tục?
        </div>
        ${reviewHTML}
        `,
        // Khối lệnh này CHỈ CHẠY khi người dùng bấm "Xác nhận Hủy"
        async () => {
            const requestUrl = '/WEB_BOBIN/public/index.php?url=bobin/deleteBobin';
            const originalBtnText = btnElement.innerHTML;

            // 2. CHỈ khóa nút và đổi chữ thành "Đang xử lý" ở bên trong khối lệnh này
            btnElement.innerHTML = '⏳ Đang xử lý...';
            btnElement.disabled = true;
            btnElement.style.opacity = '0.7';

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
                    Toast.show(data.message || "Xóa thành công!", 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || "Cập nhật thất bại");
                }
            } catch (error) {
                console.error('Lỗi Xóa Bobin:', error);
                Toast.show("Lỗi: " + error.message, 'error');

                // Trả lại trạng thái ban đầu cho nút nếu có lỗi API
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
                btnElement.style.opacity = '1';
            }
        }
    );
}