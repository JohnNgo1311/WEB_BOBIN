const Toast = {
    show: function (message, type = 'success') {
        console.log("Hiển thị toast:", message);

        const toast = document.createElement('div');
        toast.className = `toast-message ${type === 'error' ? 'toast-error' : ''}`;
        toast.innerHTML = message;

        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);

            if (type === 'success') {

            }
        }, 1500);
    }
};

const ConfirmDialog = {
    show: function (title, contentHTML, onConfirm, onToggle) {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-dialog-overlay';

        const box = document.createElement('div');
        box.className = 'confirm-dialog-box';

        box.innerHTML = `
            <h3 class="confirm-dialog-title">${title}</h3>
            <div class="confirm-dialog-content">${contentHTML}</div>
             <div class="confirm-dialog-actions">
                <button type="button" class="btn btn-secondary cancel-btn" style="padding: 8px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: #c3c3c3; color: white;">Trở lại</button>
                <button type="button" class="btn btn-primary confirm-btn" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #2563eb; color: white;">Xác nhận</button>
            </div>
        `;

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        if (!document.getElementById('confirm-dialog-style')) {
            const style = document.createElement('style');
            style.id = 'confirm-dialog-style';
            style.innerHTML = `
                .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
                .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
                .confirm-dialog-title { margin-top: 0; font-size: 18px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .confirm-dialog-content { margin: 15px 0; font-size: 14px; color: #333; max-height: 60vh; overflow-y: auto; text-align: center; }
                .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
                .error-toggle-btn { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 20px; background: #f3f4f6; color: #374151; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 500; }
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
const CopyTextDialog = {
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
            
            <div class="copy-content-wrapper">
                ${copyText}
            </div>

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
                .copy-dialog-overlay { 
                    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
                    background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(2px);
                    display: flex; align-items: center; justify-content: center; z-index: 9999;
                    animation: fadeIn 0.2s ease-out;
                }
                .copy-dialog-box { 
                    background: #fff; padding: 24px; border-radius: 12px; 
                    width: 90%; max-width: 550px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
                    animation: slideUp 0.3s ease-out;
                }
                .copy-dialog-title { 
                    margin: 0 0 16px 0; font-size: 18px; color: #ec4228; 
                    text-align: center; font-weight: 600; line-height: 1.4;
                }
                .copy-dialog-subtitle {
                    display: block; 
                    font-size: 16px; 
                    font-weight: 500; 
                    margin-top: 6px;
                }
                .copy-content-wrapper { 
                    background: #f3f4f6; border: 1px dashed #cbd5e1; border-radius: 8px; 
                    padding: 16px; margin-bottom: 20px; text-align: center; 
                    word-break: break-all; font-family: monospace; font-size: 15px; 
                    color: #000000; font-weight: bold;
                }
                .copy-dialog-actions { 
                    display: flex; flex-direction: column; gap: 12px; 
                }
                .btn-copy-to-clipboard { 
                    display: flex; align-items: center; justify-content: center; gap: 8px;
                    width: 100%; padding: 12px; background: #f8fafc; color: #475569; 
                    border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; 
                    font-size: 15px; font-weight: 600; transition: all 0.2s; 
                }
                .btn-copy-to-clipboard:hover { background: #f1f5f9; color: #0f172a; }
                .btn-copy-to-clipboard.success { 
                    background: #dcfce7; color: #166534; border-color: #bbf7d0; 
                }
                .btn-primary { 
                    width: 100%; padding: 12px; background: #2563eb; color: white; 
                    border: none; border-radius: 8px; cursor: pointer; font-size: 15.5px; 
                    font-weight: 600; transition: background 0.2s; 
                }
                .btn-primary:hover { background: #1d4ed8; }
                
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
            `;
            document.head.appendChild(style);
        }

        const copyBtn = box.querySelector('.btn-copy-to-clipboard');
        const copyBtnText = box.querySelector('.copy-btn-text');
        const confirmBtn = box.querySelector('.confirm-copy-btn');

        const handleCopySuccess = () => {
            copyBtn.classList.add('success');
            copyBtnText.textContent = 'Đã sao chép thành công!';
            setTimeout(() => {
                copyBtn.classList.remove('success');
                copyBtnText.textContent = 'Sao chép dữ liệu';
            }, 2000);
        };

        const handleCopyError = () => {
            copyBtnText.textContent = 'Lỗi! Hãy copy thủ công';
        };

        copyBtn.addEventListener('click', () => {
            if (typeof navigator !== 'undefined' && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(copyText)
                    .then(handleCopySuccess)
                    .catch(handleCopyError);
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = copyText;
                textArea.style.position = "fixed";
                textArea.style.top = "0";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        handleCopySuccess();
                    } else {
                        handleCopyError();
                    }
                } catch (err) {
                    handleCopyError();
                }
                document.body.removeChild(textArea);
            }
        });

        confirmBtn.addEventListener('click', () => {
            overlay.style.animation = 'fadeIn 0.2s ease-out reverse forwards';
            setTimeout(() => {
                overlay.remove();
                if (typeof onConfirm === 'function') onConfirm();
            }, 200);
        });
    }
};

