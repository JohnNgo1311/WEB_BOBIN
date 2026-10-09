/* =========================================
    MODULE TOAST & DIALOG
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

// Bấm nút bật/tắt OK/NG cho 5 tiêu chí đùn
function toggleExtCheck(btn) {
    if (btn.disabled) return;
    btn.classList.toggle('active');
    const isActive = btn.classList.contains('active');
    btn.dataset.value = isActive ? 'true' : 'false';
    btn.textContent = isActive ? 'OK' : 'NG';
}

// 1. BẮT ĐẦU CHỈNH SỬA
function startEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container) return;

    // Nếu đang có 1 Bobin khác sửa -> Chặn lại
    if (currentEditingContainer && currentEditingContainer !== container) {
        Toast.show("⚠️ Vui lòng hoàn thành hoặc hủy Bobin đang sửa trước!", "error");
        currentEditingContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    currentEditingContainer = container;

    // SAO LƯU DỮ LIỆU BAN ĐẦU ĐỂ PHỤC HỒI NẾU HỦY
    originalDataBackup = {
        product_code: container.querySelector('#product_code')?.value || '',
        production_order_code: container.querySelector('#production_order_code')?.value || '',
        extrusion_employee_code: container.querySelector('#extrusion_employee_code')?.value || '',
        extrusion_employee_name: container.querySelector('#extrusion_employee_name')?.value || '',
        bobin_type: container.querySelector('#bobin_type')?.value || '',
        material_lot: container.querySelector('#material_lot')?.value || '',
        extrusion_machine: container.querySelector('#extrusion_machine')?.value || '',
        material: container.querySelector('#material')?.value || '',
        grinding_time: container.querySelector('#grinding_time')?.value || '',
        print_lot: container.querySelector('#print_lot')?.value || '',
        length_m: container.querySelector('#length_m')?.value || '',
        rack_code: container.querySelector('#rack_code')?.value || '',
        shift: container.querySelector('#shift')?.value || '',
        extrusion_date: container.querySelector('#extrusion_date')?.value || '',
        finish_time: container.querySelector('#finish_time')?.value || '',
        extChecks: {}
    };

    container.querySelectorAll('.ext-toggle-btn').forEach(b => {
        const field = b.getAttribute('data-field');
        originalDataBackup.extChecks[field] = {
            value: b.getAttribute('data-value'),
            text: b.textContent.trim(),
            active: b.classList.contains('active')
        };
    });

    // KÍCH HOẠT CÁC Ô NHẬP LIỆU CỦA BOBIN NÀY
    const editableInputs = container.querySelectorAll(
        '#product_code, #extrusion_employee_code, #bobin_type, #material_lot, #extrusion_machine, #material, #grinding_time, #length_m, #rack_code, #shift, #extrusion_date, #finish_time'
    );
    editableInputs.forEach(input => {
        input.disabled = false;
    });

    // Mở khóa các nút toggle ngoại quan
    container.querySelectorAll('.ext-toggle-btn').forEach(b => b.disabled = false);

    // Thay đổi trạng thái nút bấm
    container.querySelector('.btn-edit').style.display = 'none';
    container.querySelector('.btn-cancel-edit').style.display = 'inline-flex';
    container.querySelector('.btn-confirm').style.display = 'inline-flex';

    // Đánh dấu card này đang sửa
    container.classList.add('is-editing');

    // Khóa tất cả các Bobin khác
    document.querySelectorAll('.bobin-item').forEach(item => {
        if (item !== container) {
            item.classList.add('is-locked');
        }
    });

    // Focus vào ô đầu tiên
    container.querySelector('#product_code')?.focus();
}

// 2. HỦY CHỈNH SỬA
function cancelEdit(btnElement) {
    const container = btnElement.closest('.bobin-item');
    if (!container || !originalDataBackup) return;

    // PHỤC HỒI TOÀN BỘ GIÁ TRỊ GỐC
    for (const [key, val] of Object.entries(originalDataBackup)) {
        if (key === 'extChecks') continue;
        const el = container.querySelector('#' + key);
        if (el) el.value = val;
    }

    // Phục hồi 5 nút ngoại quan
    for (const [field, state] of Object.entries(originalDataBackup.extChecks)) {
        const b = container.querySelector(`[data-field="${field}"]`);
        if (b) {
            b.setAttribute('data-value', state.value);
            b.textContent = state.text;
            if (state.active) b.classList.add('active');
            else b.classList.remove('active');
        }
    }

    // KHÓA LẠI TẤT CẢ Ô NHẬP LIỆU
    const editableInputs = container.querySelectorAll(
        '#product_code, #extrusion_employee_code, #bobin_type, #material_lot, #extrusion_machine, #material, #grinding_time, #length_m, #rack_code, #shift, #extrusion_date, #finish_time'
    );
    editableInputs.forEach(input => {
        input.disabled = true;
    });

    container.querySelectorAll('.ext-toggle-btn').forEach(b => b.disabled = true);

    // Đổi lại nút bấm
    container.querySelector('.btn-edit').style.display = 'inline-flex';
    container.querySelector('.btn-cancel-edit').style.display = 'none';
    container.querySelector('.btn-confirm').style.display = 'none';

    // Bỏ trạng thái sửa
    container.classList.remove('is-editing');

    // Mở khóa các Bobin khác
    document.querySelectorAll('.bobin-item').forEach(item => {
        item.classList.remove('is-locked');
    });

    currentEditingContainer = null;
    originalDataBackup = null;
    Toast.show("Đã hủy bỏ thay đổi", "success");
}

/* =========================================
    HÀM XỬ LÝ: Hủy Bobin
========================================= */
async function handleDelete(btnElement, bobinCode, bobinKeyCode) {
    const container = btnElement.closest('.bobin-item');
    if (!bobinCode) {
        Toast.show("Lỗi: Thiếu mã định danh của Bobin!", "error");
        return;
    }

    bobinKeyCode = bobinKeyCode || container?.dataset.bobinKey || '';

    const inEmployeeCode = container.querySelector('#extrusion_employee_code')?.value.trim() || '';
    const inEmployeeName = container.querySelector('#extrusion_employee_name')?.value.trim() || '';

    if (!inEmployeeCode || inEmployeeCode === 'Chưa cập nhật') {
        Toast.show("⚠️ Vui lòng bấm 'Chỉnh sửa' và nhập Mã nhân viên đùn trước khi hủy!", "error");
        return;
    }

    const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        extrusion_employee_code: inEmployeeCode,
        extrusion_employee_name: inEmployeeName,
    };

    const reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb; margin: 0;">
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #ccc; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace; font-weight: 700;">${bobinCode}</span></li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #ccc; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Mã nhân viên:</strong> <span>${inEmployeeCode}</span></li>
            <li style="display: flex; justify-content: space-between;"><strong>Họ tên nhân viên:</strong> <span>${inEmployeeName}</span></li>
        </ul>
    `;

    ConfirmDialog.show(
        '⚠️ Xác nhận Hủy Bobin',
        `
        <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 10px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 12px; border-left: 4px solid #dc2626;">
            ❗ Chuyển Bobin <strong style="color: #991b1b;">${bobinCode}</strong> sang danh sách chờ hủy?
        </div>
        ${reviewHTML}
        `,
        async () => {
            const requestUrl = `${API_BASE_URL}bobin/extrusionDeleteBobin`;
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
                    const successMsg = data.message || (window.t ? window.t('toast_saved_success') : 'Hủy thành công!');
                    if (window.Toast && window.Toast.flash) {
                        window.Toast.flash(successMsg, 'success');
                    } else {
                        Toast.show(successMsg, 'success');
                    }
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    Toast.show("Lỗi: " + (data.message || "Hủy thất bại"), 'error');
                    btnElement.innerHTML = originalBtnText;
                    btnElement.disabled = false;
                }
            } catch (error) {
                Toast.show(window.t ? window.t('toast_error_network') : "Đã xảy ra lỗi kết nối!", 'error');
                btnElement.innerHTML = originalBtnText;
                btnElement.disabled = false;
            }
        }
    );
}

/* =========================================
    HÀM XỬ LÝ: Cập nhật Bobin
========================================= */
async function handleConfirm(buttonElement, bobinCode) {
    try {
        const container = buttonElement.closest('.bobin-item');
        if (!container) throw new Error("Không tìm thấy dòng Bobin tương ứng.");

        const productCode = container.querySelector('#product_code')?.value.trim() || '';
        const orderCode = container.querySelector('#production_order_code')?.value.trim() || '';
        const empCode = container.querySelector('#extrusion_employee_code')?.value.trim() || '';
        const empName = container.querySelector('#extrusion_employee_name')?.value.trim() || '';
        const bobinType = container.querySelector('#bobin_type')?.value || '';
        const materialLot = container.querySelector('#material_lot')?.value.trim() || '';
        const material = container.querySelector('#material')?.value.trim() || '';
        const printLot = container.querySelector('#print_lot')?.value.trim() || '';
        const lengthM = container.querySelector('#length_m')?.value || 0;
        const rackCode = container.querySelector('#rack_code')?.value.trim() || '';
        const shift = container.querySelector('#shift')?.value || '';
        const extrusionDate = container.querySelector('#extrusion_date')?.value || '';
        const finishTimeRaw = container.querySelector('#finish_time')?.value.trim() || '';
        const finishTime = finishTimeRaw.replace('T', ' ');

        if (!empCode) {
            Toast.show("⚠️ Vui lòng nhập mã nhân viên đùn!", "error");
            container.querySelector('#extrusion_employee_code')?.focus();
            return;
        }
        if (!productCode) {
            Toast.show("⚠️ Vui lòng nhập mã sản phẩm!", "error");
            container.querySelector('#product_code')?.focus();
            return;
        }

        // Thu thập 5 nút ngoại quan Đùn
        const extChecks = {};
        container.querySelectorAll('.ext-toggle-btn').forEach(btn => {
            const field = btn.getAttribute('data-field');
            extChecks[field] = (btn.getAttribute('data-value') === 'true');
        });

        const payload = {
            bobin_identification_code: bobinCode,
            product_code: productCode,
            production_order_code: orderCode,
            extrusion_employee_code: empCode,
            extrusion_employee_name: empName,
            bobin_type: bobinType,
            material_lot: materialLot,
            material: material,
            print_lot: printLot,
            length_m: lengthM,
            rack_code: rackCode,
            shift: shift,
            extrusion_date: extrusionDate,
            finish_time: finishTime,
            ext_check_diameter: extChecks['ext_check_diameter'] ?? true,
            ext_check_gel: extChecks['ext_check_gel'] ?? true,
            ext_check_foreign_object: extChecks['ext_check_foreign_object'] ?? true,
            ext_check_color: extChecks['ext_check_color'] ?? true,
            ext_check_print: extChecks['ext_check_print'] ?? true
        };

        const extDisplay = [
            { label: window.t ? (window.t('crit_diameter') || 'Đường kính') : 'Đường kính', ok: payload.ext_check_diameter },
            { label: window.t ? (window.t('crit_gel') || 'Gel') : 'Gel', ok: payload.ext_check_gel },
            { label: window.t ? (window.t('crit_foreign') || 'Dị vật') : 'Dị vật', ok: payload.ext_check_foreign_object },
            { label: window.t ? (window.t('crit_color') || 'Màu sắc') : 'Màu sắc', ok: payload.ext_check_color },
            { label: window.t ? (window.t('crit_print') || 'Chữ in') : 'Chữ in', ok: payload.ext_check_print }
        ];

        let checksHTML = '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">';
        extDisplay.forEach(item => {
            checksHTML += `
                <div style="background:#fff;border:1px solid #cbd5e1;padding:3px 6px;border-radius:4px;display:flex;gap:4px;align-items:center;">
                    <span style="font-size:11px;color:#475569;">${item.label}:</span>
                    <span class="${item.ok ? 'vi-badge-ok' : 'vi-badge-ng'}">${item.ok ? 'OK' : 'NG'}</span>
                </div>
            `;
        });
        checksHTML += '</div>';

        const lblBobinCode = window.t ? (window.t('lbl_bobin_id_code') || 'Mã Bobin') : 'Mã Bobin';
        const lblProduct = window.t ? (window.t('field_product') || 'Mã sản phẩm') : 'Mã sản phẩm';
        const lblType = window.t ? (window.t('field_bobin_type') || 'Loại Bobin') : 'Loại Bobin';
        const lblEmp = window.t ? (window.t('field_employee_code') || 'Nhân viên') : 'Nhân viên';
        const lblRack = window.t ? (window.t('field_rack') || 'Vị trí Rack') : 'Vị trí Rack';
        const lblLength = window.t ? (window.t('field_length') || 'Chiều dài') : 'Chiều dài';
        const lblExtDate = window.t ? (window.t('field_ext_date') || 'Ngày đùn') : 'Ngày đùn';
        const lblFinishTime = window.t ? (window.t('field_finish_time') || 'Thời gian hoàn thành') : 'Thời gian hoàn thành';
        const lblChecksHeader = window.t ? (window.t('ext_criteria_title') || '🏭 Ngoại quan Đùn Check:') : '🏭 Ngoại quan Đùn Check:';

        const reviewHTML = `
            <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblBobinCode}:</strong> <span style="color: #0056b3; font-weight: bold;">${bobinCode}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblProduct}</strong> <span style="color: #dc2626; font-weight: bold;">${productCode}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblType}</strong> <span>${bobinType}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblEmp}</strong> <span>${empCode} - ${empName}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblRack}</strong> <span style="color: #0284c7; font-weight: 800;">${rackCode || '---'}</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblLength}</strong> <span>${lengthM} m</span></li>
                <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>${lblExtDate}</strong> <span>${extrusionDate}</span></li>
                <li style="display: flex; justify-content: space-between;"><strong>${lblFinishTime}</strong> <span>${finishTime}</span></li>
            </ul>
            <div style="margin-top:10px;text-align:left;padding:8px 10px;background:#f0f9ff;border-radius:6px;border:1px solid #bae6fd;">
                <strong style="font-size:12.5px;color:#0369a1;">${lblChecksHeader}</strong>
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
                    const apiUrl = `${API_BASE_URL}bobin/extrusionUpdateBobin`;
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