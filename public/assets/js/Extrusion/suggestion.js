/* File: public/assets/js/suggestions.js */

// 1. BIẾN TOÀN CỤC
let listData = {
  list_bobin: [],
  list_employee: [],
  list_product: [],
  list_material_lot: [],
  list_extrusion_machine: [],
  list_material: [],
  list_rack: [],
  list_day: [],
  list_month: [],
  list_year: [],
  pending_count: 0,
};

let isManualTime = false;

function updatePendingBadge(count) {
  const badge = document.getElementById("badgePendingCancellation");
  const pendingNum = parseInt(count, 10) || 0;

  if (badge) {
    if (pendingNum > 0) {
      badge.textContent = pendingNum;
      badge.style.display = "inline-block";
    } else {
      badge.style.display = "none";
    }
  }
}

// 2. REALTIME CLOCK
setInterval(() => {
  if (isManualTime) return;

  const d = new Date();
  const t = d.toLocaleTimeString("vi-VN", { hour12: false });
  const day = d.getDate().toString().padStart(2, "0");
  const month = (d.getMonth() + 1).toString().padStart(2, "0");
  const year = d.getFullYear();
  const dateStr = `${day}/${month}/${year}`;

  const el = document.getElementById("finish_time");
  if (el) el.value = dateStr + " " + t;
}, 1000);

// 3. LOAD DATA & DATEPICKER TOGGLE
document.addEventListener("DOMContentLoaded", () => {
  const timeToggle = document.getElementById("manual_time_toggle");
  const finishTimeInput = document.getElementById("finish_time");
  const manualPicker = document.getElementById("manual_datetime_picker");

  if (timeToggle && finishTimeInput && manualPicker) {
    timeToggle.addEventListener("change", function () {
      isManualTime = this.checked;
      if (isManualTime) {
        const parts = finishTimeInput.value.split(" ");
        if (parts.length === 2) {
          const dParts = parts[0].split("/");
          if (dParts.length === 3) {
            manualPicker.value = `${dParts[2]}-${dParts[1]}-${dParts[0]}T${parts[1]}`;
          }
        }
        finishTimeInput.style.display = "none";
        manualPicker.style.display = "block";
      } else {
        finishTimeInput.style.display = "block";
        manualPicker.style.display = "none";
      }
    });

    manualPicker.addEventListener("input", function () {
      if (!this.value) return;

      const dateObj = new Date(this.value);
      const day = dateObj.getDate().toString().padStart(2, "0");
      const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
      const year = dateObj.getFullYear();
      const hours = dateObj.getHours().toString().padStart(2, "0");
      const minutes = dateObj.getMinutes().toString().padStart(2, "0");
      const seconds = dateObj.getSeconds().toString().padStart(2, "0");

      finishTimeInput.value = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
      finishTimeInput.dispatchEvent(new Event("change"));
    });
  }

  fetch(API_BASE_URL + "listdata/getListData")
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        listData = response;
        updatePendingBadge(response.pending_count);
        setupAutoPrintLot();
        reversePrintLot();
      } else {
        console.error("❌ Không thể tải danh mục gợi ý.");
      }
    })
    .catch(console.error);
});

// 4. LOGIC GỢI Ý CÁC TRƯỜNG NHẬP LIỆU
// --- BOBIN ---
const bobinCodeInput = document.getElementById("bobin_identification_code");
const bobinSize = document.getElementById("bobin_size");
const bobinCodeBox = document.getElementById("bobin_suggestions");

