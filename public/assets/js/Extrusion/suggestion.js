/* File: public/assets/js/suggestions.js */
// 1. KHAI BÁO BIẾN TOÀN CỤC Ở ĐẦU FILE
// Biến này sẽ chứa toàn bộ dữ liệu từ PHP trả về

//TODO 1. BIẾN TOÀN CỤC (Cập nhật key đúng: list_bobin)
let listData = {
  list_bobin: [],
  list_employee: [],
  list_product: [],
  list_material_lot: [],

  list_extrusion_machine: [],
  list_material: [],
  list_day: [],
  list_month: [],
  list_year: [],
};

//TODO 2. HÀM CLOCK
setInterval(() => {
  const d = new Date();
  const t = d.toLocaleTimeString("vi-VN", { hour12: false });
  const day = d.getDate().toString().padStart(2, "0");
  const month = (d.getMonth() + 1).toString().padStart(2, "0");
  const year = d.getFullYear();
  const dateStr = `${day}/${month}/${year}`;

  const el = document.getElementById("finish_time");
  if (el) el.value = dateStr + " " + t;
}, 1000);

//TODO 3. LOAD DATA
document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Bắt đầu gọi API...");
  //API_BASE_URL = 'http://localhost/WEB_BOBIN/';
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
});

//TODO 4. Hàm hỗ trợ điền Datalist (Đã sửa để xử lý mảng Object [{},{}...])
function fillDatalist(key, dataArray) {
  const el = document.getElementById(key);

  // 1. Kiểm tra tính hợp lệ
  if (!el) return; // Không tìm thấy thẻ datalist trong HTML
  if (!Array.isArray(dataArray) || dataArray.length === 0) {
    console.warn(`⚠️ Dữ liệu cho ${key} không hợp lệ hoặc rỗng.`);
    // el.innerHTML = ''; // Xóa dữ liệu cũ nếu mảng rỗng
    return;
  }

  // 2. Cấu hình Mapping: ID Datalist => Tên cột trong Database cần lấy
  // Bạn cần đảm bảo tên cột (bên phải) khớp chính xác với kết quả trả về từ PHP/SQL
  const fieldMapping = {
    list_bobin: "bobin_identification_code", // Cột mã bobin
    list_employee: "employee_code", // Cột mã nhân viên
    list_product: "product_code", // Cột mã sản phẩm
    list_material_lot: "lot", // Cột mã lô (lưu ý tên cột trong DB là 'lot')
    list_extrusion_machine: "machine_name",
    list_material: "brand",
  };

  // Xác định tên cột cần lấy dữ liệu dựa trên ID truyền vào
  const keyName = fieldMapping[key];

  // 3. Xử lý dữ liệu
  const optionsHTML = dataArray
    .map((item) => {
      // item là một Object {id: ..., code: ...}

      // Trường hợp đặc biệt: Nhân viên (Hiển thị Mã - Tên)
      if (key === "list_employee") {
        const code = item["employee_code"];
        const name = item["employee_name"];
        if (code) {
          return `<option value="${code} - ${name}">`;
          // Hoặc nếu bạn chỉ muốn value là mã: <option value="${code}">${name}</option>
        }
      }
      // Trường hợp đặc biệt: Sản phẩm (Hiển thị Mã chỉ thị sản xuất - Mã sản phẩm)
      if (key === "list_product") {
        const production_order_code = item["production_order_code"];
        const product_code = item["product_code"];
        if (product_code) {
          return `<option value="${product_code}">`;

          // return `<option value="${production_order_code} - ${product_code}">`;
          // Hoặc nếu bạn chỉ muốn value là mã: <option value="${code}">${name}</option>
        }
      }

      // Các trường hợp khác: Lấy giá trị theo keyName đã cấu hình
      if (keyName && item[keyName]) {
        return `<option value="${item[keyName]}">`;
      }

      return ""; // Bỏ qua nếu không tìm thấy dữ liệu
    })
    .join("");

  // 4. Gán vào HTML
  el.innerHTML = optionsHTML;
}

//TODO 5. LOGIC GỢI Ý
// =============================BOBIN ===========================
const bobinInput = document.getElementById("bobin_identification_code");
const bobinBox = document.getElementById("bobin_suggestions");

if (bobinInput) {
  bobinInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    bobinBox.innerHTML = "";

    if (!listData.list_bobin || listData.list_bobin.length === 0) {
      return;
    }

    if (!val) {
      bobinBox.style.display = "none";
      return;
    }

    const matches = listData.list_bobin.filter((item) => {
      const code = item.bobin_identification_code || "";
      const status = item.bobin_current_status || "";

      return code.toLowerCase().includes(val) && status === "Ready";
    });

    if (matches.length > 0) {
      bobinBox.style.display = "block";

      matches.forEach((item) => {
        const code = item.bobin_identification_code;

        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = code;

        div.onclick = () => {
          bobinInput.value = code;
          bobinBox.style.display = "none";

          if (typeof checkBobinStatus === "function") {
            checkBobinStatus(code);
          }
        };

        bobinBox.appendChild(div);
      });
    } else {
      bobinBox.style.display = "none";
    }
  });
}

