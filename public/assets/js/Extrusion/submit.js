/* =========================================
    MODULE TOAST: Display notifications
========================================= */
const Toast = {
  show(message, type = "success") {
    const toast = document.createElement("div");
    toast.className = `toast-message${type === "error" ? " toast-error" : ""}`;
    toast.textContent = message; // Use textContent instead of innerHTML for text-only
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add("show"));
    });

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 500);
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
                .confirm-dialog-box { background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 450px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-family: sans-serif; }
                .confirm-dialog-title { margin-top: 0; font-size: 18px; color: #333; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .confirm-dialog-content { margin: 15px 0; font-size: 14px; color: #333; max-height: 60vh; overflow-y: auto; }
                .confirm-dialog-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
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
    const reviewHTML = buildReviewHTML(form, fd);

    ConfirmDialog.show(
      "Kiểm tra lại thông tin",
      `<div style="background-color: #fff3cd; color: #856404; padding: 10px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #ffeeba;">
                ⚠️ Vui lòng xác nhận thông tin Bobin trước khi gửi đi:
                </div>
                ${reviewHTML}`,
      () => submitForm(form, apiUrl, method, fd, onSuccessCallback),
    );
  });
}

function buildReviewHTML(form, formData) {
  let html =
    '<ul style="list-style: none; padding: 10px; background: #f4f6f8; border-radius: 6px; text-align: left;">';

  for (const [key, value] of formData.entries()) {
    if (!value) continue;

    const labelText = getLabelText(form, key);
    html += `<li style="margin-bottom: 8px; border-bottom: 1px dashed #ccc; padding-bottom: 4px;">
                <strong>${labelText}:</strong> <span style="color: #0056b3;">${value}</span>
          </li>`;
  }

  return (
    html +
    "</ul>" +
    `<style>
            /* CSS cho phần Switch Button */
            .check-list-container {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin-top: 12px;
            }
            .switch-row {
                display: flex;
                align-items: center;
                justify-content: space-between; /* Đẩy nhãn sang trái, nút sang phải */
                padding: 10px 14px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background-color: #ffffff;
                box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            }
            .switch-label {
                font-size: 14px;
                color: #374151;
                font-weight: 500;
            }
            .status-switch {
                padding: 6px 0;
                border-radius: 20px; /* Bo tròn giống dạng viên thuốc */
                font-weight: bold;
                font-size: 13px;
                color: #ffffff;
                border: none;
                cursor: pointer;
                width: 60px;
                text-align: center;
                transition: background-color 0.2s ease, transform 0.1s;
            }
            /* Hiệu ứng nhấn nút cho cảm giác thật hơn */
            .status-switch:active {
                transform: scale(0.95);
            }
        </style>
        
        <div style="margin-top: 15px; text-align: left;">
            <strong style="color: #0062ff; font-size: 14px; display: block; margin-bottom: 8px;">Kiểm tra tình trạng Bobin:</strong>
            <div class="check-list-container">
                <div class="switch-row">
                    <span class="switch-label">Đường kính</span>
                    <button type="button" class="status-switch check-btn" check-item="Đường kính" style="background-color: #dc3545;" 
                        onclick="this.innerText = this.innerText === 'NG' ? 'OK' : 'NG'; this.style.backgroundColor = this.innerText === 'OK' ? '#28a745' : '#dc3545';">NG</button>
                </div>

                <div class="switch-row">
                    <span class="switch-label">Gel</span>
                    <button type="button" class="status-switch check-btn" check-item="Gel" style="background-color: #dc3545;" 
                        onclick="this.innerText = this.innerText === 'NG' ? 'OK' : 'NG'; this.style.backgroundColor = this.innerText === 'OK' ? '#28a745' : '#dc3545';">NG</button>
                </div>

                <div class="switch-row">
                    <span class="switch-label">Dị vật</span>
                    <button type="button" class="status-switch check-btn" check-item="Dị vật" style="background-color: #dc3545;" 
                        onclick="this.innerText = this.innerText === 'NG' ? 'OK' : 'NG'; this.style.backgroundColor = this.innerText === 'OK' ? '#28a745' : '#dc3545';">NG</button>
                </div>

                <div class="switch-row">
                    <span class="switch-label">Màu</span>
                    <button type="button" class="status-switch check-btn" check-item="Màu" style="background-color: #dc3545;" 
                        onclick="this.innerText = this.innerText === 'NG' ? 'OK' : 'NG'; this.style.backgroundColor = this.innerText === 'OK' ? '#28a745' : '#dc3545';">NG</button>
                </div>

                <div class="switch-row">
                    <span class="switch-label">Mực in</span>
                    <button type="button" class="status-switch check-btn" check-item="Mực in" style="background-color: #dc3545;" 
                        onclick="this.innerText = this.innerText === 'NG' ? 'OK' : 'NG'; this.style.backgroundColor = this.innerText === 'OK' ? '#28a745' : '#dc3545';">NG</button>
                </div>

            </div>
        </div>`
  );
}

