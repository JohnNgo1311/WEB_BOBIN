<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Scan QR Code - WEB_BOBIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Style cho khung camera */
        #reader {
            width: 100%;
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        input[type="text"] {
            padding: 12px;
            width: 70%;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>

    <!-- Bước QUAN TRỌNG: Gọi file JS từ local, KHÔNG gọi từ internet -->
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
</head>

<body>

    <div class="container">
        <h2>Quét mã QR Bobin</h2>

        <!-- Vùng hiển thị Camera -->
        <div id="reader"></div>

        <!-- Form tìm kiếm -->
        <form action="/WEB_BOBIN/public/index.php" method="GET" id="searchForm">
            <input type="hidden" name="url" value="bobin/listBobinView_QC">
            <div class="search-box">
                <input type="text" id="searchInput" name="keyword" placeholder="Mã QR sẽ hiển thị ở đây..." required>
                <button type="submit" id="btnSubmit">Tìm kiếm</button>
            </div>
        </form>
    </div>

    <script>
        // Hàm này chạy khi quét mã QR thành công
        function onScanSuccess(decodedText, decodedResult) {
            // 1. Đưa nội dung quét được vào ô input
            document.getElementById('searchInput').value = decodedText;

            // 2. Dừng camera sau khi quét thành công (tùy chọn)
            html5QrcodeScanner.clear().then(() => {
                console.log("Đã đóng camera.");
            }).catch(error => {
                console.error("Lỗi khi đóng camera.", error);
            });

            // 3. Tự động submit form (nhấn nút Lọc/Tìm kiếm tự động)
            document.getElementById('searchForm').submit();
        }

        // Hàm này chạy liên tục khi khung hình không tìm thấy mã QR
        function onScanFailure(error) {
            // Do camera quét liên tục từng frame, nên bỏ qua lỗi này để tránh log rác
        }

        // Khởi tạo đối tượng Scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", {
                fps: 10, // Số khung hình trên giây
                qrbox: {
                    width: 250,
                    height: 250
                }, // Kích thước khung nhận diện
                rememberLastUsedCamera: true // Ghi nhớ camera đã dùng lần trước
            },
            /* verbose= */
            false
        );

        // Kích hoạt camera
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>

</body>

</html>