document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const searchKeyword = document.getElementById("searchKeyword");
  const filterForm = document.getElementById("filterForm");
  let html5QrcodeScanner = null;

  // Giao diện nút khi ở trạng thái mặc định (Màu xanh, có icon QR)
  const defaultBtnHTML = `
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><path d="M3 14h7v7H3z"></path>
    </svg>
    Quét QR
  `;

  // Giao diện nút khi đang mở camera (Màu đỏ, có icon X)
  const closeBtnHTML = `
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
    Đóng Camera
  `;

  if (btnScanQR) {
    btnScanQR.addEventListener("click", function () {
      // 1. Trạng thái: Đang mở -> Đóng lại
      if (qrReaderDiv.style.display === "block") {
        qrReaderDiv.style.display = "none";
        if (html5QrcodeScanner) {
          html5QrcodeScanner
            .clear()
            .catch((err) => console.error("Lỗi đóng camera", err));
        }

        // Trả lại text có icon và class màu Gradient xanh mới
        btnScanQR.innerHTML = defaultBtnHTML;
        btnScanQR.classList.remove("btn-danger");
        btnScanQR.classList.add("btn-scan");
        // Xóa inline style phòng ngừa
        btnScanQR.style.background = "";
        return;
      }

      // 2. Trạng thái: Đang đóng -> Bật lên
      qrReaderDiv.style.display = "block";

      // Đổi text và vứt class màu xanh đi, thay bằng class màu đỏ
      btnScanQR.innerHTML = closeBtnHTML;
      btnScanQR.classList.remove("btn-scan");
      btnScanQR.classList.add("btn-danger");
      // Ép màu đỏ hiện đại phù hợp với giao diện mới 
      btnScanQR.style.background = "#ef4444";

      // Hàm chạy khi đọc được mã QR
      function onScanSuccess(decodedText, decodedResult) {
        if (searchKeyword) {
          searchKeyword.value = decodedText;
        }

        // Tắt camera
        html5QrcodeScanner.clear();
        qrReaderDiv.style.display = "none";

        // Quét xong cũng phải trả lại nút màu Gradient xanh và icon
        btnScanQR.innerHTML = defaultBtnHTML;
        btnScanQR.classList.remove("btn-danger");
        btnScanQR.classList.add("btn-scan");
        btnScanQR.style.background = "";

        // Tự động submit form nếu có
        if (filterForm) {
          filterForm.submit();
        }
      }

      function onScanFailure(error) {
        // Bỏ qua lỗi rác do camera lấy nét liên tục
      }

      // Khởi tạo máy quét
      html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        {
          fps: 10,
          qrbox: { width: 250, height: 250 },
          rememberLastUsedCamera: true,
        },
        false
      );

      html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    });
  }
});