function getLabelText(form, fieldName) {
  const inputEl = form.querySelector(`[name="${fieldName}"]`);
  if (!inputEl) return fieldName;

  const label =
    inputEl.previousElementSibling?.tagName === "LABEL"
      ? inputEl.previousElementSibling
      : inputEl.parentElement?.previousElementSibling?.tagName === "LABEL"
        ? inputEl.parentElement.previousElementSibling
        : null;

  return label ? label.innerText.replace(/:/g, "").trim() : fieldName;
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

      // Xóa dữ liệu các trường cụ thể thay vì reset toàn bộ form
      const idCodeInput = form.querySelector(
        'input[name="bobin_identification_code"]',
      );
      if (idCodeInput) idCodeInput.value = "";

      const productCodeInput = form.querySelector('input[name="product_code"]');
      if (productCodeInput) productCodeInput.value = "";

      const orderCodeInput = form.querySelector(
        'input[name="production_order_code"]',
      );
      if (orderCodeInput) orderCodeInput.value = "";

      // Đã sửa 'lenght_m' thành 'length_m'
      const lengthInput = form.querySelector('input[name="length_m"]');
      if (lengthInput) lengthInput.value = "";

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
    HÀM XỬ LÝ: Hủy Bobin
========================================= */
async function handleDelete(btnElement, bobinCode, bobinKeyCode) {
  const container = btnElement.closest(".bobin-item");
  if (!bobinCode || !bobinKeyCode) {
    alert("Lỗi: Thiếu mã định danh hoặc mã key của Bobin!");
    return;
  }

  // const inMachine = container.querySelector('#extrusion_machine')?.value.trim() || 'Chưa cập nhật';
  const inEmployeeCode =
    container.querySelector("#extrusion_employee_code")?.value.trim() ||
    "Chưa cập nhật";
  const inEmployeeName =
    container.querySelector("#extrusion_employee_name")?.value.trim() ||
    "Chưa cập nhật";

  const bodyData = {
    bobin_identification_code: bobinCode,
    bobin_key_code: bobinKeyCode,
    extrusion_employee_code: inEmployeeCode,
    extrusion_employee_name: inEmployeeName,
    // machine: inMachine,
  };

  let reviewHTML = `
          <ul style="list-style: none; padding: 12px; background: #f9fafb; border-radius: 6px; text-align: left; border: 1px solid #e5e7eb;">
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mã Bobin:</strong> <span style="color: #1f2937; font-family: monospace;">${bobinCode}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Mã nhân viên:</strong> <span style="color: #1f2937;">${inEmployeeCode}</span></li>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; display: flex; justify-content: space-between;"><strong>Họ tên NV:</strong> <span style="color: #1f2937;">${inEmployeeName}</span></li>
          </ul>
     `;

  ConfirmDialog.show(
    "⚠️ Xác nhận Hủy Bobin",
    `
          <div style="background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #7f1d1d; padding: 12px 14px; border-radius: 6px; font-weight: 600; margin-bottom: 16px; border-left: 4px solid #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                ❗Hủy Bobin <strong style="color: #991b1b;">${bobinCode}</strong> - Hành động này không thể hoàn tác
          </div>
          ${reviewHTML}
          `,
    async () => {
      const requestUrl = `${API_BASE_URL}bobin/extDeleteBobin`;
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
          console.log("Response từ API", data);
          Toast.show(data.message, "success");
        } else {
          console.log("Lỗi từ API", data);
          Toast.show("Lỗi: " + (data.message || "Hủy thất bại"), "error");
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
    (dialogBox) => {
      const selectedErrors = Array.from(
        dialogBox.querySelectorAll(".error-toggle-btn.active"),
      ).map((btn) => btn.dataset.error);
      let newNote = originalNote;

      if (selectedErrors.length > 0) {
        const errorString = "Lỗi Hủy: " + selectedErrors.join(", ");
        newNote = originalNote
          ? originalNote + " - " + errorString
          : errorString;
      }

      const noteDisplay = dialogBox.querySelector("#dynamic-note-display");
      if (noteDisplay) {
        noteDisplay.innerText = newNote || "Không có ghi chú";
        noteDisplay.style.fontStyle = newNote ? "normal" : "italic";
      }
      dialogBox.dataset.currentNote = newNote;
    },
  );
}
async function handleConfirm(buttonElement, bobinCode, bobinKeyCode) {
  const originalText = buttonElement.innerText;
  buttonElement.innerText = "Đang lưu...";
  buttonElement.disabled = true;

  try {
    const container = buttonElement.closest(".bobin-item");
    if (!container) {
      throw new Error("Không tìm thấy dữ liệu Bobin tương ứng.");
    }

    const productCode = container.querySelector("#product_code")?.value || "";
    const orderCode =
      container.querySelector("#production_order_code")?.value || "";
    const empCode =
      container.querySelector("#extrusion_employee_code")?.value || "";
    const empName =
      container.querySelector("#extrusion_employee_name")?.value || "";
    const bobinType =
      container.querySelector('select[name="bobin_type"]')?.value || "";
    const materialLot = container.querySelector("#material_lot")?.value || "";
    const machine = container.querySelector("#machine")?.value || "";
    const material = container.querySelector("#material")?.value || "";
    const grindingTime = container.querySelector("#grinding_time")?.value || "";
    const printLot = container.querySelector("#print_lot")?.value || "";
    const lengthM = container.querySelector("#length_m")?.value || 0;
    const shift = container.querySelector('select[name="shift"]')?.value || "";
    const finishTime = container.querySelector("#finish_time")?.value || "";

    const payload = {
      bobin_identification_code: bobinCode,
      bobin_key_code: bobinKeyCode,
      product_code: productCode,
      production_order_code: orderCode,
      extrusion_employee_code: empCode,
      extrusion_employee_name: empName,
      bobin_type: bobinType,
      material_lot: materialLot,
      machine: machine,
      material: material,
      grinding_time: grindingTime,
      print_lot: printLot,
      length_m: lengthM,
      shift: shift,
      finish_time: finishTime,
    };
    console.log("Payload gửi đi:", bobinCode);
    const apiUrl = `${API_BASE_URL}bobin/extrusionUpdateBobin`;

    const response = await fetch(apiUrl, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const res = await response.json();

    if (res.success) {
      console.log("✅ Bobin đã được cập nhật thành công:", res);
      Toast.show(res.message, "success");
      buttonElement.innerText = "Đã lưu ✔️";
      buttonElement.classList.add("btn-success");
      setTimeout(() => {
        buttonElement.innerText = originalText;
        buttonElement.classList.remove("btn-success");
        buttonElement.disabled = false;
        window.location.reload();
      }, 3000);
    } else {
      console.error("❌ Lỗi từ API:", res);
      Toast.show(`❌ Lỗi: ${res.message}`, "error");
      buttonElement.innerText = originalText;
      buttonElement.disabled = false;
    }
  } catch (err) {
    console.error("Lỗi cập nhật Bobin:", err);
    Toast.show(`❌ Đã có lỗi xảy ra: ${err.message}`, "error");
    buttonElement.innerText = originalText;
    buttonElement.disabled = false;
  }
}

/* =========================================
    MODULE CLICK OUTSIDE: Close box on outside click
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
  ]);

  registerAjaxForm(
    "#bobinForm",
    `${API_BASE_URL}bobin/createBobin`,
    "POST",
    (response, formElement) => {
      const dateInput = formElement.querySelector(
        'input[name="extrusion_date"]',
      );
      if (dateInput) dateInput.valueAsDate = new Date();
      console.log("✅ Bobin mới đã được tạo thành công:", response);
      console.log("🚀 Bắt đầu gọi lại API...");
      fetch(API_BASE_URL + "listdata/getListData")
        .then((res) => res.json())
        .then((response) => {
          if (response.success) {
            console.log("📥 Dữ liệu nhận về:", response);
            listData = response;
            setupAutoPrintLot();
            reversePrintLot(); //? Nếu đã có sẵn mã Lot in thì tự động điền ngược thông tin
            console.log(`👂 Bắt đầu theo dõi nhập dữ liệu`);
          } else {
            console.error(
              "❌ Lỗi từ API: Truy xuất dữ liệu ListData không thành công.",
            );
          }
        })
        .catch(console.error);
    },
  );
});
