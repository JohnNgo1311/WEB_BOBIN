document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const searchKeyword = document.getElementById("searchKeyword");
  const filterForm = document.getElementById("filterForm");
  let html5QrcodeScanner = null;

  btnScanQR.addEventListener("click", function () {
    // 1. Trạng thái: Đang mở -> Đóng
    if (qrReaderDiv.style.display === "block") {
      qrReaderDiv.style.display = "none";
      if (html5QrcodeScanner) {
        html5QrcodeScanner
          .clear()
          .catch((err) => console.error("Lỗi đóng camera", err));
      }

      btnScanQR.innerHTML = "Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default");
      return;
    }

    // 2. Trạng thái: Đang đóng -> Bật
    qrReaderDiv.style.display = "block";

    btnScanQR.innerHTML = "Đóng Camera";
    btnScanQR.classList.remove("btn-scan-default");
    btnScanQR.classList.add("btn-danger");

    function onScanSuccess(decodedText, decodedResult) {
      // Điền dữ liệu
      searchKeyword.value = decodedText;

      // Tắt camera
      html5QrcodeScanner.clear();
      qrReaderDiv.style.display = "none";

      // Trả lại giao diện nút
      btnScanQR.innerHTML = "📷 Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default");

      // Gửi form
      filterForm.submit();
    }

    function onScanFailure(error) {
      // Bỏ qua các lỗi đọc không ra mã
    }

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