function handleCancel(btnElement, bobinCode, bobinKeyCode) {
    const container = btnElement.closest('.bobin-item');
    if (!bobinCode || !bobinKeyCode) {
        alert("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!");
        return;
    }

    const inMachine = container.querySelector('.winding-machine-name').value.trim();
    const inEmployeeCode = container.querySelector('.winding-employee-code').value.trim();
    const inEmployeeName = container.querySelector('.winding-employee-name').value.trim();
    const originalNote = container.querySelector('.winding-note-field').value.trim();

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        winding_machine: inMachine,
        winding_employee_code: inEmployeeCode,
        winding_employee_name: inEmployeeName,
        winding_note: originalNote
    };

    let reviewHTML = `
          <ul style="list-style: none; padding: 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb;">
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace;">${bobinCode}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Máy:</strong> <span style="color: #1f2937;">${inMachine}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mã nhân viên:</strong> <span style="color: #1f2937;">${inEmployeeCode}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Họ tên Nhân viên:</strong> <span style="color: #1f2937;">${inEmployeeName}</span></li>
                <li style="padding-bottom: 6px;"><strong>Ghi chú:</strong> <span id="dynamic-note-display" style="color: #1f2937; display: block; margin-top: 4px; font-style: italic;">${originalNote || 'Không có ghi chú'}</span></li>
          </ul>
          <div style="margin-top: 15px; text-align: left;">
              <strong style="color: #374151; font-size: 14px; display: block; margin-bottom: 8px;">Chọn lý do lỗi (có thể chọn nhiều):</strong>
              <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                  <button type="button" class="error-toggle-btn" data-error="Ngoại quan">Ngoại quan</button>
                  <button type="button" class="error-toggle-btn" data-error="Thông khí">Thông khí</button>
              </div>
          </div>
     `;

    ConfirmDialog.show(
        '⚠️ Xác nhận Hủy Bobin',
        `
          <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 12px 14px; border-radius: 6px; font-weight: 600; margin-bottom: 16px; border-left: 4px solid #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                ❗Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Hành động này không thể hoàn tác
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
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(bodyData)
                });

                const data = await response.json();

                if (data.success) {
                    console.log("Response từ API:", data);
                    Toast.show(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    console.error("Lỗi từ API:", data);
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                Toast.show("Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        },
        (dialogBox) => {
            const selectedErrors = Array.from(dialogBox.querySelectorAll('.error-toggle-btn.active')).map(btn => btn.dataset.error);
            let newNote = originalNote;

            if (selectedErrors.length > 0) {
                const errorString = "Lỗi Hủy: " + selectedErrors.join(", ");
                newNote = originalNote ? originalNote + " - " + errorString : errorString;
            }

            const noteDisplay = dialogBox.querySelector('#dynamic-note-display');
            if (noteDisplay) {
                noteDisplay.innerText = newNote || 'Không có ghi chú';
                noteDisplay.style.fontStyle = newNote ? 'normal' : 'italic';
            }
            dialogBox.dataset.currentNote = newNote;
        }
    );
}

function handleConfirm(btnElement, bobinCode, bobinKeyCode, mainInfoText) {
    const container = btnElement.closest('.bobin-item');

    if (!bobinCode || !bobinKeyCode) {
        alert("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!");
        return;
    }
    const inMachine = container.querySelector('.winding-machine-name').value.trim();
    const inWinemployeeCode = container.querySelector('.winding-employee-code').value.trim();
    const inWinemployeeName = container.querySelector('.winding-employee-name').value.trim();
    const inNote = container.querySelector('.winding-note-field').value.trim();

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
        <div style="padding: 20px; background: #f4f6f8; border-radius: 6px; text-align: center; border: 2px dashed #007bff;">
            <p style="margin: 0 0 10px 0; color: #555; font-size: 14px;">Mã định danh Bobin</p>
            <p style="margin: 0; color: #0056b3; font-size: 24px; font-weight: bold; letter-spacing: 1px;">
                ${bobinCode}
            </p>
        </div>
        <div style="margin-top: 15px; padding: 12px; background: #e7f3ff; border-left: 4px solid #0056b3; text-align: left;">
            <p style="margin: 5px 0; font-size: 13px;"><strong>Máy:</strong> ${inMachine}</p>
            <p style="margin: 5px 0; font-size: 13px;"><strong>Mã nhân viên:</strong> ${inWinemployeeCode}</p>
            <p style="margin: 5px 0; font-size: 13px;"><strong>Họ tên Nhân viên:</strong> ${inWinemployeeName}</p>
            <p style="margin: 5px 0; font-size: 13px;"><strong>Kết quả kiểm tra thông khí:</strong> ${bodyData.flow_test_result}</p>
            <p style="margin: 5px 0; font-size: 13px;"><strong>Ghi chú:</strong> ${inNote || 'Không có ghi chú'}</p>
        </div>
    `;

    ConfirmDialog.show(
        'Xác nhận thông tin Cuộn',
        `
        <div style="background-color: #fff3cd; color: #856404; padding: 10px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #ffeeba; text-align: left;">
            ⚠️ Vui lòng xác nhận đúng mã Bobin trước khi gửi đi:
        </div>
        ${reviewHTML}
        `,
        async () => {
            const requestUrl = '/WEB_BOBIN/public/index.php?url=bobin/updateWindingBobin';
            const originalBtnText = btnElement.innerHTML;

            btnElement.innerHTML = 'Đang xử lý...';
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
                    console.log("Response từ API:", data);
                    Toast.show(data.message, 'success');
                    CopyTextDialog.show(
                        "Hãy sao chép nội dung sau để nhập vào báo cáo. \n Lưu ý không thể hoàn tác sau khi nhấn Xác Nhận!",
                        mainInfoText,
                        () => {
                            window.location.reload();
                        }
                    );
                } else {
                    console.error("Lỗi từ API:", data);
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            }
            catch (error) {
                console.error('Error:', error);
                Toast.show("Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        }
    );
}

