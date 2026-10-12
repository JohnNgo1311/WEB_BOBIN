/* =========================================
   MODULE TOAST: Hiển thị thông báo
========================================= */
var Toast = window.Toast || {
  show(message, type = "success") {
    if (window.Toast && window.Toast.show)
      return window.Toast.show(message, type);
  },
  flash(message, type = "success") {
    if (window.Toast && window.Toast.flash)
      return window.Toast.flash(message, type);
  },
};

/* =========================================
   MODULE DIALOG: Hộp thoại xác nhận
========================================= */
if (typeof ConfirmDialog === "undefined") {
  window.ConfirmDialog = {
    show: function (title, contentHTML, onConfirm) {
      const overlay = document.createElement("div");
      overlay.className = "confirm-dialog-overlay";

      const box = document.createElement("div");
      box.className = "confirm-dialog-box";
      box.innerHTML = `
          <h3 class="confirm-dialog-title">${title}</h3>
          <div class="confirm-dialog-content">${contentHTML}</div>
          <div class="confirm-dialog-actions">
              <button type="button" class="btn btn-secondary cancel-btn" style="padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer; background: #e2e8f0; color: #334155; font-weight: 600;">Hủy bỏ</button>
              <button type="button" class="btn btn-primary confirm-btn" style="padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; background: #2563eb; color: white; font-weight: 700;">Xác nhận</button>
          </div>
      `;

      overlay.appendChild(box);
      document.body.appendChild(overlay);

      if (!document.getElementById("confirm-dialog-style")) {
        const style = document.createElement("style");
        style.id = "confirm-dialog-style";
        style.innerHTML = `
              .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(2px); }
              .confirm-dialog-box { background: #fff; padding: 20px 24px; border-radius: 12px; width: 92%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
              .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #0f172a; text-align: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px; font-weight: 800; }
              .confirm-dialog-content { margin: 12px 0; font-size: 13.5px; color: #334155; max-height: 65vh; overflow-y: auto; }
              .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; border-top: 1.5px solid #e2e8f0; padding-top: 12px; }
              .vi-badge-ok { background: #16a34a; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
              .vi-badge-ng { background: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
        `;
        document.head.appendChild(style);
      }

      box
        .querySelector(".cancel-btn")
        .addEventListener("click", () => overlay.remove());
      box.querySelector(".confirm-btn").addEventListener("click", () => {
        const dialogBox = box;
        overlay.remove();
        if (typeof onConfirm === "function") onConfirm(dialogBox);
      });
    },
  };
}

