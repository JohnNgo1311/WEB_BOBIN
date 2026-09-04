document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const inputBobinId = document.getElementById("bobin_identification_code");
  let html5QrcodeScanner = null;

  btnScanQR.addEventListener("click", function () {
    // 1. Kiểm tra trạng thái camera: Nếu đang mở thì đóng lại
    if (qrReaderDiv.style.display === "block") {
      qrReaderDiv.style.display = "none";
      if (html5QrcodeScanner) {
        html5QrcodeScanner
          .clear()
          .catch((err) => console.error("Lỗi đóng camera", err));
      }

      // Trả lại text và giao diện màu Gradient xanh mặc định
      btnScanQR.innerHTML = "Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default");
      return;
    }

    // 2. Trạng thái: Đang đóng -> Bật lên
    qrReaderDiv.style.display = "block";

    // Đổi text và sang màu đỏ cảnh báo (Đóng camera)
    btnScanQR.innerHTML = "Đóng Camera";
    btnScanQR.classList.remove("btn-scan-default");
    btnScanQR.classList.add("btn-danger");

    // Hàm chạy khi đọc được mã QR
    function onScanSuccess(decodedText, decodedResult) {
      // 1. Điền text vào ô input
      inputBobinId.value = decodedText;

      // 2. Kích hoạt sự kiện 'input' để các file JS khác (như suggestion.js) nhận diện được sự thay đổi dữ liệu
      inputBobinId.dispatchEvent(
        new Event("input", {
          bubbles: true,
        }),
      );

      // 3. Đóng camera ngay sau khi quét thành công
      html5QrcodeScanner.clear();
      qrReaderDiv.style.display = "none";

      // 4. Trả lại nút về màu Gradient ban đầu
      btnScanQR.innerHTML = "Quét QR";
      btnScanQR.classList.remove("btn-danger");
      btnScanQR.classList.add("btn-scan-default");
    }

    // Hàm chạy khi khung hình chưa có mã (bỏ qua để đỡ rác log)
    function onScanFailure(error) {
      // console.warn(`QR error = ${error}`);
    }

    // Khởi tạo máy quét
    html5QrcodeScanner = new Html5QrcodeScanner(
      "qr-reader",
      {
        fps: 10,
        qrbox: {
          width: 250,
          height: 250,
        },
        rememberLastUsedCamera: true,
      },
      false,
    );

    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
  });
});
