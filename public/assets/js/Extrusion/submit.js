/* =========================================
    MODULE TOAST: Display notifications
========================================= */
const Toast = {
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
    }, 3000);
  },
};

/* =========================================
    MODULE DIALOG: Display confirmation dialog
========================================= */
const ConfirmDialog = (() => {
  let styleInjected = false;

  const injectStyles = () => {
    if (styleInjected) return;

    const style = document.createElement("style");
    style.id = "confirm-dialog-style";
    style.textContent = `
        .confirm-dialog-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
        .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 480px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
        .confirm-dialog-title { margin-top: 0; font-size: 17px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 8px; font-weight: 700; }
        .confirm-dialog-content { margin: 12px 0; font-size: 13.5px; color: #333; max-height: 65vh; overflow-y: auto; }
        .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
        .vi-badge-ok { background: #22c55e; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; }
        .vi-badge-ng { background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 12px; }
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
              <button type="button" class="btn btn-primary confirm-btn">Xác nhận</button>
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
    MODULE FORM: Handle form submission via AJAX
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

    // GOM DỮ LIỆU TỪ 5 NÚT TOGGLE NGOẠI QUAN ĐÙN
    form.querySelectorAll(".ext-toggle-btn").forEach((btn) => {
      const fieldName = btn.getAttribute("data-field");
      const fieldValue = btn.getAttribute("data-value");
      fd.set(fieldName, fieldValue);
    });

    // GÁN RACK CODE
    const rackCode = form.querySelector("#rack_code")?.value.trim() || "";
    fd.set("rack_code", rackCode);

    const reviewHTML = buildReviewHTML(form, fd);

    ConfirmDialog.show(
      "Kiểm tra lại thông tin",
      `<div style="background-color: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 12px; border-left: 4px solid #ffeeba;">
          ⚠️ Vui lòng xác nhận thông tin Bobin trước khi gửi đi:
       </div>
       ${reviewHTML}`,
      () => submitForm(form, apiUrl, method, fd, onSuccessCallback),
    );
  });
}

function buildReviewHTML(form, formData) {
  let html =
    '<ul style="list-style: none; padding: 8px 12px; background: #f8fafc; border-radius: 6px; text-align: left; margin: 0;">';

  const extCheckKeys = [
    "ext_check_diameter",
    "ext_check_gel",
    "ext_check_foreign_object",
    "ext_check_color",
    "ext_check_print",
  ];

  for (const [key, value] of formData.entries()) {
    if (!value || extCheckKeys.includes(key)) continue;

    const labelText = getLabelText(form, key);
    const isHighlight =
      key === "bobin_identification_code" || key === "product_code";
    const valueStyle = isHighlight
      ? "color: #dc2626; font-size: 15px; font-weight: 800;"
      : "color: #0056b3; font-weight: 600;";

    html += `<li style="margin-bottom: 6px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
                <strong>${labelText}:</strong> <span style="${valueStyle}">${value}</span>
            </li>`;
  }

  html += "</ul>";

  // HIỂN THỊ ĐÚNG KẾT QUẢ 5 TIÊU CHÍ NGOẠI QUAN ĐÙN ĐÃ CHỌN
  const extLabels = {
    ext_check_diameter: "Đường kính",
    ext_check_gel: "Gel",
    ext_check_foreign_object: "Dị vật",
    ext_check_color: "Màu",
    ext_check_print: "Chữ in",
  };

  html += `
    <div style="margin-top: 10px; text-align: left; padding: 10px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;">
        <strong style="color: #0284c7; font-size: 13px; display: block; margin-bottom: 8px;">Đùn Check:</strong>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 6px;">
  `;

  for (const [key, label] of Object.entries(extLabels)) {
    const isOk = formData.get(key) === "true";
    html += `
        <div style="display: flex; flex-direction: column; align-items: center; background: #f8fafc; padding: 4px; border-radius: 4px; border: 1px solid #cbd5e1;">
            <span style="font-size: 11px; color: #475569; font-weight: 600;">${label}</span>
            <span class="${isOk ? "vi-badge-ok" : "vi-badge-ng"}">${isOk ? "OK" : "NG"}</span>
        </div>
    `;
  }

  html += `</div></div>`;
  return html;
}

function getLabelText(form, fieldName) {
  const labelMap = {
    bobin_identification_code: "Mã định danh Bobin",
    bobin_size: "Kích thước Bobin",
    bobin_type: "Loại Bobin",
    extrusion_employee_code: "Mã số nhân viên",
    extrusion_employee_name: "Họ tên nhân viên",
    product_code: "Mã sản phẩm",
    production_order_code: "Mã chỉ thị sản xuất (Tạm thời)",
    machine: "Số máy",
    material: "Vật liệu",
    grinding_time: "Số lần nghiền",
    print_lot: "Lot in",
    material_lot: "Lot vật liệu",
    length_m: "Chiều dài (m)",
    rack_code: "Vị trí đặt (Rack)",
    shift: "Ca sản xuất",
    extrusion_date: "Ngày đùn",
    finish_time: "Thời gian hoàn thành cuộn",
  };

  if (labelMap[fieldName]) {
    return labelMap[fieldName];
  }

  const inputEl = form.querySelector(`[name="${fieldName}"]`);
  if (!inputEl) return fieldName;

  const parentGroup = inputEl.closest(".form-group");
  const label = parentGroup?.querySelector("label");
  if (label) return label.innerText.replace(/:/g, "").trim();

  return fieldName;
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

      // Xóa dữ liệu các trường cần thiết sau khi thêm thành công
      const idCodeInput = form.querySelector('input[name="bobin_identification_code"]');
      if (idCodeInput) idCodeInput.value = "";

      const idSizeInput = form.querySelector('input[name="bobin_size"]');
      if (idSizeInput) idSizeInput.value = "";

      onSuccessCallback?.(res, form);
    } else {
      Toast.show(`❌ Lỗi: ${res.message}`, "error");
    }
  } catch (err) {
    Toast.show(`❌ Đã có lỗi xảy ra. ${err.message}`, "error");
  } finally {
    if (submitBtn) {
      submitBtn.innerText = originalText;
      submitBtn.disabled = false;
    }
  }
}

/* =========================================
    MODULE CLICK OUTSIDE: Đóng box gợi ý
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
    INITIALIZATION
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