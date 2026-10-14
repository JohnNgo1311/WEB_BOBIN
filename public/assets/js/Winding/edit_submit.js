/* =========================================
    MODULE TOAST & DIALOG FOR WINDING EDIT
========================================= */
var Toast = window.Toast || {
    show(message, type = 'success') {
        if (window.Toast && window.Toast.show) return window.Toast.show(message, type);
    },
    flash(message, type = 'success') {
        if (window.Toast && window.Toast.flash) return window.Toast.flash(message, type);
    }
};

const ConfirmDialog = (() => {
    let styleInjected = false;

    const injectStyles = () => {
        if (styleInjected) return;
        const style = document.createElement('style');
        style.id = 'confirm-dialog-style';
        style.textContent = `
            .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 16px; box-sizing: border-box; }
            .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 12px; width: 100%; max-width: 520px; max-height: calc(100vh - 32px); display: flex; flex-direction: column; box-shadow: 0 10px 25px rgba(0,0,0,0.25); font-family: var(--font-family-base, -apple-system, BlinkMacSystemFont, sans-serif); box-sizing: border-box; }
            .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #0f172a; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-weight: 700; flex-shrink: 0; }
            .confirm-dialog-content { margin: 12px 0; font-size: 13.5px; color: #334155; overflow-y: auto; -webkit-overflow-scrolling: touch; flex: 1 1 auto; }
            .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; flex-shrink: 0; }
            .confirm-dialog-actions button { min-height: 40px; padding: 8px 18px !important; border-radius: 7px !important; font-size: 13.5px !important; cursor: pointer; }
            .vi-badge-ok { background: #22c55e; color: #fff; padding: 2px 7px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
            .vi-badge-ng { background: #ef4444; color: #fff; padding: 2px 7px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
        `;
        document.head.appendChild(style);
        styleInjected = true;
    };

    return {
        show(title, contentHTML, onConfirm, requirePassword = false) {
            injectStyles();

            const overlay = document.createElement('div');
            overlay.className = 'confirm-dialog-overlay';

            const pwdInputHTML = requirePassword ? `
                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                    <label style="display: block; font-weight: 700; color: #1e293b; margin-bottom: 6px; font-size: 13px;">
                        🔐 <span data-i18n="confirm_pwd_label">${window.t ? window.t('confirm_pwd_label') : 'Nhập mật khẩu tài khoản của bạn để xác nhận:'}</span>
                    </label>
                    <input type="password" id="confirm_dialog_password" class="form-control" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 14px;" placeholder="${window.t ? window.t('confirm_pwd_ph') : 'Nhập mật khẩu đăng nhập...'}" data-i18n-ph="confirm_pwd_ph">
                    <div id="confirm_pwd_error" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none; font-weight: 600;"></div>
                </div>
            ` : '';

            const box = document.createElement('div');
            box.className = 'confirm-dialog-box';
            box.innerHTML = `
                <h3 class="confirm-dialog-title">${title}</h3>
                <div class="confirm-dialog-content">${contentHTML}${pwdInputHTML}</div>
                <div class="confirm-dialog-actions">
                    <button type="button" class="btn btn-secondary cancel-btn" style="padding: 7px 16px; border: 1px solid #ccc; border-radius: 5px; cursor: pointer; background: #e2e8f0; color: #334155; font-weight: 600;" data-i18n="btn_cancel">${window.t ? window.t('btn_cancel') : 'Hủy bỏ'}</button>
                    <button type="button" class="btn btn-primary confirm-btn" style="padding: 7px 16px; border: none; border-radius: 5px; cursor: pointer; background: #2563eb; color: white; font-weight: 700;" data-i18n="btn_confirm">${window.t ? window.t('btn_confirm') : 'Xác nhận'}</button>
                </div>
            `;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            if (requirePassword) {
                setTimeout(() => {
                    const pwdEl = box.querySelector('#confirm_dialog_password');
                    if (pwdEl) pwdEl.focus();
                }, 100);
            }

            const handleClose = () => overlay.remove();
            box.querySelector('.cancel-btn').addEventListener('click', handleClose);
            box.querySelector('.confirm-btn').addEventListener('click', () => {
                if (requirePassword) {
                    const pwdEl = box.querySelector('#confirm_dialog_password');
                    const errEl = box.querySelector('#confirm_pwd_error');
                    const pwdVal = pwdEl ? pwdEl.value.trim() : '';
                    if (!pwdVal) {
                        if (errEl) {
                            errEl.textContent = window.t ? window.t('confirm_pwd_empty') : 'Vui lòng nhập mật khẩu xác nhận.';
                            errEl.style.display = 'block';
                        }
                        if (pwdEl) pwdEl.focus();
                        return;
                    }
                    handleClose();
                    if (typeof onConfirm === 'function') onConfirm(pwdVal);
                } else {
                    handleClose();
                    if (typeof onConfirm === 'function') onConfirm();
                }
            });
        }
    };
})();

