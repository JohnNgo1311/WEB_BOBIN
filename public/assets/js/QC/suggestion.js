/* File: public/assets/js/QC/suggestion.js */

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

  setupInspectorAutocomplete();
});

// 2. GỢI Ý MÃ SỐ VÀ TỰ ĐỘNG ĐIỀN TÊN NHÂN VIÊN QC
function setupInspectorAutocomplete() {
  document.addEventListener("input", function (e) {
    if (!e.target.classList.contains("input-inspector-code")) return;

    const input = e.target;
    const val = input.value.toLowerCase().trim();
    const container = input.closest(".qc-section") || input.closest(".bobin-item");
    if (!container) return;

    const empName = container.querySelector(".input-inspector-name");
    const empBox = input.parentElement.querySelector(".suggestion-box");
    if (!empBox) return;

    // 1. Tự động xóa họ tên nếu người dùng đang xóa/sửa mã
    if (empName) {
      empName.value = "";
    }

    empBox.innerHTML = "";
    if (!listData.list_employee || listData.list_employee.length === 0 || !val) {
      empBox.style.display = "none";
      return;
    }

    // 2. Khớp chính xác (khi quét QR hoặc gõ xong mã)
    const exactMatch = listData.list_employee.find(
      (emp) => emp.employee_code && emp.employee_code.toLowerCase() === val
    );
    if (exactMatch) {
      input.value = exactMatch.employee_code;
      if (empName) empName.value = exactMatch.employee_name;
      empBox.style.display = "none";
      return;
    }

    // 3. Tìm kiếm tương đối hiển thị dropdown
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

  // Đóng box gợi ý khi click ra ngoài
  document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("input-inspector-code")) {
      document.querySelectorAll(".suggestion-box").forEach((box) => {
        box.style.display = "none";
      });
    }
  });
}