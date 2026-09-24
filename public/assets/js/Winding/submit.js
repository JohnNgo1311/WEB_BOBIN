/* =========================================
   MODULE TOAST: Hiển thị thông báo (Bọc an toàn)
========================================= */
if (typeof Toast === "undefined") {
    window.Toast = {
        show: function (message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-message ${type === 'error' ? 'toast-error' : ''}`;
            toast.innerHTML = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 2500);
        }
    };
}

/* =========================================
   MODULE CONFIRM DIALOG: Bảng xác nhận
========================================= */
if (typeof ConfirmDialog === "undefined") {
    window.ConfirmDialog = {
        show: function (title, contentHTML, onConfirm, onToggle) {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-dialog-overlay';

            const box = document.createElement('div');
            box.className = 'confirm-dialog-box';
            box.innerHTML = `
                <h3 class="confirm-dialog-title">${title}</h3>
                <div class="confirm-dialog-content">${contentHTML}</div>
                <div class="confirm-dialog-actions">
                    <button type="button" class="btn btn-secondary cancel-btn" style="padding: 7px 16px; border: 1px solid #cbd5e1; border-radius: 5px; cursor: pointer; background: #e2e8f0; color: #334155; font-weight: 600;">Trở lại</button>
                    <button type="button" class="btn btn-primary confirm-btn" style="padding: 7px 16px; border: none; border-radius: 5px; cursor: pointer; background: #2563eb; color: white; font-weight: 700;">Xác nhận</button>
                </div>
            `;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            if (!document.getElementById('confirm-dialog-style')) {
                const style = document.createElement('style');
                style.id = 'confirm-dialog-style';
                style.innerHTML = `
                    .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
                    .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 520px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
                    .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 8px; font-weight: 700; }
                    .confirm-dialog-content { margin: 12px 0; font-size: 13.5px; color: #333; max-height: 65vh; overflow-y: auto; }
                    .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
                    .error-toggle-btn { padding: 4px 12px; border: 1px solid #cbd5e1; border-radius: 20px; background: #f8fafc; color: #334155; cursor: pointer; font-size: 12.5px; font-weight: 600; }
                    .error-toggle-btn.active { background: #fee2e2; border-color: #ef4444; color: #b91c1c; }
                `;
                document.head.appendChild(style);
            }

            box.addEventListener('click', (e) => {
                if (e.target.classList.contains('error-toggle-btn')) {
                    e.target.classList.toggle('active');
                    if (typeof onToggle === 'function') {
                        onToggle(box);
                    }
                }
            });

            box.querySelector('.cancel-btn').addEventListener('click', () => overlay.remove());
            box.querySelector('.confirm-btn').addEventListener('click', () => {
                if (typeof onConfirm === 'function') onConfirm(box);
                overlay.remove();
            });
        }
    };
}

/* =========================================
   MODULE COPY DIALOG: Sao chép dữ liệu báo cáo
========================================= */
if (typeof CopyTextDialog === "undefined") {
    window.CopyTextDialog = {
        show: function (title, copyText, onConfirm) {
            const overlay = document.createElement('div');
            overlay.className = 'copy-dialog-overlay';

            const box = document.createElement('div');
            box.className = 'copy-dialog-box';

            let formattedTitle = title;
            if (title.includes('\n')) {
                const parts = title.split('\n');
                formattedTitle = `${parts[0]} <br> <span class="copy-dialog-subtitle">${parts[1]}</span>`;
            }

            box.innerHTML = `
                <h3 class="copy-dialog-title">${formattedTitle}</h3>
                <div class="copy-content-wrapper">${copyText}</div>
                <div class="copy-dialog-actions">
                    <button type="button" class="btn-copy-to-clipboard">
                        <span class="copy-icon">📋</span> 
                        <span class="copy-btn-text">Sao chép dữ liệu</span>
                    </button>
                    <button type="button" class="btn btn-primary confirm-copy-btn">Xác nhận đã nhập báo cáo</button>
                </div>
            `;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            if (!document.getElementById('copy-dialog-style')) {
                const style = document.createElement('style');
                style.id = 'copy-dialog-style';
                style.innerHTML = `
                    .copy-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.45); display: flex; align-items: center; justify-content: center; z-index: 9999; }
                    .copy-dialog-box { background: #fff; padding: 22px; border-radius: 10px; width: 90%; max-width: 520px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); font-family: sans-serif; text-align: center; }
                    .copy-dialog-title { margin: 0 0 14px 0; font-size: 16px; color: #ea580c; font-weight: 700; line-height: 1.4; }
                    .copy-dialog-subtitle { display: block; font-size: 13.5px; font-weight: 600; color: #dc2626; margin-top: 4px; }
                    .copy-content-wrapper { background: #f8fafc; border: 1px dashed #93c5fd; border-radius: 6px; padding: 12px; margin-bottom: 16px; word-break: break-all; font-family: monospace; font-size: 14px; color: #0284c7; font-weight: 800; }
                    .copy-dialog-actions { display: flex; flex-direction: column; gap: 10px; }
                    .btn-copy-to-clipboard { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 10px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
                    .btn-copy-to-clipboard.success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
                    .confirm-copy-btn { width: 100%; padding: 10px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 700; }
                    .confirm-copy-btn:hover { background: #15803d; }
                `;
                document.head.appendChild(style);
            }

            const copyBtn = box.querySelector('.btn-copy-to-clipboard');
            const copyBtnText = box.querySelector('.copy-btn-text');
            const confirmBtn = box.querySelector('.confirm-copy-btn');

            copyBtn.addEventListener('click', () => {
                if (typeof navigator !== 'undefined' && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(copyText).then(() => {
                        copyBtn.classList.add('success');
                        copyBtnText.textContent = 'Đã sao chép thành công!';
                        setTimeout(() => {
                            copyBtn.classList.remove('success');
                            copyBtnText.textContent = 'Sao chép dữ liệu';
                        }, 2000);
                    });
                } else {
                    const textArea = document.createElement("textarea");
                    textArea.value = copyText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    copyBtn.classList.add('success');
                    copyBtnText.textContent = 'Đã sao chép thành công!';
                }
            });

            confirmBtn.addEventListener('click', () => {
                overlay.remove();
                if (typeof onConfirm === 'function') onConfirm();
            });
        }
    };
}

/* =========================================
   HÀM XỬ LÝ: Hủy Bobin phía Cuộn
========================================= */
function handleCancel(btnElement, bobinCode, bobinKeyCode) {
    const container = btnElement.closest('.bobin-item');
    if (!bobinCode || !bobinKeyCode) {
        Toast.show("Lỗi: Thiếu mã định danh của Bobin!", "error");
        return;
    }

    const inMachine = container.querySelector('.winding-machine-name')?.value.trim() || '';
    const inEmployeeCode = container.querySelector('.winding-employee-code')?.value.trim() || '';
    const inEmployeeName = container.querySelector('.winding-employee-name')?.value.trim() || '';
    const originalNote = container.querySelector('.winding-note-field')?.value.trim() || '';

    if (!inEmployeeCode) {
        Toast.show("⚠️ Vui lòng nhập Mã nhân viên cuộn trước khi hủy!", "error");
        const empInput = container.querySelector('.winding-employee-code');
        if (empInput) {
            empInput.style.border = "2px solid red";
            empInput.focus();
            setTimeout(() => empInput.style.border = "1px solid #cbd5e1", 2000);
        }
        return;
    }

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        winding_machine: inMachine,
        winding_employee_code: inEmployeeCode,
        winding_employee_name: inEmployeeName,
        winding_note: originalNote
    };

    let reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace; font-weight: 700;">${bobinCode}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Máy cuộn:</strong> <span>${inMachine || 'Chưa chọn'}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên:</strong> <span>${inEmployeeCode} - ${inEmployeeName}</span></li>
            <li style="padding-top: 4px;"><strong>Ghi chú:</strong> <span id="dynamic-note-display" style="color: #1f2937; font-style: italic;">${originalNote || 'Không có ghi chú'}</span></li>
        </ul>
        <div style="margin-top: 12px; text-align: left;">
            <strong style="color: #374151; font-size: 13px; display: block; margin-bottom: 6px;">Chọn lý do hủy (có thể chọn nhiều):</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                <button type="button" class="error-toggle-btn" data-error="Ngoại quan">Ngoại quan</button>
                <button type="button" class="error-toggle-btn" data-error="Thông khí">Thông khí</button>
            </div>
        </div>
    `;

    ConfirmDialog.show(
        '⚠️ Xác nhận Hủy Bobin phía Cuộn',
        `
        <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 10px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 12px; border-left: 4px solid #dc2626;">
            ❗ Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Hành động này sẽ chuyển sang danh sách chờ hủy!
        </div>
        ${reviewHTML}
        `,
        async (dialogBox) => {
            const selectedErrors = Array.from(dialogBox.querySelectorAll('.error-toggle-btn.active')).map(btn => btn.dataset.error);
            bodyData.cancel_errors = selectedErrors;

            if (dialogBox.dataset.currentNote) {
                bodyData.winding_note = dialogBox.dataset.currentNote;
            }

            const requestUrl = '/WEB_BOBIN/public/index.php?url=bobin/windingCancelBobin';
            const originalBtnText = btnElement.innerHTML;

            btnElement.innerHTML = '⏳ Đang xử lý...';
            btnElement.disabled = true;

            try {
                const response = await fetch(requestUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(bodyData)
                });

                const data = await response.json();
                if (data.success) {
                    Toast.show(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                Toast.show("Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        },
        (dialogBox) => {
            const selectedErrors = Array.from(dialogBox.querySelectorAll('.error-toggle-btn.active')).map(btn => btn.dataset.error);
            let newNote = originalNote;

            if (selectedErrors.length > 0) {
                const errorString = "Cuộn " + inMachine + " yêu cầu Hủy - " + "Lý do: " + selectedErrors.join(", ") + " NG";
                newNote = originalNote ? originalNote + " - " + errorString : errorString;
            }

            const noteDisplay = dialogBox.querySelector('#dynamic-note-display');
            if (noteDisplay) {
                noteDisplay.innerText = newNote || 'Không có ghi chú';
            }
            dialogBox.dataset.currentNote = newNote;
        }
    );
}

/* =========================================
   HÀM XỬ LÝ: Xác nhận hoàn thành Cuộn
========================================= */
function handleConfirm(btnElement, bobinCode, bobinKeyCode, mainInfoText) {
    const container = btnElement.closest('.bobin-item');

    if (!bobinCode || !bobinKeyCode) {
        Toast.show("Lỗi: Thiếu mã định danh của Bobin!", "error");
        return;
    }

    const inMachine = container.querySelector('.winding-machine-name')?.value.trim() || '';
    const inWinemployeeCode = container.querySelector('.winding-employee-code')?.value.trim() || '';
    const inWinemployeeName = container.querySelector('.winding-employee-name')?.value.trim() || '';
    const inNote = container.querySelector('.winding-note-field')?.value.trim() || '';

    if (!inMachine) {
        Toast.show("⚠️ Vui lòng nhập hoặc chọn Máy cuộn!", "error");
        const mInput = container.querySelector('.winding-machine-name');
        if (mInput) {
            mInput.style.border = "2px solid red";
            mInput.focus();
            setTimeout(() => mInput.style.border = "1px solid #cbd5e1", 2000);
        }
        return;
    }

    if (!inWinemployeeCode) {
        Toast.show("⚠️ Vui lòng nhập Mã nhân viên cuộn!", "error");
        const empInput = container.querySelector('.winding-employee-code');
        if (empInput) {
            empInput.style.border = "2px solid red";
            empInput.focus();
            setTimeout(() => empInput.style.border = "1px solid #cbd5e1", 2000);
        }
        return;
    }

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        winding_machine: inMachine,
        winding_employee_code: inWinemployeeCode,
        winding_employee_name: inWinemployeeName,
        flow_test_result: "Thành công",
        winding_note: inNote
    };

    let reviewHTML = `
        <div style="padding: 12px; background: #f0fdf4; border-radius: 6px; text-align: center; border: 1.5px dashed #16a34a; margin-bottom: 10px;">
            <p style="margin: 0; color: #15803d; font-size: 13px; font-weight: 600;">Mã định danh Bobin:</p>
            <p style="margin: 4px 0 0 0; color: #166534; font-size: 20px; font-weight: 800; font-family: monospace;">
                ${bobinCode}
            </p>
        </div>
        <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Máy cuộn:</strong> <span>${inMachine}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên:</strong> <span>${inWinemployeeCode} - ${inWinemployeeName}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Thông khí:</strong> <span style="color:#16a34a; font-weight:800;">Thành công</span></li>
            <li style="display: flex; justify-content: space-between;"><strong>Ghi chú:</strong> <span>${inNote || 'Không có ghi chú'}</span></li>
        </ul>
    `;

    ConfirmDialog.show(
        'Xác nhận hoàn thành Cuộn',
        `
        <div style="background-color: #e0f2fe; color: #0369a1; padding: 8px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #0284c7;">
            ℹ️ Kiểm tra thông tin trước khi hoàn tất chu kỳ cuộn:
        </div>
        ${reviewHTML}
        `,
        async () => {
            const requestUrl = '/WEB_BOBIN/public/index.php?url=bobin/updateWindingBobin';
            const originalBtnText = btnElement.innerHTML;

            btnElement.innerHTML = 'Đang lưu...';
            btnElement.disabled = true;

            try {
                const response = await fetch(requestUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(bodyData)
                });

                const data = await response.json();
                if (data.success) {
                    Toast.show(data.message, 'success');
                    CopyTextDialog.show(
                        "Hãy sao chép nội dung sau để nhập vào báo cáo. \n Lưu ý không thể hoàn tác sau khi nhấn Xác Nhận!",
                        mainInfoText,
                        () => {
                            window.location.reload();
                        }
                    );
                } else {
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                Toast.show("Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        }
    );
}