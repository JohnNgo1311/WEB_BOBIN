/* =========================================
    MODULE TOAST: Hiển thị thông báo
========================================= */
var Toast = window.Toast = window.Toast || {
  show(message, type = "success") {
    const toast = document.createElement("div");
    toast.className = `toast-message${type === "error" ? " toast-error" : ""}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add("show"));
    });

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 400);
    }, 3500);
  },
};

/* =========================================
    MODULE DIALOG: Hộp thoại xác nhận Preview
========================================= */
const ExtrusionConfirmDialog = (() => {
  let styleInjected = false;

  const injectStyles = () => {
    if (styleInjected) return;

    const style = document.createElement("style");
    style.id = "confirm-dialog-style";
    style.textContent = `
        .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(2px); }
        .confirm-dialog-box { background: #fff; padding: 20px 24px; border-radius: 12px; width: 92%; max-width: 520px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif); }
        .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #0f172a; text-align: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px; font-weight: 800; }
        .confirm-dialog-content { margin: 12px 0; font-size: 13px; color: #334155; max-height: 65vh; overflow-y: auto; padding-right: 4px; }
        .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; border-top: 1.5px solid #e2e8f0; padding-top: 12px; }
        .review-section-title { font-size: 12px; font-weight: 800; color: #0284c7; text-transform: uppercase; margin: 10px 0 6px 0; padding-bottom: 3px; border-bottom: 1px dashed #cbd5e1; }
        .review-section-title:first-child { margin-top: 0; }
        .vi-badge-ok { background: #16a34a; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11px; }
        .vi-badge-ng { background: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11px; }
        @media (max-width: 640px) {
            .confirm-dialog-box { width: calc(100vw - 32px); padding: 16px; }
            .confirm-dialog-actions { flex-direction: column-reverse; gap: 8px; }
            .confirm-dialog-actions button { width: 100%; min-height: 42px; font-size: 14px; }
        }
    `;
    document.head.appendChild(style);
    styleInjected = true;
  };

  return {
    show(title, contentHTML, onConfirm) {
      injectStyles();

      const overlay = document.createElement("div");
      overlay.className = "confirm-dialog-overlay";

      const box = document.createElement("div");
      box.className = "confirm-dialog-box";
      box.innerHTML = `
          <h3 class="confirm-dialog-title">${title}</h3>
          <div class="confirm-dialog-content">${contentHTML}</div>
          <div class="confirm-dialog-actions">
              <button type="button" class="btn btn-secondary cancel-btn">Hủy bỏ</button>
              <button type="button" class="btn btn-primary confirm-btn">Xác nhận gửi</button>
          </div>
      `;

      overlay.appendChild(box);
      document.body.appendChild(overlay);

      const handleClose = () => overlay.remove();
      box.querySelector(".cancel-btn").addEventListener("click", handleClose);
      box.querySelector(".confirm-btn").addEventListener("click", () => {
        handleClose();
        if (typeof onConfirm === "function") onConfirm();
      });
    },
  };
})();

/* =========================================
    MODULE VALIDATION: Kiểm tra đầy đủ dữ liệu
========================================= */
function validateExtrusionForm(form, fd) {
  // 1. Kiểm tra mã Bobin & Kích thước
  const bobinCode = fd.get("bobin_identification_code")?.trim() || "";
  const bobinSize = fd.get("bobin_size")?.trim() || "";
  if (!bobinCode) {
    Toast.show("⚠️ Vui lòng nhập mã định danh Bobin!", "error");
    form.querySelector("#bobin_identification_code")?.focus();
    return false;
  }
  if (!bobinSize || bobinSize === "Chưa cập nhật") {
    Toast.show("⚠️ Mã Bobin không hợp lệ!", "error");
    form.querySelector("#bobin_identification_code")?.focus();
    return false;
  }

  // 2. Kiểm tra nhân viên đùn
  const empCode = fd.get("extrusion_employee_code")?.trim() || "";
  const empName = fd.get("extrusion_employee_name")?.trim() || "";
  if (!empCode || !empName) {
    Toast.show("⚠️ Thông tin nhân viên đùn không hợp lệ!", "error");
    form.querySelector("#extrusion_employee_code")?.focus();
    return false;
  }

  // 3. Kiểm tra sản phẩm & mã chỉ thị
  const prodCode = fd.get("product_code")?.trim() || "";
  const poCode = fd.get("production_order_code")?.trim() || "";
  if (!prodCode) {
    Toast.show("⚠️ Vui lòng nhập Mã sản phẩm!", "error");
    form.querySelector("#product_code")?.focus();
    return false;
  }
  if (!poCode || poCode === "Chưa cập nhật") {
    Toast.show("⚠️ Mã chỉ thị không hợp lệ!", "error");
    form.querySelector("#product_code")?.focus();
    return false;
  }

  // 4. Kiểm tra loại Bobin & Ca
  if (!fd.get("bobin_type")?.trim()) {
    Toast.show("⚠️ Vui lòng chọn Loại Bobin!", "error");
    form.querySelector('[name="bobin_type"]')?.focus();
    return false;
  }
  if (!fd.get("shift")?.trim()) {
    Toast.show("⚠️️ Vui lòng chọn Ca sản xuất!", "error");
    form.querySelector('[name="shift"]')?.focus();
    return false;
  }

  // 5. Kiểm tra thông số máy, vật liệu, nghiền
  if (!fd.get("machine")?.trim()) {
    Toast.show("⚠️ Vui lòng chọn Số máy đùn!", "error");
    form.querySelector("#machine")?.focus();
    return false;
  }
  if (!fd.get("material")?.trim()) {
    Toast.show("⚠️ Vui lòng chọn Loại vật liệu!", "error");
    form.querySelector("#material")?.focus();
    return false;
  }
  const grindingTime = fd.get("grinding_time");
  if (grindingTime === null || grindingTime === "" || isNaN(grindingTime)) {
    Toast.show("⚠️ Vui lòng nhập Số lần nghiền!", "error");
    form.querySelector("#grinding_time")?.focus();
    return false;
  }

  // 6. Kiểm tra Lot in (Print Lot)
  const printLot = fd.get("print_lot")?.trim() || "";
  if (!printLot || printLot === "Chưa cập nhật") {
    Toast.show("⚠️ Lot in không hợp lệ!", "error");
    return false;
  }

  // 7. Kiểm tra Lot vật liệu
  if (!fd.get("material_lot")?.trim()) {
    Toast.show("⚠️ Vui lòng nhập Lot vật liệu!", "error");
    form.querySelector("#material_lot")?.focus();
    return false;
  }

  // 8. Kiểm tra chiều dài
  const lengthM = parseFloat(fd.get("length_m"));
  if (isNaN(lengthM) || lengthM <= 0) {
    Toast.show("⚠️ Chiều dài Bobin không hợp lệ!", "error");
    form.querySelector('[name="length_m"]')?.focus();
    return false;
  }

  // 9. Kiểm tra vị trí Rack
  const rackCode = fd.get("rack_code")?.trim() || "";
  if (!rackCode) {
    Toast.show("⚠️ Vui lòng chọn Vị trí đặt (Rack) lưu kho!", "error");
    form.querySelector("#rack_code")?.focus();
    return false;
  }

  // 10. Kiểm tra ngày đùn & thời gian hoàn thành
  if (!fd.get("extrusion_date")?.trim()) {
    Toast.show("⚠️ Vui lòng chọn Ngày đùn!", "error");
    form.querySelector('[name="extrusion_date"]')?.focus();
    return false;
  }
  if (!fd.get("finish_time")?.trim()) {
    Toast.show("⚠️ Thời gian hoàn thành cuộn không được để trống!", "error");
    form.querySelector("#finish_time")?.focus();
    return false;
  }

  return true;
}

/* =========================================
    MODULE PREVIEW: Xây dựng HTML hiển thị đủ 100%
========================================= */
function buildReviewHTML(form, fd) {
  const fields = [
    // Phân khu 1
    { group: "1. Định danh Bobin & Nhân sự" },
    { key: "bobin_identification_code", label: "Mã định danh Bobin", highlight: true },
    { key: "bobin_size", label: "Kích thước Bobin" },
    { key: "bobin_type", label: "Loại Bobin" },
    { key: "shift", label: "Ca sản xuất" },
    { key: "extrusion_employee_code", label: "Mã NV đùn" },
    { key: "extrusion_employee_name", label: "Họ tên NV đùn" },

    // Phân khu 2
    { group: "2. Thông tin sản xuất & Vận hành" },
    { key: "product_code", label: "Mã sản phẩm", highlight: true },
    { key: "production_order_code", label: "Mã chỉ thị SX" },
    { key: "machine", label: "Số máy đùn" },
    { key: "material", label: "Vật liệu" },
    { key: "grinding_time", label: "Số lần nghiền" },
    { key: "print_lot", label: "Lot in (Print Lot)", greenHighlight: true },
    { key: "material_lot", label: "Lot vật liệu" },
    { key: "length_m", label: "Chiều dài", format: (v) => `${Number(v).toLocaleString("vi-VN")} mét` },
    { key: "rack_code", label: "Vị trí đặt (Rack)", blueHighlight: true },
    { key: "extrusion_date", label: "Ngày đùn" },
    { key: "finish_time", label: "Thời gian hoàn thành" },
  ];

  let html = '<div style="background: #f8fafc; border-radius: 8px; padding: 10px 14px; border: 1px solid #e2e8f0;">';

  fields.forEach((item) => {
    if (item.group) {
      html += `<div class="review-section-title">${item.group}</div><ul style="list-style: none; padding: 0; margin: 0;">`;
      return;
    }

    const rawVal = fd.get(item.key) || "";
    const valText = item.format ? item.format(rawVal) : rawVal;

    let valStyle = "color: #334155; font-weight: 600;";
    if (item.highlight) {
      valStyle = "color: #dc2626; font-size: 14px; font-weight: 800;";
    } else if (item.greenHighlight) {
      valStyle = "color: #15803d; font-weight: 800; background: #f0fdf4; padding: 1px 6px; border-radius: 4px;";
    } else if (item.blueHighlight) {
      valStyle = "color: #0284c7; font-weight: 800; background: #f0f9ff; padding: 1px 6px; border-radius: 4px; border: 1px solid #bae6fd;";
    }

    html += `<li style="margin-bottom: 5px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #64748b; font-weight: 600;">${item.label}:</span>
                <span style="${valStyle}">${valText || '<span style="color:#ef4444;">Chưa có</span>'}</span>
            </li>`;
  });

  html += "</ul></div>";

  // Phân khu 3: 5 tiêu chí Ngoại quan Đùn
  const extLabels = {
    ext_check_diameter: "Đường kính",
    ext_check_gel: "Gel",
    ext_check_foreign_object: "Dị vật",
    ext_check_color: "Màu sắc",
    ext_check_print: "Chữ in",
  };

  html += `
    <div style="margin-top: 10px; padding: 10px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px;">
        <strong style="color: #ea580c; font-size: 12.5px; display: block; margin-bottom: 6px; text-transform: uppercase;">
            🛡️ 3. Kết quả tự kiểm tra ngoại quan đùn:
        </strong>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px;">
  `;

  for (const [key, label] of Object.entries(extLabels)) {
    const isOk = fd.get(key) === "true";
    html += `
        <div style="display: flex; flex-direction: column; align-items: center; background: #f8fafc; padding: 6px 2px; border-radius: 6px; border: 1px solid #e2e8f0;">
            <span style="font-size: 11px; color: #475569; font-weight: 700; margin-bottom: 4px;">${label}</span>
            <span class="${isOk ? "vi-badge-ok" : "vi-badge-ng"}">${isOk ? "OK" : "NG"}</span>
        </div>
    `;
  }

  html += `</div></div>`;
  return html;
}

/* =========================================
    MODULE SUBMIT: Đăng ký Form AJAX
========================================= */
function registerAjaxForm(
  formSelector,
  apiUrl,
  method = "POST",
  onSuccessCallback = null,
) {
  const form = document.querySelector(formSelector);
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fd = new FormData(form);

    // 1. Gom dữ liệu từ 5 nút toggle Ngoại quan Đùn
    form.querySelectorAll(".ext-toggle-btn").forEach((btn) => {
      const fieldName = btn.getAttribute("data-field");
      const fieldValue = btn.getAttribute("data-value");
      fd.set(fieldName, fieldValue);
    });

    // 2. Thu thập đầy đủ các trường
    const machine = form.querySelector("#machine")?.value.trim() || "";
    const material = form.querySelector("#material")?.value.trim() || "";
    const grinding = form.querySelector("#grinding_time")?.value.trim() || "";
    const rackCode = form.querySelector("#rack_code")?.value.trim() || "";
    fd.set("machine", machine);
    fd.set("material", material);
    fd.set("grinding_time", grinding);
    fd.set("rack_code", rackCode);

    // 3. KIỂM TRA CHẶT CHẼ TOÀN BỘ CÁC TRƯỜNG TRƯỚC KHI HIỆN DIALOG
    if (!validateExtrusionForm(form, fd)) {
      return; // Dừng lại ngay lập tức nếu thiếu dữ liệu, không mở dialog!
    }

    // 4. Xây dựng Preview đầy đủ 100%
    const reviewHTML = buildReviewHTML(form, fd);

    ExtrusionConfirmDialog.show(
      "Kiểm tra lại thông tin Bobin",
      `<div style="background-color: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 6px; font-weight: 700; margin-bottom: 10px; border-left: 4px solid #ffeeba;">
          ⚠️ Vui lòng rà soát chính xác các thông tin trước khi lưu vào hệ thống:
       </div>
       ${reviewHTML}`,
      () => submitForm(form, apiUrl, method, fd, onSuccessCallback),
    );
  });
}

async function submitForm(form, apiUrl, method, formData, onSuccessCallback) {
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn?.innerText || "";

  if (submitBtn) {
    submitBtn.innerText = "Đang lưu...";
    submitBtn.disabled = true;
  }

  try {
    const response = await fetch(apiUrl, { method, body: formData });
    const res = await response.json();

    if (res.success) {
      Toast.show(res.message, "success");

      // Reset các ô nhập sau khi tạo thành công
      const idCodeInput = form.querySelector('input[name="bobin_identification_code"]');
      if (idCodeInput) idCodeInput.value = "";

      const idSizeInput = form.querySelector('input[name="bobin_size"]');
      if (idSizeInput) idSizeInput.value = "";

      const printLotInput = form.querySelector('input[name="print_lot"]');
      if (printLotInput) printLotInput.value = "";

      const rackInput = form.querySelector('input[name="rack_code"]');
      if (rackInput) rackInput.value = "";

      onSuccessCallback?.(res, form);
    } else {
      Toast.show(`❌ Lỗi: ${res.message || res.error}`, "error");
    }
  } catch (err) {
    Toast.show(`❌ Đã có lỗi xảy ra: ${err.message}`, "error");
  } finally {
    if (submitBtn) {
      submitBtn.innerText = originalText;
      submitBtn.disabled = false;
    }
  }
}

/* =========================================
    CLICK OUTSIDE: Tự động đóng gợi ý
========================================= */
function registerOutsideClickCleaner(pairs) {
  document.addEventListener("click", (e) => {
    pairs.forEach(({ input, box }) => {
      const boxEl = document.getElementById(box);
      if (boxEl && e.target.id !== input && !boxEl.contains(e.target)) {
        boxEl.style.display = "none";
      }
    });
  });
}

/* =========================================
    KHỞI TẠO
========================================= */
document.addEventListener("DOMContentLoaded", () => {
  registerOutsideClickCleaner([
    { input: "bobin_identification_code", box: "bobin_suggestions" },
    { input: "extrusion_employee_code", box: "employee_suggestions" },
    { input: "product_code", box: "product_suggestions" },
    { input: "machine", box: "machine_suggestions" },
    { input: "material", box: "material_suggestions" },
    { input: "material_lot", box: "material_lot_suggestions" },
    { input: "rack_code", box: "rack_suggestions" },
  ]);

  registerAjaxForm(
    "#bobinForm",
    `${API_BASE_URL}bobin/createBobin`,
    "POST",
    (response, formElement) => {
      const dateInput = formElement.querySelector('input[name="extrusion_date"]');
      if (dateInput) dateInput.valueAsDate = new Date();

      fetch(API_BASE_URL + "listdata/getListData")
        .then((res) => res.json())
        .then((response) => {
          if (response.success) {
            listData = response;
            setupAutoPrintLot();
            reversePrintLot();
          }
        })
        .catch(console.error);
    },
  );
});