if (bobinCodeInput) {
  bobinCodeInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    bobinCodeBox.innerHTML = "";

    if (bobinSize) bobinSize.value = "";
    if (!listData.list_bobin || listData.list_bobin.length === 0 || !val) {
      bobinCodeBox.style.display = "none";
      return;
    }

    const isValidStatus = (status) => status === "Rolled" || status === "Cancelled";

    const exactMatch = listData.list_bobin.find(
      (bobin) =>
        bobin.bobin_identification_code &&
        bobin.bobin_identification_code.toLowerCase() === val &&
        isValidStatus(bobin.bobin_current_status)
    );

    if (exactMatch) {
      if (bobinSize) bobinSize.value = exactMatch.bobin_size;
      bobinCodeBox.style.display = "none";
      return;
    }

    const matches = listData.list_bobin.filter((bobin) => {
      const code = bobin.bobin_identification_code || "";
      const status = bobin.bobin_current_status || "";
      return code.toLowerCase().includes(val) && isValidStatus(status);
    });

    if (matches.length > 0) {
      bobinCodeBox.style.display = "block";
      matches.forEach((bobin) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${bobin.bobin_identification_code} - ${bobin.bobin_size}`;
        div.onclick = () => {
          bobinCodeInput.value = bobin.bobin_identification_code;
          if (bobinSize) bobinSize.value = bobin.bobin_size;
          bobinCodeBox.style.display = "none";
        };
        bobinCodeBox.appendChild(div);
      });
    } else {
      bobinCodeBox.style.display = "none";
    }
  });
}

// --- LOT VẬT LIỆU ---
const material_lotInput = document.getElementById("material_lot");
const material_lotBox = document.getElementById("material_lot_suggestions");

if (material_lotInput) {
  material_lotInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    material_lotBox.innerHTML = "";

    if (!listData.list_material_lot || listData.list_material_lot.length === 0 || !val) {
      material_lotBox.style.display = "none";
      return;
    }

    const filteredLots = listData.list_material_lot.filter(
      (item) => item.lot && item.lot.toLowerCase().includes(val)
    );

    if (filteredLots.length > 0) {
      material_lotBox.style.display = "block";
      filteredLots.forEach((item) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = item.lot;
        div.onclick = () => {
          material_lotInput.value = item.lot;
          material_lotBox.style.display = "none";
        };
        material_lotBox.appendChild(div);
      });
    } else {
      material_lotBox.style.display = "none";
    }
  });
}

// --- VỊ TRÍ RACK ---
const rackInput = document.getElementById("rack_code");
const rackBox = document.getElementById("rack_suggestions");

if (rackInput && rackBox) {
  rackInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    rackBox.innerHTML = "";

    if (!listData.list_rack || listData.list_rack.length === 0 || !val) {
      rackBox.style.display = "none";
      return;
    }

    const filteredRacks = listData.list_rack.filter(
      (item) => item.rack_code && item.rack_code.toLowerCase().includes(val)
    );

    if (filteredRacks.length > 0) {
      rackBox.style.display = "block";
      filteredRacks.forEach((item) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = item.rack_code;
        div.onclick = () => {
          rackInput.value = item.rack_code;
          rackBox.style.display = "none";
        };
        rackBox.appendChild(div);
      });
    } else {
      rackBox.style.display = "none";
    }
  });
}

// --- VẬT LIỆU ---
const materialInput = document.getElementById("material");
const materialBox = document.getElementById("material_suggestions");

if (materialInput) {
  materialInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    materialBox.innerHTML = "";

    if (!listData.list_material || listData.list_material.length === 0 || !val) {
      materialBox.style.display = "none";
      return;
    }

    const uniqueBrands = [
      ...new Set(listData.list_material.map((item) => item.brand)),
    ].filter((brand) => brand && brand.toLowerCase().includes(val));

    if (uniqueBrands.length > 0) {
      materialBox.style.display = "block";
      uniqueBrands.forEach((value) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = value;
        div.onclick = () => {
          materialInput.value = value;
          materialBox.style.display = "none";
        };
        materialBox.appendChild(div);
      });
    } else {
      materialBox.style.display = "none";
    }
  });
}

// --- MÁY ĐÙN ---
const machineInput = document.getElementById("machine");
const machineBox = document.getElementById("machine_suggestions");

if (machineInput) {
  machineInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    machineBox.innerHTML = "";

    if (!listData.list_extrusion_machine || listData.list_extrusion_machine.length === 0 || !val) {
      machineBox.style.display = "none";
      return;
    }

    const filteredList = listData.list_extrusion_machine.filter(
      (item) => item.machine_name && item.machine_name.toLowerCase().includes(val)
    );

    if (filteredList.length > 0) {
      machineBox.style.display = "block";
      filteredList.forEach((item) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = item.machine_name;
        div.onclick = () => {
          machineInput.value = item.machine_name;
          machineBox.style.display = "none";
        };
        machineBox.appendChild(div);
      });
    } else {
      machineBox.style.display = "none";
    }
  });
}

// ============================= 6. NHÂN VIÊN & SẢN PHẨM ===========================

// --- A. MÃ NHÂN VIÊN -> HỌ TÊN NHÂN VIÊN ---
const empInput = document.getElementById("extrusion_employee_code");
const empName = document.getElementById("extrusion_employee_name");
const empBox = document.getElementById("employee_suggestions");

if (empInput) {
  empInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    empBox.innerHTML = "";

    // 1. Tự động xóa họ tên nhân viên ngay khi người dùng chỉnh sửa hoặc xóa mã
    if (empName) {
      empName.value = "";
    }

    if (!listData.list_employee || listData.list_employee.length === 0 || !val) {
      empBox.style.display = "none";
      return;
    }

    // 2. Tự động điền nếu khớp chính xác mã nhân viên (khi quét QR hoặc gõ xong)
    const exactMatch = listData.list_employee.find(
      (emp) => emp.employee_code && emp.employee_code.toLowerCase() === val
    );
    if (exactMatch) {
      if (empName) empName.value = exactMatch.employee_name;
      empBox.style.display = "none";
      return;
    }

    // 3. Tìm kiếm và hiển thị danh sách gợi ý tương đối
    const matches = listData.list_employee.filter(
      (emp) =>
        (emp.employee_code && emp.employee_code.toLowerCase().includes(val)) ||
        (emp.employee_name && emp.employee_name.toLowerCase().includes(val))
    );

    if (matches.length > 0) {
      empBox.style.display = "block";
      matches.forEach((emp) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${emp.employee_code} - ${emp.employee_name}`;
        div.onclick = () => {
          empInput.value = emp.employee_code;
          if (empName) empName.value = emp.employee_name;
          empBox.style.display = "none";
        };
        empBox.appendChild(div);
      });
    } else {
      empBox.style.display = "none";
    }
  });
}

