/* =========================================
    MODULE TOAST & DIALOG FOR QC EDIT
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

// Bấm nút bật/tắt OK/NG cho tiêu chí QC
function toggleQCCheck(btn) {
    if (btn.disabled) return;
    btn.classList.toggle('active');
    const isActive = btn.classList.contains('active');
    btn.dataset.value = isActive ? 'true' : 'false';
    btn.textContent = isActive ? 'OK' : 'NG';
}

// Bắt đầu sửa
function startQCEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container) return;

    if (currentEditingContainer && currentEditingContainer !== container) {
        Toast.show("⚠️ Vui lòng hoàn thành hoặc hủy Bobin đang sửa trước!", "error");
        currentEditingContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    currentEditingContainer = container;

    // Backup dữ liệu
    originalDataBackup = {
        inspectorCode: container.querySelector('.input-inspector-code')?.value || '',
        inspectorName: container.querySelector('.input-inspector-name')?.value || '',
        note: container.querySelector('.qc-note-input')?.value || '',
        bobinType: container.querySelector('.select-bobin-type')?.value || '',
        qcChecks: {}
    };

    container.querySelectorAll('.qc-toggle-btn').forEach(btn => {
        const field = btn.getAttribute('data-field');
        originalDataBackup.qcChecks[field] = {
            value: btn.getAttribute('data-value'),
            active: btn.classList.contains('active'),
            text: btn.textContent.trim()
        };
        btn.disabled = false;
    });

    const inputs = container.querySelectorAll('.input-inspector-code, .qc-note-input, .select-bobin-type');
    inputs.forEach(input => input.disabled = false);

    // Chuyển nút bấm
    container.querySelector('.btn-edit').style.display = 'none';
    container.querySelector('.btn-cancel-edit').style.display = 'inline-flex';
    container.querySelector('.btn-confirm').style.display = 'inline-flex';

    container.classList.add('is-editing');
    document.querySelectorAll('.bobin-item').forEach(item => {
        if (item !== container) item.classList.add('is-locked');
    });

    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Hủy sửa
function cancelQCEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container || !originalDataBackup) return;

    if (container.querySelector('.input-inspector-code')) {
        container.querySelector('.input-inspector-code').value = originalDataBackup.inspectorCode;
    }
    if (container.querySelector('.input-inspector-name')) {
        container.querySelector('.input-inspector-name').value = originalDataBackup.inspectorName;
    }
    if (container.querySelector('.qc-note-input')) {
        container.querySelector('.qc-note-input').value = originalDataBackup.note;
    }
    if (container.querySelector('.select-bobin-type')) {
        container.querySelector('.select-bobin-type').value = originalDataBackup.bobinType;
    }

    for (const [field, state] of Object.entries(originalDataBackup.qcChecks)) {
        const b = container.querySelector(`[data-field="${field}"]`);
        if (b) {
            b.setAttribute('data-value', state.value);
            b.textContent = state.text;
            if (state.active) b.classList.add('active');
            else b.classList.remove('active');
            b.disabled = true;
        }
    }

    const inputs = container.querySelectorAll('.input-inspector-code, .qc-note-input, .select-bobin-type');
    inputs.forEach(input => input.disabled = true);

    container.querySelector('.btn-edit').style.display = 'inline-flex';
    container.querySelector('.btn-cancel-edit').style.display = 'none';
    container.querySelector('.btn-confirm').style.display = 'none';

    container.classList.remove('is-editing');
    document.querySelectorAll('.bobin-item').forEach(item => item.classList.remove('is-locked'));

    currentEditingContainer = null;
    originalDataBackup = null;
    Toast.show("Đã hủy bỏ thay đổi", "success");
}

// Xác nhận lưu với mật khẩu
async function handleQCConfirm(buttonElement, bobinCode, bobinKeyCode) {
    try {
        const container = buttonElement.closest('.bobin-item');
        if (!container) throw new Error("Không tìm thấy dòng Bobin tương ứng.");

        const inspectorCode = container.querySelector('.input-inspector-code')?.value.trim() || '';
        const inspectorName = container.querySelector('.input-inspector-name')?.value.trim() || '';
        const note = container.querySelector('.qc-note-input')?.value.trim() || '';
        const bobinType = container.querySelector('.select-bobin-type')?.value || '';

        if (!inspectorCode) {
            Toast.show("⚠️ Vui lòng nhập mã nhân viên QC!", "error");
            container.querySelector('.input-inspector-code')?.focus();
            return;
        }

        const qcChecks = {};
        container.querySelectorAll('.qc-toggle-btn').forEach(btn => {
            const field = btn.getAttribute('data-field');
            qcChecks[field] = (btn.getAttribute('data-value') === 'true');
        });

        // Chú ý: trong DB, defect_* true nghĩa là có lỗi; nhưng UI toggle active = OK (không có lỗi)
        const payload = {
            bobin_identification_code: bobinCode,
            bobin_key_code: bobinKeyCode,
            inspector_code: inspectorCode,
            inspector_name: inspectorName,
            bobin_type: bobinType,
            defect_gel: !qcChecks['qc_check_gel'],
            defect_foreign_object: !qcChecks['qc_check_foreign_object'],
            defect_color_issue: !qcChecks['qc_check_color'],
            defect_print_quality: !qcChecks['qc_check_print'],
            defect_note: note || 'Chưa cập nhật'
        };

        const qcDisplay = [
            { label: 'Gel', ok: !payload.defect_gel },
            { label: 'Dị vật', ok: !payload.defect_foreign_object },
            { label: 'Màu sắc', ok: !payload.defect_color_issue },
            { label: 'Chữ in', ok: !payload.defect_print_quality }
        ];

        let checksHTML = '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">';
        qcDisplay.forEach(item => {
            checksHTML += `
                <div style="background:#fff;border:1px solid #cbd5e1;padding:3px 6px;border-radius:4px;display:flex;gap:4px;align-items:center;">
                    <span style="font-size:11px;color:#475569;">${item.label}:</span>
                    <span class="${item.ok ? 'vi-badge-ok' : 'vi-badge-ng'}">${item.ok ? 'OK' : 'NG'}</span>
                </div>
            `;
        });
        checksHTML += '</div>';

        const reviewHTML = `
            <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #0056b3; font-weight: bold;">${bobinCode}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Loại Bobin:</strong> <span>${bobinType}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span>${inspectorCode} - ${inspectorName}</span></li>
                <li style="display: flex; justify-content: space-between;"><strong>Ghi chú:</strong> <span>${note || 'Không có'}</span></li>
            </ul>
            <div style="margin-top:10px;text-align:left;padding:8px 10px;background:#f0f9ff;border-radius:6px;border:1px solid #bae6fd;">
                <strong style="font-size:12.5px;color:#0369a1;">🛡️ Kết quả ngoại quan QC:</strong>
                ${checksHTML}
            </div>
        `;

        ConfirmDialog.show(
            window.t ? window.t('confirm_edit_title') : 'Kiểm tra thông tin cập nhật',
            `
            <div style="background-color: #e0f2fe; color: #0369a1; padding: 8px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #0284c7;">
                ℹ️ ${window.t ? window.t('confirm_edit_sub') : 'Xác nhận lưu các thay đổi cho Bobin này:'}
            </div>
            ${reviewHTML}
            `,
            async (confirmedPassword) => {
                const originalText = buttonElement.innerText;
                buttonElement.innerText = window.t ? window.t('saving') : 'Đang lưu...';
                buttonElement.disabled = true;

                payload.confirm_password = confirmedPassword || '';

                try {
                    const apiUrl = `${API_BASE_URL}bobin/updateQCEditBobin`;
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
                        Toast.show(res.message, 'success');
                        buttonElement.innerText = window.t ? window.t('saved') : 'Đã lưu ✔️';
                        currentEditingContainer = null;
                        originalDataBackup = null;
                        setTimeout(() => window.location.reload(), 800);
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
