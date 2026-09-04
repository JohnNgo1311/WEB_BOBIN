// Hàm chuyên dụng để tìm và vẽ mã QR
function initQRCodes() {
  // Tìm tất cả các thẻ có class qr-code-box mà CHƯA được vẽ QR (tránh vẽ đè)
  var qrElements = document.querySelectorAll(".qr-code-box:not(.qr-rendered)");

  qrElements.forEach(function (el) {
    var textToEncode = el.getAttribute("data-qr");

    if (textToEncode && textToEncode.trim() !== "") {
      // 1. Chèn vòng tròn loading vào trước
      el.innerHTML = '<div class="qr-loading"></div>';

      // 2. Dùng setTimeout trì hoãn khoảng 50ms để trình duyệt kịp hiển thị hiệu ứng xoay
      setTimeout(function () {
        // Xóa loading đi trước khi vẽ mã QR
        el.innerHTML = "";

        // Khởi tạo QR Code
        new QRCode(el, {
          text: textToEncode,
          width: 145,
          height: 145,
          colorDark: "#000000", // Màu của nét QR
          colorLight: "#ffffff", // Màu nền tảng của lõi QR (Trắng)
          correctLevel: QRCode.CorrectLevel.H,
        });

        // Đánh dấu là đã vẽ để không bị vẽ lặp lại nếu gọi hàm này nhiều lần
        el.classList.add("qr-rendered");
      }, 50); // 50 milliseconds
    }
  });
}

// Tự động chạy hàm khi trang đã load xong HTML
document.addEventListener("DOMContentLoaded", function () {
  initQRCodes();
});
