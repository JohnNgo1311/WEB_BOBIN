/* File: public/assets/js/Winding/suggestion.js */

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

// 1. TẢI DỮ LIỆU DANH MỤC
document.addEventListener("DOMContentLoaded", () => {
  fetch(API_BASE_URL + "listdata/getListData")
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        listData = response;
      } else {
        console.error("❌ Truy xuất dữ liệu ListData không thành công.");
      }
    })
    .catch(console.error);

  setupWindingAutocomplete();
});

// 2. TỰ ĐỘNG GỢI Ý & ĐIỀN NHÂN VIÊN, MÁY CUỘN
function setupWindingAutocomplete() {
  // Gợi ý Mã nhân viên cuộn -> Tên nhân viên
  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("winding-employee-code")) return;

    const input = e.target;
    const val = input.value.toLowerCase().trim();
    const container = input.closest(".winding-action-col") || input.closest(".bobin-item");
    if (!container) return;

    const empName = container.querySelector(".winding-employee-name");
    const empBox = input.parentElement.querySelector(".suggestion-box");
    if (!empBox) return;

    // Tự động xóa họ tên nếu đang sửa mã
    if (empName) empName.value = "";

    empBox.innerHTML = "";
    if (!listData.list_employee || listData.list_employee.length === 0 || !val) {
      empBox.style.display = "none";
      return;
    }

    // Khớp chính xác (quét barcode hoặc gõ xong)
    const exactMatch = listData.list_employee.find(
      (emp) => emp.employee_code && emp.employee_code.toLowerCase() === val
    );
    if (exactMatch) {
      input.value = exactMatch.employee_code;
      if (empName) empName.value = exactMatch.employee_name;
      empBox.style.display = "none";
      return;
    }

    // Hiển thị danh sách gợi ý tương đối
    const matches = listData.list_employee.filter(
      (emp) =>
        (emp.employee_code && emp.employee_code.toLowerCase().includes(val)) ||
        (emp.employee_name && emp.employee_name.toLowerCase().includes(val))
    );

    if (matches.length > 0) {
      empBox.style.display = "block";
      matches.forEach((item) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = `${item.employee_code} - ${item.employee_name}`;
        div.onclick = () => {
          input.value = item.employee_code;
          if (empName) empName.value = item.employee_name;
          empBox.style.display = "none";
        };
        empBox.appendChild(div);
      });
    } else {
      empBox.style.display = "none";
    }
  });

  // Gợi ý Máy cuộn
  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("winding-machine-name")) return;

    const input = e.target;
    const val = input.value.toLowerCase().trim();
    const box = input.parentElement.querySelector(".suggestion-box");
    if (!box) return;

    box.innerHTML = "";
    if (!listData.list_winding_machine || listData.list_winding_machine.length === 0 || !val) {
      box.style.display = "none";
      return;
    }

    // Khớp chính xác
    const exactMatch = listData.list_winding_machine.find(
      (m) => m.machine_name && m.machine_name.toLowerCase() === val
    );
    if (exactMatch) {
      input.value = exactMatch.machine_name;
      box.style.display = "none";
      return;
    }

    const matches = listData.list_winding_machine.filter(
      (m) => m.machine_name && m.machine_name.toLowerCase().includes(val)
    );

    if (matches.length > 0) {
      box.style.display = "block";
      matches.forEach((item) => {
        const div = document.createElement("div");
        div.className = "suggestion-item";
        div.textContent = item.machine_name;
        div.onclick = () => {
          input.value = item.machine_name;
          box.style.display = "none";
        };
        box.appendChild(div);
      });
    } else {
      box.style.display = "none";
    }
  });

  // Đóng box gợi ý khi click ra ngoài
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("winding-employee-code") && !e.target.classList.contains("winding-machine-name")) {
      document.querySelectorAll(".suggestion-box").forEach((box) => {
        box.style.display = "none";
      });
    }
  });
}