/* =========================================
   1. XÁC NHẬN HOÀN THÀNH QC
========================================= */
function handleConfirm(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("⚠️ Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inspectorCode =
    container?.querySelector(".input-inspector-code")?.value?.trim() || "";
  const inspectorName =
    container?.querySelector(".input-inspector-name")?.value?.trim() || "";
  const note = container?.querySelector(".note-field")?.value?.trim() || "";

  if (
    !inspectorCode ||
    inspectorCode === "Chưa cập nhật" ||
    inspectorCode === ""
  ) {
    Toast.show(
      "⚠️ Vui lòng nhập Mã số nhân viên QC trước khi xác nhận!",
      "error",
    );
    const inputCode = container?.querySelector(".input-inspector-code");
    if (inputCode) {
      inputCode.style.border = "2px solid red";
      inputCode.focus();
      setTimeout(() => (inputCode.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  const defects = {};
  container?.querySelectorAll(".toggle-switch[data-defect]")?.forEach((btn) => {
    const key = btn.getAttribute("data-defect");
    const isDefect = btn.getAttribute("data-value") === "true";
    defects[key] = isDefect;
  });

  const bodyData = {
    bobin_identification_code: bobinCode,
    bobin_key_code: bobinKeyCode,
    inspector_code: inspectorCode,
    inspector_name: inspectorName,
    defect_note: note,
    defect_gel: defects["gel"] || false,
    defect_foreign_object: defects["foreign_object"] || false,
    defect_color_issue: defects["color_issue"] || false,
    defect_print_quality: defects["print_quality"] || false,
  };

  const defectBadge = (hasDefect) =>
    hasDefect
      ? '<span class="vi-badge-ng">NG</span>'
      : '<span class="vi-badge-ok">OK</span>';

  let reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #0056b3; font-weight: 700;">${bobinCode}</span></li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span>${inspectorCode} - ${inspectorName}</span></li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Gel:</strong> ${defectBadge(bodyData.defect_gel)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Dị vật:</strong> ${defectBadge(bodyData.defect_foreign_object)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Màu sắc:</strong> ${defectBadge(bodyData.defect_color_issue)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Chữ in:</strong> ${defectBadge(bodyData.defect_print_quality)}</li>
            <li style="display: flex; justify-content: space-between;"><strong>Ghi chú:</strong> <span style="color: #334155;">${note || "Không có"}</span></li>
        </ul>
  `;

  ConfirmDialog.show(
    "Xác nhận kết quả kiểm tra QC",
    `
    <div style="background-color: #f0fdf4; color: #166534; padding: 8px 12px; border-radius: 6px; font-weight: 700; margin-bottom: 12px; border-left: 4px solid #16a34a;">
        ✓ Xác nhận kết quả QC để chuyển Bobin sang công đoạn Cuộn:
    </div>
    ${reviewHTML}
    `,
    async () => {
      const requestUrl = "/WEB_BOBIN/public/index.php?url=bobin/updateQCBobin";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "⏳ Đang lưu...";
      btnElement.disabled = true;

      try {
        const response = await fetch(requestUrl, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(bodyData),
        });

        const data = await response.json();
        if (data.success) {
          if (window.Toast && window.Toast.flash) {
            window.Toast.flash(
              data.message ||
                (window.t
                  ? window.t("toast_saved_success")
                  : "Cập nhật thành công!"),
              "success",
            );
          }
          setTimeout(() => window.location.reload(), 450);
        } else {
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối hệ thống!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    },
  );
}

/* =========================================
   2. HỦY BOBIN TẠI QC
========================================= */
function handleCancel(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("⚠️ Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inspectorCode =
    container?.querySelector(".input-inspector-code")?.value?.trim() || "";
  const inspectorName =
    container?.querySelector(".input-inspector-name")?.value?.trim() || "";
  const note = container?.querySelector(".note-field")?.value?.trim() || "";

  if (
    !inspectorCode ||
    inspectorCode === "Chưa cập nhật" ||
    inspectorCode === ""
  ) {
    Toast.show(
      window.t
        ? window.t("toast_err_req_emp_code")
        : "⚠️ Vui lòng nhập Mã số nhân viên QC trước khi hủy!",
      "error",
    );
    const inputCode = container?.querySelector(".input-inspector-code");
    if (inputCode) {
      inputCode.style.border = "2px solid red";
      inputCode.focus();
      setTimeout(() => (inputCode.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  if (
    !inspectorName ||
    inspectorName === "Chưa cập nhật" ||
    inspectorName === ""
  ) {
    Toast.show(
      window.t
        ? window.t("toast_err_req_emp_name")
        : "⚠️ Vui lòng nhập Họ tên nhân viên QC trước khi hủy!",
      "error",
    );
    const inputName = container?.querySelector(".input-inspector-name");
    if (inputName) {
      inputName.style.border = "2px solid red";
      inputName.focus();
      setTimeout(() => (inputName.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  if (!note || note === "Chưa cập nhật") {
    Toast.show(
      window.t
        ? window.t("toast_err_req_cancel_note")
        : "⚠️ Vui lòng nhập lý do hủy vào phần Ghi chú QC!",
      "error",
    );
    const noteField = container?.querySelector(".note-field");
    if (noteField) {
      noteField.style.border = "2px solid red";
      noteField.focus();
      setTimeout(() => (noteField.style.border = "1.5px solid #cbd5e1"), 2500);
    }
    return;
  }

  const defects = {};
  container?.querySelectorAll(".toggle-switch[data-defect]")?.forEach((btn) => {
    const key = btn.getAttribute("data-defect");
    const isDefect = btn.getAttribute("data-value") === "true";
    defects[key] = isDefect;
  });

  const bodyData = {
    bobin_identification_code: bobinCode,
    bobin_key_code: bobinKeyCode,
    inspector_code: inspectorCode,
    inspector_name: inspectorName,
    defect_note: note,
    defect_gel: defects["gel"] || false,
    defect_foreign_object: defects["foreign_object"] || false,
    defect_color_issue: defects["color_issue"] || false,
    defect_print_quality: defects["print_quality"] || false,
  };

  const hasNG = Boolean(
    bodyData.defect_gel ||
    bodyData.defect_foreign_object ||
    bodyData.defect_color_issue ||
    bodyData.defect_print_quality,
  );
  if (!hasNG) {
    Toast.show(
      window.t
        ? window.t("toast_err_qc_require_ng")
        : "⚠️ Chưa chọn hạng mục phán định là NG!",
      "error",
    );
    return;
  }

  const defectBadge = (hasDefect) =>
    hasDefect
      ? '<span class="vi-badge-ng">NG</span>'
      : '<span class="vi-badge-ok">OK</span>';

  let reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb; margin: 0;">
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace; font-weight: 700;">${bobinCode}</span></li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span>${inspectorCode} - ${inspectorName}</span></li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Gel:</strong> ${defectBadge(bodyData.defect_gel)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Dị vật:</strong> ${defectBadge(bodyData.defect_foreign_object)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Màu sắc:</strong> ${defectBadge(bodyData.defect_color_issue)}</li>
            <li style="margin-bottom: 6px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; display: flex; justify-content: space-between;"><strong>Chữ in:</strong> ${defectBadge(bodyData.defect_print_quality)}</li>
            <li style="padding-top: 4px; display: flex; justify-content: space-between;"><strong>Lý do hủy:</strong> <span style="color: #dc2626; font-weight: 700;">${note}</span></li>
        </ul>
  `;

  ConfirmDialog.show(
    "⚠️ Xác nhận Hủy QC",
    `
    <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 10px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 12px; border-left: 4px solid #dc2626;">
          ❗ Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Chuyển vào danh sách chờ hủy!
    </div>
    ${reviewHTML}
    `,
    async () => {
      const requestUrl = "/WEB_BOBIN/public/index.php?url=bobin/qcCancelBobin";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "⏳ Đang xử lý...";
      btnElement.disabled = true;

      try {
        const response = await fetch(requestUrl, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(bodyData),
        });
        const data = await response.json();
        if (data.success) {
          if (window.Toast && window.Toast.flash) {
            window.Toast.flash(
              data.message ||
                (window.t
                  ? window.t("toast_saved_success")
                  : "Hủy thành công!"),
              "success",
            );
          }
          setTimeout(() => window.location.reload(), 450);
        } else {
          Toast.show("Lỗi: " + (data.message || "Hủy thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối hệ thống!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    },
  );
}

/* =========================================
   3. ĐỔI LOẠI BOBIN SANG ĐIỀU CHỈNH
========================================= */
function handleChangeType(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("⚠️ Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inputCodeEl = container?.querySelector(".input-inspector-code");
  const inspectorCode = inputCodeEl?.value?.trim() || "";
  const inspectorName =
    container?.querySelector(".input-inspector-name")?.value?.trim() || "";

  if (
    !inspectorCode ||
    inspectorCode === "Chưa cập nhật" ||
    inspectorCode === ""
  ) {
    Toast.show(
      "⚠️ Vui lòng nhập Mã số nhân viên QC trước khi thực hiện!",
      "error",
    );
    if (inputCodeEl) {
      inputCodeEl.style.border = "2px solid red";
      inputCodeEl.focus();
      setTimeout(
        () => (inputCodeEl.style.border = "1.5px solid #cbd5e1"),
        2000,
      );
    }
    return;
  }

  const noteInputEl = container?.querySelector(".note-field");
  const note = noteInputEl?.value?.trim() || "";

  if (!note) {
    Toast.show(
      "⚠️ Vui lòng nhập ghi chú giải trình lý do trước khi đưa Bobin về ĐIỀU CHỈNH!",
      "error",
    );
    if (noteInputEl) {
      noteInputEl.style.border = "2px solid red";
      noteInputEl.focus();
      setTimeout(
        () => (noteInputEl.style.border = "1.5px solid #cbd5e1"),
        2500,
      );
    }
    return;
  }

  const defects = {};
  container?.querySelectorAll(".vi-item-switch")?.forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect =
      switchItem.querySelector(".toggle-switch")?.getAttribute("data-value") ===
      "true";
    defects[key] = goodDefect;
  });

  let dialogHTML = `
      <style>
          .type-toggle-group { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
          .type-toggle-input { display: none; }
          .type-toggle-label { 
              display: flex; align-items: center; justify-content: space-between;
              padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 6px;
              background: #f8fafc; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s; font-size: 13px;
          }
          .type-toggle-input:checked + .type-toggle-label {
              border-color: #f59e0b; background: #fffbeb; color: #b45309; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.15);
          }
          .type-toggle-input:checked + .type-toggle-label::after {
              content: '✔'; font-weight: 900; color: #b45309;
          }
      </style>
      
      <div style="background-color: #fffbeb; color: #b45309; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid #f59e0b;">
          Chuyển đổi Bobin <strong>${bobinCode}</strong> sang hàng ĐIỀU CHỈNH.<br>
          <div style="margin-top: 4px; font-size: 12.5px;"><strong>📝 Ghi chú:</strong> ${note}</div>
      </div>
      
      <div class="type-toggle-group">
          <div class="type-toggle-item">
              <input type="radio" id="type_cp" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Do CP)" checked>
              <label for="type_cp" class="type-toggle-label">Điều chỉnh (Do CP)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_gel" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Gel)">
              <label for="type_gel" class="type-toggle-label">Điều chỉnh (Ngoại quan: Gel)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_divat" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Dị vật)">
              <label for="type_divat" class="type-toggle-label">Điều chỉnh (Ngoại quan: Dị vật)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_tray" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Trầy)">
              <label for="type_tray" class="type-toggle-label">Điều chỉnh (Ngoại quan: Trầy)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_biendang" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Biến dạng)">
              <label for="type_biendang" class="type-toggle-label">Điều chỉnh (Ngoại quan: Biến dạng)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_xuoc" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Xước)">
              <label for="type_xuoc" class="type-toggle-label">Điều chỉnh (Ngoại quan: Xước)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_chuin" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Chữ in)">
              <label for="type_chuin" class="type-toggle-label">Điều chỉnh (Ngoại quan: Chữ in)</label> 
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_voncuc" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Vón cục)">
              <label for="type_voncuc" class="type-toggle-label">Điều chỉnh (Ngoại quan: Vón cục)</label>
          </div>
          <div class="type-toggle-item">
              <input type="radio" id="type_mau" name="new_bobin_type" class="type-toggle-input" value="Điều chỉnh (Ngoại quan: Màu)">
              <label for="type_mau" class="type-toggle-label">Điều chỉnh (Ngoại quan: Màu)</label>
          </div>
      </div>
  `;

  ConfirmDialog.show(
    "Xác nhận QC & Đổi loại Bobin",
    dialogHTML,
    async (dialogBox) => {
      const selectedType =
        (dialogBox
          ? dialogBox.querySelector('input[name="new_bobin_type"]:checked')
              ?.value
          : null) ||
        document.querySelector('input[name="new_bobin_type"]:checked')?.value ||
        "Điều chỉnh (Do CP)";

      const prefixNote = `Phán định chuyển Bobin sang: ${selectedType}`;
      const fullNote = note ? `${prefixNote} - ${note}` : prefixNote;

      const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        new_bobin_type: selectedType,
        inspector_code: inspectorCode,
        inspector_name: inspectorName,
        defect_note: fullNote,
        defect_gel: defects["gel"] || false,
        defect_foreign_object: defects["foreign_object"] || false,
        defect_color_issue: defects["color_issue"] || false,
        defect_print_quality: defects["print_quality"] || false,
      };

      const requestUrl =
        "/WEB_BOBIN/public/index.php?url=bobin/updateQCAndChangeType";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "⏳ Đang chuyển...";
      btnElement.disabled = true;

      try {
        const response = await fetch(requestUrl, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(bodyData),
        });

        const data = await response.json();
        if (data.success) {
          if (window.Toast && window.Toast.flash) {
            window.Toast.flash(
              data.message ||
                (window.t
                  ? window.t("toast_saved_success")
                  : "Cập nhật thành công!"),
              "success",
            );
          }
          setTimeout(() => window.location.reload(), 450);
        } else {
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối hệ thống!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    },
  );
}