/* =========================================
    LOGIC QUẢN LÝ CHẾ ĐỘ SỬA ĐỘC QUYỀN
========================================= */
let currentEditingContainer = null;
let originalDataBackup = null;

// Bắt đầu sửa Cuộn
function startWindingEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container) return;

    if (currentEditingContainer && currentEditingContainer !== container) {
        Toast.show("⚠️ Vui lòng hoàn thành hoặc hủy Bobin đang sửa trước!", "error");
        currentEditingContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    currentEditingContainer = container;

    // Backup dữ liệu ban đầu
    originalDataBackup = {
        machine: container.querySelector('.input-winding-machine')?.value || '',
        employeeCode: container.querySelector('.input-winding-employee-code')?.value || '',
        employeeName: container.querySelector('.input-winding-employee-name')?.value || '',
        flowResult: container.querySelector('.select-flow-result')?.value || 'Thành công',
        note: container.querySelector('.input-winding-note')?.value || ''
    };

    const inputs = container.querySelectorAll('.input-winding-machine, .input-winding-employee-code, .select-flow-result, .input-winding-note');
    inputs.forEach(input => input.disabled = false);

    container.querySelector('.btn-edit').style.display = 'none';
    if (container.querySelector('.btn-cancel-bobin')) {
        container.querySelector('.btn-cancel-bobin').style.display = 'none';
    }
    container.querySelector('.btn-cancel-edit').style.display = 'inline-flex';
    container.querySelector('.btn-confirm').style.display = 'inline-flex';

    container.classList.add('is-editing');
    document.querySelectorAll('.bobin-item').forEach(item => {
        if (item !== container) item.classList.add('is-locked');
    });

    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Hủy sửa Cuộn
function cancelWindingEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container || !originalDataBackup) return;

    if (container.querySelector('.input-winding-machine')) {
        container.querySelector('.input-winding-machine').value = originalDataBackup.machine;
    }
    if (container.querySelector('.input-winding-employee-code')) {
        container.querySelector('.input-winding-employee-code').value = originalDataBackup.employeeCode;
    }
    if (container.querySelector('.input-winding-employee-name')) {
        container.querySelector('.input-winding-employee-name').value = originalDataBackup.employeeName;
    }
    if (container.querySelector('.select-flow-result')) {
        container.querySelector('.select-flow-result').value = originalDataBackup.flowResult;
    }
    if (container.querySelector('.input-winding-note')) {
        container.querySelector('.input-winding-note').value = originalDataBackup.note;
    }

    const inputs = container.querySelectorAll('.input-winding-machine, .input-winding-employee-code, .select-flow-result, .input-winding-note');
    inputs.forEach(input => input.disabled = true);

    container.querySelector('.btn-edit').style.display = 'inline-flex';
    if (container.querySelector('.btn-cancel-bobin')) {
        container.querySelector('.btn-cancel-bobin').style.display = 'inline-flex';
    }
    container.querySelector('.btn-cancel-edit').style.display = 'none';
    container.querySelector('.btn-confirm').style.display = 'none';

    container.classList.remove('is-editing');
    document.querySelectorAll('.bobin-item').forEach(item => item.classList.remove('is-locked'));

    currentEditingContainer = null;
    originalDataBackup = null;
    Toast.show("Đã hủy bỏ thay đổi", "success");
}

