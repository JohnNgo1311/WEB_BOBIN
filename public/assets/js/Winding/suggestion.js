//TODO 1. BIẾN TOÀN CỤC (Cập nhật key đúng: list_bobin)
let listData = {
  list_bobin: [],
  list_employee: [],
  list_product: [],
  list_material_lot: [],
  list_extrusion_machine: [],
  list_winding_machine: [],
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
        console.log(`👂 Bắt đầu theo dõi nhập dữ liệu`);
        reversePrintLotAll();
      } else {
        console.error(
          "❌ Lỗi từ API: Truy xuất dữ liệu ListData không thành công.",
        );
      }
    })
    .catch(console.error);
});

// Chạy reversePrintLot cho toàn bộ list khi mới load trang xong
function reversePrintLotAll() {
  const items = document.querySelectorAll(".bobin-item");
  if (items.length > 0) {
    items.forEach((container) => reversePrintLot(container));
  } else {
    reversePrintLot(document); // Chạy cho form đơn lẻ
  }
}

function reversePrintLot(container) {
  let lotString = "";

  // 1. Tìm chính xác giá trị "Lot vật liệu" bằng cách duyệt qua các .field-item
  const fieldItems = container.querySelectorAll(".field-item");
  for (const item of fieldItems) {
    const label = item.querySelector("label");
    // Nếu label có nội dung là "Lot vật liệu"
    if (label && label.textContent.trim() === "Lot vật liệu") {
      const valSub = item.querySelector(".val-sub");
      if (valSub) {
        lotString = valSub.textContent.trim();
      }
      break; // Đã tìm thấy, thoát vòng lặp để tối ưu hiệu suất
    }
  }

  // Nếu không lấy được chuỗi hoặc chuỗi rỗng thì dừng hàm
  if (!lotString) return;

  // 2. Tìm đối tượng máy đùn có mã khớp với phần đầu của chuỗi Lot vật liệu
  const machineObj = listData.list_extrusion_machine.find((item) =>
    lotString.startsWith(item.machine_code),
  );

  // 3. Nếu tìm thấy, gán tên máy vào phần tử đích
  if (machineObj) {
    const el = container.querySelector("#extrusion_machine");
    if (el) {
      el.value = machineObj.machine_name;
    }
  }
}
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
    list_extrusion_machine: "extrusion_machine_name",
    list_winding_machine: "winding_machine_name",
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
function updateFlowTestColor(selectElement) {
  if (selectElement.value === "NG") {
    selectElement.classList.remove("status-ok");
    selectElement.classList.add("status-ng");
  } else {
    selectElement.classList.remove("status-ng");
    selectElement.classList.add("status-ok");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const selectElement = document.getElementById("flow-test");
  if (selectElement) {
    updateFlowTestColor(selectElement);
  }
});

//===========================SUGGESTION CHO NHÂN VIÊN===========================
document.addEventListener("DOMContentLoaded", function () {
  const WindingMachineInputs = document.querySelectorAll(
    ".winding-employee-code",
  );

  WindingMachineInputs.forEach((empInput) => {
    empInput.addEventListener("input", function () {
      const val = this.value.toLowerCase().trim();
      const row = this.closest(".winding-info-row");
      if (!row) return;

      const empName = row.querySelector(".winding-employee-name");

      // ĐÃ SỬA DÒNG NÀY: Dùng parentElement thay vì closest('.winding-input') để tránh nhầm lẫn
      const empBox = this.parentElement.querySelector(".suggestion-box");

      if (!empBox) {
        console.error(
          "Vẫn không tìm thấy suggestion-box. Hãy kiểm tra lại HTML.",
        );
        return;
      }

      empBox.innerHTML = ""; // Xóa dữ liệu cũ

      if (
        !listData ||
        !listData.list_employee ||
        listData.list_employee.length === 0
      )
        return;

      if (!val) {
        empBox.style.display = "none";
        return;
      }

      const matches = listData.list_employee.filter(
        (emp) =>
          (emp.employee_code &&
            emp.employee_code.toLowerCase().includes(val)) ||
          (emp.employee_name && emp.employee_name.toLowerCase().includes(val)),
      );

      // HIỂN THỊ
      if (matches.length > 0) {
        empBox.style.display = "block";
        matches.forEach((item) => {
          const code = item.employee_code;
          const name = item.employee_name;

          const div = document.createElement("div");
          div.className = "suggestion-item";
          div.textContent = `${code} - ${name}`;

          div.onclick = () => {
            this.value = code; // Điền mã
            if (empName) empName.value = name; // Điền tên
            empBox.style.display = "none"; // Ẩn khung
            if (typeof checkBobinStatus === "function") checkBobinStatus(code);
          };
          empBox.appendChild(div);
        });
      } else {
        empBox.style.display = "none";
      }
    });
  });

  // Ẩn khi click ra ngoài
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("winding-employee-code")) {
      document.querySelectorAll(".suggestion-box").forEach((box) => {
        box.style.display = "none";
      });
    }
  });
});

//===========================SUGGESTION CHO MÁY CUỘN ===========================
document.addEventListener("DOMContentLoaded", function () {
  const windingMachineInputs = document.querySelectorAll(
    ".winding-machine-name",
  );

  windingMachineInputs.forEach((machineNameInput) => {
    machineNameInput.addEventListener("input", function () {
      const val = this.value.toLowerCase().trim();
      const row = this.closest(".winding-info-row");
      if (!row) return;

      const empName = row.querySelector(".winding-machine-name");

      const empBox = this.parentElement.querySelector(".suggestion-box");

      if (!empBox) {
        console.error(
          "Vẫn không tìm thấy suggestion-box. Hãy kiểm tra lại HTML.",
        );
        return;
      }

      empBox.innerHTML = ""; // Xóa dữ liệu cũ

      if (
        !listData ||
        !listData.list_winding_machine ||
        listData.list_winding_machine.length === 0
      )
        return;

      if (!val) {
        empBox.style.display = "none";
        return;
      }

      const matches = listData.list_winding_machine.filter(
        (emp) =>
          emp.machine_name && emp.machine_name.toLowerCase().includes(val),
      );

      // HIỂN THỊ
      if (matches.length > 0) {
        empBox.style.display = "block";
        matches.forEach((item) => {
          const name = item.machine_name;

          const div = document.createElement("div");
          div.className = "suggestion-item";
          div.textContent = `${name}`;

          div.onclick = () => {
            this.value = name; // Điền tên máy
            if (empName) empName.value = name; // Điền tên máy vào ô input tương ứng
            empBox.style.display = "none"; // Ẩn khung
            if (typeof checkBobinStatus === "function") checkBobinStatus(name);
          };
          empBox.appendChild(div);
        });
      } else {
        empBox.style.display = "none";
      }
    });
  });
  // Ẩn khi click ra ngoài
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("winding-machine-name")) {
      document.querySelectorAll(".suggestion-box").forEach((box) => {
        box.style.display = "none";
      });
    }
  });
});
