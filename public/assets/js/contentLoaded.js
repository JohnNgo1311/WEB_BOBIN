//TODO 3. LOAD DATA
document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Bắt đầu gọi API...");
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

  // 1. Tìm giá trị lot in từ input #print_lot hoặc label Lot in / Lot vật liệu
  const printLotEl = container.querySelector ? container.querySelector("#print_lot") : null;
  if (printLotEl && printLotEl.value && printLotEl.value.trim() !== "Chưa cập nhật") {
    lotString = printLotEl.value.trim();
  } else if (container.querySelectorAll) {
    const fieldItems = container.querySelectorAll(".field-item");
    for (const item of fieldItems) {
      const label = item.querySelector("label");
      if (label && (label.textContent.trim() === "Lot in" || label.textContent.trim() === "Lot vật liệu")) {
        const valSub = item.querySelector(".val-sub") || item.querySelector("input");
        if (valSub && (valSub.value || valSub.textContent)) {
          lotString = (valSub.value || valSub.textContent).trim();
          break;
        }
      }
    }
  }

  if (!lotString || lotString === "Chưa cập nhật") return;

  const cleanLot = lotString.replace(/\s+/g, '');
  if (cleanLot.length < 5) return;

  const machineChar = cleanLot.slice(0, 1);
  if (Array.isArray(listData.list_extrusion_machine)) {
    const machineObj = listData.list_extrusion_machine.find((item) =>
      String(item.machine_code ?? "").trim() === machineChar
    );
    if (machineObj) {
      const el = container.querySelector ? container.querySelector("#extrusion_machine") : null;
      if (el) {
        el.value = String(machineObj.machine_name ?? "").trim();
      }
    }
  }
}

