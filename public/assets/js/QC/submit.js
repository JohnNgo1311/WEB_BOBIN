/* =========================================
   MODULE TOAST: Hiển thị thông báo (Tránh khai báo trùng)
========================================= */
if (typeof Toast === "undefined") {
  window.Toast = {
    show: function (message, type = "success") {
      const toast = document.createElement("div");
      toast.className = `toast-message ${type === "error" ? "toast-error" : ""}`;
      toast.innerHTML = message;
      document.body.appendChild(toast);

      setTimeout(() => toast.classList.add("show"), 10);

      setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
      }, 2500);
    },
  };
}

/* =========================================
   MODULE DIALOG: Hiển thị bảng xác nhận (Tránh khai báo trùng)
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
              <button type="button" class="btn btn-secondary cancel-btn" style="padding: 7px 16px; border: 1px solid #cbd5e1; border-radius: 5px; cursor: pointer; background: #e2e8f0; color: #334155; font-weight: 600;">Trở lại</button>
              <button type="button" class="btn btn-primary confirm-btn" style="padding: 7px 16px; border: none; border-radius: 5px; cursor: pointer; background: #2563eb; color: white; font-weight: 700;">Xác nhận</button>
          </div>
      `;

      overlay.appendChild(box);
      document.body.appendChild(overlay);

      if (!document.getElementById("confirm-dialog-style")) {
        const style = document.createElement("style");
        style.id = "confirm-dialog-style";
        style.innerHTML = `
              .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
              .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 520px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
              .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 8px; font-weight: 700; }
              .confirm-dialog-content { margin: 12px 0; font-size: 13.5px; color: #333; max-height: 65vh; overflow-y: auto; }
              .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
              .vi-badge-ok { background: #16a34a; color: #fff; padding: 2px 7px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
              .vi-badge-ng { background: #dc2626; color: #fff; padding: 2px 7px; border-radius: 4px; font-weight: 800; font-size: 11.5px; }
        `;
        document.head.appendChild(style);
      }

      box.querySelector(".cancel-btn").addEventListener("click", () => overlay.remove());
      box.querySelector(".confirm-btn").addEventListener("click", () => {
        const dialogBox = box;
        overlay.remove();
        if (typeof onConfirm === "function") onConfirm(dialogBox);
      });
    },
  };
}

/* =========================================
   HÀM XỬ LÝ: Xác nhận QC Bobin
========================================= */
function handleConfirm(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inspectorCode = container.querySelector(".input-inspector-code")?.value.trim() || "";
  const inspectorName = container.querySelector(".input-inspector-name")?.value.trim() || "";
  const note = container.querySelector(".note-field")?.value.trim() || "";

  if (!inspectorCode || inspectorCode === "Chưa cập nhật") {
    Toast.show("⚠️ Vui lòng nhập Mã số nhân viên QC!", "error");
    const inputCode = container.querySelector(".input-inspector-code");
    if (inputCode) {
      inputCode.style.border = "2px solid red";
      inputCode.focus();
      setTimeout(() => (inputCode.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  const defects = {};
  container.querySelectorAll(".vi-item-switch").forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect = switchItem.querySelector(".toggle-switch")?.getAttribute("data-value") === "true";
    defects[key] = goodDefect;
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

  const defectBadge = (val) =>
    val ? '<span class="vi-badge-ok">OK</span>' : '<span class="vi-badge-ng">NG</span>';

  let reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f8fafc; border-radius: 6px; text-align: left; border: 1px solid #e2e8f0; margin: 0;">
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Mã định danh Bobin:</strong> <span style="color: #0056b3; font-weight: bold;">${bobinCode}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span>${inspectorCode} - ${inspectorName}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Gel:</strong> ${defectBadge(bodyData.defect_gel)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Dị vật:</strong> ${defectBadge(bodyData.defect_foreign_object)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Màu sắc:</strong> ${defectBadge(bodyData.defect_color_issue)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Chữ in:</strong> ${defectBadge(bodyData.defect_print_quality)}</li>
            <li style="display: flex; justify-content: space-between;"><strong>Ghi chú:</strong> <span>${note || "Không có ghi chú"}</span></li>
        </ul>
  `;

  ConfirmDialog.show(
    "Xác nhận kết quả kiểm tra QC",
    `
    <div style="background-color: #f0fdf4; color: #166534; padding: 8px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #16a34a;">
        ✓ Xác nhận kết quả QC để chuyển Bobin sang công đoạn Cuộn:
    </div>
    ${reviewHTML}
    `,
    async () => {
      const requestUrl = "/WEB_BOBIN/public/index.php?url=bobin/updateQCBobin";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "Đang lưu...";
      btnElement.disabled = true;

      try {
        const response = await fetch(requestUrl, {
          method: "PUT",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(bodyData),
        });

        const data = await response.json();
        if (data.success) {
          Toast.show(data.message, "success");
          setTimeout(() => window.location.reload(), 1200);
        } else {
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    }
  );
}

/* =========================================
   HÀM XỬ LÝ: Hủy QC Bobin
========================================= */
function handleCancel(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inspectorCode = container.querySelector(".input-inspector-code")?.value.trim() || "";
  const inspectorName = container.querySelector(".input-inspector-name")?.value.trim() || "";
  const note = container.querySelector(".note-field")?.value.trim() || "";

  if (!inspectorCode || inspectorCode === "Chưa cập nhật") {
    Toast.show("⚠️ Vui lòng nhập Mã số nhân viên QC trước khi hủy!", "error");
    const inputCode = container.querySelector(".input-inspector-code");
    if (inputCode) {
      inputCode.style.border = "2px solid red";
      inputCode.focus();
      setTimeout(() => (inputCode.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  if (!note) {
    Toast.show("⚠️ Vui lòng nhập lý do hủy vào phần Ghi chú!", "error");
    const noteField = container.querySelector(".note-field");
    if (noteField) {
      noteField.style.border = "2px solid red";
      noteField.focus();
      setTimeout(() => (noteField.style.border = "1.5px solid #cbd5e1"), 2500);
    }
    return;
  }

  const defects = {};
  container.querySelectorAll(".vi-item-switch").forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect = switchItem.querySelector(".toggle-switch")?.getAttribute("data-value") === "true";
    defects[key] = goodDefect;
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

  const defectBadge = (val) =>
    val ? '<span class="vi-badge-ok">OK</span>' : '<span class="vi-badge-ng">NG</span>';

  let reviewHTML = `
        <ul style="list-style: none; padding: 10px 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb; margin: 0;">
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace; font-weight: 700;">${bobinCode}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span>${inspectorCode} - ${inspectorName}</span></li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Gel:</strong> ${defectBadge(bodyData.defect_gel)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Dị vật:</strong> ${defectBadge(bodyData.defect_foreign_object)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Màu sắc:</strong> ${defectBadge(bodyData.defect_color_issue)}</li>
            <li style="margin-bottom: 5px; border-bottom: 1px dashed #ccc; padding-bottom: 3px; display: flex; justify-content: space-between;"><strong>Chữ in:</strong> ${defectBadge(bodyData.defect_print_quality)}</li>
            <li style="padding-top: 5px; margin-top: 3px; border-top: 1px dashed #cbd5e1;"><strong>Lý do hủy:</strong> <span style="color: #dc2626; font-weight: 700; display: block; margin-top: 2px;">${note}</span></li>
        </ul>
  `;

  ConfirmDialog.show(
    "⚠️ Xác nhận Hủy QC",
    `
    <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 10px 12px; border-radius: 6px; font-weight: 600; margin-bottom: 12px; border-left: 4px solid #dc2626;">
          ❗ Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Hành động này sẽ chuyển sang danh sách chờ hủy!
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
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(bodyData),
        });
        const data = await response.json();
        if (data.success) {
          Toast.show(data.message, "success");
          setTimeout(() => window.location.reload(), 1200);
        } else {
          Toast.show("Lỗi: " + (data.message || "Hủy thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    }
  );
}

/* =========================================
   HÀM XỬ LÝ: Đổi loại Bobin sang ĐIỀU CHỈNH
========================================= */
function handleChangeType(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inspectorCode = container.querySelector(".input-inspector-code")?.value.trim() || "";
  const inspectorName = container.querySelector(".input-inspector-name")?.value.trim() || "";
  const note = container.querySelector(".note-field")?.value.trim() || "";

  if (!inspectorCode || inspectorCode === "Chưa cập nhật") {
    Toast.show("⚠️ Vui lòng nhập Mã số nhân viên QC trước khi thực hiện!", "error");
    const inputCode = container.querySelector(".input-inspector-code");
    if (inputCode) {
      inputCode.style.border = "2px solid red";
      inputCode.focus();
      setTimeout(() => (inputCode.style.border = "1.5px solid #cbd5e1"), 2000);
    }
    return;
  }

  if (!note) {
    Toast.show("⚠️ Vui lòng nhập lý do giải trình vào phần Ghi chú trước khi chuyển loại!", "error");
    const noteField = container.querySelector(".note-field");
    if (noteField) {
      noteField.style.border = "2px solid red";
      noteField.focus();
      setTimeout(() => (noteField.style.border = "1.5px solid #cbd5e1"), 2500);
    }
    return;
  }

  const defects = {};
  container.querySelectorAll(".vi-item-switch").forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect = switchItem.querySelector(".toggle-switch")?.getAttribute("data-value") === "true";
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
              border-color: #f59e0b; background: #fffbeb; color: #b45309;
          }
          .type-toggle-input:checked + .type-toggle-label::after {
              content: '✔'; font-weight: 900; color: #b45309;
          }
      </style>
      
      <div style="background-color: #fffbeb; color: #b45309; padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid #f59e0b;">
          Đang xác nhận QC và chuyển đổi Bobin <strong>${bobinCode}</strong>.<br>
          <div style="margin-top: 4px; padding: 4px 8px; background: rgba(245, 158, 11, 0.1); border-radius: 4px; font-size: 12.5px;">
              <strong>📝 Lý do:</strong> <span>${note}</span>
          </div>
          <div style="margin-top: 8px; font-weight: bold;">Chọn loại Bobin điều chỉnh tương ứng:</div>
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
    "Xác nhận QC & Đổi sang loại Điều chỉnh",
    dialogHTML,
    async (dialogBox) => {
      const selectedType = dialogBox.querySelector('input[name="new_bobin_type"]:checked')?.value || "Điều chỉnh (Do CP)";

      const bodyData = {
        bobin_identification_code: bobinCode,
        bobin_key_code: bobinKeyCode,
        new_bobin_type: selectedType,
        inspector_code: inspectorCode,
        inspector_name: inspectorName,
        defect_note: note,
        defect_gel: defects["gel"] || false,
        defect_foreign_object: defects["foreign_object"] || false,
        defect_color_issue: defects["color_issue"] || false,
        defect_print_quality: defects["print_quality"] || false,
      };

      const requestUrl = "/WEB_BOBIN/public/index.php?url=bobin/updateQCAndChangeType";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "⏳ Đang xử lý...";
      btnElement.disabled = true;

      try {
        const response = await fetch(requestUrl, {
          method: "PUT",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(bodyData),
        });

        const data = await response.json();
        if (data.success) {
          Toast.show(data.message, "success");
          setTimeout(() => window.location.reload(), 1200);
        } else {
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        Toast.show("Đã xảy ra lỗi kết nối!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    }
  );
}