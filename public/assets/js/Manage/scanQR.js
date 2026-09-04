document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const searchKeyword = document.getElementById("searchKeyword");
  const filterForm = document.getElementById("filterForm");
  let html5QrcodeScanner = null;
  btnScanQR.addEventListener("click", function () {
    // 1. Trạng thái: Đang mở -> Đóng lại
    if (qrReaderDiv.style.display === "block") {
      qrReaderDiv.style.display = "none";
      if (html5QrcodeScanner) {
        html5QrcodeScanner
          .clear()
          .catch((err) => console.error("Lỗi đóng camera", err));
      }

      // Trả lại text và màu Gradient xanh
      btnScanQR.innerHTML = "Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default"); // <--- Sửa ở đây
      return;
    }

    // 2. Trạng thái: Đang đóng -> Bật lên
    qrReaderDiv.style.display = "block";

    // Đổi text và vứt class màu Gradient đi, thay bằng class màu đỏ
    btnScanQR.innerHTML = "Đóng Camera";
    btnScanQR.classList.remove("btn-scan-default"); // <--- Sửa ở đây
    btnScanQR.classList.add("btn-danger");

    // Hàm chạy khi đọc được mã QR
    function onScanSuccess(decodedText, decodedResult) {
      searchKeyword.value = decodedText; // Hoặc inputBobinId.value tùy file

      // Tắt camera
      html5QrcodeScanner.clear();
      qrReaderDiv.style.display = "none";

      // Quét xong cũng phải trả lại nút màu Gradient xanh
      btnScanQR.innerHTML = "Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default"); // <--- Sửa ở đây

      // Tự động submit (tùy trang)
      filterForm.submit();
    }

    function onScanFailure(error) {
      // Bỏ qua lỗi rác do quét liên tục
    }

    // Khởi tạo máy quét
    html5QrcodeScanner = new Html5QrcodeScanner(
      "qr-reader",
      {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        rememberLastUsedCamera: true,
      },
      false,
    );

    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
  });
});