// Xác nhận lưu thông tin Cuộn với mật khẩu
async function handleWindingConfirm(buttonElement, bobinCode, bobinKeyCode) {
    try {
        const container = buttonElement.closest('.bobin-item');
        if (!container) throw new Error("Không tìm thấy dòng Bobin tương ứng.");

        const machine = container.querySelector('.input-winding-machine')?.value.trim() || '';
        const employeeCode = container.querySelector('.input-winding-employee-code')?.value.trim() || '';
        const employeeName = container.querySelector('.input-winding-employee-name')?.value.trim() || '';
        const flowResult = container.querySelector('.select-flow-result')?.value || 'Thành công';
        const note = container.querySelector('.input-winding-note')?.value.trim() || '';

        if (!machine) {
            Toast.show("⚠️ Vui lòng nhập máy cuộn!", "error");
            container.querySelector('.input-winding-machine')?.focus();
            return;
        }

        const payload = {
            bobin_identification_code: bobinCode,
            bobin_key_code: bobinKeyCode,
            winding_machine: machine,
            winding_employee_code: employeeCode,
            flow_test_result: flowResult,
            winding_note: note
        };

        const reviewHTML = `
            <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #0056b3; font-weight: bold;">${bobinCode}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Máy cuộn:</strong> <span style="font-weight: 700;">${machine}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên cuộn:</strong> <span>${employeeCode} - ${employeeName}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Thông khí:</strong> <span class="${flowResult === 'Thành công' ? 'vi-badge-ok' : 'vi-badge-ng'}">${flowResult}</span></li>
                <li style="display: flex; justify-content: space-between;"><strong>Ghi chú:</strong> <span>${note || 'Không có'}</span></li>
            </ul>
        `;

        ConfirmDialog.show(
            window.t ? window.t('confirm_edit_title') : 'Kiểm tra thông tin cập nhật',
            `
            <div style="background-color: #e0f2fe; color: #0369a1; padding: 8px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #0284c7;">
                ℹ️ ${window.t ? window.t('confirm_edit_sub') : 'Xác nhận lưu các thay đổi cho công đoạn Cuộn của Bobin này:'}
            </div>
            ${reviewHTML}
            `,
            async (confirmedPassword) => {
                const originalText = buttonElement.innerText;
                buttonElement.innerText = window.t ? window.t('saving') : 'Đang lưu...';
                buttonElement.disabled = true;

                payload.confirm_password = confirmedPassword || '';

                try {
                    const apiUrl = `${API_BASE_URL}bobin/updateWindingEditBobin`;
                    const response = await fetch(apiUrl, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const res = await response.json();
                    if (res.success) {
                        if (window.Toast && window.Toast.flash) {
                            window.Toast.flash(res.message || (window.t ? window.t('toast_updated_success') : 'Cập nhật thành công!'), 'success');
                        }
                        buttonElement.innerText = window.t ? window.t('saved') : 'Đã lưu ✔️';
                        currentEditingContainer = null;
                        originalDataBackup = null;
                        setTimeout(() => window.location.reload(), 450);
                    } else {
                        Toast.show(`❌ ${res.message || (window.t ? window.t('save_failed') : 'Lưu thất bại')}`, 'error');
                        buttonElement.innerText = originalText;
                        buttonElement.disabled = false;
                    }
                } catch (err) {
                    Toast.show(`❌ ${err.message}`, 'error');
                    buttonElement.innerText = originalText;
                    buttonElement.disabled = false;
                }
            },
            true // requirePassword = true
        );
    } catch (err) {
        Toast.show(`❌ ${err.message}`, 'error');
    }
}

/* =========================================
    XỬ LÝ: HỦY BOBIN PHÍA ĐIỀU CHỈNH CUỘN (TASK-040 REFINEMENT)
========================================= */
async function handleWindingEditCancel(btnElement, bobinCode, bobinKeyCode) {
    if (!btnElement || !bobinCode || !bobinKeyCode) {
        Toast.show("⚠️ Lỗi: Thiếu thông tin định danh Bobin!", "error");
        return;
    }

    const container = btnElement.closest('.bobin-item');
    if (!container) return;

    if (currentEditingContainer && currentEditingContainer !== container) {
        Toast.show("⚠️ Vui lòng hoàn thành hoặc hủy Bobin đang sửa trước!", "error");
        currentEditingContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const machine = container.querySelector('.input-winding-machine')?.value.trim() || '';
    const employeeCode = container.querySelector('.input-winding-employee-code')?.value.trim() || (window.CURRENT_USER?.code || '');
    const employeeName = container.querySelector('.input-winding-employee-name')?.value.trim() || (window.CURRENT_USER?.name || '');
    const productCode = container.querySelector('.wnd-tag-product')?.textContent.trim() || 'Chưa cập nhật';
    const flowResult = container.querySelector('.select-flow-result')?.value || 'Thành công';

    // Xóa overlay cũ nếu có
    document.getElementById('winding-cancel-modal-overlay')?.remove();

    const overlay = document.createElement('div');
    overlay.className = 'wnd-modal-overlay';
    overlay.id = 'winding-cancel-modal-overlay';

    const t = (key, fallback) => (window.t ? window.t(key) : fallback);

    const titleText = t('winding_edit_cancel_title', 'Xác nhận Hủy Bobin');
    const descText = t('winding_edit_cancel_desc', 'Chuyển Bobin sang danh sách chờ hủy');
    const alertMsg = t('winding_cancel_alert_msg', 'Chuyển Bobin sang danh sách chờ hủy. Cần phê duyệt từ Quản trị viên để hoàn tất hủy.');
    const reasonLbl = t('winding_cancel_reason_lbl', 'Lý do hủy Bobin');
    const reasonPh = t('ph_winding_cancel_reason', 'Nhập lý do hủy Bobin (bắt buộc)...');
    const finishedQtyLbl = t('winding_cancel_finished_qty_lbl', 'Số lượng thành phẩm đã chạy trước khi hủy');
    const finishedQtyPh = t('ph_winding_cancel_finished_qty', 'VD: 150 (nhập 0 nếu chưa chạy)');
    const previewLbl = t('winding_cancel_preview_lbl', 'Nội dung ghi chú sẽ lưu:');
    const suggestionsLbl = t('winding_cancel_quick_suggestions', 'Gợi ý nhanh:');
    const unitRoll = t('qty_unit_roll', 'Cuộn');
    const btnCancelText = t('btn_cancel', 'Hủy bỏ');
    const btnConfirmText = t('btn_confirm_cancel_bobin', '🗑️ Xác nhận đưa vào chờ hủy');

    // Danh sách gợi ý lý do hủy: Thông khí, ngoại quan, A1, A2, A3, A4, A5
    const reasonSuggestions = [
        { label: t('chip_reason_airflow', 'Thông khí'), val: t('chip_reason_airflow', 'Thông khí') },
        { label: t('chip_reason_appearance', 'Ngoại quan'), val: t('chip_reason_appearance', 'Ngoại quan') },
        { label: 'A1', val: 'A1' },
        { label: 'A2', val: 'A2' },
        { label: 'A3', val: 'A3' },
        { label: 'A4', val: 'A4' },
        { label: 'A5', val: 'A5' }
    ];

    overlay.innerHTML = `
        <div class="wnd-modal-box" role="dialog" aria-modal="true" aria-labelledby="dlg_title">
            <div class="wnd-modal-header">
                <div class="wnd-modal-header-text">
                    <h3 class="wnd-modal-title" id="dlg_title">
                        <span>🗑️</span>
                        <span>${titleText}</span>
                    </h3>
                    <p class="wnd-modal-subtitle">${descText}</p>
                </div>
                <button type="button" class="wnd-modal-close" title="${btnCancelText}" aria-label="Close">✕</button>
            </div>

            <div class="wnd-modal-body">
                <!-- Cảnh báo trạng thái -->
                <div class="wnd-cancel-alert">
                    <span class="wnd-cancel-alert-icon">⚠️</span>
                    <div class="wnd-cancel-alert-content">
                        <div><strong>Bobin #${bobinCode}</strong></div>
                        <div style="font-size: 12px; margin-top: 2px;">${alertMsg}</div>
                    </div>
                </div>

                <!-- Tóm tắt thông tin Bobin -->
                <div class="wnd-cancel-summary">
                    <div class="wnd-summary-item">
                        <span class="wnd-summary-label">Mã Bobin</span>
                        <span class="wnd-summary-val mono">#${bobinCode}</span>
                    </div>
                    <div class="wnd-summary-item">
                        <span class="wnd-summary-label">Sản phẩm</span>
                        <span class="wnd-summary-val product">${productCode}</span>
                    </div>
                    <div class="wnd-summary-item">
                        <span class="wnd-summary-label">Máy cuộn</span>
                        <span class="wnd-summary-val">${machine || '---'}</span>
                    </div>
                    <div class="wnd-summary-item">
                        <span class="wnd-summary-label">Nhân viên cuộn</span>
                        <span class="wnd-summary-val">${employeeCode ? employeeCode + ' - ' + employeeName : '---'}</span>
                    </div>
                </div>

                <!-- Trường 1: Lý do hủy Bobin -->
                <div class="wnd-modal-field">
                    <label for="dlg_cancel_reason" class="wnd-modal-label">
                        <span>📝 ${reasonLbl}</span>
                        <span class="req">*</span>
                    </label>
                    <div class="wnd-modal-input-wrap">
                        <input type="text" id="dlg_cancel_reason" class="wnd-modal-input" placeholder="${reasonPh}" autocomplete="off">
                    </div>
                    <div id="dlg_cancel_reason_err" class="wnd-modal-error"></div>

                    <!-- Gợi ý nhanh lý do: Thông khí, ngoại quan, A1, A2, A3, A4, A5 -->
                    <div class="wnd-quick-chips-wrapper">
                        <span class="wnd-quick-chips-label">${suggestionsLbl}</span>
                        <div class="wnd-quick-chips" id="reason_chips_container">
                            ${reasonSuggestions.map(item => `
                                <button type="button" class="wnd-chip-btn" data-val="${item.val}">
                                    ${item.label}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <!-- Trường 2: Số thành phẩm đã chạy trước khi hủy (Đơn vị: Cuộn, không cần gợi ý nhanh) -->
                <div class="wnd-modal-field">
                    <label for="dlg_finished_qty" class="wnd-modal-label">
                        <span>🔢 ${finishedQtyLbl}</span>
                    </label>
                    <div class="wnd-modal-input-wrap">
                        <input type="number" min="0" step="1" id="dlg_finished_qty" class="wnd-modal-input has-unit" placeholder="${finishedQtyPh}">
                        <span class="wnd-modal-unit">${unitRoll}</span>
                    </div>
                </div>

                <!-- Preview ghi chú sẽ lưu -->
                <div class="wnd-cancel-preview-box">
                    <div class="wnd-preview-header">
                        <span>📋</span>
                        <span>${previewLbl}</span>
                    </div>
                    <div id="dlg_note_preview" class="wnd-preview-text">
                        Số thành phẩm đã chạy: 0 ${unitRoll} | Lý do hủy: ...
                    </div>
                </div>
            </div>

            <div class="wnd-modal-footer">
                <button type="button" class="wnd-btn-modal-cancel">${btnCancelText}</button>
                <button type="button" class="wnd-btn-modal-confirm">${btnConfirmText}</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    const reasonInput = overlay.querySelector('#dlg_cancel_reason');
    const qtyInput = overlay.querySelector('#dlg_finished_qty');
    const previewEl = overlay.querySelector('#dlg_note_preview');
    const errEl = overlay.querySelector('#dlg_cancel_reason_err');
    const closeBtn = overlay.querySelector('.wnd-modal-close');
    const cancelBtn = overlay.querySelector('.wnd-btn-modal-cancel');
    const confirmBtn = overlay.querySelector('.wnd-btn-modal-confirm');
    const reasonChips = overlay.querySelectorAll('#reason_chips_container .wnd-chip-btn');

    // Đóng modal
    const closeModal = () => {
        document.removeEventListener('keydown', handleKeyDown);
        overlay.remove();
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'Enter' && e.target === reasonInput) {
            confirmBtn.click();
        }
    };

    document.addEventListener('keydown', handleKeyDown);

    // Click ra ngoài overlay đóng modal
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Tự động focus vào ô nhập lý do
    setTimeout(() => reasonInput.focus(), 120);

    // Cập nhật live preview
    const updatePreview = () => {
        const reason = reasonInput.value.trim();
        const qtyVal = qtyInput.value.trim();
        const qtyText = qtyVal !== '' ? qtyVal : '0';

        if (reason) {
            previewEl.textContent = `Số thành phẩm đã chạy: ${qtyText} ${unitRoll} | Lý do hủy: ${reason}`;
            previewEl.style.opacity = '1';
            reasonInput.style.borderColor = '#cbd5e1';
            errEl.style.display = 'none';
        } else {
            previewEl.textContent = `Số thành phẩm đã chạy: ${qtyText} ${unitRoll} | Lý do hủy: ...`;
            previewEl.style.opacity = '0.7';
        }

        // Đồng bộ active state cho chips lý do
        const currentParts = reason.toLowerCase().split(',').map(s => s.trim()).filter(Boolean);
        reasonChips.forEach(chip => {
            const chipVal = chip.getAttribute('data-val').toLowerCase();
            chip.classList.toggle('active', currentParts.includes(chipVal));
        });
    };

    reasonInput.addEventListener('input', updatePreview);
    qtyInput.addEventListener('input', updatePreview);

    // Xử lý click chips lý do (hỗ trợ chọn nhanh / thêm / bỏ chọn)
    reasonChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const chipVal = chip.getAttribute('data-val');
            let current = reasonInput.value.trim();

            if (!current) {
                reasonInput.value = chipVal;
            } else {
                let parts = current.split(',').map(s => s.trim()).filter(Boolean);
                const idx = parts.findIndex(p => p.toLowerCase() === chipVal.toLowerCase());
                if (idx !== -1) {
                    parts.splice(idx, 1);
                } else {
                    parts.push(chipVal);
                }
                reasonInput.value = parts.join(', ');
            }
            updatePreview();
            reasonInput.focus();
        });
    });

    // Xử lý xác nhận hủy
    confirmBtn.addEventListener('click', async () => {
        const reason = reasonInput.value.trim();
        if (!reason) {
            reasonInput.style.borderColor = '#ef4444';
            errEl.textContent = t('err_winding_cancel_reason_required', 'Vui lòng nhập lý do hủy Bobin!');
            errEl.style.display = 'block';
            reasonInput.focus();
            return;
        }

        const qtyVal = qtyInput.value.trim();
        const qtyText = qtyVal !== '' ? qtyVal : '0';
        const combinedNote = `Số thành phẩm đã chạy: ${qtyText} ${unitRoll} | Lý do hủy: ${reason}`;

        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '⏳ ' + t('loading', 'Đang xử lý...');

        const payload = {
            bobin_identification_code: bobinCode,
            bobin_key_code: bobinKeyCode,
            winding_machine: machine || 'Chưa cập nhật',
            winding_employee_code: employeeCode,
            winding_employee_name: employeeName,
            flow_test_result: flowResult,
            winding_note: combinedNote,
            source: 'winding_edit',
            finished_product_qty: qtyText,
            cancel_reason: reason
        };

        try {
            const response = await fetch(API_BASE_URL + 'bobin/windingCancelBobin', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (data.success) {
                closeModal();
                if (window.Toast && window.Toast.flash) {
                    window.Toast.flash(data.message || t('toast_saved_success', 'Hủy thành công!'), 'success');
                } else {
                    Toast.show(data.message || 'Hủy thành công!', 'success');
                }
                setTimeout(() => window.location.reload(), 450);
            } else {
                errEl.textContent = data.message || 'Lỗi khi hủy Bobin!';
                errEl.style.display = 'block';
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = btnConfirmText;
            }
        } catch (error) {
            console.error(error);
            errEl.textContent = 'Đã xảy ra lỗi kết nối!';
            errEl.style.display = 'block';
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = btnConfirmText;
        }
    });
}