// --- B. MÃ SẢN PHẨM -> MÃ CHỈ THỊ SẢN XUẤT ---
const productCodeInput = document.getElementById("product_code");
const productionOrderCode = document.getElementById("production_order_code");
const productBox = document.getElementById("product_suggestions");

if (productCodeInput) {
  productCodeInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    productBox.innerHTML = "";

    // 1. Tự động xóa mã chỉ thị sản xuất ngay khi người dùng chỉnh sửa hoặc xóa mã sản phẩm
    if (productionOrderCode) {
      productionOrderCode.value = "";
    }

    if (!listData.list_product || listData.list_product.length === 0 || !val) {
      productBox.style.display = "none";
      return;
    }

    // 2. Tự động điền nếu khớp chính xác mã sản phẩm
    const exactMatch = listData.list_product.find(
      (p) => p.product_code && p.product_code.toLowerCase() === val
    );
    if (exactMatch) {
      if (productionOrderCode) {
        productionOrderCode.value = exactMatch.production_order_code;
      }
      productBox.style.display = "none";
      return;
    }

    // 3. Tìm kiếm và hiển thị danh sách gợi ý tương đối
    const matches = listData.list_product.filter((product) => {
      const poCode = product.production_order_code || "";
      const pCode = product.product_code || "";
      return poCode.toLowerCase().includes(val) || pCode.toLowerCase().includes(val);
    });

    if (matches.length > 0) {
      productBox.style.display = "block";
      matches.forEach((product) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${product.product_code}`;
        div.onclick = () => {
          if (productionOrderCode) {
            productionOrderCode.value = product.production_order_code;
          }
          productCodeInput.value = product.product_code;
          productBox.style.display = "none";
        };
        productBox.appendChild(div);
      });
    } else {
      productBox.style.display = "none";
    }
  });
}

// 5. TỰ ĐỘNG TẠO VÀ DỊCH MÃ LOT IN
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}

function findCode(list, keyToCompare, valueToCompare, keyToReturn) {
  if (!list || !Array.isArray(list)) return "";
  const found = list.find(
    (item) =>
      String(item[keyToCompare]).trim().toLowerCase() ===
      String(valueToCompare).trim().toLowerCase()
  );
  return found ? found[keyToReturn] : "";
}

// Khai báo duy nhất setupAutoPrintLot lắng nghe extrusion_date
function setupAutoPrintLot() {
  const inputs = ["machine", "material", "grinding_time"];
  const dateInput = document.querySelector('input[name="extrusion_date"]');
  const debouncedUpdate = debounce(updatePrintLot, 400);

  inputs.forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener("input", debouncedUpdate);
      el.addEventListener("change", debouncedUpdate);
    }
  });

  if (dateInput) {
    dateInput.addEventListener("input", debouncedUpdate);
    dateInput.addEventListener("change", debouncedUpdate);
  }
}

function updatePrintLot() {
  const machineEl = document.getElementById("machine");
  const materialEl = document.getElementById("material");
  const grindingEl = document.getElementById("grinding_time");
  const dateInput = document.querySelector('input[name="extrusion_date"]');
  const printLotEl = document.getElementById("print_lot");

  if (!machineEl || !materialEl || !grindingEl || !dateInput || !printLotEl) return;

  const machineName = machineEl.value.trim();
  const materialBrand = materialEl.value.trim();
  const grindingTime = grindingEl.value.trim();
  const dateValue = dateInput.value.trim();

  if (!machineName || !materialBrand || grindingTime === "" || !dateValue) {
    if (printLotEl.value !== "") printLotEl.value = "";
    return;
  }

  const parts = dateValue.split("-");
  const year = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10);
  const day = parseInt(parts[2], 10);

  if (isNaN(day) || isNaN(month) || isNaN(year)) {
    if (printLotEl.value !== "") printLotEl.value = "";
    return;
  }

  const machineCode = findCode(listData.list_extrusion_machine, "machine_name", machineName, "machine_code");
  const materialCode =
    listData.list_material
      .filter(
        (item) =>
          String(item.brand).trim().toLowerCase() === String(materialBrand).trim().toLowerCase() &&
          item.grinding_time == grindingTime
      )
      .map((item) => item.code)[0] || "";

  const dayCode = findCode(listData.list_day, "day", day, "code");
  const monthCode = findCode(listData.list_month, "month", month, "code");
  const yearCode = findCode(listData.list_year, "year", year, "code");

  if (!machineCode || !materialCode || !dayCode || !monthCode || !yearCode) {
    if (printLotEl.value !== "") printLotEl.value = "";
    return;
  }

  const lotString = `${machineCode}${materialCode}${yearCode}${monthCode}${dayCode}`;
  if (printLotEl.value !== lotString) {
    printLotEl.value = lotString;
  }
}

function reversePrintLot() {
  const lotEl = document.getElementById("print_lot");
  if (!lotEl) return;
  const lotString = lotEl.value.trim();
  if (!lotString) return;

  let remainingStr = lotString;
  const result = { machine: "", material: "", grinding_time: "", day: "", month: "", year: "" };

  const dayObj = listData.list_day.find((item) => remainingStr.endsWith(item.code));
  if (dayObj) {
    result.day = dayObj.day;
    remainingStr = remainingStr.slice(0, -dayObj.code.length);
  }

  const monthObj = listData.list_month.find((item) => remainingStr.endsWith(item.code));
  if (monthObj) {
    result.month = monthObj.month;
    remainingStr = remainingStr.slice(0, -monthObj.code.length);
  }

  const yearObj = listData.list_year.find((item) => remainingStr.endsWith(item.code));
  if (yearObj) {
    result.year = yearObj.year;
    remainingStr = remainingStr.slice(0, -yearObj.code.length);
  }

  const machineObj = listData.list_extrusion_machine.find((item) => remainingStr.startsWith(item.machine_code));
  if (machineObj) {
    result.machine = machineObj.machine_name;
    remainingStr = remainingStr.slice(machineObj.machine_code.length);
  }

  const materialObj = listData.list_material.find((item) => item.code === remainingStr);
  if (materialObj) {
    result.material = materialObj.brand;
    result.grinding_time = materialObj.grinding_time;
  }

  if (result.machine) document.getElementById("machine").value = result.machine;
  if (result.material) document.getElementById("material").value = result.material;
  if (result.grinding_time !== undefined) document.getElementById("grinding_time").value = result.grinding_time;

  if (result.day && result.month && result.year) {
    const formattedDay = String(result.day).padStart(2, "0");
    const formattedMonth = String(result.month).padStart(2, "0");
    const dateInput = document.querySelector('input[name="extrusion_date"]');

    if (dateInput) {
      dateInput.value = `${result.year}-${formattedMonth}-${formattedDay}`;
    }
  }
} 