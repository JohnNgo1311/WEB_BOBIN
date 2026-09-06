<?php
$capacities = $data['capacities'] ?? [];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý dung lượng Bobin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/listBobinDetail.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">
    <style>
        .capacity-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .capacity-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        .capacity-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .capacity-label {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }

        .capacity-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .capacity-input {
            width: 120px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            outline: none;
        }

        .capacity-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .btn-save-capacity {
            background: #0284c7;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-save-capacity:hover {
            background: #0369a1;
        }

        /* Toast style */
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #22c55e;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            font-weight: 600;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .toast-message.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-error {
            background-color: #ef4444;
        }
    </style>
</head>

<body>

    <h1>Cấu hình dung lượng Bobin thực tế</h1>

    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="/WEB_BOBIN/public/index.php" style="text-decoration:none; color:#0284c7; font-weight:600;">
                ← Quay lại trang đăng nhập
            </a>
        </div>

        <div class="capacity-container">
            <h3 style="margin-top:0; margin-bottom:24px; color:#0f172a; font-size:18px;">Điều chỉnh định mức theo kích
                thước</h3>

            <?php if (empty($capacities)): ?>
                <p style="color: #ef4444;">Chưa có dữ liệu cấu hình trong bảng `bobin_capacity`.</p>
            <?php else: ?>
                <?php foreach ($capacities as $sizeName => $capacityVal): ?>
                    <div class="capacity-row" data-size="<?= htmlspecialchars($sizeName) ?>">
                        <span class="capacity-label"><?= htmlspecialchars($sizeName) ?></span>
                        <div class="capacity-input-group">
                            <input type="number" class="capacity-input" value="<?= (int)$capacityVal ?>" min="0">
                            <button type="button" class="btn-save-capacity" onclick="updateCapacity(this)">Lưu</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thanh Toast thông báo -->
    <div id="toastMessage" class="toast-message" style="display:none;"></div>

    <script>
        function showToast(message, isError = false) {
            const toast = document.getElementById('toastMessage');
            toast.textContent = message;
            toast.className = 'toast-message show ' + (isError ? 'toast-error' : '');
            toast.style.display = 'block';
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.style.display = 'none', 300);
            }, 3000);
        }

        async function updateCapacity(button) {
            const row = button.closest('.capacity-row');
            const sizeName = row.getAttribute('data-size');
            const input = row.querySelector('.capacity-input');
            const newCapacity = parseInt(input.value, 10);

            if (isNaN(newCapacity) || newCapacity < 0) {
                showToast('Vui lòng nhập số lượng hợp lệ!', true);
                return;
            }

            try {
                const response = await fetch('/WEB_BOBIN/public/index.php?url=bobin/updateCapacityAction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        size_name: sizeName,
                        capacity: newCapacity
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message);
                } else {
                    showToast(result.message || 'Lỗi cập nhật!', true);
                }
            } catch (error) {
                showToast('Lỗi kết nối đến máy chủ!', true);
            }
        }
    </script>
</body>

</html>