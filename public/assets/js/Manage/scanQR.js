document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  if (!btnScanQR || !qrReaderDiv) return;

  // Giao diện nút khi ở trạng thái mặc định (Màu xanh Gradient, có icon QR)
  const defaultBtnHTML = `
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7"></rect>
        <rect x="14" y="3" width="7" height="7"></rect>
        <rect x="14" y="14" width="7" height="7"></rect>
        <path d="M3 14h7v7H3z"></path>
    </svg>
    <span>Quét QR</span>
  `;

  // Giao diện nút khi đang mở camera (Màu đỏ Gradient, có icon X)
  const closeBtnHTML = `
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
    <span>Đóng Camera</span>
  `;

  let html5QrcodeScanner = null;

  function stopScanner() {
    if (html5QrcodeScanner) {
      try {
        html5QrcodeScanner.clear().catch(() => {});
      } catch (e) {}
    }
    qrReaderDiv.style.display = "none";
    btnScanQR.innerHTML = defaultBtnHTML;
    btnScanQR.classList.remove("btn-danger");
    btnScanQR.classList.add("btn-scan");
    btnScanQR.style.background = "";
  }

  btnScanQR.addEventListener("click", function () {
    // 1. Trạng thái: Đang mở -> Đóng lại
    if (qrReaderDiv.style.display === "block") {
      stopScanner();
      return;
    }

    // 2. Trạng thái: Đang đóng -> Mở lên
    qrReaderDiv.style.display = "block";
    btnScanQR.innerHTML = closeBtnHTML;
    btnScanQR.classList.remove("btn-scan");
    btnScanQR.classList.add("btn-danger");
    btnScanQR.style.background = "";

    // Hàm chạy khi đọc được mã QR
    function onScanSuccess(decodedText, decodedResult) {
      if (typeof Toast !== "undefined") {
        Toast.show(`Đã quét mã: ${decodedText}`, "success");
      }

      // Tắt camera ngay sau khi quét
      stopScanner();

      // Kiểm tra input mục tiêu trên trang
      const bobinInput = document.getElementById("bobin_identification_code");
      const searchKeyword = document.getElementById("searchKeyword");
      const filterForm = document.getElementById("filterForm");

      if (bobinInput) {
        // Trang Nhập liệu (Đùn)
        bobinInput.value = decodedText;
        bobinInput.dispatchEvent(new Event("input", { bubbles: true }));
        bobinInput.dispatchEvent(new Event("change", { bubbles: true }));
        bobinInput.focus();
      } else if (searchKeyword) {
        // Trang Danh sách / Bộ lọc (QC, Cuộn, Chi tiết, Lịch sử, Chờ hủy, Điều chỉnh đùn)
        searchKeyword.value = decodedText;
        searchKeyword.dispatchEvent(new Event("input", { bubbles: true }));
        if (filterForm) {
          setTimeout(() => {
            filterForm.submit();
          }, 400);
        }
      }
    }

    function onScanFailure(error) {
      // Bỏ qua lỗi rác do camera đang quét
    }

    try {
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
    } catch (err) {
      console.error("Lỗi khởi động máy quét QR:", err);
      if (typeof Toast !== "undefined") {
        Toast.show("Không thể mở máy ảnh. Vui lòng cấp quyền truy cập camera!", "error");
      }
      stopScanner();
    }
  });
});