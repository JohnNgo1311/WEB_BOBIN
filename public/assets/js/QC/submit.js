/* =========================================
   MODULE TOAST: Hiển thị thông báo
========================================= */
const Toast = {
  show: function (message, type = "success") {
    console.log("Hiển thị toast:", message);

    const toast = document.createElement("div");
    toast.className = `toast-message ${type === "error" ? "toast-error" : ""}`;
    toast.innerHTML = message;

    document.body.appendChild(toast);

    // Hiệu ứng
    setTimeout(() => toast.classList.add("show"), 10);

    // Tự động tắt
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 500);

      // Reload trang nếu là success (giữ nguyên logic của bạn)
      if (type === "success") {
        window.location.reload();
      }
    }, 1500);
  },
};

/* =========================================
   MODULE DIALOG: Hiển thị bảng xác nhận
========================================= */
const ConfirmDialog = {
  show: function (title, contentHTML, onConfirm) {
    const overlay = document.createElement("div");
    overlay.className = "confirm-dialog-overlay";

    const box = document.createElement("div");
    box.className = "confirm-dialog-box";

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

    if (!document.getElementById("confirm-dialog-style")) {
      const style = document.createElement("style");
      style.id = "confirm-dialog-style";
      style.innerHTML = `
                .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
                .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
                .confirm-dialog-title { margin-top: 0; font-size: 18px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .confirm-dialog-content { margin: 15px 0; font-size: 14px; color: #333; max-height: 60vh; overflow-y: auto; }
                .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
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

/* =========================================
   HÀM XỬ LÝ: Xác nhận QC Bobin
========================================= */
function handleConfirm(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    alert("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!");
    return;
  }

  const inspectorCode = container
    .querySelector(".input-inspector-code")
    .value.trim();
  const inspectorName = container
    .querySelector(".input-inspector-name")
    .value.trim();
  const note = container.querySelector(".note-field").value.trim();

  const defects = {};
  container.querySelectorAll(".vi-item-switch").forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect =
      switchItem.querySelector(".toggle-switch").getAttribute("data-value") ===
      "true";
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

  // 1. Dựng HTML hiển thị review
  const defectText = (goodDefect) =>
    goodDefect
      ? '<span style="color: green; font-weight: bold;">OK</span>'
      : '<span style="color: red; font-weight: bold;">NG</span>';

  let reviewHTML = `
        <ul style="list-style: none; padding: 10px; background: #f4f6f8; border-radius: 6px; text-align: left;">
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Mã định danh Bobin:</strong> <span style="color: #0056b3;">${bobinCode}</span></li>
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Nhân viên QC:</strong> <span style="color: #0056b3;">${inspectorCode} - ${inspectorName}</span></li>
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Lỗi Gel:</strong> ${defectText(bodyData.defect_gel)}</li>
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Lỗi Dị vật:</strong> ${defectText(bodyData.defect_foreign_object)}</li>
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Lỗi Màu:</strong> ${defectText(bodyData.defect_color_issue)}</li>
            <li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;"><strong>Lỗi Mực In:</strong> ${defectText(bodyData.defect_print_quality)}</li>
            <li style="margin-bottom: 8px; padding-bottom: 4px;"><strong>Ghi chú:</strong> <span style="color: #0056b3;">${note || "Không có"}</span></li>
        </ul>
    `;

  // 2. Gọi Dialog hiển thị
  ConfirmDialog.show(
    "Xác nhận thông tin QC",
    `
        <div style="background-color: #fff3cd; color: #856404; padding: 10px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #ffeeba;">
            ⚠️ Vui lòng kiểm tra lại kết quả QC trước khi gửi đi:
        </div>
        ${reviewHTML}
        `,
    // 3. Callback thực hiện Fetch API khi nhấn "Xác nhận"
    async () => {
      const requestUrl = "/WEB_BOBIN/public/index.php?url=bobin/updateQCBobin";
      const originalBtnText = btnElement.innerHTML;

      btnElement.innerHTML = "Đang xử lý...";
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
          console.log("Response từ API:", data);
          Toast.show(data.message, "success");
        } else {
          console.error("Lỗi từ API:", data);
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        console.error("Error:", error);
        Toast.show("Đã xảy ra lỗi kết nối!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    },
  );
}

/* =========================================
    HÀM XỬ LÝ: Hủy QC Bobin
========================================= */
function handleCancel(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");
  if (!bobinCode || !bobinKeyCode) {
    alert("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!");
    return;
  }

  const inspectorCode = container
    .querySelector(".input-inspector-code")
    .value.trim();
  const inspectorName = container
    .querySelector(".input-inspector-name")
    .value.trim();
  const note = container.querySelector(".note-field").value.trim();

  const defects = {};
  container.querySelectorAll(".vi-item-switch").forEach((switchItem) => {
    const key = switchItem.getAttribute("data-key");
    const goodDefect =
      switchItem.querySelector(".toggle-switch").getAttribute("data-value") ===
      "true";
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

  const defectText = (goodDefect) =>
    goodDefect
      ? '<span style="color: #16a34a; font-weight: bold;">OK</span>'
      : '<span style="color: #dc2626; font-weight: bold;">NG</span>';

  let reviewHTML = `
          <ul style="list-style: none; padding: 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb;">
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace;">${bobinCode}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Nhân viên QC:</strong> <span style="color: #1f2937;">${inspectorCode} - ${inspectorName}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Gel:</strong> ${defectText(bodyData.defect_gel)}</li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Dị vật:</strong> ${defectText(bodyData.defect_foreign_object)}</li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Màu:</strong> ${defectText(bodyData.defect_color_issue)}</li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mực In:</strong> ${defectText(bodyData.defect_print_quality)}</li>
                <li style="padding-bottom: 6px;"><strong>Ghi chú:</strong> <span style="color: #1f2937; display: block; margin-top: 4px; font-style: italic;">${note || "Không có"}</span></li>
          </ul>
     `;

  ConfirmDialog.show(
    "⚠️ Xác nhận Hủy QC",
    `
          <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 12px 14px; border-radius: 6px; font-weight: 600; margin-bottom: 16px; border-left: 4px solid #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                ❗Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Hành động này không thể hoàn tác
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
          console.log("Response từ API:", data);
          Toast.show(data.message, "success");
        } else {
          console.error("Lỗi từ API:", data);
          Toast.show("Lỗi: " + (data.message || "Cập nhật thất bại"), "error");
          btnElement.innerHTML = originalBtnText;
          btnElement.disabled = false;
        }
      } catch (error) {
        console.error("Error:", error);
        Toast.show("Đã xảy ra lỗi kết nối!", "error");
        btnElement.innerHTML = originalBtnText;
        btnElement.disabled = false;
      }
    },
  );
  /* =========================================
    HÀM XỬ LÝ: Chuyển đổi loại Bobin sang ĐIỀU CHỈNH
========================================= */

}
/* =========================================
    HÀM XỬ LÝ: Kiểm tra QC kết hợp đổi loại Bobin sang ĐIỀU CHỈNH
========================================= */
function handleChangeType(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");

  if (!bobinCode || !bobinKeyCode) {
    Toast.show("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!", "error");
    return;
  }

  const inputCodeEl = container?.querySelector(".input-inspector-code");
  const inputNameEl = container?.querySelector(".input-inspector-name");

  const inspectorCode = inputCodeEl?.value?.trim() || "";
  const inspectorName = inputNameEl?.value?.trim() || "";

  if (!inspectorCode || inspectorCode === "Chưa cập nhật" || inspectorCode === "") {
    Toast.show("⚠️ Vui lòng nhập Mã số nhân viên QC trước khi thực hiện!", "error");
    if (inputCodeEl) {
      inputCodeEl.style.border = "2px solid red";
      setTimeout(() => inputCodeEl.style.border = "1px solid #cbd5e1", 2000);
    }
    return;
  }

  const note = container?.querySelector(".note-field")?.value?.trim() || "";

  // Thu thập trạng thái các lỗi QC tương tự handleConfirm
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
          .type-toggle-group { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
          .type-toggle-item { position: relative; }
          .type-toggle-input { display: none; }
          .type-toggle-label { 
              display: flex; align-items: center; justify-content: space-between;
              padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px;
              background: #f8fafc; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.2s;
          }
          .type-toggle-input:checked + .type-toggle-label {
              border-color: #f59e0b; background: #fffbeb; color: #b45309; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.1);
          }
          .type-toggle-input:checked + .type-toggle-label::after {
              content: '✔'; font-size: 16px; font-weight: 800; color: #b45309;
          }
      </style>
      
      <div style="background-color: #fffbeb; color: #b45309; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #f59e0b;">
          Đang xác nhận QC và chuyển đổi loại Bobin <strong>${bobinCode}</strong>.<br> Vui lòng chọn loại điều chỉnh tương ứng:
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
      </div>
  `;

  ConfirmDialog.show(
    "Xác nhận QC & Đổi loại Bobin",
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
          Toast.show(data.message, "success"); // Tự động reload trang khi success
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