// document.addEventListener("DOMContentLoaded", function () {
//   try {
//     // 1. Nhóm các DOM elements lại thành một object để dễ quản lý
//     const domElements = {
//       fromDate: document.getElementById("fromDate"),
//       toDate: document.getElementById("toDate"),
//       btnFilter: document.getElementById("btnFilter"),
//     };

//     // 2. Kiểm tra an toàn: Nếu không tìm thấy element nào thì dừng chạy script (tránh lỗi trên trang khác)
//     if (
//       !domElements.fromDate ||
//       !domElements.toDate ||
//       !domElements.btnFilter
//     ) {
//       console.warn("Required DOM elements not found");
//       return;
//     }

//     // 3. Cấu hình các hằng số (Configuration)
//     const CONFIG = {
//       baseUrl: "/WEB_BOBIN/public/index.php",
//       targetRoute: "bobin/listBobinHistoryView",
//     };

//     // 4. Tách riêng hàm xử lý logic tạo URL
//     const buildFilterUrl = (fromDate, toDate) => {
//       const params = new URLSearchParams({
//         url: CONFIG.targetRoute,
//         from_date: fromDate,
//         to_date: toDate,
//       });
//       return `${CONFIG.baseUrl}?${params.toString()}`;
//     };

//     // 5. Lắng nghe sự kiện
//     domElements.btnFilter.addEventListener("click", function (e) {
//       try {
//         e.preventDefault();

//         const fromDateVal = domElements.fromDate.value;
//         const toDateVal = domElements.toDate.value;
//         if (!fromDateVal || !toDateVal) {
//           Toast.show(
//             "❌Lỗi: Vui lòng chọn cả ngày bắt đầu và ngày kết thúc!",
//             "error",
//           );
//           return;
//         }
//         if (fromDateVal > toDateVal) {
//           Toast.show(
//             "❌Lỗi: Ngày bắt đầu không được sau ngày kết thúc!",
//             "error",
//           );
//           return;
//         }
//         window.location.href = buildFilterUrl(fromDateVal, toDateVal);
//       } catch (error) {
//         console.error("Error in filter click handler:", error);
//         Toast.show("❌ " + error, "error");
//       }
//     });
//   } catch (error) {
//     console.error("Error initializing script:", error);
//     Toast.show("❌ " + error, "error");
//   }
// });