const material_lotInput = document.getElementById("material_lot");
const material_lotBox = document.getElementById("material_lot_suggestions");

if (material_lotInput) {
  material_lotInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    material_lotBox.innerHTML = "";

    if (
      !listData.list_material_lot ||
      listData.list_material_lot.length === 0
    ) {
      return;
    }

    if (!val) {
      material_lotBox.style.display = "none";
      return;
    }

    const filteredLots = listData.list_material_lot.filter((item) => {
      return item.lot && item.lot.toLowerCase().includes(val);
    });

    if (filteredLots.length > 0) {
      material_lotBox.style.display = "block";

      filteredLots.forEach((item) => {
        const value = item.lot;

        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = value;

        div.onclick = () => {
          material_lotInput.value = value;
          material_lotBox.style.display = "none";
        };

        material_lotBox.appendChild(div);
      });
    } else {
      material_lotBox.style.display = "none";
    }
  });
}

const materialInput = document.getElementById("material");
const materialBox = document.getElementById("material_suggestions");

if (materialInput) {
  materialInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    materialBox.innerHTML = "";

    if (!listData.list_material || listData.list_material.length === 0) {
      return;
    }

    if (!val) {
      materialBox.style.display = "none";
      return;
    }

    const uniqueBrands = [
      ...new Set(listData.list_material.map((item) => item.brand)),
    ].filter((brand) => {
      return brand && brand.toLowerCase().includes(val);
    });

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

const machineInput = document.getElementById("machine");
const machineBox = document.getElementById("machine_suggestions");

if (machineInput) {
  machineInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    machineBox.innerHTML = "";

    if (
      !listData.list_extrusion_machine ||
      listData.list_extrusion_machine.length === 0
    ) {
      return;
    }

    if (!val) {
      machineBox.style.display = "none";
      return;
    }

    const list_extrusion_machine = listData.list_extrusion_machine;

    const filteredList = list_extrusion_machine.filter((item) => {
      return item.machine_name && item.machine_name.toLowerCase().includes(val);
    });

    if (filteredList.length > 0) {
      machineBox.style.display = "block";
      filteredList.forEach((item) => {
        const value = item.machine_name;

        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = value;

        div.onclick = () => {
          machineInput.value = value;
          machineBox.style.display = "none";
        };
        machineBox.appendChild(div);
      });
    } else {
      machineBox.style.display = "none";
    }
  });
}

const print_lotInput = document.getElementById("print_lot");
const print_lotBox = document.getElementById("print_lot_suggestions");
if (print_lotInput) {
  print_lotInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    print_lotBox.innerHTML = "";

    // Kiểm tra đúng key: listData.list_print_lot
    if (!listData.list_print_lot || listData.list_print_lot.length === 0)
      return;
    if (!val) {
      print_lotBox.style.display = "none";
      return;
    }
    const list_print_lot = listData.list_print_lot;

    //? --- HIỂN THỊ ---
    if (list_print_lot.length > 0) {
      print_lotBox.style.display = "block";
      list_print_lot.forEach((item) => {
        const value = item.lot;

        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = value;

        div.onclick = () => {
          print_lotInput.value = value;
          print_lotBox.style.display = "none";
        };
        print_lotBox.appendChild(div);
      });
    } else {
      print_lotBox.style.display = "none";
    }
  });
}

//TODO 6. CODE NHÂN VIÊN -> TÊN NHÂN VIÊN, MÃ SẢN PHẨM --> MÃ CHỈ THỊ SẢN XUẤT
const empInput = document.getElementById("extrusion_employee_code");
const empName = document.getElementById("extrusion_employee_name");
const empBox = document.getElementById("employee_suggestions");

