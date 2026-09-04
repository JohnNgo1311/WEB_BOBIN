document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const searchKeyword = document.getElementById("searchKeyword");
  const filterForm = document.getElementById("filterForm");
  let html5QrcodeScanner = null;

  btnScanQR.addEventListener("click", function () {
    // Đóng camera nếu đang mở
    if (qrReaderDiv.style.display === "block") {
      qrReaderDiv.style.display = "none";
      if (html5QrcodeScanner) {
        html5QrcodeScanner
          .clear()
          .catch((err) => console.error("Lỗi đóng camera", err));
      }
      btnScanQR.innerHTML = "📷 Quét QR";
      return;
    }

    // Bật camera
    qrReaderDiv.style.display = "block";
    btnScanQR.innerHTML = "Đóng camera";

    // Xử lý khi quét thành công
    function onScanSuccess(decodedText, decodedResult) {
      // 1. Điền text vào ô tìm kiếm
      searchKeyword.value = decodedText;

      // 2. Tắt camera
      html5QrcodeScanner.clear();
      qrReaderDiv.style.display = "none";
      btnScanQR.innerHTML = "📷 Quét QR";

      // 3. Tự động click Lọc (Submit form)
      filterForm.submit();
    }

    // Bỏ qua lỗi khi khung hình chưa có QR
    function onScanFailure(error) {
      // console.warn(`QR error = ${error}`);
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
