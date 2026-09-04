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