if (empInput) {
  empInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    empBox.innerHTML = "";

    if (!listData.list_employee || listData.list_employee.length === 0) return;
    if (!val) {
      empBox.style.display = "none";
      return;
    }

    const matches = listData.list_employee.filter(
      (emp) =>
        (emp.employee_code && emp.employee_code.toLowerCase().includes(val)) ||
        (emp.employee_name && emp.employee_name.toLowerCase().includes(val)),
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

const bobinCodeInput = document.getElementById("bobin_identification_code");
const bobinSize = document.getElementById("bobin_size");
const bobinCodeBox = document.getElementById("bobin_suggestions");

if (bobinCodeInput) {
  bobinCodeInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    bobinCodeBox.innerHTML = "";

    // Reset size mỗi khi nội dung thay đổi (chưa nhập xong)
    if (bobinSize) {
      bobinSize.value = "";
    }

    if (!listData.list_bobin || listData.list_bobin.length === 0) return;

    if (!val) {
      bobinCodeBox.style.display = "none";
      return;
    }

    // 1. KIỂM TRA KHỚP CHÍNH XÁC (Tự động điền khi quét QR hoặc gõ xong)
    const exactMatch = listData.list_bobin.find(
      (bobin) =>
        bobin.bobin_identification_code &&
        bobin.bobin_identification_code.toLowerCase() === val,
    );

    if (exactMatch) {
      // Nếu khớp chính xác 100%, điền luôn size và ẩn khung gợi ý
      if (bobinSize) bobinSize.value = exactMatch.bobin_size;
      bobinCodeBox.style.display = "none";
      return; // Dừng lại ở đây, không cần hiển thị danh sách nữa
    }

    // 2. KIỂM TRA KHỚP TƯƠNG ĐỐI (Hiển thị gợi ý khi đang gõ từng chữ)
    const matches = listData.list_bobin.filter(
      (bobin) =>
        bobin.bobin_identification_code &&
        bobin.bobin_identification_code.toLowerCase().includes(val),
    );

    if (matches.length > 0) {
      bobinCodeBox.style.display = "block";
      matches.forEach((bobin) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${bobin.bobin_identification_code} - ${bobin.bobin_size}`;
        div.onclick = () => {
          // Khi click vào gợi ý
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

const productCodeInput = document.getElementById("product_code");
const productionOrderCode = document.getElementById("production_order_code");
const productBox = document.getElementById("product_suggestions");

if (productCodeInput) {
  productCodeInput.addEventListener("input", function () {
    const val = this.value.toLowerCase().trim();
    productBox.innerHTML = "";

    if (!listData.list_product || listData.list_product.length === 0) {
      return;
    }

    if (!val) {
      productBox.style.display = "none";
      return;
    }

    const matches = listData.list_product.filter((product) => {
      const poCode = product.production_order_code || "";
      const pCode = product.product_code || "";

      return (
        poCode.toLowerCase().includes(val) || pCode.toLowerCase().includes(val)
      );
    });

    if (matches.length > 0) {
      productBox.style.display = "block";

      matches.forEach((product) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${product.production_order_code} - ${product.product_code}`;

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

//TODO 7. LOGIC TỰ ĐỘNG TẠO MÃ LOT IN (PRINT LOT) DỰA TRÊN THÔNG TIN NHẬP VÀO
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}

function setupAutoPrintLot() {
  const inputs = ["machine", "material", "grinding_time"];
  const dateInput = document.querySelector('input[name="finish_time"]');
  const debouncedUpdate = debounce(updatePrintLot, 500);

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

// 💡 NÂNG CẤP: Tìm kiếm không phân biệt hoa/thường và tự động xóa khoảng trắng dư
function findCode(list, keyToCompare, valueToCompare, keyToReturn) {
  if (!list || !Array.isArray(list)) return "";
  const found = list.find(
    (item) =>
      String(item[keyToCompare]).trim().toLowerCase() ===
      String(valueToCompare).trim().toLowerCase(),
  );
  return found ? found[keyToReturn] : "";
}

function updatePrintLot() {
  const machineEl = document.getElementById("machine");
  const materialEl = document.getElementById("material");
  const grindingEl = document.getElementById("grinding_time");
  const dateInput = document.querySelector('input[name="finish_time"]');
  const printLotEl = document.getElementById("print_lot");

  if (!machineEl || !materialEl || !grindingEl || !dateInput || !printLotEl)
    return;

  const machineName = machineEl.value.trim();
  const materialBrand = materialEl.value.trim();
  const grindingTime = grindingEl.value.trim();
  const dateValue = dateInput.value.trim();

  // 1. Kiểm tra rỗng
  if (!machineName || !materialBrand || grindingTime === "" || !dateValue) {
    if (printLotEl.value !== "") printLotEl.value = "";
    return;
  }

  // 2. Xử lý ngày tháng
  const dateObj = new Date(
    dateValue.split(" ")[0].split("/").reverse().join("-"),
  );
  const day = dateObj.getDate();
  const month = dateObj.getMonth() + 1;
  const year = dateObj.getFullYear();

  // 3. Tra cứu mã
  const machineCode = findCode(
    listData.list_extrusion_machine,
    "machine_name",
    machineName,
    "machine_code",
  );

  // 💡 NÂNG CẤP: Lọc vật liệu cũng không phân biệt hoa/thường
  const materialCode =
    listData.list_material
      .filter(
        (item) =>
          String(item.brand).trim().toLowerCase() ===
          String(materialBrand).trim().toLowerCase() &&
          item.grinding_time == grindingTime,
      )
      .map((item) => item.code)[0] || "";

  const dayCode = findCode(listData.list_day, "day", day, "code");
  const monthCode = findCode(listData.list_month, "month", month, "code");
  const yearCode = findCode(listData.list_year, "year", year, "code");

  // 4. KIỂM TRA & DEBUG (Nếu không đủ mã, báo lỗi ra Console)
  if (!machineCode || !materialCode || !dayCode || !monthCode || !yearCode) {
    console.warn("⚠️ KHÔNG THỂ TẠO PRINT LOT DO THIẾU MÃ:");
    if (!machineCode)
      console.log("- Không tìm thấy Machine Code cho tên máy:", machineName);
    if (!materialCode)
      console.log(
        "- Không tìm thấy Material Code cho vật liệu:",
        materialBrand,
        "| Số lần nghiền:",
        grindingTime,
      );
    if (!dayCode) console.log("- Không tìm thấy Day Code cho ngày:", day);
    if (!monthCode)
      console.log("- Không tìm thấy Month Code cho tháng:", month);
    if (!yearCode) console.log("- Không tìm thấy Year Code cho năm:", year);

    // Đảm bảo xóa Print Lot khi dữ liệu bị sai
    if (printLotEl.value !== "") printLotEl.value = "";
    return;
  }

  // 5. Nếu đầy đủ dữ liệu hợp lệ thì ghép chuỗi
  const lotString = `${machineCode}${materialCode}${yearCode}${monthCode}${dayCode}`;
  if (printLotEl.value !== lotString) {
    printLotEl.value = lotString;
  }
}

// Hàm chính: Từ chuỗi Print Lot suy ra các thông số
function reversePrintLot() {
  const lotString = document.getElementById("print_lot").value.trim();
  if (!lotString) return;

  let remainingStr = lotString;
  const result = {
    machine: "",
    material: "",
    grinding_time: "",
    day: "",
    month: "",
    year: "",
  };

  // 1. Tách Ngày (Day) từ CUỐI chuỗi
  const dayObj = listData.list_day.find((item) =>
    remainingStr.endsWith(item.code),
  );
  if (dayObj) {
    result.day = dayObj.day;
    remainingStr = remainingStr.slice(0, -dayObj.code.length); // Cắt bỏ phần mã ngày ở cuối
  }

  // 2. Tách Tháng (Month) từ CUỐI chuỗi (phần còn lại)
  const monthObj = listData.list_month.find((item) =>
    remainingStr.endsWith(item.code),
  );
  if (monthObj) {
    result.month = monthObj.month;
    remainingStr = remainingStr.slice(0, -monthObj.code.length);
  }

  // 3. Tách Năm (Year) từ CUỐI chuỗi (phần còn lại)
  const yearObj = listData.list_year.find((item) =>
    remainingStr.endsWith(item.code),
  );
  if (yearObj) {
    result.year = yearObj.year;
    remainingStr = remainingStr.slice(0, -yearObj.code.length);
  }

  // 4. Tách Máy (Machine) từ ĐẦU chuỗi
  const machineObj = listData.list_extrusion_machine.find((item) =>
    remainingStr.startsWith(item.machine_code),
  );
  if (machineObj) {
    result.machine = machineObj.machine_name;
    remainingStr = remainingStr.slice(machineObj.machine_code.length); // Cắt bỏ phần mã máy ở đầu
  }

  // 5. Phần còn sót lại ở giữa chính là Mã vật liệu (Material Code)
  const materialObj = listData.list_material.find(
    (item) => item.code === remainingStr,
  );
  if (materialObj) {
    result.material = materialObj.brand;
    result.grinding_time = materialObj.grinding_time;
  }

  // 6. Gán ngược dữ liệu vào các ô input trên form
  if (result.machine) document.getElementById("machine").value = result.machine;
  if (result.material)
    document.getElementById("material").value = result.material;
  if (result.grinding_time !== undefined)
    document.getElementById("grinding_time").value = result.grinding_time;

  // 7. Xử lý gán lại ngày tháng
  if (result.day && result.month && result.year) {
    // Định dạng lại ngày tháng thành DD/MM/YYYY (Dựa theo cách bạn split ở code gốc)
    const formattedDay = String(result.day).padStart(2, "0");
    const formattedMonth = String(result.month).padStart(2, "0");
    const dateString = `${formattedDay}/${formattedMonth}/${result.year}`;

    // Tìm input ngày tháng (Hỗ trợ cả name="finish_time" hoặc id="extrusion_date")
    const dateInput =
      document.querySelector('input[name="finish_time"]') ||
      document.getElementById("extrusion_date");
    if (dateInput) {
      dateInput.value = dateString;

      // Chú ý: Nếu thẻ input của bạn là type="date", bạn phải đổi format thành YYYY-MM-DD
      // dateInput.value = `${result.year}-${formattedMonth}-${formattedDay}`;
    }
  }
}
