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
  list_year: []
};

/* =========================================
   1. TẢI DỮ LIỆU DANH MỤC
========================================= */

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

/* =========================================
   2. GỢI Ý MÁY CUỘN
========================================= */

function setupWindingAutocomplete() {
  document.addEventListener("input", (e) => {
    if (!e.target.classList.contains("winding-machine-name")) {
      return;
    }

    const input = e.target;
    const value = input.value.toLowerCase().trim();
    const box = input.parentElement.querySelector(".suggestion-box");

    if (!box) {
      return;
    }

    box.innerHTML = "";

    if (
      !listData.list_winding_machine ||
      listData.list_winding_machine.length === 0 ||
      !value
    ) {
      box.style.display = "none";
      return;
    }

    const exactMatch = listData.list_winding_machine.find(
      (machine) =>
        machine.machine_name &&
        machine.machine_name.toLowerCase() === value
    );

    if (exactMatch) {
      input.value = exactMatch.machine_name;
      box.style.display = "none";
      return;
    }

    const matches = listData.list_winding_machine.filter(
      (machine) =>
        machine.machine_name &&
        machine.machine_name.toLowerCase().includes(value)
    );

    if (matches.length === 0) {
      box.style.display = "none";
      return;
    }

    box.style.display = "block";

    matches.forEach((item) => {
      const suggestion = document.createElement("div");

      suggestion.className = "suggestion-item";
      suggestion.textContent = item.machine_name;
      suggestion.onclick = () => {
        input.value = item.machine_name;
        box.style.display = "none";
      };

      box.appendChild(suggestion);
    });
  });

  /* ĐÓNG BOX GỢI Ý KHI CLICK RA NGOÀI */
  document.addEventListener("click", (e) => {
    if (
      e.target.classList.contains("winding-employee-code") ||
      e.target.classList.contains("winding-machine-name")
    ) {
      return;
    }

    document.querySelectorAll(".suggestion-box").forEach((box) => {
      box.style.display = "none";
    });
  });
}