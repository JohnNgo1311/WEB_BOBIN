/* =========================================
   MODULE TOAST: Hiển thị thông báo
========================================= */
var Toast = window.Toast || {
    show: function (message, type = 'success') {
        if (window.Toast && window.Toast.show) return window.Toast.show(message, type);
    },
    flash: function (message, type = 'success') {
        if (window.Toast && window.Toast.flash) return window.Toast.flash(message, type);
    }
};


/* =========================================
   MODULE CONFIRM DIALOG (WINDING ENHANCED)
========================================= */

window.ConfirmDialog = {
    show: function (
        title,
        contentHTML,
        onConfirm,
        onToggle,
        options = {}
    ) {
        if (!document.getElementById('winding-confirm-dialog-style')) {
            const style = document.createElement('style');
            style.id = 'winding-confirm-dialog-style';
            style.innerHTML = `
                .confirm-dialog-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    background: rgba(15, 23, 42, 0.62);
                    backdrop-filter: blur(3px);
                    -webkit-backdrop-filter: blur(3px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 99999;
                    padding: 16px;
                    box-sizing: border-box;
                }

                .confirm-dialog-box {
                    background: #ffffff;
                    padding: 20px 22px;
                    border-radius: 12px;
                    width: 100%;
                    max-width: 540px;
                    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
                    font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
                    box-sizing: border-box;
                    animation: wndDialogPop 0.18s cubic-bezier(0.16, 1, 0.3, 1);
                }

                @keyframes wndDialogPop {
                    from { opacity: 0; transform: scale(0.95) translateY(8px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
                }

                .confirm-dialog-title {
                    margin: 0 0 12px 0;
                    font-size: 17px;
                    color: #0f172a;
                    text-align: center;
                    border-bottom: 1.5px solid #e2e8f0;
                    padding-bottom: 10px;
                    font-weight: 800;
                }

                .confirm-dialog-content {
                    margin: 12px 0;
                    font-size: 13.5px;
                    color: #334155;
                    max-height: 70vh;
                    overflow-y: auto;
                }

                .confirm-dialog-actions {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    margin-top: 16px;
                    padding-top: 12px;
                    border-top: 1px solid #e2e8f0;
                }

                .wnd-reason-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    margin-top: 8px;
                }

                .error-toggle-btn {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                    width: 100%;
                    padding: 12px 14px;
                    border: 2px solid #cbd5e1;
                    border-radius: 10px;
                    background: #ffffff;
                    color: #1e293b;
                    cursor: pointer;
                    font-size: 13.5px;
                    font-weight: 700;
                    text-align: left;
                    transition: all 0.18s ease;
                    user-select: none;
                    box-sizing: border-box;
                }

                .error-toggle-btn:hover {
                    border-color: #f87171;
                    background: #fff5f5;
                }

                .error-toggle-btn .err-btn-left {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    pointer-events: none;
                }

                .error-toggle-btn .err-btn-icon {
                    font-size: 18px;
                    line-height: 1;
                }

                .error-toggle-btn .err-btn-info {
                    display: flex;
                    flex-direction: column;
                }

                .error-toggle-btn .err-btn-title {
                    font-size: 13.5px;
                    font-weight: 800;
                    color: #0f172a;
                }

                .error-toggle-btn .err-btn-sub {
                    font-size: 11px;
                    font-weight: 500;
                    color: #64748b;
                    margin-top: 1px;
                }

                .error-toggle-btn .err-btn-badge {
                    pointer-events: none;
                    padding: 3px 8px;
                    border-radius: 6px;
                    font-size: 11px;
                    font-weight: 800;
                    background: #f1f5f9;
                    color: #64748b;
                    border: 1px solid #cbd5e1;
                    white-space: nowrap;
                    transition: all 0.18s ease;
                }

                .error-toggle-btn.active {
                    background: #fef2f2;
                    border-color: #ef4444;
                    color: #991b1b;
                    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.18);
                }

                .error-toggle-btn.active .err-btn-title {
                    color: #b91c1c;
                }

                .error-toggle-btn.active .err-btn-sub {
                    color: #dc2626;
                }

                .error-toggle-btn.active .err-btn-badge {
                    background: #ef4444;
                    color: #ffffff;
                    border-color: #dc2626;
                }

                .dialog-note-input {
                    width: 100%;
                    height: 38px;
                    padding: 0 11px;
                    border: 1.5px solid #cbd5e1;
                    border-radius: 7px;
                    font-size: 13px;
                    color: #0f172a;
                    background: #ffffff;
                    outline: none;
                    box-sizing: border-box;
                    transition: border-color 0.15s ease;
                }

                .dialog-note-input:focus {
                    border-color: #2563eb;
                    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
                }

                @media (max-width: 640px) {
                    .confirm-dialog-box {
                        width: calc(100vw - 24px);
                        padding: 16px;
                    }
                    .wnd-reason-grid {
                        grid-template-columns: 1fr;
                    }
                    .confirm-dialog-actions {
                        flex-direction: column-reverse;
                        gap: 8px;
                    }
                    .confirm-dialog-actions button {
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

        const isDanger = Boolean(options.isDanger);
        const cancelLabel = options.cancelText || (window.t ? window.t('btn_back', 'Trở lại') : 'Trở lại');
        const confirmLabel = options.confirmText || (window.t ? window.t('btn_confirm', 'Xác nhận') : 'Xác nhận');
        const confirmBg = isDanger ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #2563eb, #1d4ed8)';

        box.innerHTML = `
            <h3 class="confirm-dialog-title">${title}</h3>
            <div class="confirm-dialog-content">${contentHTML}</div>
            <div class="confirm-dialog-actions">
                <button type="button"
                    class="btn btn-secondary cancel-btn"
                    style="padding:8px 18px; border:1px solid #cbd5e1; border-radius:7px; cursor:pointer; background:#f1f5f9; color:#334155; font-weight:700; font-size:13.5px;">
                    ${cancelLabel}
                </button>
                <button type="button"
                    class="btn btn-primary confirm-btn"
                    style="padding:8px 18px; border:none; border-radius:7px; cursor:pointer; background:${confirmBg}; color:white; font-weight:700; font-size:13.5px; box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                    ${confirmLabel}
                </button>
            </div>
        `;

        overlay.appendChild(box);
        document.body.appendChild(overlay);

        /* Gắn trực tiếp sự kiện click vào từng nút .error-toggle-btn */
        const updateToggleBadge = (btn) => {
            const badge = btn.querySelector('.err-btn-badge');
            if (!badge) return;
            const isActive = btn.classList.contains('active');
            badge.textContent = isActive ? '☑ NG' : '☐ OK';
        };

        box.querySelectorAll('.error-toggle-btn').forEach((btn) => {
            updateToggleBadge(btn);
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                btn.classList.toggle('active');
                updateToggleBadge(btn);
                if (typeof onToggle === 'function') {
                    onToggle(box);
                }
            });
        });

        /* Nút trở lại */
        box.querySelector('.cancel-btn').addEventListener('click', () => overlay.remove());

        /* Nút xác nhận */
        box.querySelector('.confirm-btn').addEventListener('click', async () => {
            const confirmBtn = box.querySelector('.confirm-btn');
            confirmBtn.disabled = true;
            let shouldClose = true;

            try {
                if (typeof onConfirm === 'function') {
                    const result = await onConfirm(box);
                    if (result === false) {
                        shouldClose = false;
                    }
                }
            } catch (error) {
                console.error('ConfirmDialog callback error:', error);
            }

            if (shouldClose) {
                overlay.remove();
            } else {
                confirmBtn.disabled = false;
            }
        });
    }
};

/* Hàm chọn nhanh hạng mục lỗi NG ngay trên Card Nhóm cuộn */
window.toggleCardCancelReason = function (btnElement) {
    if (!btnElement) return;
    btnElement.classList.toggle('active');
    const checkSpan = btnElement.querySelector('.reason-check');
    if (checkSpan) {
        checkSpan.textContent = btnElement.classList.contains('active') ? '✓ NG' : 'NG';
    }
};


/* =========================================
   MODULE COPY DIALOG
========================================= */

if (typeof CopyTextDialog === "undefined") {

    window.CopyTextDialog = {

        show: function (
            title,
            copyText,
            onConfirm
        ) {

            const overlay =
                document.createElement('div');

            overlay.className =
                'copy-dialog-overlay';


            const box =
                document.createElement('div');

            box.className =
                'copy-dialog-box';


            let formattedTitle =
                title;


            if (title.includes('\n')) {

                const parts =
                    title.split('\n');

                formattedTitle =
                    `${parts[0]}
                    <br>
                    <span class="copy-dialog-subtitle">
                        ${parts[1]}
                    </span>`;

            }


            box.innerHTML = `

                <h3 class="copy-dialog-title">
                    ${formattedTitle}
                </h3>

                <div class="copy-content-wrapper">
                    ${copyText}
                </div>

                <div class="copy-dialog-actions">

                    <button type="button"
                        class="btn-copy-to-clipboard">

                        <span class="copy-icon">
                            📋
                        </span>

                        <span class="copy-btn-text">
                            Sao chép dữ liệu
                        </span>

                    </button>


                    <button type="button"
                        class="btn btn-primary confirm-copy-btn">

                        Xác nhận đã nhập báo cáo

                    </button>

                </div>

            `;


            overlay.appendChild(box);

            document.body.appendChild(overlay);


            if (
                !document.getElementById(
                    'copy-dialog-style'
                )
            ) {

                const style =
                    document.createElement('style');

                style.id =
                    'copy-dialog-style';


                style.innerHTML = `

                    .copy-dialog-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100vw;
                        height: 100vh;
                        background: rgba(0,0,0,0.45);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                    }

                    .copy-dialog-box {
                        background: #fff;
                        padding: 22px;
                        border-radius: 10px;
                        width: 90%;
                        max-width: 520px;
                        box-shadow:
                            0 10px 25px rgba(0,0,0,0.15);
                        font-family: sans-serif;
                        text-align: center;
                    }

                    .copy-dialog-title {
                        margin: 0 0 14px 0;
                        font-size: 16px;
                        color: #ea580c;
                        font-weight: 700;
                        line-height: 1.4;
                    }

                    .copy-dialog-subtitle {
                        display: block;
                        font-size: 13.5px;
                        font-weight: 600;
                        color: #dc2626;
                        margin-top: 4px;
                    }

                    .copy-content-wrapper {
                        background: #f8fafc;
                        border: 1px dashed #93c5fd;
                        border-radius: 6px;
                        padding: 12px;
                        margin-bottom: 16px;
                        word-break: break-all;
                        font-family: monospace;
                        font-size: 14px;
                        color: #0284c7;
                        font-weight: 800;
                    }

                    .copy-dialog-actions {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                    }

                    .btn-copy-to-clipboard {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                        width: 100%;
                        padding: 10px;
                        background: #f1f5f9;
                        color: #334155;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 600;
                    }

                    .btn-copy-to-clipboard.success {
                        background: #dcfce7;
                        color: #166534;
                        border-color: #bbf7d0;
                    }

                    .confirm-copy-btn {
                        width: 100%;
                        padding: 10px;
                        background: #16a34a;
                        color: white;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 700;
                    }

                    .confirm-copy-btn:hover {
                        background: #15803d;
                    }

                `;

                document.head.appendChild(style);
            }


            const copyBtn =
                box.querySelector(
                    '.btn-copy-to-clipboard'
                );

            const copyBtnText =
                box.querySelector(
                    '.copy-btn-text'
                );

            const confirmBtn =
                box.querySelector(
                    '.confirm-copy-btn'
                );


            copyBtn.addEventListener(
                'click',
                () => {

                    if (
                        typeof navigator !==
                        'undefined'
                        &&
                        navigator.clipboard
                        &&
                        typeof navigator
                            .clipboard
                            .writeText ===
                        'function'
                    ) {

                        navigator.clipboard
                            .writeText(copyText)
                            .then(() => {

                                copyBtn.classList.add(
                                    'success'
                                );

                                copyBtnText.textContent =
                                    'Đã sao chép thành công!';

                                setTimeout(() => {

                                    copyBtn.classList.remove(
                                        'success'
                                    );

                                    copyBtnText.textContent =
                                        'Sao chép dữ liệu';

                                }, 2000);

                            });

                    } else {

                        const textArea =
                            document.createElement(
                                "textarea"
                            );

                        textArea.value =
                            copyText;

                        document.body.appendChild(
                            textArea
                        );

                        textArea.select();

                        document.execCommand(
                            'copy'
                        );

                        document.body.removeChild(
                            textArea
                        );

                        copyBtn.classList.add(
                            'success'
                        );

                        copyBtnText.textContent =
                            'Đã sao chép thành công!';

                    }

                }
            );


            confirmBtn.addEventListener(
                'click',
                () => {

                    overlay.remove();

                    if (
                        typeof onConfirm ===
                        'function'
                    ) {

                        onConfirm();

                    }

                }
            );

        }

    };

}


/* =========================================
   HÀM ESCAPE HTML
========================================= */

function escapeHtml(value) {

    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/* =========================================
   HÀM XỬ LÝ: Hủy Bobin phía Cuộn
========================================= */

function handleWindingCancel(
    btnElement,
    bobinCode,
    bobinKeyCode
) {
    const container = btnElement.closest('.bobin-item');

    /* Fallback nếu PHP không truyền parameter */
    bobinCode = bobinCode || container?.dataset.ident || '';
    bobinKeyCode = bobinKeyCode || container?.dataset.key || '';

    if (!container || !bobinCode || !bobinKeyCode) {
        Toast.show("Lỗi: Thiếu mã định danh của Bobin!", "error");
        return;
    }

    const inMachine = container.querySelector('.winding-machine-name')?.value.trim() || '';
    const inEmployeeCode = container.querySelector('.winding-employee-code')?.value.trim() || '';
    const inEmployeeName = container.querySelector('.winding-employee-name')?.value.trim() || '';
    const cardNoteInput = container.querySelector('.winding-note-field');
    const originalNote = (cardNoteInput?.value.trim() || '');

    if (!inMachine || inMachine === 'Chưa cập nhật') {
        Toast.show(window.t ? window.t('toast_err_req_machine') : "⚠️ Vui lòng chọn hoặc nhập Mã máy cuộn trước khi hủy!", "error");
        const machineInput = container.querySelector('.winding-machine-name');
        if (machineInput) {
            machineInput.style.border = "2px solid #ef4444";
            machineInput.focus();
            setTimeout(() => machineInput.style.border = "1.5px solid #cbd5e1", 2000);
        }
        return;
    }

    if (!inEmployeeCode || inEmployeeCode === 'Chưa cập nhật') {
        Toast.show(window.t ? window.t('toast_err_req_emp_code') : "⚠️ Vui lòng nhập Mã nhân viên cuộn trước khi hủy!", "error");
        const empInput = container.querySelector('.winding-employee-code');
        if (empInput) {
            empInput.style.border = "2px solid #ef4444";
            empInput.focus();
            setTimeout(() => empInput.style.border = "1.5px solid #cbd5e1", 2000);
        }
        return;
    }

    if (!inEmployeeName || inEmployeeName === 'Chưa cập nhật') {
        Toast.show(window.t ? window.t('toast_err_req_emp_name') : "⚠️ Vui lòng nhập Họ tên nhân viên cuộn trước khi hủy!", "error");
        const nameInput = container.querySelector('.winding-employee-name');
        if (nameInput) {
            nameInput.style.border = "2px solid #ef4444";
            nameInput.focus();
            setTimeout(() => nameInput.style.border = "1.5px solid #cbd5e1", 2000);
        }
        return;
    }

    /* Đọc trạng thái các nút Ngoại quan / Thông khí đã chọn sẵn trên Card (nếu có) */
    const preSelectedErrors = Array.from(
        container.querySelectorAll('.card-error-toggle-btn.active')
    ).map(btn => btn.dataset.error);

    const isAppearanceActive = preSelectedErrors.includes('Ngoại quan');
    const isAirflowActive = preSelectedErrors.includes('Thông khí');

    /* Hàm tổng hợp chuỗi ghi chú hủy chuẩn theo lựa chọn lỗi + ghi chú chi tiết */
    const buildCombinedCancelNote = (customNote, selectedErrors) => {
        const cleanCustom = (customNote || '').trim();
        if (selectedErrors.length > 0) {
            const errorString = `Cuộn ${inMachine} yêu cầu Hủy - Lý do: ${selectedErrors.join(', ')} NG`;
            return cleanCustom ? `${cleanCustom} - ${errorString}` : errorString;
        }
        return cleanCustom;
    };

    const initialCombinedNote = buildCombinedCancelNote(originalNote, preSelectedErrors);

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        winding_machine: inMachine,
        winding_employee_code: inEmployeeCode,
        winding_employee_name: inEmployeeName,
        winding_note: initialCombinedNote
    };

    const lblStep1 = window.t ? window.t('winding_cancel_step1_title', '1. Chọn hạng mục phát sinh lỗi NG (Bắt buộc chọn ít nhất 1):') : '1. Chọn hạng mục phát sinh lỗi NG (Bắt buộc chọn ít nhất 1):';
    const lblApp = window.t ? window.t('winding_reason_appearance', 'Ngoại quan') : 'Ngoại quan';
    const lblAppSub = window.t ? window.t('winding_reason_appearance_sub', 'Bề mặt, màu, dị vật, chữ in') : 'Bề mặt, màu, dị vật, chữ in';
    const lblAir = window.t ? window.t('winding_reason_airflow', 'Thông khí') : 'Thông khí';
    const lblAirSub = window.t ? window.t('winding_reason_airflow_sub', 'Không đạt kiểm tra thông khí') : 'Không đạt kiểm tra thông khí';
    const lblStep2 = window.t ? window.t('winding_cancel_step2_note', '2. Ghi chú chi tiết lý do hủy (Tùy chọn bổ sung):') : '2. Ghi chú chi tiết lý do hủy (Tùy chọn bổ sung):';
    const phNote = window.t ? window.t('ph_winding_note_explain', 'Nhập thêm mô tả chi tiết lỗi (nếu có)...') : 'Nhập thêm mô tả chi tiết lỗi (nếu có)...';

    let reviewHTML = `
        <div id="winding-cancel-reason-box" style="
            margin-bottom: 12px;
            text-align: left;
            padding: 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            transition: all 0.2s ease;
        ">
            <strong style="color:#0f172a; font-size:13px; display:block; margin-bottom:8px;">
                <span data-i18n="winding_cancel_step1_title">${escapeHtml(lblStep1)}</span>
                <span style="color:#dc2626;">*</span>
            </strong>

            <div class="wnd-reason-grid">
                <button
                    type="button"
                    class="error-toggle-btn ${isAppearanceActive ? 'active' : ''}"
                    data-error="Ngoại quan">
                    <span class="err-btn-left">
                        <span class="err-btn-icon">🔍</span>
                        <span class="err-btn-info">
                            <span class="err-btn-title" data-i18n="winding_reason_appearance">${escapeHtml(lblApp)}</span>
                            <span class="err-btn-sub" data-i18n="winding_reason_appearance_sub">${escapeHtml(lblAppSub)}</span>
                        </span>
                    </span>
                    <span class="err-btn-badge">${isAppearanceActive ? '☑ NG' : '☐ OK'}</span>
                </button>

                <button
                    type="button"
                    class="error-toggle-btn ${isAirflowActive ? 'active' : ''}"
                    data-error="Thông khí">
                    <span class="err-btn-left">
                        <span class="err-btn-icon">💨</span>
                        <span class="err-btn-info">
                            <span class="err-btn-title" data-i18n="winding_reason_airflow">${escapeHtml(lblAir)}</span>
                            <span class="err-btn-sub" data-i18n="winding_reason_airflow_sub">${escapeHtml(lblAirSub)}</span>
                        </span>
                    </span>
                    <span class="err-btn-badge">${isAirflowActive ? '☑ NG' : '☐ OK'}</span>
                </button>
            </div>

            <div id="winding-cancel-reason-error" style="
                display:none;
                margin-top:8px;
                color:#dc2626;
                font-size:12.5px;
                font-weight:700;
            ">
                ⚠️ Vui lòng nhấn chọn ít nhất "Ngoại quan" hoặc "Thông khí" trước khi xác nhận hủy!
            </div>

            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                <label for="dialog-winding-note-input" style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:5px;">
                    <span data-i18n="winding_cancel_step2_note">${escapeHtml(lblStep2)}</span>
                </label>
                <input
                    type="text"
                    id="dialog-winding-note-input"
                    class="dialog-note-input"
                    value="${escapeHtml(originalNote)}"
                    placeholder="${escapeHtml(phNote)}">
            </div>
        </div>

        <ul style="
            list-style:none;
            padding:10px 12px;
            background:#f8fafc;
            border-radius:8px;
            text-align:left;
            border:1px solid #e2e8f0;
            margin:0;
        ">
            <li style="margin-bottom:6px; border-bottom:1px dashed #cbd5e1; padding-bottom:4px; display:flex; justify-content:space-between;">
                <strong>Mã Bobin:</strong>
                <span style="color:#0f172a; font-family:monospace; font-weight:800;">#${escapeHtml(bobinCode)}</span>
            </li>
            <li style="margin-bottom:6px; border-bottom:1px dashed #cbd5e1; padding-bottom:4px; display:flex; justify-content:space-between;">
                <strong>Máy cuộn:</strong>
                <span style="font-weight:700; color:#1d4ed8;">${escapeHtml(inMachine || 'Chưa chọn')}</span>
            </li>
            <li style="margin-bottom:6px; border-bottom:1px dashed #cbd5e1; padding-bottom:4px; display:flex; justify-content:space-between;">
                <strong>Nhân viên:</strong>
                <span>${escapeHtml(inEmployeeCode)} - ${escapeHtml(inEmployeeName)}</span>
            </li>
            <li style="padding-top:4px;">
                <strong>Nội dung ghi nhận hủy:</strong>
                <div id="dynamic-note-display" style="
                    margin-top:4px;
                    padding:6px 8px;
                    background:#fff1f2;
                    border:1px solid #fecdd3;
                    border-radius:5px;
                    color:#9f1239;
                    font-weight:700;
                    font-size:12.5px;
                ">
                    ${escapeHtml(initialCombinedNote || 'Chưa chọn hạng mục lỗi NG')}
                </div>
            </li>
        </ul>
    `;

    const syncDialogState = (dialogBox) => {
        const selectedErrors = Array.from(
            dialogBox.querySelectorAll('.error-toggle-btn.active')
        ).map(btn => btn.dataset.error);

        /* Đồng bộ ngược ra các nút trên Card của Bobin */
        container.querySelectorAll('.card-error-toggle-btn').forEach(cardBtn => {
            const isSelected = selectedErrors.includes(cardBtn.dataset.error);
            cardBtn.classList.toggle('active', isSelected);
            const checkSpan = cardBtn.querySelector('.reason-check');
            if (checkSpan) {
                checkSpan.textContent = isSelected ? '✓ NG' : 'NG';
            }
        });

        const reasonBox = dialogBox.querySelector('#winding-cancel-reason-box');
        const reasonError = dialogBox.querySelector('#winding-cancel-reason-error');
        if (selectedErrors.length > 0) {
            if (reasonBox) {
                reasonBox.style.borderColor = '#cbd5e1';
                reasonBox.style.background = '#f8fafc';
            }
            if (reasonError) {
                reasonError.style.display = 'none';
            }
        }

        const dialogNoteEl = dialogBox.querySelector('#dialog-winding-note-input');
        const currentCustomNote = dialogNoteEl ? dialogNoteEl.value.trim() : originalNote;

        /* Đồng bộ ô ghi chú trên Card */
        if (cardNoteInput && dialogNoteEl) {
            cardNoteInput.value = dialogNoteEl.value;
        }

        const newNote = buildCombinedCancelNote(currentCustomNote, selectedErrors);
        const noteDisplay = dialogBox.querySelector('#dynamic-note-display');
        if (noteDisplay) {
            noteDisplay.textContent = newNote || (window.t ? window.t('winding_cancel_no_reason_yet', 'Vui lòng chọn Ngoại quan hoặc Thông khí ở trên') : 'Vui lòng chọn Ngoại quan hoặc Thông khí ở trên');
        }
        dialogBox.dataset.currentNote = newNote;
    };

    ConfirmDialog.show(
        window.t ? window.t('winding_cancel_dialog_title', '⚠️ Xác nhận Hủy Bobin phía Cuộn') : '⚠️ Xác nhận Hủy Bobin phía Cuộn',
        `
        <div style="
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
            color:#7f1d1d;
            padding:10px 12px;
            border-radius:8px;
            font-weight:600;
            margin-bottom:12px;
            border-left:4px solid #dc2626;
            text-align:left;
        ">
            ❗ Hủy Bobin <strong style="color:#991b1b;">#${escapeHtml(bobinCode)}</strong> — Hành động này sẽ chuyển Bobin sang danh sách chờ hủy!
        </div>
        ${reviewHTML}
        `,
        async (dialogBox) => {
            syncDialogState(dialogBox);

            const selectedErrors = Array.from(
                dialogBox.querySelectorAll('.error-toggle-btn.active')
            ).map(btn => btn.dataset.error);

            if (selectedErrors.length === 0) {
                const errMsg = window.t
                    ? window.t('toast_err_winding_cancel_reason')
                    : '⚠️ Vui lòng chọn "Thông khí" hoặc "Ngoại quan" trên hộp thoại xác nhận trước khi hủy!';
                Toast.show(errMsg, 'error');

                const reasonBox = dialogBox.querySelector('#winding-cancel-reason-box');
                const reasonError = dialogBox.querySelector('#winding-cancel-reason-error');
                if (reasonBox) {
                    reasonBox.style.borderColor = '#ef4444';
                    reasonBox.style.background = '#fef2f2';
                }
                if (reasonError) {
                    reasonError.textContent = errMsg;
                    reasonError.style.display = 'block';
                }
                return false;
            }

            bodyData.cancel_errors = selectedErrors;
            bodyData.winding_note = dialogBox.dataset.currentNote || buildCombinedCancelNote(originalNote, selectedErrors);

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
                    if (window.Toast && window.Toast.flash) {
                        window.Toast.flash(data.message || (window.t ? window.t('toast_saved_success') : 'Cập nhật thành công!'), 'success');
                    }
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                console.error(error);
                Toast.show("Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        },
        (dialogBox) => {
            syncDialogState(dialogBox);
        },
        {
            isDanger: true,
            confirmText: window.t ? window.t('btn_confirm_cancel_bobin', '🗑️ Xác nhận đưa vào chờ hủy') : '🗑️ Xác nhận đưa vào chờ hủy'
        }
    );

    /* Gắn sự kiện lắng nghe khi người dùng gõ trực tiếp vào ô ghi chú trong hộp thoại */
    const activeDialogBox = document.querySelector('.confirm-dialog-box:last-of-type');
    if (activeDialogBox) {
        syncDialogState(activeDialogBox);
        const dialogNoteInput = activeDialogBox.querySelector('#dialog-winding-note-input');
        if (dialogNoteInput) {
            dialogNoteInput.addEventListener('input', () => syncDialogState(activeDialogBox));
        }
    }
}


/* =========================================
   HÀM XỬ LÝ: Xác nhận hoàn thành Cuộn
========================================= */

function handleWindingConfirm(
    btnElement,
    bobinCode,
    bobinKeyCode,
    mainInfoText
) {

    const container =
        btnElement.closest('.bobin-item');


    /* Fallback data-* */

    bobinCode =
        bobinCode
        || container?.dataset.ident
        || '';


    bobinKeyCode =
        bobinKeyCode
        || container?.dataset.key
        || '';


    mainInfoText =
        mainInfoText
        ||
        container
            ?.querySelector('.main-info')
            ?.textContent
            .trim()
        ||
        '';


    if (
        !container
        ||
        !bobinCode
        ||
        !bobinKeyCode
    ) {

        Toast.show(
            "Lỗi: Thiếu mã định danh của Bobin!",
            "error"
        );

        return;
    }


    const inMachine =
        container
            .querySelector(
                '.winding-machine-name'
            )
            ?.value
            .trim()
        || '';


    const inWinemployeeCode =
        container
            .querySelector(
                '.winding-employee-code'
            )
            ?.value
            .trim()
        || '';


    const inWinemployeeName =
        container
            .querySelector(
                '.winding-employee-name'
            )
            ?.value
            .trim()
        || '';


    const inNote =
        container
            .querySelector(
                '.winding-note-field'
            )
            ?.value
            .trim()
        || '';


    if (!inMachine) {

        Toast.show(
            "⚠️ Vui lòng nhập hoặc chọn Máy cuộn!",
            "error"
        );


        const mInput =
            container.querySelector(
                '.winding-machine-name'
            );


        if (mInput) {

            mInput.style.border =
                "2px solid red";

            mInput.focus();

            setTimeout(
                () =>
                    mInput.style.border =
                    "1px solid #cbd5e1",
                2000
            );

        }

        return;
    }


    if (!inWinemployeeCode) {

        Toast.show(
            "⚠️ Vui lòng nhập Mã nhân viên cuộn!",
            "error"
        );


        const empInput =
            container.querySelector(
                '.winding-employee-code'
            );


        if (empInput) {

            empInput.style.border =
                "2px solid red";

            empInput.focus();

            setTimeout(
                () =>
                    empInput.style.border =
                    "1px solid #cbd5e1",
                2000
            );

        }

        return;
    }


    const bodyData = {

        bobin_identification_code:
            bobinCode,

        bobin_key_code:
            bobinKeyCode,

        winding_machine:
            inMachine,

        winding_employee_code:
            inWinemployeeCode,

        winding_employee_name:
            inWinemployeeName,

        flow_test_result:
            "Thành công",

        winding_note:
            inNote

    };


    let reviewHTML = `

        <div style="
            padding:12px;
            background:#f0fdf4;
            border-radius:6px;
            text-align:center;
            border:1.5px dashed #16a34a;
            margin-bottom:10px;
        ">

            <p style="
                margin:0;
                color:#15803d;
                font-size:13px;
                font-weight:600;
            ">

                Mã định danh Bobin:

            </p>


            <p style="
                margin:4px 0 0 0;
                color:#166534;
                font-size:20px;
                font-weight:800;
                font-family:monospace;
            ">

                ${escapeHtml(bobinCode)}

            </p>

        </div>


        <ul style="
            list-style:none;
            padding:10px 12px;
            background:#f8fafc;
            border-radius:6px;
            text-align:left;
            border:1px solid #e2e8f0;
            margin:0;
        ">

            <li style="
                margin-bottom:5px;
                border-bottom:1px dashed #ccc;
                padding-bottom:3px;
                display:flex;
                justify-content:space-between;
            ">

                <strong>
                    Máy cuộn:
                </strong>

                <span>
                    ${escapeHtml(inMachine)}
                </span>

            </li>


            <li style="
                margin-bottom:5px;
                border-bottom:1px dashed #ccc;
                padding-bottom:3px;
                display:flex;
                justify-content:space-between;
            ">

                <strong>
                    Nhân viên:
                </strong>

                <span>
                    ${escapeHtml(inWinemployeeCode)}
                    -
                    ${escapeHtml(inWinemployeeName)}
                </span>

            </li>


            <li style="
                margin-bottom:5px;
                border-bottom:1px dashed #ccc;
                padding-bottom:3px;
                display:flex;
                justify-content:space-between;
            ">

                <strong>
                    Thông khí:
                </strong>

                <span style="
                    color:#16a34a;
                    font-weight:800;
                ">
                    Thành công
                </span>

            </li>


            <li style="
                display:flex;
                justify-content:space-between;
            ">

                <strong>
                    Ghi chú:
                </strong>

                <span>
                    ${escapeHtml(
        inNote
        || 'Không có ghi chú'
    )}
                </span>

            </li>

        </ul>

    `;


    ConfirmDialog.show(

        'Xác nhận hoàn thành Cuộn',

        `

        <div style="
            background-color:#e0f2fe;
            color:#0369a1;
            padding:8px 12px;
            border-radius:4px;
            font-weight:bold;
            margin-bottom:10px;
            border-left:4px solid #0284c7;
        ">

            ℹ️ Kiểm tra thông tin
            trước khi hoàn tất
            chu kỳ cuộn:

        </div>

        ${reviewHTML}

        `,


        async () => {

            const requestUrl =
                '/WEB_BOBIN/public/index.php?url=bobin/updateWindingBobin';


            const originalBtnText =
                btnElement.innerHTML;


            btnElement.innerHTML =
                'Đang lưu...';

            btnElement.disabled =
                true;


            try {

                const response =
                    await fetch(
                        requestUrl,
                        {
                            method: 'PUT',

                            headers: {
                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json'
                            },

                            body:
                                JSON.stringify(
                                    bodyData
                                )
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    CopyTextDialog.show(

                        "Hãy sao chép nội dung sau để nhập vào báo cáo. \n Lưu ý không thể hoàn tác sau khi nhấn Xác Nhận!",

                        mainInfoText,

                        () => {
                            if (window.Toast && window.Toast.flash) {
                                window.Toast.flash(data.message || (window.t ? window.t('toast_saved_success') : 'Xác nhận thành công!'), 'success');
                            }
                            window.location.reload();

                        }

                    );


                } else {

                    Toast.show(
                        "Lỗi: "
                        +
                        (
                            data.message
                            ||
                            "Cập nhật thất bại"
                        ),
                        'error'
                    );


                    btnElement.innerHTML =
                        originalBtnText;

                    btnElement.disabled =
                        false;

                }


            } catch (error) {

                console.error(
                    error
                );


                Toast.show(
                    "Đã xảy ra lỗi kết nối!",
                    'error'
                );


                btnElement.innerHTML =
                    originalBtnText;

                btnElement.disabled =
                    false;

            }

        }

    );

}